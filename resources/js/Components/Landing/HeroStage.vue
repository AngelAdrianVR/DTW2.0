<script setup>
/**
 * Hero: una sola frase con la propuesta de valor, dos acciones y el
 * respaldo visual del producto. Sin párrafos largos.
 */
import { computed } from 'vue';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { trackCtaClick, trackWhatsAppClick } from '@/utils/analytics';

const t = useT();
const { whatsappUrl } = useContact();

const chips = computed(() => [
    { icon: 'pi pi-desktop', label: t('Windows apps'), color: '#A855F7' },
    { icon: 'pi pi-mobile', label: t('Android apps'), color: '#3B82F6' },
    { icon: 'pi pi-globe', label: t('Web platforms'), color: '#17EDF4' },
    { icon: 'pi pi-microchip', label: t('Industrial hardware'), color: '#EAB308' },
    { icon: 'pi pi-sparkles', label: t('Artificial intelligence'), color: '#C084FC' },
]);

/**
 * Destellos decorativos del fondo.
 *
 * Las posiciones son fijas (nada de Math.random) para que el render del
 * servidor y el del cliente coincidan; el titileo lo hace el CSS con
 * `--sparkle-delay` / `--sparkle-duration`, así cada punto va a su aire.
 */
const sparkles = [
    { top: '12%', left: '7%', size: 3, delay: '0s', duration: '5.4s' },
    { top: '21%', left: '16%', size: 2, delay: '1.6s', duration: '6.2s' },
    { top: '9%', left: '31%', size: 2, delay: '2.8s', duration: '4.8s' },
    { top: '16%', left: '86%', size: 3, delay: '0.9s', duration: '5.8s' },
    { top: '28%', left: '73%', size: 2, delay: '2.1s', duration: '6.6s' },
    { top: '37%', left: '93%', size: 2, delay: '3.4s', duration: '5.1s' },
    { top: '45%', left: '5%', size: 2, delay: '1.2s', duration: '6.9s' },
    { top: '58%', left: '12%', size: 3, delay: '3.1s', duration: '5.6s' },
    { top: '68%', left: '23%', size: 2, delay: '0.4s', duration: '6.4s' },
    { top: '72%', left: '86%', size: 3, delay: '2.5s', duration: '5.9s' },
    { top: '84%', left: '74%', size: 2, delay: '1.9s', duration: '6.1s' },
    { top: '88%', left: '41%', size: 2, delay: '3.7s', duration: '5.3s' },
    { top: '61%', left: '95%', size: 2, delay: '0.7s', duration: '6.7s' },
    { top: '33%', left: '36%', size: 2, delay: '4.1s', duration: '5.7s' },
];

function onWhatsApp() {
    trackCtaClick('hero');
    trackWhatsAppClick('hero');
}

function onSeeProjects() {
    trackCtaClick('hero_see_projects');
}
</script>

<template>
    <section id="inicio" class="relative flex min-h-[100svh] items-center overflow-hidden pb-20 pt-28 md:pt-32">
        <!-- Fondo: velo + cuadrícula + halos + destellos (decorativo) -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-gradient-to-b from-dtw-void/60 via-dtw-void/10 to-dtw-void/70"></div>

            <!-- Cuadrícula propia del hero (más marcada que la global, con los bordes difuminados) -->
            <div class="dtw-hero-grid absolute inset-0"></div>

            <div
                class="absolute -top-48 left-1/2 h-[36rem] w-[64rem] -translate-x-1/2 rounded-full bg-dtw-brand/30 blur-[150px]"
            ></div>
            <div
                class="absolute -bottom-24 -right-24 h-[26rem] w-[26rem] animate-drift rounded-full bg-dtw-cyan/[0.09] blur-[120px]"
            ></div>

            <!-- Destellos -->
            <div class="absolute inset-0 hidden sm:block">
                <span
                    v-for="(sparkle, index) in sparkles"
                    :key="`sparkle-${index}`"
                    class="dtw-hero-sparkle"
                    :style="{
                        top: sparkle.top,
                        left: sparkle.left,
                        '--sparkle-size': `${sparkle.size}px`,
                        '--sparkle-delay': sparkle.delay,
                        '--sparkle-duration': sparkle.duration,
                    }"
                ></span>
            </div>

            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-dtw-void to-transparent"></div>
        </div>

        <div class="relative mx-auto w-full max-w-5xl px-5 text-center md:px-8">
            <!-- Etiqueta -->
            

            <!-- Titular -->
            <h1
                class="mt-8 font-display text-[clamp(2rem,5.6vw,4.4rem)] font-extrabold leading-[1.02] tracking-[-0.03em] text-white"
            >
                {{ t('Less manual work.') }}
                <span class="dtw-hero-shimmer mt-1">
                    <span
                        class="block bg-gradient-to-r from-dtw-primary-soft via-dtw-primary to-dtw-cyan bg-clip-text pb-2 text-transparent"
                    >
                        {{ t('More time and money for your business.') }}
                    </span>
                    <!-- Copia del texto que sólo pinta el brillo en barrido -->
                    <span aria-hidden="true" class="dtw-hero-shimmer__sheen">
                        {{ t('More time and money for your business.') }}
                    </span>
                </span>
            </h1>

            <p class="mx-auto mt-7 max-w-2xl text-base leading-relaxed text-dtw-muted md:text-lg">
                {{ t('We automate your operations and solve day-to-day problems with custom digital tools.') }}
            </p>

            <!-- Acciones -->
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-dtw-primary px-7 py-3.5 text-sm font-semibold text-white shadow-[0_18px_50px_-18px_rgba(168,85,247,0.95)] transition hover:-translate-y-0.5 hover:bg-dtw-primary-deep sm:w-auto"
                    @click="onWhatsApp"
                >
                    <i class="pi pi-whatsapp text-base"></i>
                    {{ t('Book a free diagnosis') }}
                </a>

                <a
                    href="#proyectos"
                    class="group inline-flex w-full items-center justify-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-7 py-3.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/[0.07] sm:w-auto"
                    @click="onSeeProjects"
                >
                    {{ t('See projects') }}
                    <i class="pi pi-arrow-down text-xs transition-transform duration-300 group-hover:translate-y-0.5"></i>
                </a>
            </div>

            <p class="mt-6 text-xs text-dtw-dim">
                {{ t('Free and with no commitment · Reply in less than 24 hours') }}
            </p>
            <!-- Capacidades -->
            <div class="mt-12 flex flex-wrap items-center justify-center gap-2">
                <span
                    v-for="chip in chips"
                    :key="chip.label"
                    class="inline-flex items-center gap-2 rounded-full border border-white/[0.08] bg-white/[0.03] px-4 py-2 text-xs font-medium text-dtw-muted transition hover:border-white/20 hover:text-white"
                >
                    <i :class="chip.icon" class="text-[0.8rem]" :style="{ color: chip.color }"></i>
                    {{ chip.label }}
                </span>
            </div>

            <!-- Indicador de scroll -->
            <div class="pointer-events-none absolute bottom-6 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-2 md:flex">
                <span class="text-[0.6rem] font-semibold uppercase tracking-[0.35em] text-dtw-dim">
                    {{ t('Scroll') }}
                </span>
                <span class="relative flex h-9 w-px overflow-hidden bg-white/10">
                    <span class="absolute inset-x-0 top-0 h-3 animate-scroll-hint bg-gradient-to-b from-dtw-primary to-transparent"></span>
                </span>
            </div>
        </div>
    </section>
</template>
