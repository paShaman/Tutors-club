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
    title: (title) => (title ? `${title} — ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        const page = pages[`./Pages/${name}.vue`] as any;
        return page.default;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => [h(App, props), h(Toaster)] })
            .use(plugin)
            .mount(el);
    },
});