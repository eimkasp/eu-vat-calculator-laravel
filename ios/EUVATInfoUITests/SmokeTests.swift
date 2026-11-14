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
        app.swipeDown()

        app.buttons["VAT calculator"].tap()
        XCTAssertTrue(app.navigationBars["VAT calculator"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons.matching(NSPredicate(format: "label CONTAINS %@", "Lithuania")).firstMatch.exists)
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

        app.buttons["Reduced (7%)"].tap()
        XCTAssertTrue(text(containing: "€214.00").waitForExistence(timeout: 5))

        app.buttons["Remove VAT"].tap()
        XCTAssertTrue(app.textFields["Amount (incl. VAT)"].waitForExistence(timeout: 5))
        XCTAssertTrue(text(containing: "€186.92").waitForExistence(timeout: 5))

        app.buttons["Custom"].tap()
        let custom = app.textFields["Custom %"]
        XCTAssertTrue(custom.waitForExistence(timeout: 5))
        custom.typeText("25")
        XCTAssertTrue(text(containing: "€160.00").waitForExistence(timeout: 5))
        app.buttons["dismissKeyboard"].tap()

        let amountInclVat = app.textFields["Amount (incl. VAT)"]
        amountInclVat.tap()
        amountInclVat.clear()
        amountInclVat.typeText("abc")
        XCTAssertTrue(text(containing: "Enter a valid amount").waitForExistence(timeout: 5))
    }

    func testValidatorAnswersOrSaysWhyItCannot() {
        tab("Validator")
        XCTAssertTrue(app.navigationBars["VAT number validator"].waitForExistence(timeout: 10))
        XCTAssertFalse(app.buttons["Validate"].isEnabled)

        let number = app.textFields["VAT number"]
        number.tap()
        number.typeText("IE6388047V")
        XCTAssertTrue(app.staticTexts["Country detected from the prefix"].waitForExistence(timeout: 5))
        XCTAssertTrue(app.buttons.matching(NSPredicate(format: "label CONTAINS %@", "Ireland")).firstMatch.exists)

        app.buttons["Validate"].tap()

        // VIES is someone else's service: a valid answer, an invalid one and an outage are all honest outcomes.
        let outcome = app.staticTexts.matching(NSPredicate(
            format: "label BEGINSWITH %@ OR label BEGINSWITH %@ OR label CONTAINS %@ OR label CONTAINS %@",
            "Valid VAT number", "Not a valid VAT number", "temporarily unavailable", "Too many lookups"
        )).firstMatch
        XCTAssertTrue(outcome.waitForExistence(timeout: 30))
        capture("06-validator")
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
        capture("09-history")

        app.buttons["Filters"].tap()
        app.buttons["Decreases only"].tap()
        XCTAssertTrue(text(containing: "Decrease").waitForExistence(timeout: 5))
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
