<script setup>
/**
 * Tarjeta de solución. `items` son textos ya traducidos.
 */
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    items: { type: Array, default: () => [] },
    ctaLabel: { type: String, default: '' },
});
</script>

<template>
    <article
        class="flex h-full flex-col rounded-2xl border border-dtw-ink/10 bg-white p-6 shadow-dtw-card transition duration-300 hover:-translate-y-1 hover:border-dtw-primary/30 hover:shadow-dtw-soft"
    >
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-dtw-primary/10 text-lg text-dtw-primary">
            <i :class="icon" aria-hidden="true"></i>
        </span>

        <h3 class="mt-5 font-display text-lg font-bold leading-snug text-dtw-ink">{{ title }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-dtw-ink/65">{{ description }}</p>

        <ul v-if="items.length" class="mt-4 space-y-2">
            <li v-for="item in items" :key="item" class="flex gap-2 text-sm text-dtw-ink/70">
                <i class="pi pi-check mt-0.5 text-xs text-dtw-success" aria-hidden="true"></i>
                <span>{{ item }}</span>
            </li>
        </ul>

        <a
            href="#contacto"
            class="mt-auto inline-flex items-center gap-2 pt-6 text-sm font-semibold text-dtw-primary transition hover:gap-3 hover:text-dtw-primary-deep"
        >
            {{ ctaLabel }}
            <i class="pi pi-arrow-right text-xs" aria-hidden="true"></i>
        </a>
    </article>
</template>
