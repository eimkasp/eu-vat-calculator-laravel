// Freezes the English site into a static, read-only archive under public/<version>/, following the pattern of
// public/v1: every page is noindex, carries the archive bar, loads its own copy of every asset and answers the
// Livewire runtime in the browser through public/<version>/archive.js.
//
// Usage (against a local server of the release being archived, with seeded data and a production build):
//   node scripts/archive-site.mjs --base=http://127.0.0.1:8123 --version=v2 --captured="10 October 2026" --release=5.0
import { createHash } from 'node:crypto';
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
    const [key, ...value] = arg.replace(/^--/, '').split('=');

    return [key, value.join('=')];
}));

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const BASE = (args.base ?? 'http://127.0.0.1:8123').replace(/\/$/, '');
const LIVE = (args.live ?? 'https://vat.businesspress.io').replace(/\/$/, '');
const VERSION = args.version ?? 'v2';
const CAPTURED = args.captured ?? new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const RELEASE = args.release ?? '5.0';
const PREFIX = `/${VERSION}`;
const OUT = join(ROOT, 'public', VERSION);
const PUBLIC = join(ROOT, 'public');
const LIVEWIRE = join(ROOT, 'vendor/livewire/livewire/dist/livewire.min.js');
const CONCURRENCY = Number(args.concurrency ?? 6);

const LOCALES = ['bg', 'cs', 'da', 'de', 'el', 'es', 'et', 'fi', 'fr', 'ga', 'hr', 'hu', 'it', 'lt', 'lv', 'mt', 'nl', 'pl', 'pt', 'ro', 'sk', 'sl', 'sv'];
const NOT_PAGES = /^\/(?:v\d+(?:\/|$)|vat-calculation\/|api(?:\/|$)|embed(?:\/|$)|public\/|amp(?:\/|$)|lang\/|sitemaps?\/|\.well-known\/|admin(?:\/|$)|pulse(?:\/|$)|livewire|vat-change-alerts\/|calculation(?:\/|$)|platform\/|up$|country\/)/;
const ASSET = /^\/(?:build|images|flags|fonts)\/|^\/(?:favicon\.ico|icon-\d+x\d+\.png)$/;

// The ways templates write a slash: plain, JSON-escaped, and JSON inside a JSON.parse('…') string.
const SLASHES = ['/', '\\/', '\\\\\\/'];
const inEncoding = (value, slash) => value.replaceAll('/', slash);
const toLive = (text) => SLASHES.reduce((current, slash) => current.replaceAll(inEncoding(BASE, slash), inEncoding(LIVE, slash)), text);
const isLocalised = (path) => new RegExp(`^/(${LOCALES.join('|')})(/|$)`).test(path);
const normalise = (path) => (path.length > 1 ? path.replace(/\/+$/, '') : path);
const isPage = (path) => !isLocalised(path) && !NOT_PAGES.test(path) && !/\.[a-z0-9]{2,5}$/i.test(path);

const pages = new Map();
const redirects = new Map();
const missing = new Set();
const assets = new Set();

async function fetchPage(path) {
    const response = await fetch(BASE + path, { redirect: 'manual', headers: { Accept: 'text/html' } });

    if (response.status >= 300 && response.status < 400) {
        const target = new URL(response.headers.get('location'), BASE + path);

        return { redirect: target.origin === BASE || target.origin === LIVE ? normalise(target.pathname) : null };
    }

    if (response.status !== 200 || !(response.headers.get('content-type') ?? '').includes('text/html')) {
        return { status: response.status };
    }

    const html = toLive(await response.text());

    return { html };
}

function linksIn(html, from) {
    const found = new Set();

    for (const match of html.matchAll(/<a\s(?:[^>]*?\s)?href="([^"#][^"]*)"/g)) {
        const url = new URL(match[1].replaceAll('&amp;', '&'), LIVE + from);

        if (url.origin === LIVE && isPage(url.pathname)) {
            found.add(normalise(url.pathname));
        }
    }

    return found;
}

async function sitemapPaths() {
    const index = await (await fetch(`${BASE}/sitemap.xml`)).text();
    const paths = new Set();

    for (const [, section] of index.matchAll(/<loc>([^<]+)<\/loc>/g)) {
        const xml = await (await fetch(section.replace(LIVE, BASE))).text();

        for (const [, loc] of xml.matchAll(/<loc>([^<]+)<\/loc>/g)) {
            const path = normalise(new URL(loc).pathname);

            if (isPage(path)) {
                paths.add(path);
            }
        }
    }

    return paths;
}

async function crawl() {
    const queue = ['/', '/sitemap', ...await sitemapPaths()];
    const seen = new Set(queue);

    const worker = async () => {
        while (queue.length) {
            const path = queue.shift();
            const result = await fetchPage(path);

            if (result.redirect !== undefined) {
                redirects.set(path, result.redirect);

                if (result.redirect && isPage(result.redirect) && !seen.has(result.redirect)) {
                    seen.add(result.redirect);
                    queue.push(result.redirect);
                }

                continue;
            }

            if (!result.html) {
                missing.add(path);

                continue;
            }

            pages.set(path, result.html);

            for (const link of linksIn(result.html, path)) {
                if (!seen.has(link)) {
                    seen.add(link);
                    queue.push(link);
                }
            }
        }
    };

    while (queue.length) {
        await Promise.all(Array.from({ length: CONCURRENCY }, worker));
    }
}

function resolveRedirects(path) {
    const visited = new Set();

    while (redirects.has(path) && !visited.has(path)) {
        visited.add(path);
        path = redirects.get(path);
    }

    return path;
}

function asset(path) {
    if (!existsSync(join(PUBLIC, path)) || !statSync(join(PUBLIC, path)).isFile()) {
        throw new Error(`Asset ${path} is referenced but missing from public/`);
    }

    assets.add(path);

    return PREFIX + path;
}

// Maps a URL found in a page to its archived copy, to the live site, or leaves it alone when it is external.
function rewriteUrl(raw, from) {
    if (raw === '' || raw.startsWith('#') || /^(?:data|mailto|tel|javascript):/i.test(raw)) {
        return raw;
    }

    const decoded = raw.replaceAll('&amp;', '&');
    const url = new URL(decoded, LIVE + from);

    if (url.origin !== LIVE) {
        return raw;
    }

    if (/^\/v\d+\//.test(url.pathname) || url.pathname === '/v1' || url.pathname === '/v1/') {
        return url.pathname + url.search + url.hash;
    }

    if (ASSET.test(url.pathname)) {
        return asset(decodeURIComponent(url.pathname));
    }

    const target = resolveRedirects(normalise(url.pathname));

    if (pages.has(target) && url.search === '') {
        return (target === '/' ? `${PREFIX}/` : `${PREFIX}${target}/`) + url.hash;
    }

    return (LIVE + url.pathname + url.search + url.hash).replaceAll('&', '&amp;');
}

const archiveBar = (path) => `<aside class="${VERSION}-archive-bar" aria-label="Archived version"><div class="${VERSION}-archive-bar__inner"><p class="${VERSION}-archive-bar__text"><strong class="${VERSION}-archive-bar__label">Archive ${VERSION}</strong> <span>EU VAT Info ${RELEASE} as it looked on ${CAPTURED}. Rates here are frozen and may be out of date.</span></p><a class="${VERSION}-archive-bar__link" href="${path}">View the current site<span aria-hidden="true">&rarr;</span></a></div></aside>`;

function transform(path, html) {
    const removals = [
        /<link rel="canonical"[^>]*>\s*/g,
        /<link rel="alternate"[^>]*>\s*/g,
        /<link rel="amphtml"[^>]*>\s*/g,
        /<link rel="manifest"[^>]*>\s*/g,
        /<link rel="(?:preconnect|dns-prefetch)"[^>]*>\s*/g,
        /<meta (?:property|name)="(?:og|twitter|article):[^"]*"[^>]*>\s*/g,
        /<meta name="(?:robots|googlebot|csrf-token|title)"[^>]*>\s*/g,
        /<script[^>]*type="application\/ld\+json"[^>]*>[\s\S]*?<\/script>\s*/g,
        /<script>\s*if \('serviceWorker' in navigator\)[\s\S]*?<\/script>\s*/g,
        /<script[^>]*>(?:(?!<\/script>)[\s\S])*?navigator\.modelContext[\s\S]*?<\/script>\s*/g,
        /<script[^>]*data-domain=[^>]*><\/script>\s*/g,
        /<!-- (?:Primary Meta Tags|Open Graph \/ Facebook|Twitter|Additional SEO) -->\s*/g,
    ];

    let page = removals.reduce((current, pattern) => current.replace(pattern, ''), html);

    page = page.replace(/<meta name="viewport"[^>]*>/, (viewport) => `${viewport}\n    <meta name="robots" content="noindex, nofollow">\n    <script src="${PREFIX}/archive.js"></script>`);
    page = page.replace(/<\/title>/, ` · Archive ${VERSION}</title>`);
    page = page.replace(/<\/head>/, `    <link rel="stylesheet" href="${PREFIX}/archive.css">\n</head>`);

    page = page.replace(/<script src="[^"]*\/livewire-[0-9a-f]+\/livewire(?:\.min)?\.js[^"]*"([^>]*)><\/script>/, (tag, attributes) => {
        const rest = attributes
            .replace(/\s*data-csrf="[^"]*"/, '')
            .replace(/\s*data-module-url="[^"]*"/, '')
            .replace(/\s*data-update-uri="[^"]*"/, '')
            .trim();

        return `<script src="${PREFIX}/livewire/livewire.min.js" data-csrf="archive" data-module-url="${PREFIX}/livewire" data-update-uri="${PREFIX}/livewire/update"${rest ? ' ' + rest : ''}></script>`;
    });

    page = page.replace(/\s+wire:navigate(?:\.hover)?(?=[\s>])/g, '');

    page = page.replace(/(\s)(href|src|action|poster)="([^"]*)"/g, (all, space, attribute, value) => `${space}${attribute}="${rewriteUrl(value, path)}"`);
    page = page.replace(/(\s)(srcset|imagesrcset)="([^"]*)"/g, (all, space, attribute, value) => `${space}${attribute}="${value.split(',').map((candidate) => {
        const [url, ...descriptor] = candidate.trim().split(/\s+/);

        return [rewriteUrl(url, path), ...descriptor].join(' ');
    }).join(', ')}"`);
    page = page.replace(/url\((['"]?)(\/(?:images|build)\/[^)'"]+)\1\)/g, (all, quote, url) => `url(${quote}${asset(url)}${quote})`);

    // Asset and search URLs inside Alpine expressions and component configuration.
    for (const slash of SLASHES) {
        page = page.replaceAll(inEncoding(`${LIVE}/images/`, slash), inEncoding(`${PREFIX}/images/`, slash));
    }
    page = page.replace(/(["'])\\?\/images\\?\/flags\1/g, (all, quote) => `${quote}\\${PREFIX}\\/images\\/flags${quote}`);
    page = page.replace(new RegExp(`${escapeRegex(inEncoding(LIVE, '\\/'))}\\\\/search-index\\.json[^'"&]*`, 'g'), `\\${PREFIX}\\/search-index.json`);
    page = page.replace(/'\\\/vat-calculator'(?= \+ '\/' \+ \$wire\.selectedCountrySlug)/g, `'\\${PREFIX}\\/vat-calculator'`);

    page = rewriteEncodedPaths(page);

    return page.replace(/(<a href="#main-content"[^>]*>[\s\S]*?<\/a>)/, (skip) => `${skip}\n    ${archiveBar(LIVE + (path === '/' ? '/' : path))}`);
}

const escapeRegex = (value) => value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

// Internal page URLs inside JSON (component configuration, map data), in each escaping the templates produce.
function rewriteEncodedPaths(page) {
    const encodings = [
        { quote: '\\u0022', slash: '\\\\\\/' },
        { quote: '"', slash: '\\/' },
        { quote: "'", slash: '\\/' },
    ];

    for (const { quote, slash } of encodings) {
        const host = escapeRegex(LIVE.replaceAll('/', slash));
        const pattern = new RegExp(`${escapeRegex(quote)}(?:${host})?((?:${escapeRegex(slash)}[A-Za-z0-9-]+)+)${escapeRegex(quote)}`, 'g');

        page = page.replace(pattern, (all, encodedPath) => {
            const path = resolveRedirects(normalise(encodedPath.replaceAll(slash, '/')));

            if (!pages.has(path)) {
                return all;
            }

            return quote + (path === '/' ? `${PREFIX}/` : `${PREFIX}${path}/`).replaceAll('/', slash) + quote;
        });
    }

    return page;
}

function referencedFlags(html) {
    return [...html.matchAll(/\/images\/flags\/([a-z-]+)\.svg/g)].map((match) => `/images/flags/${match[1]}.svg`);
}

function writeAssets() {
    for (const path of [...assets].sort()) {
        const target = join(OUT, path);
        mkdirSync(dirname(target), { recursive: true });

        if (path.endsWith('.css')) {
            const css = readFileSync(join(PUBLIC, path), 'utf8').replace(/url\((['"]?)(\/(?:build|images)\/[^)'"]+)\1\)/g, (all, quote, url) => {
                const file = url.split(/[?#]/)[0];
                const nested = join(OUT, file);
                mkdirSync(dirname(nested), { recursive: true });
                cpSync(join(PUBLIC, file), nested);

                return `url(${quote}${PREFIX}${url}${quote})`;
            });
            writeFileSync(target, css);
        } else {
            cpSync(join(PUBLIC, path), target);
        }
    }

    mkdirSync(join(OUT, 'livewire'), { recursive: true });
    cpSync(LIVEWIRE, join(OUT, 'livewire/livewire.min.js'));
}

async function writeSearchIndex() {
    const items = await (await fetch(`${BASE}/search-index.json?locale=en`)).json();
    const archived = items.map((item) => ({ ...item, url: rewriteUrl(toLive(item.url), '/').replaceAll('&amp;', '&') }));
    writeFileSync(join(OUT, 'search-index.json'), JSON.stringify(archived));
}

await crawl();
process.stderr.write(`crawled ${pages.size} pages, ${redirects.size} redirects, ${missing.size} not found\n`);

for (const keep of ['archive.css', 'archive.js']) {
    if (!existsSync(join(OUT, keep))) {
        throw new Error(`public/${VERSION}/${keep} must exist before archiving`);
    }
}

for (const entry of ['build', 'images', 'livewire', 'search-index.json']) {
    rmSync(join(OUT, entry), { recursive: true, force: true });
}

for (const path of pages.keys()) {
    rmSync(join(OUT, path === '/' ? 'index.html' : path.slice(1)), { recursive: true, force: true });
}

const written = [];
for (const [path, html] of [...pages].sort(([a], [b]) => a.localeCompare(b))) {
    const page = transform(path, html);
    referencedFlags(page).forEach((flag) => assets.add(flag));
    const file = join(OUT, path === '/' ? '' : path.slice(1), 'index.html');
    mkdirSync(dirname(file), { recursive: true });
    writeFileSync(file, page);
    written.push(path);
}

// Flags picked at runtime by the validator and the command palette.
for (const flag of [...new Set([...pages.values()].flatMap((html) => [...html.matchAll(/"flag":"([a-z]{2})"|data-flag="([a-z]{2})"/g)].map((match) => match[1] ?? match[2])))]) {
    if (existsSync(join(PUBLIC, `images/flags/${flag}.svg`))) {
        assets.add(`/images/flags/${flag}.svg`);
    }
}

await writeSearchIndex();
writeAssets();

const calculators = written.map((path) => path.match(/^\/vat-calculator\/([a-z-]+)$/)?.[1]).filter(Boolean);
const script = readFileSync(join(OUT, 'archive.js'), 'utf8').replace(/const calculators = new Set\(\[.*?\]\);/, `const calculators = new Set(${JSON.stringify(calculators)});`);
writeFileSync(join(OUT, 'archive.js'), script);

const fingerprint = createHash('sha256').update(written.join('\n')).digest('hex').slice(0, 12);
process.stderr.write(`wrote ${written.length} pages and ${assets.size} assets to public/${VERSION} (page list ${fingerprint})\n`);

if (missing.size) {
    process.stderr.write(`not archived (non-200): ${[...missing].sort().join(', ')}\n`);
}
