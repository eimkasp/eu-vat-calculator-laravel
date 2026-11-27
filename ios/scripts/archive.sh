#!/bin/sh
# Archives the app and exports the build that App Store Connect takes: ios/build/export/EUVATInfo.ipa.
# Nothing is uploaded. The team comes from ios/Config/Local.xcconfig; Xcode must be signed in to it,
# because the export asks Apple for the App Store signing of io.businesspress.vat.
#
#   ios/scripts/archive.sh
set -eu

cd "$(dirname "$0")/.."

TEAM="$(sed -n 's/^DEVELOPMENT_TEAM *= *//p' Config/Local.xcconfig 2>/dev/null | tr -d ' ')"

if [ -z "$TEAM" ]; then
    echo "Put DEVELOPMENT_TEAM = <your team> in ios/Config/Local.xcconfig first." >&2
    exit 1
fi

xcodegen generate
rm -rf build/EUVATInfo.xcarchive build/export

xcodebuild -project EUVATInfo.xcodeproj -scheme EUVATInfo -configuration Release \
    -destination 'generic/platform=iOS' -derivedDataPath build/device \
    -archivePath build/EUVATInfo.xcarchive -allowProvisioningUpdates -quiet archive

cat > build/ExportOptions.plist <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>method</key>
	<string>app-store-connect</string>
	<key>destination</key>
	<string>export</string>
	<key>signingStyle</key>
	<string>automatic</string>
	<key>teamID</key>
	<string>$TEAM</string>
	<key>manageAppVersionAndBuildNumber</key>
	<false/>
	<key>uploadSymbols</key>
	<true/>
</dict>
</plist>
PLIST

xcodebuild -exportArchive -archivePath build/EUVATInfo.xcarchive -exportPath build/export \
    -exportOptionsPlist build/ExportOptions.plist -allowProvisioningUpdates -quiet

echo "Exported $(pwd)/build/export/EUVATInfo.ipa"
