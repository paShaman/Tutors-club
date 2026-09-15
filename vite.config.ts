import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import inertia from '@inertiajs/vite'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            // Если SSR пока не используешь, лучше оставить закомментированным
            // ssr: 'resources/js/ssr.ts',
            publicDirectory: 'public_html',
            refresh: true,
        }),
        // Резолв страниц берёт на себя плагин (см. `pages` в resources/js/app.ts);
        // SSR выключен и на сервере (config/inertia.php), и здесь.
        inertia({ ssr: false }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            // Используем абсолютный путь через path для стабильности на сервере
            '@': path.resolve(import.meta.dirname, './resources/js'),
        },
    },
    build: {
        // Включаем разбивку на чанки для ускорения загрузки
        chunkSizeWarningLimit: 1600,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        return id.toString().split('node_modules/')[1].split('/')[0].toString();
                    }
                }
            }
        }
    }
})