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
 */
import { onBeforeUnmount, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const COVER_MS = 420;
const REVEAL_MS = 560;
const MIN_COVER_MS = 440;

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

function onStart() {
    if (prefersReducedMotion()) {
        return;
    }

    clearTimers();
    startedAt = performance.now();
    state.value = 'out';
}

function onFinish() {
    if (prefersReducedMotion()) {
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
</script>

<template>
    <div
        class="dtw-curtain fixed inset-0 z-[150] flex items-center justify-center overflow-hidden bg-dtw-void"
        :data-state="state"
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
