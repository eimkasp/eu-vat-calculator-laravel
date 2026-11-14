#!/bin/sh
# Refreshes the data the app ships with, so a first launch without a connection still shows rates.
#
#   ios/scripts/refresh-seed.sh                      # from production
#   EU_VAT_BASE_URL=http://127.0.0.1:8000 ios/scripts/refresh-seed.sh
set -eu

BASE="${EU_VAT_BASE_URL:-https://vat.businesspress.io}"
OUT="$(cd "$(dirname "$0")/.." && pwd)/EUVATInfo/Resources/Seed/snapshot.json"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

curl -fsS -m 30 -H 'Accept: application/json' "$BASE/api/v1/countries" -o "$TMP/countries.json"
curl -fsS -m 30 -H 'Accept: application/json' "$BASE/api/vat-changes" -o "$TMP/changes.json"

python3 - "$TMP" "$OUT" <<'PY'
import json, sys
from datetime import datetime, timezone
from pathlib import Path

tmp, out = Path(sys.argv[1]), Path(sys.argv[2])
countries = json.loads((tmp / "countries.json").read_text())["data"]
changes = json.loads((tmp / "changes.json").read_text())

if len(countries) < 27 or not all("rates" in country for country in countries):
    sys.exit("The countries response looks incomplete; the seed was left as it was.")

out.parent.mkdir(parents=True, exist_ok=True)
out.write_text(json.dumps({
    "fetched_at": datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ"),
    "countries": countries,
    "changes": changes,
}, ensure_ascii=False, indent=1) + "\n")
print(f"Wrote {len(countries)} countries and {len(changes)} changes to {out}")
PY
