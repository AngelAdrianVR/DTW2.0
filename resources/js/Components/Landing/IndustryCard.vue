<script setup>
/**
 * Sector atendido. Texto ya traducido por el padre.
 */
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <article
        class="group flex h-full items-start gap-4 rounded-xl border border-dtw-ink/10 bg-white p-4 transition duration-300 hover:border-dtw-primary/40 hover:shadow-dtw-card"
    >
        <span
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-dtw-light text-dtw-primary transition group-hover:bg-dtw-primary group-hover:text-white"
        >
            <i :class="icon" aria-hidden="true"></i>
        </span>
        <div>
            <h3 class="font-display text-sm font-bold text-dtw-ink">{{ title }}</h3>
            <p v-if="description" class="mt-1 text-xs leading-relaxed text-dtw-ink/60">{{ description }}</p>
        </div>
    </article>
</template>
