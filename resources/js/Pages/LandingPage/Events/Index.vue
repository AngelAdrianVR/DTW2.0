<script setup>
/**
 * Galería pública de eventos (/eventos).
 *
 * Muestra todas las ediciones publicadas: la que tiene el registro abierto se
 * distingue por su etiqueta de estado y el resto queda como historial. Cada
 * tarjeta abre la ficha del evento con su formulario de registro.
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import EventCard from '@/Components/Landing/EventCard.vue';
import { useEvents } from '@/Composables/useEvents';
import { useT } from '@/Composables/useT';

const props = defineProps({
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
    events: { type: Object, default: () => ({ data: [], links: [] }) },
});

const t = useT();
const page = usePage();

const eventList = useEvents(computed(() => props.events?.data || []));
const links = computed(() => props.events?.links || []);

/* ---------------------------------------------
   SEO
   --------------------------------------------- */
const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/eventos`);
const ogImage = computed(() => eventList.value[0]?.image || page.props.metaImage || '');

const seoTitle = computed(() => t('Events'));

const seoDescription = computed(() => t('Events and challenges open to the community'));
</script>

<template>
    <LandingShell>
        <Head :title="seoTitle">
            <meta name="description" :content="seoDescription" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="`${seoTitle} · DTW`" />
            <meta property="og:description" :content="seoDescription" />
            <meta v-if="ogImage" property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
        </Head>

        <section class="relative pb-16 pt-32 md:pb-20 md:pt-40">
            <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft">
                    {{ t('Events') }}
                </p>

                <h1
                    class="mt-4 max-w-3xl font-display text-[clamp(2rem,5vw,3.4rem)] font-extrabold leading-[1.05] tracking-tight text-white"
                >
                    {{ t('Events and challenges open to the community') }}
                </h1>

                <p class="mt-5 max-w-2xl text-base leading-relaxed text-dtw-muted">
                    {{ t('We launch challenges for local businesses and reward the best answers. Sign up, compete and win.') }}
                </p>
            </div>
        </section>

        <section class="relative pb-24">
            <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <div v-if="eventList.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <EventCard v-for="event in eventList" :key="event.id" :event="event" />
                </div>

                <div
                    v-else
                    class="rounded-[28px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-16 text-center text-sm text-dtw-muted"
                >
                    {{ t('There are no published events yet.') }}
                </div>

                <nav
                    v-if="links.length > 3"
                    class="mt-12 flex flex-wrap items-center justify-center gap-2"
                    :aria-label="t('Pagination')"
                >
                    <template v-for="(link, index) in links" :key="index">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-full border px-4 text-sm font-semibold transition"
                            :class="
                                link.active
                                    ? 'border-dtw-primary bg-dtw-primary text-white'
                                    : 'border-white/15 text-white hover:border-white/35 hover:bg-white/5'
                            "
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="inline-flex h-10 min-w-[2.5rem] items-center justify-center rounded-full border border-white/10 px-4 text-sm text-dtw-dim"
                            v-html="link.label"
                        ></span>
                    </template>
                </nav>
            </div>
        </section>
    </LandingShell>
</template>
