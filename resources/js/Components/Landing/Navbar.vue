<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { trackCtaClick } from '../../utils/analytics';

defineProps({
    whatsapp: { type: String, default: '' },
});

const page = usePage();
const locale = computed(() => page.props.locale || 'es');
const scrolled = ref(false);
const menuOpen = ref(false);

// Anclas internas de la landing (las secciones llevan scroll-margin-top).
const links = [
    { id: 'problemas', labelKey: 'Problems' },
    { id: 'calculadora', labelKey: 'Calculator' },
    { id: 'antes-despues', labelKey: 'Before / After' },
    { id: 'soluciones', labelKey: 'Solutions' },
    { id: 'proceso', labelKey: 'Process' },
    { id: 'sectores', labelKey: 'Industries' },
    { id: 'faq', labelKey: 'FAQ' },
];

function onScroll() {
    scrolled.value = window.scrollY > 20;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
});

function closeMenu() {
    menuOpen.value = false;
}

function onCtaClick() {
    trackCtaClick('navbar');
    closeMenu();
}
</script>

<template>
    <header
        :class="[
            'fixed inset-x-0 top-0 z-50 transition-colors duration-300',
            scrolled || menuOpen
                ? 'border-b border-white/10 bg-dtw-dark/95 backdrop-blur-md'
                : 'border-b border-transparent bg-transparent',
        ]"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <!-- Marca -->
            <a href="#top" class="flex items-center gap-3" :aria-label="t('DTW home')">
                <img
                    src="/images/white_logo.png"
                    alt="DTW"
                    class="h-8 w-auto sm:h-9"
                    width="120"
                    height="36"
                    loading="eager"
                    decoding="async"
                />
            </a>

            <!-- Navegación de escritorio -->
            <nav class="hidden items-center gap-1 lg:flex" :aria-label="t('Main navigation')">
                <a
                    v-for="link in links"
                    :key="link.id"
                    :href="`#${link.id}`"
                    class="rounded-full px-3 py-2 text-sm font-medium text-white/70 transition-colors hover:bg-white/5 hover:text-white"
                >
                    {{ t(link.labelKey) }}
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <!-- Selector de idioma -->
                <div
                    class="hidden items-center gap-1 rounded-full border border-white/15 bg-white/5 p-1 sm:flex"
                    role="group"
                    :aria-label="t('Change language')"
                >
                    <Link
                        :href="route('language.switch', 'es')"
                        preserve-scroll
                        :aria-current="locale === 'es' ? 'true' : undefined"
                        :class="[
                            'rounded-full px-2.5 py-1 text-xs font-semibold transition-colors',
                            locale === 'es' ? 'bg-dtw-primary text-white' : 'text-white/60 hover:text-white',
                        ]"
                    >
                        ES
                    </Link>
                    <Link
                        :href="route('language.switch', 'en')"
                        preserve-scroll
                        :aria-current="locale === 'en' ? 'true' : undefined"
                        :class="[
                            'rounded-full px-2.5 py-1 text-xs font-semibold transition-colors',
                            locale === 'en' ? 'bg-dtw-primary text-white' : 'text-white/60 hover:text-white',
                        ]"
                    >
                        EN
                    </Link>
                </div>

                <a
                    href="#contacto"
                    class="hidden items-center gap-2 rounded-full bg-dtw-primary px-5 py-2.5 text-sm font-semibold text-white shadow-dtw-glow transition hover:bg-dtw-primary-deep sm:inline-flex"
                    @click="onCtaClick"
                >
                    <i class="pi pi-calendar-plus text-xs" aria-hidden="true"></i>
                    {{ t('Request a free diagnosis') }}
                </a>

                <!-- Botón del menú móvil -->
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/5 text-white transition hover:bg-white/10 lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="dtw-mobile-menu"
                    :aria-label="menuOpen ? t('Close menu') : t('Open menu')"
                    @click="menuOpen = !menuOpen"
                >
                    <i :class="menuOpen ? 'pi pi-times' : 'pi pi-bars'" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <!-- Menú móvil -->
        <div v-show="menuOpen" id="dtw-mobile-menu" class="border-t border-white/10 bg-dtw-dark lg:hidden">
            <nav class="mx-auto grid max-w-7xl gap-1 px-4 py-4 sm:px-6" :aria-label="t('Main navigation')">
                <a
                    v-for="link in links"
                    :key="link.id"
                    :href="`#${link.id}`"
                    class="rounded-xl px-3 py-2.5 text-sm font-medium text-white/80 transition hover:bg-white/5 hover:text-white"
                    @click="closeMenu"
                >
                    {{ t(link.labelKey) }}
                </a>

                <a
                    href="#contacto"
                    class="mt-2 inline-flex items-center justify-center gap-2 rounded-full bg-dtw-primary px-5 py-3 text-sm font-semibold text-white"
                    @click="onCtaClick"
                >
                    <i class="pi pi-calendar-plus text-xs" aria-hidden="true"></i>
                    {{ t('Request a free diagnosis') }}
                </a>

                <div
                    class="mt-2 flex items-center justify-center gap-1 rounded-full border border-white/15 bg-white/5 p-1 sm:hidden"
                    role="group"
                    :aria-label="t('Change language')"
                >
                    <Link
                        :href="route('language.switch', 'es')"
                        preserve-scroll
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            locale === 'es' ? 'bg-dtw-primary text-white' : 'text-white/60',
                        ]"
                    >
                        ES
                    </Link>
                    <Link
                        :href="route('language.switch', 'en')"
                        preserve-scroll
                        :class="[
                            'rounded-full px-3 py-1 text-xs font-semibold',
                            locale === 'en' ? 'bg-dtw-primary text-white' : 'text-white/60',
                        ]"
                    >
                        EN
                    </Link>
                </div>
            </nav>
        </div>
    </header>
</template>
