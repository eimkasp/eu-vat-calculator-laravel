import Charts
import SwiftUI

/// How many rates went up and how many went down in each year.
struct ChangesByYear: Equatable {
    struct Year: Identifiable, Equatable {
        let year: Int
        let increases: Int
        let decreases: Int

        var id: Int { year }
    }

    /// The years that have a change, oldest first.
    let years: [Year]

    static let calendar = Calendar(identifier: .gregorian)

    init(changes: [RateChange]) {
        var counts: [Int: (increases: Int, decreases: Int)] = [:]

        for change in changes {
            guard let date = change.changeDate else { continue }

            let year = Self.calendar.component(.year, from: date)

            if change.direction == .increase {
                counts[year, default: (0, 0)].increases += 1
            } else {
                counts[year, default: (0, 0)].decreases += 1
            }
        }

        years = counts.keys.sorted().map { Year(year: $0, increases: counts[$0]?.increases ?? 0, decreases: counts[$0]?.decreases ?? 0) }
    }

    var increases: Int { years.reduce(0) { $0 + $1.increases } }

    var decreases: Int { years.reduce(0) { $0 + $1.decreases } }

    /// The first day of a year, which is where its bar is drawn.
    static func start(of year: Int) -> Date {
        calendar.date(from: DateComponents(year: year, month: 1, day: 1)) ?? .distantPast
    }
}

/// Rate changes per year: increases rise above the line and decreases hang below it, so the direction
/// reads from position as well as from colour. Touch a year to jump to it in the list.
struct ChangesChart: View {
    let summary: ChangesByYear
    let onSelect: (Int) -> Void

    @State private var selection: Date?

    var body: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack(spacing: 16) {
                Label("\(L10n.historyStatIncreases) \(summary.increases)", systemImage: "arrow.up.right")
                    .foregroundStyle(Palette.danger)
                Label("\(L10n.historyStatDecreases) \(summary.decreases)", systemImage: "arrow.down.right")
                    .foregroundStyle(Palette.success)
            }
            .labelStyle(.tightIcon)
            .font(.footnote.weight(.medium))
            .monospacedDigit()

            Chart {
                ForEach(summary.years) { year in
                    if year.increases > 0 {
                        BarMark(
                            x: .value(L10n.navVatHistory, ChangesByYear.start(of: year.year), unit: .year),
                            y: .value(L10n.historyStatIncreases, year.increases)
                        )
                        .foregroundStyle(Palette.danger)
                        .accessibilityLabel("\(year.year)")
                        .accessibilityValue("\(L10n.historyStatIncreases) \(year.increases)")
                    }

                    if year.decreases > 0 {
                        BarMark(
                            x: .value(L10n.navVatHistory, ChangesByYear.start(of: year.year), unit: .year),
                            y: .value(L10n.historyStatDecreases, -year.decreases)
                        )
                        .foregroundStyle(Palette.success)
                        .accessibilityLabel("\(year.year)")
                        .accessibilityValue("\(L10n.historyStatDecreases) \(year.decreases)")
                    }
                }

                RuleMark(y: .value(L10n.navVatHistory, 0))
                    .foregroundStyle(Palette.lineStrong)
                    .lineStyle(StrokeStyle(lineWidth: 1))
                    .accessibilityHidden(true)

                if let selection {
                    RuleMark(x: .value(L10n.navVatHistory, selection))
                        .foregroundStyle(Palette.inkQuiet)
                        .lineStyle(StrokeStyle(lineWidth: 1))
                        .accessibilityHidden(true)
                }
            }
            .chartXSelection(value: $selection)
            .chartXScale(range: .plotDimension(startPadding: 4, endPadding: 4))
            .chartYAxis {
                AxisMarks(position: .leading, values: .automatic(desiredCount: 4)) { value in
                    AxisGridLine().foregroundStyle(Palette.line)
                    AxisValueLabel {
                        if let count = value.as(Int.self) {
                            Text("\(abs(count))")
                                .foregroundStyle(Palette.inkMuted)
                        }
                    }
                }
            }
            .chartXAxis {
                // Aligned marks keep the first and last year inside the chart instead of cutting them off.
                AxisMarks(preset: .aligned, values: .automatic(desiredCount: 5)) { _ in
                    AxisGridLine().foregroundStyle(Palette.line)
                    AxisValueLabel(format: .dateTime.year())
                        .foregroundStyle(Palette.inkMuted)
                }
            }
            .frame(height: 120)
            .accessibilityLabel(L10n.navVatHistory)
            .onChange(of: selection.map { ChangesByYear.calendar.component(.year, from: $0) }) { _, year in
                if let year {
                    onSelect(year)
                }
            }
        }
    }
}
