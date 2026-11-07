<script setup>
/**
 * Galería completa de proyectos (/proyectos).
 *
 * La vista tiene un selector en la parte superior con dos galerías:
 *
 *  - `clients`: proyectos desarrollados para clientes (WebContent tipo
 *    `portfolio` publicados, enviados por LandingController como prop
 *    `portfolio`). Cada tarjeta enlaza a la ficha del proyecto
 *    (/landing-projects/{id}).
 *  - `community`: proyectos propios liberados gratis para la comunidad
 *    (WebContent tipo `own_projects` publicados, prop `ownProjects`). Cada
 *    tarjeta enlaza al sitio o repositorio externo guardado en el back-office
 *    (`link_url`).
 *
 * Ambas galerías comparten la cuadrícula de imágenes cuadradas con bordes
 * redondeados y el título debajo.
 *
 * Como respaldo visual, la cabecera lleva una medalla con estrella y cinta (SVG
 * inline en `currentColor`) como fondo de la esquina superior derecha, en morado
 * de marca a muy baja opacidad, con fade-in al entrar, destellos que titilan y
 * tamaño escalado por tramos según el viewport: premia la calidad de lo
 * construido.
 */
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import { useProjects } from '@/Composables/useProjects';
import { useOwnProjects } from '@/Composables/useOwnProjects';
import { useT } from '@/Composables/useT';

const props = defineProps({
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
    portfolio: { type: Array, default: () => [] },
    ownProjects: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();

const clientProjects = useProjects(props.portfolio);
const communityProjects = useOwnProjects(props.ownProjects);

/* ---------------------------------------------
   Selector de galería
   --------------------------------------------- */
const activeTab = ref('clients');

const tabs = computed(() => [
    {
        id: 'clients',
        label: t('Developed for clients'),
        count: clientProjects.value.length,
    },
    {
        id: 'community',
        label: t('Developed for the community'),
        count: communityProjects.value.length,
    },
]);

const isCommunity = computed(() => activeTab.value === 'community');

/**
 * Tarjetas de la galería activa, normalizadas a un único modelo para que la
 * cuadrícula tenga un solo marcado: las de cliente abren su ficha interna y las
 * de comunidad el enlace externo del back-office.
 */
const activeProjects = computed(() => {
    if (isCommunity.value) {
        return communityProjects.value.map((project) => ({
            id: `community-${project.id}`,
            title: project.title,
            subtitle: project.subtitle,
            description: project.description,
            image: project.image,
            href: project.url || '',
            external: Boolean(project.url),
            free: true,
        }));
    }

    return clientProjects.value.map((project) => ({
        id: project.id,
        title: project.title,
        subtitle: project.subtitle,
        description: '',
        image: project.image,
        href: project.href,
        external: false,
        free: false,
    }));
});

/* ---------------------------------------------
   SEO
   --------------------------------------------- */
const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/proyectos`);
const ogImage = computed(() => clientProjects.value[0]?.image || page.props.metaImage || '');

const seoTitle = computed(() => t('All our projects'));
const seoDescription = computed(() =>
    isCommunity.value
        ? t('Tools we build and release for anyone who needs them.')
        : t(
              'Software, hardware and AI built for real operations. Every project starts with a process that had to be improved.',
          ),
);

const projectCount = computed(() => activeProjects.value.length);

const emptyMessage = computed(() =>
    isCommunity.value
        ? t('Our own projects are on their way.')
        : t('The first projects of our gallery are on their way.'),
);

/* ---------------------------------------------
   Ícono decorativo: destellos del fondo
   --------------------------------------------- */
/**
 * Destellos alrededor del ícono decorativo (la medalla).
 *
 * Se copian tal cual de la galería de eventos (/eventos) para que el fondo se
 * sienta igual en las dos vistas: posiciones fijas (nada de Math.random)
 * expresadas en porcentaje de la caja del ícono, y el titileo lo hace el CSS
 * con `--sparkle-delay` / `--sparkle-duration` (ver `.dtw-hero-sparkle` en
 * app.css, la misma clase que usa el hero de la portada).
 */
const medalSparkles = [
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
    <LandingShell :show-cta="false">
        <Head :title="seoTitle">
            <meta name="description" :content="seoDescription" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="seoTitle" />
            <meta property="og:description" :content="seoDescription" />
            <meta property="og:image" :content="ogImage" />
        </Head>

        <section class="relative pb-20 pt-32 md:pb-24 md:pt-40">
            <!--
                Halos del hero. Se recortan en su propio contenedor (`overflow-hidden`)
                para que el desenfoque no abra scroll lateral sin recortar también la
                medalla decorativa, que cae por debajo de esta sección.
            -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-dtw-void/70 via-dtw-void/20 to-dtw-void/80"></div>
                <div
                    class="absolute -top-40 left-1/2 h-[30rem] w-[56rem] -translate-x-1/2 rounded-full bg-dtw-brand/25 blur-[140px]"
                ></div>
            </div>

            <!--
                Medalla decorativa: roseta con estrella calada y cinta (glifo
                relleno, licencia MIT de Bootstrap Icons, incrustado como SVG para
                no depender de una fuente de iconos extra; la estrella se recorta
                con `fill-rule="evenodd"`). Se pinta con `currentColor`, así hereda
                el morado de marca a muy baja opacidad: se lee como silueta sobre
                el fondo oscuro sin competir con el texto. Va después de los halos
                (para quedar por encima de ellos) y antes del contenido (para
                quedar detrás de los textos y las tarjetas).
                El tamaño escala con la pantalla: `min()` se queda con la medida
                más pequeña entre el ancho y el alto del viewport, así en móvil
                la medalla no se desborda ni se corta y crece por tramos hasta el
                80 % del alto en escritorio (xl), el mismo ritmo que la copa de
                /eventos.
            -->
            <div
                aria-hidden="true"
                class="dtw-clip-x pointer-events-none absolute right-10 top-20 select-none motion-safe:animate-fade-in"
                :style="{ animationDuration: '1.4s', animationDelay: '0.4s' }"
            >
                <svg
                    class="block aspect-square w-[min(38vw,30vh)] text-dtw-primary/[0.2] sm:w-[min(34vw,38vh)] md:w-[min(38vw,52vh)] lg:text-[min(25vw,32vh)] xl:text-[40vh]"
                    viewBox="0 0 16 16"
                    fill="currentColor"
                >
                    <!-- Roseta con la estrella recortada en el centro -->
                    <path
                        fill-rule="evenodd"
                        d="m8 0 1.669.864 1.858.282.842 1.68 1.337 1.32L13.4 6l.306 1.854-1.337 1.32-.842 1.68-1.858.282L8 12l-1.669-.864-1.858-.282-.842-1.68-1.337-1.32L2.6 6l-.306-1.854 1.337-1.32.842-1.68L6.331.864z M8 2.4 8.882 4.787 11.424 4.888 9.427 6.464 10.116 8.912 8 7.5 5.884 8.912 6.573 6.464 4.576 4.888 7.118 4.787z"
                    />
                    <!-- Cinta -->
                    <path d="M4 11.794V16l4-1 4 1v-4.206l-2.018.306L8 13.126 6.018 12.1z" />
                </svg>

                <!-- Brillos alrededor de la medalla: aparecen y desaparecen como en el hero -->
                <div class="absolute inset-0 hidden sm:block">
                    <span
                        v-for="(sparkle, index) in medalSparkles"
                        :key="`medal-sparkle-${index}`"
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

            <div class="relative mx-auto w-full max-w-7xl px-5 md:px-8">
                <Link
                    href="/inicio"
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

                <!-- Selector de galería: proyectos de clientes / proyectos de la comunidad -->
                <div
                    role="group"
                    :aria-label="t('Project galleries')"
                    class="mt-8 inline-flex flex-col gap-1.5 rounded-[999px] border border-white/10 bg-white/[0.03] p-1.5 backdrop-blur sm:flex-row sm:items-center"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        :aria-pressed="activeTab === tab.id"
                        :class="
                            activeTab === tab.id
                                ? 'bg-dtw-primary text-white shadow-[0_18px_44px_-22px_rgba(168,85,247,0.95)]'
                                : 'text-dtw-muted hover:bg-white/[0.06] hover:text-white'
                        "
                        class="inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                        <span
                            class="dtw-tabular rounded-full px-2 py-0.5 text-[0.65rem] font-bold"
                            :class="activeTab === tab.id ? 'bg-black/25 text-white' : 'bg-white/10 text-dtw-muted'"
                        >
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

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
                    <!--
                        Tarjeta de proyecto. Los proyectos de cliente son enlaces
                        internos (Inertia) a su ficha; los de comunidad abren el
                        sitio o repositorio externo y, si aún no tiene enlace, se
                        muestran como tarjeta informativa.
                    -->
                    <component
                        :is="project.href ? (project.external ? 'a' : Link) : 'div'"
                        v-for="(project, index) in activeProjects"
                        :key="project.id"
                        :href="project.href || undefined"
                        :target="project.external ? '_blank' : undefined"
                        :rel="project.external ? 'noopener noreferrer' : undefined"
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
                                v-if="project.free"
                                class="pointer-events-none absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-dtw-cyan/40 bg-dtw-void/75 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-[0.18em] text-dtw-cyan backdrop-blur md:left-4 md:top-4"
                            >
                                <i class="pi pi-gift text-[0.7rem]"></i>
                                {{ t('Free') }}
                            </span>

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
                                <p
                                    v-if="project.description"
                                    class="mt-2 line-clamp-2 text-xs leading-relaxed text-dtw-dim md:text-sm"
                                >
                                    {{ project.description }}
                                </p>
                            </div>

                            <span
                                class="dtw-tabular shrink-0 pt-1 font-display text-[0.65rem] font-bold tracking-[0.28em] text-white/30"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                        </div>
                    </component>
                </div>

                <!-- Estado vacío de la galería activa -->
                <div
                    v-else
                    class="rounded-[28px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-14 text-center text-sm text-dtw-muted"
                >
                    {{ emptyMessage }}
                </div>
            </div>
        </section>
    </LandingShell>
</template>
