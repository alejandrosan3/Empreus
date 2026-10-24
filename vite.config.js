import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/lot-map.js', 'resources/js/app.js', 'resources/js/statistics.js'],
            refresh: true,
        }),
    ],
});
