<?php

namespace App\Console\Commands;

use App\Support\EntityMap\EntityMap;
use App\Support\EntityMap\PassageChecker;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyEntityMap extends Command
{
    protected $signature = 'entity-map:verify {--live : Fetch the pages from the canonical host instead of this application}';

    protected $description = 'Check every passage of the entity map against the page it cites';

    public function handle(PassageChecker $checker, Kernel $kernel): int
    {
        $base = rtrim((string) config('seo.canonical_url'), '/');

        $problems = $checker->problems(function (string $path) use ($base, $kernel): ?string {
            if ($this->option('live')) {
                $response = Http::timeout(20)->withHeaders(['User-Agent' => 'EntityMapVerifier'])->get($base.$path);

                return $response->successful() ? $response->body() : null;
            }

            $response = $kernel->handle(Request::create($path));

            return $response->isSuccessful() ? (string) $response->getContent() : null;
        });

        $count = count(EntityMap::passages());

        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->error($problem);
            }

            return self::FAILURE;
        }

        $this->info("All {$count} passages found on their pages.");

        return self::SUCCESS;
    }
}
