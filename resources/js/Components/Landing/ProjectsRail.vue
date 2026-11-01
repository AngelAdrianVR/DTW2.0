<script setup>
/**
 * Bloque "Proyectos" (parte 1): riel horizontal anclado.
 *
 * El scroll vertical de la sección mueve las tarjetas a la derecha mientras la
 * sección queda pegada a la pantalla (con `factor: 1` el riel avanza al mismo
 * ritmo que el scroll vertical, sin obligar a bajar de más). Arriba a la
 * derecha se mantiene visible el acceso a la galería completa de proyectos.
 *
 * Las tarjetas son los proyectos publicados en el back-office (WebContent tipo
 * `portfolio`) que llegan por la prop `projects`; sólo se pintan las primeras
 * `limit` (5 por defecto) para no alargar la landing en exceso.
 *
 * En móvil (<1024px) o con prefers-reduced-motion se degrada a un carrusel
 * con scroll-snap nativo, sin anclaje.
 */
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProjectRailCard from './ProjectRailCard.vue';
import { useProjects } from '@/Composables/useProjects';
import { useHorizontalPin } from '@/Composables/useHorizontalPin';
import { useT } from '@/Composables/useT';

const props = defineProps({
    /** Proyectos realizados (portfolio) enviados por LandingController. */
    projects: { type: Array, default: () => [] },
    /** Tarjetas visibles en la landing; el resto sigue accesible por su ficha. */
    limit: { type: Number, default: 5 },
});

const t = useT();
const allCards = useProjects(props.projects);

// Sólo las primeras tarjetas: con diez o más proyectos el recorrido anclado
// obligaba a bajar demasiado antes de llegar a la siguiente sección.
const cards = computed(() => allCards.value.slice(0, Math.max(0, props.limit)));

const sectionRef = ref(null);
const trackRef = ref(null);
const mobileIndex = ref(0);

const { progress, offset, pinned, sectionHeight, scrollToProgress } = useHorizontalPin(
    sectionRef,
    trackRef,
    // `factor: 1` = el riel avanza 1 px por cada px de scroll vertical. Subirlo
    // alarga el recorrido (más lento); bajarlo (0.75) lo acelera.
    { padding: 48, factor: 1 },
);

const total = computed(() => cards.value.length);

const pinnedIndex = computed(() => {
    if (!total.value) {
        return 0;
    }

    return Math.min(total.value - 1, Math.round(progress.value * (total.value - 1)));
});

const currentIndex = computed(() => (pinned.value ? pinnedIndex.value : mobileIndex.value));

function goToProject(index) {
    if (!total.value) {
        return;
    }

    if (pinned.value) {
        scrollToProgress(total.value > 1 ? index / (total.value - 1) : 0);
        return;
    }

    const track = trackRef.value;
    const card = track?.children?.[index];

    if (track && card) {
        track.scrollTo({ left: card.offsetLeft - 20, behavior: 'smooth' });
    }
}

function step(direction) {
    if (pinned.value) {
        goToProject(Math.min(total.value - 1, Math.max(0, pinnedIndex.value + direction)));
        return;
    }

    const track = trackRef.value;

    if (track) {
        track.scrollBy({ left: direction * track.clientWidth * 0.85, behavior: 'smooth' });
    }
}

/** Sincroniza los puntos con el carrusel nativo (móvil). */
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

    mobileIndex.value = best;
}
</script>

<template>
    <section
        id="proyectos"
        ref="sectionRef"
        class="relative bg-transparent"
        :style="sectionHeight ? { height: sectionHeight } : undefined"
    >
        <div
            :class="
                pinned
                    ? 'sticky top-0 flex h-[100svh] flex-col justify-center overflow-hidden'
                    : 'flex flex-col justify-center py-20 md:py-24'
            "
        >
            <!-- Encabezado -->
            <div class="mx-auto w-full max-w-7xl px-5 pb-7 md:px-8 md:pb-10">
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div class="max-w-2xl" v-reveal>
                        <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft">
                            {{ t('Projects') }}
                        </p>
                        <h2
                            class="mt-4 font-display text-[clamp(1.7rem,3.6vw,2.8rem)] font-extrabold leading-[1.08] tracking-tight text-white"
                        >
                            {{ t('Projects built for our clients') }}
                        </h2>
                    </div>

                    <Link
                        href="/proyectos"
                        class="group inline-flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-6 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.07]"
                    >
                        {{ t('See all projects') }}
                        <i class="pi pi-arrow-up-right text-xs transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5"></i>
                    </Link>
                </div>
            </div>

            <!-- Riel anclado (desktop) -->
            <div v-if="pinned" class="relative w-full overflow-hidden">
                <div
                    ref="trackRef"
                    class="dtw-pin-track flex items-stretch gap-6 pl-5 pr-8 md:pl-8"
                    :style="{ transform: `translate3d(${offset}px, 0, 0)` }"
                >
                    <ProjectRailCard
                        v-for="(card, index) in cards"
                        :key="card.id"
                        :project="card"
                        :index="index"
                    />
                </div>
            </div>

            <!-- Carrusel nativo (móvil / reduced motion) -->
            <div v-else class="dtw-no-scrollbar relative w-full snap-x snap-mandatory overflow-x-auto">
                <div
                    ref="trackRef"
                    class="relative flex items-stretch gap-4 px-5 pb-2 md:px-8"
                    @scroll.passive="onTrackScroll"
                >
                    <ProjectRailCard
                        v-for="(card, index) in cards"
                        :key="card.id"
                        :project="card"
                        :index="index"
                    />
                </div>
            </div>

            <!-- Estado vacío (sin proyectos publicados) -->
            <div v-if="!total" class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <div
                    class="rounded-[28px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-14 text-center text-sm text-dtw-muted"
                >
                    {{ t('Our projects are on their way.') }}
                </div>
            </div>

            <!-- Controles -->
            <div v-if="total" class="mx-auto mt-7 w-full max-w-7xl px-5 md:mt-9 md:px-8">
                <div class="flex items-center gap-5">
                    <div class="flex items-center gap-1.5">
                        <button
                            v-for="(card, index) in cards"
                            :key="`dot-${card.id}`"
                            type="button"
                            class="h-1.5 rounded-full transition-all duration-300"
                            :class="currentIndex === index ? 'w-8 bg-dtw-primary' : 'w-1.5 bg-white/25 hover:bg-white/50'"
                            :aria-label="t('Go to project :number', { number: index + 1 })"
                            :aria-current="currentIndex === index ? 'true' : undefined"
                            @click="goToProject(index)"
                        ></button>
                    </div>

                    <div class="h-px flex-1 bg-white/10">
                        <div
                            class="h-px bg-gradient-to-r from-dtw-primary via-dtw-primary-soft to-dtw-cyan"
                            :style="{
                                width: `${Math.max(
                                    4,
                                    (pinned ? progress : (currentIndex + 1) / (total || 1)) * 100,
                                )}%`,
                            }"
                        ></div>
                    </div>

                    <span class="dtw-tabular hidden text-xs font-medium tracking-[0.2em] text-dtw-dim sm:block">
                        {{ String(currentIndex + 1).padStart(2, '0') }} / {{ String(total).padStart(2, '0') }}
                    </span>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/35 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-30"
                            :aria-label="t('Previous')"
                            :disabled="currentIndex === 0"
                            @click="step(-1)"
                        >
                            <i class="pi pi-arrow-left text-xs"></i>
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/15 text-white transition hover:border-white/35 hover:bg-white/5 disabled:cursor-not-allowed disabled:opacity-30"
                            :aria-label="t('Next')"
                            :disabled="currentIndex >= total - 1"
                            @click="step(1)"
                        >
                            <i class="pi pi-arrow-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
