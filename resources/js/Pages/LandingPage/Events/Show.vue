<script setup>
/**
 * Ficha pública de un evento (/eventos/{slug}).
 *
 * Reto, premios, ganadores y el formulario de registro. Cuando la ventana está
 * cerrada el formulario se sustituye por el aviso con la fecha correspondiente.
 */
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Toast from 'primevue/toast';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import EventStatusBadge from '@/Components/Landing/EventStatusBadge.vue';
import EventRegistrationForm from '@/Components/Landing/EventRegistrationForm.vue';
import { formatEventDate, translateEvent } from '@/Composables/useEvents';
import { useT } from '@/Composables/useT';

const props = defineProps({
    event: { type: Object, required: true },
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();

const locale = computed(() => page.props.locale || 'es');
const ev = computed(() => translateEvent(props.event, locale.value));

/* ---------------------------------------------
   SEO
   --------------------------------------------- */
const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/eventos/${props.event.slug}`);
const ogImage = computed(() => ev.value.image || page.props.metaImage || '');

const seoTitle = computed(() => ev.value.title);

const seoDescription = computed(() => {
    const source = ev.value.description || ev.value.challenge || ev.value.prizesSummary || '';

    return source.length > 160 ? `${source.slice(0, 157)}...` : source;
});

/**
 * Aviso cuando el registro no está abierto: si aún no empieza se muestra la
 * fecha de apertura, que es lo único que le importa al visitante.
 */
const closedMessage = computed(() => {
    if (ev.value.status === 'upcoming') {
        const date = formatEventDate(props.event.registration_starts_at, locale.value);

        return date ? t('Registration opens on :date', { date }) : t('Registration is not open yet.');
    }

    return t('Registration is closed.');
});

const eventSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'Event',
    name: ev.value.title,
    description: seoDescription.value || undefined,
    image: ogImage.value ? [ogImage.value] : undefined,
    startDate: props.event.event_date || undefined,
    eventStatus: 'https://schema.org/EventScheduled',
    eventAttendanceMode: 'https://schema.org/OnlineEventAttendanceMode',
    url: canonicalUrl.value,
    location: {
        '@type': 'VirtualLocation',
        url: canonicalUrl.value,
    },
    organizer: {
        '@type': 'Organization',
        name: 'DTW',
        url: baseUrl.value,
    },
}));

const schemaId = 'dtw-event-schema';

function injectSchema() {
    document.getElementById(schemaId)?.remove();

    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = schemaId;
    script.textContent = JSON.stringify(eventSchema.value);
    document.head.appendChild(script);
}

onMounted(injectSchema);
onBeforeUnmount(() => document.getElementById(schemaId)?.remove());
</script>

<template>
    <LandingShell :show-cta="false">
        <Head :title="seoTitle">
            <meta name="description" :content="seoDescription" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="`${seoTitle} · DTW`" />
            <meta property="og:description" :content="seoDescription" />
            <meta v-if="ogImage" property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta name="twitter:card" content="summary_large_image" />
        </Head>

        <Toast position="top-right" />

        <!-- Encabezado -->
        <section class="relative pb-10 pt-28 md:pt-36">
            <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
                <Link
                    href="/eventos"
                    class="inline-flex items-center gap-2 text-sm font-semibold text-dtw-muted transition hover:text-white"
                >
                    <i class="pi pi-arrow-left text-xs"></i>
                    {{ t('Back to events') }}
                </Link>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <EventStatusBadge :status="ev.status" />
                    <span
                        v-if="ev.eventDateLabel"
                        class="text-xs font-medium uppercase tracking-[0.2em] text-dtw-dim"
                    >
                        {{ ev.eventDateLabel }}
                    </span>
                </div>

                <h1
                    class="mt-5 max-w-4xl font-display text-[clamp(2rem,5vw,3.4rem)] font-extrabold leading-[1.05] tracking-tight text-white"
                >
                    {{ ev.title }}
                </h1>

                <p v-if="ev.description" class="mt-5 max-w-3xl text-base leading-relaxed text-dtw-muted">
                    {{ ev.description }}
                </p>
            </div>
        </section>

        <!-- Portada: se muestra completa y centrada. La altura la dicta la
             imagen (sin proporción impuesta), con un tope para que una portada
             vertical no empuje el resto de la ficha fuera de pantalla. Las
             bandas que deja `object-contain` se funden con el fondo. -->
        <div v-if="ev.image" class="mx-auto w-full max-w-7xl px-5 md:px-8">
            <div class="overflow-hidden rounded-[30px] border border-white/10 bg-dtw-void">
                <img
                    :src="ev.image"
                    :alt="ev.title"
                    decoding="async"
                    class="mx-auto block h-auto max-h-[72vh] w-full object-contain"
                />
            </div>
        </div>
        <section class="py-12 md:py-16">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-5 md:px-8 lg:grid-cols-[1.35fr_0.65fr]">
                <!-- Detalle -->
                <div class="space-y-10">
                    <div v-if="ev.challenge">
                        <h2 class="font-display text-xl font-extrabold tracking-tight text-white md:text-2xl">
                            {{ t('The challenge') }}
                        </h2>
                        <p class="mt-4 whitespace-pre-line text-base leading-relaxed text-dtw-ink/80">
                            {{ ev.challenge }}
                        </p>
                    </div>

                    <div v-if="ev.prizesSummary">
                        <h2 class="font-display text-xl font-extrabold tracking-tight text-white md:text-2xl">
                            {{ t('Prizes') }}
                        </h2>
                        <p class="mt-4 whitespace-pre-line text-base leading-relaxed text-dtw-ink/80">
                            {{ ev.prizesSummary }}
                        </p>
                    </div>

                    <div>
                        <h2 class="font-display text-xl font-extrabold tracking-tight text-white md:text-2xl">
                            {{ t('Winners') }}
                        </h2>

                        <ul v-if="ev.winners.length" class="mt-4 space-y-3">
                            <li
                                v-for="(winner, index) in ev.winners"
                                :key="winner.id"
                                class="flex gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4"
                            >
                                <span
                                    class="dtw-tabular inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-dtw-primary/15 font-display text-sm font-bold text-dtw-primary-soft"
                                >
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>

                                <div class="min-w-0">
                                    <p class="font-display text-base font-bold text-white">{{ winner.name }}</p>
                                    <p v-if="winner.businessName" class="text-xs text-dtw-muted">
                                        {{ winner.businessName }}
                                    </p>
                                    <p class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-dtw-cyan/30 bg-dtw-cyan/10 px-3 py-1 text-[0.68rem] font-semibold text-dtw-cyan">
                                        <i class="pi pi-gift text-[0.7rem]"></i>
                                        {{ winner.prize }}
                                    </p>
                                    <p v-if="winner.note" class="mt-2 text-xs leading-relaxed text-dtw-dim">
                                        {{ winner.note }}
                                    </p>
                                </div>
                            </li>
                        </ul>

                        <p
                            v-else
                            class="mt-4 rounded-2xl border border-dashed border-white/15 bg-white/[0.02] px-5 py-6 text-sm text-dtw-muted"
                        >
                            {{ t('Winners will be announced soon.') }}
                        </p>
                    </div>
                </div>

                <!-- Registro -->
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <EventRegistrationForm v-if="ev.canRegister" :event="ev" />

                    <div v-else class="rounded-[26px] border border-white/10 bg-white/[0.04] p-6">
                        <EventStatusBadge :status="ev.status" />

                        <p class="mt-4 font-display text-lg font-bold text-white">{{ closedMessage }}</p>

                        <p
                            v-if="ev.registrationEndsLabel && ev.status !== 'upcoming'"
                            class="mt-2 text-sm leading-relaxed text-dtw-muted"
                        >
                            {{ t('Registration closed on :date', { date: ev.registrationEndsLabel }) }}
                        </p>

                        <Link
                            href="/eventos"
                            class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-white/35 hover:bg-white/5"
                        >
                            {{ t('See other events') }}
                            <i class="pi pi-arrow-right text-xs"></i>
                        </Link>
                    </div>
                </aside>
            </div>
        </section>
    </LandingShell>
</template>
