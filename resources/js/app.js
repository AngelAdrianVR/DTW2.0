import './bootstrap';
import '../css/app.css';

import { createApp, Fragment, h } from 'vue';
import { createInertiaApp, usePage } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import reveal from './directives/reveal';
import PageCurtain from './Components/Landing/PageCurtain.vue';
import CempasuchilCurtain from './Components/Landing/CempasuchilCurtain.vue';
import SnowCurtain from './Components/Landing/SnowCurtain.vue';

// Importa PrimeVue y su configuración
import PrimeVue from 'primevue/config';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';
import ToastService from 'primevue/toastservice';
import Chart from 'primevue/chart';
import 'primeicons/primeicons.css';
import ConfirmationService from 'primevue/confirmationservice'; 
import Aura from '@primeuix/themes/aura'; // Se importa el preset del tema Aura

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // La cortina de transición se monta junto a la página (no dentro) para
        // que sobreviva a cada navegación de Inertia.
        const Root = {
            render: () => h(Fragment, [h(App, props), h(PageCurtain), h(CempasuchilCurtain), h(SnowCurtain)]),
        };

        const vueApp = createApp(Root);
        
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
            // Configuración de PrimeVue con el tema Aura y modo oscuro
            .use(PrimeVue, {
                ripple: true,
                theme: {
                    preset: Aura,
                    options: {
                        // Conecta el modo oscuro de PrimeVue con el de Tailwind CSS
                        darkModeSelector: '.dark',
                        // AGREGAR ESTO: Define el orden de prioridad de los estilos
                        cssLayer: {
                            name: 'primevue',
                            order: 'tailwind-base, primevue, tailwind-utilities'
                        }
                    }
                }
            })
            .component('Accordion', Accordion)
            .component('AccordionTab', AccordionTab)
            .component('Chart', Chart)
            .use(ConfirmationService)
            .use(ToastService)
            .directive('reveal', reveal)
            .mount(el);
    },
    progress: {
        color: '#A855F7',
    },
});
