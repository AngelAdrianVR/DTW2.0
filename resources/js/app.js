import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Importa PrimeVue y su configuración
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

// No es necesario importar useTranslations aquí si se define en el mixin

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const vueApp = createApp({ render: () => h(App, props) });
        
        // Hacemos la función de traducción 't()' disponible globalmente en todos los componentes
        vueApp.mixin({
            methods: {
                // Definimos un método global 't'
                t(key, replacements = {}) {
                    const { props } = usePage();
                    let translation = props.translations[key] || key;

                    // Lógica simple para reemplazar placeholders, ej: "Hola :name"
                    Object.keys(replacements).forEach(r => {
                        translation = translation.replace(`:${r}`, replacements[r]);
                    });

                    return translation;
                }
            }
        });

        vueApp
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, { ripple: true, theme: {
                preset: Aura
            } }) // Configura PrimeVue
            .mount(el);
    },
    progress: {
        color: '#6366F1',
    },
});
