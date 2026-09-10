import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

// Fonts are not bundled here: the tool loads each design's pairing from Bunny
// Fonts at runtime, since which pairing is on screen changes per design.
export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ]),
    server: {
        // Bind and advertise the same address. Without this the dev server
        // listens on 127.0.0.1 but writes http://[::1]:5173 into public/hot,
        // and any browser that resolves the IPv6 address gets no stylesheet.
        host: '127.0.0.1',
        cors: true,
        watch: {
            ignored: ['**/.agents/**', '**/.claude/**', '**/storage/framework/views/**', '**/vendor/**'],
        },
    },
});
