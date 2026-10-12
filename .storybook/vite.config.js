import tailwindcss from '@tailwindcss/vite';
import { defineConfig } from 'vite';

// Storybook builds the stylesheet the site ships, without the Laravel plugin or the public directory (the site
// archives alone are tens of megabytes); images come from staticDirs in main.js.
export default defineConfig({
    plugins: [tailwindcss()],
    publicDir: false,
});
