/** @type {import('@storybook/html-vite').StorybookConfig} */
export default {
    framework: {
        name: '@storybook/html-vite',
        options: { builder: { viteConfigPath: '.storybook/vite.config.js' } },
    },
    stories: ['../storybook/**/*.mdx', '../storybook/**/*.stories.js'],
    addons: ['@storybook/addon-docs', '@storybook/addon-a11y', '@storybook/addon-themes'],
    staticDirs: [{ from: '../resources/images/flags', to: '/images/flags' }],
    core: { disableTelemetry: true },
};
