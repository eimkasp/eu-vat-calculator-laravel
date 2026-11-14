# EU VAT Info for iOS

A native SwiftUI app for iPhone and iPad that reads the same public API as the website: VAT rates for every
country the site covers, a calculator, the VIES VAT number validator, country comparison and the history of
rate changes. No account, no tracking, no third-party code.

| Tab | What it does | Works offline |
|---|---|---|
| Countries | Standard rate of every country, search, sort; each country opens its rates, VAT number format and history | Yes |
| Calculator | Adds or removes VAT at any of a country's rates or a custom one; copy and share with a link to the web page | Yes |
| Validator | Checks an EU VAT number in VIES, reads the country from the prefix, keeps the last ten checks on the device | No |
| Compare | Two or three countries side by side | Yes |
| History | Recorded rate changes, filtered by country and direction | Yes |

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
| `python3 ios/scripts/generate-assets.py` | `resources/css/app.css`, `resources/images/flags`, the logo | `Resources/Assets.xcassets` | a colour token, a flag or the logo changes (needs Pillow) |
| `ios/scripts/refresh-seed.sh` | the live API | `Resources/Seed/snapshot.json` | before a release, so a first launch offline shows current rates |

To add a string, list its key from `lang/en/ui.php` in `KEYS` in `sync-strings.php` (with its placeholders, in
the order the Swift function takes them) and use `L10n.<name>` in code. A string the website has no use for
goes in `APP_ONLY`, in all 24 languages.

## Before a release

1. Run `ios/scripts/refresh-seed.sh` and the tests.
2. Raise `MARKETING_VERSION` and `CURRENT_PROJECT_VERSION` in `Config/App.xcconfig`.
3. Archive the `EUVATInfo` scheme with your team set and upload it from the Organizer.

App Store Connect needs, once: an app record for `io.businesspress.vat`, screenshots (the UI tests produce
them for iPhone and iPad), a privacy policy address (`https://vat.businesspress.io/privacy`) and the privacy
answers below.

**Privacy answers.** The app has no analytics and no advertising, and it does not track. Checking a VAT number
sends that number to the EU VAT Info server, which logs it with the result, the IP address and the user agent
(`VatValidationLog`). Declare it as *Other Data*, used for *App Functionality*, not linked to the person and
not used for tracking; `Resources/PrivacyInfo.xcprivacy` says the same. The privacy policy on the website
should mention the app.

## Licences

Flags are from [flag-icons](https://github.com/lipis/flag-icons) (MIT). The licence ships in the app and is
shown under About.
