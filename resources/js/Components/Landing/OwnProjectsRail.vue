<script setup>
/**
 * Carrusel de proyectos propios (gratis para la comunidad).
 *
 * Se muestra debajo de la galería de clientes. El desplazamiento es nativo
 * (scroll + snap) para que funcione igual con trackpad, rueda horizontal,
 * teclado y gestos táctiles; las flechas y los puntos sólo lo acompañan.
 *
 * En móvil (<1024px) las flechas y los puntos se ocultan: sólo se desliza con
 * el dedo y una barra de progreso fina indica la posición.
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

const nativeTrackRef = ref(null);
const currentIndex = ref(0);

/**
 * Tarjetas del carrusel. El elemento que scrollea es el contenedor con
 * `overflow-x-auto`; las tarjetas viven dentro del track flex.
 */
function nativeCards() {
    const track = nativeTrackRef.value?.firstElementChild;

    return track ? Array.from(track.children) : [];
}

/** Sincroniza puntos, flechas y progreso con la posición real del carrusel. */
function onTrackScroll() {
    const scroller = nativeTrackRef.value;

    if (!scroller) {
        return;
    }

    let best = 0;
    let bestDistance = Number.POSITIVE_INFINITY;

    nativeCards().forEach((card, index) => {
        const distance = Math.abs(card.offsetLeft - 20 - scroller.scrollLeft);

        if (distance < bestDistance) {
            bestDistance = distance;
            best = index;
        }
    });

    currentIndex.value = best;
}

function goToProject(index) {
    const scroller = nativeTrackRef.value;
    const card = nativeCards()[index];

    if (!scroller || !card) {
        return;
    }

    scroller.scrollTo({ left: card.offsetLeft - 20, behavior: 'smooth' });
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

                <!-- Flechas: sólo en escritorio; en móvil se desliza con el dedo -->
                <div v-if="total > 1" class="hidden items-center gap-2 lg:flex">
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
            ref="nativeTrackRef"
            class="dtw-no-scrollbar relative mt-10 w-full snap-x snap-mandatory overflow-x-auto pb-3"
            @scroll.passive="onTrackScroll"
        >
            <div class="flex items-stretch gap-4 px-5 md:gap-6 md:px-8">
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

        <!-- Puntos: sólo en escritorio; en móvil se desliza con el dedo -->
        <div
            v-if="total > 1"
            class="mx-auto mt-6 hidden w-full max-w-7xl items-center justify-center gap-2 px-5 md:px-8 lg:flex"
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

        <!-- Progreso (móvil): el carrusel se desliza con el dedo -->
        <div v-if="total > 1" class="mx-auto mt-6 w-full max-w-7xl px-5 md:px-8 lg:hidden">
            <div class="h-px bg-white/10">
                <div
                    class="h-px bg-gradient-to-r from-dtw-primary via-dtw-primary-soft to-dtw-cyan"
                    :style="{ width: `${Math.max(4, ((currentIndex + 1) / total) * 100)}%` }"
                ></div>
            </div>
        </div>
    </section>
</template>
