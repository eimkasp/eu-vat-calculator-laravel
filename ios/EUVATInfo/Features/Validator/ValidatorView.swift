import SwiftUI

/// Checks an EU VAT number in the Commission's VIES register.
struct ValidatorView: View {
    private enum Anchor { case outcome }

    @Environment(RatesStore.self) private var store
    @Environment(ValidatorModel.self) private var model
    @Environment(ToastCenter.self) private var toasts

    @State private var pickingCountry = false
    @FocusState private var numberFocused: Bool

    var body: some View {
        NavigationStack {
            ScrollViewReader { proxy in
                ScrollView {
                    VStack(alignment: .leading, spacing: 20) {
                        form
                        outcome
                            .id(Anchor.outcome)
                        recent

                        Text(L10n.viesPageDisclaimer)
                            .font(.footnote)
                            .foregroundStyle(Palette.inkMuted)
                    }
                    .padding(.vertical, 16)
                    .contentColumn()
                }
                .onChange(of: model.phase) { _, phase in
                    guard case .done = phase else { return }

                    // The answer can land below the fold on a small phone: bring it into view.
                    withAnimation(.easeOut(duration: 0.2)) { proxy.scrollTo(Anchor.outcome, anchor: .center) }
                }
            }
            .scrollDismissesKeyboard(.interactively)
            .workspaceBackground()
            .navigationTitle(L10n.viesPageNavTitle)
            .sensoryFeedback(trigger: model.phase) { _, phase in
                guard case let .done(outcome) = phase else { return nil }

                switch outcome.kind {
                case .valid: return .success
                case .invalid: return .error
                case .invalidFormat, .unsupportedCountry, .formatRejected, .rateLimited, .unavailable: return .warning
                }
            }
            .sheet(isPresented: $pickingCountry) {
                CountryPicker(title: L10n.calculatorSelectCountry, countries: store.memberStates, selected: model.countryCode) {
                    model.choose($0)
                }
            }
        }
    }

    private var form: some View {
        @Bindable var model = model
        let country = model.country(in: store)

        return VStack(alignment: .leading, spacing: 16) {
            VStack(alignment: .leading, spacing: 8) {
                Text(L10n.viesPageVatNumberLabel)
                    .font(.label)
                    .foregroundStyle(Palette.inkMuted)

                HStack(spacing: 8) {
                    TextField(L10n.viesPageVatPlaceholder, text: $model.numberText)
                        .font(.system(.title3, design: .monospaced).weight(.medium))
                        .foregroundStyle(Palette.ink)
                        .textInputAutocapitalization(.characters)
                        .autocorrectionDisabled()
                        .keyboardType(.asciiCapable)
                        .submitLabel(.search)
                        .focused($numberFocused)
                        .onSubmit(check)
                        .onChange(of: model.numberText) { model.numberChanged(countries: store.memberStates) }
                        .accessibilityLabel(L10n.viesPageVatNumberLabel)
                        .padding(.horizontal, 12)
                        .frame(minHeight: 52)
                        .background(Palette.surface, in: RoundedRectangle(cornerRadius: Metrics.controlRadius))
                        .overlay(
                            RoundedRectangle(cornerRadius: Metrics.controlRadius)
                                .strokeBorder(numberFocused ? Palette.action : Palette.inkQuiet, lineWidth: numberFocused ? 2 : 1)
                        )

                    // The system's paste button reads the clipboard without asking each time.
                    PasteButton(payloadType: String.self) { strings in
                        guard let text = strings.first else { return }

                        Task { @MainActor in
                            model.enter(text, countries: store.memberStates)
                        }
                    }
                    .labelStyle(.iconOnly)
                    .buttonBorderShape(.roundedRectangle(radius: Metrics.controlRadius))
                    .controlSize(.large)
                    .tint(Palette.button)
                }

                if model.numberText.isEmpty {
                    Button(L10n.viesPageTryExample) {
                        model.enter(ValidatorModel.exampleNumber, countries: store.memberStates)
                    }
                    .font(.footnote.weight(.medium))
                    .frame(minHeight: 28)
                } else {
                    Text(model.detected && country != nil ? L10n.viesPageDetectedCountry : L10n.viesPageAutoDetectHint)
                        .font(.footnote)
                        .foregroundStyle(Palette.inkMuted)
                        .frame(minHeight: 28, alignment: .leading)
                }
            }

            VStack(alignment: .leading, spacing: 8) {
                Text(L10n.country)
                    .font(.label)
                    .foregroundStyle(Palette.inkMuted)
                CountryKey(country: country) { pickingCountry = true }

                if let format = country?.vatNumber {
                    // The shape the number should have, beside the country it belongs to.
                    HStack(alignment: .firstTextBaseline, spacing: 12) {
                        Text(L10n.calculatorVatNumberFormat)
                            .font(.footnote)
                            .foregroundStyle(Palette.inkMuted)
                        Spacer(minLength: 8)

                        VStack(alignment: .trailing, spacing: 2) {
                            ForEach(format.masks, id: \.self) { mask in
                                Text(mask)
                            }
                        }
                        .font(.machine)
                        .foregroundStyle(Palette.ink)
                    }
                    .accessibilityElement(children: .combine)
                }
            }

            if let country, let format = country.vatNumber, model.formatMismatch(for: country) {
                Notice(tone: .warning, title: L10n.viesPageFormatMismatch(country: country.localizedName, format: format.format))
            }

            Button(action: check) {
                if model.phase == .checking {
                    HStack(spacing: 8) {
                        ProgressView().tint(.white)
                        Text(L10n.viesPageValidating)
                    }
                } else {
                    Text(L10n.viesPageValidateBtn)
                }
            }
            .buttonStyle(.primaryKey)
            .disabled(!model.canValidate)
        }
        .padding(20)
        .frame(maxWidth: .infinity, alignment: .leading)
        .panel()
    }

    private func check() {
        guard model.canValidate else { return }

        numberFocused = false

        Task {
            await model.validate(in: store)

            if case let .done(outcome) = model.phase {
                AccessibilityNotification.Announcement(title(for: outcome)).post()
            }
        }
    }

    private func title(for outcome: ValidationOutcome) -> String {
        let country = outcome.country?.localizedName ?? ""

        return switch outcome.kind {
        case .valid: L10n.viesPageResultValid
        case .invalid: L10n.viesPageResultInvalid
        case .invalidFormat: L10n.viesPageInvalidFormat
        case .unsupportedCountry: L10n.viesPageUnsupportedCountry
        case .formatRejected: L10n.viesPageFormatRejected(country: country)
        case .rateLimited: L10n.viesPageRateLimited
        case .unavailable: L10n.viesPageServiceUnavailable
        }
    }

    @ViewBuilder
    private var outcome: some View {
        if case let .done(outcome) = model.phase {
            Group {
                switch outcome.kind {
                case .valid:
                    details(outcome, tone: .success, message: L10n.viesPageResultValidDesc(country: outcome.country?.localizedName ?? ""))
                case .invalid:
                    details(outcome, tone: .danger, message: L10n.viesPageResultInvalidDesc)
                case .unavailable:
                    VStack(alignment: .leading, spacing: 12) {
                        Notice(tone: .warning, title: title(for: outcome))
                        Button(L10n.appTryAgain, action: check)
                            .buttonStyle(.secondaryKey)
                    }
                case .invalidFormat, .unsupportedCountry, .formatRejected, .rateLimited:
                    Notice(tone: .warning, title: title(for: outcome))
                }
            }
            .transition(.opacity)
        }
    }

    private func details(_ outcome: ValidationOutcome, tone: Notice.Tone, message: String) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            Notice(tone: tone, title: title(for: outcome), message: message)
                .padding(.bottom, 4)

            InfoRow(label: L10n.viesPageVatNumberLabel) {
                HStack(spacing: 0) {
                    Text(outcome.number)
                        .font(.machine)
                        .foregroundStyle(Palette.ink)
                        .textSelection(.enabled)
                        .padding(.trailing, 4)

                    Button {
                        Clipboard.copy(outcome.number)
                        toasts.show(L10n.viesPageCopied)
                    } label: {
                        Image(systemName: "doc.on.doc")
                            .frame(width: Metrics.touch, height: 28)
                    }
                    .accessibilityLabel(L10n.viesPageCopyNumber)

                    ShareLink(item: model.shareText(outcome)) {
                        Image(systemName: "square.and.arrow.up")
                            .frame(width: Metrics.touch, height: 28)
                    }
                }
            }

            if outcome.kind == .valid, let result = outcome.result {
                Hairline()
                InfoRow(label: L10n.viesPageFieldCompany) {
                    Text(result.name ?? L10n.viesPageNotProvided)
                        .foregroundStyle(Palette.ink)
                        .textSelection(.enabled)
                }
                Hairline()
                InfoRow(label: L10n.viesPageFieldAddress) {
                    Text(result.address ?? L10n.viesPageNotProvided)
                        .foregroundStyle(Palette.ink)
                        .textSelection(.enabled)
                }

                if let identifier = result.requestIdentifier {
                    Hairline()
                    InfoRow(label: L10n.viesPageFieldRequestId) {
                        Text(identifier).font(.machine).foregroundStyle(Palette.ink).textSelection(.enabled)
                    }
                }
            }

            if let result = outcome.result {
                Hairline()
                InfoRow(L10n.viesPageFieldSource, source(result))

                if let date = result.requestDate {
                    Text(L10n.viesPageCheckedOn(date: Format.date(date)))
                        .font(.footnote)
                        .foregroundStyle(Palette.inkMuted)
                        .padding(.top, 4)
                }
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .card()
    }

    private func source(_ result: ValidationResult) -> String {
        switch result.source {
        case "database_fallback": L10n.viesPageSourceFallback
        case "cache", "database": L10n.viesPageSourceRecent
        default: L10n.viesPageSourceLive
        }
    }

    @ViewBuilder
    private var recent: some View {
        if !model.recent.isEmpty {
            VStack(alignment: .leading, spacing: 0) {
                HStack(alignment: .firstTextBaseline) {
                    Text(L10n.viesPageRecentHeading)
                        .font(.section)
                        .foregroundStyle(Palette.ink)
                        .accessibilityAddTraits(.isHeader)
                    Spacer()
                    Button(L10n.calculatorClearAll) { model.clearRecent() }
                        .font(.subheadline.weight(.medium))
                }

                Text(L10n.viesPageRecentPrivacy)
                    .font(.footnote)
                    .foregroundStyle(Palette.inkMuted)
                    .padding(.top, 2)
                    .padding(.bottom, 8)

                ForEach(model.recent) { lookup in
                    Hairline()

                    Button {
                        model.restore(lookup)
                        check()
                    } label: {
                        HStack(spacing: 12) {
                            FlagView(iso: lookup.countryCode)

                            VStack(alignment: .leading, spacing: 2) {
                                Text(lookup.number)
                                    .font(.machine)
                                    .foregroundStyle(Palette.ink)
                                Text(lookup.name ?? (lookup.valid ? L10n.viesPageResultValid : L10n.viesPageResultInvalid))
                                    .font(.footnote)
                                    .foregroundStyle(Palette.inkMuted)
                                    .lineLimit(1)
                            }

                            Spacer(minLength: 8)
                            Image(systemName: lookup.valid ? "checkmark.seal" : "xmark.octagon")
                                .foregroundStyle(lookup.valid ? Palette.success : Palette.danger)
                                .accessibilityLabel(lookup.valid ? L10n.viesPageResultValid : L10n.viesPageResultInvalid)
                        }
                        .frame(minHeight: Metrics.touch)
                        .padding(.vertical, 4)
                        .contentShape(Rectangle())
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(16)
            .frame(maxWidth: .infinity, alignment: .leading)
            .card()
        }
    }
}
