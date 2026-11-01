<script setup>
/**
 * Carrusel de proyectos propios (gratis para la comunidad).
 *
 * Se muestra debajo de la galería de clientes. El desplazamiento es nativo
 * (scroll + snap) para que funcione igual con trackpad, rueda horizontal,
 * teclado y gestos táctiles; las flechas y los puntos sólo lo acompañan.
 */
import { computed, ref } from 'vue';
import OwnProjectCard from './OwnProjectCard.vue';
import { useOwnProjects } from '@/Composables/useOwnProjects';
import { useT } from '@/Composables/useT';

const props = defineProps({
    projects: { type: Array, default: () => [] },
    limit: { type: Number, default: 8 },
});

const t = useT();
const projects = useOwnProjects(props.projects);

const cards = computed(() => projects.value.slice(0, Math.max(0, props.limit)));
const total = computed(() => cards.value.length);

const trackRef = ref(null);
const currentIndex = ref(0);

/** Sincroniza puntos y flechas con la posición real del carrusel. */
function onTrackScroll() {
    const track = trackRef.value;

    if (!track) {
        return;
    }

    let best = 0;
    let bestDistance = Number.POSITIVE_INFINITY;

    Array.from(track.children).forEach((child, index) => {
        const distance = Math.abs(child.offsetLeft - 20 - track.scrollLeft);

        if (distance < bestDistance) {
            bestDistance = distance;
            best = index;
        }
    });

    currentIndex.value = best;
}

function goToProject(index) {
    const track = trackRef.value;
    const card = track?.children[index];

    if (!track || !card) {
        return;
    }

    track.scrollTo({ left: card.offsetLeft - 20, behavior: 'smooth' });
}

function step(direction) {
    goToProject(Math.min(Math.max(currentIndex.value + direction, 0), total.value - 1));
}

const canGoBack = computed(() => currentIndex.value > 0);
const canGoForward = computed(() => currentIndex.value < total.value - 1);
</script>

<template>
    <section id="proyectos-propios" class="relative overflow-hidden py-16 md:py-20">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -left-32 top-6 h-72 w-72 rounded-full bg-dtw-cyan/10 blur-[120px]"
        ></div>

        <div class="relative mx-auto w-full max-w-7xl px-5 md:px-8">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between" v-reveal>
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-cyan/80">
                        {{ t('Community projects') }}
                    </p>

                    <h2
                        class="mt-4 font-display text-[clamp(1.6rem,3.4vw,2.6rem)] font-extrabold leading-tight tracking-[-0.02em] text-white"
                    >
                        {{ t('Our own projects, free for the community') }}
                    </h2>

                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-dtw-muted md:text-base">
                        {{ t('Tools we build and release for anyone who needs them.') }}
                    </p>
                </div>

                <div v-if="total > 1" class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/35 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-30"
                        :disabled="!canGoBack"
                        :aria-label="t('Previous')"
                        @click="step(-1)"
                    >
                        <i class="pi pi-arrow-left text-xs"></i>
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/35 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-30"
                        :disabled="!canGoForward"
                        :aria-label="t('Next')"
                        @click="step(1)"
                    >
                        <i class="pi pi-arrow-right text-xs"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Carrusel -->
        <div
            v-if="total"
            class="dtw-no-scrollbar relative mt-10 w-full snap-x snap-mandatory overflow-x-auto pb-3"
        >
            <div
                ref="trackRef"
                class="flex items-stretch gap-4 px-5 md:gap-6 md:px-8"
                @scroll.passive="onTrackScroll"
            >
                <OwnProjectCard
                    v-for="(project, index) in cards"
                    :key="project.id"
                    :project="project"
                    :index="index"
                />
            </div>
        </div>

        <!-- Sin proyectos propios publicados -->
        <div v-else class="mx-auto w-full max-w-7xl px-5 md:px-8">
            <div
                class="mt-10 rounded-[26px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-12 text-center"
            >
                <i class="pi pi-heart text-2xl text-dtw-cyan/70"></i>
                <p class="mt-3 text-sm text-dtw-muted">
                    {{ t('Our own projects are on their way.') }}
                </p>
            </div>
        </div>

        <!-- Puntos -->
        <div
            v-if="total > 1"
            class="mx-auto mt-6 flex w-full max-w-7xl items-center justify-center gap-2 px-5 md:px-8"
        >
            <button
                v-for="(project, index) in cards"
                :key="`dot-${project.id}`"
                type="button"
                class="h-1.5 rounded-full transition-all duration-300"
                :class="index === currentIndex ? 'w-8 bg-dtw-primary' : 'w-3 bg-white/25 hover:bg-white/50'"
                :aria-label="t('Go to community project :number', { number: index + 1 })"
                :aria-current="index === currentIndex ? 'true' : undefined"
                @click="goToProject(index)"
            ></button>
        </div>
    </section>
</template>
