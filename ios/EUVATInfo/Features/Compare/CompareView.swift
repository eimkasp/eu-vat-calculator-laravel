import SwiftUI

/// The two or three countries being compared, kept between launches.
@MainActor
@Observable
final class ComparisonModel {
    static let limit = 3

    private(set) var codes: [String] {
        didSet { defaults.set(codes, forKey: Self.key) }
    }

    @ObservationIgnored private let defaults: UserDefaults
    private static let key = "compare.countries"

    init(defaults: UserDefaults = .app) {
        self.defaults = defaults
        codes = Array((defaults.stringArray(forKey: Self.key) ?? []).prefix(Self.limit))
    }

    /// The chosen countries that the app has rates for, filled up to two with well-known ones.
    func countries(in store: RatesStore) -> [Country] {
        var countries = codes.compactMap(store.country(iso:))

        for fallback in ["DE", "FR"] where countries.count < 2 && !countries.contains(where: { $0.isoCode == fallback }) {
            if let country = store.country(iso: fallback) {
                countries.append(country)
            }
        }

        return countries
    }

    /// Adds a country to the comparison; when it is full, the new one takes the last place.
    func include(_ code: String) {
        guard !codes.contains(code) else { return }

        codes = Array(codes.prefix(Self.limit - 1)) + [code]
    }

    func replace(_ old: String, with new: String, in store: RatesStore) {
        var current = countries(in: store).map(\.isoCode)

        guard let index = current.firstIndex(of: old), !current.contains(new) else { return }

        current[index] = new
        codes = current
    }

    func add(_ code: String, in store: RatesStore) {
        let current = countries(in: store).map(\.isoCode)

        guard current.count < Self.limit, !current.contains(code) else { return }

        codes = current + [code]
    }

    func remove(_ code: String, in store: RatesStore) {
        let current = countries(in: store).map(\.isoCode)

        guard current.count > 2 else { return }

        codes = current.filter { $0 != code }
    }
}

/// VAT rates of two or three countries side by side.
struct CompareView: View {
    private enum Picking: Identifiable {
        case add
        case replace(String)

        var id: String {
            switch self {
            case .add: "add"
            case let .replace(code): code
            }
        }
    }

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router
    @Environment(ComparisonModel.self) private var model

    @State private var picking: Picking?

    var body: some View {
        @Bindable var router = router
        let countries = model.countries(in: store)

        NavigationStack(path: $router.comparePath) {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    StaleDataNotice()
                    chooser(countries)

                    if countries.count >= 2 {
                        table(countries)
                        FreshnessLine(updated: store.fetchedAt, isEU: countries.allSatisfy(\.isEUMember))

                        Text(L10n.compareDisclaimer)
                            .font(.footnote)
                            .foregroundStyle(Palette.inkMuted)
                    } else {
                        Notice(tone: .neutral, title: L10n.compareIndexError)
                    }
                }
                .padding(.vertical, 16)
                .contentColumn()
            }
            .workspaceBackground()
            .navigationTitle(L10n.navCompare)
            .navigationDestination(for: String.self) { CountryView(iso: $0) }
            .sheet(item: $picking) { picking in
                CountryPicker(title: L10n.compareIndexChoose, countries: store.countries, disabled: Set(countries.map(\.isoCode))) { country in
                    switch picking {
                    case .add: model.add(country.isoCode, in: store)
                    case let .replace(old): model.replace(old, with: country.isoCode, in: store)
                    }
                }
            }
        }
    }

    private func chooser(_ countries: [Country]) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(L10n.compareIndexPickerHeading)
                .font(.label)
                .foregroundStyle(Palette.inkMuted)

            ForEach(countries) { country in
                HStack(spacing: 8) {
                    CountryKey(country: country) { picking = .replace(country.isoCode) }

                    if countries.count > 2 {
                        Button {
                            model.remove(country.isoCode, in: store)
                        } label: {
                            Image(systemName: "xmark")
                                .frame(width: Metrics.touch, height: Metrics.touch)
                        }
                        .accessibilityLabel("\(L10n.close): \(country.localizedName)")
                    }
                }
            }

            if countries.count < ComparisonModel.limit {
                Button {
                    picking = .add
                } label: {
                    Label(L10n.compareIndexChoose, systemImage: "plus")
                        .labelStyle(.tightIcon)
                }
                .buttonStyle(KeyButtonStyle(kind: .secondary, fullWidth: true))
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .panel()
    }

    private func table(_ countries: [Country]) -> some View {
        let highest = countries.map(\.rates.standard).max() ?? 0
        let lowest = countries.map(\.rates.standard).min() ?? 0

        return VStack(alignment: .leading, spacing: 0) {
            columns(countries) { country in
                Button {
                    router.comparePath.append(country.isoCode)
                } label: {
                    VStack(alignment: .leading, spacing: 6) {
                        FlagView(iso: country.isoCode, height: 20)
                        Text(country.localizedName)
                            .font(.subheadline.weight(.semibold))
                            .foregroundStyle(Palette.ink)
                            .multilineTextAlignment(.leading)
                    }
                    .frame(maxWidth: .infinity, minHeight: Metrics.touch, alignment: .leading)
                    .contentShape(Rectangle())
                }
                .buttonStyle(.plain)
            }
            .padding(.bottom, 12)

            measure(L10n.countryPageStandardRate, countries, style: .figure(highest: highest)) { Format.percent($0.rates.standard) }

            Text(highest == lowest
                ? L10n.compareSameRate
                : "\(countries.first { $0.rates.standard == highest }?.localizedName ?? ""): \(L10n.compareHigherBy(points: Format.points(highest - lowest)))")
                .font(.subheadline)
                .foregroundStyle(Palette.inkMuted)
                .padding(.bottom, 12)

            measure(L10n.compareReducedRates, countries) { $0.rates.reducedRates.isEmpty ? L10n.compareNone : Format.rates($0.rates.reducedRates) }
            measure(L10n.compareSuperReduced, countries) { $0.rates.superReduced.map { Format.percent($0) } ?? L10n.compareNone }
            measure(L10n.compareParking, countries) { $0.rates.parking.map { Format.percent($0) } ?? L10n.compareNone }
            measure(L10n.compareExampleHeading, countries, note: L10n.compareExampleNote) {
                Format.money(VatCalculation(amount: 100, rate: $0.rates.standard, mode: .exclude).gross, currency: $0.currency)
            }
            measure(L10n.compareCurrency, countries, style: .machine) { $0.currency }
            measure(L10n.compareVies, countries) { $0.vatNumber != nil ? L10n.countryPageAvailable : L10n.countryPageNotAvailable }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .card()
    }

    private enum CellStyle {
        case text, machine
        /// A large figure over a bar drawn against the highest rate in the comparison.
        case figure(highest: Double)
    }

    /// One measure: its name across the card, then a value under each country.
    private func measure(_ label: String, _ countries: [Country], note: String? = nil, style: CellStyle = .text, text: @escaping (Country) -> String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Hairline()
            Text(label)
                .font(.label)
                .foregroundStyle(Palette.inkMuted)
                .padding(.top, 6)
                .accessibilityAddTraits(.isHeader)

            columns(countries) { country in
                Group {
                    switch style {
                    case .text:
                        Text(text(country))
                            .font(.body.monospacedDigit())
                    case .machine:
                        Text(text(country))
                            .font(.machine)
                    case let .figure(highest):
                        VStack(alignment: .leading, spacing: 6) {
                            Text(text(country))
                                .font(.system(.title, weight: .semibold).monospacedDigit())
                                .lineLimit(1)
                                .minimumScaleFactor(0.6)
                            RateBar(share: highest > 0 ? country.rates.standard / highest : 0)
                        }
                    }
                }
                .foregroundStyle(Palette.ink)
                .accessibilityElement(children: .ignore)
                .accessibilityLabel("\(country.localizedName), \(text(country))")
            }

            if let note {
                Text(note)
                    .font(.footnote)
                    .foregroundStyle(Palette.inkMuted)
            }
        }
        .padding(.bottom, 12)
    }

    private func columns(_ countries: [Country], @ViewBuilder cell: @escaping (Country) -> some View) -> some View {
        HStack(alignment: .top, spacing: 12) {
            ForEach(countries) { country in
                cell(country)
                    .frame(maxWidth: .infinity, alignment: .leading)
            }
        }
    }
}
