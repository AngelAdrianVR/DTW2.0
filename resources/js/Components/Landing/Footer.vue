<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useT } from '../../Composables/useT';
import { trackWhatsAppClick } from '../../utils/analytics';

const props = defineProps({
    contactEmail: { type: String, default: '' },
    whatsapp: { type: String, default: '' },
    whatsappDisplay: { type: String, default: '' },
});

const t = useT();
const year = new Date().getFullYear();

const whatsappUrl = computed(() => {
    if (!props.whatsapp) {
        return '';
    }
    const message = t('Hello, I want a free diagnosis for my company.');
    return `https://wa.me/${props.whatsapp}?text=${encodeURIComponent(message)}`;
});

const solutionLinks = [
    { href: '#calculadora', labelKey: 'Financial calculator' },
    { href: '#soluciones', labelKey: 'Solutions' },
    { href: '#antes-despues', labelKey: 'Before / After' },
    { href: '#roi', labelKey: 'Return on investment' },
];

const companyLinks = [
    { href: '#problemas', labelKey: 'Problems' },
    { href: '#proceso', labelKey: 'Process' },
    { href: '#sectores', labelKey: 'Industries' },
    { href: '#faq', labelKey: 'FAQ' },
];
</script>

<template>
    <footer class="relative border-t border-white/10 bg-dtw-dark">
        <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <!-- Marca -->
                <div>
                    <a href="#top" class="inline-flex items-center" :aria-label="t('DTW home')">
                        <img
                            src="/images/white_logo.png"
                            alt="DTW"
                            class="h-9 w-auto"
                            width="140"
                            height="36"
                            loading="lazy"
                            decoding="async"
                        />
                    </a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/55">
                        {{ t('Automation, software and data that make your operation measurably more profitable.') }}
                    </p>
                    <p class="mt-4 flex items-center gap-2 text-sm text-white/60">
                        <i class="pi pi-map-marker text-dtw-primary-soft" aria-hidden="true"></i>
                        {{ t('Zapopan, Jalisco, Mexico') }}
                    </p>
                </div>

                <!-- Soluciones -->
                <nav :aria-label="t('Solutions')">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/55">
                        {{ t('Solutions') }}
                    </p>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in solutionLinks" :key="link.href">
                            <a :href="link.href" class="text-sm text-white/60 transition hover:text-white">
                                {{ t(link.labelKey) }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Empresa -->
                <nav :aria-label="t('Company')">
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/55">
                        {{ t('Company') }}
                    </p>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="link in companyLinks" :key="link.href">
                            <a :href="link.href" class="text-sm text-white/60 transition hover:text-white">
                                {{ t(link.labelKey) }}
                            </a>
                        </li>
                        <li>
                            <a href="#contacto" class="text-sm text-white/60 transition hover:text-white">
                                {{ t('Contact') }}
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Contacto y legal -->
                <div>
                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/55">
                        {{ t('Contact') }}
                    </p>
                    <ul class="mt-4 space-y-2.5">
                        <li v-if="whatsappUrl">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-2 text-sm text-white/60 transition hover:text-white"
                                @click="trackWhatsAppClick('footer')"
                            >
                                <i class="pi pi-whatsapp text-dtw-success" aria-hidden="true"></i>
                                {{ whatsappDisplay || whatsapp }}
                            </a>
                        </li>
                        <li v-if="contactEmail">
                            <a
                                :href="`mailto:${contactEmail}`"
                                class="flex items-center gap-2 text-sm text-white/60 transition hover:text-white"
                            >
                                <i class="pi pi-envelope text-dtw-primary-soft" aria-hidden="true"></i>
                                {{ contactEmail }}
                            </a>
                        </li>
                    </ul>

                    <ul class="mt-6 space-y-2.5 border-t border-white/10 pt-4">
                        <li>
                            <Link :href="route('terms.show')" class="text-sm text-white/50 transition hover:text-white">
                                {{ t('Terms of Service') }}
                            </Link>
                        </li>
                        <li>
                            <Link :href="route('privacy.show')" class="text-sm text-white/50 transition hover:text-white">
                                {{ t('Privacy Policy') }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col gap-3 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-xs text-white/55">© {{ year }} DTW. {{ t('All rights reserved.') }}</p>
                <p class="text-xs text-white/55">{{ t('Designed and built in Jalisco, Mexico.') }}</p>
            </div>
        </div>
    </footer>
</template>
