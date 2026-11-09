import { readonly, ref } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Transición de salida de la portada navideña.
 *
 * Cubre la pantalla con una nevada densa, navega a la vista de destino y,
 * cuando Inertia termina de pintarla, deja caer la nieve para revelarla.
 *
 * El estado vive a nivel de módulo porque la cortina se monta una sola vez en
 * `app.js` (fuera de la página) y así sobrevive a la navegación de Inertia.
 */
export const SNOW_COVER_MS = 1100;
export const SNOW_HOLD_MS = 180;
export const SNOW_REVEAL_MS = 1000;

const state = ref('idle');

/** `idle | cover | reveal`. Sólo lectura para la cortina. */
export const snowState = readonly(state);

export const snowTiming = {
    '--sn-cover': `${SNOW_COVER_MS}ms`,
    '--sn-reveal': `${SNOW_REVEAL_MS}ms`,
};

let running = null;

const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * Ejecuta la transición y llama a `navigate` cuando la nieve ya cubre la
 * pantalla. Devuelve una promesa que se resuelve al terminar la revelación.
 *
 * @param {() => void} navigate
 * @param {string} fallbackUrl  Destino de emergencia si Inertia no responde.
 */
export function playSnowTransition(navigate, fallbackUrl = '/inicio') {
    if (running) {
        return running;
    }

    if (prefersReducedMotion()) {
        navigate();
        return Promise.resolve();
    }

    state.value = 'cover';

    running = new Promise((resolve) => {
        let navigated = false;
        let revealing = false;
        let revealedOnce = false;
        let stopFinish = null;
        const timers = [];
        const later = (fn, ms) => timers.push(window.setTimeout(fn, ms));

        const finish = () => {
            timers.forEach((id) => window.clearTimeout(id));
            timers.length = 0;

            if (typeof stopFinish === 'function') {
                stopFinish();
            }

            stopFinish = null;
            running = null;
            resolve();
        };

        const beginReveal = () => {
            if (revealedOnce) {
                return;
            }

            revealedOnce = true;

            later(() => {
                state.value = 'reveal';
                later(() => {
                    state.value = 'idle';
                    finish();
                }, SNOW_REVEAL_MS);
            }, SNOW_HOLD_MS);
        };

        const reveal = () => {
            if (!navigated || revealing) {
                return;
            }

            revealing = true;

            // Dos frames: la nueva vista ya está pintada antes de descubrirla.
            // El temporizador es la red de seguridad: si la pestaña está en
            // segundo plano, el navegador pausa requestAnimationFrame.
            requestAnimationFrame(() => requestAnimationFrame(beginReveal));
            later(beginReveal, 350);
        };

        stopFinish = router.on('finish', reveal);

        later(() => {
            navigated = true;
            navigate();

            // Red de seguridad: si la visita no termina, forzamos una carga
            // completa para no dejar la cortina bloqueando la pantalla.
            later(() => {
                if (!revealing) {
                    window.location.assign(fallbackUrl);
                }
            }, 6000);
        }, SNOW_COVER_MS);
    });

    return running;
}
