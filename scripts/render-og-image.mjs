// Renders resources/og/default.html to public/images/og-default.png (1200×630), the default social image.
// Usage: node scripts/render-og-image.mjs, with Playwright installed (or PLAYWRIGHT=/path/to/playwright/index.mjs).
import { fileURLToPath } from 'node:url';

const { chromium } = await import(process.env.PLAYWRIGHT ?? 'playwright');
const root = fileURLToPath(new URL('..', import.meta.url));
const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1200, height: 630 } });
await page.goto(`file://${root}resources/og/default.html`);
await page.evaluate(() => document.fonts.ready);
await page.screenshot({ path: `${root}public/images/og-default.png` });
await browser.close();
