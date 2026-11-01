<script setup>
/**
 * Tarjeta de un proyecto propio (gratis para la comunidad).
 *
 * Se usa dentro del carrusel `OwnProjectsRail.vue`. A diferencia de las
 * tarjetas de cliente, no hay ficha interna: el botón abre el sitio o
 * repositorio externo guardado en el back-office (`link_url`).
 */
import { computed } from 'vue';
import { useT } from '@/Composables/useT';
import { trackCtaClick } from '@/utils/analytics';

const props = defineProps({
    project: { type: Object, required: true },
    index: { type: Number, default: 0 },
});

const t = useT();

const linkLabel = computed(() => props.project.linkText || t('View project'));

function onOpen() {
    trackCtaClick(`own_project_${props.project.id}`);
}
</script>

<template>
    <article
        class="group relative flex h-full w-[78vw] max-w-[420px] shrink-0 snap-start flex-col overflow-hidden rounded-[26px] border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.015] shadow-[0_28px_70px_-50px_rgba(0,0,0,0.95)] transition duration-500 hover:-translate-y-1.5 hover:border-white/20"
    >
        <!-- Imagen -->
        <div class="relative aspect-[16/10] overflow-hidden bg-dtw-surface">
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
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-dtw-cyan/20 via-dtw-surface-soft to-dtw-void"
            >
                <img src="/images/white_logo.png" alt="" class="h-9 w-auto opacity-40" />
            </div>

            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-dtw-void/55 via-transparent to-transparent"
            ></div>

            <!-- Gratis para la comunidad -->
            <span
                class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full border border-dtw-cyan/40 bg-dtw-void/70 px-3 py-1 text-[0.62rem] font-semibold uppercase tracking-[0.18em] text-dtw-cyan backdrop-blur"
            >
                <i class="pi pi-gift text-[0.7rem]"></i>
                {{ t('Free') }}
            </span>

            <span
                class="pointer-events-none absolute right-4 top-4 font-display text-[0.7rem] font-bold tracking-[0.3em] text-white/60"
            >
                {{ String(index + 1).padStart(2, '0') }}
            </span>
        </div>

        <!-- Contenido -->
        <div class="flex flex-1 flex-col gap-3 px-5 py-5 md:px-6">
            <h3 class="line-clamp-2 font-display text-lg font-bold leading-snug text-white md:text-xl">
                {{ project.title }}
            </h3>

            <p
                v-if="project.subtitle"
                class="text-[0.65rem] font-semibold uppercase tracking-[0.22em] text-dtw-primary-soft"
            >
                {{ project.subtitle }}
            </p>

            <p v-if="project.description" class="line-clamp-3 text-sm leading-relaxed text-dtw-muted">
                {{ project.description }}
            </p>

            <a
                v-if="project.url"
                :href="project.url"
                target="_blank"
                rel="noopener"
                class="mt-auto inline-flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-4 py-2.5 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.07]"
                @click="onOpen"
            >
                {{ linkLabel }}
                <i
                    class="pi pi-arrow-up-right text-xs transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
                ></i>
            </a>
        </div>
    </article>
</template>
