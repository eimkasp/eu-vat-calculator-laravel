import SwiftUI
import UIKit

/// The shortcuts under the app's icon on the Home Screen: straight to a tool.
@MainActor
@Observable
final class QuickActionCenter {
    static let shared = QuickActionCenter()

    /// The tab a shortcut asked for, until the interface has opened it.
    var requested: AppTab?

    private static let tabs: [(type: String, tab: AppTab, icon: String)] = [
        ("calculator", .calculator, "plus.forwardslash.minus"),
        ("validator", .validator, "checkmark.seal"),
        ("compare", .compare, "rectangle.split.2x1"),
    ]

    /// Lists the shortcuts in the interface language. iOS keeps them until they are set again.
    func register() {
        UIApplication.shared.shortcutItems = Self.tabs.map { entry in
            UIApplicationShortcutItem(
                type: entry.type,
                localizedTitle: Self.title(entry.tab),
                localizedSubtitle: nil,
                icon: UIApplicationShortcutIcon(systemImageName: entry.icon)
            )
        }
    }

    @discardableResult
    func handle(_ item: UIApplicationShortcutItem) -> Bool {
        guard let entry = Self.tabs.first(where: { $0.type == item.type }) else { return false }

        requested = entry.tab

        return true
    }

    private static func title(_ tab: AppTab) -> String {
        switch tab {
        case .calculator: L10n.calculatorTitle
        case .validator: L10n.viesPageNavTitle
        case .compare: L10n.navCompare
        case .rates: L10n.countryPageVatRates
        case .history: L10n.navVatHistory
        }
    }
}

/// Gives the scene a delegate, which is where iOS hands over a Home Screen shortcut.
final class AppDelegate: NSObject, UIApplicationDelegate {
    func application(
        _ application: UIApplication,
        configurationForConnecting connectingSceneSession: UISceneSession,
        options: UIScene.ConnectionOptions
    ) -> UISceneConfiguration {
        let configuration = UISceneConfiguration(name: nil, sessionRole: connectingSceneSession.role)
        configuration.delegateClass = SceneDelegate.self

        return configuration
    }
}

final class SceneDelegate: NSObject, UIWindowSceneDelegate {
    /// The app was closed when the shortcut was chosen.
    func scene(_ scene: UIScene, willConnectTo session: UISceneSession, options connectionOptions: UIScene.ConnectionOptions) {
        if let item = connectionOptions.shortcutItem {
            QuickActionCenter.shared.handle(item)
        }
    }

    /// The app was in the background when the shortcut was chosen.
    func windowScene(_ windowScene: UIWindowScene, performActionFor shortcutItem: UIApplicationShortcutItem) async -> Bool {
        QuickActionCenter.shared.handle(shortcutItem)
    }
}
