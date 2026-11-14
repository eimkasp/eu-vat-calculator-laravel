import SwiftUI

@main
struct EUVATInfoApp: App {
    @State private var store = RatesStore()
    @State private var router = AppRouter()
    @State private var calculator = CalculatorModel()
    @State private var validator = ValidatorModel()
    @State private var comparison = ComparisonModel()

    @Environment(\.scenePhase) private var scenePhase

    var body: some Scene {
        WindowGroup {
            RootView()
                .environment(store)
                .environment(router)
                .environment(calculator)
                .environment(validator)
                .environment(comparison)
                .environment(\.locale, AppLocale.current)
                .tint(Palette.action)
                .task(id: scenePhase) {
                    if scenePhase == .active {
                        await store.refreshIfStale()
                    }
                }
        }
    }
}

enum AppTab: Hashable {
    case rates, calculator, validator, compare, history
}

/// Which tab is showing and what each stack has open. Country screens are identified by ISO code,
/// so they survive a refresh of the data.
@MainActor
@Observable
final class AppRouter {
    var tab: AppTab = .rates
    var ratesPath: [String] = []
    var comparePath: [String] = []
    var historyPath: [String] = []
}

struct RootView: View {
    @Environment(AppRouter.self) private var router

    var body: some View {
        @Bindable var router = router

        TabView(selection: $router.tab) {
            RatesView()
                .tabItem { Label(L10n.countries, systemImage: "percent") }
                .tag(AppTab.rates)

            CalculatorView()
                .tabItem { Label(L10n.bottomNavCalculator, systemImage: "plus.forwardslash.minus") }
                .tag(AppTab.calculator)

            ValidatorView()
                .tabItem { Label(L10n.bottomNavValidator, systemImage: "checkmark.seal") }
                .tag(AppTab.validator)

            CompareView()
                .tabItem { Label(L10n.compareIndexSubmit, systemImage: "rectangle.split.2x1") }
                .tag(AppTab.compare)

            HistoryView()
                .tabItem { Label(L10n.bottomNavHistory, systemImage: "clock.arrow.circlepath") }
                .tag(AppTab.history)
        }
    }
}
