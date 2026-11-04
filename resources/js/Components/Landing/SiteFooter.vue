<script setup>
/**
 * Pie de página de la landing. Limpio, en una sola franja y con los accesos
 * que de verdad usa el visitante: servicios, proyectos, contacto y legales.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { useSolutions } from '@/Composables/useSolutions';
import { trackWhatsAppClick } from '@/utils/analytics';

const t = useT();
const { whatsappUrl, whatsappDisplay, email, emailUrl } = useContact();
const solutions = useSolutions();

const year = computed(() => new Date().getFullYear());

const legalLinks = computed(() => [
    { href: '/terms-of-service', label: t('Terms of service') },
    { href: '/privacy-policy', label: t('Privacy policy') },
]);
</script>

<template>
    <footer class="relative overflow-hidden border-t border-white/[0.07] bg-dtw-night/80 backdrop-blur-sm">
        <div
            class="pointer-events-none absolute -top-40 left-1/2 h-80 w-[70rem] -translate-x-1/2 rounded-full bg-dtw-primary/10 blur-[120px]"
        ></div>

        <div class="relative mx-auto w-full max-w-7xl px-5 py-16 md:px-8">
            <div class="grid gap-12 md:grid-cols-[1.4fr_1fr_1fr]">
                <!-- Marca -->
                <div class="max-w-sm">
                    <img src="/images/white_logo.png" :alt="t('DTW logo')" class="h-8 w-auto" />
                    <p class="mt-5 text-sm leading-relaxed text-dtw-muted">
                        {{ t('Software, hardware and AI for companies that want to grow without adding headcount.') }}
                    </p>

                    <a
                        :href="whatsappUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-white transition hover:text-dtw-primary-soft"
                        @click="trackWhatsAppClick('footer')"
                    >
                        <i class="pi pi-whatsapp text-base"></i>
                        {{ whatsappDisplay }}
                    </a>
                    <div class="mt-2">
                        <a
                            :href="emailUrl"
                            class="text-sm text-dtw-muted transition hover:text-white"
                        >
                            {{ email }}
                        </a>
                    </div>
                </div>

                <!-- Servicios (sin enlace a la ficha: el detalle vive en la landing) -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-dtw-dim">
                        {{ t('Services') }}
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-dtw-muted">
                        <li v-for="solution in solutions" :key="solution.slug">
                            {{ solution.title }}
                        </li>
                    </ul>
                </div>

                <!-- Contacto -->
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-dtw-dim">
                        {{ t('Contact') }}
                    </p>
                    <ul class="mt-5 space-y-3 text-sm text-dtw-muted">
                        <li>{{ t('Zapopan, Jalisco, Mexico') }}</li>
                        <li>
                            <a :href="emailUrl" class="transition hover:text-white">{{ email }}</a>
                        </li>
                        <li>
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener"
                                class="transition hover:text-white"
                                @click="trackWhatsAppClick('footer_contact')"
                            >
                                {{ whatsappDisplay }}
                            </a>
                        </li>
                    </ul>

                    <div class="mt-6 flex flex-col gap-2">
                        <Link
                            href="/eventos"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-dtw-primary-soft transition hover:text-white"
                        >
                            {{ t('See all events') }}
                            <i class="pi pi-arrow-up-right text-xs"></i>
                        </Link>

                        <Link
                            href="/proyectos"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-dtw-primary-soft transition hover:text-white"
                        >
                            {{ t('See all projects') }}
                            <i class="pi pi-arrow-up-right text-xs"></i>
                        </Link>
                    </div>
                </div>
            </div>

            <div
                class="mt-14 flex flex-col gap-4 border-t border-white/[0.07] pt-8 text-xs text-dtw-dim md:flex-row md:items-center md:justify-between"
            >
                <p>© {{ year }} DTW. {{ t('All rights reserved.') }}</p>

                <div class="flex flex-wrap items-center gap-6">
                    <Link
                        v-for="link in legalLinks"
                        :key="link.href"
                        :href="link.href"
                        class="transition hover:text-white"
                    >
                        {{ link.label }}
                    </Link>
                </div>
            </div>
        </div>
    </footer>
</template>
