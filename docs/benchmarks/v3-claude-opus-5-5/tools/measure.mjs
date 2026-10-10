// Front-end measurement for the EU VAT Info v3 benchmark: performance, accessibility, SEO and AI readiness.
// Usage: node measure.mjs --base=http://127.0.0.1:8123 --out=../front-end.json [--runs=3] [--only=/vat-map]
import { chromium } from 'playwright';
import { createRequire } from 'node:module';
import { readFileSync, writeFileSync } from 'node:fs';
import { gzipSync, brotliCompressSync, constants as zlib } from 'node:zlib';
import { PAGES, LOCALES, CANONICAL_HOST, localized } from './pages.mjs';

const require = createRequire(import.meta.url);
const args = Object.fromEntries(process.argv.slice(2).map((arg) => arg.replace(/^--/, '').split('=')));
const BASE = (args.base ?? 'http://127.0.0.1:8123').replace(/\/$/, '');
const RUNS = Number(args.runs ?? 3);
const OUT = args.out ?? 'front-end.json';
const ONLY = args.only ?? null;
// --reuse=<earlier output> keeps that run's throttled performance figures and repeats only the audits.
const REUSED = args.reuse ? new Map(JSON.parse(readFileSync(args.reuse, 'utf8')).pages.map((page) => [page.path, page.performance])) : null;
const AXE = require.resolve('axe-core/axe.min.js');
const AXE_VERSION = require('axe-core/package.json').version;

// Lighthouse's mobile profile: Moto G Power screen, 4x CPU slowdown, "Slow 4G" (150ms RTT x 3.75, 1.6 Mbps x 0.9).
const MOBILE = {
    viewport: { width: 412, height: 823 },
    deviceScaleFactor: 1.75,
    isMobile: true,
    hasTouch: true,
    userAgent: 'Mozilla/5.0 (Linux; Android 11; moto g power (2022)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36',
    colorScheme: 'light',
    locale: 'en-GB',
};
const SLOW_4G = { offline: false, latency: 562.5, downloadThroughput: (1474.56 * 1024) / 8, uploadThroughput: (675 * 1024) / 8 };
const CPU_SLOWDOWN = 4;
const AXE_TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

const median = (values) => {
    const sorted = values.filter((value) => value !== null && Number.isFinite(value)).sort((a, b) => a - b);

    if (sorted.length === 0) {
        return null;
    }

    const middle = Math.floor(sorted.length / 2);

    return sorted.length % 2 ? sorted[middle] : (sorted[middle - 1] + sorted[middle]) / 2;
};

const round = (value, digits = 0) => (value === null || value === undefined ? null : Number(value.toFixed(digits)));

const PERF_OBSERVERS = () => {
    window.__perf = { lcp: null, lcpElement: null, shifts: [], longTasks: [] };
    new PerformanceObserver((list) => {
        const entry = list.getEntries().at(-1);
        window.__perf.lcp = entry.startTime;
        window.__perf.lcpElement = entry.element ? `${entry.element.tagName.toLowerCase()}${entry.element.id ? '#' + entry.element.id : ''}${entry.element.className && typeof entry.element.className === 'string' ? '.' + entry.element.className.trim().split(/\s+/).slice(0, 3).join('.') : ''}` : (entry.url || null);
    }).observe({ type: 'largest-contentful-paint', buffered: true });
    new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
            if (!entry.hadRecentInput) {
                window.__perf.shifts.push({ value: entry.value, time: entry.startTime });
            }
        });
    }).observe({ type: 'layout-shift', buffered: true });
    new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => window.__perf.longTasks.push({ start: entry.startTime, duration: entry.duration }));
    }).observe({ type: 'longtask', buffered: true });
};

// CLS as Core Web Vitals defines it: the largest session window (gaps under 1s, windows up to 5s).
function cumulativeLayoutShift(shifts) {
    let worst = 0;
    let current = 0;
    let windowStart = 0;
    let previous = -Infinity;

    for (const shift of shifts) {
        if (shift.time - previous > 1000 || shift.time - windowStart > 5000) {
            current = 0;
            windowStart = shift.time;
        }

        current += shift.value;
        previous = shift.time;
        worst = Math.max(worst, current);
    }

    return worst;
}

function classify(type, url, mime = '') {
    if (type === 'Document') return 'html';
    if (type === 'Script' || /javascript/.test(mime)) return 'js';
    if (type === 'Stylesheet' || /css/.test(mime)) return 'css';
    if (type === 'Font' || /font|woff/.test(mime)) return 'font';
    if (type === 'Image' || /^image\//.test(mime)) return 'image';
    if (type === 'Fetch' || type === 'XHR') return 'fetch';

    return 'other';
}

const compressible = (kind, mime) => ['html', 'js', 'css', 'fetch'].includes(kind) || /svg|json|xml|text/.test(mime);

async function performanceRun(browser, url) {
    const context = await browser.newContext(MOBILE);
    const page = await context.newPage();
    const cdp = await context.newCDPSession(page);
    const requests = new Map();

    await cdp.send('Network.enable');
    await cdp.send('Network.setCacheDisabled', { cacheDisabled: true });
    await cdp.send('Network.emulateNetworkConditions', SLOW_4G);
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: CPU_SLOWDOWN });
    await page.addInitScript(PERF_OBSERVERS);

    cdp.on('Network.requestWillBeSent', ({ requestId, request, type }) => {
        if (!request.url.startsWith('data:')) {
            requests.set(requestId, { url: request.url, type, mime: '', transfer: 0 });
        }
    });
    cdp.on('Network.responseReceived', ({ requestId, response, type }) => {
        const entry = requests.get(requestId);

        if (entry) {
            Object.assign(entry, { mime: response.mimeType, type: type ?? entry.type, status: response.status });
        }
    });
    cdp.on('Network.loadingFinished', ({ requestId, encodedDataLength }) => {
        const entry = requests.get(requestId);

        if (entry) {
            entry.transfer = encodedDataLength;
        }
    });

    const bodies = new Map();
    page.on('response', async (response) => {
        try {
            bodies.set(response.url(), await response.body());
        } catch {
            // Redirects and aborted requests have no body.
        }
    });

    const started = Date.now();
    await page.goto(url, { waitUntil: 'load', timeout: 120000 });
    await page.waitForLoadState('networkidle', { timeout: 60000 }).catch(() => {});
    await page.waitForTimeout(3000);

    const timing = await page.evaluate(() => {
        const navigation = performance.getEntriesByType('navigation')[0];
        const fcp = performance.getEntriesByName('first-contentful-paint')[0]?.startTime ?? null;

        return {
            ttfb: navigation?.responseStart ?? null,
            fcp,
            domContentLoaded: navigation?.domContentLoadedEventEnd ?? null,
            load: navigation?.loadEventEnd ?? null,
            lcp: window.__perf.lcp,
            lcpElement: window.__perf.lcpElement,
            shifts: window.__perf.shifts,
            longTasks: window.__perf.longTasks,
        };
    });

    const totals = {};
    for (const entry of requests.values()) {
        const kind = classify(entry.type, entry.url, entry.mime);
        const body = bodies.get(entry.url);
        const raw = body?.length ?? 0;
        const gzip = body && compressible(kind, entry.mime) ? gzipSync(body, { level: 6 }).length : raw;
        const brotli = body && compressible(kind, entry.mime) ? brotliCompressSync(body, { params: { [zlib.BROTLI_PARAM_QUALITY]: 5 } }).length : raw;
        totals[kind] ??= { requests: 0, raw: 0, gzip: 0, brotli: 0 };
        totals[kind].requests += 1;
        totals[kind].raw += raw;
        totals[kind].gzip += gzip;
        totals[kind].brotli += brotli;
    }

    const tbt = timing.longTasks
        .filter((task) => timing.fcp === null || task.start + task.duration > timing.fcp)
        .reduce((sum, task) => sum + Math.max(0, task.duration - 50), 0);

    await context.close();

    return {
        wallMs: Date.now() - started,
        requests: requests.size,
        bytes: totals,
        ttfb: timing.ttfb,
        fcp: timing.fcp,
        lcp: timing.lcp,
        lcpElement: timing.lcpElement,
        cls: cumulativeLayoutShift(timing.shifts),
        tbt,
        longTasks: timing.longTasks.length,
        domContentLoaded: timing.domContentLoaded,
        load: timing.load,
    };
}

function summarisePerformance(runs) {
    const pick = (key) => median(runs.map((run) => run[key]));
    const bytes = (kind, field) => median(runs.map((run) => run.bytes[kind]?.[field] ?? 0));
    const last = runs.at(-1);

    return {
        runs: runs.length,
        htmlBytes: { raw: bytes('html', 'raw'), gzip: bytes('html', 'gzip'), brotli: bytes('html', 'brotli') },
        jsBytes: { raw: bytes('js', 'raw'), gzip: bytes('js', 'gzip'), brotli: bytes('js', 'brotli'), requests: last.bytes.js?.requests ?? 0 },
        cssBytes: { raw: bytes('css', 'raw'), gzip: bytes('css', 'gzip'), brotli: bytes('css', 'brotli'), requests: last.bytes.css?.requests ?? 0 },
        fontBytes: bytes('font', 'raw'),
        imageBytes: bytes('image', 'raw'),
        totalTransferGzip: median(runs.map((run) => Object.values(run.bytes).reduce((sum, item) => sum + item.gzip, 0))),
        requestCount: pick('requests'),
        ttfbMs: round(pick('ttfb')),
        fcpMs: round(pick('fcp')),
        lcpMs: round(pick('lcp')),
        lcpElement: last.lcpElement,
        cls: round(pick('cls'), 4),
        tbtMs: round(pick('tbt')),
        longTasks: pick('longTasks'),
        loadMs: round(pick('load')),
        perRun: runs.map((run) => ({ lcpMs: round(run.lcp), cls: round(run.cls, 4), tbtMs: round(run.tbt), fcpMs: round(run.fcp), requests: run.requests })),
    };
}

// Required properties per schema.org type, following Google's structured data documentation where it exists.
const REQUIRED = {
    BreadcrumbList: ['itemListElement'],
    ListItem: ['position', 'name'],
    FAQPage: ['mainEntity'],
    Question: ['name', 'acceptedAnswer'],
    Answer: ['text'],
    WebPage: ['name'],
    WebSite: ['name', 'url'],
    Organization: ['name', 'url'],
    WebApplication: ['name', 'offers'],
    SoftwareApplication: ['name', 'offers'],
    Offer: ['price', 'priceCurrency'],
    Dataset: ['name', 'description'],
    DataDownload: ['contentUrl'],
    HowTo: ['name', 'step'],
    HowToStep: ['text'],
    Article: ['headline'],
    BlogPosting: ['headline'],
    Table: ['about'],
    ItemList: ['itemListElement'],
    Event: ['name', 'startDate'],
    TechArticle: ['headline'],
    APIReference: ['name'],
    MonetaryAmount: ['currency', 'value'],
    Place: ['name'],
    Country: ['name'],
    DefinedTerm: ['name'],
    Thing: ['name'],
};

function validateJsonLd(blocks) {
    const types = new Set();
    const problems = [];

    const walk = (node, path) => {
        if (Array.isArray(node)) {
            node.forEach((item, index) => walk(item, `${path}[${index}]`));

            return;
        }

        if (!node || typeof node !== 'object') {
            return;
        }

        const nodeTypes = [node['@type']].flat().filter(Boolean);
        nodeTypes.forEach((type) => types.add(type));

        for (const type of nodeTypes) {
            for (const property of REQUIRED[type] ?? []) {
                const value = node[property];

                if (value === undefined || value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
                    if (!(type === 'ListItem' && property === 'name' && node.item?.name)) {
                        problems.push(`${path} ${type} is missing ${property}`);
                    }
                }
            }

            if (type === 'ListItem' && node.item === undefined && path.includes('itemListElement') && node.position !== undefined && !node.url) {
                // The last breadcrumb may omit item; Google accepts it.
            }
        }

        Object.entries(node).forEach(([key, value]) => {
            if (key !== '@context') {
                walk(value, `${path}.${key}`);
            }
        });
    };

    const parsed = [];
    blocks.forEach((text, index) => {
        try {
            const data = JSON.parse(text);
            parsed.push(data);

            if (![data].flat().every((item) => item['@context'] || item['@graph'])) {
                problems.push(`block ${index} has no @context`);
            }

            walk(data, `block${index}`);
        } catch (error) {
            problems.push(`block ${index} is not valid JSON: ${error.message}`);
        }
    });

    return { blocks: blocks.length, types: [...types].sort(), valid: blocks.length > 0 && problems.length === 0, problems };
}

async function keyboardTask(page, task) {
    if (!task) {
        return { task: null, completed: null, note: 'no interactive main task defined' };
    }

    const outcome = { task: task.name, completed: false, tabPresses: null, focusVisibleMisses: [] };
    const startUrl = page.url();
    await page.evaluate(() => document.activeElement?.blur());
    await page.mouse.move(0, 0);

    for (let presses = 1; presses <= (task.maxTabs ?? 80); presses += 1) {
        await page.keyboard.press('Tab');
        const state = await page.evaluate((selector) => {
            const element = document.activeElement;

            if (!element || element === document.body) {
                return { matches: false, visible: true, label: 'body' };
            }

            const style = getComputedStyle(element);
            const outline = style.outlineStyle !== 'none' && parseFloat(style.outlineWidth) > 0;
            const ring = style.boxShadow && style.boxShadow !== 'none';
            const label = `${element.tagName.toLowerCase()}${element.id ? '#' + element.id : ''} "${(element.getAttribute('aria-label') || element.textContent || element.getAttribute('placeholder') || '').trim().replace(/\s+/g, ' ').slice(0, 40)}"`;

            return { matches: element.matches(selector), visible: outline || ring, label };
        }, task.focus);

        if (!state.visible) {
            outcome.focusVisibleMisses.push(state.label);
        }

        if (state.matches) {
            outcome.tabPresses = presses;
            break;
        }
    }

    if (outcome.tabPresses === null) {
        outcome.note = `could not reach ${task.focus} with Tab`;

        return outcome;
    }

    try {
        for (const step of task.steps ?? []) {
            if (step.type) {
                await page.keyboard.press('ControlOrMeta+A');
                await page.keyboard.type(step.type, { delay: 20 });
            }

            if (step.press) {
                await page.keyboard.press(step.press);
            }

            await page.waitForTimeout(step.wait ?? 300);
        }

        if (task.navigates) {
            await page.waitForLoadState('load').catch(() => {});
            outcome.completed = page.url() !== startUrl;
            outcome.landedOn = new URL(page.url()).pathname;
        } else {
            outcome.completed = await page.waitForFunction(task.done, null, { timeout: task.timeout ?? 8000 }).then(() => true, () => false);
        }
    } catch (error) {
        outcome.note = error.message.split('\n')[0];
    }

    return outcome;
}

async function auditRun(browser, url, definition, locale) {
    const context = await browser.newContext({ ...MOBILE, locale: locale === 'en' ? 'en-GB' : locale });
    const page = await context.newPage();
    const response = await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
    const status = response?.status() ?? null;

    const seo = await page.evaluate(() => {
        const meta = (selector) => document.querySelector(selector)?.getAttribute('content') ?? null;
        const main = document.querySelector('main') ?? document.body;
        const text = (node) => node.innerText.replace(/\s+/g, ' ').trim();
        const internal = new Set();

        document.querySelectorAll('a[href]').forEach((link) => {
            const target = new URL(link.getAttribute('href'), location.href);

            if (target.origin === location.origin && !target.pathname.startsWith('/api/')) {
                internal.add(target.pathname.replace(/(.)\/$/, '$1'));
            }
        });

        return {
            title: document.title,
            description: meta('meta[name="description"]'),
            canonical: document.querySelector('link[rel="canonical"]')?.getAttribute('href') ?? null,
            robots: meta('meta[name="robots"]'),
            hreflang: document.querySelectorAll('link[rel="alternate"][hreflang]').length,
            alternates: [...document.querySelectorAll('link[rel="alternate"][type]')].map((link) => ({ type: link.getAttribute('type'), href: link.getAttribute('href') })),
            jsonLd: [...document.querySelectorAll('script[type="application/ld+json"]')].map((node) => node.textContent),
            h1: [...document.querySelectorAll('h1')].map((node) => node.innerText.replace(/\s+/g, ' ').trim()),
            wordCount: text(document.body).split(' ').filter((word) => /[\p{L}\p{N}]/u.test(word)).length,
            mainWordCount: text(main).split(' ').filter((word) => /[\p{L}\p{N}]/u.test(word)).length,
            internalLinksOut: [...internal].sort(),
            first600: text(document.body).slice(0, 600),
            firstMain600: text(main).slice(0, 600),
            visibleText: text(document.body),
            mainText: text(main),
        };
    });

    await page.addScriptTag({ path: AXE });
    const axe = await page.evaluate(async (tags) => {
        const result = await window.axe.run(document, { runOnly: { type: 'tag', values: tags }, resultTypes: ['violations'] });

        return result.violations.map((violation) => ({ id: violation.id, impact: violation.impact, nodes: violation.nodes.length, help: violation.help, sample: violation.nodes[0]?.target?.join(' ') ?? null }));
    }, AXE_TAGS);

    const keyboard = await keyboardTask(page, definition.keyboard);
    await context.close();

    const byImpact = { critical: 0, serious: 0, moderate: 0, minor: 0 };
    const nodesByImpact = { critical: 0, serious: 0, moderate: 0, minor: 0 };
    axe.forEach((violation) => {
        byImpact[violation.impact] = (byImpact[violation.impact] ?? 0) + 1;
        nodesByImpact[violation.impact] = (nodesByImpact[violation.impact] ?? 0) + violation.nodes;
    });

    const answer = definition.answer?.[locale] ?? definition.answer?.default ?? null;
    const source = /\b(European Commission|Europäische[n]? Kommission|Kummissjoni Ewropea|TEDB|Taxes in Europe|kdeldycke|source[sd]?|Quelle[n]?|sors)\b/iu;
    const asOf = /\b(updated|as of|last (?:checked|verified|updated)|verified|aktualisiert|geprüft|Stand:|aġġornat|ivverifikat)\b[^.]{0,40}?(\d{1,2}\.?\s+\p{L}+\.?\s+\d{4}|\d{4}-\d{2}-\d{2}|\d{1,2}[./]\d{1,2}[./]\d{2,4}|\p{L}+\s+\d{1,2},?\s+\d{4})/iu;
    const sourceMatch = seo.visibleText.match(source);
    const dateMatch = seo.visibleText.match(asOf);
    const mainSourceMatch = seo.mainText.match(source);
    const mainDateMatch = seo.mainText.match(asOf);

    return {
        status,
        accessibility: {
            axeVersion: AXE_VERSION,
            tags: AXE_TAGS,
            violationRules: axe.length,
            violationsByImpact: byImpact,
            nodesByImpact,
            violations: axe,
            keyboard,
        },
        seo: {
            title: seo.title,
            titleLength: [...seo.title].length,
            description: seo.description,
            descriptionLength: seo.description ? [...seo.description].length : 0,
            canonical: seo.canonical,
            robots: seo.robots,
            hreflangCount: seo.hreflang,
            jsonLd: validateJsonLd(seo.jsonLd),
            h1: seo.h1,
            h1Count: seo.h1.length,
            wordCount: seo.wordCount,
            mainWordCount: seo.mainWordCount,
            internalLinksOut: seo.internalLinksOut.length,
            internalLinksOutList: seo.internalLinksOut,
        },
        ai: {
            answerPattern: answer,
            answerInFirst600: answer ? new RegExp(answer, 'iu').test(seo.first600) : null,
            answerInFirst600OfMain: answer ? new RegExp(answer, 'iu').test(seo.firstMain600) : null,
            first600: seo.first600,
            declaredAlternates: seo.alternates,
            sourceShown: Boolean(sourceMatch),
            sourceSnippet: sourceMatch?.[0] ?? null,
            sourceShownInMain: Boolean(mainSourceMatch),
            asOfDateShown: Boolean(dateMatch),
            asOfSnippet: dateMatch?.[0] ?? null,
            asOfDateShownInMain: Boolean(mainDateMatch),
        },
    };
}

async function markdownAlternate(url) {
    const response = await fetch(url, { headers: { Accept: 'text/markdown' } });
    const body = await response.text();

    return { negotiated: /text\/markdown/.test(response.headers.get('content-type') ?? ''), bytes: body.length, tokens: Number(response.headers.get('x-markdown-tokens')) || null };
}

const browser = await chromium.launch({ headless: true });
const results = [];

for (const definition of PAGES) {
    if (ONLY && definition.path !== ONLY) {
        continue;
    }

    for (const locale of LOCALES) {
        const path = localized(definition.path, locale);
        const url = BASE + path;
        process.stderr.write(`${path} `);

        const runs = [];
        for (let run = 0; !REUSED && run < RUNS; run += 1) {
            runs.push(await performanceRun(browser, url));
            process.stderr.write('.');
        }

        const performance = REUSED ? REUSED.get(path) : summarisePerformance(runs);

        const audit = await auditRun(browser, url, definition, locale);
        audit.ai.markdownByAcceptHeader = await markdownAlternate(url);
        audit.ai.machineReadableAlternate = audit.ai.markdownByAcceptHeader.negotiated || audit.ai.declaredAlternates.length > 0;
        process.stderr.write(` lcp=${performance?.lcpMs} axe=${audit.accessibility.violationRules}\n`);

        results.push({ path, page: definition.path, locale, canonicalUrl: CANONICAL_HOST + path, status: audit.status, performance, ...audit });
    }
}

await browser.close();

writeFileSync(OUT, JSON.stringify({
    tool: 'measure.mjs',
    measuredAt: new Date().toISOString(),
    base: BASE,
    browser: `Chromium ${browser.version()} (Playwright ${require('playwright/package.json').version}, headless)`,
    profile: { device: MOBILE, network: { name: 'Slow 4G (Lighthouse devtools values)', ...SLOW_4G }, cpuSlowdown: CPU_SLOWDOWN, cache: 'disabled', runsPerPage: RUNS, statistic: 'median' },
    performanceReusedFrom: args.reuse ? JSON.parse(readFileSync(args.reuse, 'utf8')).measuredAt : null,
    pages: results,
}, null, 2));

process.stderr.write(`wrote ${OUT}\n`);
