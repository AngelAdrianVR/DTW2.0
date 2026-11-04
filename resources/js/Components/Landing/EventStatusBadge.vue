<script setup>
/**
 * Etiqueta de estado de un evento (abierto, próximamente, cerrado, finalizado).
 * Recibe el estado calculado en el backend y sólo decide color y traducción.
 */
import { computed } from 'vue';
import { useT } from '@/Composables/useT';

const props = defineProps({
    status: { type: String, default: 'draft' },
});

const t = useT();

const variants = {
    open: {
        labelKey: 'Registration open',
        icon: 'pi pi-bolt',
        className: 'border-dtw-cyan/40 bg-dtw-cyan/10 text-dtw-cyan',
    },
    upcoming: {
        labelKey: 'Coming soon',
        icon: 'pi pi-clock',
        className: 'border-dtw-primary/40 bg-dtw-primary/10 text-dtw-primary-soft',
    },
    closed: {
        labelKey: 'Registration closed',
        icon: 'pi pi-lock',
        className: 'border-white/15 bg-white/[0.04] text-dtw-muted',
    },
    finished: {
        labelKey: 'Finished',
        icon: 'pi pi-verified',
        className: 'border-dtw-success/40 bg-dtw-success/10 text-dtw-success',
    },
    draft: {
        labelKey: 'Draft',
        icon: 'pi pi-pencil',
        className: 'border-white/15 bg-white/[0.04] text-dtw-dim',
    },
};

const variant = computed(() => variants[props.status] || variants.draft);
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[0.62rem] font-bold uppercase tracking-[0.16em]',
            variant.className,
        ]"
    >
        <i :class="variant.icon" class="text-[0.7rem]" aria-hidden="true"></i>
        {{ t(variant.labelKey) }}
    </span>
</template>
