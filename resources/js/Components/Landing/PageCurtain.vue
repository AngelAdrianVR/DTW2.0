<script setup>
/**
 * Cortina global de transición entre vistas.
 *
 * Se monta una sola vez (en app.js) fuera del componente de página, así que
 * sobrevive a cada navegación:
 *
 *  1. Al iniciar la visita, la cortina sube desde abajo y cubre la vista
 *     actual  → slide up para salir.
 *  2. Cuando la nueva vista está lista, la cortina baja y la revela
 *     → slide down para entrar.
 *
 * Con `prefers-reduced-motion` no se anima nada (la navegación es inmediata).
 *
 * La cortina es parte del lenguaje visual del sitio público: sólo se anima
 * cuando la vista de destino pertenece a la landing (ver `routes/web.php`). En
 * el back-office las vistas cambian de inmediato, sin cortina.
 */
import { onBeforeUnmount, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { cempasuchilState } from './cempasuchilTransition';

/* Las duraciones se inyectan como variables CSS en el elemento para que la
   animación (app.css) y los temporizadores de JS usen exactamente el mismo
   valor. */
const COVER_MS = 640;
const REVEAL_MS = 900;
const MIN_COVER_MS = 700;

/**
 * Rutas públicas de la landing. Se comparan por ruta (no por nombre de ruta)
 * porque en el evento `start` de Inertia todavía no existe la página de
 * destino.
 */
const LANDING_PATHS = [
    /^\/$/, // portada temática
    /^\/inicio$/, // landing principal
    /^\/proyectos(\/|$)/, // galería de proyectos
    /^\/soluciones(\/|$)/, // enlace antiguo (redirige a /proyectos)
    /^\/landing-projects(\/|$)/, // detalle de un proyecto
    /^\/eventos(\/|$)/, // eventos y ficha de cada evento
    /^\/terms-of-service$/, // documentos legales (misma plantilla pública)
    /^\/privacy-policy$/,
];

/** Normaliza la ruta para que `/proyectos/` y `/proyectos` sean iguales. */
function normalizePath(path) {
    return path.replace(/\/+$/, '') || '/';
}

/**
 * Ruta a la que apunta la visita, tomada del evento de Inertia. Si faltara se
 * usa la actual, que es el mejor aproximado disponible.
 */
function targetPath(visit) {
    const raw = visit?.url;
    const fallback = typeof window !== 'undefined' ? window.location.pathname : '/';

    if (!raw) {
        return normalizePath(fallback);
    }

    try {
        return normalizePath(new URL(String(raw), window.location.origin).pathname);
    } catch {
        return normalizePath(fallback);
    }
}

/** True si la ruta pertenece al sitio público. */
function isLandingPath(path) {
    return LANDING_PATHS.some((pattern) => pattern.test(path));
}

const state = ref('idle');
let startedAt = 0;
let timers = [];
let frame = null;

const prefersReducedMotion = () =>
    typeof window !== 'undefined' &&
    typeof window.matchMedia === 'function' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function clearTimers() {
    timers.forEach((id) => window.clearTimeout(id));
    timers = [];

    if (frame !== null) {
        cancelAnimationFrame(frame);
        frame = null;
    }
}

function reset() {
    clearTimers();
    state.value = 'idle';
}

function onStart(event) {
    if (prefersReducedMotion()) {
        return;
    }

    // La salida de la portada temática la cubre la cortina de cempasúchil.
    if (cempasuchilState.value !== 'idle') {
        reset();
        return;
    }

    // Fuera de la landing (back-office, autenticación, TPSP…) no hay cortina.
    if (!isLandingPath(targetPath(event?.detail?.visit))) {
        reset();
        return;
    }

    clearTimers();
    startedAt = performance.now();
    state.value = 'out';
}

function onFinish() {
    // Si la navegación no arrancó cortina (destino fuera de la landing) no hay
    // nada que revelar.
    if (state.value === 'idle' || prefersReducedMotion()) {
        reset();
        return;
    }

    const elapsed = performance.now() - startedAt;
    const wait = Math.max(0, MIN_COVER_MS - elapsed);

    timers.push(
        window.setTimeout(() => {
            // Dos frames: nos aseguramos de que la nueva vista ya está pintada
            // antes de bajar la cortina.
            frame = requestAnimationFrame(() => {
                frame = requestAnimationFrame(() => {
                    frame = null;
                    state.value = 'in';

                    timers.push(window.setTimeout(() => { state.value = 'idle'; }, REVEAL_MS));
                });
            });
        }, wait),
    );
}

const stopStart = router.on('start', onStart);
const stopFinish = router.on('finish', onFinish);
const stopError = router.on('error', reset);
const stopException = router.on('exception', reset);

onBeforeUnmount(() => {
    [stopStart, stopFinish, stopError, stopException].forEach((stop) => {
        if (typeof stop === 'function') {
            stop();
        }
    });

    reset();
});

// Exportado solo para pruebas manuales en consola.
defineExpose({ COVER_MS, REVEAL_MS });

/* Duraciones para la animación de app.css (deben coincidir con los valores de
   respaldo declarados allí). */
const curtainTiming = {
    '--dtw-curtain-cover': `${COVER_MS}ms`,
    '--dtw-curtain-reveal': `${REVEAL_MS}ms`,
};
</script>

<template>
    <div
        class="dtw-curtain fixed inset-0 z-[150] flex items-center justify-center overflow-hidden bg-dtw-void"
        :data-state="state"
        :style="curtainTiming"
        aria-hidden="true"
    >
        <div class="pointer-events-none absolute inset-0 bg-dtw-halo opacity-60"></div>
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-dtw-primary/80 to-transparent"
        ></div>

        <div class="relative flex flex-col items-center gap-6">
            <img src="/images/white_logo.png" alt="" class="h-9 w-auto opacity-90" />
            <div class="h-px w-40 overflow-hidden bg-white/10">
                <div
                    class="dtw-curtain__line h-full w-full bg-gradient-to-r from-dtw-primary via-dtw-cyan to-dtw-primary"
                ></div>
            </div>
        </div>
    </div>
</template>
