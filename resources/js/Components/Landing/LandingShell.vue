<script setup>
/**
 * Marco común de las vistas públicas: header auto-ocultable, contenido,
 * banda de contacto, footer y botón flotante de WhatsApp.
 */
import SiteHeader from '@/Components/Landing/SiteHeader.vue';
import SiteFooter from '@/Components/Landing/SiteFooter.vue';
import SiteBackdrop from '@/Components/Landing/SiteBackdrop.vue';
import CtaBand from '@/Components/Landing/CtaBand.vue';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { trackWhatsAppClick } from '@/utils/analytics';

defineProps({
    /** Muestra la banda de contacto antes del footer. */
    showCta: { type: Boolean, default: true },
    /**
     * Muestra el pie de página. Se desactiva en vistas que deben quedarse
     * enfocadas en su contenido, como la galería de un proyecto.
     */
    showFooter: { type: Boolean, default: true },
});

const t = useT();
const { whatsappUrl } = useContact();
</script>

<template>
    <div class="dtw-scope relative isolate min-h-screen bg-dtw-void font-inter text-dtw-ink antialiased">
        <SiteBackdrop />

        <SiteHeader />

        <main id="contenido">
            <slot />
        </main>

        <CtaBand v-if="showCta" />

        <SiteFooter v-if="showFooter" />

        <!-- Contacto siempre a un toque -->
        <a
            :href="whatsappUrl"
            target="_blank"
            rel="noopener"
            :aria-label="t('Message us on WhatsApp')"
            class="group fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-[#25D366] px-4 py-3.5 text-sm font-semibold text-dtw-void shadow-[0_18px_45px_-18px_rgba(37,211,102,0.95)] transition hover:scale-[1.04] md:bottom-7 md:right-7"
            @click="trackWhatsAppClick('floating')"
        >
            <i class="pi pi-whatsapp text-lg"></i>
            <span class="hidden pr-1 md:inline">{{ t("Let's talk") }}</span>
        </a>
    </div>
</template>
