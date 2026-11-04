<script setup>
/**
 * Encabezado de sección reutilizable.
 * Recibe el texto ya traducido: la traducción se resuelve en el padre con t().
 */
defineProps({
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    align: { type: String, default: 'center' }, // center | left
    theme: { type: String, default: 'light' }, // light | dark
});

const eyebrowClass = 'text-xs font-semibold uppercase tracking-[0.2em]';
</script>

<template>
    <div :class="['max-w-3xl', align === 'center' ? 'mx-auto text-center' : 'text-left']">
        <p
            v-if="eyebrow"
            :class="[eyebrowClass, theme === 'dark' ? 'text-dtw-primary-soft' : 'text-dtw-primary']"
        >
            {{ eyebrow }}
        </p>

        <h2
            :class="[
                'mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-[2.6rem] lg:leading-[1.15]',
                theme === 'dark' ? 'text-white' : 'text-dtw-ink',
            ]"
        >
            {{ title }}
        </h2>

        <p
            v-if="description"
            :class="[
                'mt-4 text-base leading-relaxed',
                theme === 'dark' ? 'text-white/70' : 'text-dtw-ink/70',
            ]"
        >
            {{ description }}
        </p>

        <slot />
    </div>
</template>
