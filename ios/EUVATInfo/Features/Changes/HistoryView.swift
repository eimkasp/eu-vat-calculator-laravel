import SwiftUI

/// Every recorded change of a VAT rate, newest first, with filters by country and direction.
struct HistoryView: View {
    private enum DirectionFilter: Hashable {
        case all, increases, decreases
    }

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router

    @State private var countryFilter: String?
    @State private var directionFilter: DirectionFilter = .all

    var body: some View {
        @Bindable var router = router
        let changes = filtered

        NavigationStack(path: $router.historyPath) {
            List {
                if store.refreshFailed {
                    StaleDataNotice()
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                }

                ForEach(years(changes), id: \.self) { year in
                    Section {
                        ForEach(changes.filter { self.year($0) == year }) { change in
                            NavigationLink(value: change.country.code) {
                                ChangeRow(change: change, showsCountry: true)
                                    .padding(.vertical, 4)
                            }
                            .listRowBackground(Palette.surface)
                            .listRowSeparatorTint(Palette.line)
                        }
                    } header: {
                        Text(verbatim: year.map(String.init) ?? "—")
                            .font(.label)
                            .foregroundStyle(Palette.inkMuted)
                    }
                }
            }
            .listStyle(.plain)
            .scrollContentBackground(.hidden)
            .listColumn()
            .workspaceBackground()
            .overlay {
                if changes.isEmpty {
                    ContentUnavailableView {
                        Label(L10n.historyNoChanges, systemImage: "clock.arrow.circlepath")
                    } actions: {
                        if isFiltered {
                            Button(L10n.historyClearFilters, action: clearFilters)
                                .buttonStyle(.secondaryKey)
                        }
                    }
                }
            }
            .refreshable { await store.refresh() }
            .navigationTitle(L10n.navVatHistory)
            .navigationDestination(for: String.self) { CountryView(iso: $0) }
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    Menu {
                        Picker(L10n.country, selection: $countryFilter) {
                            Text(L10n.historyAllCountries).tag(String?.none)

                            ForEach(countriesWithChanges) { country in
                                Text(country.localizedName).tag(String?.some(country.isoCode))
                            }
                        }
                        .pickerStyle(.menu)

                        Picker(L10n.historyDirection, selection: $directionFilter) {
                            Text(L10n.historyAllDirections).tag(DirectionFilter.all)
                            Text(L10n.historyIncreases).tag(DirectionFilter.increases)
                            Text(L10n.historyDecreases).tag(DirectionFilter.decreases)
                        }

                        if isFiltered {
                            Button(L10n.historyClearFilters, systemImage: "xmark", action: clearFilters)
                        }
                    } label: {
                        Image(systemName: isFiltered ? "line.3.horizontal.decrease.circle.fill" : "line.3.horizontal.decrease.circle")
                    }
                    .accessibilityLabel(L10n.filters)
                }
            }
        }
    }

    private var isFiltered: Bool {
        countryFilter != nil || directionFilter != .all
    }

    private func clearFilters() {
        countryFilter = nil
        directionFilter = .all
    }

    private var filtered: [RateChange] {
        store.changes.filter { change in
            (countryFilter == nil || change.country.code == countryFilter)
                && (directionFilter == .all || (directionFilter == .increases) == (change.direction == .increase))
        }
    }

    private var countriesWithChanges: [Country] {
        let codes = Set(store.changes.map(\.country.code))

        return store.countries.filter { codes.contains($0.isoCode) }
    }

    private func year(_ change: RateChange) -> Int? {
        change.changeDate.map { Calendar(identifier: .gregorian).component(.year, from: $0) }
    }

    /// The years that have changes, newest first, with undated changes last.
    private func years(_ changes: [RateChange]) -> [Int?] {
        var seen: [Int?] = []

        for change in changes where !seen.contains(year(change)) {
            seen.append(year(change))
        }

        return seen
    }
}

/// One change: the old and new rate, when it took effect and which way it went.
struct ChangeRow: View {
    let change: RateChange
    let showsCountry: Bool

    @Environment(RatesStore.self) private var store

    var body: some View {
        let increase = change.direction == .increase
        let name = store.country(iso: change.country.code)?.localizedName ?? change.country.name

        VStack(alignment: .leading, spacing: 6) {
            if showsCountry {
                HStack(spacing: 10) {
                    FlagView(iso: change.country.code)
                    Text(name)
                        .font(.body.weight(.medium))
                        .foregroundStyle(Palette.ink)
                }
            }

            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(Format.percent(change.oldRate))
                    .foregroundStyle(Palette.inkMuted)
                Image(systemName: "arrow.right")
                    .font(.footnote.weight(.semibold))
                    .foregroundStyle(Palette.inkQuiet)
                    .accessibilityHidden(true)
                Text(Format.percent(change.newRate))
                    .foregroundStyle(Palette.ink)

                Spacer(minLength: 8)

                Label {
                    Text("\(increase ? L10n.rateChangesIncrease : L10n.rateChangesDecrease), \(L10n.rateChangesPoints(value: Format.points(change.points)))")
                } icon: {
                    Image(systemName: increase ? "arrow.up.right" : "arrow.down.right")
                }
                .labelStyle(.tightIcon)
                .font(.footnote.weight(.medium))
                .foregroundStyle(increase ? Palette.danger : Palette.success)
            }
            .font(.figure)

            HStack(spacing: 8) {
                if let date = change.changeDate {
                    Text(change.isUpcoming() ? L10n.historyEffectiveFrom(date: Format.date(date)) : L10n.historyChangedOn(date: Format.date(date)))
                }

                if change.kind != .standard {
                    Text(change.kind.title)
                }

                if change.isUpcoming() {
                    Text(L10n.historyUpcomingBadge)
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(Palette.warning)
                        .padding(.horizontal, 6)
                        .padding(.vertical, 2)
                        .background(Palette.warningSoft, in: RoundedRectangle(cornerRadius: 4))
                }
            }
            .font(.footnote)
            .foregroundStyle(Palette.inkMuted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .accessibilityElement(children: .combine)
    }
}
