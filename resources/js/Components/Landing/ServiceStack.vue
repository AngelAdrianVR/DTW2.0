<script setup>
/**
 * Bloque "Servicios" (parte 2): tarjetas apiladas.
 *
 * Cada solución se presenta en una tarjeta que se queda pegada arriba
 * mientras entra la siguiente: la tarjeta anterior se encoge y se atenúa
 * ligeramente, dando profundidad al scroll (scroll-stacked cards).
 *
 * En móvil o con prefers-reduced-motion las tarjetas fluyen normalmente.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useSolutions } from '@/Composables/useSolutions';
import { useContact } from '@/Composables/useContact';
import { useT } from '@/Composables/useT';
import { trackWhatsAppClick } from '@/utils/analytics';

const STACK_TOP = 88;
const STACK_OFFSET = 14;

const t = useT();
const { whatsappUrl } = useContact();
const solutions = useSolutions();

const listRef = ref(null);
const stacked = ref(false);

let frame = null;
let mediaQuery = null;
let reducedQuery = null;

function apply() {
    frame = null;

    const list = listRef.value;

    if (!list) {
        return;
    }

    const items = Array.from(list.children);

    items.forEach((item, index) => {
        const card = item.querySelector('[data-stack-card]');

        if (!card) {
            return;
        }

        if (!stacked.value) {
            card.style.transform = '';
            card.style.opacity = '';
            return;
        }

        const next = items[index + 1];
        let covered = 0;

        if (next) {
            const nextTop = next.getBoundingClientRect().top;
            const span = Math.max(1, window.innerHeight * 0.7);

            covered = Math.min(1, Math.max(0, (STACK_TOP + span - nextTop) / span));
        }

        const scale = 1 - covered * 0.055;
        const shift = -covered * 18;
        const opacity = 1 - covered * 0.45;

        card.style.transform = `translate3d(0, ${shift.toFixed(2)}px, 0) scale(${scale.toFixed(4)})`;
        card.style.opacity = opacity.toFixed(3);
    });
}

function onScroll() {
    if (frame !== null) {
        return;
    }

    frame = requestAnimationFrame(apply);
}

function syncMode() {
    stacked.value = Boolean(mediaQuery?.matches) && !Boolean(reducedQuery?.matches);
    onScroll();
}

function itemStyle(index) {
    if (!stacked.value) {
        return { position: 'relative', zIndex: index + 1 };
    }

    return {
        position: 'sticky',
        top: `${STACK_TOP + index * STACK_OFFSET}px`,
        zIndex: index + 1,
    };
}

onMounted(() => {
    mediaQuery = window.matchMedia('(min-width: 1024px)');
    reducedQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    syncMode();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', syncMode);
    mediaQuery.addEventListener?.('change', syncMode);
    reducedQuery.addEventListener?.('change', syncMode);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', syncMode);
    mediaQuery?.removeEventListener?.('change', syncMode);
    reducedQuery?.removeEventListener?.('change', syncMode);

    if (frame !== null) {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <section id="detalle-servicios" class="relative bg-transparent pb-24 pt-10 md:pb-32">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-dtw-void to-transparent"></div>

        <div class="relative mx-auto w-full max-w-6xl px-5 md:px-8">
            <div class="max-w-2xl" v-reveal>
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-cyan/80">
                    {{ t('What we solve') }}
                </p>
                <h2
                    class="mt-4 font-display text-[clamp(1.6rem,3.2vw,2.5rem)] font-extrabold leading-[1.1] tracking-tight text-white"
                >
                    {{ t('What each one solves for you') }}
                </h2>
            </div>

            <div ref="listRef" class="mt-12 space-y-6 lg:mt-16 lg:space-y-0">
                <div
                    v-for="(solution, index) in solutions"
                    :key="solution.slug"
                    :style="itemStyle(index)"
                    class="lg:h-auto"
                >
                    <article
                        data-stack-card
                        class="relative overflow-hidden rounded-[28px] border border-white/10 bg-dtw-surface shadow-[0_40px_120px_-70px_rgba(0,0,0,1)]"
                    >
                        <div
                            class="pointer-events-none absolute inset-0"
                            :style="{
                                background: `radial-gradient(90% 70% at 15% 0%, ${solution.accent}1f, transparent 65%)`,
                            }"
                        ></div>
                        <div
                            class="pointer-events-none absolute inset-x-0 top-0 h-px"
                            :style="{
                                background: `linear-gradient(to right, transparent, ${solution.accent}66, transparent)`,
                            }"
                        ></div>

                        <div
                            class="relative grid gap-8 p-6 md:grid-cols-[1.05fr_0.95fr] md:items-center md:gap-10 md:p-10 lg:p-12"
                        >
                            <div>
                                <div class="flex items-center gap-3">
                                    <span
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/[0.04]"
                                        :style="{ color: solution.accent }"
                                    >
                                        <i :class="solution.icon"></i>
                                    </span>
                                    <span class="font-display text-xs font-bold tracking-[0.32em] text-white/35">
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                </div>

                                <h3 class="mt-5 font-display text-2xl font-bold leading-tight text-white md:text-[1.75rem]">
                                    {{ solution.title }}
                                </h3>

                                <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-dtw-muted md:text-[0.95rem]">
                                    {{ solution.summary }}
                                </p>

                                <ul class="mt-6 space-y-2.5">
                                    <li
                                        v-for="bullet in solution.bullets"
                                        :key="bullet"
                                        class="flex items-start gap-3 text-sm text-dtw-ink/90"
                                    >
                                        <i class="pi pi-check mt-1 text-[0.65rem]" :style="{ color: solution.accent }"></i>
                                        <span>{{ bullet }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="relative">
                                <div class="overflow-hidden rounded-2xl border border-white/10">
                                    <img
                                        :src="solution.image"
                                        :alt="solution.title"
                                        loading="lazy"
                                        class="aspect-[4/3] h-full w-full object-cover opacity-90 transition duration-700 hover:scale-[1.03] hover:opacity-100"
                                    />
                                </div>

                                <div class="mt-6 flex flex-wrap items-center gap-3">
                                    <a
                                        :href="whatsappUrl"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-full bg-white/[0.06] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/[0.12]"
                                        @click="trackWhatsAppClick(`stack_${solution.slug}`)"
                                    >
                                        <i class="pi pi-whatsapp" :style="{ color: solution.accent }"></i>
                                        {{ t('Talk to us about this solution') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>
