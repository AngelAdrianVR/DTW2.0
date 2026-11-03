<script setup>
/**
 * Escenario de detalle (proyecto o solución):
 * texto breve a la izquierda y carrusel anclado de imágenes cuadradas a la
 * derecha, con lightbox.
 *
 * Las imágenes miden 70% del alto de la pantalla y son cuadradas (`aspect-ratio`
 * sobre el alto, que es el único valor que se fija); el riel avanza con el
 * scroll mientras la sección queda pegada. En móvil se convierte en un carrusel
 * con snap.
 *
 * Con `infoInRail` el resumen (slot `info`) se pinta como primera tarjeta del
 * riel y el recorrido cambia de modelo: cada tarjeta se detiene CENTRADA en el
 * escenario y consume ~0.9 pantallas de scroll, así que la galería no se puede
 * saltar (hacia abajo avanza, hacia arriba retrocede). Sin anclaje (móvil o
 * reduced-motion) el resumen se queda arriba del carrusel para que el texto
 * siga siendo legible.
 *
 * La rueda del ratón debe mover la página aunque el cursor esté encima del
 * resumen o de una imagen: el escenario recorta su desborde para que el riel
 * no se salga de la pantalla y ese recorte no puede quedarse con el gesto.
 */
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useHorizontalPin } from '@/Composables/useHorizontalPin';
import { useT } from '@/Composables/useT';

const props = defineProps({
    /** @type {{ url: string, alt: string }[]} */
    images: { type: Array, default: () => [] },
    emptyLabel: { type: String, default: '' },
    /**
     * Coloca el contenido del slot `info` como primera tarjeta del riel
     * anclado, para que el scroll también desplace el resumen y deje las
     * imágenes grandes a la vista.
     */
    infoInRail: { type: Boolean, default: false },
});

const t = useT();

const sectionRef = ref(null);
const trackRef = ref(null);
const stageRef = ref(null);

const { progress, offset, pinned: measuredPin, canPin, sectionHeight, scrollToProgress, measure } =
    useHorizontalPin(sectionRef, trackRef, {
        padding: 24,
        container: true,
    });

const total = computed(() => props.images.length);

/**
 * Con el resumen dentro del riel el recorrido va centrado, así que el riel
 * desborda por diseño: basta con que haya galería para anclar (no depende de
 * que el carrusel nativo desborde). Sin `infoInRail` se mantiene la medición.
 */
const pinned = computed(() =>
    props.infoInRail ? canPin.value && total.value > 1 : measuredPin.value,
);

// El resumen viaja dentro del riel sólo cuando hay anclaje, así que se cuenta
// como una tarjeta más del recorrido.
const hasIntro = computed(() => props.infoInRail && pinned.value && total.value > 0);
const steps = computed(() => total.value + (hasIntro.value ? 1 : 0));

// Posición continua del riel en "unidades de tarjeta" (0 = primera tarjeta
// centrada). Con el resumen delante, la imagen `i` queda centrada en `i + 1`.
const railShift = computed(() => progress.value * Math.max(0, steps.value - 1));

// Al cambiar el número de imágenes cambia el largo del riel: se vuelve a medir
// para que el recorrido termine justo en la última imagen.
watch(total, () => measure(), { flush: 'post' });

const activeIndex = computed(() => {
    if (!total.value) {
        return 0;
    }

    const position = hasIntro.value
        ? railShift.value - 1
        : progress.value * Math.max(1, total.value - 1);

    return Math.min(total.value - 1, Math.max(0, Math.round(position)));
});

// 70% del alto de la pantalla, cuadrado, con tope por ancho de columna.
// El alto es la única fuente de verdad y `aspect-ratio` deriva el ancho: así
// ninguna restricción de la columna (o del propio contenido) puede dejar la
// tarjeta más alta que ancha.
const CARD_HEIGHT = 'min(70svh, 44vw)';
const CARD_HEIGHT_MOBILE = 'min(78vw, 70svh)';

const pinnedFigureStyle = { height: CARD_HEIGHT, aspectRatio: '1 / 1' };
const figureStyle = { height: CARD_HEIGHT_MOBILE, aspectRatio: '1 / 1' };

/* ---------------------------------------------
   Riel centrado, un paso de scroll por tarjeta
   --------------------------------------------- */

/** Pantallas de scroll que consume cada tarjeta del riel. */
const RAIL_STEP_SCREENS = 0.9;

// Tamaño de tarjeta y separación: una sola fuente de verdad para las tarjetas,
// los espaciadores y el paso del riel.
const railVars = {
    '--dtw-card': CARD_HEIGHT,
    '--dtw-rail-gap': '1.5rem',
    '--dtw-rail-step': 'calc(var(--dtw-card) + var(--dtw-rail-gap))',
};

const railCardStyle = {
    height: 'var(--dtw-card)',
    aspectRatio: '1 / 1',
    marginRight: 'var(--dtw-rail-gap)',
};

// Espaciadores simétricos: dejan la tarjeta activa centrada en el escenario
// (el de salida descuenta el margen que arrastra la última tarjeta).
const railLeadStyle = { width: 'calc((100% - var(--dtw-card)) / 2)' };
const railTrailStyle = { width: 'calc((100% - var(--dtw-card)) / 2 - var(--dtw-rail-gap))' };

// Un viewport para la primera tarjeta y ~0.9 más por cada una de las
// restantes: la sección cubre toda la galería antes de soltar el scroll.
const stageHeight = computed(() =>
    hasIntro.value ? `${100 + (steps.value - 1) * RAIL_STEP_SCREENS * 100}svh` : null,
);

const sectionStyle = computed(() => {
    const style = props.infoInRail ? { ...railVars } : {};
    // En modo resumen-en-riel el alto lo manda el recorrido paso a paso, no la
    // medición de desborde del riel.
    const height = props.infoInRail ? stageHeight.value : sectionHeight.value;

    if (height) {
        style.height = height;
    }

    return style;
});

const trackStyle = computed(() =>
    props.infoInRail
        ? { transform: `translate3d(calc(${-railShift.value} * var(--dtw-rail-step)), 0, 0)` }
        : { transform: `translate3d(${offset.value}px, 0, 0)` },
);

function goTo(index) {
    if (pinned.value) {
        const position = index + (hasIntro.value ? 1 : 0);

        scrollToProgress(steps.value > 1 ? position / (steps.value - 1) : 0);
        return;
    }

    const track = trackRef.value;
    const figure = track?.children?.[index];

    if (track && figure) {
        track.scrollTo({ left: figure.offsetLeft - 20, behavior: 'smooth' });
    }
}

/* ---------------------------------------------
   La rueda siempre mueve la página
   --------------------------------------------- */

/**
 * ¿Hay algún contenedor con scroll propio bajo el cursor que todavía pueda
 * desplazarse en esa dirección? (por ejemplo la tarjeta del resumen cuando el
 * texto no cabe). Si lo hay, el gesto se le deja a él.
 */
function innerScrollAvailable(node, delta) {
    let element = node instanceof Element ? node : null;

    while (element && element !== stageRef.value) {
        const { overflowY } = window.getComputedStyle(element);
        const scrollable =
            /(auto|scroll|overlay)/.test(overflowY) &&
            element.scrollHeight - element.clientHeight > 1;

        if (scrollable) {
            const atStart = element.scrollTop <= 0;
            const atEnd = element.scrollTop + element.clientHeight >= element.scrollHeight - 1;

            if (delta < 0 ? !atStart : !atEnd) {
                return true;
            }
        }

        element = element.parentElement;
    }

    return false;
}

/**
 * El escenario y el riel recortan el desborde para que las tarjetas no se
 * salgan de la pantalla. Ese recorte (o un contenedor interno que ya llegó a
 * su límite) puede quedarse con la rueda y dejar la página clavada mientras el
 * cursor está encima del resumen o de una imagen. Aquí el gesto vuelve a la
 * página cuando nadie más puede aprovecharlo.
 */
function onWheel(event) {
    // En móvil / reduced-motion el carrusel nativo manda: no se toca nada.
    if (!pinned.value || event.ctrlKey || event.metaKey) {
        return;
    }

    // Firefox puede reportar líneas o páginas en lugar de píxeles.
    const unit = event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? window.innerHeight : 1;
    const delta = event.deltaY * unit;

    // Gesto horizontal (trackpad) o sin desplazamiento vertical.
    if (!delta || Math.abs(delta) <= Math.abs(event.deltaX)) {
        return;
    }

    if (innerScrollAvailable(event.target, delta)) {
        return;
    }

    event.preventDefault();

    // `instant` evita que `html { scroll-behavior: smooth }` anime cada tick;
    // en navegadores sin ese valor se usa el desplazamiento posicional.
    try {
        window.scrollBy({ top: delta, left: 0, behavior: 'instant' });
    } catch {
        window.scrollBy(0, delta);
    }
}

/* ---------------------------------------------
   Lightbox
   --------------------------------------------- */
const lightboxIndex = ref(null);

const isLightboxOpen = computed(() => lightboxIndex.value !== null);
const lightboxImage = computed(() => (isLightboxOpen.value ? props.images[lightboxIndex.value] : null));

function openLightbox(index) {
    lightboxIndex.value = index;
}

function closeLightbox() {
    lightboxIndex.value = null;
}

function moveLightbox(delta) {
    if (!total.value || !isLightboxOpen.value) {
        return;
    }

    lightboxIndex.value = (lightboxIndex.value + delta + total.value) % total.value;
}

function onKeydown(event) {
    if (!isLightboxOpen.value) {
        return;
    }

    if (event.key === 'Escape') {
        closeLightbox();
    } else if (event.key === 'ArrowRight') {
        moveLightbox(1);
    } else if (event.key === 'ArrowLeft') {
        moveLightbox(-1);
    }
}

watch(isLightboxOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
    // `passive: false` porque el puente de la rueda necesita `preventDefault`.
    stageRef.value?.addEventListener('wheel', onWheel, { passive: false });
});

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    stageRef.value?.removeEventListener('wheel', onWheel);
    document.body.style.overflow = '';
});
</script>

<template>
    <section ref="sectionRef" class="relative" :style="sectionStyle">
        <div
            ref="stageRef"
            :class="
                pinned
                    ? 'sticky top-0 flex h-[100svh] items-center dtw-clip-x'
                    : 'flex items-center py-24 md:py-28'
            "
        >
            <div
                :class="
                    hasIntro
                        ? 'mx-auto w-full max-w-[1500px] px-5 md:px-8'
                        : 'mx-auto grid w-full max-w-[1500px] items-center gap-10 px-5 md:px-8 lg:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)] lg:gap-14'
                "
            >
                <!-- Texto (con `infoInRail` viaja dentro del riel) -->
                <div v-if="!hasIntro" class="min-w-0">
                    <slot name="info" />
                </div>

                <!-- Galería -->
                <div class="min-w-0">
                    <template v-if="total">
                        <!-- Riel anclado -->
                        <div v-if="pinned" class="relative dtw-clip-x">
                            <div
                                ref="trackRef"
                                class="dtw-pin-track flex"
                                :class="infoInRail ? 'items-stretch' : 'gap-6'"
                                :style="trackStyle"
                            >
                                <!-- Espaciador de entrada: centra la primera tarjeta -->
                                <span
                                    v-if="infoInRail"
                                    aria-hidden="true"
                                    class="shrink-0"
                                    :style="railLeadStyle"
                                ></span>

                                <!-- Resumen: primera tarjeta del riel.
                                     Sin `overscroll-contain`: si el texto no
                                     cupiera, el scroll sigue de largo hacia la
                                     página en vez de quedarse atrapado. -->
                                <div
                                    v-if="infoInRail"
                                    class="dtw-no-scrollbar flex shrink-0 flex-col overflow-y-auto rounded-[26px] border border-white/10 bg-gradient-to-b from-white/[0.06] to-white/[0.015] p-6 md:p-8"
                                    :style="railCardStyle"
                                >
                                    <div class="my-auto w-full">
                                        <slot name="info" />
                                    </div>
                                </div>

                                <button
                                    v-for="(image, index) in images"
                                    :key="`${image.url}-${index}`"
                                    type="button"
                                    class="group relative shrink-0 overflow-hidden rounded-[26px] border border-white/10 bg-dtw-surface"
                                    :style="infoInRail ? railCardStyle : pinnedFigureStyle"
                                    @click="openLightbox(index)"
                                >
                                    <img
                                        :src="image.url"
                                        :alt="image.alt"
                                        loading="lazy"
                                        class="absolute inset-0 h-full w-full object-cover transition duration-[800ms] ease-out group-hover:scale-[1.03]"
                                    />
                                    <span
                                        class="absolute inset-0 z-10 bg-gradient-to-t from-dtw-void/70 via-transparent to-transparent opacity-0 transition duration-500 group-hover:opacity-100"
                                    ></span>
                                    <span
                                        class="absolute bottom-5 left-5 z-10 text-[0.65rem] font-semibold tracking-[0.3em] text-white/70"
                                    >
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                    <span
                                        class="absolute right-5 top-5 z-10 inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/20 bg-dtw-void/60 text-white opacity-0 backdrop-blur transition duration-300 group-hover:opacity-100"
                                    >
                                        <i class="pi pi-search text-xs"></i>
                                    </span>
                                </button>

                                <!-- Espaciador de salida: centra la última tarjeta -->
                                <span
                                    v-if="infoInRail"
                                    aria-hidden="true"
                                    class="shrink-0"
                                    :style="railTrailStyle"
                                ></span>
                            </div>
                        </div>

                        <!-- Carrusel nativo -->
                        <div v-else class="dtw-no-scrollbar flex snap-x snap-mandatory gap-4 overflow-x-auto pb-2">
                            <button
                                v-for="(image, index) in images"
                                :key="`${image.url}-${index}-mobile`"
                                type="button"
                                class="relative shrink-0 overflow-hidden rounded-[22px] border border-white/10 bg-dtw-surface"
                                :style="figureStyle"
                                @click="openLightbox(index)"
                            >
                                <img
                                    :src="image.url"
                                    :alt="image.alt"
                                    loading="lazy"
                                    class="absolute inset-0 h-full w-full object-cover"
                                />
                                <span class="absolute bottom-4 left-4 z-10 text-[0.65rem] font-semibold tracking-[0.3em] text-white/70">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                            </button>
                        </div>
                        <!-- Controles -->
                        <div class="mt-5 flex items-center gap-4">
                            <div class="flex items-center gap-1.5">
                                <button
                                    v-for="(image, index) in images"
                                    :key="`detail-dot-${index}`"
                                    type="button"
                                    class="h-1.5 rounded-full transition-all duration-300"
                                    :class="activeIndex === index ? 'w-8 bg-dtw-primary' : 'w-1.5 bg-white/25 hover:bg-white/50'"
                                    :aria-label="t('View image :number', { number: index + 1 })"
                                    :aria-current="activeIndex === index ? 'true' : undefined"
                                    @click="goTo(index)"
                                ></button>
                            </div>

                            <div class="h-px flex-1 bg-white/10">
                                <div
                                    class="h-px bg-gradient-to-r from-dtw-primary to-dtw-cyan"
                                    :style="{ width: `${Math.max(4, progress * 100)}%` }"
                                ></div>
                            </div>

                            <span class="dtw-tabular text-xs tracking-[0.2em] text-dtw-dim">
                                {{ String(activeIndex + 1).padStart(2, '0') }} / {{ String(total).padStart(2, '0') }}
                            </span>
                        </div>

                        <p class="mt-3 text-[0.7rem] text-dtw-dim">{{ t('Scroll to browse images') }}</p>
                    </template>

                    <!-- Sin imágenes -->
                    <div
                        v-else
                        class="flex aspect-square w-full max-w-[70svh] flex-col items-center justify-center gap-4 rounded-[26px] border border-dashed border-white/15 bg-white/[0.02] px-8 text-center"
                    >
                        <i class="pi pi-image text-2xl text-dtw-dim"></i>
                        <p class="text-sm text-dtw-muted">{{ emptyLabel || t('This project has no images yet') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Lightbox -->
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isLightboxOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-dtw-void/95 p-4 backdrop-blur-xl"
                role="dialog"
                aria-modal="true"
                @click.self="closeLightbox"
            >
                <button
                    type="button"
                    class="absolute right-5 top-5 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:bg-white/15"
                    :aria-label="t('Close')"
                    @click="closeLightbox"
                >
                    <i class="pi pi-times"></i>
                </button>

                <button
                    v-if="total > 1"
                    type="button"
                    class="absolute left-4 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:bg-white/15 md:left-8"
                    :aria-label="t('Previous')"
                    @click="moveLightbox(-1)"
                >
                    <i class="pi pi-arrow-left"></i>
                </button>

                <img
                    v-if="lightboxImage"
                    :src="lightboxImage.url"
                    :alt="lightboxImage.alt"
                    class="max-h-[86vh] max-w-[92vw] rounded-2xl object-contain shadow-[0_40px_120px_-40px_rgba(0,0,0,1)]"
                />

                <button
                    v-if="total > 1"
                    type="button"
                    class="absolute right-4 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:bg-white/15 md:right-8"
                    :aria-label="t('Next')"
                    @click="moveLightbox(1)"
                >
                    <i class="pi pi-arrow-right"></i>
                </button>

                <p class="absolute bottom-6 left-1/2 -translate-x-1/2 text-xs tracking-[0.3em] text-dtw-dim">
                    {{ String(lightboxIndex + 1).padStart(2, '0') }} / {{ String(total).padStart(2, '0') }}
                </p>
            </div>
        </transition>
    </Teleport>
</template>
