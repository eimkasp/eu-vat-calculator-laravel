import XCTest

/// Walks the app the way a person would: every tab, a calculation, a VIES check, a comparison and a filter.
/// The rates come from the live API or, without a connection, from the data the app ships with, so the
/// assertions hold either way. Every screen is attached to the test report as a screenshot.
@MainActor
final class SmokeTests: XCTestCase {
    private var app: XCUIApplication!

    override func setUp() async throws {
        continueAfterFailure = false

        app = XCUIApplication()
        // English with a US region keeps labels and number formats the same on every machine.
        app.launchArguments += ["-AppleLanguages", "(en)", "-AppleLocale", "en_US"]
        // Start without favourites, recent calculations or recent checks from an earlier run.
        app.launchArguments += ["-resetState"]
        app.launchArguments += ["-calculator.country", "DE", "-compare.countries", "(DE, FR)"]
        app.launch()
    }

    func testRatesListOpensACountryAndItsCalculator() {
        XCTAssertTrue(app.navigationBars["VAT rates"].waitForExistence(timeout: 10))
        XCTAssertTrue(app.staticTexts["European Union"].exists)
        capture("01-rates")

        search("lith")
        let lithuania = app.buttons.matching(NSPredicate(format: "label BEGINSWITH %@", "Lithuania")).firstMatch
        XCTAssertTrue(lithuania.waitForExistence(timeout: 5))
        lithuania.tap()

        XCTAssertTrue(app.navigationBars["Lithuania"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.staticTexts["EU member"].exists)
        XCTAssertTrue(app.staticTexts["Source: European Commission"].exists || app.links["Source: European Commission"].exists)
        capture("02-country")

        app.swipeUp()
        capture("03-country-details")
        app.swipeUp()
        capture("15-country-history")
        app.swipeDown()
        app.swipeDown()

        // A rate on the country's page is a key that opens the calculator at that rate.
        let reduced = app.buttons.matching(NSPredicate(format: "label BEGINSWITH %@", "Reduced (")).firstMatch
        XCTAssertTrue(reduced.waitForExistence(timeout: 5))
        let rate = reduced.label
        reduced.tap()

        XCTAssertTrue(app.navigationBars["VAT calculator"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons.matching(NSPredicate(format: "label CONTAINS %@", "Lithuania")).firstMatch.exists)
        XCTAssertTrue(app.buttons[rate].waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons[rate].isSelected)
    }

    func testFavouritesComeFirstAndTheListShowsOtherRates() {
        XCTAssertTrue(app.navigationBars["VAT rates"].waitForExistence(timeout: 10))
        XCTAssertFalse(app.staticTexts["Favourites"].exists)

        app.buttons.matching(NSPredicate(format: "label BEGINSWITH %@", "Austria")).firstMatch.tap()
        XCTAssertTrue(app.navigationBars["Austria"].waitForExistence(timeout: 5))
        app.buttons["Favourites"].tap()
        app.navigationBars.buttons.element(boundBy: 0).tap()

        XCTAssertTrue(app.staticTexts["Favourites"].waitForExistence(timeout: 5))
        capture("12-rates-favourites")

        // The head of the figures column switches the list to another type of rate.
        app.buttons.matching(NSPredicate(format: "label == %@", "Type")).firstMatch.tap()
        app.buttons["Reduced rate"].tap()
        XCTAssertTrue(text(containing: "Austria, Reduced rate").waitForExistence(timeout: 5))
        capture("13-rates-reduced")
    }

    func testCalculatorAddsAndRemovesVat() {
        tab("Calculator")
        XCTAssertTrue(app.navigationBars["VAT calculator"].waitForExistence(timeout: 10))

        let amount = app.textFields["Amount (excl. VAT)"]
        XCTAssertTrue(amount.waitForExistence(timeout: 5))
        amount.tap()
        amount.clear()
        amount.typeText("200")

        // Germany: 19% standard, 7% reduced.
        XCTAssertTrue(text(containing: "€238.00").waitForExistence(timeout: 5))
        capture("04-calculator-typing")

        app.buttons["dismissKeyboard"].tap()
        capture("05-calculator")

        // A common amount is one tap instead of typing.
        app.buttons["1,000"].tap()
        XCTAssertTrue(text(containing: "€1,190.00").waitForExistence(timeout: 5))

        app.buttons["Reduced (7%)"].tap()
        XCTAssertTrue(text(containing: "€1,070.00").waitForExistence(timeout: 5))

        app.buttons["Remove VAT"].tap()
        XCTAssertTrue(app.textFields["Amount (incl. VAT)"].waitForExistence(timeout: 5))
        XCTAssertTrue(text(containing: "€934.58").waitForExistence(timeout: 5))

        app.buttons["Custom"].tap()
        let custom = app.textFields["Custom %"]
        XCTAssertTrue(custom.waitForExistence(timeout: 5))
        custom.typeText("25")
        XCTAssertTrue(text(containing: "€800.00").waitForExistence(timeout: 5))
        app.buttons["dismissKeyboard"].tap()

        // The first calculation is kept and can be opened again.
        XCTAssertTrue(app.staticTexts["Recent calculations"].waitForExistence(timeout: 5))
        app.swipeUp()
        capture("14-calculator-recent")
        text(containing: "€200.00 plus 19% VAT in Germany").tap()
        XCTAssertTrue(app.textFields["Amount (excl. VAT)"].waitForExistence(timeout: 5))
        XCTAssertTrue(text(containing: "€238.00").waitForExistence(timeout: 5))

        let amountExclVat = app.textFields["Amount (excl. VAT)"]
        app.swipeDown()
        amountExclVat.tap()
        amountExclVat.clear()
        amountExclVat.typeText("abc")
        XCTAssertTrue(text(containing: "Enter a valid amount").waitForExistence(timeout: 5))
    }

    func testValidatorAnswersOrSaysWhyItCannot() {
        tab("Validator")
        XCTAssertTrue(app.navigationBars["VAT number validator"].waitForExistence(timeout: 10))
        XCTAssertFalse(app.buttons["Validate"].isEnabled)

        // The example fills the field and the country is read from its prefix.
        app.buttons["Try LT100019070512"].tap()
        XCTAssertTrue(app.buttons.matching(NSPredicate(format: "label CONTAINS %@", "Lithuania")).firstMatch.waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons["Validate"].isEnabled)

        let number = app.textFields["VAT number"]
        number.tap()
        number.clear()
        number.typeText("IE6388047V")
        XCTAssertTrue(app.staticTexts["Country detected from the prefix"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons.matching(NSPredicate(format: "label CONTAINS %@", "Ireland")).firstMatch.exists)
        capture("06-validator-ready")

        app.buttons["Validate"].tap()

        // VIES is someone else's service: a valid answer, an invalid one and an outage are all honest outcomes.
        let outcome = app.staticTexts.matching(NSPredicate(
            format: "label BEGINSWITH %@ OR label BEGINSWITH %@ OR label CONTAINS %@ OR label CONTAINS %@",
            "Valid VAT number", "Not a valid VAT number", "temporarily unavailable", "Too many lookups"
        )).firstMatch
        XCTAssertTrue(outcome.waitForExistence(timeout: 30))
        capture("06-validator-result")
    }

    func testComparisonHoldsUpToThreeCountries() {
        tab("Compare")
        XCTAssertTrue(app.navigationBars["Compare countries"].waitForExistence(timeout: 10))
        XCTAssertTrue(app.staticTexts["Standard rate"].waitForExistence(timeout: 5))
        capture("07-compare")

        app.buttons["Choose a country"].tap()
        search("Hungary")
        app.buttons.matching(NSPredicate(format: "label BEGINSWITH %@", "Hungary")).firstMatch.tap()

        XCTAssertTrue(text(containing: "Hungary: ").waitForExistence(timeout: 5))
        XCTAssertFalse(app.buttons["Choose a country"].exists)
        app.swipeUp()
        capture("08-compare-three")
    }

    func testHistoryListsChangesAndFilters() {
        tab("History")
        XCTAssertTrue(app.navigationBars["VAT history"].waitForExistence(timeout: 10))
        XCTAssertTrue(text(containing: "Changed on").waitForExistence(timeout: 5))
        XCTAssertTrue(text(containing: "Increases ").exists)
        capture("09-history")

        // The filters sit above the list.
        app.buttons["Decreases only"].tap()
        XCTAssertTrue(text(containing: "Decrease,").waitForExistence(timeout: 5))
        XCTAssertFalse(text(containing: "Increase,").exists)
        capture("10-history-filtered")
    }

    func testAboutNamesTheSources() {
        XCTAssertTrue(app.navigationBars["VAT rates"].waitForExistence(timeout: 10))
        app.buttons["About EU VAT Info"].tap()

        XCTAssertTrue(app.navigationBars["About EU VAT Info"].waitForExistence(timeout: 5))
        XCTAssertTrue(text(containing: "Source: European Commission").exists)
        XCTAssertTrue(text(containing: "Privacy policy").exists)
        capture("11-about")
    }

    func testHomeScreenShortcutOpensATool() {
        XCTAssertTrue(app.navigationBars["VAT rates"].waitForExistence(timeout: 10))

        // The shortcuts are listed on first launch; they are offered from the icon on the Home Screen.
        XCUIDevice.shared.press(.home)
        let springboard = XCUIApplication(bundleIdentifier: "com.apple.springboard")
        let icon = springboard.icons["EU VAT Info"].firstMatch
        XCTAssertTrue(icon.waitForExistence(timeout: 10))
        icon.press(forDuration: 1.3)

        let shortcut = springboard.buttons["VAT number validator"].firstMatch
        XCTAssertTrue(shortcut.waitForExistence(timeout: 5))
        shortcut.tap()

        XCTAssertTrue(app.navigationBars["VAT number validator"].waitForExistence(timeout: 10))
    }

    // MARK: - Helpers

    private func tab(_ name: String) {
        let button = app.tabBars.buttons[name].exists ? app.tabBars.buttons[name] : app.buttons[name].firstMatch
        XCTAssertTrue(button.waitForExistence(timeout: 10))
        button.tap()
    }

    /// Types into the search field. An iPad keeps it behind a button in the toolbar until it is asked for.
    private func search(_ query: String) {
        let field = app.searchFields.firstMatch

        if !field.waitForExistence(timeout: 3) {
            app.buttons["Search"].firstMatch.tap()
        }

        XCTAssertTrue(field.waitForExistence(timeout: 5))
        field.tap()
        field.typeText(query)
    }

    /// Any element whose label contains the text: SwiftUI merges rows into one element, so a figure is rarely alone.
    private func text(containing text: String) -> XCUIElement {
        app.descendants(matching: .any).matching(NSPredicate(format: "label CONTAINS %@", text)).firstMatch
    }

    private func capture(_ name: String) {
        let attachment = XCTAttachment(screenshot: app.screenshot())
        attachment.name = name
        attachment.lifetime = .keepAlways
        add(attachment)
    }
}

private extension XCUIElement {
    /// Empties a text field that already has a value.
    func clear() {
        guard let value = value as? String, !value.isEmpty else { return }

        typeText(String(repeating: XCUIKeyboardKey.delete.rawValue, count: value.count))
    }
}
