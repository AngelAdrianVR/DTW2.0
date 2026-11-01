<script setup>
/**
 * Tarjeta de "fuga de dinero". Recibe textos ya traducidos.
 */
defineProps({
    icon: { type: String, required: true },
    title: { type: String, required: true },
    description: { type: String, required: true },
    stat: { type: String, default: '' },
    statLabel: { type: String, default: '' },
});
</script>

<template>
    <article
        class="group flex h-full flex-col rounded-2xl border border-dtw-ink/10 bg-white p-6 shadow-dtw-card transition duration-300 hover:-translate-y-1 hover:border-dtw-primary/30"
    >
        <div class="flex items-start justify-between gap-4">
            <span
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-dtw-primary/10 text-dtw-primary transition group-hover:bg-dtw-primary group-hover:text-white"
            >
                <i :class="icon" aria-hidden="true"></i>
            </span>

            <div v-if="stat" class="text-right">
                <span class="block font-display text-xl font-extrabold leading-none text-dtw-ink">{{ stat }}</span>
                <span class="mt-1 block max-w-[8.5rem] text-[0.62rem] uppercase leading-tight tracking-wide text-dtw-ink/55">
                    {{ statLabel }}
                </span>
            </div>
        </div>

        <h3 class="mt-5 font-display text-lg font-bold leading-snug text-dtw-ink">{{ title }}</h3>
        <p class="mt-2 text-sm leading-relaxed text-dtw-ink/65">{{ description }}</p>
    </article>
</template>
