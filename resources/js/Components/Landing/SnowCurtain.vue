<script setup>
/**
 * Cortina de nieve para la salida de la portada navideña.
 *
 * Se monta una sola vez en `app.js`, junto a `PageCurtain` y
 * `CempasuchilCurtain`, para sobrevivir a la navegación de Inertia. El estado
 * lo controla `playSnowTransition()` (ver `snowTransition.js`).
 */
import { snowState as state, snowTiming as timing } from './snowTransition';

const COLUMNS = 12;
const ROWS = 8;

/* Jitter determinista: mismo reparto en cada transición, sin Math.random. */
const jitter = (index, salt = 1) => ((index * 37 * salt) % 17) / 17 - 0.5;

const flakes = [];

for (let row = 0; row < ROWS; row += 1) {
    for (let column = 0; column < COLUMNS; column += 1) {
        const index = row * COLUMNS + column;
        const crystal = index % 9 === 0;

        flakes.push({
            key: index,
            crystal,
            x: `${((column + 0.5) / COLUMNS) * 100 + jitter(index, 1) * 3.4}%`,
            y: `${((row + 0.5) / ROWS) * 100 + jitter(index, 2) * 3}%`,
            size: crystal ? `${9 + (index % 4) * 3}px` : `${3 + (index % 4) * 1.4}px`,
            delay: `${(row * 42 + column * 24) % 420}ms`,
            revealDelay: `${((ROWS - 1 - row) * 36 + column * 22) % 620}ms`,
            drift: `${((index * 11) % 10) - 5}vw`,
            opacity: (0.55 + ((index * 7) % 45) / 100).toFixed(2),
        });
    }
}
</script>

<template>
    <div
        v-if="state !== 'idle'"
        class="snow-curtain"
        :data-state="state"
        :style="timing"
        aria-hidden="true"
    >
        <div class="snow-curtain__wash"></div>

        <template v-for="flake in flakes" :key="flake.key">
            <svg
                v-if="flake.crystal"
                class="snow-curtain__flake snow-curtain__flake--crystal"
                viewBox="0 0 24 24"
                :style="{
                    '--sn-x': flake.x,
                    '--sn-y': flake.y,
                    '--sn-size': flake.size,
                    '--sn-delay': flake.delay,
                    '--sn-reveal-delay': flake.revealDelay,
                    '--sn-drift': flake.drift,
                    '--sn-opacity': flake.opacity,
                }"
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
                class="snow-curtain__flake snow-curtain__flake--dot"
                :style="{
                    '--sn-x': flake.x,
                    '--sn-y': flake.y,
                    '--sn-size': flake.size,
                    '--sn-delay': flake.delay,
                    '--sn-reveal-delay': flake.revealDelay,
                    '--sn-drift': flake.drift,
                    '--sn-opacity': flake.opacity,
                }"
            ></span>
        </template>
    </div>
</template>

<style scoped>
.snow-curtain {
    position: fixed;
    inset: 0;
    z-index: 10000;
    overflow: hidden;
    pointer-events: auto;
}

.snow-curtain__wash {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(74% 66% at 50% 42%, #16365c 0%, #0a1a33 54%, #050d1c 100%);
    opacity: 0;
}

.snow-curtain[data-state='cover'] .snow-curtain__wash {
    animation: snWashCover var(--sn-cover, 1100ms) ease-out forwards;
}

.snow-curtain[data-state='reveal'] .snow-curtain__wash {
    animation: snWashReveal var(--sn-reveal, 1000ms) ease-in forwards;
}

.snow-curtain__flake {
    position: absolute;
    top: var(--sn-y, 50%);
    left: var(--sn-x, 50%);
    width: var(--sn-size, 4px);
    height: var(--sn-size, 4px);
    margin-left: calc(var(--sn-size, 4px) / -2);
    opacity: 0;
    transform: translate3d(0, -135vh, 0);
    will-change: transform, opacity;
}

.snow-curtain__flake--dot {
    border-radius: 50%;
    background: radial-gradient(circle, #ffffff 0%, rgba(255, 255, 255, 0.92) 48%, rgba(255, 255, 255, 0) 74%);
    box-shadow: 0 0 7px rgba(220, 238, 255, 0.45);
}

.snow-curtain__flake--crystal {
    color: rgba(255, 255, 255, 0.9);
    filter: drop-shadow(0 0 4px rgba(215, 236, 255, 0.55));
}

.snow-curtain[data-state='cover'] .snow-curtain__flake {
    animation: snFlakeCover var(--sn-cover, 1100ms) cubic-bezier(0.22, 0.7, 0.2, 1) var(--sn-delay, 0ms) forwards;
}

.snow-curtain[data-state='reveal'] .snow-curtain__flake {
    animation: snFlakeReveal var(--sn-reveal, 1000ms) cubic-bezier(0.55, 0.06, 0.68, 0.19) var(--sn-reveal-delay, 0ms) forwards;
}

@keyframes snWashCover {
    from {
        opacity: 0;
    }
    to {
        opacity: 0.97;
    }
}

@keyframes snWashReveal {
    from {
        opacity: 0.97;
    }
    to {
        opacity: 0;
    }
}

@keyframes snFlakeCover {
    0% {
        transform: translate3d(0, -135vh, 0);
        opacity: 0;
    }
    16% {
        opacity: var(--sn-opacity, 0.75);
    }
    100% {
        transform: translate3d(0, 0, 0);
        opacity: var(--sn-opacity, 0.75);
    }
}

@keyframes snFlakeReveal {
    0% {
        transform: translate3d(0, 0, 0);
        opacity: var(--sn-opacity, 0.75);
    }
    100% {
        transform: translate3d(var(--sn-drift, 0), 135vh, 0);
        opacity: 0;
    }
}

@media (prefers-reduced-motion: reduce) {
    .snow-curtain {
        display: none;
    }
}
</style>
