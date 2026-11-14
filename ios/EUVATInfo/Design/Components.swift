import SwiftUI
import UIKit

/// A country's flag from the asset catalog, 4:3 with a hairline so white flags keep an edge.
struct FlagView: View {
    let iso: String
    @ScaledMetric(relativeTo: .body) var height: CGFloat = 16

    var body: some View {
        let shape = RoundedRectangle(cornerRadius: 2)

        Group {
            if let image = UIImage(named: "flag-\(iso.lowercased())") {
                Image(uiImage: image)
                    .resizable()
                    .aspectRatio(contentMode: .fill)
            } else {
                Palette.surfaceMuted
            }
        }
        .frame(width: height * 4 / 3, height: height)
        .clipShape(shape)
        .overlay(shape.strokeBorder(Palette.line, lineWidth: 0.5))
        .accessibilityHidden(true)
    }
}

/// A label over or beside a value.
struct InfoRow<Value: View>: View {
    let label: String
    @ViewBuilder var value: Value

    var body: some View {
        ViewThatFits(in: .horizontal) {
            HStack(alignment: .firstTextBaseline, spacing: 16) {
                labelText
                Spacer(minLength: 16)
                value.multilineTextAlignment(.trailing)
            }
            VStack(alignment: .leading, spacing: 4) {
                labelText
                value
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(.vertical, 12)
        .accessibilityElement(children: .combine)
    }

    private var labelText: some View {
        Text(label)
            .font(.label)
            .foregroundStyle(Palette.inkMuted)
    }
}

extension InfoRow where Value == Text {
    init(_ label: String, _ value: String) {
        self.label = label
        self.value = Text(value).font(.body).foregroundStyle(Palette.ink)
    }
}

/// A hairline between rows.
struct Hairline: View {
    var body: some View {
        Rectangle()
            .fill(Palette.line)
            .frame(height: 1)
            .accessibilityHidden(true)
    }
}

/// When the rates were updated and where they come from, beside the figures they qualify.
struct FreshnessLine: View {
    let updated: Date?
    var isEU = true

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            if let updated {
                Label(L10n.trustUpdated(date: Format.date(updated)), systemImage: "clock")
                    .foregroundStyle(Palette.inkMuted)
            }

            Link(destination: isEU ? AppLinks.commissionRates : AppLinks.dataset) {
                Label(isEU ? L10n.trustSourceEc : L10n.trustSourceDataset, systemImage: "checkmark.shield")
                    .underline()
            }
            .foregroundStyle(Palette.inkMuted)
        }
        .font(.footnote.weight(.medium))
        .labelStyle(.tightIcon)
        .frame(maxWidth: .infinity, alignment: .leading)
    }
}

/// An icon close to its text, aligned to the first line.
struct TightIconLabelStyle: LabelStyle {
    func makeBody(configuration: Configuration) -> some View {
        HStack(alignment: .firstTextBaseline, spacing: 6) {
            configuration.icon
            configuration.title
        }
    }
}

extension LabelStyle where Self == TightIconLabelStyle {
    static var tightIcon: TightIconLabelStyle { TightIconLabelStyle() }
}

/// A status message with an icon, so colour is never the only signal.
struct Notice: View {
    enum Tone { case warning, danger, success, neutral }

    let tone: Tone
    let title: String
    var message: String?

    var body: some View {
        HStack(alignment: .firstTextBaseline, spacing: 10) {
            Image(systemName: icon)
                .foregroundStyle(colour)
                .accessibilityHidden(true)

            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.subheadline.weight(.semibold))
                    .foregroundStyle(Palette.ink)

                if let message {
                    Text(message)
                        .font(.subheadline)
                        .foregroundStyle(Palette.inkMuted)
                }
            }
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(12)
        .background(fill, in: RoundedRectangle(cornerRadius: Metrics.controlRadius))
        .accessibilityElement(children: .combine)
    }

    private var icon: String {
        switch tone {
        case .warning: "exclamationmark.triangle"
        case .danger: "xmark.octagon"
        case .success: "checkmark.seal"
        case .neutral: "info.circle"
        }
    }

    private var colour: Color {
        switch tone {
        case .warning: Palette.warning
        case .danger: Palette.danger
        case .success: Palette.success
        case .neutral: Palette.inkMuted
        }
    }

    private var fill: Color {
        switch tone {
        case .warning: Palette.warningSoft
        case .danger: Palette.dangerSoft
        case .success: Palette.successSoft
        case .neutral: Palette.surfaceSubtle
        }
    }
}

/// Shown above the content when the last refresh failed and stored rates are on screen.
struct StaleDataNotice: View {
    @Environment(RatesStore.self) private var store

    var body: some View {
        if store.refreshFailed {
            HStack(spacing: 12) {
                Notice(tone: .warning, title: L10n.errorsDataLoadFailedTitle, message: store.fetchedAt.map { "\(L10n.statsLastUpdated): \(Format.date($0))" })

                Button {
                    Task { await store.refresh() }
                } label: {
                    Image(systemName: "arrow.clockwise")
                        .frame(width: Metrics.touch, height: Metrics.touch)
                }
                .accessibilityLabel(L10n.appTryAgain)
                .disabled(store.isRefreshing)
            }
        }
    }
}

/// The brand mark: an ink tile cut on two corners, a percent sign with one cobalt dot.
struct LogoMark: View {
    var size: CGFloat = 56

    var body: some View {
        Canvas { context, canvas in
            let unit = canvas.width / 32
            func point(_ x: CGFloat, _ y: CGFloat) -> CGPoint { CGPoint(x: x * unit, y: y * unit) }

            var tile = Path()
            tile.move(to: point(8, 0))
            tile.addLine(to: point(26, 0))
            tile.addLine(to: point(32, 6))
            tile.addLine(to: point(32, 24))
            tile.addArc(tangent1End: point(32, 32), tangent2End: point(24, 32), radius: 8 * unit)
            tile.addLine(to: point(6, 32))
            tile.addLine(to: point(0, 26))
            tile.addLine(to: point(0, 8))
            tile.addArc(tangent1End: point(0, 0), tangent2End: point(8, 0), radius: 8 * unit)
            tile.closeSubpath()
            context.fill(tile, with: .color(Palette.ink))

            var slash = Path()
            slash.move(to: point(10.5, 21.5))
            slash.addLine(to: point(21.5, 10.5))
            context.stroke(slash, with: .color(Palette.workspace), style: StrokeStyle(lineWidth: 2.75 * unit, lineCap: .round))

            let radius = 3.25 * unit
            context.fill(Path(ellipseIn: CGRect(x: 11.25 * unit - radius, y: 11.25 * unit - radius, width: radius * 2, height: radius * 2)), with: .color(Palette.workspace))
            context.fill(Path(ellipseIn: CGRect(x: 20.75 * unit - radius, y: 20.75 * unit - radius, width: radius * 2, height: radius * 2)), with: .color(Palette.button))
        }
        .frame(width: size, height: size)
        .accessibilityHidden(true)
    }
}

/// Copies text and says so, for VoiceOver as well.
@MainActor
enum Clipboard {
    static func copy(_ text: String, announcement: String) {
        UIPasteboard.general.string = text
        UINotificationFeedbackGenerator().notificationOccurred(.success)
        UIAccessibility.post(notification: .announcement, argument: announcement)
    }
}
