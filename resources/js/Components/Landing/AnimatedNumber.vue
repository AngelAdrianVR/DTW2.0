<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useInView } from '../../Composables/useInView';

/**
 * Cifra que se anima al entrar en pantalla.
 * Respeta prefers-reduced-motion y vuelve a animar cuando el valor cambia
 * (por ejemplo, al mover los campos de la calculadora).
 */
const props = defineProps({
    value: { type: Number, default: 0 },
    decimals: { type: Number, default: 0 },
    prefix: { type: String, default: '' },
    suffix: { type: String, default: '' },
    duration: { type: Number, default: 1100 },
    locale: { type: String, default: 'es-MX' },
    animate: { type: Boolean, default: true },
});

const { target, isInView } = useInView({ threshold: 0.35 });

const current = ref(0);
let frame = null;

const formatted = computed(() =>
    new Intl.NumberFormat(props.locale, {
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals,
    }).format(current.value),
);

function prefersReducedMotion() {
    return (
        typeof window !== 'undefined' &&
        typeof window.matchMedia === 'function' &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches
    );
}

function animateTo(to, from = 0) {
    if (typeof window === 'undefined' || !props.animate || prefersReducedMotion()) {
        current.value = to;
        return;
    }

    if (frame) {
        cancelAnimationFrame(frame);
    }

    const startedAt = performance.now();
    const distance = to - from;

    const step = (now) => {
        const progress = Math.min(1, (now - startedAt) / Math.max(200, props.duration));
        const eased = 1 - Math.pow(1 - progress, 3);
        current.value = from + distance * eased;

        if (progress < 1) {
            frame = requestAnimationFrame(step);
        }
    };

    frame = requestAnimationFrame(step);
}

watch(
    () => [props.value, isInView.value],
    ([value, visible]) => {
        if (!visible) {
            return;
        }
        animateTo(Number(value) || 0, current.value);
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    if (frame) {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <span ref="target" class="dtw-tabular whitespace-nowrap">{{ prefix }}{{ formatted }}{{ suffix }}</span>
</template>
