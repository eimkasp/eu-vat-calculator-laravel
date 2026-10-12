import { withThemeByClassName } from '@storybook/addon-themes';
import Alpine from 'alpinejs';
import '../storybook/storybook.css';
import '../resources/js/app.js';

// The site gets Alpine from the Livewire runtime; Storybook loads the same version so the stores and components in
// resources/js/app.js register exactly as they do on the site.
window.Alpine = Alpine;
Alpine.start();

/** @type {import('@storybook/html-vite').Preview} */
export default {
    decorators: [
        withThemeByClassName({
            themes: { light: '', dark: 'dark' },
            defaultTheme: 'light',
            parentSelector: 'html',
        }),
    ],
    parameters: {
        layout: 'fullscreen',
        a11y: { test: 'error' },
        backgrounds: { disable: true },
        controls: { disable: true },
        actions: { disable: true },
        interactions: { disable: true },
        options: {
            storySort: { order: ['Introduction', 'Foundations', 'Components', 'Patterns'] },
        },
    },
    tags: ['autodocs'],
};
