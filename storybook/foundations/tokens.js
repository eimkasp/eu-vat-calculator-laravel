import tokens from '../generated/design.tokens.json';

const extension = (token) => token.$extensions?.['io.businesspress.vat'] ?? {};

// A DTCG group can hold $type and $description next to its tokens; only entries with a $value are tokens.
const entries = (group) => Object.entries(group).filter(([name, token]) => !name.startsWith('$') && token?.$value !== undefined);

const dimension = (value) => (value && typeof value === 'object' ? `${value.value}${value.unit}` : String(value));

export function element(tag, className = '', text = '') {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;

    return node;
}

function page(title, intro) {
    const root = element('div', 'bg-workspace min-h-full p-6 text-ink');
    root.append(element('h2', 'text-2xl font-bold tracking-[-0.02em]', title), element('p', 'mt-2 max-w-[68ch] text-sm leading-6 text-ink-muted', intro));

    return root;
}

export function colours(groupName, intro) {
    const root = page(groupName === 'semantic' ? 'Semantic colours' : `${groupName[0].toUpperCase()}${groupName.slice(1)} colours`, intro);
    const grid = element('div', 'mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3');

    for (const [name, token] of entries(tokens.color[groupName])) {
        const meta = extension(token);
        const card = element('div', 'app-surface flex items-center gap-4 p-3');
        const swatch = element('span', 'size-12 shrink-0 rounded-card border border-line');

        swatch.style.background = `var(${meta.cssVariable})`;
        const text = element('div', 'min-w-0');
        text.append(
            element('p', 'font-mono text-sm font-semibold', name),
            element('p', 'tabular text-xs text-ink-muted', meta.dark ? `${token.$value.hex} · dark ${meta.dark.hex}` : token.$value.hex),
        );

        if (token.$description) {
            text.append(element('p', 'mt-0.5 text-xs text-ink-quiet', token.$description));
        }

        card.append(swatch, text);
        grid.append(card);
    }

    root.append(grid);

    return root;
}

export function typography() {
    const root = page('Type scale', 'Familjen Grotesk at weight 600 for headings and figures, IBM Plex Sans for reading and JetBrains Mono for machine values. Headings stay one colour, labels stay in sentence case, and every figure that can change is set in tabular numbers.');
    const list = element('div', 'mt-6 app-surface divide-y divide-line');

    for (const [name, token] of entries(tokens.typography)) {
        const value = token.$value;
        const row = element('div', 'grid gap-2 p-4 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-baseline');
        const sample = element('p', 'min-w-0 text-ink', name === 'label' ? 'Amount (excl. VAT)' : 'Germany VAT calculator 19%');

        sample.style.fontSize = dimension(value.fontSize);
        sample.style.fontWeight = value.fontWeight;
        sample.style.lineHeight = value.lineHeight;
        sample.style.letterSpacing = dimension(value.letterSpacing ?? 0);
        row.append(element('p', 'font-mono text-xs text-ink-muted', `${name} · ${dimension(value.fontSize)} / ${value.fontWeight}`), sample);
        list.append(row);
    }

    root.append(list);

    return root;
}

export function shape() {
    const root = page('Radius and elevation', 'Three radius tokens: 6px for keys, fields and chips, 8px for cards and panels; the cut and cut-sm utilities bevel two opposite corners. Depth comes from hairlines first, then three shadows; keys add a 2px bottom edge.');
    const grid = element('div', 'mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3');

    for (const [name, token] of entries(tokens.radius)) {
        const meta = extension(token);
        const tile = element('div', 'flex h-28 flex-col justify-end border border-line-strong bg-surface p-4');

        tile.style.borderRadius = `var(${meta.cssVariable})`;
        tile.append(element('p', 'font-mono text-sm font-semibold', meta.utility ?? name), element('p', 'tabular text-xs text-ink-muted', dimension(token.$value)));
        grid.append(tile);
    }

    for (const [name, token] of entries(tokens.shadow)) {
        const meta = extension(token);
        const tile = element('div', 'flex h-28 flex-col justify-end rounded-card border border-line bg-surface p-4');

        tile.style.boxShadow = `var(${meta.cssVariable})`;
        tile.append(element('p', 'font-mono text-sm font-semibold', meta.utility ?? `shadow-${name}`), element('p', 'text-xs text-ink-muted', token.$description ?? ''));
        grid.append(tile);
    }

    root.append(grid);

    return root;
}
