import { onMounted, ref } from 'vue';
import { trackCtaClick } from '@/utils/analytics';

/**
 * Saludo flotante de WhatsApp.
 *
 * El estado vive en el ámbito del módulo (no dentro del componente) por dos
 * razones:
 *
 *  1. Cada navegación de Inertia desmonta la página y, con ella, el marco
 *     público (`LandingShell`). Si el temporizador perteneciera al componente,
 *     `onBeforeUnmount` lo cancelaría y el saludo nunca llegaría a abrirse
 *     cuando el visitante navega en los primeros segundos.
 *  2. El saludo debe mostrarse una sola vez por carga de página: así un
 *     `setTimeout` compartido evita que se reprograme en cada vista.
 *
 * A propósito no se persiste nada en `sessionStorage`/`localStorage`: guardar
 * la marca hacía que, tras el primer aviso, el saludo no volviera a abrirse
 * nunca más en la pestaña (ni siquiera con F5), que era el fallo reportado.
 * Con este diseño cada carga de página vuelve a saludar a los `delay` ms.
 */

/** Milisegundos de espera por defecto antes de mostrar el saludo. */
const DEFAULT_DELAY = 6000;

/** El saludo está visible. */
const open = ref(false);

/** Ya se mostró al menos una vez en esta carga de página. */
const seen = ref(false);

/** Temporizador compartido entre todas las instancias montadas. */
let timer = null;

/**
 * Programa el saludo automático. Sólo se ejecuta una vez por carga de página.
 *
 * @param {number} delay Milisegundos de espera.
 */
function schedule(delay) {
    if (typeof window === 'undefined' || seen.value || timer !== null) {
        return;
    }

    const wait = Math.max(0, Number(delay) || 0);

    timer = window.setTimeout(() => {
        timer = null;
        seen.value = true;
        open.value = true;
        trackCtaClick('chat_prompt');
    }, wait);
}

/**
 * Estado y acciones del saludo de WhatsApp.
 *
 * @param {number} delay Milisegundos de espera antes de abrir el saludo.
 */
export function useWhatsAppPrompt(delay = DEFAULT_DELAY) {
    onMounted(() => schedule(delay));

    /** Abre el saludo a mano desde el botón flotante. */
    function show() {
        seen.value = true;
        open.value = true;
        trackCtaClick('chat_prompt_manual');
    }

    /** Cierra el saludo sin escribir nada en el navegador. */
    function hide() {
        open.value = false;
    }

    return { open, seen, show, hide };
}
