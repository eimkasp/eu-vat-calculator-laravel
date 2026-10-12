import { addons } from 'storybook/manager-api';
import { create } from 'storybook/theming';
import tokens from '../storybook/generated/design.tokens.json';

const hex = (group, name) => tokens.color[group][name].$value.hex;

addons.setConfig({
    theme: create({
        base: 'light',
        brandTitle: 'EU VAT Info design system',
        brandUrl: 'https://vat.businesspress.io/styleguide',
        brandTarget: '_blank',
        colorPrimary: hex('semantic', 'brand'),
        colorSecondary: hex('semantic', 'action'),
        appBg: hex('semantic', 'workspace'),
        appContentBg: hex('semantic', 'surface'),
        appBorderColor: hex('semantic', 'line'),
        appBorderRadius: 6,
        textColor: hex('semantic', 'ink'),
        textMutedColor: hex('semantic', 'ink-muted'),
        barBg: hex('semantic', 'surface'),
        barSelectedColor: hex('semantic', 'action'),
        fontBase: '"Inter Variable", "Inter", ui-sans-serif, system-ui, sans-serif',
        fontCode: 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
    }),
    panelPosition: 'right',
    selectedPanel: 'storybook/a11y/panel',
});
