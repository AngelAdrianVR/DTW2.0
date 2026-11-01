<script setup>
/**
 * Marco común de las vistas públicas: header auto-ocultable, contenido,
 * banda de contacto, footer y atajos flotantes (volver arriba y WhatsApp).
 */
import SiteHeader from '@/Components/Landing/SiteHeader.vue';
import SiteFooter from '@/Components/Landing/SiteFooter.vue';
import SiteBackdrop from '@/Components/Landing/SiteBackdrop.vue';
import CtaBand from '@/Components/Landing/CtaBand.vue';
import WhatsAppChat from '@/Components/Landing/WhatsAppChat.vue';
import BackToTop from '@/Components/Landing/BackToTop.vue';

defineProps({
    /** Muestra la banda de contacto antes del footer. */
    showCta: { type: Boolean, default: true },
    /**
     * Muestra el pie de página. Se desactiva en vistas que deben quedarse
     * enfocadas en su contenido, como la galería de un proyecto.
     */
    showFooter: { type: Boolean, default: true },
});
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

        <!-- Vuelta arriba: aparece al llegar al carrusel de proyectos -->
        <BackToTop target="#proyectos" />

        <!-- Contacto siempre a un toque: saludo diferido + botón flotante -->
        <WhatsAppChat />
    </div>
</template>
