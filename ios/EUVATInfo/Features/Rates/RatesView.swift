import SwiftUI

/// Every country with its standard rate: the answer most people open the app for.
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

    @State private var query = ""
    @State private var showingAbout = false
    @AppStorage("rates.order") private var order: Order = .name

    var body: some View {
        @Bindable var router = router

        NavigationStack(path: $router.ratesPath) {
            List {
                if store.refreshFailed {
                    StaleDataNotice()
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                }

                section(L10n.calculatorGroupsEu, ordered(store.memberStates))
                section(L10n.calculatorGroupsOtherEurope, ordered(store.otherCountries))

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

    private func ordered(_ countries: [Country]) -> [Country] {
        let countries = matches(countries)

        switch order {
        case .name: return countries
        case .highest: return countries.sorted { $0.rates.standard > $1.rates.standard }
        case .lowest: return countries.sorted { $0.rates.standard < $1.rates.standard }
        }
    }

    @ViewBuilder
    private func section(_ heading: String, _ countries: [Country]) -> some View {
        if !countries.isEmpty {
            Section {
                ForEach(countries) { country in
                    NavigationLink(value: country.isoCode) {
                        HStack(spacing: 12) {
                            FlagView(iso: country.isoCode)
                            Text(country.localizedName)
                                .foregroundStyle(Palette.ink)
                            Spacer(minLength: 12)
                            Text(Format.percent(country.rates.standard))
                                .font(.figure)
                                .foregroundStyle(Palette.ink)
                        }
                        .frame(minHeight: 32)
                        .accessibilityElement(children: .combine)
                        .accessibilityLabel("\(country.localizedName), \(L10n.countryPageStandardRate) \(Format.percent(country.rates.standard))")
                    }
                    .listRowBackground(Palette.surface)
                    .listRowSeparatorTint(Palette.line)
                }
            } header: {
                Text(heading)
                    .font(.label)
                    .foregroundStyle(Palette.inkMuted)
            }
        }
    }
}
