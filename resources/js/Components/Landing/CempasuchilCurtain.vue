<script setup>
/**
 * Cortina de pétalos de cempasúchil para la salida de la portada temática.
 *
 * Se monta una sola vez en `app.js`, junto a `PageCurtain`, para sobrevivir a
 * la navegación de Inertia. El estado lo controla
 * `playCempasuchilTransition()` (ver `cempasuchilTransition.js`).
 */
import { cempasuchilState as state, cempasuchilTiming as timing } from './cempasuchilTransition';

const COLUMNS = 8;
const ROWS = 7;

/* Jitter determinista: mismo reparto en cada transición, sin Math.random. */
const jitter = (index, salt = 1) => ((index * 37 * salt) % 17) / 17 - 0.5;

const petals = [];

for (let row = 0; row < ROWS; row += 1) {
    for (let column = 0; column < COLUMNS; column += 1) {
        const index = row * COLUMNS + column;

        petals.push({
            key: index,
            tone: (column + row * 2) % 5 === 0 ? 'rose' : 'marigold',
            x: `${((column + 0.5) / COLUMNS) * 100 + jitter(index, 1) * 4}%`,
            y: `${((row + 0.5) / ROWS) * 100 - 8 + jitter(index, 2) * 5}%`,
            size: `${4.5 + ((index * 3) % 5) * 0.9}vmax`,
            rotate: `${(index * 67) % 360}deg`,
            delay: `${(row * 70 + column * 38) % 520}ms`,
            revealDelay: `${((ROWS - 1 - row) * 40 + column * 26) % 700}ms`,
        });
    }
}
</script>

<template>
    <div
        v-if="state !== 'idle'"
        class="cem-curtain"
        :data-state="state"
        :style="timing"
        aria-hidden="true"
    >
        <div class="cem-curtain__wash"></div>

        <span
            v-for="petal in petals"
            :key="petal.key"
            class="cem-curtain__petal"
            :class="`cem-curtain__petal--${petal.tone}`"
            :style="{
                '--cem-x': petal.x,
                '--cem-y': petal.y,
                '--cem-size': petal.size,
                '--cem-rotate': petal.rotate,
                '--cem-delay': petal.delay,
                '--cem-reveal-delay': petal.revealDelay,
            }"
        ></span>
    </div>
</template>

<style scoped>
.cem-curtain {
    position: fixed;
    inset: 0;
    z-index: 10000;
    overflow: hidden;
    pointer-events: auto;
}

.cem-curtain__wash {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(72% 68% at 50% 55%, #7a2540 0%, #3a1245 46%, #150822 100%);
    opacity: 0;
}

.cem-curtain[data-state='cover'] .cem-curtain__wash {
    animation: cemWashCover var(--cem-cover, 900ms) ease-out forwards;
}

.cem-curtain[data-state='reveal'] .cem-curtain__wash {
    animation: cemWashReveal var(--cem-reveal, 980ms) ease-in forwards;
}

.cem-curtain__petal {
    position: absolute;
    left: var(--cem-x);
    top: var(--cem-y);
    width: var(--cem-size);
    height: calc(var(--cem-size) * 1.55);
    margin-left: calc(var(--cem-size) / -2);
    border-radius: 100% 0 100% 0 / 100% 0 100% 0;
    opacity: 0;
    transform: translate3d(0, 115vh, 0) rotate(var(--cem-rotate));
    will-change: transform, opacity;
}

.cem-curtain__petal--marigold {
    background: radial-gradient(120% 120% at 32% 22%, #ffe9a8 0%, #ffbe0b 40%, #f77f00 76%, #cf4f00 100%);
    box-shadow: inset -2px -3px 6px rgba(120, 34, 0, 0.35);
}

.cem-curtain__petal--rose {
    background: radial-gradient(120% 120% at 32% 22%, #ffe0ea 0%, #ff8fab 38%, #e63975 74%, #8f1b3f 100%);
    box-shadow: inset -2px -3px 6px rgba(90, 12, 40, 0.35);
}

.cem-curtain[data-state='cover'] .cem-curtain__petal {
    animation: cemPetalCover var(--cem-cover, 900ms) cubic-bezier(0.22, 0.7, 0.2, 1) var(--cem-delay) forwards;
}

.cem-curtain[data-state='reveal'] .cem-curtain__petal {
    animation: cemPetalReveal var(--cem-reveal, 980ms) cubic-bezier(0.55, 0.06, 0.68, 0.19) var(--cem-reveal-delay) forwards;
}

@keyframes cemWashCover {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes cemWashReveal {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

@keyframes cemPetalCover {
    0% {
        transform: translate3d(0, 115vh, 0) rotate(var(--cem-rotate));
        opacity: 0;
    }
    18% {
        opacity: 1;
    }
    100% {
        transform: translate3d(0, 0, 0) rotate(var(--cem-rotate));
        opacity: 1;
    }
}

@keyframes cemPetalReveal {
    0% {
        transform: translate3d(0, 0, 0) rotate(var(--cem-rotate));
        opacity: 1;
    }
    100% {
        transform: translate3d(0, -115vh, 0) rotate(calc(var(--cem-rotate) + 200deg));
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .cem-curtain {
        display: none;
    }
}
</style>
