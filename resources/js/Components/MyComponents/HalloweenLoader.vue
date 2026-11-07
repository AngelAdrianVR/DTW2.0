<template>
    <!--
        ============================================================
        PANTALLA DE CARGA TEMÁTICA (Halloween) — DTW
        ============================================================
        Escena en capas (z-index dentro de .hw-scene):
          1) .hw-sky            → cielo nocturno con degradados volumétricos
          2) .hw-moon-glow/moon → luna texturizada + halo cálido
          3) .hw-cloud          → nubes volumétricas (deriva muy lenta)
          4) .hw-bat            → murciélagos volando (recorrido en bucle)
          5) .hw-web            → telarañas en las esquinas
          6) .hw-ground         → colinas, hierba y hojas secas
          7) .hw-pumpkin        → calabazas con cara iluminada (parpadeo suave)
          8) .hw-vignette/grain → viñeta y grano fotográfico (textura 3D)

        Todas las animaciones usan únicamente `transform` y `opacity`
        (aceleradas por GPU). Los filtros y texturas son estáticos y se
        rasterizan una sola vez, por lo que el consumo es mínimo.

        Personalización rápida:
          - duration: 4500 ms (4 - 5 s)   - logoSrc / status
          - bats[]: tamaño, velocidad, retardo, color y altura de cada murciélago
    -->
    <Teleport to="body">
        <div
            v-if="isVisible"
            class="hw-loader"
            :class="{ 'is-closing': isClosing }"
            :style="{ '--hw-duration': `${duration}ms` }"
            role="status"
            aria-live="polite"
            :aria-label="status"
        >
            <!-- ==================== ESCENARIO ==================== -->
            <div class="hw-scene" aria-hidden="true">
                <!-- Cielo -->
                <div class="hw-sky"></div>
                <div class="hw-stars"></div>

                <!-- Luna con halo -->
                <div class="hw-moon-glow"></div>
                <div class="hw-moon"></div>

                <!-- Nubes volumétricas (deriva lenta en bucle) -->
                <div class="hw-cloud hw-cloud--1"><span class="hw-cloud__body"><i></i><i></i><i></i><i></i></span></div>
                <div class="hw-cloud hw-cloud--2"><span class="hw-cloud__body"><i></i><i></i><i></i><i></i></span></div>
                <div class="hw-cloud hw-cloud--3"><span class="hw-cloud__body"><i></i><i></i><i></i><i></i></span></div>
                <div class="hw-cloud hw-cloud--4"><span class="hw-cloud__body"><i></i><i></i><i></i><i></i></span></div>

                <!-- Murciélagos -->
                <span
                    v-for="(bat, index) in bats"
                    :key="index"
                    class="hw-bat"
                    :style="{
                        '--bat-top': bat.top,
                        '--bat-size': bat.size,
                        '--bat-duration': bat.duration,
                        '--bat-delay': bat.delay,
                        '--bat-opacity': bat.opacity,
                        '--bat-color': bat.color,
                    }"
                >
                    <span class="hw-bat__inner">
                        <svg viewBox="0 0 120 40" fill="currentColor">
                            <path d="M60 30Q56 22 50 27Q44 20 36 23Q28 17 18 20Q8 14 0 6Q10 9 18 3Q30 8 42 5Q50 0 52 5Q55 8 60 8Q65 8 68 5Q70 0 78 5Q90 8 102 3Q110 9 120 6Q112 14 102 20Q92 17 84 23Q76 20 70 27Q64 22 60 30Z" />
                        </svg>
                    </span>
                </span>

                <!-- Telarañas de esquina -->
                <svg class="hw-web hw-web--left" viewBox="0 0 200 200" fill="none" stroke="currentColor">
                    <g stroke-width="1.1">
                        <path d="M0 0L200 0M0 0L186 72M0 0L140 140M0 0L72 186M0 0L0 200" />
                        <circle cx="0" cy="0" r="54" />
                        <circle cx="0" cy="0" r="98" />
                        <circle cx="0" cy="0" r="142" />
                    </g>
                </svg>
                <svg class="hw-web hw-web--right" viewBox="0 0 200 200" fill="none" stroke="currentColor">
                    <g stroke-width="1.1">
                        <path d="M0 0L200 0M0 0L186 72M0 0L140 140M0 0L72 186M0 0L0 200" />
                        <circle cx="0" cy="0" r="54" />
                        <circle cx="0" cy="0" r="98" />
                        <circle cx="0" cy="0" r="142" />
                    </g>
                </svg>

                <!-- Suelo: colinas, hierba y hojas -->
                <div class="hw-ground">
                    <svg class="hw-hills hw-hills--back" viewBox="0 0 1440 220" preserveAspectRatio="none">
                        <path
                            d="M0 44C110 14 240 10 360 28C480 46 600 8 740 18C880 28 1000 4 1120 22C1240 40 1350 30 1440 14L1440 220L0 220Z"
                            fill="url(#hwHillBack)"
                        />
                    </svg>
                    <svg class="hw-hills hw-hills--mid" viewBox="0 0 1440 220" preserveAspectRatio="none">
                        <path
                            d="M0 96C160 74 300 68 440 84C580 100 700 62 840 76C980 90 1120 64 1250 84C1340 96 1400 94 1440 86L1440 220L0 220Z"
                            fill="url(#hwHillMid)"
                        />
                    </svg>
                    <div class="hw-ground__fog"></div>
                    <svg class="hw-hills hw-hills--front" viewBox="0 0 1440 220" preserveAspectRatio="none">
                        <path
                            d="M0 146C180 126 330 134 470 146C610 158 730 128 870 136C1010 144 1150 124 1290 140C1360 148 1410 147 1440 142L1440 220L0 220Z"
                            fill="url(#hwHillFront)"
                        />
                        <path
                            d="M0 146C180 126 330 134 470 146C610 158 730 128 870 136C1010 144 1150 124 1290 140C1360 148 1410 147 1440 142"
                            fill="none"
                            stroke="#ff9a45"
                            stroke-width="1.4"
                            opacity="0.1"
                        />
                    </svg>
                    <div class="hw-ground__lights"></div>
                    <svg class="hw-ground__details" viewBox="0 0 1440 120" preserveAspectRatio="none">
                        <g fill="#0a0712">
                            <path d="M120 120c-4-30 2-52 16-74-6 24-8 48-6 74z" />
                            <path d="M138 120c2-26 12-44 28-58-12 20-18 36-18 58z" />
                            <path d="M420 120c-6-24 0-44 12-62-4 22-6 40-4 62z" />
                            <path d="M700 120c-2-28 6-48 20-66-8 24-12 44-10 66z" />
                            <path d="M980 120c-4-26 4-46 18-64-8 22-12 42-10 64z" />
                            <path d="M1280 120c-2-24 6-42 18-58-8 20-12 38-10 58z" />
                            <path d="M240 118c10-11 26-13 38-4-12 9-27 11-38 4z" />
                            <path d="M330 112c9-9 22-11 32-4-10 8-22 10-32 4z" />
                            <path d="M520 116c11-10 27-11 39-3-13 8-28 10-39 3z" />
                            <path d="M640 110c8-8 20-9 29-3-9 7-20 9-29 3z" />
                            <path d="M820 118c10-10 25-12 36-4-12 8-26 10-36 4z" />
                            <path d="M920 112c9-9 22-10 32-3-10 8-22 9-32 3z" />
                            <path d="M1120 116c11-10 26-12 38-3-13 8-27 10-38 3z" />
                            <path d="M1240 110c8-8 20-9 28-3-9 7-19 9-28 3z" />
                        </g>
                        <g fill="#2a1409" opacity="0.75">
                            <path d="M280 118c8-9 21-10 30-3-10 7-21 9-30 3z" />
                            <path d="M760 116c9-9 22-10 31-3-10 7-22 9-31 3z" />
                            <path d="M1360 116c9-9 22-10 31-3-10 7-22 9-31 3z" />
                        </g>
                    </svg>
                </div>

                <!-- Calabazas: las dos grandes se encienden y se apagan -->
                <svg
                    v-for="pumpkin in pumpkins"
                    :key="pumpkin.className"
                    class="hw-pumpkin"
                    :class="[pumpkin.className, { 'hw-pumpkin--lit': pumpkin.lit }]"
                    viewBox="0 0 200 190"
                    aria-hidden="true"
                >
                    <ellipse cx="100" cy="178" rx="80" ry="13" fill="#040208" opacity="0.7" filter="url(#hwPkBlur)" />
                    <ellipse class="hw-pumpkin__aura" cx="100" cy="142" rx="88" ry="24" fill="url(#hwPkAura)" filter="url(#hwPkBlur)" />
                    <g class="hw-pumpkin__body">
                        <path d="M98 34C90 22 97 10 110 6C110 17 106 26 101 34Z" fill="url(#hwPkStem)" />
                        <ellipse cx="36" cy="120" rx="21" ry="43" fill="url(#hwPkLobe)" />
                        <ellipse cx="164" cy="120" rx="21" ry="43" fill="url(#hwPkLobe)" />
                        <ellipse cx="63" cy="118" rx="31" ry="51" fill="url(#hwPkLobe)" />
                        <ellipse cx="137" cy="118" rx="31" ry="51" fill="url(#hwPkLobe)" />
                        <ellipse cx="100" cy="116" rx="45" ry="55" fill="url(#hwPkLobe)" />
                        <path d="M80 70C75 100 75 132 82 162" fill="none" stroke="#5a1b04" stroke-width="3" opacity="0.4" />
                        <path d="M120 70C125 100 125 132 118 162" fill="none" stroke="#5a1b04" stroke-width="3" opacity="0.4" />
                        <ellipse cx="96" cy="78" rx="54" ry="30" fill="#ffd79a" opacity="0.14" filter="url(#hwPkBlur)" />
                        <ellipse cx="100" cy="156" rx="72" ry="22" fill="#3d1204" opacity="0.42" />
                    </g>
                    <g class="hw-pumpkin__face-glow" fill="#ff9a2e" filter="url(#hwPkBlur)" opacity="0.5">
                        <path d="M64 102 86 94 84 114Z" />
                        <path d="M136 102 114 94 116 114Z" />
                        <path d="M100 112 110 125 90 125Z" />
                        <path d="M58 138 74 134 82 144 92 136 104 146 114 136 124 144 132 134 146 138 142 152C120 162 82 162 60 152Z" />
                    </g>
                    <g class="hw-pumpkin__face" fill="url(#hwPkFace)">
                        <path d="M64 102 86 94 84 114Z" />
                        <path d="M136 102 114 94 116 114Z" />
                        <path d="M100 112 110 125 90 125Z" />
                        <path d="M58 138 74 134 82 144 92 136 104 146 114 136 124 144 132 134 146 138 142 152C120 162 82 162 60 152Z" />
                    </g>
                </svg>

                <!-- Viñeta y grano -->
                <div class="hw-vignette"></div>
                <div class="hw-grain"></div>

                <!-- Definiciones compartidas (degradados y filtros) -->
                <svg class="hw-defs" width="0" height="0" focusable="false">
                    <defs>
                        <linearGradient id="hwHillBack" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2a2344" />
                            <stop offset="45%" stop-color="#16102a" />
                            <stop offset="100%" stop-color="#0c0917" />
                        </linearGradient>
                        <linearGradient id="hwHillMid" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#15102a" />
                            <stop offset="60%" stop-color="#0b0718" />
                            <stop offset="100%" stop-color="#08060f" />
                        </linearGradient>
                        <linearGradient id="hwHillFront" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0d0a18" />
                            <stop offset="50%" stop-color="#050409" />
                            <stop offset="100%" stop-color="#040309" />
                        </linearGradient>
                        <radialGradient id="hwPkLobe" cx="42%" cy="28%" r="80%">
                            <stop offset="0%" stop-color="#ffc072" />
                            <stop offset="32%" stop-color="#e5811f" />
                            <stop offset="68%" stop-color="#a8410b" />
                            <stop offset="100%" stop-color="#571a05" />
                        </radialGradient>
                        <linearGradient id="hwPkStem" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#8a6a34" />
                            <stop offset="100%" stop-color="#2c1b0c" />
                        </linearGradient>
                        <linearGradient id="hwPkFace" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#fff8d2" />
                            <stop offset="55%" stop-color="#ffc247" />
                            <stop offset="100%" stop-color="#ff8a1f" />
                        </linearGradient>
                        <radialGradient id="hwPkAura">
                            <stop offset="0%" stop-color="#ff9628" stop-opacity="0.6" />
                            <stop offset="55%" stop-color="#ff7a14" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#ff7a14" stop-opacity="0" />
                        </radialGradient>
                        <filter id="hwPkBlur" x="-30%" y="-60%" width="160%" height="220%">
                            <feGaussianBlur stdDeviation="9" />
                        </filter>
                    </defs>
                </svg>
            </div>

            <!-- ==================== CONTENIDO ==================== -->
            <div class="hw-content">
                <div class="hw-emblem">
                    <!-- Sombrero de bruja (SVG inline, sin imágenes extra) -->
                    <div class="hw-hat" aria-hidden="true">
                        <svg viewBox="0 0 200 170" role="presentation">
                            <defs>
                                <linearGradient id="hwHatCone" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#8a63c9" />
                                    <stop offset="45%" stop-color="#4b2f7a" />
                                    <stop offset="100%" stop-color="#1f1130" />
                                </linearGradient>
                                <linearGradient id="hwHatBrim" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stop-color="#6a459f" />
                                    <stop offset="55%" stop-color="#3a2361" />
                                    <stop offset="100%" stop-color="#140a20" />
                                </linearGradient>
                                <linearGradient id="hwHatBand" x1="0" y1="0" x2="1" y2="0">
                                    <stop offset="0%" stop-color="#2c1a45" />
                                    <stop offset="100%" stop-color="#120a1f" />
                                </linearGradient>
                            </defs>

                            <ellipse cx="100" cy="128" rx="94" ry="26" fill="url(#hwHatBrim)" />
                            <ellipse cx="100" cy="123" rx="94" ry="21" fill="url(#hwHatBrim)" opacity="0.95" />

                            <path
                                d="M56 122C84 88 100 52 116 24C120 16 131 15 135 22C139 30 132 37 125 34C121 32 120 26 124 22C132 50 148 88 156 122Z"
                                fill="url(#hwHatCone)"
                            />
                            <path d="M56 122C84 88 100 52 116 24C110 50 96 86 90 122Z" fill="#ffffff" opacity="0.08" />

                            <path d="M68 99C88 94 118 94 141 99L147 116C120 121 86 121 61 116Z" fill="url(#hwHatBand)" />
                            <rect x="87" y="93" width="27" height="27" rx="5" fill="none" stroke="#e3b45f" stroke-width="5" />
                            <rect x="97" y="101" width="5" height="14" rx="2.5" fill="#e3b45f" />
                            <path d="M61 116C86 121 120 121 147 116" fill="none" stroke="#e3b45f" stroke-width="2" opacity="0.35" />
                        </svg>
                    </div>

                    <img class="hw-logo" :src="logoSrc" alt="DTW — Desarrollo de Tecnologías Web" />
                </div>

                <div class="hw-progress" aria-hidden="true">
                    <span class="hw-progress__bar"></span>
                </div>

                <p class="hw-status">
                    {{ status }}
                    <span class="hw-dots" aria-hidden="true"><i>.</i><i>.</i><i>.</i></span>
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script>
/*
 * Estado a nivel de MÓDULO (fuera de setup): sobrevive a las navegaciones
 * SPA de Inertia, así el loader no se repite al volver a la raíz.
 */
let loaderAlreadyShown = false;
</script>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** Duración mínima visible en milisegundos (4 - 5 segundos). */
    duration: { type: Number, default: 4500 },
    /** Tiempo máximo de espera al evento `load` para no bloquear la entrada. */
    maxWait: { type: Number, default: 1000 },
    /** Logotipo principal de la marca. */
    logoSrc: { type: String, default: '/images/white_logo.png' },
    /** Texto inferior de estado. */
    status: { type: String, default: 'Invocando la magia' },
    /** Evita repetir el loader en cada navegación interna. */
    showOnce: { type: Boolean, default: true },
    /** Murciélagos: altura, tamaño, velocidad, retardo, opacidad y color. */
    bats: {
        type: Array,
        default: () => [
            { top: '9%', size: '46px', duration: '38s', delay: '0s', opacity: 0.85, color: '#0a0614' },
            { top: '18%', size: '28px', duration: '30s', delay: '-11s', opacity: 0.55, color: '#0a0614' },
            { top: '27%', size: '56px', duration: '46s', delay: '-24s', opacity: 0.9, color: '#08050f' },
            { top: '36%', size: '32px', duration: '34s', delay: '-6s', opacity: 0.6, color: '#0a0614' },
        ],
    },
});

const emit = defineEmits(['finished']);

/* Calabazas de la escena: las marcadas como `lit` se encienden y apagan */
const pumpkins = [
    { className: 'hw-pumpkin--left', lit: true },
    { className: 'hw-pumpkin--right', lit: true },
    { className: 'hw-pumpkin--small', lit: false },
];

const isVisible = ref(!(props.showOnce && loaderAlreadyShown));
const isClosing = ref(false);

const timers = [];
let previousBodyOverflow = '';

const wait = (ms) => new Promise((resolve) => {
    const id = setTimeout(resolve, ms);
    timers.push(id);
});

/** Espera al evento `window.load` (equivalente a window.onload). */
const waitForWindowLoad = () => new Promise((resolve) => {
    if (document.readyState === 'complete') {
        resolve();
        return;
    }
    window.addEventListener('load', resolve, { once: true });
});

const lockScroll = () => {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
};

const unlockScroll = () => {
    document.body.style.overflow = previousBodyOverflow;
};

const close = () => {
    // La transición de opacidad vive en CSS (opacity 0.5s ease).
    isClosing.value = true;
    const id = setTimeout(() => {
        isVisible.value = false; // display: none real (v-if desmonta el nodo)
        unlockScroll();
        emit('finished');
    }, 500);
    timers.push(id);
};

const startLoader = async () => {
    if (!isVisible.value) return;

    loaderAlreadyShown = true;
    lockScroll();

    const startedAt = performance.now();

    // Nunca bloqueamos más de `maxWait` esperando la carga completa.
    await Promise.race([waitForWindowLoad(), wait(props.maxWait)]);

    const elapsed = performance.now() - startedAt;
    if (elapsed < props.duration) await wait(props.duration - elapsed);

    close();
};

onMounted(startLoader);

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
.hw-loader {
    position: fixed;
    inset: 0;
    z-index: 9999;
    overflow: hidden;
    background: #04030a;
    font-family: 'Quicksand', system-ui, -apple-system, 'Segoe UI', sans-serif;
    opacity: 1;
    transition: opacity 0.5s ease;
    -webkit-tap-highlight-color: transparent;
}

.hw-loader.is-closing {
    opacity: 0;
    pointer-events: none;
}

.hw-scene {
    position: absolute;
    inset: 0;
    overflow: hidden;
    isolation: isolate;
}

/* Cielo nocturno con halo cálido alrededor de la luna */
.hw-sky {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(140% 80% at 50% 14%, rgba(255, 190, 96, 0.16) 0%, rgba(255, 150, 60, 0.05) 34%, transparent 62%),
        radial-gradient(120% 70% at 50% 0%, #191331 0%, #0d0b1c 46%, #05040c 100%);
}

/* Estrellas tenues (textura estática) */
.hw-stars {
    position: absolute;
    inset: 0;
    opacity: 0.55;
    background-image:
        radial-gradient(1.4px 1.4px at 12% 12%, rgba(255, 255, 255, 0.75), transparent 60%),
        radial-gradient(1.2px 1.2px at 26% 6%, rgba(255, 255, 255, 0.55), transparent 60%),
        radial-gradient(1.6px 1.6px at 74% 9%, rgba(255, 255, 255, 0.65), transparent 60%),
        radial-gradient(1.2px 1.2px at 88% 18%, rgba(255, 255, 255, 0.45), transparent 60%),
        radial-gradient(1.6px 1.6px at 62% 4%, rgba(255, 255, 255, 0.5), transparent 60%),
        radial-gradient(1.2px 1.2px at 40% 18%, rgba(255, 255, 255, 0.4), transparent 60%),
        radial-gradient(1.4px 1.4px at 8% 30%, rgba(255, 255, 255, 0.35), transparent 60%),
        radial-gradient(1.2px 1.2px at 94% 32%, rgba(255, 255, 255, 0.35), transparent 60%);
}

/* ============================================================
   2. LUNA (textura con cráteres y volumen)
   ============================================================ */
.hw-moon-glow {
    position: absolute;
    left: 50%;
    top: 4%;
    width: clamp(320px, 64vmin, 780px);
    aspect-ratio: 1;
    transform: translateX(-50%);
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 205, 110, 0.28) 0%, rgba(255, 165, 60, 0.11) 40%, transparent 68%);
    filter: blur(28px);
}

.hw-moon {
    position: absolute;
    left: 50%;
    top: 7%;
    width: clamp(210px, 34vmin, 380px);
    aspect-ratio: 1;
    transform: translateX(-50%);
    border-radius: 50%;
    overflow: hidden;
    background: radial-gradient(circle at 36% 30%, #fffef4 0%, #fff2c2 26%, #ffe6a0 48%, #f3cf7f 70%, #dcac57 100%);
    box-shadow:
        0 0 70px rgba(255, 214, 120, 0.35),
        0 0 190px rgba(255, 170, 60, 0.16),
        inset -18px -22px 60px rgba(120, 70, 15, 0.45),
        inset 12px 14px 44px rgba(255, 255, 230, 0.2);
}

/* Cráteres: manchas cálidas con borde iluminado (relieve) */
.hw-moon::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background-image:
        radial-gradient(circle at 58% 56%, rgba(140, 100, 40, 0.14) 0 24%, transparent 42%),
        radial-gradient(circle at 30% 30%, rgba(140, 100, 40, 0.1) 0 18%, transparent 36%),
        radial-gradient(circle at 28% 34%, rgba(150, 104, 36, 0.22) 0 4%, rgba(255, 246, 208, 0.2) 5%, transparent 7%),
        radial-gradient(circle at 44% 62%, rgba(150, 104, 36, 0.2) 0 6%, rgba(255, 246, 208, 0.16) 7%, transparent 9%),
        radial-gradient(circle at 66% 30%, rgba(150, 104, 36, 0.18) 0 3.5%, rgba(255, 246, 208, 0.16) 4.5%, transparent 6%),
        radial-gradient(circle at 74% 58%, rgba(150, 104, 36, 0.18) 0 5%, rgba(255, 246, 208, 0.14) 6%, transparent 8%),
        radial-gradient(circle at 54% 20%, rgba(150, 104, 36, 0.14) 0 3%, transparent 5%),
        radial-gradient(circle at 22% 62%, rgba(150, 104, 36, 0.12) 0 3.5%, transparent 5.5%),
        radial-gradient(circle at 58% 78%, rgba(150, 104, 36, 0.14) 0 4%, transparent 6%),
        radial-gradient(circle at 36% 48%, rgba(150, 104, 36, 0.1) 0 2.5%, transparent 4%),
        radial-gradient(circle at 82% 40%, rgba(150, 104, 36, 0.12) 0 2%, transparent 4%);
    filter: blur(1.6px);
    opacity: 0.8;
}

/* Sombra lateral: da sensación esférica */
.hw-moon::after {
    content: '';
    position: absolute;
    inset: -6%;
    border-radius: 50%;
    background:
        radial-gradient(circle at 70% 62%, transparent 40%, rgba(30, 14, 8, 0.28) 74%),
        radial-gradient(circle at 26% 22%, rgba(255, 255, 240, 0.28) 0%, transparent 42%);
}

/* ============================================================
   3. NUBES VOLUMÉTRICAS (deriva muy lenta)
   ============================================================ */
.hw-cloud {
    position: absolute;
    will-change: transform;
    animation: cloudDrift var(--cloud-duration, 58s) ease-in-out infinite alternate;
}

/* Nubes volumétricas: cúmulos de "puffs" con luz cenital de la luna */
.hw-cloud__body {
    position: absolute;
    inset: 0;
    display: block;
    filter: blur(20px);
}

.hw-cloud__body i {
    position: absolute;
    display: block;
    border-radius: 50%;
    background: radial-gradient(
        circle at 44% 24%,
        rgba(122, 110, 164, 0.46) 0%,
        rgba(54, 46, 80, 0.86) 42%,
        rgba(24, 20, 38, 0.72) 68%,
        transparent 92%
    );
}

.hw-cloud__body i:nth-child(1) {
    left: 0;
    top: 32%;
    width: 46%;
    height: 70%;
    opacity: 0.9;
}

.hw-cloud__body i:nth-child(2) {
    left: 18%;
    top: 2%;
    width: 50%;
    height: 96%;
}

.hw-cloud__body i:nth-child(3) {
    left: 44%;
    top: 16%;
    width: 42%;
    height: 88%;
    opacity: 0.95;
}

.hw-cloud__body i:nth-child(4) {
    left: 64%;
    top: 34%;
    width: 34%;
    height: 66%;
    opacity: 0.7;
}

.hw-cloud--1 {
    left: 2%;
    top: 24%;
    width: 48%;
    height: 17%;
    --cloud-duration: 54s;
    animation-delay: -8s;
}

.hw-cloud--2 {
    right: 1%;
    top: 31%;
    width: 42%;
    height: 15%;
    --cloud-duration: 46s;
    animation-delay: -22s;
}

/* Banda que roza la base de la luna */
.hw-cloud--3 {
    left: 16%;
    top: 41%;
    width: 66%;
    height: 19%;
    --cloud-duration: 64s;
    animation-delay: -4s;
}

.hw-cloud--4 {
    right: 16%;
    top: 13%;
    width: 36%;
    height: 12%;
    --cloud-duration: 60s;
    animation-delay: -33s;
    opacity: 0.75;
}

/* ============================================================
   4. MURCIÉLAGOS
   ============================================================ */
.hw-bat {
    position: absolute;
    top: var(--bat-top, 20%);
    left: 0;
    width: var(--bat-size, 42px);
    color: var(--bat-color, #0a0614);
    filter: drop-shadow(0 0 6px rgba(190, 150, 255, 0.22));
    will-change: transform, opacity;
    opacity: 0;
    animation: batFly var(--bat-duration, 34s) linear infinite;
    animation-delay: var(--bat-delay, 0s);
}

.hw-bat__inner {
    display: block;
}

.hw-bat__inner svg {
    display: block;
    width: 100%;
    height: auto;
    animation: batFlap 0.55s ease-in-out infinite;
}

/* ============================================================
   5. TELARAÑAS
   ============================================================ */
.hw-web {
    position: absolute;
    top: 0;
    width: clamp(130px, 24vmin, 280px);
    height: auto;
    color: rgba(206, 196, 236, 0.55);
    opacity: 0.38;
    filter: drop-shadow(0 0 6px rgba(180, 150, 255, 0.15));
}

.hw-web--left {
    left: 0;
}

.hw-web--right {
    right: 0;
    transform: scaleX(-1);
}

/* ============================================================
   6. SUELO (colinas, hierba y hojas)
   ============================================================ */
.hw-ground {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 34%;
    z-index: 6;
    pointer-events: none;
}

/* Colinas: dos siluetas superpuestas con borde suave */
.hw-hills {
    position: absolute;
    top: 0;
    left: 0;
    display: block;
    width: 100%;
    height: 100%;
}

.hw-hills--back {
    z-index: 1;
}

.hw-hills--mid {
    z-index: 2;
}

/* Bruma en el horizonte: funde cielo y suelo */
.hw-ground__fog {
    position: absolute;
    left: -6%;
    right: -6%;
    top: -4%;
    height: 32%;
    background: radial-gradient(66% 100% at 50% 100%, rgba(122, 98, 172, 0.14) 0%, transparent 72%);
    filter: blur(18px);
}

/* Luz cálida que proyectan las calabazas sobre el terreno */
.hw-ground__lights {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(30% 62% at 12% 70%, rgba(255, 146, 36, 0.42) 0%, transparent 74%),
        radial-gradient(24% 54% at 88% 76%, rgba(255, 134, 28, 0.34) 0%, transparent 76%),
        radial-gradient(46% 40% at 50% 96%, rgba(255, 118, 18, 0.12) 0%, transparent 78%);
    filter: blur(14px);
}

.hw-ground__details {
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    width: 100%;
    height: 22%;
    opacity: 0.95;
}

/* ============================================================
   7. CALABAZAS
   ============================================================ */
.hw-pumpkin {
    position: absolute;
    z-index: 7;
    filter: drop-shadow(0 20px 28px rgba(0, 0, 0, 0.6));
    transform-box: fill-box;
}

.hw-pumpkin--left {
    left: 3%;
    bottom: 4%;
    width: clamp(150px, 22vmin, 250px);
}

.hw-pumpkin--right {
    right: 4%;
    bottom: 7%;
    width: clamp(122px, 18vmin, 205px);
}

.hw-pumpkin--small {
    left: 25%;
    bottom: 16%;
    width: clamp(80px, 11vmin, 124px);
    opacity: 0.92;
}

.hw-pumpkin--small .hw-pumpkin__face {
    opacity: 0.5;
}

.hw-pumpkin--small .hw-pumpkin__aura {
    opacity: 0.3;
}

/* Parpadeo: solo opacity (compuesto por GPU, sin repintar la calabaza) */
.hw-pumpkin--lit .hw-pumpkin__face,
.hw-pumpkin--lit .hw-pumpkin__aura {
    animation: pumpkinFlicker 6s ease-in-out infinite;
}

.hw-pumpkin--left.hw-pumpkin--lit .hw-pumpkin__face,
.hw-pumpkin--left.hw-pumpkin--lit .hw-pumpkin__aura {
    animation-delay: -1.4s;
}

.hw-pumpkin--right.hw-pumpkin--lit .hw-pumpkin__face,
.hw-pumpkin--right.hw-pumpkin--lit .hw-pumpkin__aura {
    animation-delay: -3.6s;
}

/* ============================================================
   8. VIÑETA Y GRANO (textura de película)
   ============================================================ */
.hw-vignette {
    position: absolute;
    inset: 0;
    z-index: 8;
    background:
        radial-gradient(ellipse at 50% 38%, transparent 38%, rgba(3, 2, 8, 0.42) 74%, rgba(3, 2, 8, 0.88) 100%);
}

/* Grano fotográfico: textura de 160x160 rasterizada una sola vez y repetida */
.hw-grain {
    position: absolute;
    inset: 0;
    z-index: 9;
    width: 100%;
    height: 100%;
    opacity: 0.16;
    mix-blend-mode: overlay;
    pointer-events: none;
    background-repeat: repeat;
    background-size: 160px 160px;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='160' height='160' filter='url(%23n)'/%3E%3C/svg%3E");
}

.hw-defs {
    position: absolute;
    width: 0;
    height: 0;
    overflow: hidden;
}

/* ============================================================
   9. CONTENIDO (logotipo, sombrero, progreso)
   ============================================================ */
.hw-content {
    position: absolute;
    inset: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: clamp(14px, 2.2vh, 24px);
    padding: 0 clamp(16px, 5vw, 48px) clamp(64px, 15vh, 180px);
    text-align: center;
}

.hw-emblem {
    position: relative;
    display: grid;
    place-items: center;
    width: min(70vw, 420px);
}

/* Resplandor violeta que separa el logo del cielo */
.hw-emblem::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 58%;
    width: 150%;
    height: 90%;
    transform: translate(-50%, -50%);
    background: radial-gradient(ellipse, rgba(120, 80, 220, 0.22) 0%, transparent 68%);
    filter: blur(18px);
    pointer-events: none;
}

.hw-hat {
    position: absolute;
    left: 32%;
    bottom: 60%;
    z-index: 2;
    width: clamp(118px, 21vmin, 196px);
    transform-origin: 50% 88%;
    filter: drop-shadow(0 16px 22px rgba(0, 0, 0, 0.6));
    pointer-events: none;
    animation: floatHat 6s ease-in-out infinite;
}

.hw-hat svg {
    display: block;
    width: 100%;
    height: auto;
}

.hw-logo {
    position: relative;
    display: block;
    width: 100%;
    height: auto;
    filter: drop-shadow(0 0 16px rgba(150, 108, 255, 0.4)) drop-shadow(0 10px 26px rgba(0, 0, 0, 0.7));
    animation: riseIn 0.9s 0.1s cubic-bezier(0.22, 0.7, 0.2, 1) both;
}

.hw-progress {
    position: relative;
    width: clamp(180px, 42vw, 300px);
    height: 4px;
    border-radius: 999px;
    background: rgba(255, 235, 205, 0.12);
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    overflow: hidden;
    animation: riseIn 0.8s 0.45s both;
}

.hw-progress__bar {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 0;
    border-radius: inherit;
    background: linear-gradient(90deg, #ffb347, #ff8a2b 48%, #c084fc);
    box-shadow: 0 0 12px rgba(255, 170, 70, 0.55), 0 0 26px rgba(192, 132, 252, 0.35);
    animation: progressFill var(--hw-duration, 4500ms) linear forwards;
}

.hw-status {
    margin: 0;
    font-size: clamp(10px, 1.5vmin, 13px);
    font-weight: 600;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: rgba(255, 238, 214, 0.72);
    animation: riseIn 0.8s 0.55s both;
}

.hw-dots i {
    display: inline-block;
    font-style: normal;
    animation: dotPulse 1.6s ease-in-out infinite;
}

.hw-dots i:nth-child(2) {
    animation-delay: 0.2s;
}

.hw-dots i:nth-child(3) {
    animation-delay: 0.4s;
}

/* ============================================================
   10. ANIMACIONES (@keyframes)
   ============================================================ */

/* Nubes: desplazamiento lento y alterno (sin saltos) */
@keyframes cloudDrift {
    from { transform: translate3d(-4.5%, 0, 0); }
    to   { transform: translate3d(4.5%, 1.2%, 0); }
}

/* Murciélagos */
@keyframes batFly {
    0%   { transform: translate3d(-16vw, 0, 0); opacity: 0; }
    10%  { opacity: var(--bat-opacity, 0.8); }
    88%  { opacity: var(--bat-opacity, 0.8); }
    100% { transform: translate3d(114vw, -3vh, 0); opacity: 0; }
}


@keyframes batFlap {
    0%, 100% { transform: scaleY(1); }
    50%      { transform: scaleY(0.72); }
}

/* Sombrero flotante */
@keyframes floatHat {
    0%, 100% { transform: translate(-50%, 0) rotate(-5deg); }
    50%      { transform: translate(-50%, -12px) rotate(2deg); }
}

/* Calabazas encendiéndose y apagándose */
@keyframes pumpkinFlicker {
    0%, 100% { opacity: 0.66; }
    32%      { opacity: 1; }
    52%      { opacity: 0.8; }
    70%      { opacity: 1; }
}


/* Entrada del contenido */
@keyframes riseIn {
    from { opacity: 0; transform: translateY(18px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Barra de progreso */
@keyframes progressFill {
    from { width: 0; }
    to   { width: 100%; }
}

@keyframes dotPulse {
    0%, 100% { opacity: 0.25; }
    50%      { opacity: 1; }
}

/* ============================================================
   11. RESPONSIVE
   ============================================================ */
@media (max-width: 768px) {
    .hw-pumpkin--small {
        display: none;
    }

    .hw-ground {
        height: 40%;
    }

    .hw-pumpkin--left {
        left: 1%;
        bottom: 4%;
        width: clamp(110px, 27vmin, 155px);
    }

    .hw-pumpkin--right {
        right: 2%;
        bottom: 8%;
        width: clamp(94px, 23vmin, 134px);
    }

    .hw-emblem {
        width: min(80vw, 340px);
    }

    .hw-hat {
        left: 27%;
        bottom: 64%;
        width: clamp(96px, 24vmin, 132px);
    }

    .hw-content {
        padding-bottom: clamp(48px, 13vh, 150px);
    }

    .hw-web {
        width: clamp(96px, 26vmin, 150px);
    }
}

@media (max-height: 520px) and (orientation: landscape) {
    .hw-content {
        padding-bottom: 24px;
        gap: 10px;
    }

    .hw-emblem {
        width: min(46vw, 300px);
    }

    .hw-hat {
        width: clamp(78px, 15vmin, 110px);
    }

    .hw-moon {
        width: clamp(140px, 30vmin, 200px);
        top: 5%;
    }

    .hw-pumpkin--left {
        width: clamp(80px, 16vmin, 120px);
    }

    .hw-pumpkin--right {
        width: clamp(70px, 14vmin, 100px);
    }
}

/* Accesibilidad: respeta la preferencia de movimiento reducido */
@media (prefers-reduced-motion: reduce) {
    .hw-cloud,
    .hw-bat,
    .hw-hat,
    .hw-pumpkin--lit .hw-pumpkin__face,
    .hw-pumpkin--lit .hw-pumpkin__aura,
    .hw-logo,
    .hw-dots i,
    .hw-progress__bar {
        animation: none !important;
    }

    .hw-bat {
        display: none;
    }

    .hw-progress__bar {
        width: 100%;
    }
}
</style>
