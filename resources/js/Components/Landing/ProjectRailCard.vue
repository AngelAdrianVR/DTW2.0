<script setup>
/**
 * Tarjeta de proyecto para el riel horizontal de la landing.
 *
 * La imagen es la protagonista (sin descripción dentro del marco) y el pie
 * conserva el título del proyecto junto al enlace a su ficha.
 */
import { Link } from '@inertiajs/vue3';
import { useT } from '@/Composables/useT';

defineProps({
    project: { type: Object, required: true },
    index: { type: Number, default: 0 },
});

const t = useT();
</script>

<template>
    <article
        class="group relative flex h-[clamp(360px,54vh,600px)] w-[84vw] max-w-[820px] shrink-0 flex-col overflow-hidden rounded-[28px] border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.015] shadow-[0_30px_80px_-50px_rgba(0,0,0,0.95)] transition duration-500 hover:-translate-y-1.5 hover:border-white/20"
    >
        <!-- Imagen grande -->
        <Link
            :href="project.href"
            class="relative block min-h-0 flex-1 overflow-hidden bg-dtw-surface"
            :aria-label="project.title"
            tabindex="-1"
        >
            <img
                v-if="project.image"
                :src="project.image"
                :alt="project.title"
                loading="lazy"
                draggable="false"
                class="h-full w-full object-cover transition duration-[900ms] ease-out group-hover:scale-[1.05]"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-dtw-brand/30 via-dtw-surface-soft to-dtw-void"
            >
                <img src="/images/white_logo.png" alt="" class="h-10 w-auto opacity-40" />
            </div>

            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-dtw-void/55 via-transparent to-transparent"
            ></div>

            <span
                class="pointer-events-none absolute right-6 top-6 font-display text-xs font-bold tracking-[0.32em] text-white/60"
            >
                {{ String(index + 1).padStart(2, '0') }}
            </span>
        </Link>

        <!-- Pie: título + enlace a la ficha -->
        <div
            class="flex shrink-0 items-center justify-between gap-4 border-t border-white/[0.06] px-6 py-5 md:px-7"
        >
            <h3 class="line-clamp-2 font-display text-lg font-bold leading-snug text-white md:text-xl">
                {{ project.title }}
            </h3>

            <Link
                :href="project.href"
                class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-dtw-primary-soft transition hover:text-white"
            >
                {{ t('View solution') }}
                <i class="pi pi-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </Link>
        </div>
    </article>
</template>
