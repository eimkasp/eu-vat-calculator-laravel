#!/usr/bin/env bash
# Reproduces docs/benchmarks/v3-claude-opus-5-5/<label>.json for the commit that is checked out.
# Usage: docs/benchmarks/v3-claude-opus-5-5/tools/run.sh baseline
set -euo pipefail

LABEL="${1:-baseline}"
ROOT="$(cd "$(dirname "$0")/../../../.." && pwd)"
TOOLS="$ROOT/docs/benchmarks/v3-claude-opus-5-5/tools"
RAW="$ROOT/storage/app/benchmarks/$LABEL"
PORT="${PORT:-8123}"
BASE="http://127.0.0.1:$PORT"

mkdir -p "$RAW"
cd "$ROOT"

# 1. A production-like build with the repository's own data.
composer install --no-interaction --prefer-dist
yarn install --frozen-lockfile
[ -f .env ] || cp .env.example .env
grep -q '^APP_KEY=base64' .env || php artisan key:generate --force
sed -i "s#^APP_ENV=.*#APP_ENV=staging#; s#^APP_DEBUG=.*#APP_DEBUG=false#; s#^APP_URL=.*#APP_URL=$BASE#; s#^CACHE_STORE=.*#CACHE_STORE=file#; s#^SESSION_DRIVER=.*#SESSION_DRIVER=file#; s#^QUEUE_CONNECTION=.*#QUEUE_CONNECTION=sync#" .env
rm -f database/database.sqlite && touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force
php artisan tinker --execute 'dispatch_sync(new App\Jobs\GenerateVatRateChanges);'
yarn build
php artisan optimize

# 2. Repository health (tests run without the cached configuration).
php artisan optimize:clear >/dev/null
START=$(date +%s%N)
TEST_EXIT=0
TEST_OUTPUT="$(php artisan test 2>&1)" || TEST_EXIT=$?
END=$(date +%s%N)
PINT_EXIT=0
PINT_OUTPUT="$(./vendor/bin/pint --test 2>&1)" || PINT_EXIT=$?
php artisan optimize >/dev/null
node -e '
const [out, pint, wall, testExit, pintExit] = [process.argv[1], process.argv[2], Number(process.argv[3]) / 1000, Number(process.argv[4]), Number(process.argv[5])];
const plain = (text) => text.replace(/\x1b\[[0-9;]*m/g, "");
const tests = plain(out).match(/Tests:\s+(.*?)\s+\((\d+) assertions\)/);
const duration = plain(out).match(/Duration:\s+([\d.]+)s/);
const counts = Object.fromEntries([...(tests?.[1] ?? "").matchAll(/(\d+) (\w+)/g)].map((m) => [m[2], Number(m[1])]));
console.log(JSON.stringify({
  command: "php artisan test",
  passed: testExit === 0,
  tests: Object.values(counts).reduce((a, b) => a + b, 0),
  results: counts,
  assertions: Number(tests?.[2] ?? 0),
  reportedDurationSeconds: Number(duration?.[1] ?? 0),
  wallSeconds: Number(wall.toFixed(2)),
  pint: { command: "./vendor/bin/pint --test", passed: pintExit === 0, output: plain(pint).trim().split("\n").slice(-1)[0] },
}, null, 2));
' "$TEST_OUTPUT" "$PINT_OUTPUT" "$(( (END - START) / 1000000 ))" "$TEST_EXIT" "$PINT_EXIT" > "$RAW/repository.json"

# 3. Serve it and measure.
PHP_CLI_SERVER_WORKERS=6 php artisan serve --host=127.0.0.1 --port="$PORT" > "$RAW/serve.log" 2>&1 &
SERVER=$!
trap 'kill $SERVER 2>/dev/null || true' EXIT
until curl -fs "$BASE/up" >/dev/null; do sleep 0.5; done

(cd "$TOOLS" && PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1 npm install --no-audit --no-fund >/dev/null)
PATHS=$(node -e 'import("'"$TOOLS"'/pages.mjs").then(({ PAGES, LOCALES, localized }) => console.log(PAGES.flatMap((p) => LOCALES.map((l) => localized(p.path, l))).join(" ")))')
node "$TOOLS/measure.mjs" --base="$BASE" --runs=3 --out="$RAW/front-end.json"
node "$TOOLS/site.mjs" --base="$BASE" --out="$RAW/site.json"
# shellcheck disable=SC2086
php "$TOOLS/backend.php" $PATHS > "$RAW/backend.json"

node "$TOOLS/compile.mjs" --raw="$RAW" --label="$LABEL" --out="$ROOT/docs/benchmarks/v3-claude-opus-5-5/$LABEL.json"
echo "wrote docs/benchmarks/v3-claude-opus-5-5/$LABEL.json"
