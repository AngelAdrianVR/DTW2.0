<script setup>
/**
 * Galería completa de soluciones (/soluciones).
 * Cada tarjeta resume la solución y ofrece contacto directo (el detalle
 * individual de cada solución ya no existe como vista aparte).
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import { useSolutions } from '@/Composables/useSolutions';
import { useContact } from '@/Composables/useContact';
import { useT } from '@/Composables/useT';
import { trackWhatsAppClick } from '@/utils/analytics';

defineProps({
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();
const list = useSolutions();
const { whatsappUrl } = useContact();

const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/soluciones`);
const ogImage = computed(() => page.props.metaImage || '');

const seoTitle = computed(() => t('All our solutions'));
const seoDescription = computed(() =>
    t('Every solution starts with a diagnosis of your process, not with a technology.'),
);
</script>

<template>
    <LandingShell>
        <Head :title="seoTitle">
            <meta name="description" :content="seoDescription" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="seoTitle" />
            <meta property="og:description" :content="seoDescription" />
            <meta property="og:image" :content="ogImage" />
        </Head>

        <section class="relative overflow-hidden pb-20 pt-32 md:pb-24 md:pt-40">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-dtw-void/70 via-dtw-void/20 to-dtw-void/80"></div>
            <div
                class="pointer-events-none absolute -top-40 left-1/2 h-[30rem] w-[56rem] -translate-x-1/2 rounded-full bg-dtw-brand/25 blur-[140px]"
            ></div>

            <div class="relative mx-auto w-full max-w-7xl px-5 md:px-8">
                <Link
                    href="/"
                    class="group inline-flex items-center gap-2 text-sm font-medium text-dtw-muted transition hover:text-white"
                >
                    <i class="pi pi-arrow-left text-xs transition-transform duration-300 group-hover:-translate-x-1"></i>
                    {{ t('Home') }}
                </Link>

                <h1
                    class="mt-8 max-w-3xl font-display text-[clamp(2rem,5vw,3.6rem)] font-extrabold leading-[1.03] tracking-tight text-white"
                >
                    {{ seoTitle }}
                </h1>

                <p class="mt-6 max-w-2xl text-base leading-relaxed text-dtw-muted md:text-lg">
                    {{ seoDescription }}
                </p>
            </div>
        </section>

        <section class="relative bg-transparent pb-24 md:pb-32">
            <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="solution in list"
                        :key="solution.slug"
                        class="group relative flex flex-col overflow-hidden rounded-[26px] border border-white/10 bg-dtw-surface transition duration-500 hover:-translate-y-1 hover:border-white/25"
                    >
                        <div class="relative aspect-[16/10] overflow-hidden">
                            <img
                                :src="solution.image"
                                :alt="solution.title"
                                loading="lazy"
                                class="h-full w-full object-cover opacity-75 transition duration-[900ms] group-hover:scale-105 group-hover:opacity-100"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-dtw-surface via-dtw-void/30 to-transparent"></div>

                            <span
                                class="absolute left-5 top-5 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-dtw-void/70 backdrop-blur"
                                :style="{ color: solution.accent }"
                            >
                                <i :class="solution.icon"></i>
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col justify-between gap-5 p-6">
                            <div>
                                <h2 class="font-display text-xl font-bold leading-snug text-white">
                                    {{ solution.title }}
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-dtw-muted">
                                    {{ solution.tagline }}
                                </p>
                            </div>

                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 text-sm font-semibold transition hover:opacity-80"
                                :style="{ color: solution.accent }"
                                @click="trackWhatsAppClick(`gallery_${solution.slug}`)"
                            >
                                <i class="pi pi-whatsapp text-xs"></i>
                                {{ t('Talk to us about this solution') }}
                                <i class="pi pi-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </article>
                </div>

                <p class="mt-12 max-w-2xl text-sm text-dtw-muted">
                    {{ t('Not sure which one you need?') }}
                    {{ t('We will help you find out in a 20-minute call, free of charge.') }}
                </p>
            </div>
        </section>
    </LandingShell>
</template>
