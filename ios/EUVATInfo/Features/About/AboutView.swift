import SwiftUI

/// What the app is, where its data comes from and how current it is.
struct AboutView: View {
    @Environment(RatesStore.self) private var store
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    HStack(spacing: 14) {
                        LogoMark()

                        VStack(alignment: .leading, spacing: 2) {
                            Text(L10n.siteName)
                                .font(.system(.title2, weight: .semibold))
                                .foregroundStyle(Palette.ink)
                            Text(verbatim: "\(AppInfo.version) (\(AppInfo.build))")
                                .font(.machine)
                                .foregroundStyle(Palette.inkMuted)
                        }
                    }
                    .accessibilityElement(children: .combine)

                    VStack(alignment: .leading, spacing: 0) {
                        Text(L10n.trustOfficialEcData)
                            .font(.section)
                            .foregroundStyle(Palette.ink)
                            .padding(.bottom, 4)
                            .accessibilityAddTraits(.isHeader)

                        if let fetchedAt = store.fetchedAt {
                            InfoRow(L10n.statsLastUpdated, Format.date(fetchedAt))
                            Hairline()
                        }

                        link(L10n.trustSourceEc, AppLinks.commissionRates)
                        Hairline()
                        link(L10n.trustSourceDataset, AppLinks.dataset)
                        Hairline()
                        link("VIES", AppLinks.vies)
                    }
                    .padding(16)
                    .card()

                    VStack(alignment: .leading, spacing: 0) {
                        link(AppLinks.site.host() ?? L10n.siteName, AppLinks.home)
                        Hairline()
                        link(L10n.footerPrivacy, AppLinks.privacy)
                        Hairline()
                        link(L10n.sitemapGithubRepo, AppLinks.repository)
                        Hairline()

                        NavigationLink {
                            LicenceView()
                        } label: {
                            HStack {
                                Text(verbatim: "flag-icons (MIT)")
                                    .foregroundStyle(Palette.ink)
                                Spacer()
                                Image(systemName: "chevron.right")
                                    .font(.footnote.weight(.semibold))
                                    .foregroundStyle(Palette.inkQuiet)
                                    .accessibilityHidden(true)
                            }
                            .frame(minHeight: Metrics.touch)
                            .contentShape(Rectangle())
                        }
                        .buttonStyle(.plain)
                    }
                    .padding(.horizontal, 16)
                    .padding(.vertical, 4)
                    .card()

                    Text(L10n.compareDisclaimer)
                        .font(.footnote)
                        .foregroundStyle(Palette.inkMuted)
                }
                .padding(.vertical, 16)
                .contentColumn()
            }
            .workspaceBackground()
            .navigationTitle(L10n.sitemapAboutTitle)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button {
                        dismiss()
                    } label: {
                        Image(systemName: "xmark")
                    }
                    .accessibilityLabel(L10n.close)
                }
            }
        }
    }

    private func link(_ title: String, _ url: URL) -> some View {
        Link(destination: url) {
            HStack {
                Text(title)
                    .foregroundStyle(Palette.ink)
                    .multilineTextAlignment(.leading)
                Spacer()
                Image(systemName: "arrow.up.right")
                    .font(.footnote.weight(.semibold))
                    .foregroundStyle(Palette.inkQuiet)
                    .accessibilityHidden(true)
            }
            .frame(minHeight: Metrics.touch)
            .contentShape(Rectangle())
        }
    }
}

/// The licence of the flag artwork, which asks to be shipped with every copy.
private struct LicenceView: View {
    var body: some View {
        ScrollView {
            Text(verbatim: Self.text)
                .font(.footnote.monospaced())
                .foregroundStyle(Palette.inkMuted)
                .textSelection(.enabled)
                .padding(.vertical, 16)
                .contentColumn()
        }
        .workspaceBackground()
        .navigationTitle(Text(verbatim: "flag-icons"))
        .navigationBarTitleDisplayMode(.inline)
    }

    private static let text: String = {
        guard let url = Bundle.main.url(forResource: "flag-icons-license", withExtension: "txt") else { return "" }

        return (try? String(contentsOf: url, encoding: .utf8)) ?? ""
    }()
}
