<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, h } from 'vue';
import { Link, usePage, Head } from '@inertiajs/vue3';
import AplicationLogo from '@/Components/ApplicationLogo.vue';
import Button from 'primevue/button';

// --- Props ---
const props = defineProps({
    welcomeMessage: {
        type: String,
        default: '' // Mensaje de bienvenida opcional
    },
    title: String,
});

// --- Estado del Notch ---
const isHovering = ref(false);      // Para el hover en desktop
const isToggledOpen = ref(false);   // Para el clic en móvil
const showFlash = ref(false);
const showWelcome = ref(false);     // Para el mensaje de bienvenida al inicio
const flashData = ref({ message: '', type: 'success' });
const collapseTimer = ref(null);    // Timer para el retraso al plegarse
const notchRef = ref(null);         // Ref para el elemento <header> del notch

// --- Estado del Mensaje de Bienvenida ---
const typedMessage = ref('');       // El texto que se va mostrando
const isTyping = ref(false);        // Para controlar la visibilidad del cursor

// El notch se considera expandido si CUALQUIERA de estas condiciones es verdadera.
const isNotchExpanded = computed(() => isHovering.value || showFlash.value || showWelcome.value || isToggledOpen.value);

// --- Clases dinámicas para Borde y Sombra del Notch ---
const notchDynamicClasses = computed(() => {
    // 1. Animación de bienvenida: un brillo para la sombra y un borde con movimiento.
    if (showWelcome.value) {
        return 'animate-welcome-glow animated-border-welcome';
    }
    // 2. Colores para mensajes flash según el tipo.
    if (showFlash.value) {
        switch (flashData.value.type) {
            case 'success':
                return 'shadow-green-500/50 border-green-400';
            case 'error': // Tipo 'danger'
                return 'shadow-red-500/50 border-red-400';
            case 'warning':
                return 'shadow-amber-500/50 border-amber-400';
            default:
                return 'shadow-indigo-500/30 border-white/10'; // Color por defecto
        }
    }
    // 3. Estado normal por defecto.
    return 'shadow-indigo-500/30 border-white/10';
});


// --- Lógica de Mensaje de Bienvenida ---
onMounted(() => {
    // Si se pasó un mensaje de bienvenida, inicia el efecto de escritura.
    if (props.welcomeMessage) {
        showWelcome.value = true;
        isTyping.value = true;
        let i = 0;
        const typingInterval = setInterval(() => {
            if (i < props.welcomeMessage.length) {
                typedMessage.value += props.welcomeMessage.charAt(i);
                i++;
            } else {
                clearInterval(typingInterval);
                isTyping.value = false;
                // Espera 2 segundos después de terminar de escribir y luego oculta.
                setTimeout(() => {
                    showWelcome.value = false;
                }, 2000);
            }
        }, 100); // Velocidad de escritura (milisegundos por caracter)
    }

    // Agrega el listener para clics fuera del notch en móvil
    watch(isToggledOpen, (newValue) => {
        if (newValue) {
            document.addEventListener('click', handleClickOutside, true);
        } else {
            document.removeEventListener('click', handleClickOutside, true);
        }
    });
});

// Limpieza del listener al desmontar el componente
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


// --- Lógica de Interacción (Desktop/Móvil) ---
const handleMouseEnter = () => {
    if (window.innerWidth < 1024) return; // No activar con hover en pantallas pequeñas
    clearTimeout(collapseTimer.value);
    isHovering.value = true;
};

const handleMouseLeave = () => {
    if (window.innerWidth < 1024) return; // No activar con hover en pantallas pequeñas
    collapseTimer.value = setTimeout(() => {
        isHovering.value = false;
    }, 2000); // Retraso de 2 segundos para plegarse
};

const handleNotchClick = () => {
    if (window.innerWidth < 1024) { // Solo funciona con clic en pantallas pequeñas
        isToggledOpen.value = !isToggledOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (notchRef.value && !notchRef.value.contains(event.target)) {
        isToggledOpen.value = false;
    }
};

// --- Lógica de Idioma y Navegación ---
const currentLang = ref('es');
const toggleLang = () => {
    currentLang.value = currentLang.value === 'es' ? 'en' : 'es';
};

const navLinks = ref([
    { id: 'home', es: 'Inicio', en: 'Home', href: '/#inicio' },
    { id: 'services', es: 'Servicios', en: 'Services', href: '/#servicios' },
    { id: 'projects', es: 'Proyectos', en: 'Projects', href: '/#proyectos' },
    { id: 'contact', es: 'Contacto', en: 'Contact', href: '/#contacto' },
]);

// Propiedad computada para obtener los enlaces en el idioma actual
const localizedNavLinks = computed(() => {
    return navLinks.value.map(link => ({
        ...link,
        name: link[currentLang.value]
    }));
});


// --- Iconos SVG (Refactorizados como Componentes Funcionales) ---
const commonSvgProps = (className) => ({
    xmlns: "http://www.w3.org/2000/svg", width: "24", height: "24", viewBox: "0 0 24 24",
    fill: "none", stroke: "currentColor", "stroke-width": "2", "stroke-linecap": "round",
    "stroke-linejoin": "round", class: className
});

const HandWaveIcon = () => h('svg', commonSvgProps('h-6 w-6 text-indigo-300'), [
    h('path', { d: "M18 18.5a.5.5 0 0 1-1 0V7.5a.5.5 0 0 1 1 0v11Z" }),
    h('path', { d: "M13.5 18.5a.5.5 0 0 1-1 0V11a.5.5 0 0 1 1 0v7.5Z" }),
    h('path', { d: "M9.5 18.5a.5.5 0 0 1-1 0V9.5a.5.5 0 0 1 1 0v9Z" }),
    h('path', { d: "M6 18.5a.5.5 0 0 1-1 0V14a.5.5 0 0 1 1 0v4.5Z" }),
    h('path', { d: "M2 14h12.5a2 2 0 0 0 1.9-2.9l-3.3-6.4a2 2 0 0 0-3.8.1L6.7 12H2Z" })
]);

const GlobeIcon = () => h('svg', commonSvgProps('h-5 w-5'), [
    h('circle', { cx: "12", cy: "12", r: "10" }),
    h('line', { x1: "2", y1: "12", x2: "22", y2: "12" }),
    h('path', { d: "M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" })
]);

const CheckCircleIcon = () => h('svg', commonSvgProps('h-6 w-6 text-green-400'), [
    h('path', { d: "M22 11.08V12a10 10 0 1 1-5.93-9.14" }),
    h('polyline', { points: "22 4 12 14.01 9 11.01" })
]);

const AlertTriangleIcon = () => h('svg', commonSvgProps('h-6 w-6 text-red-400'), [
    h('path', { d: "m21.73 18-8-14a2 2 0 0 0-3.46 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" }),
    h('line', { x1: "12", y1: "9", y2: "13" }),
    h('line', { x1: "12", y1: "17", x2: "12.01", y2: "17" })
]);

</script>

<template>
    <Head :title="title" />
    <div class="bg-gray-800 min-h-screen text-gray-200 font-sans">
        <!-- =========== Notch Interactivo =========== -->
        <header
            ref="notchRef"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            @click="handleNotchClick"
            :class="[
                'fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center justify-center',
                'transition-all duration-500 ease-in-out',
                'bg-black/30 backdrop-blur-xl border-2 shadow-2xl',
                notchDynamicClasses, // Clase computada para color y animación de borde y sombra
                {
                    'w-32 h-10 rounded-full': !isNotchExpanded,
                    'w-[90vw] max-w-4xl h-16 rounded-3xl': isNotchExpanded,
                }
            ]"
        >
            <!-- Contenido del Notch -->
            <div class="w-full h-full flex items-center justify-between px-5 transition-opacity duration-300"
                 :class="{ 'opacity-0': !isNotchExpanded, 'opacity-100 delay-200': isNotchExpanded }">

                <!-- 1. Mensaje de Bienvenida (Máxima prioridad) -->
                <div v-if="showWelcome" class="w-full flex items-center justify-center gap-4 animate-fade-in">
                    <span class="text-white font-medium font-mono">{{ typedMessage }}</span>
                    <span v-if="isTyping" class="typing-cursor text-indigo-500 text-lg">_</span>
                </div>

                <!-- 2. Mensaje Flash (Segunda prioridad) -->
                <div v-else-if="showFlash" class="w-full flex items-center justify-center gap-4 animate-fade-in">
                    <CheckCircleIcon v-if="flashData.type === 'success'" />
                    <AlertTriangleIcon v-if="['error', 'warning'].includes(flashData.type)" />
                    <span class="text-white font-medium">{{ flashData.message }}</span>
                </div>

                <!-- 3. Contenido Normal (se muestra si no hay mensajes) -->
                <template v-else-if="isNotchExpanded">
                    <!-- Logo -->
                    <Link href="/" class="text-xl font-bold tracking-wider hover:text-indigo-500 transition-colors hidden sm:inline">
                        <AplicationLogo />
                    </Link>

                    <!-- Enlaces de Navegación -->
                    <nav class="flex-1 flex justify-center items-center space-x-2 sm:space-x-4 md:space-x-6">
                        <a v-for="link in localizedNavLinks" :key="link.id" :href="link.href"
                           class="text-xs sm:text-sm font-medium pb-1 border-b-2 border-transparent hover:border-indigo-500 hover:text-white transition-all duration-300">
                            {{ link.name }}
                        </a>
                    </nav>

                    <!-- Controles y Acciones -->
                    <div class="hidden md:flex items-center gap-4">
                        <button @click.stop="toggleLang" class="flex items-center gap-2 p-2 rounded-full hover:bg-white/10 transition-colors">
                            <GlobeIcon />
                            <span class="text-sm font-semibold uppercase">{{ currentLang }}</span>
                        </button>
                        <div class="w-px h-6 bg-white/10"></div>
                        <Button label="Login" severity="info" text />
                        <Button label="Registrarse" severity="info" raised />
                    </div>
                </template>
            </div>

            <!-- Indicador en estado colapsado -->
            <div
                v-if="!isNotchExpanded"
                class="absolute h-2 w-10 bg-indigo-500/50 rounded-full animate-pulse-slow"
            ></div>

        </header>
        <!-- ======================================= -->

        <main class="pt-28">
            <slot />
        </main>

        <!-- FOOTER -->
        <footer class="bg-black/30 backdrop-blur-lg border-t border-white/10 mt-20">
            <div class="container mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8 text-center lg:text-left">
                    <div class="flex flex-col items-center lg:items-start">
                        <Link href="/" class="text-2xl font-bold tracking-wider">
                            <AplicationLogo height="16" />
                        </Link>
                        <p class="mt-4 text-sm text-gray-400 max-w-xs">
                            Construyendo el futuro digital, una línea de código a la vez.
                        </p>
                    </div>
                    <div class="flex flex-col items-center">
                        <h3 class="font-semibold text-white tracking-wider">Navegación</h3>
                        <ul class="mt-4 space-y-2">
                            <li v-for="link in localizedNavLinks" :key="link.id">
                                <a :href="link.href" class="text-gray-400 hover:text-indigo-500 transition-colors">
                                    {{ link.name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="flex flex-col items-center lg:items-end">
                        <h3 class="font-semibold text-white tracking-wider">Síguenos</h3>
                        <div class="mt-4 flex space-x-5">
                            <a href="#" class="text-gray-400 hover:text-indigo-500 transition-colors"><i class="pi pi-github" style="font-size: 1.5rem"></i></a>
                            <a href="#" class="text-gray-400 hover:text-indigo-500 transition-colors"><i class="pi pi-linkedin" style="font-size: 1.5rem"></i></a>
                            <a href="#" class="text-gray-400 hover:text-indigo-500 transition-colors"><i class="pi pi-twitter" style="font-size: 1.5rem"></i></a>
                        </div>
                    </div>
                </div>
                <div class="mt-12 pt-8 border-t border-white/10 text-center text-gray-500 text-sm">
                    &copy; {{ new Date().getFullYear() }} DTW. Todos los derechos reservados.
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Animaciones personalizadas con Keyframes */
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out forwards;
}

@keyframes pulse-slow {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(0.95);
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Animación de brillo para la SOMBRA del mensaje de bienvenida */
@keyframes welcome-glow {
  0%   { box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.4); } /* Indigo */
  25%  { box-shadow: 0 25px 50px -12px rgba(236, 72, 153, 0.4); } /* Pink */
  50%  { box-shadow: 0 25px 50px -12px rgba(22, 237, 244, 0.4); } /* Cyan */
  75%  { box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.4); } /* Violet */
  100% { box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.4); } /* Indigo */
}

.animate-welcome-glow {
  animation: welcome-glow 5s ease-in-out infinite;
}

/* --- NUEVA ANIMACIÓN DE BORDE --- */
.animated-border-welcome {
  position: relative; /* Necesario para el posicionamiento del pseudo-elemento */
  border-color: transparent; /* Oculta el borde base para mostrar el animado */
}

.animated-border-welcome::before {
  content: "";
  position: absolute;
  z-index: -1;
  top: -2px; left: -2px; right: -2px; bottom: -2px;
  background: linear-gradient(120deg, #6215C0, #17EDF4, #6215C0);
  background-size: 400% 400%;
  border-radius: inherit;
  animation: borderGradientMove 5s linear infinite;
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  mask-composite: exclude;
  -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out;
}

@keyframes borderGradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}
/* --- FIN NUEVA ANIMACIÓN DE BORDE --- */

/* Animación para el cursor de escritura */
@keyframes blink {
  50% { opacity: 0; }
}

.typing-cursor {
  animation: blink 1s step-end infinite;
}
</style>
