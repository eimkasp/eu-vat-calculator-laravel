#!/bin/sh
# Takes the App Store screenshots from the UI tests, at the two sizes App Store Connect asks for:
# a 6.9-inch iPhone (1320 x 2868) and a 13-inch iPad (2064 x 2752). The plain screenshots land in
# ios/AppStore/screenshots/raw, numbered in the order they should appear in the listing, and
# frame-screenshots.py then writes the captioned images of the listing to ios/AppStore/screenshots/en-US,
# with the required 6.3-inch iPhone set (1206 x 2622) framed from the 6.9-inch screenshots.
#
#   ios/scripts/store-screenshots.sh
#
# Each size runs on a simulator made for the run and deleted after it, so nothing else on the
# machine is using the device while the tests drive it.
set -eu

cd "$(dirname "$0")/.."

OUT="AppStore/screenshots/raw"
# Names the UI tests give their screenshots, in listing order.
SHOTS="01-rates 05-calculator 02-country 07-compare 09-history 06-validator-ready"
RUNTIME="$(xcrun simctl list runtimes | awk '/^iOS/ { id = $NF } END { print id }')"
DEVICE=""

cleanup() {
    if [ -n "$DEVICE" ]; then
        xcrun simctl shutdown "$DEVICE" >/dev/null 2>&1 || true
        xcrun simctl delete "$DEVICE" >/dev/null 2>&1 || true
    fi
}
trap cleanup EXIT

shoot() {
    prefix="$1"
    type="$2"

    DEVICE="$(xcrun simctl create "EU VAT Store $prefix" "$type" "$RUNTIME")"
    xcrun simctl boot "$DEVICE"
    xcrun simctl bootstatus "$DEVICE" -b >/dev/null
    xcrun simctl status_bar "$DEVICE" override --time "9:41" --dataNetwork wifi --wifiMode active --wifiBars 3 \
        --cellularMode active --cellularBars 4 --batteryState charged --batteryLevel 100

    rm -rf "build/store-$prefix.xcresult" "build/store-$prefix"
    xcodebuild -project EUVATInfo.xcodeproj -scheme EUVATInfo -destination "platform=iOS Simulator,id=$DEVICE" \
        -derivedDataPath build -resultBundlePath "build/store-$prefix.xcresult" \
        -only-testing:EUVATInfoUITests -skip-testing:EUVATInfoUITests/SmokeTests/testHomeScreenShortcutOpensATool \
        -quiet test || echo "Some UI tests failed on $type; keeping the screenshots that were taken." >&2

    xcrun xcresulttool export attachments --path "build/store-$prefix.xcresult" --output-path "build/store-$prefix" >/dev/null

    python3 - "build/store-$prefix" "$OUT" "$prefix" $SHOTS <<'PY'
import json, shutil, sys
from pathlib import Path

source, out, prefix, shots = Path(sys.argv[1]), Path(sys.argv[2]), sys.argv[3], sys.argv[4:]
out.mkdir(parents=True, exist_ok=True)
attachments = [a for test in json.loads((source / "manifest.json").read_text()) for a in test.get("attachments", [])]

for position, shot in enumerate(shots, start=1):
    match = next((a for a in attachments if a.get("suggestedHumanReadableName", "").startswith(shot + "_")), None)

    if match is None:
        print(f"Missing {shot} for {prefix}", file=sys.stderr)
        continue

    shutil.copyfile(source / match["exportedFileName"], out / f"{prefix}-{position}-{shot[3:]}.png")
PY

    cleanup
    DEVICE=""
}

xcodegen generate >/dev/null
rm -rf "$OUT"
shoot iphone-69 "iPhone 17 Pro Max"
shoot ipad-13 "iPad Pro 13-inch (M5)"

python3 scripts/frame-screenshots.py
ls AppStore/screenshots/en-US
