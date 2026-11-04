<script setup>
/**
 * Comparativa "antes / después" de un proceso concreto.
 * Los importes son ejemplos ilustrativos, no casos reales.
 */
defineProps({
    index: { type: [Number, String], default: 1 },
    title: { type: String, required: true },
    metric: { type: String, required: true },
    metricLabel: { type: String, default: '' },
    before: { type: Array, default: () => [] },
    after: { type: Array, default: () => [] },
    note: { type: String, default: '' },
});
</script>

<template>
    <article
        class="overflow-hidden rounded-2xl border border-white/10 bg-dtw-surface/80 transition duration-300 hover:border-dtw-primary/30"
    >
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
            <div class="flex items-center gap-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-white/5 font-display text-xs font-bold text-white/60"
                >
                    {{ index }}
                </span>
                <h3 class="font-display text-base font-bold text-white">{{ title }}</h3>
            </div>

            <div class="text-right">
                <p class="font-display text-lg font-extrabold leading-none text-dtw-success">{{ metric }}</p>
                <p v-if="metricLabel" class="mt-1 text-[0.62rem] uppercase leading-tight tracking-wide text-white/55">
                    {{ metricLabel }}
                </p>
            </div>
        </header>

        <div class="grid gap-4 p-5 sm:grid-cols-2">
            <div class="rounded-xl border border-dtw-danger/20 bg-dtw-danger/[0.06] p-4">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-dtw-danger">
                    {{ t('Before') }}
                </p>
                <ul class="mt-3 space-y-2">
                    <li v-for="item in before" :key="item" class="flex gap-2 text-sm leading-snug text-white/70">
                        <i class="pi pi-times mt-0.5 text-[0.7rem] text-dtw-danger" aria-hidden="true"></i>
                        <span>{{ item }}</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-xl border border-dtw-success/20 bg-dtw-success/[0.06] p-4">
                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-dtw-success">
                    {{ t('After') }}
                </p>
                <ul class="mt-3 space-y-2">
                    <li v-for="item in after" :key="item" class="flex gap-2 text-sm leading-snug text-white/70">
                        <i class="pi pi-check mt-0.5 text-[0.7rem] text-dtw-success" aria-hidden="true"></i>
                        <span>{{ item }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <footer class="border-t border-white/10 px-5 py-3 text-[0.68rem] leading-relaxed text-white/55">
            {{ note || t('Illustrative example, not a real client case.') }}
        </footer>
    </article>
</template>
