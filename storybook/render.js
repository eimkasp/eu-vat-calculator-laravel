import catalog from './generated/examples.json';

/**
 * Renders a styleguide example exactly as /styleguide shows it: the Blade output on its canvas.
 */
export function renderExample(name) {
    const example = catalog.examples[name];
    const canvas = document.createElement('div');

    canvas.className = `${catalog.canvases[example.canvas] ?? catalog.canvases.surface} min-h-full p-5 text-ink sm:p-6`;
    canvas.innerHTML = example.html;

    return canvas;
}

export function exampleParameters(name) {
    const example = catalog.examples[name];

    return {
        docs: {
            description: { story: example.description },
            source: { code: example.source, language: 'html' },
        },
    };
}
