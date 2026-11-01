<script setup>
/**
 * Banda de contacto: el cierre de todas las vistas públicas.
 * Un solo mensaje, dos acciones y cero formularios.
 */
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { trackCtaClick, trackWhatsAppClick } from '@/utils/analytics';

const t = useT();
const { whatsappUrl, emailUrl } = useContact();

function onWhatsApp() {
    trackCtaClick('cta_band');
    trackWhatsAppClick('cta_band');
}
</script>

<template>
    <section id="contacto" class="relative overflow-hidden bg-transparent py-24 md:py-32">
        <!-- Fondo -->
        <div class="pointer-events-none absolute inset-0 bg-dtw-radial opacity-70"></div>
        <div
            class="pointer-events-none absolute -bottom-52 left-1/2 h-[28rem] w-[60rem] -translate-x-1/2 rounded-full bg-dtw-brand/25 blur-[140px]"
        ></div>

        <div class="relative mx-auto w-full max-w-4xl px-5 text-center md:px-8" v-reveal>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft">
                {{ t('Contact') }}
            </p>

            <h2
                class="mt-6 font-display text-[clamp(1.9rem,4.4vw,3.4rem)] font-extrabold leading-[1.05] tracking-tight text-white"
            >
                {{ t('Does a process cost you time or money?') }}
            </h2>

            <p class="mx-auto mt-6 max-w-2xl text-base leading-relaxed text-dtw-muted md:text-lg">
                {{ t('Tell us about it and we will give you our point of view, free and with no commitment.') }}
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-dtw-primary px-7 py-3.5 text-sm font-semibold text-white shadow-[0_18px_50px_-18px_rgba(168,85,247,0.9)] transition hover:-translate-y-0.5 hover:bg-dtw-primary-deep sm:w-auto"
                    @click="onWhatsApp"
                >
                    <i class="pi pi-whatsapp text-base"></i>
                    {{ t('Message us on WhatsApp') }}
                </a>

                <a
                    :href="emailUrl"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-7 py-3.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/[0.06] sm:w-auto"
                    @click="trackCtaClick('cta_band_email')"
                >
                    <i class="pi pi-envelope text-base"></i>
                    {{ t('Send us an email') }}
                </a>
            </div>

            <p class="mt-6 text-xs text-dtw-dim">
                {{ t('Free and with no commitment · Reply in less than 24 hours') }}
            </p>
        </div>
    </section>
</template>
