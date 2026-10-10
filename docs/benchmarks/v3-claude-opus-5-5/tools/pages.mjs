// The representative pages of the v3 benchmark, the main answer each should give and its keyboard-only main task.
export const CANONICAL_HOST = 'https://vat.businesspress.io';
export const LOCALES = ['en', 'de', 'mt'];

export const localized = (path, locale) => (locale === 'en' ? path : `/${locale}${path === '/' ? '' : path}`);

const calculatorTask = {
    name: 'Type an amount with the keyboard and get a total',
    focus: 'input[id^="amount-"]',
    steps: [{ type: '250', wait: 500 }],
    done: () => /\b(297[.,]50|295[.,]00|300[.,]00|\d{3}[.,]\d{2})\b/.test(document.querySelector('[data-calculator-result]')?.textContent ?? '')
        && !/^\D*0[.,]00\D*$/.test(document.querySelector('[data-calculator-result]')?.textContent ?? '')
        && (document.querySelector('input[id^="amount-"]')?.value ?? '') === '250',
};

const linkTask = (name, selector) => ({
    name,
    focus: selector,
    steps: [{ press: 'Enter', wait: 1500 }],
    navigates: true,
});

export const PAGES = [
    {
        path: '/',
        answer: { default: '\\d{1,2}(?:[.,]\\d)?\\s?%' },
        keyboard: calculatorTask,
    },
    {
        path: '/vat-calculator',
        answer: { default: '\\d{1,2}(?:[.,]\\d)?\\s?%' },
        keyboard: calculatorTask,
    },
    {
        path: '/vat-calculator/germany',
        answer: { default: '19\\s?%' },
        keyboard: { ...calculatorTask, done: () => /297[.,]50/.test(document.querySelector('[data-calculator-result]')?.textContent ?? '') },
    },
    {
        path: '/vat-calculator/malta',
        answer: { default: '18\\s?%' },
        keyboard: { ...calculatorTask, done: () => /295[.,]00/.test(document.querySelector('[data-calculator-result]')?.textContent ?? '') },
    },
    {
        path: '/vat-number-validator',
        answer: { default: 'VIES' },
        keyboard: {
            name: 'Enter a VAT number with the keyboard and submit it',
            focus: '#vat-number',
            steps: [{ type: 'DE811569869', wait: 300 }, { press: 'Enter', wait: 500 }],
            done: () => Boolean(document.querySelector('[data-validation-result], form ~ * [role="alert"], [aria-live] [role="alert"]')),
            timeout: 45000,
        },
    },
    {
        path: '/vat-map',
        answer: { default: '\\d{1,2}(?:[.,]\\d)?\\s?%' },
        keyboard: {
            name: 'Select a country on the map with the keyboard',
            focus: '[data-iso][tabindex="0"]',
            steps: [{ press: 'Enter', wait: 500 }],
            done: () => [...document.querySelectorAll('[aria-live="polite"]')].some((node) => node.offsetParent !== null && /\d\s?%/.test(node.textContent)),
        },
    },
    {
        path: '/vat-changes',
        answer: { default: '(19|20)\\d\\d' },
        keyboard: {
            name: 'Filter the rate history by country with the keyboard',
            focus: '#filter-country',
            steps: [{ press: 'ArrowDown', wait: 2500 }],
            done: () => (document.querySelector('#filter-country')?.value ?? '') !== '',
        },
    },
    {
        path: '/compare/germany-vs-france-vat',
        answer: { default: '19\\s?%[\\s\\S]*20\\s?%|20\\s?%[\\s\\S]*19\\s?%' },
        keyboard: linkTask('Open a compared country calculator with the keyboard', 'main a[href*="/vat-calculator/"]'),
    },
    {
        path: '/vat-calculation/germany/1000/19/exclude',
        answer: { default: '1[.,\\s\\u00a0\\u202f]?190' },
        keyboard: linkTask('Open the calculation in the calculator with the keyboard', 'main a[href*="/vat-calculator"]'),
    },
    {
        path: '/top-vat-calculations/1000',
        answer: { default: '1[.,\\s\\u00a0\\u202f]?190|1[.,\\s\\u00a0\\u202f]?2\\d\\d[.,]\\d\\d' },
        keyboard: linkTask('Open a country calculation with the keyboard', 'main a[href*="/vat-calculation/"]'),
    },
    {
        path: '/vat-rates/categories',
        answer: { default: '\\d{1,2}(?:[.,]\\d)?\\s?%' },
        keyboard: linkTask('Open a category with the keyboard', 'main a[href*="/vat-rates/categories/"]'),
    },
    {
        path: '/mcp-server',
        answer: { default: '/api/mcp' },
        keyboard: {
            name: 'Copy the MCP endpoint with the keyboard',
            focus: 'main button[aria-label], main button',
            steps: [{ press: 'Enter', wait: 800 }],
            done: () => Boolean(document.querySelector('[role="status"], [aria-live]')?.textContent.trim()),
        },
    },
];
