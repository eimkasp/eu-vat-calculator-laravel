export const MAX_AMOUNT = 1e12;

export function round2(value) {
    const number = Number(value);

    if (!Number.isFinite(number)) {
        return 0;
    }

    const rounded = Number(`${Math.round(Number(`${Math.abs(number)}e2`))}e-2`);

    return number < 0 ? -rounded : rounded;
}

const decimalSeparators = new Map();

function decimalSeparatorFor(locale) {
    if (!decimalSeparators.has(locale)) {
        let separator = '.';

        try {
            separator = new Intl.NumberFormat(locale).formatToParts(1.1).find((part) => part.type === 'decimal')?.value ?? '.';
        } catch {
            separator = '.';
        }

        decimalSeparators.set(locale, separator);
    }

    return decimalSeparators.get(locale);
}

/**
 * Parses human-typed amounts such as "1,234.56", "1.234,56", "1 234,5", "€100" or "12'500.10".
 * A single separator followed by exactly three digits is ambiguous, so the locale decides.
 */
export function parseAmount(input, locale = 'en') {
    if (typeof input === 'number') {
        return Number.isFinite(input) ? input : null;
    }

    let value = String(input ?? '').trim().replace(/[\s\u00a0\u202f']/g, '');

    if (value === '') {
        return null;
    }

    const negative = /^-|-$/.test(value);
    value = value.replace(/[^\d.,]/g, '');

    if (!/\d/.test(value)) {
        return null;
    }

    const lastDot = value.lastIndexOf('.');
    const lastComma = value.lastIndexOf(',');

    if (lastDot !== -1 && lastComma !== -1) {
        const decimal = lastDot > lastComma ? '.' : ',';
        const thousands = decimal === '.' ? ',' : '.';
        value = value.split(thousands).join('');

        if (value.split(decimal).length > 2) {
            return null;
        }

        value = value.replace(decimal, '.');
    } else if (lastDot !== -1 || lastComma !== -1) {
        const separator = lastDot !== -1 ? '.' : ',';
        const occurrences = value.split(separator).length - 1;
        const fraction = value.slice(value.lastIndexOf(separator) + 1);

        if (occurrences > 1 || (fraction.length === 3 && separator !== decimalSeparatorFor(locale))) {
            value = value.split(separator).join('');
        } else {
            value = value.replace(separator, '.');
        }
    }

    if (!/^(\d+\.?\d*|\.\d+)$/.test(value)) {
        return null;
    }

    const number = Number.parseFloat(value);

    return negative ? -number : number;
}

export function calculateVat(amount, rate, mode) {
    const safeAmount = Math.min(Math.max(Number(amount) || 0, 0), MAX_AMOUNT);
    const safeRate = Math.min(Math.max(Number(rate) || 0, 0), 100);

    if (mode === 'include') {
        const gross = round2(safeAmount);
        const net = round2(gross / (1 + safeRate / 100));

        return { net, vat: round2(gross - net), gross };
    }

    const net = round2(safeAmount);
    const vat = round2((net * safeRate) / 100);

    return { net, vat, gross: round2(net + vat) };
}

const moneyFormatters = new Map();

export function formatMoney(value, currency = 'EUR', locale = 'en') {
    const key = `${locale}|${currency}`;

    if (!moneyFormatters.has(key)) {
        let formatter;

        try {
            formatter = new Intl.NumberFormat(locale, { style: 'currency', currency, minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } catch {
            formatter = new Intl.NumberFormat(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        moneyFormatters.set(key, formatter);
    }

    return moneyFormatters.get(key).format(Number(value) || 0);
}

const percentFormatters = new Map();

export function formatPercent(value, locale = 'en') {
    if (!percentFormatters.has(locale)) {
        percentFormatters.set(locale, new Intl.NumberFormat(locale, { maximumFractionDigits: 2 }));
    }

    return `${percentFormatters.get(locale).format(Number(value) || 0)}%`;
}

export function normalizeSearch(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .trim();
}
