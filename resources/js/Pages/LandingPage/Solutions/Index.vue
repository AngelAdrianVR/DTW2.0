<script setup>
/**
 * Galería completa de proyectos (/soluciones).
 *
 * Se pintan todos los proyectos desarrollados para clientes (WebContent tipo
 * `portfolio` publicados, enviados por LandingController como prop
 * `portfolio`) en una cuadrícula de imágenes cuadradas con bordes redondeados
 * y el título debajo. Cada tarjeta enlaza a la ficha del proyecto
 * (/landing-projects/{id}).
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import { useProjects } from '@/Composables/useProjects';
import { useT } from '@/Composables/useT';

const props = defineProps({
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
    portfolio: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();
const projects = useProjects(props.portfolio);

const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/soluciones`);
const ogImage = computed(() => projects.value[0]?.image || page.props.metaImage || '');

const seoTitle = computed(() => t('All our projects'));
const seoDescription = computed(() =>
    t(
        'Software, hardware and AI built for real operations. Every project starts with a process that had to be improved.',
    ),
);

const projectCount = computed(() => projects.value.length);
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

                <p
                    v-if="projectCount"
                    class="mt-5 text-[0.7rem] font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft"
                >
                    {{ projectCount }} {{ projectCount === 1 ? t('Project') : t('Projects') }}
                </p>
            </div>
        </section>

        <section class="relative bg-transparent pb-24 md:pb-32">
            <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <div
                    v-if="projectCount"
                    class="grid grid-cols-2 gap-x-4 gap-y-9 sm:gap-x-5 sm:gap-y-10 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <Link
                        v-for="(project, index) in projects"
                        :key="project.id"
                        :href="project.href"
                        class="group block rounded-[24px] focus:outline-none focus-visible:ring-2 focus-visible:ring-dtw-primary"
                        :aria-label="project.title"
                        v-reveal
                    >
                        <!-- Imagen cuadrada con bordes redondeados -->
                        <div
                            class="relative aspect-square overflow-hidden rounded-[24px] border border-white/10 bg-dtw-surface shadow-[0_28px_70px_-46px_rgba(0,0,0,0.95)] transition duration-500 group-hover:-translate-y-1 group-hover:border-white/25 group-focus-visible:-translate-y-1 group-focus-visible:border-white/25"
                        >
                            <img
                                v-if="project.image"
                                :src="project.image"
                                :alt="project.title"
                                loading="lazy"
                                draggable="false"
                                class="h-full w-full object-cover transition duration-[900ms] ease-out group-hover:scale-[1.06]"
                            />
                            <div
                                v-else
                                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-dtw-brand/30 via-dtw-surface-soft to-dtw-void"
                            >
                                <img src="/images/white_logo.png" alt="" class="h-8 w-auto opacity-40" />
                            </div>

                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-dtw-void/45 via-transparent to-transparent opacity-0 transition duration-500 group-hover:opacity-100"
                            ></div>

                            <span
                                class="pointer-events-none absolute right-3 top-3 inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/15 bg-dtw-void/70 text-white opacity-0 backdrop-blur transition duration-300 group-hover:opacity-100 md:right-4 md:top-4"
                            >
                                <i class="pi pi-arrow-up-right text-[0.65rem]"></i>
                            </span>
                        </div>

                        <!-- Título debajo de la imagen -->
                        <div class="mt-4 flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2
                                    class="line-clamp-2 font-display text-base font-bold leading-snug text-white transition group-hover:text-dtw-primary-soft md:text-lg"
                                >
                                    {{ project.title }}
                                </h2>
                                <p v-if="project.subtitle" class="mt-1 line-clamp-1 text-xs text-dtw-muted md:text-sm">
                                    {{ project.subtitle }}
                                </p>
                            </div>

                            <span
                                class="dtw-tabular shrink-0 pt-1 font-display text-[0.65rem] font-bold tracking-[0.28em] text-white/30"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                        </div>
                    </Link>
                </div>

                <!-- Estado vacío (sin proyectos publicados) -->
                <div
                    v-else
                    class="rounded-[28px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-14 text-center text-sm text-dtw-muted"
                >
                    {{ t('The first projects of our gallery are on their way. In the meantime, explore our solutions.') }}
                </div>
            </div>
        </section>
    </LandingShell>
</template>
