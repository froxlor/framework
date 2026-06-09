import {defineConfig} from 'vite';
import {viteStaticCopy} from 'vite-plugin-static-copy';

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                app: 'resources/js/app.js',
                styles: 'resources/css/app.css',
            },
            output: {
                entryFileNames: '[name].js',
                assetFileNames: '[name].[ext]',
            },
        },
        cssCodeSplit: true,
    },
    plugins: [
        viteStaticCopy({
            targets: [
                {src: 'resources/js/blade.min.js', dest: 'libs/highlight.js'},
                {src: 'resources/js/highlight.min.js', dest: 'libs/highlight.js'},
                {src: 'resources/css/highlight.min.css', dest: 'libs/highlight.js'},
                {src: 'resources/img/*', dest: 'assets/img'}
            ],
        }),
    ],
});
