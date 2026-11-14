import SwiftUI

/// The semantic colours of DESIGN.md. The values live in the asset catalog, written from
/// resources/css/app.css by ios/scripts/generate-assets.py, with a light and a dark value each.
enum Palette {
    static let workspace = Color(.workspace)
    static let surface = Color(.surface)
    static let surfaceSubtle = Color(.surfaceSubtle)
    static let surfaceMuted = Color(.surfaceMuted)
    static let line = Color(.line)
    static let lineStrong = Color(.lineStrong)
    static let ink = Color(.ink)
    static let inkMuted = Color(.inkMuted)
    static let inkQuiet = Color(.inkQuiet)
    static let action = Color(.action)
    static let actionDeep = Color(.actionDeep)
    static let actionSoft = Color(.actionSoft)
    static let button = Color(.button)
    static let buttonEdge = Color(.buttonEdge)
    static let keyFace = Color(.keyFace)
    static let keyEdge = Color(.keyEdge)
    static let success = Color(.success)
    static let successSoft = Color(.successSoft)
    static let warning = Color(.warning)
    static let warningSoft = Color(.warningSoft)
    static let danger = Color(.danger)
    static let dangerSoft = Color(.dangerSoft)
}

enum Metrics {
    /// Keys, fields and chips.
    static let controlRadius: CGFloat = 6
    /// Cards and panels.
    static let cardRadius: CGFloat = 8
    /// The bevel on two opposite corners of the primary panel.
    static let cut: CGFloat = 16
    /// The smallest touch target.
    static let touch: CGFloat = 44
    /// The widest a column of content gets on an iPad.
    static let contentWidth: CGFloat = 640
    /// The widest a list gets on an iPad.
    static let listWidth: CGFloat = 760
    static let gutter: CGFloat = 16
}

extension Font {
    /// Field labels, data labels and table heads: 13pt, medium, sentence case.
    static let label = Font.footnote.weight(.medium)
    /// VAT numbers, formats and codes.
    static let machine = Font.system(.subheadline, design: .monospaced).weight(.medium)
    /// A figure inside a row or a table.
    static let figure = Font.system(.title3, weight: .semibold).monospacedDigit()
    /// Section headings inside a screen.
    static let section = Font.system(.title3, weight: .semibold)
}

/// A panel cut on its top-leading and bottom-trailing corners; the other two keep a small radius.
struct CutShape: InsettableShape {
    var cut: CGFloat = Metrics.cut
    var radius: CGFloat = 4
    var inset: CGFloat = 0

    func path(in rect: CGRect) -> Path {
        let rect = rect.insetBy(dx: inset, dy: inset)
        let cut = min(cut, min(rect.width, rect.height) / 2)
        let radius = min(radius, min(rect.width, rect.height) / 2)
        var path = Path()

        path.move(to: CGPoint(x: rect.minX + cut, y: rect.minY))
        path.addLine(to: CGPoint(x: rect.maxX - radius, y: rect.minY))
        path.addArc(tangent1End: CGPoint(x: rect.maxX, y: rect.minY), tangent2End: CGPoint(x: rect.maxX, y: rect.minY + radius), radius: radius)
        path.addLine(to: CGPoint(x: rect.maxX, y: rect.maxY - cut))
        path.addLine(to: CGPoint(x: rect.maxX - cut, y: rect.maxY))
        path.addLine(to: CGPoint(x: rect.minX + radius, y: rect.maxY))
        path.addArc(tangent1End: CGPoint(x: rect.minX, y: rect.maxY), tangent2End: CGPoint(x: rect.minX, y: rect.maxY - radius), radius: radius)
        path.addLine(to: CGPoint(x: rect.minX, y: rect.minY + cut))
        path.closeSubpath()

        return path
    }

    func inset(by amount: CGFloat) -> CutShape {
        var shape = self
        shape.inset += amount

        return shape
    }
}

extension View {
    /// The primary task card of a screen: opaque paper, a hairline and two cut corners.
    func panel() -> some View {
        background(Palette.surface, in: CutShape())
            .overlay(CutShape().strokeBorder(Palette.line, lineWidth: 1))
            .shadow(color: Palette.ink.opacity(0.08), radius: 1, y: 1)
    }

    /// A resting card: opaque paper and a hairline.
    func card(fill: Color = Palette.surface) -> some View {
        background(fill, in: RoundedRectangle(cornerRadius: Metrics.cardRadius))
            .overlay(RoundedRectangle(cornerRadius: Metrics.cardRadius).strokeBorder(Palette.line, lineWidth: 1))
    }

    /// Centres a column of content and stops it growing past a readable width on an iPad.
    func contentColumn() -> some View {
        frame(maxWidth: Metrics.contentWidth)
            .frame(maxWidth: .infinity)
            .padding(.horizontal, Metrics.gutter)
    }

    /// Keeps a list's names and figures within reach of each other on an iPad.
    func listColumn() -> some View {
        frame(maxWidth: Metrics.listWidth)
            .frame(maxWidth: .infinity)
    }

    /// The paper every screen sits on.
    func workspaceBackground() -> some View {
        background(Palette.workspace.ignoresSafeArea())
    }
}

/// A key you press: it sits on a 2pt bottom edge and sinks onto it. Primary is the one cobalt key of a view.
struct KeyButtonStyle: ButtonStyle {
    enum Kind { case primary, secondary }

    var kind: Kind = .secondary
    var fullWidth = false

    @Environment(\.isEnabled) private var isEnabled

    func makeBody(configuration: Configuration) -> some View {
        let shape = RoundedRectangle(cornerRadius: Metrics.controlRadius)
        let pressed = configuration.isPressed

        configuration.label
            .font(.body.weight(.medium))
            .foregroundStyle(kind == .primary ? Color.white : Palette.ink)
            .padding(.horizontal, 16)
            .frame(maxWidth: fullWidth ? .infinity : nil, minHeight: Metrics.touch)
            .background(kind == .primary ? Palette.button : Palette.keyFace, in: shape)
            .overlay(shape.strokeBorder(kind == .primary ? Palette.buttonEdge : Palette.lineStrong, lineWidth: 1))
            .offset(y: pressed ? 2 : 0)
            .background(kind == .primary ? Palette.buttonEdge : Palette.keyEdge, in: shape.offset(y: 2))
            .padding(.bottom, 2)
            .opacity(isEnabled ? 1 : 0.5)
            .contentShape(shape)
            .animation(.easeOut(duration: 0.12), value: pressed)
    }
}

extension ButtonStyle where Self == KeyButtonStyle {
    static var primaryKey: KeyButtonStyle { KeyButtonStyle(kind: .primary, fullWidth: true) }
    static var secondaryKey: KeyButtonStyle { KeyButtonStyle(kind: .secondary) }
}
