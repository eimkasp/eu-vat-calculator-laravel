# EU VAT Info for iOS

A native SwiftUI app for iPhone and iPad that reads the same public API as the website: VAT rates for every
country the site covers, a calculator, the VIES VAT number validator, country comparison and the history of
rate changes. No account, no tracking, no third-party code.

| Tab | What it does | Works offline |
|---|---|---|
| Countries | One type of rate for every country (standard, reduced, super-reduced or parking), drawn as bars; search, sort and favourites. Each country opens its rates, common amounts, VAT number format and a chart of its rate history | Yes |
| Calculator | Adds or removes VAT at any of a country's rates or a custom one; common amounts, recent calculations, copy and share with a link to the web page | Yes |
| Validator | Checks an EU VAT number in VIES; paste, an example, the country read from the prefix and the format it should have; keeps the last ten checks on the device | No |
| Compare | Two or three countries side by side | Yes |
| History | Recorded rate changes with a chart of increases and decreases per year, filtered by country and direction | Yes |

Long-press the app's icon on the Home Screen to open the calculator, the validator or the comparison directly.
In the list of countries, swipe a row right to add it to the favourites and left to open its calculator; a long
press offers every tool for that country.

## Run it

Requirements: Xcode 26 or newer and [XcodeGen](https://github.com/yonaskolb/XcodeGen) (`brew install xcodegen`).
The app targets iOS 17.

```bash
cd ios
xcodegen
open EUVATInfo.xcodeproj
```

The project file is generated from `project.yml` and is not in Git: run `xcodegen` again after adding,
moving or deleting a file.

To run on a device or to archive, put your team in `Config/Local.xcconfig` (ignored by Git):

```
DEVELOPMENT_TEAM = ABCDE12345
```

The bundle identifier, version and the website the app talks to are in `Config/App.xcconfig`.

## Test it

```bash
# Unit tests: VAT maths, parsing, API decoding, stores and models (under a second)
xcodebuild -project EUVATInfo.xcodeproj -scheme EUVATInfo \
  -destination 'platform=iOS Simulator,name=iPhone 17 Pro' -only-testing:EUVATInfoTests test

# UI tests: every tab, a calculation, a live VIES check, a comparison and a filter
xcodebuild -project EUVATInfo.xcodeproj -scheme EUVATInfo \
  -destination 'platform=iOS Simulator,name=iPhone 17 Pro' -only-testing:EUVATInfoUITests \
  -resultBundlePath build/ui.xcresult test

# The screenshots the UI tests took
xcrun xcresulttool export attachments --path build/ui.xcresult --output-path build/shots
```

The UI tests drive the real app against the live API and fall back to the shipped data without a connection.
The VIES test accepts a valid answer, an invalid one and an outage, because VIES is someone else's service.

## How it is built

```
EUVATInfo/
├── App/          Entry point, tabs, links to the website
├── Models/       Country, RateChange, ValidationResult (what the API returns)
├── Data/         APIClient, the snapshot on disk, RatesStore
├── Vat/          VatCalculation, AmountParser, VatNumberFormat, Format
├── Design/       Colours, shapes, keys and shared components
├── Features/     One folder per tab, plus About
├── Generated/    L10n.swift (written by scripts/sync-strings.php)
└── Resources/    Asset catalog, strings, seed data, privacy manifest
```

- **Data.** `RatesStore` shows what is on the device at once (the last fetch, or the snapshot shipped in the
  app) and refreshes from `/api/v1/countries` and `/api/vat-changes` when the data is more than six hours old
  or on pull to refresh. A failed refresh keeps the stored rates and says so.
- **VAT maths.** `Vat/VatCalculation.swift` and `Vat/AmountParser.swift` mirror `App\Support\Vat` (PHP) and
  `resources/js/vat.js`. The unit tests carry the same cases as `tests/Unit/VatCalculationTest.php` and
  `tests/Unit/AmountParserTest.php`. Change the three together.
- **Charts.** The rate history of a country and the changes per year are drawn with Swift Charts from
  `RateTimeline` and `ChangesByYear`, which are plain values with unit tests. Touching a chart reads a date or
  jumps to a year.
- **Feedback.** A copy is confirmed by a toast (`ToastCenter`), a choice by a light haptic. Favourites, recent
  calculations and recent checks are kept in `UserDefaults.app`; a debug build started with `-resetState`
  begins empty, which is how the UI tests start.
- **Design.** The app follows `DESIGN.md`: paper surfaces with hairlines, one cobalt signal, keys with a bottom
  edge, the primary panel cut on two corners, figures as the largest type. Type is the system face so Dynamic
  Type and all 24 languages work; the web fonts ship as subset `woff2` files, which iOS cannot load.
- **Languages.** The same 24 as the website. Names of countries, numbers, money and dates follow the interface
  language, as on the website.

## Generated files

Three scripts keep the app in step with the website. Run them from the repository root and commit the result.

| Script | Reads | Writes | Run it when |
|---|---|---|---|
| `php ios/scripts/sync-strings.php` | `lang/*/ui.php` | `Resources/Localizable.xcstrings`, `Generated/L10n.swift` | a string the app uses changes, or the app needs a new one |
| `python3 ios/scripts/generate-assets.py` | `resources/css/app.css`, `resources/images/flags`, the logo | `Resources/Assets.xcassets`: colours, flags and the app icon in its default, dark and tinted renderings | a colour token, a flag or the logo changes (needs Pillow) |
| `ios/scripts/refresh-seed.sh` | the live API | `Resources/Seed/snapshot.json` | before a release, so a first launch offline shows current rates |

To add a string, list its key from `lang/en/ui.php` in `KEYS` in `sync-strings.php` (with its placeholders, in
the order the Swift function takes them) and use `L10n.<name>` in code. A string the website has no use for
goes in `APP_ONLY`, in all 24 languages.

## Release

1. Run `ios/scripts/refresh-seed.sh` and the tests.
2. Raise `MARKETING_VERSION` and `CURRENT_PROJECT_VERSION` in `Config/App.xcconfig`.
3. Run `ios/scripts/archive.sh`. It archives the app and exports `build/export/EUVATInfo.ipa`, signed for the
   App Store; it uploads nothing. Xcode must be signed in to the team in `Config/Local.xcconfig`.
4. Run `ios/scripts/store-screenshots.sh` when a screen changed. It takes the 6.9-inch iPhone and 13-inch
   iPad screenshots from the UI tests into `AppStore/screenshots/raw`, then `frame-screenshots.py` sets each
   on an ink ground under one line of text and writes the listing's images to `AppStore/screenshots/en-US`
   (neither folder is in Git). The lines are in `CAPTIONS` in `frame-screenshots.py`. App Store Connect
   requires the 6.3-inch iPhone set (`iphone-63-*`, 1206 x 2622), which the script frames from the same
   screenshots; the 6.9-inch set (`iphone-69-*`) goes in the optional large-display slot and `ipad-13-*`
   in the iPad one.
5. Upload the build. With Xcode signed in to the team, export the archive again with `destination` set to
   `upload` in a copy of `build/ExportOptions.plist`:
   `xcodebuild -exportArchive -archivePath build/EUVATInfo.xcarchive -exportPath build/upload -exportOptionsPlist build/UploadOptions.plist -allowProvisioningUpdates`.
   Transporter and the Xcode Organizer (Distribute App) do the same. The app record must exist in App Store
   Connect first.

To put a build on your own iPhone, unlock it, then:

```bash
xcodebuild -project EUVATInfo.xcodeproj -scheme EUVATInfo -configuration Release \
  -destination 'generic/platform=iOS' -derivedDataPath build/device -allowProvisioningUpdates build
xcrun devicectl device install app --device <device id> build/device/Build/Products/Release-iphoneos/EUVATInfo.app
```

`xcrun devicectl list devices` shows the device id.

### The listing

`AppStore/metadata` holds the text of the listing in the layout fastlane's `deliver` reads, so it can be
pasted into App Store Connect or uploaded with `fastlane deliver` later.

| File | Field in App Store Connect |
|---|---|
| `en-US/name.txt`, `subtitle.txt` | Name, Subtitle |
| `en-US/promotional_text.txt`, `description.txt`, `keywords.txt` | Promotional Text, Description, Keywords |
| `en-US/support_url.txt`, `marketing_url.txt`, `privacy_url.txt` | Support URL, Marketing URL, Privacy Policy URL |
| `primary_category.txt`, `secondary_category.txt` | Finance, Business |
| `copyright.txt` | Copyright |
| `review_information/notes.txt` | App Review notes |

App Store Connect also asks for these, which are yours to answer: a contact for App Review, the price
(free) and the countries.

**Age rating.** Answer "none" to every content question: the app shows tax rates and makes no web browsing,
user content, gambling or purchases available. That gives the lowest rating.

**Content rights.** Answer that the app does not contain, show or access third-party content.

**Export compliance.** The app only uses HTTPS from the system, and `ITSAppUsesNonExemptEncryption` is already
`false` in the build, so App Store Connect does not ask.

**App Privacy.** The app has no analytics and no advertising, and it does not track. Checking a VAT number
sends that number to the EU VAT Info server, which logs it with the result, the IP address and the user agent
(`VatValidationLog`). Declare *Other Data*, used for *App Functionality*, linked to the person (Apple counts
an IP address as personal data, and the log keeps it with each number) and not used for tracking;
`Resources/PrivacyInfo.xcprivacy` says the same from the next build on (build 1 of 1.0.0 was uploaded while it
still said not linked). It can become *not linked* once the server stops storing the IP address and user agent
with a check. The privacy policy on the website should mention the app.

**China mainland.** App Compliance asks two questions for China mainland: the app offers no AI-generated
content services and has no features that need a security assessment.

## Licences

Flags are from [flag-icons](https://github.com/lipis/flag-icons) (MIT). The licence ships in the app and is
shown under About.
