import Foundation

extension UserDefaults {
    /// Where the app keeps what a person chose: favourites, the calculator's country, recent checks.
    /// A debug build started with `-resetState` begins empty, so UI tests do not inherit an earlier run.
    /// `UserDefaults` is safe to use from any thread; the compiler is not told so, hence the annotation.
    nonisolated(unsafe) static let app: UserDefaults = {
        #if DEBUG
        if ProcessInfo.processInfo.arguments.contains("-resetState"), let domain = Bundle.main.bundleIdentifier {
            UserDefaults.standard.removePersistentDomain(forName: domain)
        }
        #endif

        return .standard
    }()
}
