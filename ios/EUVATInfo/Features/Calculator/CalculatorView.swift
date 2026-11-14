import SwiftUI

/// Add VAT to a net amount or take it out of a total, at any of a country's rates. Works without a connection.
struct CalculatorView: View {
    private enum Field { case amount, customRate }

    @Environment(RatesStore.self) private var store
    @Environment(CalculatorModel.self) private var model

    @State private var pickingCountry = false
    @FocusState private var focus: Field?
    @ScaledMetric(relativeTo: .largeTitle) private var heroSize: CGFloat = 44

    var body: some View {
        NavigationStack {
            ScrollViewReader { proxy in
                ScrollView {
                    VStack(alignment: .leading, spacing: 20) {
                        StaleDataNotice()

                        if let country = model.country(in: store) {
                            form(country)
                            result(country)
                            FreshnessLine(updated: country.lastUpdated ?? store.fetchedAt, isEU: country.isEUMember)
                        } else {
                            ContentUnavailableView {
                                Label(L10n.errorsDataLoadFailedTitle, systemImage: "wifi.slash")
                            } description: {
                                Text(L10n.calculatorUnavailableDesc)
                            } actions: {
                                Button(L10n.appTryAgain) {
                                    Task { await store.refresh() }
                                }
                                .buttonStyle(.secondaryKey)
                            }
                        }
                    }
                    .padding(.vertical, 16)
                    .contentColumn()
                }
                .onChange(of: focus) { _, field in
                    guard field == .amount else { return }

                    // Once the number pad is up, bring the amount to the top so the total under it stays in sight.
                    Task {
                        try? await Task.sleep(for: .milliseconds(300))
                        withAnimation(.easeOut(duration: 0.2)) { proxy.scrollTo(Field.amount, anchor: .top) }
                    }
                }
            }
            .scrollDismissesKeyboard(.interactively)
            .workspaceBackground()
            .navigationTitle(L10n.calculatorTitle)
            .sheet(isPresented: $pickingCountry) {
                CountryPicker(title: L10n.calculatorSelectCountry, countries: store.countries, selected: model.country(in: store)?.isoCode) {
                    model.countryCode = $0.isoCode
                }
            }
        }
    }

    private func form(_ country: Country) -> some View {
        @Bindable var model = model

        return VStack(alignment: .leading, spacing: 16) {
            field(L10n.country) {
                CountryKey(country: country) { pickingCountry = true }
            }

            Picker(L10n.calculatorModeLabel, selection: $model.mode) {
                Text(L10n.calculatorAddVatMode).tag(VatMode.exclude)
                Text(L10n.calculatorRemoveVatMode).tag(VatMode.include)
            }
            .pickerStyle(.segmented)

            field(model.mode == .exclude ? L10n.calculatorAmountExclVat : L10n.calculatorAmountInclVat) {
                HStack(alignment: .firstTextBaseline, spacing: 8) {
                    TextField("0", text: $model.amountText)
                        .keyboardType(.decimalPad)
                        .focused($focus, equals: .amount)
                        .font(.system(.title, weight: .semibold).monospacedDigit())
                        .foregroundStyle(Palette.ink)
                        .accessibilityLabel(model.mode == .exclude ? L10n.calculatorAmountExclVat : L10n.calculatorAmountInclVat)

                    Text(country.currency)
                        .font(.machine)
                        .foregroundStyle(Palette.inkMuted)

                    if focus == .amount {
                        dismissKey
                    }
                }
                .padding(.leading, 12)
                .padding(.trailing, focus == .amount ? 0 : 12)
                .frame(minHeight: 52)
                .background(Palette.surface, in: RoundedRectangle(cornerRadius: Metrics.controlRadius))
                .overlay(
                    RoundedRectangle(cornerRadius: Metrics.controlRadius)
                        .strokeBorder(model.amountIsInvalid ? Palette.danger : focus == .amount ? Palette.action : Palette.inkQuiet, lineWidth: focus == .amount ? 2 : 1)
                )

                if model.amountIsInvalid {
                    Label(L10n.calculatorInvalidAmount, systemImage: "exclamationmark.circle")
                        .labelStyle(.tightIcon)
                        .font(.footnote)
                        .foregroundStyle(Palette.danger)
                }
            }
            .id(Field.amount)

            field(L10n.calculatorVatRate) {
                FlowLayout(spacing: 8) {
                    ForEach(model.options(for: country)) { option in
                        RateChip(label: option.label, selected: model.effectiveChoice(for: country) == option.choice) {
                            model.choice = option.choice

                            if option.choice == .custom {
                                // The field appears with this change, so it can take focus only on the next pass.
                                Task { focus = .customRate }
                            }
                        }
                    }
                }

                if model.effectiveChoice(for: country) == .custom {
                    HStack(alignment: .firstTextBaseline, spacing: 8) {
                        TextField(L10n.calculatorCustomPercent, text: $model.customRateText)
                            .keyboardType(.decimalPad)
                            .focused($focus, equals: .customRate)
                            .font(.body.monospacedDigit())
                        Text(verbatim: "%")
                            .foregroundStyle(Palette.inkMuted)

                        if focus == .customRate {
                            dismissKey
                        }
                    }
                    .padding(.leading, 12)
                    .padding(.trailing, focus == .customRate ? 0 : 12)
                    .frame(minHeight: Metrics.touch)
                    .background(Palette.surface, in: RoundedRectangle(cornerRadius: Metrics.controlRadius))
                    .overlay(
                        RoundedRectangle(cornerRadius: Metrics.controlRadius)
                            .strokeBorder(model.customRateIsInvalid ? Palette.danger : focus == .customRate ? Palette.action : Palette.inkQuiet, lineWidth: focus == .customRate ? 2 : 1)
                    )

                    Text(L10n.calculatorEnterCustom)
                        .font(.footnote)
                        .foregroundStyle(model.customRateIsInvalid ? Palette.danger : Palette.inkMuted)
                }
            }
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .panel()
    }

    @ViewBuilder
    private func result(_ country: Country) -> some View {
        if let calculation = model.calculation(for: country) {
            let answerIsGross = calculation.mode == .exclude
            let rateLabel = L10n.calculatorVatLabel(rate: Format.rateNumber(calculation.rate))
            let summary = model.summary(calculation, in: country)

            VStack(alignment: .leading, spacing: 0) {
                VStack(alignment: .leading, spacing: 2) {
                    Text(answerIsGross ? L10n.calculatorTotalInclVat : L10n.calculatorNetAmount)
                        .font(.label)
                        .foregroundStyle(Palette.inkMuted)
                    Text(Format.money(answerIsGross ? calculation.gross : calculation.net, currency: country.currency))
                        .font(.system(size: heroSize, weight: .semibold).monospacedDigit())
                        .tracking(-0.03 * heroSize)
                        .foregroundStyle(Palette.ink)
                        .lineLimit(1)
                        .minimumScaleFactor(0.4)
                        .contentTransition(.numericText())
                }
                .accessibilityElement(children: .combine)

                Meter(share: calculation.vatShare)
                    .padding(.top, 12)

                Text(summary)
                    .font(.subheadline)
                    .foregroundStyle(Palette.inkMuted)
                    .padding(.top, 10)
                    .padding(.bottom, 8)

                Hairline()
                amountRow(answerIsGross ? L10n.calculatorNetAmount : L10n.calculatorTotalInclVat, answerIsGross ? calculation.net : calculation.gross, country)
                Hairline()
                amountRow(rateLabel, calculation.vat, country)
                Hairline()

                Text(answerIsGross
                    ? L10n.calculatorFormulaAdd(multiplier: Format.multiplier(calculation.rate))
                    : L10n.calculatorFormulaRemove(multiplier: Format.multiplier(calculation.rate)))
                    .font(.machine)
                    .foregroundStyle(Palette.inkMuted)
                    .padding(.vertical, 12)

                HStack(spacing: 10) {
                    Button {
                        Clipboard.copy(model.shareText(calculation, in: country), announcement: L10n.calculatorCopied)
                    } label: {
                        Label(L10n.calculatorCopyResult, systemImage: "doc.on.doc")
                            .labelStyle(.tightIcon)
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    .buttonStyle(KeyButtonStyle(kind: .secondary, fullWidth: true))

                    ShareLink(item: model.shareText(calculation, in: country)) {
                        Image(systemName: "square.and.arrow.up")
                    }
                    .buttonStyle(.secondaryKey)
                }
            }
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .card(fill: Palette.surfaceSubtle)
            .animation(.easeOut(duration: 0.2), value: calculation)
        }
    }

    /// Puts the number pad away: it has no return key of its own.
    private var dismissKey: some View {
        Button {
            focus = nil
        } label: {
            Image(systemName: "keyboard.chevron.compact.down")
                .frame(width: Metrics.touch, height: Metrics.touch)
        }
        .accessibilityLabel(L10n.close)
        .accessibilityIdentifier("dismissKeyboard")
    }

    private func amountRow(_ label: String, _ value: Double, _ country: Country) -> some View {
        InfoRow(label: label) {
            Text(Format.money(value, currency: country.currency))
                .font(.figure)
                .foregroundStyle(Palette.ink)
        }
    }

    private func field(_ label: String, @ViewBuilder content: () -> some View) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(label)
                .font(.label)
                .foregroundStyle(Palette.inkMuted)
            content()
        }
    }
}

/// A rate you can pick: a small key that stays pressed in while it is the chosen one.
private struct RateChip: View {
    let label: String
    let selected: Bool
    let action: () -> Void

    var body: some View {
        let shape = RoundedRectangle(cornerRadius: Metrics.controlRadius)

        Button(action: action) {
            HStack(spacing: 6) {
                if selected {
                    Image(systemName: "checkmark")
                        .font(.footnote.weight(.semibold))
                        .accessibilityHidden(true)
                }

                Text(label)
                    .font(.subheadline.weight(.medium))
            }
            .foregroundStyle(selected ? Palette.actionDeep : Palette.ink)
            .padding(.horizontal, 12)
            .frame(minHeight: Metrics.touch)
            .background(selected ? Palette.actionSoft : Palette.keyFace, in: shape)
            .overlay(shape.strokeBorder(selected ? Palette.action : Palette.lineStrong, lineWidth: 1))
            .contentShape(shape)
        }
        .buttonStyle(.plain)
        .accessibilityAddTraits(selected ? .isSelected : [])
    }
}

/// Fifty cells under the total: the cobalt ones are the share of it that is VAT.
struct Meter: View {
    let share: Double

    var body: some View {
        Canvas { context, size in
            let cells = 50
            let gap: CGFloat = 1.5
            let width = (size.width - gap * CGFloat(cells - 1)) / CGFloat(cells)
            let filled = share > 0 ? max(1, Int((share * Double(cells)).rounded())) : 0

            for index in 0 ..< cells {
                let cell = CGRect(x: CGFloat(index) * (width + gap), y: 0, width: width, height: size.height)
                context.fill(Path(cell), with: .color(index >= cells - filled ? Palette.action : Palette.lineStrong))
            }
        }
        .frame(height: 12)
        .accessibilityHidden(true)
    }
}

/// Lays chips out in rows, wrapping to the next row when one is full.
struct FlowLayout: Layout {
    var spacing: CGFloat = 8

    func sizeThatFits(proposal: ProposedViewSize, subviews: Subviews, cache: inout ()) -> CGSize {
        let rows = arrange(subviews, width: proposal.width ?? .infinity)
        let width = rows.map(\.width).max() ?? 0

        return CGSize(width: proposal.width.map { min($0, width) } ?? width, height: rows.last.map { $0.y + $0.height } ?? 0)
    }

    func placeSubviews(in bounds: CGRect, proposal: ProposedViewSize, subviews: Subviews, cache: inout ()) {
        for row in arrange(subviews, width: bounds.width) {
            for item in row.items {
                subviews[item.index].place(
                    at: CGPoint(x: bounds.minX + item.x, y: bounds.minY + row.y),
                    proposal: ProposedViewSize(width: item.size.width, height: row.height)
                )
            }
        }
    }

    private struct Row {
        var items: [(index: Int, x: CGFloat, size: CGSize)] = []
        var y: CGFloat = 0
        var width: CGFloat = 0
        var height: CGFloat = 0
    }

    private func arrange(_ subviews: Subviews, width: CGFloat) -> [Row] {
        var rows = [Row()]

        for (index, subview) in subviews.enumerated() {
            var size = subview.sizeThatFits(.unspecified)
            size.width = min(size.width, width)
            var row = rows[rows.count - 1]

            if !row.items.isEmpty, row.width + spacing + size.width > width {
                rows.append(Row(y: row.y + row.height + spacing))
                row = rows[rows.count - 1]
            }

            let x = row.items.isEmpty ? 0 : row.width + spacing
            row.items.append((index, x, size))
            row.width = x + size.width
            row.height = max(row.height, size.height)
            rows[rows.count - 1] = row
        }

        return rows
    }
}
