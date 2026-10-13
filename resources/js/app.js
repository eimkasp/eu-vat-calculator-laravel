import { MAX_AMOUNT, calculateVat, formatMoney, formatPercent, normalizeSearch, parseAmount, round2 } from './vat';

const THEME_KEY = 'theme';

const storage = {
    get(key) {
        try {
            return window.localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, value);
        } catch {
            // Storage can be unavailable (private mode, blocked cookies); the preference then lasts for the page.
        }
    },
    remove(key) {
        try {
            window.localStorage.removeItem(key);
        } catch {
            // Nothing to clean up when storage is unavailable.
        }
    },
};

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const systemPrefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

function applyTheme(mode) {
    const dark = mode === 'dark' || (mode !== 'light' && systemPrefersDark());
    document.documentElement.classList.toggle('dark', dark);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#0b1220' : '#0a2a7a');
}

async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);

        return true;
    } catch {
        const field = Object.assign(document.createElement('textarea'), { value: text });
        field.setAttribute('readonly', '');
        field.style.cssText = 'position:fixed;opacity:0;pointer-events:none';
        document.body.append(field);
        field.select();
        const copied = document.execCommand('copy');
        field.remove();

        return copied;
    }
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('theme', {
        mode: storage.get(THEME_KEY) ?? 'system',
        set(mode) {
            this.mode = mode;
            storage.set(THEME_KEY, mode);
            applyTheme(mode);
        },
        cycle() {
            const order = ['system', 'light', 'dark'];
            this.set(order[(order.indexOf(this.mode) + 1) % order.length]);
        },
    });

    Alpine.store('toasts', {
        items: [],
        push(message, tone = 'success', sticky = false) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, tone, sticky });

            if (!sticky) {
                setTimeout(() => this.dismiss(id), 3800);
            }
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    Alpine.store('palette', {
        open: false,
        opener: null,
        show() {
            if (!this.open) {
                this.opener = document.activeElement;
            }

            this.open = true;
        },
        hide() {
            if (!this.open) {
                return;
            }

            this.open = false;
            const opener = this.opener;
            this.opener = null;
            setTimeout(() => opener?.isConnected && opener.focus({ preventScroll: true }), 0);
        },
        toggle() {
            if (this.open) {
                this.hide();
            } else {
                this.show();
            }
        },
    });

    Alpine.magic('rove', () => (event) => {
        const radios = [...event.currentTarget.querySelectorAll('[role="radio"]')];
        const index = radios.indexOf(document.activeElement);

        if (index === -1) {
            return;
        }

        const step = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1;
        const next = radios[(index + step + radios.length) % radios.length];
        next.click();
        next.focus();
    });

    Alpine.magic('copy', () => async (text, message) => {
        const copied = await copyText(text);
        Alpine.store('toasts').push(copied ? message : text, copied ? 'success' : 'info', !copied);
    });

    Alpine.data('commandPalette', ({ source, recentKey = 'palette-recent', validator = null } = {}) => ({
        query: '',
        active: 0,
        items: [],
        loading: false,
        loaded: false,
        recent: (() => {
            try {
                const stored = JSON.parse(storage.get(recentKey) ?? '[]');

                return Array.isArray(stored) ? stored.filter((url) => typeof url === 'string') : [];
            } catch {
                return [];
            }
        })(),
        get results() {
            const query = normalizeSearch(this.query);

            if (query === '') {
                const recent = this.recent.map((url) => this.items.find((item) => item.url === url)).filter(Boolean);

                return [...recent, ...this.items.filter((item) => !recent.includes(item))].slice(0, 12);
            }

            const words = query.split(/\s+/).filter(Boolean);
            const typeOrder = { country: 0, page: 0.5, 'country-page': 0.75 };
            const matches = this.items
                .map((item) => {
                    const title = normalizeSearch(item.title);
                    const haystack = `${title} ${normalizeSearch(`${item.subtitle ?? ''} ${item.keywords ?? ''}`)}`;

                    if (!words.every((word) => haystack.includes(word))) {
                        return null;
                    }

                    const rank = title.startsWith(query) ? 0 : (title.includes(query) ? 1 : (words.every((word) => title.includes(word)) ? 2 : 3));

                    return { item, score: rank + (typeOrder[item.type] ?? 1) };
                })
                .filter(Boolean)
                .sort((a, b) => a.score - b.score)
                .map(({ item }) => item);

            const number = this.query.toUpperCase().replace(/[\s.\-]/g, '');

            if (validator && /^[A-Z]{2}[0-9A-Z]{2,13}$/.test(number) && /\d/.test(number)) {
                matches.unshift({ type: 'action', title: validator.title, subtitle: number, url: `${validator.url}?vat_number=${encodeURIComponent(number)}`, flag: null, icon: 'shield-check', meta: null });
            }

            return matches.slice(0, 12);
        },
        async load() {
            if (this.loaded || this.loading || !source) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(source, { headers: { Accept: 'application/json' } });

                if (response.ok) {
                    this.items = await response.json();
                    this.loaded = true;
                }
            } catch {
                // Offline or blocked: the palette retries the next time it opens.
            } finally {
                this.loading = false;
            }
        },
        init() {
            this.$watch('query', () => (this.active = 0));
            this.$watch('$store.palette.open', (open) => {
                if (open) {
                    this.load();
                    this.query = '';
                    this.active = 0;
                    this.$nextTick(() => this.$refs.input?.focus());
                }
            });
        },
        move(step) {
            const total = this.results.length;

            if (total === 0) {
                return;
            }

            this.active = (this.active + step + total) % total;
            this.$nextTick(() => this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
        },
        go(item = this.results[this.active]) {
            if (!item) {
                return;
            }

            this.recent = [item.url, ...this.recent.filter((url) => url !== item.url)].slice(0, 5);
            storage.set(recentKey, JSON.stringify(this.recent));
            this.$store.palette.hide();
            window.location.assign(item.url);
        },
    }));

    Alpine.data('europeMap', (countries, active) => ({
        countries,
        hovered: null,
        selected: active ? countries[active] ?? null : null,
        x: 0,
        y: 0,
        init() {
            if (this.selected) {
                this.$nextTick(() => this.highlight(this.selected.code, 'is-selected'));
            }
        },
        regionFrom(event) {
            const region = event.target.closest?.('[data-iso]');

            return region ? this.countries[region.dataset.iso] ?? null : null;
        },
        hover(country) {
            this.hovered = country;
            this.highlight(country?.code ?? null, 'is-hovered');
        },
        hoverRegion(event) {
            this.hover(this.regionFrom(event));
        },
        track(event) {
            const bounds = this.$refs.canvas.getBoundingClientRect();
            this.x = event.clientX - bounds.left;
            this.y = event.clientY - bounds.top;
        },
        focusRegion(event) {
            const region = event.target.closest?.('[data-iso]');

            if (!region) {
                return;
            }

            const box = region.getBoundingClientRect();
            const bounds = this.$refs.canvas.getBoundingClientRect();
            this.x = box.left - bounds.left + box.width / 2;
            this.y = box.top - bounds.top + 8;
            this.hover(this.countries[region.dataset.iso] ?? null);
        },
        selectRegion(event) {
            const country = this.regionFrom(event);

            if (country) {
                this.selected = country;
                this.hover(null);
                this.highlight(country.code, 'is-selected');
            }
        },
        clear() {
            const code = this.selected?.code;
            this.selected = null;
            this.highlight(null, 'is-selected');
            this.$nextTick(() => this.$refs.canvas.querySelector(`[data-iso="${code}"][tabindex="0"]`)?.focus());
        },
        highlight(code, className) {
            this.$refs.canvas.querySelectorAll(`.${className}`).forEach((node) => node.classList.remove(className));

            if (code) {
                this.$refs.canvas.querySelectorAll(`[data-iso="${code}"]`).forEach((node) => node.classList.add(className));
            }
        },
    }));

    Alpine.data('vatCalculator', (config) => ({
        mode: config.mode === 'include' ? 'include' : 'exclude',
        amount: String(config.amount ?? ''),
        rate: Number(config.rate) || 0,
        customRate: config.customRate === null || config.customRate === undefined ? '' : formatPercent(config.customRate, config.locale || 'en').replace('%', ''),
        useCustomRate: config.customRate !== null && config.customRate !== undefined,
        currency: config.currency || 'EUR',
        currencySymbol: config.currencySymbol || '€',
        locale: config.locale || document.documentElement.lang || 'en',
        countries: config.countries ?? [],
        rateDescriptions: config.rateDescriptions ?? {},
        templates: config.templates ?? {},
        countryOpen: false,
        countryQuery: '',
        countryActive: 0,
        announcement: '',
        persistTimer: null,
        announceTimer: null,
        init() {
            this.$watch('countryQuery', () => (this.countryActive = 0));
            this.$watch('result', () => {
                clearTimeout(this.announceTimer);
                this.announceTimer = setTimeout(() => (this.announcement = this.summaryText()), 700);
            });
        },
        get effectiveRate() {
            if (!this.useCustomRate) {
                return this.rate;
            }

            const custom = parseAmount(this.customRate, this.locale);

            return custom === null ? 0 : Math.min(Math.max(custom, 0), 100);
        },
        get parsedAmount() {
            return parseAmount(this.amount, this.locale);
        },
        get invalid() {
            const parsed = this.parsedAmount;

            return String(this.amount).trim() !== '' && (parsed === null || parsed < 0 || parsed > MAX_AMOUNT);
        },
        get result() {
            return calculateVat(this.invalid ? 0 : (this.parsedAmount ?? 0), this.effectiveRate, this.mode);
        },
        get netShare() {
            return this.result.gross > 0 ? Math.round((this.result.net / this.result.gross) * 1000) / 10 : 100;
        },
        get filteredCountries() {
            const query = normalizeSearch(this.countryQuery);

            return query === '' ? this.countries : this.countries.filter((country) => normalizeSearch(`${country.name} ${country.iso} ${country.slug}`).includes(query));
        },
        money(value, currency = this.currency) {
            return formatMoney(value, currency, this.locale);
        },
        percent(value) {
            return formatPercent(value, this.locale);
        },
        summaryText() {
            const inputAmount = this.mode === 'include' ? this.result.gross : this.result.net;
            const country = this.countries.find((item) => item.slug === this.$wire.selectedCountrySlug)?.name ?? '';

            return String(this.templates[this.mode] ?? '')
                .replace(':amount', this.money(inputAmount))
                .replace(':rate', this.percent(this.effectiveRate))
                .replace(':country', country);
        },
        rateDescription() {
            if (this.useCustomRate) {
                return this.rateDescriptions.custom ?? '';
            }

            const option = (this.$wire.rates ?? []).find((item) => Number(item.rate) === Number(this.rate));

            return this.rateDescriptions[option?.type ?? 'standard'] ?? '';
        },
        setMode(mode) {
            this.mode = mode;
            this.persist();
        },
        setAmount(value) {
            this.amount = String(value);
            this.persist();
        },
        pickRate(rate) {
            this.useCustomRate = false;
            this.rate = Number(rate);
            this.persist();
        },
        enableCustomRate() {
            this.useCustomRate = true;
            this.customRate = String(this.rate);
            this.$nextTick(() => this.$refs.customRate?.select());
        },
        toggleCountries() {
            this.countryOpen = !this.countryOpen;

            if (this.countryOpen) {
                this.countryQuery = '';
                this.countryActive = Math.max(0, this.countries.findIndex((item) => item.slug === this.$wire.selectedCountrySlug));
                this.$nextTick(() => {
                    this.$refs.countrySearch?.focus();
                    this.scrollToActiveCountry();
                });
            }
        },
        moveCountry(step) {
            const total = this.filteredCountries.length;

            if (total > 0) {
                this.countryActive = (this.countryActive + step + total) % total;
                this.$nextTick(() => this.scrollToActiveCountry());
            }
        },
        scrollToActiveCountry() {
            const slug = this.filteredCountries[this.countryActive]?.slug;

            if (slug) {
                this.$refs.countryList?.querySelector(`[data-slug="${slug}"]`)?.scrollIntoView({ block: 'nearest' });
            }
        },
        syncCountryList() {
            const list = this.$refs.countryList;
            const filtered = this.filteredCountries;
            const positions = new Map(filtered.map((country, index) => [country.slug, index]));
            const active = filtered[this.countryActive]?.slug;
            const selected = this.$wire.selectedCountrySlug;

            list?.querySelectorAll('[data-slug]').forEach((option) => {
                const slug = option.dataset.slug;
                const index = positions.get(slug);
                const isSelected = slug === selected;
                option.hidden = index === undefined;
                option.setAttribute('aria-selected', isSelected.toString());
                option.classList.toggle('bg-action-soft', slug === active);
                option.querySelector('[data-group-label]')?.classList.toggle('hidden', !(index === 0 || (index > 0 && filtered[index - 1].group !== filtered[index].group)));
                const name = option.querySelector('[data-name]');
                name?.classList.toggle('font-semibold', isSelected);
                name?.classList.toggle('text-action-deep', isSelected);
                name?.classList.toggle('text-ink', !isSelected);
            });
        },
        countryOptionFrom(event) {
            return event.target.closest?.('[data-slug]') ?? null;
        },
        pickCountry(event) {
            const option = this.countryOptionFrom(event);

            if (option) {
                this.selectCountry(option.dataset.slug);
            }
        },
        hoverCountry(event) {
            const index = this.filteredCountries.findIndex((country) => country.slug === this.countryOptionFrom(event)?.dataset.slug);

            if (index !== -1 && index !== this.countryActive) {
                this.countryActive = index;
            }
        },
        closeCountries() {
            this.countryOpen = false;
            this.$nextTick(() => this.$refs.countryTrigger?.focus());
        },
        chooseActiveCountry() {
            const country = this.filteredCountries[this.countryActive];

            if (country) {
                this.selectCountry(country.slug);
            }
        },
        async selectCountry(slug) {
            this.closeCountries();

            if (slug === this.$wire.selectedCountrySlug) {
                return;
            }

            await this.$wire.selectCountry(slug);
            this.currency = this.$wire.currency;
            this.currencySymbol = this.$wire.currencySymbol;
            this.rate = Number(this.$wire.selectedRate) || 0;
            this.useCustomRate = false;
            this.persist();
        },
        async restore(entry) {
            if (entry.slug !== this.$wire.selectedCountrySlug) {
                await this.$wire.selectCountry(entry.slug);
                this.currency = this.$wire.currency;
                this.currencySymbol = this.$wire.currencySymbol;
            }

            const known = (this.$wire.rates ?? []).some((option) => Number(option.rate) === Number(entry.rate));
            this.mode = entry.mode;
            this.amount = String(entry.amount);
            this.useCustomRate = !known;
            this.rate = known ? Number(entry.rate) : this.rate;
            this.customRate = known ? '' : String(entry.rate);
            document.getElementById('hero-calculator')?.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'center' });
        },
        shareUrl(base, slug) {
            const amount = this.mode === 'include' ? this.result.gross : this.result.net;

            return `${base}/${slug}/${Number(amount.toFixed(2))}/${Number(this.effectiveRate.toFixed(2))}/${this.mode}`;
        },
        persist(immediately = false) {
            clearTimeout(this.persistTimer);

            if (this.invalid || !this.parsedAmount) {
                return;
            }

            this.persistTimer = setTimeout(() => this.$wire.calculate(this.mode, this.effectiveRate, this.parsedAmount), immediately ? 0 : 1500);
        },
    }));
    Alpine.data('viesValidator', ({ prefixes = {}, flagBase = '/images/flags', formats = {}, names = {}, mismatchTemplate = '' } = {}) => ({
        detected: false,
        mismatch: '',
        get flag() {
            const iso = String(this.$wire.country_code ?? '').toLowerCase();

            return /^[a-z]{2}$/.test(iso) ? `${flagBase}/${iso}.svg` : null;
        },
        detect(value) {
            const cleaned = String(value).toUpperCase().replace(/[\s./-]+/g, '');
            const iso = cleaned.length > 4 ? prefixes[cleaned.slice(0, 2)] : undefined;
            this.detected = Boolean(iso);

            if (iso && iso !== this.$wire.country_code) {
                this.$wire.country_code = iso;
            }

            this.checkFormat(value, false);
        },
        // Hints at a typing mistake once the field is left; VIES stays the authority, so nothing is blocked.
        checkFormat(value, settled = true) {
            const cleaned = String(value ?? '').toUpperCase().replace(/[\s./-]+/g, '');
            const selected = String(this.$wire.country_code ?? '').toUpperCase();
            const prefix = formats[cleaned.slice(0, 2)] ? cleaned.slice(0, 2) : (selected === 'GR' ? 'EL' : selected);
            const format = formats[prefix];

            if (!format || cleaned.length < 4) {
                this.mismatch = '';

                return;
            }

            const number = cleaned.startsWith(prefix) ? cleaned : prefix + cleaned;

            if (new RegExp(format.pattern).test(number)) {
                this.mismatch = '';
            } else if (settled) {
                this.mismatch = mismatchTemplate.replace(':country', names[prefix] ?? prefix).replace(':format', format.format);
            }
        },
    }));

    Alpine.data('lookupHistory', ({ key = 'vat_validation_history', limit = 8 } = {}) => ({
        items: [],
        init() {
            try {
                const stored = JSON.parse(storage.get(key) ?? '[]');
                this.items = Array.isArray(stored)
                    ? stored.filter((item) => typeof item?.cc === 'string' && typeof item?.vn === 'string').slice(0, limit)
                    : [];
            } catch {
                this.items = [];
            }
        },
        record(entry) {
            if (!entry?.cc || !entry?.vn) {
                return;
            }

            this.items = [entry, ...this.items.filter((item) => item.cc !== entry.cc || item.vn !== entry.vn)].slice(0, limit);
            storage.set(key, JSON.stringify(this.items));
        },
        clear() {
            this.items = [];
            storage.remove(key);
        },
        async open(item) {
            this.$wire.country_code = item.cc;
            this.$wire.vat_number = item.vn;
            document.getElementById('validator')?.scrollIntoView({ behavior: prefersReducedMotion() ? 'auto' : 'smooth', block: 'start' });
            await this.$wire.validateVat();
        },
    }));

    Alpine.data('embedBuilder', ({ base, country, heights = {} }) => ({
        country,
        style: 'vertical',
        get src() {
            const url = new URL(`${String(base).replace(/\/$/, '')}/${encodeURIComponent(this.country)}`);

            if (this.style === 'horizontal') {
                url.searchParams.set('style', 'horizontal');
            }

            return url.toString();
        },
        get height() {
            return heights[this.style] ?? 640;
        },
        get code() {
            return `<iframe src="${this.src}" title="EU VAT calculator" width="100%" height="${this.height}" style="border:0;background:transparent" loading="lazy"></iframe>`;
        },
    }));

    Alpine.data('mcpPlayground', ({ endpoint, examples }) => ({
        examples,
        active: Object.keys(examples)[0],
        loading: false,
        status: null,
        response: '',
        elapsed: null,
        get body() {
            return JSON.stringify(this.examples[this.active], null, 2);
        },
        get ok() {
            return this.status >= 200 && this.status < 300;
        },
        select(key) {
            this.active = key;
            this.status = null;
            this.response = '';
            this.elapsed = null;
        },
        async send() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.response = '';
            const started = performance.now();

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json, text/event-stream' },
                    body: this.body,
                });
                this.status = response.status;
                const text = await response.text();

                try {
                    this.response = JSON.stringify(JSON.parse(text), null, 2);
                } catch {
                    this.response = text;
                }
            } catch (error) {
                this.status = 0;
                this.response = error.message;
            } finally {
                this.elapsed = Math.round(performance.now() - started);
                this.loading = false;
            }
        },
    }));

    Alpine.data('apiPlayground', ({ endpoint, country = 'LT', number = '100019070512' }) => ({
        country,
        number,
        loading: false,
        status: null,
        response: '',
        elapsed: null,
        get body() {
            return JSON.stringify({ country_code: this.country.trim().toUpperCase(), vat_number: this.number.trim() }, null, 2);
        },
        get ok() {
            return this.status >= 200 && this.status < 300;
        },
        async send() {
            if (this.loading) {
                return;
            }

            this.loading = true;
            this.response = '';
            const started = performance.now();

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: this.body,
                });
                this.status = response.status;
                const text = await response.text();

                try {
                    this.response = JSON.stringify(JSON.parse(text), null, 2);
                } catch {
                    this.response = text;
                }
            } catch (error) {
                this.status = 0;
                this.response = error.message;
            } finally {
                this.elapsed = Math.round(performance.now() - started);
                this.loading = false;
            }
        },
    }));

    Alpine.data('sectionNav', () => ({
        current: null,
        init() {
            const sections = [...this.$el.querySelectorAll('a[href^="#"]')]
                .map((link) => document.getElementById(link.hash.slice(1)))
                .filter(Boolean);
            const visible = new Set();
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => (entry.isIntersecting ? visible.add(entry.target) : visible.delete(entry.target)));
                const active = sections.find((section) => visible.has(section));

                if (active) {
                    this.current = active.id;
                } else if (sections[0]?.getBoundingClientRect().top > window.innerHeight * 0.3) {
                    this.current = null;
                }
            }, { rootMargin: '-15% 0px -70% 0px' });

            sections.forEach((section) => observer.observe(section));

            this.$watch('current', (id) => {
                const box = this.$el.closest('aside');
                const link = id && this.$el.querySelector(`a[href="#${CSS.escape(id)}"]`);

                if (!box || box.scrollHeight <= box.clientHeight) {
                    return;
                }

                if (!link) {
                    box.scrollTop = 0;

                    return;
                }

                const linkRect = link.getBoundingClientRect();
                const boxRect = box.getBoundingClientRect();

                if (linkRect.top < boxRect.top || linkRect.bottom > boxRect.bottom) {
                    box.scrollTop += linkRect.top - boxRect.top - (box.clientHeight - linkRect.height) / 2;
                }
            });
        },
    }));
});

window.addEventListener('toast', (event) => {
    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;

    if (detail?.message && window.Alpine) {
        window.Alpine.store('toasts').push(detail.message, detail.tone ?? 'success');
    }
});

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if ((storage.get(THEME_KEY) ?? 'system') === 'system') {
        applyTheme('system');
    }
});

document.addEventListener('keydown', (event) => {
    const target = event.target;
    const typing = target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

    if ((event.key === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
        event.preventDefault();
        window.Alpine?.store('palette').toggle();
    }
});

window.EuVat = { parseAmount, calculateVat, formatMoney, formatPercent, round2, applyTheme };
