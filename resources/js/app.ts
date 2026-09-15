import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import Toaster from './components/ui/Toaster.vue'

import '../css/app.css' // Возвращаем стили

const appName = 'Tutors Club'

// Метрика не перезагружает страницу при SPA-переходах — шлём просмотр вручную
const metrikaId = window.yandexMetrikaId
if (metrikaId) {
    router.on('navigate', () => {
        window.ym?.(metrikaId, 'hit', window.location.href, { title: document.title })
    })
}

createInertiaApp({
    // Резолв страниц генерирует @inertiajs/vite: ленивая загрузка по чанкам
    pages: './Pages',
    title: (title) => (title ? `${title} — ${appName}` : appName),
    setup({ el, App, props, plugin }) {
        if (!el) {
            return;
        }

        createApp({ render: () => [h(App, props), h(Toaster)] })
            .use(plugin)
            .mount(el);
    },
});