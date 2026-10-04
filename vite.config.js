import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/typography.css',
                'resources/css/language-switcher.css',
                'resources/css/site-navbar.css',
                'resources/css/editor.css',
                'resources/css/select.css',
                'resources/css/site.css',
                'resources/css/test-drive-drawer.css',
                'resources/js/app.js',
                'resources/js/admin.js',
                'resources/js/editor.js',
                'resources/js/site.js',
                'resources/js/site-navbar.js',
                'resources/js/test-drive-drawer.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
