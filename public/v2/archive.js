(() => {
    const prefix = '/v2';
    const updatePath = '/v2/livewire/update';
    const calculators = new Set(["austria","belgium","bulgaria","croatia","cyprus","czech-republic","denmark","estonia","finland","france","germany","greece","hungary","iceland","ireland","italy","latvia","lithuania","luxembourg","malta","netherlands","norway","poland","portugal","romania","slovakia","slovenia","spain","sweden","switzerland","turkey","united-kingdom"]);
    const browserOnly = new Set(['calculate']);
    const currentPath = location.pathname.replace(/^\/v2(?=\/|$)/, '').replace(/(.)\/$/, '$1') || '/';
    const nativeFetch = window.fetch.bind(window);
    let leaving = false;
    let notice;
    let live;

    const target = (input) => new URL(input instanceof Request ? input.url : String(input), location.href);

    const reply = (status, data) => new Response(JSON.stringify(data), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });

    const hide = () => {
        if (notice) {
            notice.hidden = true;
        }
    };

    const notify = () => {
        if (leaving) {
            return;
        }

        if (!notice) {
            notice = document.createElement('div');
            notice.className = 'v2-archive-notice';
            notice.innerHTML = '<p>Live features such as VAT number checks, filters, sign-ups and the API and MCP testers are switched off in this archive. <a></a></p>'
                + '<button type="button" aria-label="Dismiss"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>';
            notice.querySelector('a').href = currentPath;
            notice.querySelector('a').textContent = 'Use them on the current site.';
            notice.querySelector('button').addEventListener('click', hide);
            document.body.append(notice);
        }

        notice.hidden = false;

        if (live) {
            live.textContent = '';
            setTimeout(() => {
                live.textContent = 'Live features are switched off in this archive. Use them on the current site.';
            }, 100);
        }
    };

    const leaveFor = (slug) => {
        leaving = true;
        location.assign(`${prefix}/vat-calculator/${slug}/`);
    };

    // Answers the Livewire 4 update endpoint: calculations run in the browser, so the server only ever recorded
    // them; picking another calculator country opens that country's archived page; everything else is a live action.
    const answer = (body) => {
        let components = [];

        try {
            components = JSON.parse(body).components ?? [];
        } catch {
            components = [];
        }

        const slug = components
            .flatMap((component) => [
                component.updates?.selectedCountrySlug,
                ...(component.calls ?? []).filter((call) => call.method === 'selectCountry').map((call) => call.params?.[0]),
            ])
            .find((value) => typeof value === 'string');

        if (slug !== undefined && calculators.has(slug)) {
            leaveFor(slug);

            return null;
        }

        const onlyBrowserWork = components.length > 0 && components.every((component) =>
            Object.keys(component.updates ?? {}).length === 0
            && (component.calls ?? []).length > 0
            && component.calls.every((call) => browserOnly.has(call.method)));

        if (onlyBrowserWork) {
            return reply(200, {
                components: components.map((component) => ({
                    snapshot: component.snapshot,
                    effects: { returns: component.calls.map(() => null) },
                })),
                assets: [],
            });
        }

        return reply(503, {});
    };

    window.fetch = (input, init = {}) => {
        const url = target(input);
        const method = String(init.method ?? (input instanceof Request ? input.method : 'GET')).toUpperCase();

        if (url.origin === location.origin && url.pathname === updatePath) {
            const response = answer(init.body);

            // While the archived page of another country opens, the request is left pending rather than failed.
            return response ? Promise.resolve(response) : new Promise(() => {});
        }

        if (url.origin === location.origin && url.pathname.startsWith(`${prefix}/`) && method === 'GET') {
            return nativeFetch(input, init);
        }

        notify();

        return Promise.reject(new TypeError('Live requests are switched off in this archive.'));
    };

    document.addEventListener('DOMContentLoaded', () => {
        live = document.createElement('div');
        live.className = 'v2-archive-live';
        live.setAttribute('role', 'status');
        document.body.append(live);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hide();
        }
    });

    document.addEventListener('livewire:init', () => {
        window.Livewire.interceptRequest(({ request, onError }) => {
            if (target(request.uri).pathname !== updatePath) {
                return;
            }

            onError(({ preventDefault }) => {
                preventDefault();
                notify();
            });
        });
    });
})();
