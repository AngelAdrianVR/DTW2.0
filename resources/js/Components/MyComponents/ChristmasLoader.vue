<template>
    <!--
        ============================================================
        PANTALLA DE CARGA TEMÁTICA (Navidad) — DTW
        ============================================================
        Escena en capas (z-index dentro de .cx-scene):
          1) .cx-bg                → escena navideña estática (backgroundSrc)
          2) .cx-scrim             → oscurecido inferior para el texto
          3) .cx-star / .cx-bokeh  → estrellas y luces desenfocadas
          4) .cx-wreath-glow       → brillo cálido sobre la corona
          5) .cx-snow              → nieve cayendo en tres profundidades
          6) .cx-content           → felicitación, progreso y estado

        Rendimiento: todo lo animado usa `transform` y `opacity` (GPU); la
        imagen de fondo es estática y la nieve va en capas de distinta
        velocidad para dar sensación de profundidad.

        Contrato:
          - `duration` indica cuánto tarda en emitir `finished`.
          - El padre decide cuándo desmontar el componente.
    -->
    <Teleport to="body">
        <div
            v-if="isVisible"
            class="cx-loader"
            :style="{ '--cx-duration': `${duration}ms` }"
            role="status"
            aria-live="polite"
            :aria-label="status"
        >
            <!-- ==================== ESCENARIO ==================== -->
            <div class="cx-scene" aria-hidden="true">
                <div class="cx-bg-fallback"></div>
                <img
                    v-if="!backgroundFailed"
                    class="cx-bg"
                    :class="{ 'is-loaded': backgroundLoaded }"
                    :src="backgroundSrc"
                    alt=""
                    decoding="async"
                    fetchpriority="high"
                    @load="backgroundLoaded = true"
                    @error="backgroundFailed = true"
                />
                <div class="cx-scrim"></div>

                <!-- Estrellas titilando en el cielo -->
                <span
                    v-for="(star, index) in stars"
                    :key="`star-${index}`"
                    class="cx-star"
                    :style="{
                        '--cx-star-left': star.left,
                        '--cx-star-top': star.top,
                        '--cx-star-size': star.size,
                        '--cx-star-duration': star.duration,
                        '--cx-star-delay': star.delay,
                    }"
                ></span>

                <!-- Luces desenfocadas (bokeh) -->
                <span
                    v-for="(bokeh, index) in bokeh"
                    :key="`bokeh-${index}`"
                    class="cx-bokeh"
                    :style="{
                        '--cx-bokeh-left': bokeh.left,
                        '--cx-bokeh-top': bokeh.top,
                        '--cx-bokeh-size': bokeh.size,
                        '--cx-bokeh-duration': bokeh.duration,
                        '--cx-bokeh-delay': bokeh.delay,
                        '--cx-bokeh-opacity': bokeh.opacity,
                    }"
                ></span>

                <!-- Brillo cálido de la corona -->
                <div class="cx-wreath-glow"></div>

                <!-- Nieve cayendo -->
                <div class="cx-snow">
                    <template v-for="flake in flakes" :key="`flake-${flake.key}`">
                        <svg
                            v-if="flake.crystal"
                            class="cx-snow__flake cx-snow__flake--crystal"
                            viewBox="0 0 24 24"
                            :style="flakeStyle(flake)"
                        >
                            <path
                                d="M12 2v20M3.3 6.5l17.4 11M20.7 6.5L3.3 17.5M9 4.2 12 7l3-2.8M9 19.8 12 17l3 2.8M3.6 11.2l4.1.6-.6-4.1M20.4 12.8l-4.1-.6.6 4.1M20.4 11.2l-4.1.6.6-4.1M3.6 12.8l4.1-.6-.6 4.1"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linecap="round"
                            />
                        </svg>
                        <span
                            v-else
                            class="cx-snow__flake cx-snow__flake--dot"
                            :style="flakeStyle(flake)"
                        ></span>
                    </template>
                </div>

                <div class="cx-vignette"></div>
            </div>

            <!-- ==================== CONTENIDO ==================== -->
            <div class="cx-content">
                <p class="cx-kicker">Felices fiestas</p>
                <h1 class="cx-title">Navidad</h1>

                <div class="cx-progress" aria-hidden="true">
                    <span class="cx-progress__bar"></span>
                </div>

                <p class="cx-status">
                    {{ status }}
                    <span class="cx-dots" aria-hidden="true"><i>.</i><i>.</i><i>.</i></span>
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** Tiempo visible antes de emitir `finished` (4 s por defecto). */
    duration: { type: Number, default: 4000 },
    /** Escena navideña de fondo. */
    backgroundSrc: { type: String, default: '/images/christmas-bg.webp' },
    /** Texto inferior de estado. */
    status: { type: String, default: 'Preparando la magia navideña' },
});

const emit = defineEmits(['finished']);

const isVisible = ref(true);
const backgroundLoaded = ref(false);
const backgroundFailed = ref(false);

/* Reparto determinista: la nieve es igual en cada carga, sin Math.random. */
const flakes = Array.from({ length: 66 }, (_, index) => {
    const depth = index % 3; // 0 = lejos, 1 = medio, 2 = cerca
    const crystal = index % 11 === 3;
    const baseSize = crystal ? 11 + (index % 4) * 4 : 1.8 + (index % 5) * 0.9 + depth * 0.7;
    const baseDuration = depth === 2 ? 8 : depth === 1 ? 12 : 17;

    return {
        key: index,
        crystal,
        left: `${((index * 61.803) % 100).toFixed(2)}%`,
        size: `${baseSize.toFixed(1)}px`,
        duration: `${baseDuration + (index % 5)}s`,
        delay: `-${((index * 1.37) % 19).toFixed(2)}s`,
        drift: `${(((index * 13) % 13) - 6).toFixed(1)}vw`,
        opacity: (0.3 + ((index * 7) % 60) / 100).toFixed(2),
        blur: depth === 0 ? '1.3px' : depth === 1 ? '0.45px' : '0',
    };
});

const stars = Array.from({ length: 24 }, (_, index) => ({
    left: `${((index * 47.3 + 3) % 96).toFixed(2)}%`,
    top: `${((index * 29.7 + 4) % 62).toFixed(2)}%`,
    size: `${(1 + (index % 3) * 0.7).toFixed(1)}px`,
    duration: `${(2.4 + (index % 5) * 0.8).toFixed(1)}s`,
    delay: `-${((index * 0.7) % 5).toFixed(1)}s`,
}));

const bokeh = Array.from({ length: 7 }, (_, index) => ({
    left: `${(index * 17 + 4) % 94}%`,
    top: `${(index * 23 + 6) % 78}%`,
    size: `${40 + (index % 4) * 30}px`,
    duration: `${12 + (index % 5) * 3}s`,
    delay: `-${(index * 2.3).toFixed(1)}s`,
    opacity: (0.06 + (index % 3) * 0.02).toFixed(2),
}));

const flakeStyle = (flake) => ({
    '--cx-flake-left': flake.left,
    '--cx-flake-size': flake.size,
    '--cx-flake-duration': flake.duration,
    '--cx-flake-delay': flake.delay,
    '--cx-flake-drift': flake.drift,
    '--cx-flake-opacity': flake.opacity,
    '--cx-flake-blur': flake.blur,
});

const timers = [];
let previousBodyOverflow = '';

const lockScroll = () => {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
};

const unlockScroll = () => {
    document.body.style.overflow = previousBodyOverflow;
};

onMounted(() => {
    lockScroll();
    const id = setTimeout(() => emit('finished'), props.duration);
    timers.push(id);
});

onBeforeUnmount(() => {
    timers.forEach(clearTimeout);
    timers.length = 0;
    unlockScroll();
});
</script>

<style scoped>
/* ============================================================
   1. CONTENEDOR Y ESCENARIO
   ============================================================ */
.cx-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    overflow: hidden;
    background: #071528;
    font-family: 'Manrope', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    -webkit-tap-highlight-color: transparent;
}

.cx-scene {
    position: absolute;
    inset: 0;
    overflow: hidden;
    isolation: isolate;
}

.cx-bg-fallback {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(90% 60% at 50% 90%, rgba(120, 170, 220, 0.18) 0%, transparent 70%),
        radial-gradient(120% 80% at 50% 0%, #12345a 0%, #071528 100%);
}

.cx-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: 50% 44%;
    opacity: 0;
    transition: opacity 0.9s ease;
}

.cx-bg.is-loaded {
    opacity: 1;
}

/* Degradado inferior para leer la felicitación sin ensuciar la escena. */
.cx-scrim {
    position: absolute;
    inset: 0;
    z-index: 2;
    background:
        linear-gradient(to bottom, rgba(4, 12, 28, 0.34) 0%, transparent 26%, transparent 58%, rgba(4, 12, 28, 0.72) 100%),
        radial-gradient(72% 62% at 50% 44%, transparent 52%, rgba(4, 12, 28, 0.28) 100%);
}

.cx-vignette {
    position: absolute;
    inset: 0;
    z-index: 8;
    background: radial-gradient(ellipse at 50% 42%, transparent 44%, rgba(3, 9, 21, 0.38) 78%, rgba(3, 9, 21, 0.82) 100%);
}

/* ============================================================
   2. ESTRELLAS, BOKEH Y BRILLO DE LA CORONA
   ============================================================ */
.cx-star {
    position: absolute;
    top: var(--cx-star-top, 40%);
    left: var(--cx-star-left, 50%);
    z-index: 3;
    width: var(--cx-star-size, 2px);
    height: var(--cx-star-size, 2px);
    border-radius: 50%;
    background: #ffffff;
    box-shadow: 0 0 6px rgba(255, 255, 255, 0.85);
    opacity: 0.2;
    animation: cxTwinkle var(--cx-star-duration, 3s) ease-in-out var(--cx-star-delay, 0s) infinite;
    will-change: transform, opacity;
}

.cx-bokeh {
    position: absolute;
    top: var(--cx-bokeh-top, 40%);
    left: var(--cx-bokeh-left, 50%);
    z-index: 3;
    width: var(--cx-bokeh-size, 70px);
    height: var(--cx-bokeh-size, 70px);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 246, 214, 0.55) 0%, rgba(190, 220, 255, 0.16) 45%, transparent 70%);
    filter: blur(9px);
    opacity: var(--cx-bokeh-opacity, 0.08);
    animation: cxBokehFloat var(--cx-bokeh-duration, 15s) ease-in-out var(--cx-bokeh-delay, 0s) infinite;
    will-change: transform;
}

.cx-wreath-glow {
    position: absolute;
    top: 43%;
    left: 50%;
    z-index: 4;
    width: min(72vmin, 760px);
    aspect-ratio: 1;
    transform: translate(-50%, -50%);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 214, 132, 0.3) 0%, rgba(255, 186, 76, 0.12) 38%, transparent 68%);
    mix-blend-mode: screen;
    filter: blur(3px);
    animation: cxGlowPulse 6.5s ease-in-out infinite;
    pointer-events: none;
    will-change: transform, opacity;
}

/* ============================================================
   3. NIEVE
   ============================================================ */
.cx-snow {
    position: absolute;
    inset: 0;
    z-index: 5;
    pointer-events: none;
}

.cx-snow__flake {
    position: absolute;
    top: -12vh;
    left: var(--cx-flake-left, 50%);
    width: var(--cx-flake-size, 3px);
    height: var(--cx-flake-size, 3px);
    opacity: 0;
    filter: blur(var(--cx-flake-blur, 0));
    will-change: transform, opacity;
    animation: cxSnowFall var(--cx-flake-duration, 14s) linear var(--cx-flake-delay, 0s) infinite;
}

.cx-snow__flake--dot {
    border-radius: 50%;
    background: radial-gradient(circle, #ffffff 0%, rgba(255, 255, 255, 0.92) 48%, rgba(255, 255, 255, 0) 74%);
    box-shadow: 0 0 7px rgba(220, 238, 255, 0.45);
}

.cx-snow__flake--crystal {
    color: rgba(255, 255, 255, 0.88);
    filter: drop-shadow(0 0 4px rgba(215, 236, 255, 0.55)) blur(var(--cx-flake-blur, 0));
}

/* ============================================================
   4. CONTENIDO
   ============================================================ */
.cx-content {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    gap: clamp(8px, 1.5vh, 18px);
    padding: 0 clamp(16px, 5vw, 48px) clamp(28px, 6vh, 64px);
    text-align: center;
}

.cx-content::before {
    content: '';
    position: absolute;
    inset: auto 0 0 0;
    z-index: -1;
    height: 52%;
    background: linear-gradient(to top, rgba(3, 9, 22, 0.9) 0%, rgba(3, 9, 22, 0.5) 42%, transparent 100%);
    pointer-events: none;
}

.cx-kicker {
    margin: 0;
    font-size: clamp(9px, 1.35vmin, 12px);
    font-weight: 600;
    letter-spacing: 0.46em;
    text-transform: uppercase;
    color: rgba(255, 220, 150, 0.9);
    animation: cxRise 0.8s 0.15s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.cx-title {
    margin: 0;
    font-size: clamp(24px, 4.8vmin, 44px);
    font-weight: 300;
    letter-spacing: 0.24em;
    line-height: 1;
    text-indent: 0.24em;
    background: linear-gradient(100deg, #ffffff 0%, #ffe9b8 52%, #f7c873 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 6px 20px rgba(255, 205, 120, 0.28));
    animation: cxRise 0.9s 0.26s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.cx-progress {
    position: relative;
    width: clamp(180px, 44vw, 320px);
    height: 3px;
    margin-top: clamp(2px, 1vh, 10px);
    border-radius: 999px;
    background: rgba(255, 244, 220, 0.16);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
    overflow: hidden;
    animation: cxRise 0.8s 0.5s both;
}

.cx-progress__bar {
    position: absolute;
    inset: 0 auto 0 0;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #f7c873, #fff6de 52%, #ffd88a);
    box-shadow: 0 0 12px rgba(247, 200, 115, 0.5), 0 0 24px rgba(255, 244, 214, 0.3);
    animation: cxProgressFill var(--cx-duration, 4000ms) linear forwards;
}

.cx-status {
    margin: 0;
    font-size: clamp(10px, 1.5vmin, 13px);
    font-weight: 600;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(234, 244, 255, 0.72);
    animation: cxRise 0.8s 0.58s both;
}

.cx-dots i {
    display: inline-block;
    font-style: normal;
    animation: cxDotPulse 1.6s ease-in-out infinite;
}

.cx-dots i:nth-child(2) {
    animation-delay: 0.2s;
}

.cx-dots i:nth-child(3) {
    animation-delay: 0.4s;
}

/* ============================================================
   5. ANIMACIONES
   ============================================================ */
@keyframes cxSnowFall {
    0% {
        transform: translate3d(0, -12vh, 0);
        opacity: 0;
    }
    8% {
        opacity: var(--cx-flake-opacity, 0.7);
    }
    50% {
        transform: translate3d(calc(var(--cx-flake-drift, 3vw) * 0.45), 52vh, 0);
        opacity: var(--cx-flake-opacity, 0.7);
    }
    88% {
        opacity: var(--cx-flake-opacity, 0.7);
    }
    100% {
        transform: translate3d(var(--cx-flake-drift, 3vw), 114vh, 0);
        opacity: 0;
    }
}

@keyframes cxTwinkle {
    0%,
    100% {
        opacity: 0.15;
        transform: scale(0.8);
    }
    50% {
        opacity: 0.95;
        transform: scale(1.15);
    }
}

@keyframes cxBokehFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(2vw, -2.5vh, 0);
    }
}

@keyframes cxGlowPulse {
    0%,
    100% {
        opacity: 0.45;
        transform: translate(-50%, -50%) scale(0.98);
    }
    50% {
        opacity: 0.82;
        transform: translate(-50%, -50%) scale(1.05);
    }
}

@keyframes cxRise {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes cxProgressFill {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

@keyframes cxDotPulse {
    0%,
    100% {
        opacity: 0.25;
    }
    50% {
        opacity: 1;
    }
}

/* ============================================================
   6. RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .cx-snow__flake:nth-child(2n) {
        display: none;
    }

    .cx-content {
        padding-bottom: clamp(26px, 5vh, 48px);
    }

    .cx-title {
        letter-spacing: 0.18em;
        text-indent: 0.18em;
    }
}

@media (max-height: 560px) and (orientation: landscape) {
    .cx-content {
        gap: 6px;
        padding-bottom: 14px;
    }

    .cx-title {
        font-size: clamp(20px, 3.8vmin, 30px);
    }

    .cx-progress {
        width: clamp(160px, 36vw, 260px);
    }

    .cx-bokeh {
        display: none;
    }
}

/* Accesibilidad: respeta la preferencia de movimiento reducido. */
@media (prefers-reduced-motion: reduce) {
    .cx-star,
    .cx-bokeh,
    .cx-wreath-glow,
    .cx-snow__flake,
    .cx-progress__bar,
    .cx-dots i {
        animation: none !important;
    }

    .cx-snow__flake,
    .cx-bokeh {
        display: none;
    }

    .cx-progress__bar {
        width: 100%;
    }
}
</style>
