// Merges the raw measurements into one benchmark file with a per-page summary.
// Usage: node compile.mjs --raw=<dir with front-end.json, site.json, backend.json, repository.json> --out=../baseline.json --label=baseline
import { execSync } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
    const [key, ...value] = arg.replace(/^--/, '').split('=');

    return [key, value.join('=')];
}));

const raw = args.raw ?? '.';
const read = (name) => JSON.parse(readFileSync(join(raw, name), 'utf8'));
const frontEnd = read('front-end.json');
const site = read('site.json');
const backend = read('backend.json');
const repository = read('repository.json');
const meta = JSON.parse(readFileSync(new URL('./meta.json', import.meta.url), 'utf8'));

const sitePage = new Map(site.pages.map((page) => [page.path, page]));
const backendPage = new Map(backend.pages.map((page) => [page.path, page]));
const sum = (values) => values.reduce((total, value) => total + (value ?? 0), 0);
const median = (values) => {
    const sorted = values.filter((value) => Number.isFinite(value)).sort((a, b) => a - b);
    const middle = Math.floor(sorted.length / 2);

    return sorted.length === 0 ? null : sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
};

const pages = frontEnd.pages.map((page) => {
    const links = sitePage.get(page.path) ?? {};
    const server = backendPage.get(page.path) ?? {};
    const { perRun, ...performance } = page.performance;
    const { internalLinksOutList, ...seo } = page.seo;
    const { first600, ...ai } = page.ai;

    return {
        path: page.path,
        locale: page.locale,
        status: page.status,
        performance: { ...performance, perRun },
        accessibility: {
            axeViolationRules: page.accessibility.violationRules,
            axeViolationsByImpact: page.accessibility.violationsByImpact,
            axeNodesByImpact: page.accessibility.nodesByImpact,
            axeViolations: page.accessibility.violations,
            keyboard: page.accessibility.keyboard,
        },
        seo: { ...seo, internalLinksIn: links.internalLinksIn ?? null, inSitemap: links.inSitemap ?? null },
        ai: {
            ...ai,
            first600,
            reachableThrough: page.page.startsWith('/vat-calculator/germany') || page.page.startsWith('/vat-calculation/germany') || page.page.startsWith('/compare/germany')
                ? site.facts.germanyStandardRate19
                : page.page.startsWith('/vat-calculator/malta') ? site.facts.maltaStandardRate18 : null,
        },
        backend: server.warm ? { cold: { queries: server.cold.queries, serverMs: server.cold.serverMs }, warm: server.warm } : null,
    };
});

const ok = pages.filter((page) => page.status === 200);

const summary = {
    pages: pages.length,
    pagesWith200: ok.length,
    pagesNot200: pages.filter((page) => page.status !== 200).map((page) => `${page.path} (${page.status})`),
    medianLcpMs: median(ok.map((page) => page.performance.lcpMs)),
    worstLcp: ok.reduce((worst, page) => (page.performance.lcpMs > (worst?.lcpMs ?? -1) ? { path: page.path, lcpMs: page.performance.lcpMs } : worst), null),
    medianTbtMs: median(ok.map((page) => page.performance.tbtMs)),
    maxCls: Math.max(...ok.map((page) => page.performance.cls)),
    medianHtmlGzipBytes: median(ok.map((page) => page.performance.htmlBytes.gzip)),
    medianJsGzipBytes: median(ok.map((page) => page.performance.jsBytes.gzip)),
    medianCssGzipBytes: median(ok.map((page) => page.performance.cssBytes.gzip)),
    medianRequests: median(ok.map((page) => page.performance.requestCount)),
    axeViolationRulesTotal: sum(ok.map((page) => page.accessibility.axeViolationRules)),
    axeViolationsByImpact: ['critical', 'serious', 'moderate', 'minor'].reduce((totals, impact) => ({ ...totals, [impact]: sum(ok.map((page) => page.accessibility.axeViolationsByImpact[impact])) }), {}),
    keyboardTasksCompleted: `${ok.filter((page) => page.accessibility.keyboard.completed).length}/${ok.length}`,
    keyboardTasksFailed: ok.filter((page) => !page.accessibility.keyboard.completed).map((page) => `${page.path}: ${page.accessibility.keyboard.note ?? 'not completed'}`),
    titlesOutside30to60: ok.filter((page) => page.seo.titleLength < 30 || page.seo.titleLength > 60).map((page) => `${page.path} (${page.seo.titleLength})`),
    descriptionsOutside70to160: ok.filter((page) => page.seo.descriptionLength < 70 || page.seo.descriptionLength > 160).map((page) => `${page.path} (${page.seo.descriptionLength})`),
    invalidJsonLd: ok.filter((page) => !page.seo.jsonLd.valid).map((page) => `${page.path}: ${page.seo.jsonLd.problems.slice(0, 3).join('; ') || 'no JSON-LD'}`),
    notInSitemap: ok.filter((page) => !page.seo.inSitemap).map((page) => page.path),
    answerInFirst600: `${ok.filter((page) => page.ai.answerInFirst600).length}/${ok.length}`,
    asOfDateShown: `${ok.filter((page) => page.ai.asOfDateShown).length}/${ok.length}`,
    asOfDateShownInMain: `${ok.filter((page) => page.ai.asOfDateShownInMain).length}/${ok.length}`,
    sourceShown: `${ok.filter((page) => page.ai.sourceShown).length}/${ok.length}`,
    sourceShownInMain: `${ok.filter((page) => page.ai.sourceShownInMain).length}/${ok.length}`,
    answerInFirst600OfMain: `${ok.filter((page) => page.ai.answerInFirst600OfMain).length}/${ok.length}`,
    machineReadableAlternate: `${ok.filter((page) => page.ai.machineReadableAlternate).length}/${ok.length}`,
    medianWarmQueries: median(pages.filter((page) => page.backend).map((page) => page.backend.warm.queries)),
    maxWarmQueries: Math.max(...pages.filter((page) => page.backend).map((page) => page.backend.warm.queries)),
    medianColdQueries: median(pages.filter((page) => page.backend).map((page) => page.backend.cold.queries)),
    maxSnapshotBytes: Math.max(...pages.filter((page) => page.backend).map((page) => page.backend.warm.snapshotBytesTotal)),
};

writeFileSync(args.out ?? 'baseline.json', JSON.stringify({
    benchmark: 'EU VAT Info v3',
    label: args.label ?? 'baseline',
    ...meta,
    commit: args.commit ?? execSync('git rev-parse --short HEAD').toString().trim(),
    measuredAt: frontEnd.measuredAt,
    auditsMeasuredAt: frontEnd.performanceReusedFrom ? frontEnd.measuredAt : undefined,
    performanceMeasuredAt: frontEnd.performanceReusedFrom ?? frontEnd.measuredAt,
    profile: frontEnd.profile,
    browser: frontEnd.browser,
    summary,
    site: { sitemap: site.sitemap, inboundLinkMethod: site.inboundLinkMethod, machineReadable: site.machineReadable, facts: site.facts },
    repository,
    backendEnvironment: backend.environment,
    pages,
}, null, 2) + '\n');
