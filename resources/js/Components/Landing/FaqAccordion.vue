<script setup>
import { ref } from 'vue';

/**
 * Acordeón de preguntas frecuentes accesible.
 * `items` = [{ question, answer }] con el texto ya traducido.
 */
const props = defineProps({
    items: { type: Array, default: () => [] },
});

// Solo una pregunta abierta a la vez (patrón acordeón).
const openIndex = ref(0);

function toggle(index) {
    openIndex.value = openIndex.value === index ? -1 : index;
}
</script>

<template>
    <div class="divide-y divide-dtw-ink/10 overflow-hidden rounded-2xl border border-dtw-ink/10 bg-white shadow-dtw-card">
        <div v-for="(item, index) in items" :key="item.question">
            <h3 class="m-0">
                <button
                    :id="`faq-trigger-${index}`"
                    type="button"
                    class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-dtw-light/70 sm:px-6"
                    :aria-expanded="openIndex === index"
                    :aria-controls="`faq-panel-${index}`"
                    @click="toggle(index)"
                >
                    <span class="font-display text-sm font-bold text-dtw-ink sm:text-base">{{ item.question }}</span>
                    <span
                        :class="[
                            'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs transition',
                            openIndex === index
                                ? 'border-dtw-primary bg-dtw-primary text-white'
                                : 'border-dtw-ink/15 text-dtw-ink/60',
                        ]"
                        aria-hidden="true"
                    >
                        <i :class="openIndex === index ? 'pi pi-minus' : 'pi pi-plus'"></i>
                    </span>
                </button>
            </h3>

            <div
                :id="`faq-panel-${index}`"
                role="region"
                :aria-labelledby="`faq-trigger-${index}`"
                class="grid transition-all duration-300 ease-out"
                :class="openIndex === index ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'"
            >
                <div class="overflow-hidden">
                    <p class="px-5 pb-5 pr-12 text-sm leading-relaxed text-dtw-ink/65 sm:px-6">
                        {{ item.answer }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
