<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, h } from 'vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import AplicationLogo from '@/Components/ApplicationLogo.vue';
import Button from 'primevue/button';
import MiniGlobe from '@/Components/MyComponents/MiniGlobe.vue'; // Asegúrate de que la ruta sea correcta

// --- Props ---
const props = defineProps({
    welcomeMessage: {
        type: String,
        default: '' // Mensaje de bienvenida opcional
    },
    title: String,
});

// --- Estado del Notch ---
const isHovering = ref(false);
const isToggledOpen = ref(false);
const showFlash = ref(false);
const showWelcome = ref(false);
const flashData = ref({ message: '', type: 'success' });
const collapseTimer = ref(null);
const notchRef = ref(null);
const isMobileMenuOpen = ref(false);

// --- Estado del Mensaje de Bienvenida ---
const typedMessage = ref('');
const isTyping = ref(false);

const isNotchExpanded = computed(() => isHovering.value || showFlash.value || showWelcome.value || isToggledOpen.value);

const headerPositionClass = computed(() => {
    return showWelcome.value ? 'absolute' : 'fixed';
});

const notchDynamicClasses = computed(() => {
    if (showWelcome.value) {
        return 'animate-welcome-glow animated-border-welcome';
    }
    if (showFlash.value) {
        switch (flashData.value.type) {
            case 'success':
                return 'shadow-green-500/50 border-green-400';
            case 'error':
                return 'shadow-red-500/50 border-red-400';
            case 'warning':
                return 'shadow-amber-500/50 border-amber-400';
            default:
                return 'shadow-indigo-500/30 border-white/10';
        }
    }
    return 'shadow-indigo-500/30 border-white/10';
});


// --- Lógica de Mensaje de Bienvenida ---
onMounted(() => {
    if (props.welcomeMessage) {
        setTimeout(() => {
            showWelcome.value = true;
        }, 100)
        isTyping.value = true;
        let i = 0;
        const typingInterval = setInterval(() => {
            if (i < props.welcomeMessage.length) {
                typedMessage.value += props.welcomeMessage.charAt(i);
                i++;
            } else {
                clearInterval(typingInterval);
                isTyping.value = false;
                setTimeout(() => {
                    showWelcome.value = false;
                }, 1500);
            }
        }, 70);
    }

    watch(isToggledOpen, (newValue) => {
        if (newValue) {
            document.addEventListener('click', handleClickOutside, true);
        } else {
            document.removeEventListener('click', handleClickOutside, true);
        }
    });
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside, true);
});


// --- Lógica de Flash Messages (Inertia) ---
const page = usePage();
watch(() => page.props.flash, (newFlash) => {
    if (newFlash && newFlash.message) {
        flashData.value = {
            message: newFlash.message,
            type: newFlash.type || 'success'
        };
        showFlash.value = true;
        setTimeout(() => {
            showFlash.value = false;
        }, 5000);
    }
}, { deep: true });

// Watcher para cerrar el menú móvil si el notch se contrae.
watch(isNotchExpanded, (newValue) => {
    if (!newValue) {
        isMobileMenuOpen.value = false;
    }
});

// --- Lógica de Interacción (Desktop/Móvil) ---
const handleMouseEnter = () => {
    if (window.innerWidth < 1024) return;
    clearTimeout(collapseTimer.value);
    isHovering.value = true;
};

const handleMouseLeave = () => {
    if (window.innerWidth < 1024) return;
    collapseTimer.value = setTimeout(() => {
        isHovering.value = false;
    }, 2000);
};

const handleNotchClick = () => {
    if (window.innerWidth < 1024) {
        isToggledOpen.value = !isToggledOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (notchRef.value && !notchRef.value.contains(event.target)) {
        isToggledOpen.value = false;
    }
};

// --- Lógica de Navegación ---
const navLinks = ref([
    { id: 'home', key: 'Home', href: '/#inicio' },
    { id: 'services', key: 'Services', href: '/#servicios' },
    { id: 'projects', key: 'Projects', href: '/#proyectos' },
    { id: 'contact', key: 'Contact', href: '/#contacto' },
]);

// --- Iconos SVG ---
const commonSvgProps = (className) => ({
    xmlns: "http://www.w3.org/2000/svg", width: "24", height: "24", viewBox: "0 0 24 24",
    fill: "none", stroke: "currentColor", "stroke-width": "2", "stroke-linecap": "round",
    "stroke-linejoin": "round", class: className
});

const CheckCircleIcon = () => h('svg', commonSvgProps('h-6 w-6 text-green-400'), [
    h('path', { d: "M22 11.08V12a10 10 0 1 1-5.93-9.14" }),
    h('polyline', { points: "22 4 12 14.01 9 11.01" })
]);

const AlertTriangleIcon = () => h('svg', commonSvgProps('h-6 w-6 text-amber-400'), [
    h('path', { d: "m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" }),
    h('line', { x1: "12", y1: "9", y2: "13" }),
    h('line', { x1: "12", y1: "17", x2: "12.01", y2: "17" })
]);

const XCircleIcon = () => h('svg', commonSvgProps('h-6 w-6 text-red-400'), [
    h('circle', { cx: "12", cy: "12", r: "10" }),
    h('line', { x1: "15", y1: "9", x2: "9", y2: "15" }),
    h('line', { x1: "9", y1: "9", x2: "15", y2: "15" })
]);


const MenuIcon = () => h('svg', commonSvgProps('h-6 w-6'), [
    h('line', { x1: "3", y1: "12", x2: "21", y2: "12" }),
    h('line', { x1: "3", y1: "6", x2: "21", y2: "6" }),
    h('line', { x1: "3", y1: "18", x2: "21", y2: "18" })
]);

const XIcon = () => h('svg', commonSvgProps('h-6 w-6'), [
    h('line', { x1: "18", y1: "6", x2: "6", y2: "18" }),
    h('line', { x1: "6", y1: "6", x2: "18", y2: "18" })
]);
</script>

<template>
    <Head :title="title" />
    <div class="bg-gray-900 min-h-screen text-gray-200 font-sans selection:bg-indigo-500/30">
        <!-- =========== Notch Interactivo =========== -->
        <header
            ref="notchRef"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            @click="handleNotchClick"
            :class="[
                headerPositionClass,
                'top-4 left-1/2 -translate-x-1/2 z-50 flex items-center justify-center',
                'transition-all duration-500 ease-in-out',
                'bg-black/30 backdrop-blur-xl border-2 shadow-2xl',
                notchDynamicClasses,
                {
                    'w-32 h-10 rounded-full': !isNotchExpanded,
                    'w-[90vw] max-w-4xl h-16 rounded-3xl': isNotchExpanded,
                }
            ]"
        >
            <!-- Contenido del Notch -->
            <div class="w-full h-full flex items-center justify-between px-5 transition-opacity duration-300"
                 :class="{ 'opacity-0': !isNotchExpanded, 'opacity-100 delay-200': isNotchExpanded }">

                <!-- Mensaje flash -->
                <div v-if="showFlash" class="w-full flex items-center justify-center gap-4 animate-fade-in">
                    <CheckCircleIcon v-if="flashData.type === 'success'" />
                    <AlertTriangleIcon v-if="flashData.type === 'warning'" />
                    <XCircleIcon v-if="flashData.type === 'error'" />
                    <span class="text-white font-medium">{{ flashData.message }}</span>
                </div>

                <!-- Mensaje de bienvenida -->
                <div v-else-if="showWelcome" class="w-full flex items-center justify-center gap-4 animate-fade-in">
                    <span class="text-white font-medium font-mono">{{ typedMessage }}</span>
                    <span v-if="isTyping" class="typing-cursor text-indigo-500 text-lg">_</span>
                </div>

                <!-- Contenido Normal -->
                <template v-else-if="isNotchExpanded">
                    <!-- Izquierda: Logo -->
                    <Link href="/" class="shrink-0">
                        <AplicationLogo class="h-8 w-auto" />
                    </Link>

                    <!-- Centro: Navegación Desktop -->
                    <nav class="hidden sm:flex flex-1 justify-center items-center space-x-2 sm:space-x-4 md:space-x-6">
                        <a v-for="link in navLinks" :key="link.id" :href="link.href"
                           class="text-xs sm:text-sm font-medium pb-1 border-b-2 border-transparent hover:border-indigo-500 hover:text-white transition-all duration-300">
                            {{ t(link.key) }}
                        </a>
                    </nav>

                    <!-- Centro: Botón de Menú Móvil -->
                    <div class="flex-1 flex justify-center items-center sm:hidden">
                        <button @click.stop="isMobileMenuOpen = !isMobileMenuOpen" class="p-2 rounded-full hover:bg-white/10 transition-colors z-10">
                            <XIcon v-if="isMobileMenuOpen" />
                            <MenuIcon v-else />
                        </button>
                    </div>

                    <!-- Derecha: Idioma y Acciones -->
                    <div class="flex items-center justify-end gap-2 sm:gap-4 shrink-0">
                        <div class="flex items-center gap-1 p-1 rounded-full bg-white/5">
                            <Link :href="route('language.switch', 'es')" preserve-scroll
                                  :class="['p-1 px-2 sm:px-3 rounded-full text-xs font-semibold transition-colors',
                                  $page.props.locale === 'es' ? 'bg-indigo-500 text-white' : 'hover:bg-white/10']"
                            >
                                ES
                            </Link>
                            <Link :href="route('language.switch', 'en')" preserve-scroll
                                  :class="['p-1 px-2 sm:px-3 rounded-full text-xs font-semibold transition-colors',
                                  $page.props.locale === 'en' ? 'bg-indigo-500 text-white' : 'hover:bg-white/10']"
                            >
                                EN
                            </Link>
                        </div>

                        <!-- <div class="hidden md:flex items-center gap-4">
                            <div class="w-px h-6 bg-white/10"></div>
                            <Button @click="$inertia.visit(route('login'))" :label="t('Login')" severity="info" text />
                            <Button @click="$inertia.visit(route('register'))" :label="t('Register')" severity="info" raised />
                        </div> -->
                    </div>
                </template>
            </div>

            <div v-if="!isNotchExpanded" class="absolute h-2 w-10 bg-indigo-500/50 rounded-full animate-pulse-slow"></div>
        </header>

        <main class="pt-20">
            <slot />
        </main>

        <!-- Menú desplegable para móvil -->
        <transition name="slide-fade">
            <div v-if="isMobileMenuOpen" 
                 class="fixed inset-0 top-0 pt-24 z-40 sm:hidden" 
                 @click="isMobileMenuOpen = false">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>
                <div class="relative container mx-auto px-4">
                    <nav class="bg-black/60 backdrop-blur-xl rounded-2xl border border-white/10 p-4 flex flex-col gap-2">
                        <a v-for="link in navLinks" :key="link.id" :href="link.href" @click="isMobileMenuOpen = false"
                           class="block text-center py-3 text-lg font-semibold hover:bg-indigo-500/20 rounded-lg transition-colors">
                           {{ t(link.key) }}
                        </a>
                    </nav>
                </div>
            </div>
        </transition>

        <!-- ================== FOOTER MEJORADO ================== -->
        <footer class="bg-gray-900/50 backdrop-blur-lg mt-20 relative overflow-hidden">
            <!-- Efecto de borde animado -->
            <div class="absolute top-0 left-0 w-full h-px footer-border-glow"></div>
            
            <div class="container mx-auto py-16 px-6 lg:px-8">
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 text-center md:text-left">
                    
                    <!-- Columna 1: Logo y Lema con MiniGlobo -->
                    <div class="flex flex-col items-center md:items-start relative h-44">
                        <Link href="/login" class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2">
                            <AplicationLogo class="h-12" />
                        </Link>

                        <MiniGlobe />

                        <p class="text-sm text-gray-400 w-full text-center">
                            {{ t('Building the digital future, one line of code at a time.') }}
                        </p>
                    </div>

                    <!-- Columna 2: Navegación -->
                    <div>
                        <h3 class="font-semibold text-white tracking-wider uppercase">{{ t('Navigation') }}</h3>
                        <ul class="mt-4 space-y-3">
                            <li v-for="link in navLinks" :key="link.id">
                                <a :href="link.href" class="text-gray-400 hover:text-indigo-400 transition-colors duration-300 group">
                                    {{ t(link.key) }}
                                    <span class="block max-w-0 group-hover:max-w-full transition-all duration-500 h-0.5 bg-indigo-500 mx-auto md:mx-0"></span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Columna 3: Legal -->
                     <div>
                        <h3 class="font-semibold text-white tracking-wider uppercase">{{ t('Legal') }}</h3>
                        <ul class="mt-4 space-y-3">
                            <li><Link :href="route('terms.show')" class="text-gray-400 hover:text-indigo-400 transition-colors duration-300">{{ t('Terms of Service') }}</Link></li>
                            <li><Link :href="route('privacy.show')" class="text-gray-400 hover:text-indigo-400 transition-colors duration-300">{{ t('Privacy Policy') }}</Link></li>
                        </ul>
                    </div>

                    <!-- Columna 4: Contacto -->
                    <div class="flex flex-col items-center md:items-start">
                         <h3 class="font-semibold text-white tracking-wider uppercase">{{ t('Contact') }}</h3>
                        <ul class="mt-4 space-y-4">
                            <li class="flex items-center justify-center md:justify-start">
                                <a href="https://wa.me/5213321705650" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-indigo-400 transition-colors duration-300 flex items-center gap-3 group">
                                    <!-- <component :is="WhatsAppIcon" class="..."/> -->
                                    <span>+52 1 33 2170 5650</span>
                                </a>
                            </li>
                            <li class="flex items-center justify-center md:justify-start">
                                <a href="mailto:contacto@dtw.com.mx" class="text-gray-400 hover:text-indigo-400 transition-colors duration-300 flex items-center gap-3 group">
                                    <!-- <component :is="MailIcon" class="..."/> -->
                                    <span>contacto@dtw.com.mx</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-12 pt-8 border-t border-white/10 text-center text-gray-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} DTW. {{ t('All rights reserved.') }}
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/*
  SOLUCIÓN: Se ha añadido el prefijo "ll-" (por LandingLayout) a todas las
  definiciones de @keyframes y a las clases que las usan. Esto evita
  que los nombres de las animaciones colisionen con otros estilos globales
  en la aplicación.
*/
@keyframes ll-fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: ll-fade-in 0.5s ease-out forwards;
}
@keyframes ll-pulse-slow {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.95); }
}
.animate-pulse-slow {
  animation: ll-pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes ll-welcome-glow {
  0%   { box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.4); }
  25%  { box-shadow: 0 25px 50px -12px rgba(236, 72, 153, 0.4); }
  50%  { box-shadow: 0 25px 50px -12px rgba(22, 237, 244, 0.4); }
  75%  { box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.4); }
  100% { box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.4); }
}
.animate-welcome-glow {
  animation: ll-welcome-glow 5s ease-in-out infinite;
}
.animated-border-welcome {
  position: relative;
  border-color: transparent;
}
.animated-border-welcome::before {
  content: "";
  position: absolute;
  z-index: -1;
  top: -2px; left: -2px; right: -2px; bottom: -2px;
  background: linear-gradient(120deg, #6215C0, #17EDF4, #6215C0);
  background-size: 400% 400%;
  border-radius: inherit;
  animation: ll-borderGradientMove 5s linear infinite;
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  mask-composite: exclude;
  -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
}
@keyframes ll-borderGradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
@keyframes ll-blink {
  50% { opacity: 0; }
}
.typing-cursor {
  animation: ll-blink 1s step-end infinite;
}

/* Transición del menú móvil */
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}

/* =========== ESTILOS DEL NUEVO FOOTER =========== */
.footer-border-glow {
    background: linear-gradient(to right, transparent, #4f46e5, transparent);
    animation: ll-glow-animation 4s linear infinite;
}

@keyframes ll-glow-animation {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}
</style>

