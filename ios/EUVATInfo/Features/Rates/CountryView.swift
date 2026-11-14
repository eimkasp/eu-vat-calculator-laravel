import SwiftUI

/// One country: every rate, where the figures come from, and the ways on into the tools.
struct CountryView: View {
    let iso: String

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router
    @Environment(CalculatorModel.self) private var calculator
    @Environment(ValidatorModel.self) private var validator
    @Environment(ComparisonModel.self) private var comparison

    @ScaledMetric(relativeTo: .largeTitle) private var heroSize: CGFloat = 56

    var body: some View {
        ScrollView {
            if let country = store.country(iso: iso) {
                VStack(alignment: .leading, spacing: 20) {
                    rates(country)
                    FreshnessLine(updated: country.lastUpdated ?? store.fetchedAt, isEU: country.isEUMember)
                    actions(country)
                    keyInformation(country)

                    if let format = country.vatNumber {
                        numberFormat(format, country)
                    }

                    history(country)

                    Link(destination: AppLinks.guide(for: country)) {
                        Label(L10n.calculatorViewVatGuide(country: country.localizedName), systemImage: "arrow.up.right")
                            .labelStyle(.tightIcon)
                            .font(.body.weight(.medium))
                            .frame(minHeight: Metrics.touch)
                    }
                }
                .padding(.vertical, 16)
                .contentColumn()
            }
        }
        .workspaceBackground()
        .navigationTitle(store.country(iso: iso)?.localizedName ?? iso)
        .navigationBarTitleDisplayMode(.large)
    }

    private func rates(_ country: Country) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            HStack(spacing: 8) {
                FlagView(iso: country.isoCode, height: 18)
                Text(country.isEUMember ? L10n.calculatorScopeEu : L10n.calculatorScopeOtherEurope)
                    .font(.label)
                    .foregroundStyle(Palette.inkMuted)
            }
            .accessibilityElement(children: .combine)

            VStack(alignment: .leading, spacing: 2) {
                Text(L10n.countryPageStandardRate)
                    .font(.label)
                    .foregroundStyle(Palette.inkMuted)
                Text(Format.percent(country.rates.standard))
                    .font(.system(size: heroSize, weight: .semibold).monospacedDigit())
                    .tracking(-0.03 * heroSize)
                    .foregroundStyle(Palette.ink)
                    .lineLimit(1)
                    .minimumScaleFactor(0.5)
            }
            .padding(.top, 16)
            .padding(.bottom, 8)
            .accessibilityElement(children: .combine)

            Hairline()
            InfoRow(L10n.countryPageReducedRate, country.rates.reducedRates.isEmpty ? L10n.compareNone : Format.rates(country.rates.reducedRates))

            if let superReduced = country.rates.superReduced {
                Hairline()
                InfoRow(L10n.countryPageSuperReduced, Format.percent(superReduced))
            }

            if let parking = country.rates.parking {
                Hairline()
                InfoRow(L10n.countryPageParkingRate, Format.percent(parking))
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .panel()
    }

    private func actions(_ country: Country) -> some View {
        VStack(spacing: 10) {
            Button(L10n.countryPageVatCalculator) {
                calculator.countryCode = country.isoCode
                router.tab = .calculator
            }
            .buttonStyle(.primaryKey)

            if country.vatNumber != nil {
                Button(L10n.countryPageValidateVatCta(country: country.localizedName)) {
                    validator.choose(country)
                    router.tab = .validator
                }
                .buttonStyle(KeyButtonStyle(kind: .secondary, fullWidth: true))
            }

            Button(L10n.countryPageCompareWith(country: country.localizedName)) {
                comparison.include(country.isoCode)
                router.comparePath = []
                router.tab = .compare
            }
            .buttonStyle(KeyButtonStyle(kind: .secondary, fullWidth: true))
        }
    }

    private func keyInformation(_ country: Country) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            Text(L10n.countryPageKeyInformation)
                .font(.section)
                .foregroundStyle(Palette.ink)
                .padding(.bottom, 4)
                .accessibilityAddTraits(.isHeader)

            InfoRow(label: L10n.countryPageCountryCode) {
                Text(country.isoCode).font(.machine).foregroundStyle(Palette.ink)
            }
            Hairline()
            InfoRow(label: L10n.countryPageCurrency) {
                Text(country.currency).font(.machine).foregroundStyle(Palette.ink)
            }
            Hairline()
            InfoRow(L10n.countryPageEuMember, country.isEUMember ? L10n.countryPageYes : L10n.countryPageNo)
            Hairline()
            InfoRow(L10n.countryPageViesValidation, country.vatNumber != nil ? L10n.countryPageAvailable : L10n.countryPageNotAvailable)
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .card()
    }

    private func numberFormat(_ format: Country.VatNumber, _ country: Country) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(L10n.calculatorVatNumberFormat)
                .font(.section)
                .foregroundStyle(Palette.ink)
                .accessibilityAddTraits(.isHeader)

            Text(format.format)
                .font(.machine)
                .foregroundStyle(Palette.ink)
                .textSelection(.enabled)

            Text(L10n.viesPageFormatLegend)
                .font(.footnote)
                .foregroundStyle(Palette.inkMuted)

            if format.prefix != country.isoCode {
                Text(L10n.viesPageFormatNoteGr)
                    .font(.footnote)
                    .foregroundStyle(Palette.inkMuted)
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .card()
    }

    @ViewBuilder
    private func history(_ country: Country) -> some View {
        let changes = store.changes(for: country)

        if !changes.isEmpty {
            VStack(alignment: .leading, spacing: 0) {
                Text(L10n.historyRateHistory)
                    .font(.section)
                    .foregroundStyle(Palette.ink)
                    .padding(.bottom, 4)
                    .accessibilityAddTraits(.isHeader)

                ForEach(changes) { change in
                    if change.id != changes.first?.id {
                        Hairline()
                    }

                    ChangeRow(change: change, showsCountry: false)
                        .padding(.vertical, 12)
                }
            }
            .padding(16)
            .frame(maxWidth: .infinity, alignment: .leading)
            .card()
        }
    }
}
