<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, h } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AplicationLogo from '@/Components/ApplicationLogo.vue';
import Button from 'primevue/button';

// --- Props ---
const props = defineProps({
    welcomeMessage: {
        type: String,
        default: '' // Mensaje de bienvenida opcional
    }
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
    }, 2000); // Retraso de 3 segundos para plegarse
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

// --- Estado de Idioma ---
const currentLang = ref('es');
const toggleLang = () => {
    currentLang.value = currentLang.value === 'es' ? 'en' : 'es';
};

// --- Enlaces de Navegación ---
const navLinks = ref([
    { name: 'Inicio', href: '/#inicio' },
    { name: 'Servicios', href: '/#servicios' },
    { name: 'Proyectos', href: '/#proyectos' },
    { name: 'Contacto', href: '/#contacto' },
]);

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
                'bg-black/30 backdrop-blur-xl border border-white/10 shadow-2xl shadow-indigo-500/30',
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
                    <!-- <HandWaveIcon/> -->
                    <span class="text-white font-medium font-mono">{{ typedMessage }}</span>
                    <span v-if="isTyping" class="typing-cursor text-indigo-500 text-lg">_</span>
                </div>

                <!-- 2. Mensaje Flash (Segunda prioridad) -->
                <div v-else-if="showFlash" class="w-full flex items-center justify-center gap-4 animate-fade-in">
                    <CheckCircleIcon v-if="flashData.type === 'success'" />
                    <AlertTriangleIcon v-if="flashData.type === 'error'" />
                    <span class="text-white font-medium">{{ 'flashData.message' }}</span>
                </div>

                <!-- 3. Contenido Normal (se muestra si no hay mensajes) -->
                <template v-else-if="isNotchExpanded">
                    <!-- Logo -->
                    <Link href="/" class="text-xl font-bold tracking-wider hover:text-indigo-500 transition-colors hidden sm:inline">
                        <AplicationLogo />
                    </Link>

                    <!-- Enlaces de Navegación -->
                    <nav class="flex-1 flex justify-center items-center space-x-2 sm:space-x-4 md:space-x-6">
                        <a v-for="link in navLinks" :key="link.name" :href="link.href"
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

        <!-- FOOTER (Sin cambios) -->
        <footer class="bg-black/30 backdrop-blur-lg border-t border-white/10 mt-20">
            <div class="container mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid lg:grid-cols-3 gap-8 text-center lg:text-left">
                    <div class="flex flex-col items-center lg:items-start">
                        <Link href="/" class="text-2xl font-bold tracking-wider">
                            <span class="text-indigo-500">DEV</span>SOLUTION
                        </Link>
                        <p class="mt-4 text-sm text-gray-400 max-w-xs">
                            Construyendo el futuro digital, una línea de código a la vez.
                        </p>
                    </div>
                    <div class="flex flex-col items-center">
                        <h3 class="font-semibold text-white tracking-wider">Navegación</h3>
                        <ul class="mt-4 space-y-2">
                            <li v-for="link in navLinks" :key="link.name">
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
                    &copy; {{ new Date().getFullYear() }} DEVSOLUTION. Todos los derechos reservados.
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

/* NUEVA animación para el cursor de escritura */
@keyframes blink {
  50% { opacity: 0; }
}

.typing-cursor {
  animation: blink 1s step-end infinite;
}
</style>
