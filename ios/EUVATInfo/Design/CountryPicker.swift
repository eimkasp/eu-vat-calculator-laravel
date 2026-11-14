import SwiftUI

/// A searchable list of countries in a sheet, grouped into member states and other European countries.
struct CountryPicker: View {
    let title: String
    let countries: [Country]
    var selected: String?
    /// Codes that cannot be chosen, such as the countries already in a comparison.
    var disabled: Set<String> = []
    let onSelect: (Country) -> Void

    @Environment(\.dismiss) private var dismiss
    @State private var query = ""

    var body: some View {
        NavigationStack {
            List {
                section(L10n.calculatorGroupsEu, matches.filter(\.isEUMember))
                section(L10n.calculatorGroupsOtherEurope, matches.filter { !$0.isEUMember })
            }
            .listStyle(.plain)
            .scrollContentBackground(.hidden)
            .workspaceBackground()
            .overlay {
                if matches.isEmpty {
                    ContentUnavailableView(L10n.calculatorNoCountriesFound, systemImage: "magnifyingglass")
                }
            }
            .searchable(text: $query, placement: .navigationBarDrawer(displayMode: .always), prompt: L10n.calculatorSearchCountries)
            .autocorrectionDisabled()
            .navigationTitle(title)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button {
                        dismiss()
                    } label: {
                        Image(systemName: "xmark")
                    }
                    .accessibilityLabel(L10n.close)
                }
            }
        }
    }

    private var matches: [Country] {
        let needle = query.searchNormalized

        return needle.isEmpty ? countries : countries.filter { $0.searchText.contains(needle) }
    }

    @ViewBuilder
    private func section(_ heading: String, _ countries: [Country]) -> some View {
        if !countries.isEmpty {
            Section {
                ForEach(countries) { country in
                    Button {
                        onSelect(country)
                        dismiss()
                    } label: {
                        HStack(spacing: 12) {
                            FlagView(iso: country.isoCode)
                            Text(country.localizedName)
                                .foregroundStyle(Palette.ink)
                            Spacer()
                            Text(Format.percent(country.rates.standard))
                                .font(.body.monospacedDigit())
                                .foregroundStyle(Palette.inkMuted)

                            if country.isoCode == selected {
                                Image(systemName: "checkmark")
                                    .foregroundStyle(Palette.action)
                                    .accessibilityHidden(true)
                            }
                        }
                        .frame(minHeight: 28)
                        .contentShape(Rectangle())
                    }
                    .disabled(disabled.contains(country.isoCode))
                    .opacity(disabled.contains(country.isoCode) ? 0.45 : 1)
                    .accessibilityAddTraits(country.isoCode == selected ? .isSelected : [])
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

/// The key that opens the picker: the chosen country's flag and name, or a prompt.
struct CountryKey: View {
    let country: Country?
    var prompt: String = L10n.calculatorSelectCountry
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack(spacing: 10) {
                if let country {
                    FlagView(iso: country.isoCode)
                    Text(country.localizedName)
                } else {
                    Text(prompt)
                        .foregroundStyle(Palette.inkMuted)
                }

                Spacer(minLength: 8)
                Image(systemName: "chevron.up.chevron.down")
                    .font(.footnote.weight(.semibold))
                    .foregroundStyle(Palette.inkQuiet)
                    .accessibilityHidden(true)
            }
            .frame(maxWidth: .infinity)
        }
        .buttonStyle(KeyButtonStyle(kind: .secondary, fullWidth: true))
    }
}
