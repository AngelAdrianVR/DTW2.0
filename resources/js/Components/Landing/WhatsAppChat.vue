<script setup>
/**
 * Prompt flotante estilo chatbot para WhatsApp.
 *
 * Sustituye al botón flotante tradicional: unos segundos después de entrar se
 * abre solo un saludo (una única vez por carga de página, ver
 * `useWhatsAppPrompt`) y, si el visitante lo descarta, queda el botón flotante
 * habitual para reabrirlo.
 */
import { computed } from 'vue';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { useWhatsAppPrompt } from '@/Composables/useWhatsAppPrompt';
import { trackWhatsAppClick } from '@/utils/analytics';

const props = defineProps({
    /** Milisegundos de espera antes de mostrar el saludo. */
    delay: { type: Number, default: 6000 },
});

const t = useT();
const { whatsappUrl } = useContact();

// El temporizador y el estado viven en el composable para que sobrevivan a las
// navegaciones de Inertia (la página y este marco se desmontan en cada visita).
const { open, seen, show, hide } = useWhatsAppPrompt(props.delay);

function onChat() {
    trackWhatsAppClick('chat_prompt');
    hide();
}

/** Muestra el globo hasta que el saludo se abre por primera vez. */
const highlighted = computed(() => !seen.value);
</script>

<template>
    <div class="pointer-events-none fixed bottom-5 right-5 z-40 flex flex-col items-end gap-3 md:bottom-7 md:right-7">
        <!-- Saludo automático -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 scale-95 opacity-0"
            enter-to-class="translate-y-0 scale-100 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 scale-100 opacity-100"
            leave-to-class="translate-y-2 scale-95 opacity-0"
        >
            <div
                v-if="open"
                role="dialog"
                aria-live="polite"
                :aria-label="t('Chat on WhatsApp')"
                class="pointer-events-auto w-[min(20rem,calc(100vw-2.5rem))] overflow-hidden rounded-3xl border border-white/10 bg-dtw-surface/95 shadow-[0_30px_80px_-40px_rgba(0,0,0,0.95)] backdrop-blur-xl"
            >
                <!-- Cabecera tipo conversación -->
                <div class="flex items-center gap-3 border-b border-white/[0.06] bg-[#25D366]/10 px-4 py-3">
                    <span
                        class="relative inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#25D366] text-dtw-void"
                    >
                        <i class="pi pi-whatsapp text-base"></i>
                        <span
                            class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full border-2 border-dtw-surface bg-emerald-400"
                        ></span>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-white">{{ t('DTW team') }}</p>
                        <p class="truncate text-[0.65rem] text-dtw-muted">
                            {{ t('We usually reply in minutes') }}
                        </p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full text-dtw-muted transition hover:bg-white/10 hover:text-white"
                        :aria-label="t('Close chat')"
                        @click="hide"
                    >
                        <i class="pi pi-times text-xs"></i>
                    </button>
                </div>

                <!-- Burbuja con el mensaje -->
                <div class="px-4 py-4">
                    <div
                        class="rounded-2xl rounded-tl-md border border-white/[0.06] bg-white/[0.05] px-4 py-3 text-sm leading-relaxed"
                    >
                        <p class="font-semibold text-white">{{ t('Hi! How can we help you?') }}</p>
                        <p class="mt-1 text-dtw-muted">
                            {{ t('Tell us about your process and we will reply right away.') }}
                        </p>
                    </div>

                    <div class="mt-4 flex flex-col gap-2">
                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-dtw-void transition hover:brightness-105"
                            @click="onChat"
                        >
                            <i class="pi pi-whatsapp text-base"></i>
                            {{ t('Chat on WhatsApp') }}
                        </a>

                        <button
                            type="button"
                            class="py-1 text-xs font-medium text-dtw-dim transition hover:text-white"
                            @click="hide"
                        >
                            {{ t('Not now') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Botón flotante (siempre disponible) -->
        <button
            v-if="!open"
            type="button"
            :aria-label="t('Open chat')"
            class="pointer-events-auto relative inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3.5 text-sm font-semibold text-dtw-void shadow-[0_18px_45px_-18px_rgba(37,211,102,0.95)] transition hover:scale-[1.04]"
            @click="show"
        >
            <i class="pi pi-whatsapp text-lg"></i>
            <span class="hidden pr-1 md:inline">{{ t("Let's talk") }}</span>

            <span v-if="highlighted" class="absolute -right-0.5 -top-0.5 flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white/70"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full border-2 border-[#25D366] bg-white"></span>
            </span>
        </button>
    </div>
</template>
