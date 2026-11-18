import SwiftUI

/// Every country with one of its rates, drawn to be compared: the answer most people open the app for.
struct RatesView: View {
    enum Order: String, CaseIterable {
        case name, highest, lowest

        var title: String {
            switch self {
            case .name: L10n.country
            case .highest: L10n.homePageStatHighest
            case .lowest: L10n.homePageStatLowest
            }
        }
    }

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router
    @Environment(FavouritesStore.self) private var favourites
    @Environment(CalculatorModel.self) private var calculator
    @Environment(ValidatorModel.self) private var validator
    @Environment(ComparisonModel.self) private var comparison

    @State private var query = ""
    @State private var showingAbout = false
    @AppStorage("rates.order") private var order: Order = .name
    /// Which rate the list shows: standard, reduced, super-reduced or parking.
    @AppStorage("rates.kind") private var kind: RateKind = .standard

    var body: some View {
        @Bindable var router = router
        // Bars are drawn against the highest rate of the chosen type, so they compare across sections.
        let highest = store.countries.compactMap { $0.rates.values(kind).max() }.max() ?? 0

        NavigationStack(path: $router.ratesPath) {
            List {
                if store.refreshFailed {
                    StaleDataNotice()
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                }

                let pinned = ordered(favourites.countries(among: store.countries))
                let members = ordered(store.memberStates)

                // The head of the first section carries the switch between the types of rate.
                section(L10n.appFavourites, pinned, highest: highest, leads: true, marksFavourites: false)
                section(L10n.calculatorGroupsEu, members, highest: highest, leads: pinned.isEmpty)
                section(L10n.calculatorGroupsOtherEurope, ordered(store.otherCountries), highest: highest, leads: pinned.isEmpty && members.isEmpty)

                if !store.isEmpty, query.isEmpty {
                    FreshnessLine(updated: store.fetchedAt)
                        .padding(.vertical, 8)
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                }
            }
            .listStyle(.plain)
            .scrollContentBackground(.hidden)
            .listColumn()
            .workspaceBackground()
            .overlay { emptyState }
            .refreshable { await store.refresh() }
            .searchable(text: $query, prompt: L10n.calculatorSearchCountries)
            .autocorrectionDisabled()
            .navigationTitle(L10n.countryPageVatRates)
            .navigationDestination(for: String.self) { CountryView(iso: $0) }
            .toolbar {
                ToolbarItem(placement: .topBarLeading) {
                    Button {
                        showingAbout = true
                    } label: {
                        Image(systemName: "info.circle")
                    }
                    .accessibilityLabel(L10n.sitemapAboutTitle)
                }

                ToolbarItem(placement: .topBarTrailing) {
                    Menu {
                        Picker(L10n.filters, selection: $order) {
                            ForEach(Order.allCases, id: \.self) { Text($0.title).tag($0) }
                        }
                    } label: {
                        Image(systemName: "arrow.up.arrow.down")
                    }
                    .accessibilityLabel(L10n.filters)
                }
            }
            .sheet(isPresented: $showingAbout) { AboutView() }
            .animation(.easeOut(duration: 0.2), value: kind)
            .animation(.easeOut(duration: 0.2), value: order)
        }
    }

    @ViewBuilder
    private var emptyState: some View {
        if store.isEmpty {
            ContentUnavailableView {
                Label(L10n.errorsDataLoadFailedTitle, systemImage: "wifi.slash")
            } description: {
                Text(L10n.calculatorUnavailableDesc)
            } actions: {
                Button(L10n.appTryAgain) {
                    Task { await store.refresh() }
                }
                .buttonStyle(.secondaryKey)
                .disabled(store.isRefreshing)
            }
        } else if matches(store.countries).isEmpty {
            ContentUnavailableView(L10n.noResults, systemImage: "magnifyingglass")
        }
    }

    private func matches(_ countries: [Country]) -> [Country] {
        let needle = query.searchNormalized

        return needle.isEmpty ? countries : countries.filter { $0.searchText.contains(needle) }
    }

    /// The matching countries in the chosen order. A country without the chosen rate goes last.
    private func ordered(_ countries: [Country]) -> [Country] {
        let countries = matches(countries)

        switch order {
        case .name:
            return countries
        case .highest:
            return countries.sorted { ($0.rates.values(kind).max() ?? -1) > ($1.rates.values(kind).max() ?? -1) }
        case .lowest:
            return countries.sorted { ($0.rates.values(kind).first ?? .infinity) < ($1.rates.values(kind).first ?? .infinity) }
        }
    }

    @ViewBuilder
    private func section(_ heading: String, _ countries: [Country], highest: Double, leads: Bool, marksFavourites: Bool = true) -> some View {
        if !countries.isEmpty {
            Section {
                ForEach(countries) { country in
                    row(country, highest: highest, marksFavourite: marksFavourites)
                }
            } header: {
                HStack(alignment: .firstTextBaseline) {
                    Text(heading)
                        .foregroundStyle(Palette.inkMuted)

                    Spacer(minLength: 12)

                    if leads {
                        kindMenu
                    } else {
                        Text(kind.title)
                            .foregroundStyle(Palette.inkMuted)
                    }
                }
                .font(.label)
            }
        }
    }

    /// The head of the figures column, which is also where the type of rate is chosen.
    private var kindMenu: some View {
        Menu {
            Picker(L10n.type, selection: $kind) {
                ForEach(RateKind.allCases, id: \.self) { Text($0.title).tag($0) }
            }
        } label: {
            HStack(spacing: 4) {
                Text(kind.title)
                Image(systemName: "chevron.up.chevron.down")
                    .font(.caption2.weight(.semibold))
                    .accessibilityHidden(true)
            }
            .foregroundStyle(Palette.action)
            .frame(minHeight: 32)
            .contentShape(Rectangle())
        }
        .accessibilityLabel(L10n.type)
        .accessibilityValue(kind.title)
    }

    private func row(_ country: Country, highest: Double, marksFavourite: Bool) -> some View {
        let values = country.rates.values(kind)
        let figure = values.isEmpty ? L10n.compareNone : Format.rates(values)
        let favourite = favourites.contains(country.isoCode)

        return NavigationLink(value: country.isoCode) {
            VStack(alignment: .leading, spacing: 8) {
                HStack(spacing: 12) {
                    FlagView(iso: country.isoCode)
                    Text(country.localizedName)
                        .foregroundStyle(Palette.ink)

                    if favourite, marksFavourite {
                        Image(systemName: "star.fill")
                            .font(.caption2)
                            .foregroundStyle(Palette.inkQuiet)
                            .accessibilityHidden(true)
                    }

                    Spacer(minLength: 12)
                    Text(figure)
                        .font(values.isEmpty ? .body : .figure)
                        .foregroundStyle(values.isEmpty ? Palette.inkQuiet : Palette.ink)
                        .contentTransition(.numericText())
                }

                RateBar(
                    share: highest > 0 ? (values.first ?? 0) / highest : 0,
                    upper: values.count > 1 && highest > 0 ? (values.last ?? 0) / highest : nil,
                    height: 3
                )
            }
            .padding(.vertical, 4)
            .accessibilityElement(children: .combine)
            .accessibilityLabel("\(country.localizedName), \(kind.title) \(figure)")
        }
        .listRowBackground(Palette.surface)
        .listRowSeparatorTint(Palette.line)
        .swipeActions(edge: .leading, allowsFullSwipe: true) {
            Button {
                favourites.toggle(country.isoCode)
            } label: {
                Label(L10n.appFavourites, systemImage: favourite ? "star.slash" : "star")
            }
            .tint(Palette.inkMuted)
        }
        .swipeActions(edge: .trailing, allowsFullSwipe: false) {
            Button {
                open(.calculator, country)
            } label: {
                Label(L10n.bottomNavCalculator, systemImage: "plus.forwardslash.minus")
            }
            .tint(Palette.button)
        }
        .contextMenu {
            Button(L10n.appFavourites, systemImage: favourite ? "star.slash" : "star") {
                favourites.toggle(country.isoCode)
            }

            Divider()

            Button(L10n.countryPageVatCalculator, systemImage: "plus.forwardslash.minus") {
                open(.calculator, country)
            }

            if country.vatNumber != nil {
                Button(L10n.countryPageVatValidator, systemImage: "checkmark.seal") {
                    open(.validator, country)
                }
            }

            Button(L10n.navCompare, systemImage: "rectangle.split.2x1") {
                open(.compare, country)
            }
        }
    }

    /// Carries a country into one of the tools.
    private func open(_ tab: AppTab, _ country: Country) {
        switch tab {
        case .calculator:
            calculator.open(country, choice: kindChoice(for: country))
        case .validator:
            validator.choose(country)
        case .compare:
            comparison.include(country.isoCode)
            router.comparePath = []
        case .rates, .history:
            break
        }

        router.tab = tab
    }

    /// The calculator opens at the rate the list is showing, when the country has it.
    private func kindChoice(for country: Country) -> RateChoice {
        switch kind {
        case .standard: .standard
        case .reduced: country.rates.reducedRates.first.map { .reduced($0) } ?? .standard
        case .superReduced: country.rates.superReduced == nil ? .standard : .superReduced
        case .parking: country.rates.parking == nil ? .standard : .parking
        }
    }
}
