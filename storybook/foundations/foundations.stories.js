import { colours, shape, typography } from './tokens.js';

export default {
    title: 'Foundations/Tokens',
    parameters: {
        docs: {
            description: {
                component: 'Read from storybook/generated/design.tokens.json, the Design Tokens file /styleguide/design.tokens.json serves. Every swatch uses the CSS variable, so the theme switch in the toolbar shows the dark values.',
            },
        },
    },
};

export const SemanticColours = {
    name: 'Semantic colours',
    render: () => colours('semantic', 'Semantic tokens carry meaning, not hue. Each has a light and a dark value; the data-theme attribute on the html element swaps them.'),
};

export const FixedColours = {
    name: 'Fixed colours',
    render: () => colours('fixed', 'The code surfaces keep one value in both themes: they stay graphite in light and dark.'),
};

export const Typography = {
    name: 'Typography',
    render: () => typography(),
};

export const Shape = {
    name: 'Radius and elevation',
    render: () => shape(),
};
