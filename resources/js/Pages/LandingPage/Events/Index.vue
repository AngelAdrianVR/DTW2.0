<script setup>
/**
 * Galería pública de eventos (/eventos).
 *
 * Muestra todas las ediciones publicadas: la que tiene el registro abierto se
 * distingue por su etiqueta de estado y el resto queda como historial. Cada
 * tarjeta abre la ficha del evento con su formulario de registro.
 *
 * La vista se enfoca solo en los eventos: el marco común (`LandingShell`) se
 * pide sin la banda de contacto y sin el pie de página. Como respaldo visual de
 * que aquí se premia a los ganadores, una copa rellena (icono `pi-trophy` de
 * PrimeIcons, ya instalado) acompaña el fondo en la esquina superior derecha en
 * un tono muy sutil, con fade-in al entrar y destellos que titilan igual que los
 * del hero de la portada. La copa escala por tramos según el viewport (más
 * pequeña en móvil) para que nunca se salga de la pantalla.
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

/* ---------------------------------------------
   Copa decorativa: destellos del fondo
   --------------------------------------------- */
/**
 * Destellos alrededor de la copa.
 *
 * Las posiciones son fijas (nada de Math.random) y se expresan en porcentaje
 * de la caja de la copa; el titileo lo hace el CSS con `--sparkle-delay` /
 * `--sparkle-duration`, así cada punto va a su aire (ver `.dtw-hero-sparkle`
 * en app.css, la misma clase que usa el hero de la portada).
 */
const trophySparkles = [
    { top: '4%', left: '34%', size: 3, delay: '0s', duration: '5.4s' },
    { top: '6%', left: '78%', size: 2, delay: '1.7s', duration: '6.2s' },
    { top: '16%', left: '6%', size: 2, delay: '0.6s', duration: '4.9s' },
    { top: '22%', left: '94%', size: 3, delay: '2.4s', duration: '5.7s' },
    { top: '38%', left: '2%', size: 2, delay: '3.1s', duration: '6.5s' },
    { top: '46%', left: '98%', size: 2, delay: '1.2s', duration: '5.2s' },
    { top: '62%', left: '4%', size: 3, delay: '2.9s', duration: '6.8s' },
    { top: '70%', left: '92%', size: 2, delay: '0.9s', duration: '5.5s' },
    { top: '84%', left: '16%', size: 2, delay: '3.6s', duration: '6.1s' },
    { top: '88%', left: '70%', size: 3, delay: '1.9s', duration: '5.9s' },
];
</script>

<template>
    <LandingShell :show-cta="false" :show-footer="false">
        <Head :title="seoTitle">
            <meta name="description" :content="seoDescription" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="`${seoTitle} · DTW`" />
            <meta property="og:description" :content="seoDescription" />
            <meta v-if="ogImage" property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
        </Head>

        <!-- Copa decorativa: icono relleno con fade-in y destellos (decorativo) -->
        <div
            aria-hidden="true"
            class="dtw-clip-x pointer-events-none absolute right-10 top-20 -z-10 select-none motion-safe:animate-fade-in"
            :style="{ animationDuration: '1.4s', animationDelay: '0.4s' }"
        >
            <!--
                Copa rellena: `pi-trophy` de PrimeIcons se pinta con el morado de
                marca a muy baja opacidad, suficiente para leerse como silueta
                sobre el fondo oscuro sin competir con el texto (sube el alfa si
                hace falta más presencia).
                El tamaño escala con la pantalla: `min()` se queda con la medida
                más pequeña entre el ancho y el alto del viewport, así en móvil
                la copa no se desborda ni se corta y crece por tramos hasta el
                80 % del alto en escritorio (xl), como estaba antes.
            -->
            <i
                class="pi pi-trophy block text-[min(38vw,30vh)] text-dtw-primary/[0.2] sm:text-[min(34vw,38vh)] md:text-[min(38vw,52vh)] lg:text-[min(25vw,32vh)] xl:text-[40vh]"
            ></i>

            <!-- Brillos alrededor de la copa: aparecen y desaparecen como en el hero -->
            <div class="absolute inset-0 hidden sm:block">
                <span
                    v-for="(sparkle, index) in trophySparkles"
                    :key="`trophy-sparkle-${index}`"
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
        </div>

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
