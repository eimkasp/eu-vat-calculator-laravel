import SwiftUI

/// One country: every rate, where the figures come from, and the ways on into the tools.
struct CountryView: View {
    let iso: String

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router
    @Environment(FavouritesStore.self) private var favourites
    @Environment(CalculatorModel.self) private var calculator
    @Environment(ValidatorModel.self) private var validator
    @Environment(ComparisonModel.self) private var comparison

    @ScaledMetric(relativeTo: .largeTitle) private var heroSize: CGFloat = 56

    /// The net amounts of the table of common amounts.
    private static let amounts: [Double] = [100, 500, 1000, 5000]

    var body: some View {
        let favourite = favourites.contains(iso)

        ScrollView {
            if let country = store.country(iso: iso) {
                VStack(alignment: .leading, spacing: 20) {
                    rates(country)
                    FreshnessLine(updated: country.lastUpdated ?? store.fetchedAt, isEU: country.isEUMember)
                    actions(country)
                    commonAmounts(country)
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
        .toolbar {
            ToolbarItem(placement: .topBarTrailing) {
                Button {
                    favourites.toggle(iso)
                } label: {
                    Image(systemName: favourite ? "star.fill" : "star")
                }
                .accessibilityLabel(L10n.appFavourites)
                .accessibilityAddTraits(favourite ? .isSelected : [])
                .sensoryFeedback(.selection, trigger: favourite)
            }
        }
    }

    // MARK: - Rates

    private func rates(_ country: Country) -> some View {
        // A member state is placed among the member states; any other country among all of them.
        let peers = (country.isEUMember ? store.memberStates : store.countries).map(\.rates.standard)

        return VStack(alignment: .leading, spacing: 0) {
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
            .accessibilityElement(children: .combine)

            if let lowest = peers.min(), let highest = peers.max(), highest > lowest {
                RangePosition(value: country.rates.standard, lowest: lowest, highest: highest)
                    .padding(.top, 10)
                    .padding(.bottom, 14)
            }

            Hairline()
            rateRow(L10n.countryPageReducedRate, country.rates.reducedRates, in: country) { .reduced($0) } name: {
                L10n.calculatorReducedRateLabel(rate: $0)
            }

            if let superReduced = country.rates.superReduced {
                Hairline()
                rateRow(L10n.countryPageSuperReduced, [superReduced], in: country) { _ in .superReduced } name: {
                    L10n.calculatorSuperReducedRateLabel(rate: $0)
                }
            }

            if let parking = country.rates.parking {
                Hairline()
                rateRow(L10n.countryPageParkingRate, [parking], in: country) { _ in .parking } name: {
                    L10n.calculatorParkingRateLabel(rate: $0)
                }
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .panel()
    }

    /// A type of rate with each of its values as a key that opens the calculator at that rate.
    @ViewBuilder
    private func rateRow(
        _ label: String,
        _ values: [Double],
        in country: Country,
        choice: @escaping (Double) -> RateChoice,
        name: @escaping (String) -> String
    ) -> some View {
        if values.isEmpty {
            InfoRow(label, L10n.compareNone)
        } else {
            let title = Text(label)
                .font(.label)
                .foregroundStyle(Palette.inkMuted)
            let keys = ForEach(values, id: \.self) { value in
                Button(Format.percent(value)) {
                    calculator.open(country, choice: choice(value))
                    router.tab = .calculator
                }
                .buttonStyle(.secondaryKey)
                .monospacedDigit()
                .accessibilityLabel(name(Format.rateNumber(value)))
                .accessibilityHint(L10n.countryPageVatCalculator)
            }

            ViewThatFits(in: .horizontal) {
                HStack(spacing: 8) {
                    title
                    Spacer(minLength: 12)
                    keys
                }
                VStack(alignment: .leading, spacing: 8) {
                    title
                    FlowLayout(spacing: 8) { keys }
                }
            }
            .padding(.vertical, 10)
        }
    }

    // MARK: - Actions

    private func actions(_ country: Country) -> some View {
        VStack(spacing: 10) {
            Button(L10n.countryPageVatCalculator) {
                calculator.open(country)
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

    // MARK: - Common amounts

    /// What the standard rate does to round amounts. A row opens the calculator on that amount.
    private func commonAmounts(_ country: Country) -> some View {
        let rate = country.rates.standard
        let adding = L10n.calculatorThAddVat(rate: Format.rateNumber(rate))
        let removing = L10n.calculatorThRemoveVat(rate: Format.rateNumber(rate))

        return VStack(alignment: .leading, spacing: 0) {
            Text(L10n.calculatorQuickAmounts)
                .font(.section)
                .foregroundStyle(Palette.ink)
                .padding(.bottom, 8)
                .accessibilityAddTraits(.isHeader)

            HStack(alignment: .lastTextBaseline, spacing: 12) {
                Text(L10n.calculatorThAmount)
                    .frame(maxWidth: .infinity, alignment: .leading)
                Text(adding)
                    .multilineTextAlignment(.trailing)
                    .frame(maxWidth: .infinity, alignment: .trailing)
                Text(removing)
                    .multilineTextAlignment(.trailing)
                    .frame(maxWidth: .infinity, alignment: .trailing)
                Color.clear
                    .frame(width: 10, height: 1)
            }
            .font(.label)
            .foregroundStyle(Palette.inkMuted)
            .padding(.bottom, 8)
            .accessibilityHidden(true)

            ForEach(Self.amounts, id: \.self) { amount in
                let gross = Format.money(VatCalculation(amount: amount, rate: rate, mode: .exclude).gross, currency: country.currency)
                let net = Format.money(VatCalculation(amount: amount, rate: rate, mode: .include).net, currency: country.currency)

                Hairline()

                Button {
                    calculator.open(country, amount: amount)
                    router.tab = .calculator
                } label: {
                    HStack(spacing: 12) {
                        Text(Format.wholeMoney(amount, currency: country.currency))
                            .foregroundStyle(Palette.inkMuted)
                            .frame(maxWidth: .infinity, alignment: .leading)
                        Text(gross)
                            .frame(maxWidth: .infinity, alignment: .trailing)
                        Text(net)
                            .frame(maxWidth: .infinity, alignment: .trailing)
                        Image(systemName: "chevron.right")
                            .font(.caption2.weight(.semibold))
                            .foregroundStyle(Palette.inkQuiet)
                            .frame(width: 10)
                            .accessibilityHidden(true)
                    }
                    .font(.subheadline.monospacedDigit())
                    .foregroundStyle(Palette.ink)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
                    .frame(minHeight: Metrics.touch)
                    .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
                .accessibilityLabel("\(Format.wholeMoney(amount, currency: country.currency)). \(adding): \(gross). \(removing): \(net)")
                .accessibilityHint(L10n.countryPageVatCalculator)
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .card()
    }

    // MARK: - Reference

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

            VStack(alignment: .leading, spacing: 2) {
                ForEach(format.masks, id: \.self) { mask in
                    Text(mask)
                }
            }
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
                    .padding(.bottom, 12)
                    .accessibilityAddTraits(.isHeader)

                if let timeline = RateTimeline(changes: changes) {
                    RateHistoryChart(timeline: timeline)
                        .padding(.bottom, 12)
                    Hairline()
                }

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
