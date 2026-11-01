<script setup>
/**
 * Barra de navegación de la landing.
 *
 * - Transparente sobre el hero y con fondo glass al hacer scroll.
 * - Se oculta al bajar y reaparece al subir (o al volver arriba).
 * - Navegación por anclas sin recargar cuando ya estamos en la landing.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useT } from '@/Composables/useT';
import { useContact } from '@/Composables/useContact';
import { trackWhatsAppClick } from '@/utils/analytics';

const t = useT();
const page = usePage();
const { whatsappUrl } = useContact();

const locale = computed(() => page.props.locale || 'es');
const isHome = computed(() => page.component === 'LandingPage/Index');

const links = computed(() => [
    { id: 'proyectos', label: t('Projects') },
    { id: 'detalle-servicios', label: t('Services') },
    { id: 'clientes', label: t('Clients') },
]);

const hidden = ref(false);
const scrolled = ref(false);
const menuOpen = ref(false);

let lastY = 0;
let frame = null;

function onScroll() {
    if (frame !== null) {
        return;
    }

    frame = requestAnimationFrame(() => {
        frame = null;

        const y = Math.max(0, window.scrollY);
        const delta = y - lastY;

        scrolled.value = y > 16;

        if (menuOpen.value || y < 96) {
            hidden.value = false;
        } else if (delta > 6) {
            hidden.value = true;
        } else if (delta < -6) {
            hidden.value = false;
        }

        lastY = y;
    });
}

function goToSection(id) {
    menuOpen.value = false;

    const hash = `#${id}`;

    if (isHome.value) {
        document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    // Desde otra vista: volvemos a la landing y saltamos a la sección cuando
    // la cortina ya terminó de revelar la página.
    router.visit('/', {
        onFinish: () => {
            window.setTimeout(() => {
                document.querySelector(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 560);
        },
    });
}

function closeMenu() {
    menuOpen.value = false;
}

onMounted(() => {
    lastY = window.scrollY;
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);

    if (frame !== null) {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <header
        class="fixed inset-x-0 top-0 z-50 transition-[transform,background-color,border-color,backdrop-filter] duration-500 ease-[cubic-bezier(0.22,1,0.36,1)]"
        :class="[
            hidden ? '-translate-y-[110%]' : 'translate-y-0',
            scrolled || menuOpen
                ? 'border-b border-white/10 bg-dtw-void/80 backdrop-blur-xl'
                : 'border-b border-transparent bg-transparent',
        ]"
    >
        <div class="mx-auto flex h-20 w-full max-w-7xl items-center justify-between gap-6 px-5 md:px-8">
            <!-- Logo -->
            <Link href="/" class="group flex items-center gap-3" :aria-label="t('Home')">
                <img
                    src="/images/white_logo.png"
                    :alt="t('DTW logo')"
                    class="h-8 w-auto transition-transform duration-300 group-hover:scale-105 md:h-9"
                />
            </Link>

            <!-- Navegación desktop -->
            <nav class="hidden items-center gap-1 lg:flex" :aria-label="t('Main navigation')">
                <button
                    v-for="link in links"
                    :key="link.id"
                    type="button"
                    class="rounded-full px-4 py-2 text-sm font-medium text-dtw-muted transition-colors hover:bg-white/5 hover:text-white"
                    @click="goToSection(link.id)"
                >
                    {{ link.label }}
                </button>

                <Link
                    href="/eventos"
                    class="rounded-full px-4 py-2 text-sm font-medium text-dtw-muted transition-colors hover:bg-white/5 hover:text-white"
                >
                    {{ t('Events') }}
                </Link>

                <Link
                    href="/proyectos"
                    class="rounded-full px-4 py-2 text-sm font-medium text-dtw-muted transition-colors hover:bg-white/5 hover:text-white"
                >
                    {{ t('All projects') }}
                </Link>
            </nav>

            <div class="flex items-center gap-2 md:gap-3">
                <!-- Idioma -->
                <div
                    class="hidden items-center rounded-full border border-white/10 bg-white/5 p-1 md:flex"
                    role="group"
                    :aria-label="t('Change language')"
                >
                    <Link
                        :href="route('language.switch', 'es')"
                        preserve-scroll
                        :aria-current="locale === 'es' ? 'true' : undefined"
                        class="rounded-full px-2.5 py-1 text-xs font-semibold transition-colors"
                        :class="locale === 'es' ? 'bg-dtw-primary text-white' : 'text-dtw-muted hover:text-white'"
                    >
                        ES
                    </Link>
                    <Link
                        :href="route('language.switch', 'en')"
                        preserve-scroll
                        :aria-current="locale === 'en' ? 'true' : undefined"
                        class="rounded-full px-2.5 py-1 text-xs font-semibold transition-colors"
                        :class="locale === 'en' ? 'bg-dtw-primary text-white' : 'text-dtw-muted hover:text-white'"
                    >
                        EN
                    </Link>
                </div>

                <!-- CTA -->
                <a
                    :href="whatsappUrl"
                    target="_blank"
                    rel="noopener"
                    class="group hidden items-center gap-2 rounded-full bg-dtw-primary px-5 py-2.5 text-sm font-semibold text-white shadow-[0_10px_40px_-12px_rgba(168,85,247,0.8)] transition hover:bg-dtw-primary-deep sm:inline-flex"
                    @click="trackWhatsAppClick('navbar')"
                >
                    <i class="pi pi-whatsapp text-base"></i>
                    <span>{{ t("Let's talk") }}</span>
                </a>

                <!-- Menú móvil -->
                <button
                    type="button"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white transition hover:bg-white/10 lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="dtw-mobile-menu"
                    :aria-label="menuOpen ? t('Close menu') : t('Open menu')"
                    @click="menuOpen = !menuOpen"
                >
                    <i :class="menuOpen ? 'pi pi-times' : 'pi pi-bars'" class="text-lg"></i>
                </button>
            </div>
        </div>
        <!-- Panel móvil -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="-translate-y-4 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="-translate-y-4 opacity-0"
        >
            <div v-if="menuOpen" id="dtw-mobile-menu" class="border-t border-white/10 lg:hidden">
                <nav class="mx-auto flex w-full max-w-7xl flex-col gap-1 px-5 py-5">
                    <button
                        v-for="link in links"
                        :key="link.id"
                        type="button"
                        class="rounded-xl px-3 py-3 text-left text-base font-medium text-dtw-ink transition hover:bg-white/5"
                        @click="goToSection(link.id)"
                    >
                        {{ link.label }}
                    </button>

                    <Link
                        href="/eventos"
                        class="rounded-xl px-3 py-3 text-base font-medium text-dtw-ink transition hover:bg-white/5"
                        @click="closeMenu"
                    >
                        {{ t('Events') }}
                    </Link>

                    <Link
                        href="/proyectos"
                        class="rounded-xl px-3 py-3 text-base font-medium text-dtw-ink transition hover:bg-white/5"
                        @click="closeMenu"
                    >
                        {{ t('All projects') }}
                    </Link>

                    <div class="mt-3 flex items-center justify-between gap-3 border-t border-white/10 pt-4">
                        <div class="flex items-center rounded-full border border-white/10 bg-white/5 p-1">
                            <Link
                                :href="route('language.switch', 'es')"
                                preserve-scroll
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="locale === 'es' ? 'bg-dtw-primary text-white' : 'text-dtw-muted'"
                            >
                                ES
                            </Link>
                            <Link
                                :href="route('language.switch', 'en')"
                                preserve-scroll
                                class="rounded-full px-3 py-1 text-xs font-semibold"
                                :class="locale === 'en' ? 'bg-dtw-primary text-white' : 'text-dtw-muted'"
                            >
                                EN
                            </Link>
                        </div>

                        <a
                            :href="whatsappUrl"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full bg-dtw-primary px-4 py-2 text-sm font-semibold text-white"
                            @click="trackWhatsAppClick('navbar_mobile')"
                        >
                            <i class="pi pi-whatsapp"></i>
                            {{ t("Let's talk") }}
                        </a>
                    </div>
                </nav>
            </div>
        </transition>
    </header>
</template>
