import SwiftUI
import UIKit

/// A short confirmation that appears above the tab bar and leaves on its own.
@MainActor
@Observable
final class ToastCenter {
    private(set) var message: String?

    @ObservationIgnored private var dismissal: Task<Void, Never>?

    func show(_ message: String) {
        dismissal?.cancel()
        self.message = message
        UIAccessibility.post(notification: .announcement, argument: message)

        dismissal = Task { [weak self] in
            try? await Task.sleep(for: .seconds(2))

            guard !Task.isCancelled else { return }

            self?.message = nil
        }
    }
}

private struct ToastOverlay: ViewModifier {
    @Environment(ToastCenter.self) private var toasts
    @Environment(\.horizontalSizeClass) private var sizeClass
    @Environment(\.accessibilityReduceMotion) private var reduceMotion

    func body(content: Content) -> some View {
        content
            .overlay(alignment: .bottom) {
                if let message = toasts.message {
                    Label(message, systemImage: "checkmark")
                        .labelStyle(.tightIcon)
                        .font(.subheadline.weight(.medium))
                        .foregroundStyle(Palette.workspace)
                        .padding(.horizontal, 14)
                        .padding(.vertical, 10)
                        .background(Palette.ink, in: RoundedRectangle(cornerRadius: Metrics.controlRadius))
                        .shadow(color: .black.opacity(0.2), radius: 12, y: 6)
                        // A phone keeps its tab bar at the bottom; an iPad has it at the top.
                        .padding(.bottom, sizeClass == .compact ? 76 : 24)
                        .padding(.horizontal, Metrics.gutter)
                        .transition(reduceMotion ? .opacity : .opacity.combined(with: .offset(y: 4)))
                        // VoiceOver already heard the announcement.
                        .accessibilityHidden(true)
                        .id(message)
                }
            }
            .animation(.easeOut(duration: 0.16), value: toasts.message)
    }
}

extension View {
    /// Shows the confirmations of `ToastCenter` over this view.
    func toasts() -> some View {
        modifier(ToastOverlay())
    }
}

/// Copies text with a light tap of feedback. Say what was copied with `ToastCenter`.
@MainActor
enum Clipboard {
    static func copy(_ text: String) {
        UIPasteboard.general.string = text
        UINotificationFeedbackGenerator().notificationOccurred(.success)
    }
}

/// A choice you can pick from a set: a small key that stays pressed in while it is the chosen one.
struct ChoiceChip: View {
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
                    .lineLimit(1)
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
        .sensoryFeedback(.selection, trigger: selected)
    }
}

/// A rate drawn against the highest one beside it. `upper` extends the bar to the top of a range of rates.
struct RateBar: View {
    let share: Double
    var upper: Double?
    var height: CGFloat = 6

    var body: some View {
        GeometryReader { geometry in
            let shape = RoundedRectangle(cornerRadius: 2)

            ZStack(alignment: .leading) {
                shape.fill(Palette.action.opacity(0.15))

                if let upper {
                    shape.fill(Palette.action.opacity(0.45))
                        .frame(width: geometry.size.width * Self.clamp(upper))
                }

                shape.fill(Palette.action)
                    .frame(width: geometry.size.width * Self.clamp(share))
            }
        }
        .frame(height: height)
        .accessibilityHidden(true)
    }

    private static func clamp(_ value: Double) -> Double {
        value.isFinite ? min(max(value, 0), 1) : 0
    }
}
