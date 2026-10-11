// Site-wide measurement for the EU VAT Info v3 benchmark: sitemap membership, internal links in, and whether the
// facts on each page are reachable through llms.txt, the REST API and the MCP server.
// Usage: node site.mjs --base=http://127.0.0.1:8123 --out=../site.json
import { writeFileSync } from 'node:fs';
import { PAGES, LOCALES, CANONICAL_HOST, localized } from './pages.mjs';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => arg.replace(/^--/, '').split('=')));
const BASE = (args.base ?? 'http://127.0.0.1:8123').replace(/\/$/, '');
const OUT = args.out ?? 'site.json';
const CONCURRENCY = Number(args.concurrency ?? 6);

const local = (url) => url.replace(CANONICAL_HOST, BASE);
const normalise = (path) => path.replace(/(.)\/$/, '$1');

async function text(url, init) {
    const response = await fetch(url, init);

    return { status: response.status, type: response.headers.get('content-type') ?? '', body: await response.text() };
}

async function sitemapUrls() {
    const index = await text(`${BASE}/sitemap.xml`);
    const sections = [...index.body.matchAll(/<loc>([^<]+)<\/loc>/g)].map((match) => match[1]);
    const urls = new Set();

    for (const section of sections) {
        const xml = await text(local(section));
        [...xml.body.matchAll(/<loc>([^<]+)<\/loc>/g)].forEach((match) => urls.add(match[1]));
    }

    return { sections: sections.length, urls };
}

async function pool(items, worker) {
    const queue = [...items];
    const runners = Array.from({ length: CONCURRENCY }, async () => {
        while (queue.length) {
            await worker(queue.shift());
        }
    });

    await Promise.all(runners);
}

async function inboundLinks(urls) {
    const inbound = new Map();

    await pool([...urls], async (url) => {
        try {
            const page = await text(local(url));
            const source = normalise(new URL(url).pathname);
            const targets = new Set();

            for (const match of page.body.matchAll(/<a\s[^>]*href="([^"]+)"/g)) {
                const target = new URL(match[1].replaceAll('&amp;', '&'), url);

                if ([new URL(CANONICAL_HOST).host, new URL(BASE).host].includes(target.host)) {
                    targets.add(normalise(target.pathname));
                }
            }

            targets.delete(source);
            targets.forEach((target) => inbound.set(target, (inbound.get(target) ?? 0) + 1));
        } catch {
            // A page that fails to load contributes no links.
        }
    });

    return inbound;
}

async function mcp(method, params = {}) {
    const response = await fetch(`${BASE}/api/mcp`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream', 'MCP-Protocol-Version': '2025-06-18' },
        body: JSON.stringify({ jsonrpc: '2.0', id: 1, method, params }),
    });

    return { status: response.status, json: await response.json().catch(() => null) };
}

const sitemap = await sitemapUrls();
process.stderr.write(`sitemap: ${sitemap.urls.size} URLs in ${sitemap.sections} sections\n`);

const crawlSet = [...sitemap.urls].filter((url) => {
    const path = new URL(url).pathname;
    const locale = path.match(/^\/([a-z]{2})(\/|$)/)?.[1];

    return locale ? LOCALES.includes(locale) : true;
});
process.stderr.write(`crawling ${crawlSet.length} sitemap URLs in ${LOCALES.join(', ')} for inbound links\n`);
const inbound = await inboundLinks(crawlSet);

const llms = await text(`${BASE}/llms.txt`);
const llmsFull = await text(`${BASE}/llms-full.txt`);
const api = await text(`${BASE}/api/v1/countries`);
const apiGermany = await text(`${BASE}/api/v1/countries/germany`);
const apiCalc = await text(`${BASE}/api/v1/calculate?amount=1000&country=germany&mode=add`);
const openapi = await text(`${BASE}/api/v1/openapi.json`);
const initialize = await mcp('initialize', { protocolVersion: '2025-06-18', capabilities: {}, clientInfo: { name: 'benchmark', version: '1' } });
const tools = await mcp('tools/list');
const toolNames = tools.json?.result?.tools?.map((tool) => tool.name) ?? [];

const facts = {
    germanyStandardRate19: {
        llmsTxt: /Germany[^\n]*19\s?%/.test(llms.body) || /Germany[^\n]*19\s?%/.test(llmsFull.body),
        api: /"standard_rate"\s*:\s*"?19/.test(apiGermany.body) || /19/.test(apiGermany.body),
        mcp: toolNames.length > 0 ? /19/.test(JSON.stringify((await mcp('tools/call', { name: toolNames.find((name) => /rate/.test(name)) ?? toolNames[0], arguments: { country: 'germany' } })).json ?? {})) : false,
    },
    maltaStandardRate18: {
        llmsTxt: /Malta[^\n]*18\s?%/.test(llms.body) || /Malta[^\n]*18\s?%/.test(llmsFull.body),
        api: /Malta/.test(api.body) && /18/.test(api.body),
    },
    germany1000Gross1190: {
        api: /1190/.test(apiCalc.body.replace(/[.,]00/g, '')),
    },
};

const pages = [];
for (const definition of PAGES) {
    for (const locale of LOCALES) {
        const path = localized(definition.path, locale);
        const canonical = CANONICAL_HOST + (path === '/' ? '/' : path);

        pages.push({
            path,
            inSitemap: sitemap.urls.has(canonical) || sitemap.urls.has(canonical.replace(/\/$/, '')),
            internalLinksIn: inbound.get(normalise(path)) ?? 0,
        });
    }
}

writeFileSync(OUT, JSON.stringify({
    tool: 'site.mjs',
    measuredAt: new Date().toISOString(),
    base: BASE,
    sitemap: { sections: sitemap.sections, urls: sitemap.urls.size },
    inboundLinkMethod: `unique source pages linking to the page, among the ${crawlSet.length} sitemap URLs in ${LOCALES.join(', ')}`,
    machineReadable: {
        llmsTxt: { status: llms.status, bytes: llms.body.length },
        llmsFullTxt: { status: llmsFull.status, bytes: llmsFull.body.length },
        apiCountries: { status: api.status, type: api.type },
        apiCalculate: { status: apiCalc.status },
        openapi: { status: openapi.status },
        mcp: { initialize: initialize.status, protocolVersion: initialize.json?.result?.protocolVersion ?? null, tools: toolNames },
    },
    facts,
    pages,
}, null, 2));

process.stderr.write(`wrote ${OUT}\n`);
