<script setup>
/**
 * Línea de tiempo del proceso de trabajo.
 * `steps` llega con los textos ya traducidos.
 */
defineProps({
    steps: { type: Array, default: () => [] },
});
</script>

<template>
    <ol class="relative grid gap-8 md:grid-cols-4 md:gap-6">
        <li v-for="(step, index) in steps" :key="step.title" class="relative">
            <!-- Conector (solo escritorio) -->
            <span
                v-if="index < steps.length - 1"
                class="absolute left-14 right-0 top-6 hidden h-px bg-dtw-ink/12 md:block"
                aria-hidden="true"
            ></span>

            <div class="flex items-center gap-4 md:block">
                <span
                    class="relative z-10 flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-dtw-primary font-display text-base font-bold text-white shadow-dtw-glow"
                >
                    {{ index + 1 }}
                </span>
                <p
                    v-if="step.duration"
                    class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-dtw-primary md:mt-4"
                >
                    {{ step.duration }}
                </p>
            </div>

            <h3 class="mt-4 font-display text-base font-bold text-dtw-ink">{{ step.title }}</h3>
            <p class="mt-2 text-sm leading-relaxed text-dtw-ink/60">{{ step.description }}</p>
        </li>
    </ol>
</template>
