import Charts
import SwiftUI

/// The standard rate over time, as steps: each recorded change starts a new level.
struct RateTimeline: Equatable {
    struct Point: Identifiable, Equatable {
        let id: Int
        let date: Date
        let rate: Double
    }

    /// The levels, oldest first. The first is the rate before the first recorded change and the last
    /// repeats the latest rate, so the line runs to the edge of the chart.
    let points: [Point]
    /// The dates on which a recorded change took effect, oldest first.
    let changeDates: [Date]

    /// Nil when no change of the standard rate with a date is on record.
    init?(changes: [RateChange], now: Date = .now) {
        let steps = changes
            .filter { $0.kind == .standard }
            .compactMap { change in change.changeDate.map { (date: $0, change: change) } }
            .sorted { $0.date < $1.date }

        guard let first = steps.first, let last = steps.last else { return nil }

        // Room before the first change to show the rate it replaced, and after the last to show it holding.
        let lead = max(max(now, last.date).timeIntervalSince(first.date) * 0.12, 180 * 86400)
        let levels = [(first.date.addingTimeInterval(-lead), first.change.oldRate)]
            + steps.map { ($0.date, $0.change.newRate) }
            + [(max(now, last.date.addingTimeInterval(lead)), last.change.newRate)]

        points = levels.enumerated().map { Point(id: $0.offset, date: $0.element.0, rate: $0.element.1) }
        changeDates = steps.map(\.date)
    }

    /// The rate in force on a date.
    func rate(at date: Date) -> Double {
        points.last { $0.date <= date }?.rate ?? points.first?.rate ?? 0
    }

    /// When the rate in force on a date began; nil before the first recorded change.
    func since(_ date: Date) -> Date? {
        changeDates.last { $0 <= date }
    }

    /// The vertical range of the chart, with room above and below the rates.
    var domain: ClosedRange<Double> {
        let rates = points.map(\.rate)

        return max(((rates.min() ?? 0) - 2).rounded(.down), 0) ... ((rates.max() ?? 0) + 2).rounded(.up)
    }
}

/// The standard rate drawn over time. Touch the chart to read the rate on any date.
struct RateHistoryChart: View {
    let timeline: RateTimeline

    @State private var selection: Date?

    var body: some View {
        let date = selection ?? .now
        let series = L10n.countryPageStandardRate

        VStack(alignment: .leading, spacing: 10) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(Format.percent(timeline.rate(at: date)))
                    .font(.figure)
                    .foregroundStyle(Palette.ink)
                    .contentTransition(.numericText())

                Text(caption(for: date))
                    .font(.footnote)
                    .foregroundStyle(Palette.inkMuted)
            }
            .accessibilityElement(children: .combine)

            Chart {
                ForEach(timeline.points) { point in
                    AreaMark(
                        x: .value(L10n.historyRateHistory, point.date),
                        yStart: .value(series, timeline.domain.lowerBound),
                        yEnd: .value(series, point.rate)
                    )
                    .interpolationMethod(.stepEnd)
                    .foregroundStyle(Palette.action.opacity(0.12))
                    .accessibilityHidden(true)

                    LineMark(x: .value(L10n.historyRateHistory, point.date), y: .value(series, point.rate))
                        .interpolationMethod(.stepEnd)
                        .foregroundStyle(Palette.action)
                        .lineStyle(StrokeStyle(lineWidth: 2))
                        .accessibilityLabel(Format.date(point.date))
                        .accessibilityValue(Format.percent(point.rate))
                }

                if let selection {
                    RuleMark(x: .value(L10n.historyRateHistory, selection))
                        .foregroundStyle(Palette.inkQuiet)
                        .lineStyle(StrokeStyle(lineWidth: 1))
                        .accessibilityHidden(true)
                }
            }
            .chartYScale(domain: timeline.domain)
            .chartXSelection(value: $selection)
            .chartYAxis {
                AxisMarks(position: .leading, values: .automatic(desiredCount: 3)) { value in
                    AxisGridLine().foregroundStyle(Palette.line)
                    AxisValueLabel {
                        if let rate = value.as(Double.self) {
                            Text(Format.percent(rate))
                                .foregroundStyle(Palette.inkMuted)
                        }
                    }
                }
            }
            .chartXAxis {
                // Aligned marks keep the first and last year inside the chart instead of cutting them off.
                AxisMarks(preset: .aligned, values: .automatic(desiredCount: 4)) { _ in
                    AxisGridLine().foregroundStyle(Palette.line)
                    AxisValueLabel(format: .dateTime.year())
                        .foregroundStyle(Palette.inkMuted)
                }
            }
            .frame(height: 140)
            .accessibilityLabel(L10n.historyRateHistory)
        }
    }

    private func caption(for date: Date) -> String {
        if selection != nil {
            return Format.date(date)
        }

        return timeline.since(date).map { L10n.calculatorRateSince(date: Format.date($0)) } ?? ""
    }
}

/// Where a rate sits between the lowest and the highest of the countries it belongs with.
struct RangePosition: View {
    let value: Double
    let lowest: Double
    let highest: Double

    var body: some View {
        VStack(spacing: 6) {
            GeometryReader { geometry in
                let share = highest > lowest ? min(max((value - lowest) / (highest - lowest), 0), 1) : 0.5
                let position = geometry.size.width * share

                ZStack(alignment: .leading) {
                    RoundedRectangle(cornerRadius: 2)
                        .fill(Palette.action.opacity(0.15))
                        .frame(height: 6)
                    RoundedRectangle(cornerRadius: 2)
                        .fill(Palette.action)
                        .frame(width: max(position, 3), height: 6)
                    RoundedRectangle(cornerRadius: 1)
                        .fill(Palette.ink)
                        .frame(width: 3, height: 14)
                        .offset(x: min(max(position - 1.5, 0), geometry.size.width - 3))
                }
                .frame(height: 14)
            }
            .frame(height: 14)

            HStack {
                Text("\(L10n.homePageStatLowest) \(Format.percent(lowest))")
                Spacer(minLength: 12)
                Text("\(L10n.homePageStatHighest) \(Format.percent(highest))")
            }
            .font(.footnote)
            .foregroundStyle(Palette.inkMuted)
        }
        .accessibilityElement(children: .ignore)
        .accessibilityLabel("\(L10n.homePageStatLowest) \(Format.percent(lowest)), \(L10n.homePageStatHighest) \(Format.percent(highest))")
    }
}
