import SwiftUI

/// Add VAT to a net amount or take it out of a total, at any of a country's rates. Works without a connection.
struct CalculatorView: View {
    private enum Field { case amount, customRate }
    private enum Anchor { case total }

    @Environment(RatesStore.self) private var store
    @Environment(CalculatorModel.self) private var model
    @Environment(ToastCenter.self) private var toasts

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
                            recent(country)
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
                .onChange(of: focus) { old, field in
                    if field == nil, old != nil {
                        rememberCalculation()
                    }

                    guard field == .amount else { return }

                    // Once the number pad is up, rest the total just above it so it stays in sight while typing.
                    Task {
                        try? await Task.sleep(for: .milliseconds(300))
                        withAnimation(.easeOut(duration: 0.2)) { proxy.scrollTo(Anchor.total, anchor: .bottom) }
                    }
                }
            }
            .onDisappear(perform: rememberCalculation)
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
            .sensoryFeedback(.selection, trigger: model.mode)

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

                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 8) {
                        ForEach(CalculatorModel.commonAmounts, id: \.self) { amount in
                            ChoiceChip(label: Format.amount(amount), selected: model.amount == amount) {
                                model.setAmount(amount)
                            }
                        }
                    }
                }
                .accessibilityElement(children: .contain)
                .accessibilityLabel(L10n.calculatorQuickAmounts)
            }

            field(L10n.calculatorVatRate) {
                FlowLayout(spacing: 8) {
                    ForEach(model.options(for: country)) { option in
                        ChoiceChip(label: option.label, selected: model.effectiveChoice(for: country) == option.choice) {
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
                        .textSelection(.enabled)

                    Meter(share: calculation.vatShare)
                        .padding(.top, 10)
                }
                .accessibilityElement(children: .combine)
                .id(Anchor.total)

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
                        Clipboard.copy(model.shareText(calculation, in: country))
                        toasts.show(L10n.calculatorCopied)
                        model.remember(calculation, in: country)
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
                    .simultaneousGesture(TapGesture().onEnded { model.remember(calculation, in: country) })
                }
            }
            .padding(20)
            .frame(maxWidth: .infinity, alignment: .leading)
            .card(fill: Palette.surfaceSubtle)
            .animation(.easeOut(duration: 0.2), value: calculation)
        }
    }

    /// Earlier calculations, without the one on screen. A row puts that calculation back.
    @ViewBuilder
    private func recent(_ country: Country) -> some View {
        let current = model.calculation(for: country).map {
            RecentCalculation(countryCode: country.isoCode, amount: $0.input, rate: $0.rate, mode: $0.mode).id
        }
        let entries = model.recent.filter { $0.id != current && store.country(iso: $0.countryCode) != nil }

        if !entries.isEmpty {
            VStack(alignment: .leading, spacing: 0) {
                HStack(alignment: .firstTextBaseline) {
                    Text(L10n.calculatorRecentCalculations)
                        .font(.section)
                        .foregroundStyle(Palette.ink)
                        .accessibilityAddTraits(.isHeader)
                    Spacer()
                    Button(L10n.calculatorClearAll) { model.clearRecent() }
                        .font(.subheadline.weight(.medium))
                }
                .padding(.bottom, 8)

                ForEach(entries) { entry in
                    if let place = store.country(iso: entry.countryCode) {
                        let calculation = entry.calculation

                        Hairline()

                        Button {
                            model.restore(entry, in: store)
                        } label: {
                            HStack(spacing: 12) {
                                FlagView(iso: place.isoCode)
                                Text(model.summary(calculation, in: place))
                                    .font(.subheadline)
                                    .foregroundStyle(Palette.inkMuted)
                                    .multilineTextAlignment(.leading)
                                Spacer(minLength: 8)
                                Text(Format.money(calculation.mode == .exclude ? calculation.gross : calculation.net, currency: place.currency))
                                    .font(.body.weight(.medium).monospacedDigit())
                                    .foregroundStyle(Palette.ink)
                            }
                            .frame(minHeight: Metrics.touch)
                            .padding(.vertical, 4)
                            .contentShape(Rectangle())
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
            .padding(16)
            .frame(maxWidth: .infinity, alignment: .leading)
            .card()
            .sensoryFeedback(.selection, trigger: model.countryCode)
        }
    }

    private func rememberCalculation() {
        guard let country = model.country(in: store), let calculation = model.calculation(for: country) else { return }

        model.remember(calculation, in: country)
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
                .textSelection(.enabled)
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
