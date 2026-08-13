import './bootstrap';
import '../css/app.css';
import Alpine from 'alpinejs'
import anchor from '@alpinejs/anchor'
import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue,route } from '../../vendor/tightenco/ziggy/dist';
import { Ziggy } from "./ziggy"

const appName = "Kontakami"
window.route = (name?: string, params?: any , absolute?: boolean) => route(name, params, absolute, Ziggy)

Alpine.plugin(anchor)
Alpine.start()
window.Alpine = Alpine


createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue,Ziggy)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
