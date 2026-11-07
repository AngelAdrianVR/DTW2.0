<script setup>
/**
 * Landing principal.
 *
 * Cuatro bloques, texto mínimo:
 *   1. Hero con la propuesta de valor.
 *   2. Proyectos: riel horizontal anclado con los proyectos hechos a clientes.
 *   3. Servicios: tarjetas apiladas con el detalle de cada solución y, debajo,
 *      el carrusel de proyectos propios gratis para la comunidad.
 *   4. Clientes: marquesina con los logos de las empresas que confían en DTW.
 *
 * El contenido vive en config/dtw.php (soluciones) y en el back-office
 * (proyectos y logos), así que no hay textos duplicados en el componente.
 */
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import HeroStage from '@/Components/Landing/HeroStage.vue';
import ProjectsRail from '@/Components/Landing/ProjectsRail.vue';
import ServiceStack from '@/Components/Landing/ServiceStack.vue';
import OwnProjectsRail from '@/Components/Landing/OwnProjectsRail.vue';
import ClientsSection from '@/Components/Landing/ClientsSection.vue';
import EventsSection from '@/Components/Landing/EventsSection.vue';
import { useT } from '@/Composables/useT';

defineProps({
    // Props que conserva LandingController (no romper el backend ni sus tests)
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
    portfolio: { type: Array, default: () => [] },
    ownProjects: { type: Array, default: () => [] },
    clientLogos: { type: Array, default: () => [] },
    advertising: { type: Array, default: () => [] },
    events: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();

const locale = computed(() => page.props.locale || 'es');
const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/inicio`);
const ogImage = computed(() => page.props.metaImage || `${baseUrl.value}/images/branding.webp`);

/* ---------------------------------------------
   SEO
   --------------------------------------------- */
const seoTitle = computed(() => t('Business automation and custom software in Mexico'));

const seoDescription = computed(() =>
    t(
        'We automate business processes for growing companies: custom software, integrations, dashboards and a free financial diagnosis to recover time and money. Based in Zapopan, Jalisco.',
    ),
);

const organizationSchema = computed(() => ({
    '@context': 'https://schema.org',
    '@type': 'ProfessionalService',
    name: 'DTW',
    url: canonicalUrl.value,
    logo: `${baseUrl.value}/images/black_logo.png`,
    image: ogImage.value,
    description: seoDescription.value,
    email: page.props.contact?.email || undefined,
    telephone: page.props.contact?.whatsapp ? `+${page.props.contact.whatsapp}` : undefined,
    address: {
        '@type': 'PostalAddress',
        addressLocality: 'Zapopan',
        addressRegion: 'Jalisco',
        addressCountry: 'MX',
    },
    areaServed: { '@type': 'Country', name: 'Mexico' },
    knowsAbout: [
        'Business process automation',
        'Custom software development',
        'Business intelligence dashboards',
        'System integration',
    ],
}));

const schemaId = 'dtw-organization-schema';

function injectSchema() {
    document.getElementById(schemaId)?.remove();

    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.id = schemaId;
    script.textContent = JSON.stringify(organizationSchema.value);
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
            <meta property="og:type" content="website" />
            <meta property="og:title" :content="seoTitle" />
            <meta property="og:description" :content="seoDescription" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta property="og:locale" :content="locale === 'es' ? 'es_MX' : 'en_US'" />
            <meta name="twitter:card" content="summary_large_image" />
        </Head>

        <HeroStage />

        <ProjectsRail :projects="portfolio" />

        <ServiceStack />

        <OwnProjectsRail :projects="ownProjects" />

        <EventsSection :events="events" />

        <ClientsSection :logos="clientLogos" />
    </LandingShell>
</template>
