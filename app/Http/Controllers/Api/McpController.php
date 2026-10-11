<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\VatRateChange;
use App\Services\ViesValidationService;
use App\Support\Mcp\VatMcpServer;
use App\Support\Vat\VatCalculation;
use App\Support\Vat\VatMode;
use App\Support\Vat\VatNumberFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use stdClass;
use Throwable;

/**
 * Stateless MCP server over Streamable HTTP: every POST carries one JSON-RPC message
 * (or a batch for 2025-03-26 clients) and is answered with application/json.
 */
class McpController extends Controller
{
    private const COUNTRY_ALIASES = ['EL' => 'GR', 'UK' => 'GB'];

    public function __construct(
        private ViesValidationService $viesService
    ) {}

    public function handle(Request $request): JsonResponse|Response
    {
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response()->json($this->error(null, -32700, 'Parse error'));
        }

        if ($payload === [] || ! array_is_list($payload)) {
            $reply = $this->dispatch($payload, $request);

            return $reply === null ? response()->noContent(202) : response()->json($reply);
        }

        $replies = array_values(array_filter(array_map(fn (mixed $message) => $this->dispatch($message, $request), $payload)));

        return $replies === [] ? response()->noContent(202) : response()->json($replies);
    }

    /**
     * @return array<string, mixed>|null null for notifications and client responses, which get no reply
     */
    private function dispatch(mixed $message, Request $request): ?array
    {
        if (! is_array($message) || ($message['jsonrpc'] ?? null) !== '2.0') {
            return $this->error(is_array($message) ? ($message['id'] ?? null) : null, -32600, 'Invalid Request');
        }

        if (! isset($message['method'])) {
            return array_key_exists('result', $message) || array_key_exists('error', $message)
                ? null
                : $this->error($message['id'] ?? null, -32600, 'Invalid Request');
        }

        if (! array_key_exists('id', $message)) {
            return null;
        }

        $id = $message['id'];
        $params = is_array($message['params'] ?? null) ? $message['params'] : [];

        return match ($message['method']) {
            'initialize' => $this->result($id, [
                'protocolVersion' => VatMcpServer::negotiate($params['protocolVersion'] ?? null),
                'capabilities' => ['tools' => ['listChanged' => false]],
                'serverInfo' => ['name' => VatMcpServer::NAME, 'title' => VatMcpServer::TITLE, 'version' => VatMcpServer::VERSION],
                'instructions' => VatMcpServer::instructions(),
            ]),
            'ping' => $this->result($id, new stdClass),
            'tools/list' => $this->result($id, ['tools' => VatMcpServer::tools()]),
            'tools/call' => $this->callTool($id, $params, $request),
            default => $this->error($id, -32601, 'Method not found: '.(is_string($message['method']) ? $message['method'] : '')),
        };
    }

    private function callTool(mixed $id, array $params, Request $request): array
    {
        $name = $params['name'] ?? null;
        $arguments = is_array($params['arguments'] ?? null) ? $params['arguments'] : [];

        if (! is_string($name) || ! in_array($name, VatMcpServer::toolNames(), true)) {
            return $this->error($id, -32602, 'Unknown tool: '.(is_string($name) ? $name : ''));
        }

        return $this->result($id, match ($name) {
            'get_all_vat_rates' => $this->allRates(),
            'get_country_vat_rate' => $this->countryRates($arguments),
            'calculate_vat' => $this->calculate($arguments),
            'compare_vat_rates' => $this->compare($arguments),
            'get_vat_rate_changes' => $this->rateChanges($arguments),
            'validate_vat_number' => $this->validateNumber($arguments, $request),
        });
    }

    private function allRates(): array
    {
        $countries = Cache::remember('mcp_all_vat_rates_v2', 600, fn () => Country::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country) => $this->describeCountry($country))
            ->all());

        return $this->toolResult(['count' => count($countries), 'countries' => $countries]);
    }

    private function countryRates(array $arguments): array
    {
        $country = $this->findCountry($arguments['country'] ?? null);

        if (! $country) {
            return $this->countryNotFound($arguments['country'] ?? null);
        }

        return $this->toolResult($this->describeCountry($country));
    }

    private function calculate(array $arguments): array
    {
        $amount = $arguments['amount'] ?? null;
        $mode = $arguments['mode'] ?? 'add';
        $rateType = $arguments['rate_type'] ?? 'standard';

        if (! is_numeric($amount) || (float) $amount < 0) {
            return $this->toolError('"amount" must be a number of zero or more.');
        }

        if (! in_array($mode, ['add', 'remove'], true) || ! in_array($rateType, ['standard', 'reduced', 'super_reduced', 'parking'], true)) {
            return $this->toolError('"mode" must be add or remove, and "rate_type" standard, reduced, super_reduced or parking.');
        }

        $country = $this->findCountry($arguments['country'] ?? null);

        if (! $country) {
            return $this->countryNotFound($arguments['country'] ?? null);
        }

        $rate = $country->rateForType($rateType);

        if ($rate === null) {
            return $this->toolError("{$country->name} has no {$rateType} VAT rate.");
        }

        $calculation = VatCalculation::make((float) $amount, $rate, $mode === 'remove' ? VatMode::Include : VatMode::Exclude);

        return $this->toolResult([
            'country' => $country->name,
            'iso_code' => $country->iso_code,
            'mode' => $mode,
            'rate_type' => $rateType,
            'rate_percent' => $rate,
            'currency' => $country->currencyCode(),
            'net_amount' => $calculation->net,
            'vat_amount' => $calculation->vat,
            'gross_amount' => $calculation->gross,
        ]);
    }

    private function compare(array $arguments): array
    {
        $queries = array_values(array_filter((array) ($arguments['countries'] ?? []), fn (mixed $query) => is_string($query) && trim($query) !== ''));

        if (count($queries) < 2) {
            return $this->toolError('Give at least two countries to compare.');
        }

        $countries = array_map(function (string $query) {
            $country = $this->findCountry($query);

            return $country ? $this->describeCountry($country) : ['query' => $query, 'error' => 'Country not found'];
        }, array_slice($queries, 0, 10));

        return $this->toolResult(['countries' => $countries]);
    }

    private function rateChanges(array $arguments): array
    {
        $country = null;

        if (filled($arguments['country'] ?? null)) {
            $country = $this->findCountry($arguments['country']);

            if (! $country) {
                return $this->countryNotFound($arguments['country']);
            }
        }

        $upcoming = filter_var($arguments['upcoming_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $limit = max(1, min(50, (int) ($arguments['limit'] ?? 20)));

        $changes = VatRateChange::query()
            ->with('country:id,name,iso_code,slug')
            ->whereHas('country')
            ->when($country, fn ($query) => $query->where('country_id', $country->id))
            ->when($upcoming, fn ($query) => $query->whereDate('change_date', '>=', today())->orderBy('change_date'))
            ->unless($upcoming, fn ($query) => $query->orderByDesc('change_date'))
            ->limit($limit)
            ->get()
            ->map(fn (VatRateChange $change) => [
                'country' => $change->country->name,
                'iso_code' => $change->country->iso_code,
                'rate_type' => $change->rate_type,
                'old_rate' => $change->old_rate === null ? null : (float) $change->old_rate,
                'new_rate' => $change->new_rate === null ? null : (float) $change->new_rate,
                'effective_date' => $change->change_date?->toDateString(),
                'direction' => $change->change_direction,
                'description' => $change->description,
                'source_url' => $change->source_url,
            ])
            ->all();

        return $this->toolResult(['count' => count($changes), 'changes' => $changes]);
    }

    private function validateNumber(array $arguments, Request $request): array
    {
        $countryCode = strtoupper(trim((string) ($arguments['country_code'] ?? '')));
        $vatNumber = preg_replace('/[\s.\-]/', '', (string) ($arguments['vat_number'] ?? ''));

        if (! preg_match('/^[A-Z]{2}$/', $countryCode)) {
            return $this->toolError('"country_code" must be the two-letter prefix of the VAT number, such as DE or FR. Greece uses EL.');
        }

        if (str_starts_with(strtoupper($vatNumber), $countryCode)) {
            $vatNumber = substr($vatNumber, 2);
        }

        if (! preg_match('/^[A-Za-z0-9+*]{2,14}$/', $vatNumber)) {
            return $this->toolError('"vat_number" must contain 2 to 14 letters or digits.');
        }

        $key = 'mcp-vies:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, VatMcpServer::VIES_CHECKS_PER_MINUTE)) {
            return $this->toolError('Too many VAT number checks. Try again in '.RateLimiter::availableIn($key).' seconds.');
        }

        RateLimiter::hit($key, 60);

        try {
            $result = $this->viesService->validate($countryCode, $vatNumber);
        } catch (Throwable $exception) {
            report($exception);

            return $this->toolError('The VIES service is temporarily unavailable. Please try again shortly.');
        }

        if (isset($result['error'])) {
            return $this->toolError($result['error']);
        }

        return $this->toolResult(array_filter([
            'valid' => (bool) ($result['valid'] ?? false),
            'country_code' => $result['country_code'] ?? $countryCode,
            'vat_number' => $result['vat_number'] ?? $vatNumber,
            'name' => $result['name'] ?? null,
            'address' => $result['address'] ?? null,
            'request_date' => $result['request_date'] ?? null,
            'source' => $result['source'] ?? 'vies',
            'warning' => $result['warning'] ?? null,
        ], fn (mixed $value) => $value !== null));
    }

    private function describeCountry(Country $country): array
    {
        return [
            'country' => $country->name,
            'iso_code' => $country->iso_code,
            'slug' => $country->slug,
            'eu_member' => (bool) $country->is_eu_member,
            'currency' => $country->currencyCode(),
            'rates' => $country->apiRates(),
            'vat_number' => VatNumberFormat::forCountry($country),
            'last_updated' => $country->updated_at?->toIso8601String(),
        ];
    }

    private function findCountry(mixed $query): ?Country
    {
        if (! is_string($query) || trim($query) === '') {
            return null;
        }

        $query = trim($query);
        $code = self::COUNTRY_ALIASES[strtoupper($query)] ?? strtoupper($query);

        return Country::query()
            ->where('iso_code', $code)
            ->orWhere('slug', strtolower($query))
            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower($query)])
            ->first();
    }

    private function countryNotFound(mixed $query): array
    {
        return $this->toolError('No country matches "'.(is_string($query) ? $query : '').'". Use an English name, an ISO code such as DE, or a slug such as czech-republic.');
    }

    private function toolResult(array $data): array
    {
        return [
            'content' => [['type' => 'text', 'text' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]],
            'structuredContent' => $data,
            'isError' => false,
        ];
    }

    private function toolError(string $message): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $message]],
            'isError' => true,
        ];
    }

    private function result(mixed $id, array|stdClass $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function error(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
