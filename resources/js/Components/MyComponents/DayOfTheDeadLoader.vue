<template>
    <!--
        ============================================================
        PANTALLA DE CARGA TEMÁTICA (Día de Muertos) — DTW
        ============================================================
        Escena en capas (z-index dentro de .dm-scene):
          1) .dm-bg                → imagen de fondo (reemplazable con backgroundSrc)
          2) .dm-scrim             → oscurecido radial para dar legibilidad al logo
          3) .dm-petal / .dm-light → pétalos de cempasúchil y luces de ánimas
          4) .dm-picado            → papel picado meciéndose en las esquinas
          5) .dm-butterfly         → monarcas aleteando
          6) .dm-content           → corona de cempasúchil, logotipo y progreso

        Rendimiento: las animaciones sólo usan `transform` y `opacity`
        (se ejecutan en la GPU), la imagen y la corona son estáticas y no
        hay filtros animados ni capas con blur en movimiento.

        Contrato:
          - `duration` indica cuánto tarda en emitir `finished`.
          - El padre decide cuándo desmontar el componente (por ejemplo,
            para cubrir la salida con la transición de flores).
    -->
    <Teleport to="body">
        <div
            v-if="isVisible"
            class="dm-loader"
            :style="{ '--dm-duration': `${duration}ms` }"
            role="status"
            aria-live="polite"
            :aria-label="status"
        >
            <!-- ==================== ESCENARIO ==================== -->
            <div class="dm-scene" aria-hidden="true">
                <div class="dm-bg-fallback"></div>
                <img
                    v-if="!backgroundFailed"
                    class="dm-bg"
                    :class="{ 'is-loaded': backgroundLoaded }"
                    :src="backgroundSrc"
                    alt=""
                    decoding="async"
                    fetchpriority="high"
                    @load="backgroundLoaded = true"
                    @error="backgroundFailed = true"
                />

                <div class="dm-scrim"></div>

                <!-- Pétalos de cempasúchil cayendo -->
                <span
                    v-for="(petal, index) in petals"
                    :key="`petal-${index}`"
                    class="dm-petal"
                    :style="{
                        '--dm-petal-left': petal.left,
                        '--dm-petal-size': petal.size,
                        '--dm-petal-duration': petal.duration,
                        '--dm-petal-delay': petal.delay,
                        '--dm-petal-drift': petal.drift,
                        '--dm-petal-spin': petal.spin,
                        '--dm-petal-opacity': petal.opacity ?? 0.85,
                    }"
                ></span>

                <!-- Luces de las ánimas -->
                <span
                    v-for="(light, index) in lights"
                    :key="`light-${index}`"
                    class="dm-light"
                    :style="{
                        '--dm-light-left': light.left,
                        '--dm-light-top': light.top,
                        '--dm-light-size': light.size,
                        '--dm-light-duration': light.duration,
                        '--dm-light-delay': light.delay,
                        '--dm-light-drift': light.drift,
                    }"
                ></span>

                <!-- Papel picado -->
                <div class="dm-picado dm-picado--left">
                    <svg viewBox="0 0 380 130" fill="none">
                        <path
                            class="dm-picado__string"
                            d="M0 6Q190 44 380 6"
                        />
                        <path
                            v-for="(flag, index) in picadoPositions"
                            :key="`flag-left-${index}`"
                            :d="flagShapes[index % flagShapes.length]"
                            fill-rule="evenodd"
                            :fill="picadoColorsLeft[index]"
                            :transform="`translate(${flag.x} ${flag.y}) rotate(${flag.rot})`"
                        />
                    </svg>
                </div>
                <div class="dm-picado dm-picado--right">
                    <svg viewBox="0 0 380 130" fill="none">
                        <path
                            class="dm-picado__string"
                            d="M0 6Q190 44 380 6"
                        />
                        <path
                            v-for="(flag, index) in picadoPositions"
                            :key="`flag-right-${index}`"
                            :d="flagShapes[index % flagShapes.length]"
                            fill-rule="evenodd"
                            :fill="picadoColorsRight[index]"
                            :transform="`translate(${flag.x} ${flag.y}) rotate(${flag.rot})`"
                        />
                    </svg>
                </div>

                <!-- Mariposas monarca -->
                <div class="dm-butterfly dm-butterfly--one">
                    <svg viewBox="0 0 90 70">
                        <g class="dm-butterfly__wing dm-butterfly__wing--left">
                            <path d="M44 36C30 10 8 6 6 22c-2 14 16 20 34 20z" fill="#ff7f11" />
                            <path d="M44 40C30 56 10 62 10 48c0-12 18-14 34-10z" fill="#ffb703" />
                        </g>
                        <g class="dm-butterfly__wing dm-butterfly__wing--right">
                            <path d="M46 36C60 10 82 6 84 22c2 14-16 20-34 20z" fill="#ff7f11" />
                            <path d="M46 40C60 56 80 62 80 48c0-12-18-14-34-10z" fill="#ffb703" />
                        </g>
                        <ellipse cx="45" cy="38" rx="2.6" ry="12" fill="#2b1610" />
                        <path
                            d="M45 27c-3-6-7-9-11-10M45 27c3-6 7-9 11-10"
                            stroke="#2b1610"
                            stroke-width="1.6"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>
                <div class="dm-butterfly dm-butterfly--two">
                    <svg viewBox="0 0 90 70">
                        <g class="dm-butterfly__wing dm-butterfly__wing--left">
                            <path d="M44 36C30 10 8 6 6 22c-2 14 16 20 34 20z" fill="#ff9e00" />
                            <path d="M44 40C30 56 10 62 10 48c0-12 18-14 34-10z" fill="#ffd166" />
                        </g>
                        <g class="dm-butterfly__wing dm-butterfly__wing--right">
                            <path d="M46 36C60 10 82 6 84 22c2 14-16 20-34 20z" fill="#ff9e00" />
                            <path d="M46 40C60 56 80 62 80 48c0-12-18-14-34-10z" fill="#ffd166" />
                        </g>
                        <ellipse cx="45" cy="38" rx="2.6" ry="12" fill="#2b1610" />
                        <path
                            d="M45 27c-3-6-7-9-11-10M45 27c3-6 7-9 11-10"
                            stroke="#2b1610"
                            stroke-width="1.6"
                            stroke-linecap="round"
                        />
                    </svg>
                </div>

                <div class="dm-vignette"></div>
            </div>

            <!-- ==================== CONTENIDO ==================== -->
            <div class="dm-content">
                <div class="dm-emblem">
                    <!-- Corona de cempasúchil alrededor del logotipo -->
                    <svg class="dm-wreath" viewBox="0 0 440 440" aria-hidden="true">
                        <defs>
                            <radialGradient id="dmPetalGradient" cx="50%" cy="28%" r="76%">
                                <stop offset="0%" stop-color="#ffe9a8" />
                                <stop offset="46%" stop-color="#ffbe0b" />
                                <stop offset="100%" stop-color="#f77f00" />
                            </radialGradient>
                            <radialGradient id="dmCoreGradient">
                                <stop offset="0%" stop-color="#fff6cf" />
                                <stop offset="100%" stop-color="#f08c00" />
                            </radialGradient>
                            <g id="dmMarigold">
                                <g fill="url(#dmPetalGradient)">
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(45)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(90)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(135)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(180)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(225)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(270)" />
                                    <ellipse cx="0" cy="-13" rx="6.6" ry="12" transform="rotate(315)" />
                                </g>
                                <circle r="7" fill="url(#dmCoreGradient)" />
                                <circle r="3.2" fill="#d96600" opacity="0.75" />
                            </g>
                            <g id="dmBud">
                                <circle r="4.5" fill="#2f6b3c" />
                                <circle cx="-6" cy="-4" r="3.2" fill="#3f8a4f" />
                                <circle cx="6" cy="-4" r="3.2" fill="#3f8a4f" />
                            </g>
                        </defs>

                        <circle cx="220" cy="220" r="198" stroke="#2f6b3c" stroke-width="2" opacity="0.35" />

                        <use
                            v-for="(angle, index) in wreathAngles"
                            :key="`bud-${index}`"
                            href="#dmBud"
                            :transform="`rotate(${angle + 13} 220 220) translate(220 24)`"
                        />
                        <use
                            v-for="(angle, index) in wreathAngles"
                            :key="`flower-${index}`"
                            href="#dmMarigold"
                            :transform="`rotate(${angle} 220 220) translate(220 26)`"
                        />
                    </svg>

                    <img class="dm-logo" :src="logoSrc" alt="DTW — Desarrollo de Tecnologías Web" />

                    <!-- Calavera de azúcar como dije de la corona -->
                    <svg class="dm-skull" viewBox="0 0 80 96" aria-hidden="true">
                        <path d="M4 52h72" stroke="#f6ecd9" stroke-width="3" stroke-linecap="round" opacity="0.85" />
                        <path d="M40 10C20 10 9 24 9 43c0 13 6 21 13 26v12c0 5 4 8 9 8h18c5 0 9-3 9-8V69c7-5 13-13 13-26C71 24 60 10 40 10z" fill="#f6ecd9" />
                        <path d="M19 43c5-4 10 0 10 6s-5 8-9 6c-4-2-5-8-1-12z" fill="#2a0f3d" />
                        <path d="M61 43c-5-4-10 0-10 6s5 8 9 6c4-2 5-8 1-12z" fill="#2a0f3d" />
                        <path d="M40 55l-6 9h12z" fill="#2a0f3d" />
                        <path d="M30 73h20" stroke="#2a0f3d" stroke-width="2.4" stroke-linecap="round" />
                        <path d="M34 73v6M40 73v6M46 73v6" stroke="#2a0f3d" stroke-width="2" stroke-linecap="round" />
                        <circle cx="19" cy="43" r="2" fill="#ff9e00" />
                        <circle cx="61" cy="43" r="2" fill="#ff9e00" />
                        <circle cx="40" cy="30" r="3.4" fill="#ff6b8a" />
                    </svg>

                    <!-- Veladoras -->
                    <svg class="dm-candle dm-candle--left" viewBox="0 0 60 150" aria-hidden="true">
                        <ellipse cx="30" cy="142" rx="24" ry="6" fill="#000" opacity="0.35" />
                        <rect x="18" y="48" width="24" height="92" rx="6" fill="#f7ecd4" />
                        <rect x="18" y="48" width="24" height="10" rx="5" fill="#ffe9b8" />
                        <rect x="22" y="72" width="16" height="4" rx="2" fill="#e7d3ab" />
                        <rect x="22" y="94" width="16" height="4" rx="2" fill="#e7d3ab" />
                        <path class="dm-flame dm-flame--outer" d="M30 10c10 12 13 22 6 32-3 4-8 6-6 6 2 0-3-2-6-6-7-10-4-20 6-32z" fill="#ff9e00" />
                        <path class="dm-flame dm-flame--inner" d="M30 20c6 8 7 14 3 20-2 3-4 3-6 0-4-6-3-12 3-20z" fill="#ffe066" />
                    </svg>
                    <svg class="dm-candle dm-candle--right" viewBox="0 0 60 150" aria-hidden="true">
                        <ellipse cx="30" cy="142" rx="24" ry="6" fill="#000" opacity="0.35" />
                        <rect x="18" y="56" width="24" height="84" rx="6" fill="#f7ecd4" />
                        <rect x="18" y="56" width="24" height="10" rx="5" fill="#ffe9b8" />
                        <rect x="22" y="80" width="16" height="4" rx="2" fill="#e7d3ab" />
                        <rect x="22" y="102" width="16" height="4" rx="2" fill="#e7d3ab" />
                        <path class="dm-flame dm-flame--outer" d="M30 18c10 12 13 22 6 32-3 4-8 6-6 6 2 0-3-2-6-6-7-10-4-20 6-32z" fill="#ff9e00" />
                        <path class="dm-flame dm-flame--inner" d="M30 28c6 8 7 14 3 20-2 3-4 3-6 0-4-6-3-12 3-20z" fill="#ffe066" />
                    </svg>
                </div>

                <p class="dm-kicker">1 · 2 de noviembre</p>
                <h1 class="dm-title">Día de Muertos</h1>

                <div class="dm-progress" aria-hidden="true">
                    <span class="dm-progress__bar"></span>
                </div>

                <p class="dm-status">
                    {{ status }}
                    <span class="dm-dots" aria-hidden="true"><i>.</i><i>.</i><i>.</i></span>
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
    /** Logotipo de la marca. */
    logoSrc: { type: String, default: '/images/white_logo.png' },
    /**
     * Imagen de fondo de la escena. Puede apuntar a cualquier archivo en
     * `public/images`: basta con cambiar esta prop para usar otra fotografía.
     */
    backgroundSrc: { type: String, default: '/images/day-of-the-dead-bg.svg' },
    /** Texto inferior de estado. */
    status: { type: String, default: 'Preparando la ofrenda' },
    /** Pétalos de cempasúchil: posición, tamaño, velocidad, retardo y deriva. */
    petals: {
        type: Array,
        default: () => [
            { left: '7%', size: '13px', duration: '14s', delay: '-2s', drift: '7vw', spin: '420deg', opacity: 0.9 },
            { left: '19%', size: '9px', duration: '18s', delay: '-9s', drift: '-5vw', spin: '-360deg', opacity: 0.7 },
            { left: '33%', size: '15px', duration: '12s', delay: '-5s', drift: '9vw', spin: '520deg', opacity: 0.85 },
            { left: '47%', size: '10px', duration: '16s', delay: '-12s', drift: '-7vw', spin: '-440deg', opacity: 0.75 },
            { left: '61%', size: '14px', duration: '13s', delay: '-7s', drift: '6vw', spin: '480deg', opacity: 0.9 },
            { left: '74%', size: '8px', duration: '19s', delay: '-3s', drift: '-6vw', spin: '-380deg', opacity: 0.65 },
            { left: '86%', size: '12px', duration: '15s', delay: '-11s', drift: '5vw', spin: '400deg', opacity: 0.8 },
            { left: '94%', size: '9px', duration: '17s', delay: '-6s', drift: '-4vw', spin: '-420deg', opacity: 0.7 },
        ],
    },
    /** Luces de ánimas flotando en la escena. */
    lights: {
        type: Array,
        default: () => [
            { left: '12%', top: '68%', size: '7px', duration: '9s', delay: '-2s', drift: '4vw' },
            { left: '27%', top: '58%', size: '5px', duration: '12s', delay: '-6s', drift: '-3vw' },
            { left: '68%', top: '64%', size: '6px', duration: '10s', delay: '-4s', drift: '3vw' },
            { left: '84%', top: '56%', size: '8px', duration: '13s', delay: '-8s', drift: '-4vw' },
            { left: '52%', top: '74%', size: '5px', duration: '11s', delay: '-1s', drift: '2vw' },
        ],
    },
});

const emit = defineEmits(['finished']);

const isVisible = ref(true);
const backgroundLoaded = ref(false);
const backgroundFailed = ref(false);

/* 14 flores de cempasúchil + 14 capullos forman la corona del logotipo. */
const wreathAngles = Array.from({ length: 14 }, (_, index) => (index * 360) / 14);

/* Banderines de papel picado: tres plantillas con calados distintos. */
const flagShapes = [
    'M0 0h62v70l-31 16-31-16z M16 20a7 7 0 1 0 0 14a7 7 0 1 0 0-14 M46 20a7 7 0 1 0 0 14a7 7 0 1 0 0-14 M22 45l9 9-9 9-9-9z M40 45l9-9 9 9-9 9z',
    'M0 0h62v70l-31 16-31-16z M31 12l9 15h-18z M31 50l-10 9h20z M11 30h12v12H11z M51 30H39v12h12z',
    'M0 0h62v70l-31 16-31-16z M31 20a10 10 0 1 0 0 20a10 10 0 1 0 0-20 M16 41a6 6 0 1 0 0 12a6 6 0 1 0 0-12 M46 41a6 6 0 1 0 0 12a6 6 0 1 0 0-12',
];

const picadoPositions = [
    { x: 4, y: 10, rot: -4 },
    { x: 82, y: 22, rot: -2 },
    { x: 160, y: 30, rot: 0 },
    { x: 238, y: 22, rot: 2 },
    { x: 316, y: 10, rot: 4 },
];

const picadoColorsLeft = ['#e63975', '#ffb703', '#2ec4b6', '#9b5de5', '#ff6b35'];
const picadoColorsRight = ['#2ec4b6', '#ff6b35', '#e63975', '#ffb703', '#9b5de5'];

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
.dm-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    overflow: hidden;
    background: #0a0412;
    font-family: 'Manrope', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    -webkit-tap-highlight-color: transparent;
}

.dm-scene {
    position: absolute;
    inset: 0;
    overflow: hidden;
    isolation: isolate;
}

.dm-bg-fallback {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(90% 60% at 50% 100%, rgba(255, 138, 0, 0.22) 0%, transparent 70%),
        radial-gradient(120% 80% at 50% 0%, #170a28 0%, #0a0412 100%);
}

.dm-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.dm-bg.is-loaded {
    opacity: 1;
}

/* Oscurecido central: mantiene el logotipo legible sobre la imagen. */
.dm-scrim {
    position: absolute;
    inset: 0;
    z-index: 2;
    background:
        radial-gradient(58% 52% at 50% 47%, rgba(10, 4, 18, 0.62) 0%, rgba(10, 4, 18, 0.26) 58%, transparent 78%),
        linear-gradient(to bottom, rgba(6, 3, 13, 0.35) 0%, transparent 32%, rgba(6, 3, 13, 0.55) 100%);
}

.dm-vignette {
    position: absolute;
    inset: 0;
    z-index: 8;
    background: radial-gradient(ellipse at 50% 42%, transparent 40%, rgba(5, 2, 10, 0.44) 76%, rgba(5, 2, 10, 0.9) 100%);
}

/* ============================================================
   2. PÉTALOS Y LUCES
   ============================================================ */
.dm-petal {
    position: absolute;
    top: -10vh;
    left: var(--dm-petal-left, 50%);
    z-index: 5;
    width: var(--dm-petal-size, 12px);
    height: calc(var(--dm-petal-size, 12px) * 0.62);
    border-radius: 60% 40% 55% 45% / 55% 60% 40% 45%;
    background: radial-gradient(120% 120% at 30% 25%, #ffe9a8 0%, #ffbe0b 46%, #f77f00 100%);
    box-shadow: inset -2px -2px 4px rgba(130, 45, 0, 0.35);
    opacity: 0;
    will-change: transform, opacity;
    animation: dmPetalFall var(--dm-petal-duration, 14s) linear var(--dm-petal-delay, 0s) infinite;
}

.dm-light {
    position: absolute;
    top: var(--dm-light-top, 60%);
    left: var(--dm-light-left, 50%);
    z-index: 5;
    width: var(--dm-light-size, 6px);
    height: var(--dm-light-size, 6px);
    border-radius: 50%;
    background: radial-gradient(circle, #fff3c4 0%, #ffd166 42%, rgba(255, 158, 0, 0) 72%);
    box-shadow: 0 0 12px rgba(255, 209, 102, 0.55);
    opacity: 0;
    will-change: transform, opacity;
    animation: dmLightFloat var(--dm-light-duration, 10s) ease-in-out var(--dm-light-delay, 0s) infinite;
}

/* ============================================================
   3. PAPEL PICADO
   ============================================================ */
.dm-picado {
    position: absolute;
    top: 0;
    z-index: 6;
    width: min(38vw, 520px);
    opacity: 0.92;
    transform-origin: 50% 0;
    animation: dmPicadoSway 7s ease-in-out infinite alternate;
}

.dm-picado svg {
    display: block;
    width: 100%;
    height: auto;
    filter: drop-shadow(0 6px 10px rgba(0, 0, 0, 0.35));
}

.dm-picado--left {
    left: 0;
}

.dm-picado--right {
    right: 0;
    animation-delay: -3.5s;
}

.dm-picado--right svg {
    transform: scaleX(-1);
}

.dm-picado__string {
    stroke: rgba(247, 230, 200, 0.5);
    stroke-width: 2;
}

/* ============================================================
   4. MARIPOSAS
   ============================================================ */
.dm-butterfly {
    position: absolute;
    z-index: 6;
    width: clamp(48px, 8vmin, 82px);
    pointer-events: none;
    filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.35));
    will-change: transform;
}

.dm-butterfly svg {
    display: block;
    width: 100%;
    height: auto;
}

.dm-butterfly--one {
    left: 8%;
    top: 36%;
    animation: dmButterflyFloat 9s ease-in-out infinite;
}

.dm-butterfly--two {
    right: 9%;
    top: 58%;
    width: clamp(38px, 6.5vmin, 66px);
    animation: dmButterflyFloat 11s ease-in-out -4s infinite reverse;
}

.dm-butterfly__wing {
    transform-box: fill-box;
    animation: dmWingFlap 0.7s ease-in-out infinite alternate;
}

.dm-butterfly__wing--left {
    transform-origin: 100% 60%;
}

.dm-butterfly__wing--right {
    transform-origin: 0% 60%;
    animation-delay: -0.1s;
}

/* ============================================================
   5. CONTENIDO
   ============================================================ */
.dm-content {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: clamp(10px, 1.8vh, 20px);
    padding: 6vh clamp(16px, 5vw, 48px) clamp(48px, 12vh, 140px);
    text-align: center;
}

.dm-emblem {
    position: relative;
    display: grid;
    place-items: center;
    width: min(78vw, 52vh, 440px);
    aspect-ratio: 1;
    /* Deja sitio al dije de la calavera, que sobresale por debajo. */
    margin-bottom: clamp(20px, 4vh, 44px);
}

.dm-wreath {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 10px 22px rgba(0, 0, 0, 0.45));
}

.dm-logo {
    position: relative;
    display: block;
    width: 56%;
    height: auto;
    filter: drop-shadow(0 0 14px rgba(255, 177, 60, 0.35)) drop-shadow(0 10px 22px rgba(0, 0, 0, 0.65));
    animation: dmLogoFloat 6s ease-in-out infinite;
}

.dm-skull {
    position: absolute;
    left: 50%;
    bottom: -7%;
    width: clamp(34px, 7vmin, 54px);
    transform: translateX(-50%);
    filter: drop-shadow(0 8px 12px rgba(0, 0, 0, 0.5));
    animation: dmSkullSway 5s ease-in-out infinite;
    transform-origin: 50% 0;
}

.dm-candle {
    position: absolute;
    bottom: -4%;
    width: clamp(26px, 5vmin, 40px);
    filter: drop-shadow(0 10px 14px rgba(0, 0, 0, 0.5));
}

.dm-candle--left {
    left: 1%;
}

.dm-candle--right {
    right: 1%;
}

.dm-flame {
    transform-box: fill-box;
    transform-origin: 50% 100%;
    animation: dmFlameFlicker 2.4s ease-in-out infinite;
}

.dm-flame--inner {
    animation-duration: 1.9s;
    animation-delay: -0.7s;
}

.dm-candle--right .dm-flame {
    animation-delay: -1.1s;
}

.dm-kicker {
    margin: 0;
    font-size: clamp(9px, 1.4vmin, 12px);
    font-weight: 600;
    letter-spacing: 0.42em;
    text-transform: uppercase;
    color: rgba(255, 224, 178, 0.78);
    animation: dmRise 0.8s 0.15s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.dm-title {
    margin: 0;
    font-size: clamp(26px, 5.2vmin, 46px);
    font-weight: 800;
    letter-spacing: 0.01em;
    line-height: 1;
    background: linear-gradient(100deg, #ffe9b0 0%, #ffb703 48%, #ff6b8a 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 6px 18px rgba(255, 140, 0, 0.28));
    animation: dmRise 0.9s 0.25s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.dm-progress {
    position: relative;
    width: clamp(180px, 44vw, 320px);
    height: 4px;
    margin-top: clamp(2px, 1vh, 10px);
    border-radius: 999px;
    background: rgba(255, 236, 205, 0.13);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    overflow: hidden;
    animation: dmRise 0.8s 0.5s both;
}

.dm-progress__bar {
    position: absolute;
    inset: 0 auto 0 0;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #ffb703, #ff6b8a 52%, #2ec4b6);
    box-shadow: 0 0 12px rgba(255, 183, 3, 0.5), 0 0 24px rgba(255, 107, 138, 0.3);
    animation: dmProgressFill var(--dm-duration, 4000ms) linear forwards;
}

.dm-status {
    margin: 0;
    font-size: clamp(10px, 1.5vmin, 13px);
    font-weight: 600;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(255, 238, 214, 0.72);
    animation: dmRise 0.8s 0.58s both;
}

.dm-dots i {
    display: inline-block;
    font-style: normal;
    animation: dmDotPulse 1.6s ease-in-out infinite;
}

.dm-dots i:nth-child(2) {
    animation-delay: 0.2s;
}

.dm-dots i:nth-child(3) {
    animation-delay: 0.4s;
}

/* ============================================================
   6. ANIMACIONES
   ============================================================ */
@keyframes dmPetalFall {
    0% {
        transform: translate3d(0, -10vh, 0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: var(--dm-petal-opacity, 0.85);
    }
    82% {
        opacity: var(--dm-petal-opacity, 0.85);
    }
    100% {
        transform: translate3d(var(--dm-petal-drift, 6vw), 112vh, 0) rotate(var(--dm-petal-spin, 420deg));
        opacity: 0;
    }
}

@keyframes dmLightFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(0.8);
        opacity: 0.15;
    }
    45% {
        transform: translate3d(var(--dm-light-drift, 3vw), -3vh, 0) scale(1.1);
        opacity: 0.9;
    }
    70% {
        transform: translate3d(0, -5vh, 0) scale(1);
        opacity: 0.55;
    }
}

@keyframes dmPicadoSway {
    from {
        transform: rotate(-1.6deg);
    }
    to {
        transform: rotate(1.8deg);
    }
}

@keyframes dmButterflyFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) rotate(-5deg);
    }
    35% {
        transform: translate3d(2vw, -2.5vh, 0) rotate(4deg);
    }
    68% {
        transform: translate3d(-1vw, -4.5vh, 0) rotate(-2deg);
    }
}

@keyframes dmWingFlap {
    from {
        transform: scaleX(0.62);
    }
    to {
        transform: scaleX(1);
    }
}

@keyframes dmLogoFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0);
    }
    50% {
        transform: translate3d(0, -8px, 0);
    }
}

@keyframes dmSkullSway {
    0%,
    100% {
        transform: translateX(-50%) rotate(-3deg);
    }
    50% {
        transform: translateX(-50%) rotate(3deg);
    }
}

@keyframes dmFlameFlicker {
    0%,
    100% {
        transform: scale(1) translateY(0);
        opacity: 0.95;
    }
    35% {
        transform: scale(0.92, 1.06) translateY(-1px);
        opacity: 1;
    }
    68% {
        transform: scale(1.05, 0.95) translateY(1px);
        opacity: 0.8;
    }
}

@keyframes dmRise {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes dmProgressFill {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

@keyframes dmDotPulse {
    0%,
    100% {
        opacity: 0.25;
    }
    50% {
        opacity: 1;
    }
}

/* ============================================================
   7. RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .dm-picado {
        width: 46vw;
        opacity: 0.8;
    }

    .dm-butterfly--one {
        left: 3%;
        top: 32%;
    }

    .dm-butterfly--two {
        right: 4%;
        top: 64%;
    }

    .dm-emblem {
        width: min(86vw, 52vh, 360px);
    }

    .dm-content {
        padding-bottom: clamp(40px, 10vh, 110px);
    }
}

@media (max-height: 560px) and (orientation: landscape) {
    .dm-content {
        gap: 8px;
        padding-top: 3vh;
        padding-bottom: 14px;
    }

    .dm-emblem {
        width: min(46vw, 52vh, 240px);
        margin-bottom: 8px;
    }

    .dm-title {
        font-size: clamp(22px, 4.4vmin, 34px);
    }

    .dm-progress {
        width: clamp(160px, 36vw, 260px);
    }

    .dm-butterfly {
        display: none;
    }
}

/* Accesibilidad: respeta la preferencia de movimiento reducido. */
@media (prefers-reduced-motion: reduce) {
    .dm-petal,
    .dm-light,
    .dm-picado,
    .dm-butterfly,
    .dm-butterfly__wing,
    .dm-logo,
    .dm-skull,
    .dm-flame,
    .dm-progress__bar,
    .dm-dots i {
        animation: none !important;
    }

    .dm-petal,
    .dm-light,
    .dm-butterfly {
        display: none;
    }

    .dm-progress__bar {
        width: 100%;
    }

    .dm-logo,
    .dm-skull {
        transform: none;
    }

    .dm-skull {
        transform: translateX(-50%);
    }
}
</style>
