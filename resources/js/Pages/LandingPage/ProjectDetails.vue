<script setup>
/**
 * Detalle de proyecto: el resumen viaja como primera tarjeta del riel anclado y
 * detrás vienen las imágenes cuadradas (70% del alto de la pantalla). La vista
 * no lleva footer (ni banda de contacto): todo se centra en la galería y el
 * resumen, y el CTA de WhatsApp vive dentro del resumen.
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import LandingShell from '@/Components/Landing/LandingShell.vue';
import DetailStage from '@/Components/Landing/DetailStage.vue';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { trackWhatsAppClick } from '@/utils/analytics';

const props = defineProps({
    project: { type: Object, required: true },
    contact: { type: Object, default: () => ({}) },
    siteUrl: { type: String, default: '' },
    metaImage: { type: String, default: '' },
    solutions: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();
const { whatsappUrl } = useContact();

const locale = computed(() => page.props.locale || 'es');

const title = computed(() => {
    if (locale.value === 'es') {
        return props.project.spanish_title || props.project.english_title || '';
    }

    return props.project.english_title || props.project.spanish_title || '';
});

const rawContent = computed(() => {
    if (locale.value === 'es') {
        return props.project.spanish_content || props.project.english_content || '';
    }

    return props.project.english_content || props.project.spanish_content || '';
});

/** Texto plano: el detalle debe quedarse en unas cuantas líneas. */
const plainContent = computed(() =>
    rawContent.value
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .replace(/\s+/g, ' ')
        .trim(),
);

const subtitle = computed(() => props.project.subtitle || '');
const projectUrl = computed(() => props.project.link_url || '');

const images = computed(() =>
    (props.project.media || [])
        .filter((media) => media?.original_url)
        .map((media, index) => ({
            url: media.original_url,
            alt: `${title.value || t('Project')} (${index + 1})`,
        })),
);

const baseUrl = computed(() => (page.props.siteUrl || '').replace(/\/$/, ''));
const canonicalUrl = computed(() => `${baseUrl.value}/landing-projects/${props.project.id}`);
const ogImage = computed(() => images.value[0]?.url || page.props.metaImage || '');

const description = computed(() => {
    if (plainContent.value.length > 158) {
        return `${plainContent.value.slice(0, 155).trimEnd()}…`;
    }

    return plainContent.value || title.value;
});
</script>

<template>
    <LandingShell :show-cta="false" :show-footer="false">
        <Head :title="title">
            <meta name="description" :content="description" />
            <link rel="canonical" :href="canonicalUrl" />
            <meta property="og:type" content="article" />
            <meta property="og:title" :content="title" />
            <meta property="og:description" :content="description" />
            <meta property="og:image" :content="ogImage" />
            <meta property="og:url" :content="canonicalUrl" />
            <meta name="twitter:card" content="summary_large_image" />
        </Head>

        <div class="relative pt-16">
            <!-- Ambiente -->
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-32 top-24 h-96 w-96 rounded-full bg-dtw-brand/15 blur-[130px]"></div>
                <div class="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-dtw-cyan/[0.07] blur-[120px]"></div>
            </div>

            <DetailStage info-in-rail :images="images" :empty-label="t('This project has no images yet')">
                <template #info>
                    <Link
                        href="/soluciones"
                        class="group inline-flex items-center gap-2 text-sm font-medium text-dtw-muted transition hover:text-white"
                    >
                        <i class="pi pi-arrow-left text-xs transition-transform duration-300 group-hover:-translate-x-1"></i>
                        {{ t('Back to projects') }}
                    </Link>

                    <p
                        v-if="subtitle"
                        class="mt-5 text-[0.7rem] font-semibold uppercase tracking-[0.3em] text-dtw-primary-soft"
                    >
                        {{ subtitle }}
                    </p>

                    <h1
                        class="mt-3 font-display text-[clamp(1.5rem,2.6vw,2.3rem)] font-extrabold leading-[1.06] tracking-tight text-white"
                    >
                        {{ title }}
                    </h1>

                    <p
                        v-if="plainContent"
                        class="mt-4 line-clamp-[6] text-sm leading-relaxed text-dtw-muted"
                    >
                        {{ plainContent }}
                    </p>

                    <div class="mt-6 flex flex-wrap items-center gap-2.5">
                        <a
                            v-if="projectUrl"
                            :href="projectUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-4 py-2.5 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.07]"
                        >
                            {{ t('Visit website') }}
                            <i class="pi pi-arrow-up-right text-xs"></i>
                        </a>

                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full bg-dtw-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-dtw-primary-deep"
                            @click="trackWhatsAppClick('project_detail')"
                        >
                            <i class="pi pi-whatsapp text-base"></i>
                            {{ t('I want something like this') }}
                        </a>
                    </div>
                </template>
            </DetailStage>
        </div>
    </LandingShell>
</template>
