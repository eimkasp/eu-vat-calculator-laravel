// Wide audit: every page type in the XML sitemap (two URLs per path pattern, plus a few translated pages), each in light and
// dark mode at 390px and 1440px: axe-core (WCAG 2.2 AA and best practices), horizontal overflow, script errors, failed
// requests, small tap targets, title and description length, JSON-LD validity, one h1, image alt and size, Markdown
// negotiation, then every internal link it saw. Uses the Playwright and axe-core of the v3 benchmark tools.
// Usage: BASE=http://127.0.0.1:8123 OUT=audit.json node docs/benchmarks/cycle-5.3/tools/audit.mjs
import { readFileSync, writeFileSync } from 'node:fs';
import { createRequire } from 'node:module';

const require = createRequire(new URL('../../v3-claude-opus-5-5/tools/package.json', import.meta.url));
const { chromium } = require('playwright');

const BASE = process.env.BASE ?? 'http://127.0.0.1:8123';
const OUT = process.env.OUT ?? 'audit.json';
const PER_PATTERN = Number(process.env.PER_PATTERN ?? 2);
const EXTRA = (process.env.EXTRA ?? '').split(',').filter(Boolean);
const AXE = readFileSync(require.resolve('axe-core/axe.min.js'), 'utf8');
const CANON = 'https://vat.businesspress.io';

const text = async (url, headers = {}) => {
    const res = await fetch(url, { headers, redirect: 'manual' });
    return { status: res.status, body: await res.text(), headers: res.headers };
};

const locs = (xml) => [...xml.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1].replace(CANON, BASE).replace(/&amp;/g, '&'));

async function urls() {
    const index = await text(`${BASE}/sitemap.xml`);
    const sections = locs(index.body);
    const all = [];
    for (const s of sections) all.push(...locs((await text(s)).body));
    const english = all.filter((u) => !/^\/(bg|cs|da|de|el|es|et|fi|fr|ga|hr|hu|it|lt|lv|mt|nl|pl|pt|ro|sk|sl|sv)(\/|$)/.test(new URL(u).pathname));
    const groups = new Map();
    for (const u of english) {
        const p = new URL(u).pathname;
        const key = p.split('/').slice(0, 3).map((seg, i) => (i === 2 && seg ? '*' : seg)).join('/');
        if (!groups.has(key)) groups.set(key, []);
        groups.get(key).push(u);
    }
    const picked = [];
    for (const list of groups.values()) picked.push(...list.slice(0, PER_PATTERN));
    for (const p of ['/de', '/mt', '/de/vat-calculator/germany', '/fr/vat-number-validator', '/ga/vat-map', ...EXTRA]) picked.push(BASE + p);
    return { total: all.length, english: english.length, picked: [...new Set(picked)] };
}

async function inspect(page, url, width, theme) {
    const errors = [];
    const failed = [];
    page.removeAllListeners('pageerror');
    page.removeAllListeners('console');
    page.removeAllListeners('response');
    page.on('pageerror', (e) => errors.push(e.message));
    page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
    page.on('response', (r) => r.status() >= 400 && failed.push(`${r.status()} ${r.url()}`));
    await page.setViewportSize({ width, height: width < 600 ? 844 : 900 });
    await page.emulateMedia({ colorScheme: theme });
    const res = await page.goto(url, { waitUntil: 'networkidle', timeout: 60000 });
    await page.evaluate((t) => { document.documentElement.classList.toggle('dark', t === 'dark'); }, theme);
    await page.evaluate(async () => { window.scrollTo(0, document.body.scrollHeight); await new Promise((r) => setTimeout(r, 400)); window.scrollTo(0, 0); });
    await page.addScriptTag({ content: AXE });
    const axe = await page.evaluate(async () => {
        document.querySelectorAll('.app-deferred').forEach((n) => { n.style.contentVisibility = 'visible'; });
        const r = await window.axe.run(document, { runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] }, resultTypes: ['violations'] });
        return r.violations.map((v) => ({ id: v.id, impact: v.impact, help: v.help, nodes: v.nodes.slice(0, 4).map((n) => n.target.join(' ') + ' :: ' + (n.failureSummary ?? '').split('\n').slice(1, 2).join('')), count: v.nodes.length }));
    });
    const dom = await page.evaluate(() => {
        const meta = (n) => document.querySelector(`meta[name="${n}"]`)?.content ?? null;
        const ld = [...document.querySelectorAll('script[type="application/ld+json"]')].map((s) => { try { const j = JSON.parse(s.textContent); return { ok: true, types: (j['@graph'] ?? [j]).map((x) => x['@type']).flat() }; } catch (e) { return { ok: false, error: e.message }; } });
        const overflow = document.documentElement.scrollWidth - document.documentElement.clientWidth;
        const wide = overflow > 0 ? [...document.querySelectorAll('body *')].filter((n) => { const r = n.getBoundingClientRect(); return r.right > document.documentElement.clientWidth + 1 && getComputedStyle(n).position !== 'fixed'; }).slice(0, 5).map((n) => n.tagName.toLowerCase() + (n.id ? '#' + n.id : '') + '.' + [...n.classList].slice(0, 3).join('.')) : [];
        const imgs = [...document.images];
        const smallTargets = [...document.querySelectorAll('a[href], button, input, select, [role="button"], [tabindex="0"]')].filter((n) => { const r = n.getBoundingClientRect(); if (!r.width || !r.height) return false; const inline = getComputedStyle(n).display === 'inline' && n.closest('p, li, td, dd'); return !inline && (r.width < 24 || r.height < 24); }).slice(0, 5).map((n) => n.outerHTML.slice(0, 90));
        const links = [...document.querySelectorAll('a[href]')].map((a) => a.href).filter((h) => h.startsWith(location.origin));
        return {
            lang: document.documentElement.lang,
            title: document.title,
            description: meta('description'),
            robots: meta('robots'),
            canonical: document.querySelector('link[rel="canonical"]')?.href ?? null,
            hreflang: document.querySelectorAll('link[rel="alternate"][hreflang]').length,
            ogImage: document.querySelector('meta[property="og:image"]')?.content ?? null,
            h1: [...document.querySelectorAll('h1')].map((h) => h.textContent.trim().slice(0, 80)),
            ld,
            overflow,
            wide,
            imgNoAlt: imgs.filter((i) => !i.hasAttribute('alt')).length,
            imgNoSize: imgs.filter((i) => !(i.getAttribute('width') && i.getAttribute('height'))).map((i) => i.src.split('/').pop()).slice(0, 5),
            dupIds: [...new Set([...document.querySelectorAll('[id]')].map((n) => n.id).filter((id, i, a) => a.indexOf(id) !== i))].slice(0, 5),
            smallTargets,
            emptyLinks: [...document.querySelectorAll('a[href]')].filter((a) => !a.textContent.trim() && !a.getAttribute('aria-label') && !a.querySelector('img[alt]:not([alt=""]), svg[aria-label], [aria-label]')).length,
            mainText: (document.querySelector('main')?.innerText ?? '').length,
            links,
            domNodes: document.querySelectorAll('*').length,
        };
    });
    return { status: res?.status() ?? 0, axe, errors: [...new Set(errors)].slice(0, 5), failed: [...new Set(failed)].slice(0, 5), ...dom };
}

const { total, english, picked } = await urls();
console.error(`sitemap ${total} urls, ${english} English, auditing ${picked.length}`);
const browser = await chromium.launch(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : {});
const ctx = await browser.newContext();
const page = await ctx.newPage();
const results = [];
const allLinks = new Set();
for (const url of picked) {
    const entry = { url: url.replace(BASE, '') };
    for (const [width, theme] of [[390, 'light'], [390, 'dark'], [1440, 'light'], [1440, 'dark']]) {
        try {
            const r = await inspect(page, url, width, theme);
            r.links.forEach((l) => allLinks.add(l.split('#')[0]));
            delete r.links;
            entry[`${width}-${theme}`] = width === 390 && theme === 'light' ? r : { axe: r.axe, overflow: r.overflow, wide: r.wide, errors: r.errors, failed: r.failed, smallTargets: r.smallTargets };
        } catch (e) {
            entry[`${width}-${theme}`] = { error: e.message };
        }
    }
    const md = await text(url, { Accept: 'text/markdown' });
    entry.markdown = { status: md.status, type: md.headers.get('content-type'), length: md.body.length };
    results.push(entry);
    console.error(`done ${entry.url}`);
}
const broken = [];
for (const l of allLinks) {
    if (/\/(storybook|v1|v2)\//.test(l)) continue;
    const r = await fetch(l, { redirect: 'manual' });
    if (r.status >= 300) broken.push(`${r.status} ${l.replace(BASE, '')}${r.headers.get('location') ? ' -> ' + r.headers.get('location').replace(BASE, '') : ''}`);
}
await browser.close();
const titles = new Map();
for (const r of results) { const t = r['390-light']?.title; if (t) titles.set(t, [...(titles.get(t) ?? []), r.url]); }
writeFileSync(OUT, JSON.stringify({ total, english, audited: picked.length, linksChecked: allLinks.size, broken, duplicateTitles: [...titles].filter(([, v]) => v.length > 1), results }, null, 2));
console.error(`wrote ${OUT}`);
