import SwiftUI

/// Every recorded change of a VAT rate, newest first, with filters by country and direction.
struct HistoryView: View {
    private enum DirectionFilter: Hashable {
        case all, increases, decreases
    }

    /// Marks the first change of a year, so the chart can scroll the list to it.
    private struct YearAnchor: Hashable {
        let year: Int
    }

    @Environment(RatesStore.self) private var store
    @Environment(AppRouter.self) private var router

    @State private var countryFilter: String?
    @State private var directionFilter: DirectionFilter = .all

    var body: some View {
        @Bindable var router = router
        let changes = filtered
        let years = years(changes)

        NavigationStack(path: $router.historyPath) {
            ScrollViewReader { proxy in
                List {
                    if store.refreshFailed {
                        StaleDataNotice()
                            .listRowBackground(Color.clear)
                            .listRowSeparator(.hidden)
                    }

                    filters
                        .listRowInsets(EdgeInsets(top: 4, leading: Metrics.gutter, bottom: 4, trailing: Metrics.gutter))
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)

                    if changes.isEmpty {
                        ContentUnavailableView {
                            Label(L10n.historyNoChanges, systemImage: "clock.arrow.circlepath")
                        } actions: {
                            if isFiltered {
                                Button(L10n.historyClearFilters, action: clearFilters)
                                    .buttonStyle(.secondaryKey)
                            }
                        }
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                    } else {
                        ChangesChart(summary: ChangesByYear(changes: changes)) { year in
                            // A year without a change of its own leads to the one before it.
                            guard let target = years.compactMap({ $0 }).first(where: { $0 <= year }) else { return }

                            withAnimation(.easeOut(duration: 0.2)) { proxy.scrollTo(YearAnchor(year: target), anchor: .top) }
                        }
                        .padding(16)
                        .card()
                        .listRowInsets(EdgeInsets(top: 8, leading: Metrics.gutter, bottom: 8, trailing: Metrics.gutter))
                        .listRowBackground(Color.clear)
                        .listRowSeparator(.hidden)
                    }

                    ForEach(years, id: \.self) { year in
                        let rows = changes.filter { self.year($0) == year }

                        Section {
                            ForEach(rows) { change in
                                NavigationLink(value: change.country.code) {
                                    ChangeRow(change: change, showsCountry: true)
                                        .padding(.vertical, 4)
                                }
                                .listRowBackground(Palette.surface)
                                .listRowSeparatorTint(Palette.line)
                                .id(anchor(for: change, firstOf: rows, in: year))
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
                .refreshable { await store.refresh() }
                .navigationTitle(L10n.navVatHistory)
                .navigationDestination(for: String.self) { CountryView(iso: $0) }
                .animation(.easeOut(duration: 0.2), value: countryFilter)
                .animation(.easeOut(duration: 0.2), value: directionFilter)
            }
        }
    }

    /// The filters, in sight above the list: the country, then the direction of the change.
    private var filters: some View {
        FlowLayout(spacing: 8) {
            countryMenu

            ChoiceChip(label: L10n.historyAllDirections, selected: directionFilter == .all) { directionFilter = .all }
            ChoiceChip(label: L10n.historyIncreases, selected: directionFilter == .increases) { directionFilter = .increases }
            ChoiceChip(label: L10n.historyDecreases, selected: directionFilter == .decreases) { directionFilter = .decreases }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private var countryMenu: some View {
        let chosen = countryFilter.flatMap(store.country(iso:))
        let shape = RoundedRectangle(cornerRadius: Metrics.controlRadius)

        return Menu {
            Picker(L10n.country, selection: $countryFilter) {
                Text(L10n.historyAllCountries).tag(String?.none)

                ForEach(countriesWithChanges) { country in
                    Text(country.localizedName).tag(String?.some(country.isoCode))
                }
            }
        } label: {
            HStack(spacing: 6) {
                if let chosen {
                    FlagView(iso: chosen.isoCode, height: 12)
                }

                Text(chosen?.localizedName ?? L10n.historyAllCountries)
                    .font(.subheadline.weight(.medium))
                    .lineLimit(1)
                Image(systemName: "chevron.down")
                    .font(.caption2.weight(.semibold))
                    .accessibilityHidden(true)
            }
            .foregroundStyle(chosen == nil ? Palette.ink : Palette.actionDeep)
            .padding(.horizontal, 12)
            .frame(minHeight: Metrics.touch)
            .background(chosen == nil ? Palette.keyFace : Palette.actionSoft, in: shape)
            .overlay(shape.strokeBorder(chosen == nil ? Palette.lineStrong : Palette.action, lineWidth: 1))
            .contentShape(shape)
        }
        .accessibilityLabel(L10n.country)
        .accessibilityValue(chosen?.localizedName ?? L10n.historyAllCountries)
    }

    /// The first change of a year answers to the year; every other row keeps its own identity.
    private func anchor(for change: RateChange, firstOf rows: [RateChange], in year: Int?) -> AnyHashable {
        if let year, change.id == rows.first?.id {
            AnyHashable(YearAnchor(year: year))
        } else {
            AnyHashable(change.id)
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
        change.changeDate.map { ChangesByYear.calendar.component(.year, from: $0) }
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
