<template>
    <!--
        ============================================================
        PANTALLA DE CARGA TEMÁTICA (Buen Fin) — DTW
        ============================================================
        Misma estructura que la portada de Día de Muertos:
          1) .bf-bg                → fondo estático (reemplazable con backgroundSrc)
          2) .bf-scrim             → oscurecido radial para dar legibilidad al logo
          3) .bf-tag / .bf-spark   → etiquetas de descuento y destellos cayendo
          4) .bf-streamer          → confeti meciéndose en las esquinas
          5) .bf-bubble            → porcentajes gigantes flotando al fondo
          6) .bf-content           → anillo de descuentos, logotipo y progreso

        Rendimiento: todo lo animado usa `transform` y `opacity` (GPU); el
        fondo y el logotipo son estáticos salvo una flotación suave.

        Contrato:
          - `duration` indica cuánto tarda en emitir `finished`.
          - El padre decide cuándo desmontar el componente.
    -->
    <Teleport to="body">
        <div
            v-if="isVisible"
            class="bf-loader"
            :style="{ '--bf-duration': `${duration}ms` }"
            role="status"
            aria-live="polite"
            :aria-label="status"
        >
            <!-- ==================== ESCENARIO ==================== -->
            <div class="bf-scene" aria-hidden="true">
                <div class="bf-bg-fallback"></div>
                <img
                    v-if="!backgroundFailed"
                    class="bf-bg"
                    :class="{ 'is-loaded': backgroundLoaded }"
                    :src="backgroundSrc"
                    alt=""
                    decoding="async"
                    fetchpriority="high"
                    @load="backgroundLoaded = true"
                    @error="backgroundFailed = true"
                />

                <div class="bf-scrim"></div>

                <!-- Etiquetas de descuento cayendo -->
                <span
                    v-for="(tag, index) in tags"
                    :key="`tag-${index}`"
                    class="bf-tag"
                    :class="`bf-tag--${tag.variant}`"
                    :style="{
                        '--bf-tag-left': tag.left,
                        '--bf-tag-duration': tag.duration,
                        '--bf-tag-delay': tag.delay,
                        '--bf-tag-drift': tag.drift,
                        '--bf-tag-spin': tag.spin,
                        '--bf-tag-opacity': tag.opacity ?? 0.9,
                    }"
                >
                    {{ tag.label }}
                </span>

                <!-- Destellos dorados -->
                <span
                    v-for="(spark, index) in sparks"
                    :key="`spark-${index}`"
                    class="bf-spark"
                    :style="{
                        '--bf-spark-left': spark.left,
                        '--bf-spark-top': spark.top,
                        '--bf-spark-size': spark.size,
                        '--bf-spark-duration': spark.duration,
                        '--bf-spark-delay': spark.delay,
                        '--bf-spark-drift': spark.drift,
                    }"
                ></span>

                <!-- Confeti en las esquinas -->
                <div class="bf-streamer bf-streamer--left">
                    <svg viewBox="0 0 380 140" fill="none">
                        <path class="bf-streamer__string" d="M0 8Q190 48 380 8" />
                        <g v-for="(piece, index) in streamerPiecesLeft" :key="`left-${index}`">
                            <rect
                                v-if="piece.shape === 'rect'"
                                :x="piece.x"
                                :y="piece.y"
                                :width="piece.w"
                                :height="piece.h"
                                :rx="1.5"
                                :fill="piece.color"
                                :transform="`rotate(${piece.rot} ${piece.x} ${piece.y})`"
                            />
                            <circle
                                v-else
                                :cx="piece.x"
                                :cy="piece.y"
                                :r="piece.r"
                                :fill="piece.color"
                            />
                        </g>
                    </svg>
                </div>
                <div class="bf-streamer bf-streamer--right">
                    <svg viewBox="0 0 380 140" fill="none">
                        <path class="bf-streamer__string" d="M0 8Q190 48 380 8" />
                        <g v-for="(piece, index) in streamerPiecesRight" :key="`right-${index}`">
                            <rect
                                v-if="piece.shape === 'rect'"
                                :x="piece.x"
                                :y="piece.y"
                                :width="piece.w"
                                :height="piece.h"
                                :rx="1.5"
                                :fill="piece.color"
                                :transform="`rotate(${piece.rot} ${piece.x} ${piece.y})`"
                            />
                            <circle
                                v-else
                                :cx="piece.x"
                                :cy="piece.y"
                                :r="piece.r"
                                :fill="piece.color"
                            />
                        </g>
                    </svg>
                </div>

                <!-- Porcentajes gigantes que dan profundidad -->
                <span class="bf-bubble bf-bubble--one">20%</span>
                <span class="bf-bubble bf-bubble--two">-20%</span>

                <div class="bf-vignette"></div>
            </div>

            <!-- ==================== CONTENIDO ==================== -->
            <div class="bf-content">
                <div class="bf-emblem">
                    <!-- Anillo de descuentos girando alrededor del logotipo -->
                    <svg class="bf-ring" viewBox="0 0 440 440" aria-hidden="true">
                        <defs>
                            <linearGradient id="bfRingGradient" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#ffd166" />
                                <stop offset="50%" stop-color="#ffffff" />
                                <stop offset="100%" stop-color="#ff4d4d" />
                            </linearGradient>
                        </defs>

                        <circle
                            cx="220"
                            cy="220"
                            r="198"
                            fill="none"
                            stroke="url(#bfRingGradient)"
                            stroke-width="3"
                            stroke-dasharray="10 18"
                            opacity="0.78"
                        />
                        <g class="bf-ring__spin">
                            <circle cx="220" cy="220" r="196" fill="none" stroke="#ffffff" stroke-opacity="0.16" stroke-width="1.4" />
                            <g v-for="(angle, index) in ringAngles" :key="`badge-${index}`">
                                <g :transform="`rotate(${angle} 220 220) translate(220 22)`">
                                    <circle r="15" fill="#ffffff" />
                                    <text y="5" text-anchor="middle" font-size="13" font-weight="900" fill="#c1121f">%</text>
                                </g>
                            </g>
                        </g>
                        <circle cx="220" cy="220" r="176" fill="none" stroke="#ffd166" stroke-opacity="0.18" stroke-width="1" />
                    </svg>

                    <img class="bf-logo" :src="logoSrc" alt="Buen Fin — 13 al 17 de noviembre" />
                </div>

                <p class="bf-kicker">13 al 17 de noviembre</p>
                <h1 class="bf-title">Buen Fin</h1>
                <p class="bf-offer"><span class="bf-offer__inner">Hasta 20% de descuento</span></p>

                <div class="bf-progress" aria-hidden="true">
                    <span class="bf-progress__bar"></span>
                </div>

                <p class="bf-status">
                    {{ status }}
                    <span class="bf-dots" aria-hidden="true"><i>.</i><i>.</i><i>.</i></span>
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
    /** Logotipo de Buen Fin. */
    logoSrc: { type: String, default: '/images/buen-fin-logo.png' },
    /**
     * Imagen de fondo de la escena. Puede apuntar a cualquier archivo en
     * `public/images`: basta con cambiar esta prop para usar otra fotografía.
     */
    backgroundSrc: { type: String, default: '/images/buen-fin-bg.svg' },
    /** Texto inferior de estado. */
    status: { type: String, default: 'Aplicando los descuentos' },
    /** Etiquetas de descuento: texto, estilo, posición, velocidad y deriva. */
    tags: {
        type: Array,
        default: () => [
            { label: '-20%', variant: 'solid', left: '6%', duration: '13s', delay: '-2s', drift: '6vw', spin: '220deg', opacity: 0.95 },
            { label: '-15%', variant: 'gold', left: '18%', duration: '17s', delay: '-9s', drift: '-5vw', spin: '-180deg', opacity: 0.85 },
            { label: 'OFERTA', variant: 'outline', left: '31%', duration: '15s', delay: '-5s', drift: '7vw', spin: '160deg', opacity: 0.7 },
            { label: '-20%', variant: 'gold', left: '43%', duration: '19s', delay: '-12s', drift: '-6vw', spin: '-200deg', opacity: 0.8 },
            { label: '-10%', variant: 'solid', left: '56%', duration: '14s', delay: '-7s', drift: '5vw', spin: '240deg', opacity: 0.9 },
            { label: 'SALE', variant: 'outline', left: '68%', duration: '18s', delay: '-3s', drift: '-5vw', spin: '-160deg', opacity: 0.7 },
            { label: '-20%', variant: 'solid', left: '81%', duration: '16s', delay: '-11s', drift: '4vw', spin: '200deg', opacity: 0.9 },
            { label: '-5%', variant: 'gold', left: '93%', duration: '20s', delay: '-6s', drift: '-4vw', spin: '-220deg', opacity: 0.75 },
        ],
    },
    /** Destellos dorados flotando en la escena. */
    sparks: {
        type: Array,
        default: () => [
            { left: '11%', top: '66%', size: '7px', duration: '9s', delay: '-2s', drift: '4vw' },
            { left: '26%', top: '56%', size: '5px', duration: '12s', delay: '-6s', drift: '-3vw' },
            { left: '69%', top: '62%', size: '6px', duration: '10s', delay: '-4s', drift: '3vw' },
            { left: '85%', top: '54%', size: '8px', duration: '13s', delay: '-8s', drift: '-4vw' },
            { left: '53%', top: '74%', size: '5px', duration: '11s', delay: '-1s', drift: '2vw' },
        ],
    },
});

const emit = defineEmits(['finished']);

const isVisible = ref(true);
const backgroundLoaded = ref(false);
const backgroundFailed = ref(false);

/* Ocho insignias de porcentaje forman el anillo del logotipo. */
const ringAngles = Array.from({ length: 8 }, (_, index) => (index * 360) / 8);

const confettiColors = ['#ffd166', '#ffffff', '#ff8fa3', '#ff4d4d', '#ffe3a3'];

function buildStreamer(mirror = false) {
    return Array.from({ length: 9 }, (_, index) => {
        const t = index / 8;
        const x = 12 + t * 356;
        const y = 14 + Math.sin(t * Math.PI) * 30;
        const piece = {
            shape: index % 3 === 0 ? 'circle' : 'rect',
            x: mirror ? 380 - x : x,
            y,
            w: 5 + (index % 3) * 3,
            h: 10 + (index % 2) * 5,
            r: 3 + (index % 3),
            rot: (mirror ? -1 : 1) * (index * 17),
            color: confettiColors[index % confettiColors.length],
        };

        return piece;
    });
}

const streamerPiecesLeft = buildStreamer(false);
const streamerPiecesRight = buildStreamer(false);

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
.bf-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    overflow: hidden;
    background: #150205;
    font-family: 'Manrope', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
    -webkit-tap-highlight-color: transparent;
}

.bf-scene {
    position: absolute;
    inset: 0;
    overflow: hidden;
    isolation: isolate;
}

.bf-bg-fallback {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(90% 60% at 50% 100%, rgba(255, 183, 3, 0.22) 0%, transparent 70%),
        radial-gradient(120% 80% at 50% 0%, #3a060d 0%, #150205 100%);
}

.bf-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.8s ease;
}

.bf-bg.is-loaded {
    opacity: 1;
}

/* Oscurecido central: mantiene logotipo y textos legibles. */
.bf-scrim {
    position: absolute;
    inset: 0;
    z-index: 2;
    background:
        radial-gradient(58% 52% at 50% 47%, rgba(21, 2, 5, 0.6) 0%, rgba(21, 2, 5, 0.24) 58%, transparent 78%),
        linear-gradient(to bottom, rgba(10, 1, 3, 0.32) 0%, transparent 32%, rgba(10, 1, 3, 0.5) 100%);
}

.bf-vignette {
    position: absolute;
    inset: 0;
    z-index: 8;
    background: radial-gradient(ellipse at 50% 42%, transparent 40%, rgba(8, 1, 2, 0.42) 76%, rgba(8, 1, 2, 0.9) 100%);
}

/* ============================================================
   2. ETIQUETAS DE DESCUENTO Y DESTELLOS
   ============================================================ */
.bf-tag {
    position: absolute;
    top: -12vh;
    left: var(--bf-tag-left, 50%);
    z-index: 5;
    padding: 6px 11px;
    border-radius: 9px;
    font-size: clamp(10px, 1.6vmin, 14px);
    font-weight: 900;
    letter-spacing: 0.05em;
    line-height: 1;
    white-space: nowrap;
    opacity: 0;
    will-change: transform, opacity;
    animation: bfTagFall var(--bf-tag-duration, 15s) linear var(--bf-tag-delay, 0s) infinite;
}

.bf-tag--solid {
    color: #ffffff;
    background: linear-gradient(135deg, #ff4d4d, #c1121f);
    box-shadow: 0 6px 16px rgba(193, 18, 31, 0.45), inset 0 0 0 1px rgba(255, 255, 255, 0.28);
}

.bf-tag--gold {
    color: #3b0a0a;
    background: linear-gradient(135deg, #ffe08a, #f4a300);
    box-shadow: 0 6px 16px rgba(244, 163, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.4);
}

.bf-tag--outline {
    color: #ffffff;
    background: rgba(255, 255, 255, 0.1);
    border: 1.5px solid rgba(255, 255, 255, 0.7);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
}

.bf-spark {
    position: absolute;
    top: var(--bf-spark-top, 60%);
    left: var(--bf-spark-left, 50%);
    z-index: 5;
    width: var(--bf-spark-size, 6px);
    height: var(--bf-spark-size, 6px);
    border-radius: 50%;
    background: radial-gradient(circle, #fffbe6 0%, #ffd166 42%, rgba(255, 209, 102, 0) 72%);
    box-shadow: 0 0 12px rgba(255, 209, 102, 0.6);
    opacity: 0;
    will-change: transform, opacity;
    animation: bfSparkFloat var(--bf-spark-duration, 10s) ease-in-out var(--bf-spark-delay, 0s) infinite;
}

/* ============================================================
   3. CONFETI DE ESQUINAS
   ============================================================ */
.bf-streamer {
    position: absolute;
    top: 0;
    z-index: 6;
    width: min(38vw, 520px);
    opacity: 0.92;
    transform-origin: 50% 0;
    animation: bfSway 8s ease-in-out infinite alternate;
}

.bf-streamer svg {
    display: block;
    width: 100%;
    height: auto;
    filter: drop-shadow(0 6px 10px rgba(0, 0, 0, 0.35));
}

.bf-streamer--left {
    left: 0;
}

.bf-streamer--right {
    right: 0;
    animation-delay: -4s;
}

.bf-streamer--right svg {
    transform: scaleX(-1);
}

.bf-streamer__string {
    stroke: rgba(255, 236, 200, 0.42);
    stroke-width: 2;
}

/* ============================================================
   4. PORCENTAJES GIGANTES DE FONDO
   ============================================================ */
.bf-bubble {
    position: absolute;
    z-index: 4;
    font-family: 'Arial Black', 'Manrope', sans-serif;
    font-weight: 900;
    letter-spacing: -0.05em;
    color: rgba(255, 255, 255, 0.07);
    pointer-events: none;
    will-change: transform;
}

.bf-bubble--one {
    top: 12%;
    left: 3%;
    font-size: clamp(76px, 17vmin, 190px);
    animation: bfBubbleFloat 18s ease-in-out infinite;
}

.bf-bubble--two {
    right: 2%;
    bottom: 8%;
    font-size: clamp(64px, 14vmin, 160px);
    animation: bfBubbleFloat 22s ease-in-out -7s infinite reverse;
}

/* ============================================================
   5. CONTENIDO
   ============================================================ */
.bf-content {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: clamp(8px, 1.5vh, 18px);
    padding: 6vh clamp(16px, 5vw, 48px) clamp(48px, 12vh, 140px);
    text-align: center;
}

.bf-emblem {
    position: relative;
    display: grid;
    place-items: center;
    width: min(78vw, 50vh, 430px);
    aspect-ratio: 1;
    margin-bottom: clamp(16px, 3vh, 34px);
}

.bf-ring {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 10px 22px rgba(0, 0, 0, 0.45));
}

.bf-ring__spin {
    transform-origin: 220px 220px;
    animation: bfRingSpin 26s linear infinite;
}

.bf-logo {
    position: relative;
    display: block;
    width: 54%;
    height: auto;
    border-radius: 8px;
    filter: drop-shadow(0 0 16px rgba(255, 209, 102, 0.35)) drop-shadow(0 14px 26px rgba(0, 0, 0, 0.6));
    animation: bfLogoFloat 5.5s ease-in-out infinite;
}

.bf-kicker {
    margin: 0;
    font-size: clamp(9px, 1.4vmin, 12px);
    font-weight: 700;
    letter-spacing: 0.4em;
    text-transform: uppercase;
    color: rgba(255, 227, 163, 0.82);
    animation: bfRise 0.8s 0.15s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.bf-title {
    margin: 0;
    font-size: clamp(30px, 5.8vmin, 50px);
    font-weight: 900;
    letter-spacing: 0.01em;
    line-height: 1;
    background: linear-gradient(100deg, #ffffff 0%, #ffe3a3 48%, #ff5c5c 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    filter: drop-shadow(0 6px 18px rgba(255, 77, 77, 0.3));
    animation: bfRise 0.9s 0.25s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.bf-offer {
    display: inline-flex;
    align-items: center;
    margin: 0;
    padding: 8px 18px;
    border-radius: 999px;
    font-size: clamp(11px, 1.7vmin, 15px);
    font-weight: 800;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #ffffff;
    background: linear-gradient(135deg, #ff4d4d 0%, #c1121f 100%);
    box-shadow: 0 10px 24px rgba(193, 18, 31, 0.45), inset 0 0 0 1px rgba(255, 255, 255, 0.28);
    animation: bfRise 0.8s 0.38s both;
}

.bf-offer__inner {
    display: inline-block;
    animation: bfOfferPulse 2.6s 1.2s ease-in-out infinite;
}

.bf-progress {
    position: relative;
    width: clamp(180px, 44vw, 320px);
    height: 4px;
    margin-top: clamp(2px, 1vh, 10px);
    border-radius: 999px;
    background: rgba(255, 236, 205, 0.14);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
    overflow: hidden;
    animation: bfRise 0.8s 0.5s both;
}

.bf-progress__bar {
    position: absolute;
    inset: 0 auto 0 0;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #ff4d4d, #ffd166 58%, #ffffff);
    box-shadow: 0 0 12px rgba(255, 77, 77, 0.5), 0 0 24px rgba(255, 209, 102, 0.3);
    animation: bfProgressFill var(--bf-duration, 4000ms) linear forwards;
}

.bf-status {
    margin: 0;
    font-size: clamp(10px, 1.5vmin, 13px);
    font-weight: 700;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: rgba(255, 238, 214, 0.75);
    animation: bfRise 0.8s 0.58s both;
}

.bf-dots i {
    display: inline-block;
    font-style: normal;
    animation: bfDotPulse 1.6s ease-in-out infinite;
}

.bf-dots i:nth-child(2) {
    animation-delay: 0.2s;
}

.bf-dots i:nth-child(3) {
    animation-delay: 0.4s;
}

/* ============================================================
   6. ANIMACIONES
   ============================================================ */
@keyframes bfTagFall {
    0% {
        transform: translate3d(0, -12vh, 0) rotate(0deg);
        opacity: 0;
    }
    10% {
        opacity: var(--bf-tag-opacity, 0.9);
    }
    82% {
        opacity: var(--bf-tag-opacity, 0.9);
    }
    100% {
        transform: translate3d(var(--bf-tag-drift, 5vw), 112vh, 0) rotate(var(--bf-tag-spin, 200deg));
        opacity: 0;
    }
}

@keyframes bfSparkFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) scale(0.8);
        opacity: 0.15;
    }
    45% {
        transform: translate3d(var(--bf-spark-drift, 3vw), -3vh, 0) scale(1.1);
        opacity: 0.9;
    }
    70% {
        transform: translate3d(0, -5vh, 0) scale(1);
        opacity: 0.55;
    }
}

@keyframes bfSway {
    from {
        transform: rotate(-1.6deg);
    }
    to {
        transform: rotate(1.8deg);
    }
}

@keyframes bfBubbleFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) rotate(-3deg);
    }
    50% {
        transform: translate3d(2vw, -3vh, 0) rotate(3deg);
    }
}

@keyframes bfRingSpin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

@keyframes bfLogoFloat {
    0%,
    100% {
        transform: translate3d(0, 0, 0) rotate(-1deg);
    }
    50% {
        transform: translate3d(0, -8px, 0) rotate(1deg);
    }
}

@keyframes bfOfferPulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.04);
    }
}

@keyframes bfRise {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes bfProgressFill {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

@keyframes bfDotPulse {
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
    .bf-streamer {
        width: 48vw;
        opacity: 0.82;
    }

    .bf-emblem {
        width: min(86vw, 50vh, 350px);
    }

    .bf-content {
        padding-bottom: clamp(40px, 10vh, 110px);
    }
}

@media (max-height: 560px) and (orientation: landscape) {
    .bf-content {
        gap: 7px;
        padding-top: 3vh;
        padding-bottom: 14px;
    }

    .bf-emblem {
        width: min(44vw, 50vh, 230px);
        margin-bottom: 8px;
    }

    .bf-title {
        font-size: clamp(22px, 4.4vmin, 34px);
    }

    .bf-offer {
        padding: 6px 14px;
        letter-spacing: 0.14em;
    }

    .bf-progress {
        width: clamp(160px, 36vw, 260px);
    }

    .bf-bubble {
        display: none;
    }
}

/* Accesibilidad: respeta la preferencia de movimiento reducido. */
@media (prefers-reduced-motion: reduce) {
    .bf-tag,
    .bf-spark,
    .bf-streamer,
    .bf-bubble,
    .bf-ring__spin,
    .bf-logo,
    .bf-offer,
    .bf-offer__inner,
    .bf-progress__bar,
    .bf-dots i {
        animation: none !important;
    }

    .bf-tag,
    .bf-spark,
    .bf-bubble {
        display: none;
    }

    .bf-progress__bar {
        width: 100%;
    }

    .bf-logo {
        transform: none;
    }
}
</style>
