import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import Toaster from './components/ui/Toaster.vue'

import '../css/app.css' // Возвращаем стили

const appName = 'Tutors Club'

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