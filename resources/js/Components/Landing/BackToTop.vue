<script setup>
/**
 * Botón flotante para volver al inicio de la página.
 *
 * Permanece escondido mientras el visitante está en el hero y aparece en cuanto
 * llega a la sección indicada en `target` (por defecto el carrusel de proyectos
 * de la landing). Al pulsarlo sube con scroll suave y respeta
 * `prefers-reduced-motion`.
 *
 * Vive en LandingShell para estar disponible en todas las vistas públicas y se
 * coloca abajo a la izquierda porque abajo a la derecha ya está el botón de
 * WhatsApp (ver WhatsAppChat.vue).
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { useT } from '@/Composables/useT';

const props = defineProps({
    /** Selector de la sección a partir de la cual aparece el botón. */
    target: { type: String, default: '#proyectos' },
    /**
     * Píxeles de margen con los que se considera que ya se llegó a la sección:
     * 0 = justo cuando su borde superior toca el borde superior de la pantalla.
     */
    offset: { type: Number, default: 0 },
});

const t = useT();
const visible = ref(false);

let section = null;
let frame = null;

function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

/**
 * Vistas sin carrusel (galería de proyectos, eventos, ficha de proyecto): al no
 * existir la sección, el botón aparece al bajar una pantalla.
 */
function passedTarget() {
    if (section) {
        return section.getBoundingClientRect().top <= props.offset;
    }

    return window.scrollY > window.innerHeight * 0.9;
}

function update() {
    frame = null;

    const next = passedTarget();

    if (next !== visible.value) {
        visible.value = next;
    }
}

function onScroll() {
    if (frame !== null) {
        return;
    }

    frame = requestAnimationFrame(update);
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
}

onMounted(() => {
    section = document.querySelector(props.target);
    update();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', onScroll);

    if (frame !== null) {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-3 scale-90 opacity-0"
        enter-to-class="translate-y-0 scale-100 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 scale-100 opacity-100"
        leave-to-class="translate-y-2 scale-90 opacity-0"
    >
        <button
            v-if="visible"
            type="button"
            class="group fixed bottom-5 left-5 z-40 inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-dtw-surface/85 text-white shadow-[0_18px_45px_-20px_rgba(0,0,0,0.95)] backdrop-blur-xl transition hover:-translate-y-0.5 hover:border-dtw-primary-soft/70 hover:bg-dtw-primary/25 md:bottom-7 md:left-7"
            :aria-label="t('Back to top')"
            :title="t('Back to top')"
            @click="scrollToTop"
        >
            <i
                class="pi pi-arrow-up text-sm transition-transform duration-300 group-hover:-translate-y-0.5"
            ></i>
        </button>
    </Transition>
</template>
