<script setup>
import { ref, computed, onMounted, watchEffect, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Sidebar from '@/Components/MyComponents/SideBar.vue';
import BottomNavBar from '@/Components/MyComponents/BottomNavBar.vue';
// 1. Importa el PomodoroTimer y el composable
import PomodoroTimer from '@/Components/MyComponents/PomodoroTimer.vue';
import { usePomodoro } from '@/Composables/usePomodoro';

const props = defineProps({
    title: String,
});

// 2. Usa el composable
const { state, displayTime } = usePomodoro();

// 3. Crea el título dinámico para la pestaña del navegador
const pageTitle = computed(() => {
    if (state.isRunning) {
        return `${displayTime.value} - ${state.isWorkSession ? 'Enfoque' : 'Descanso'}`;
    }
    return props.title;
});


// Definición de los módulos de navegación con más items para la barra inferior
const navigationMenu = computed(() => [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" /></svg>`,
        active: route().current('dashboard'),
        show: true,
    },
    {
        name: 'CRM',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" /></svg>`,
        active: route().current('dashboard'),
        show: true,
        children: [
            {
                name: 'Clientes',
                route: 'clients.index',
                active: route().current('clients.*'),
            },
            {
                name: 'Cotizaciones',
                route: 'quotes.index',
                active: route().current('quotes.*'),
            }
        ]
    },
    {
        name: 'PMS',
        route: 'projects.index',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" /></svg>`,
        active: route().current('projects.*'),
        show: true,
    },
    {
        name: 'RRHH',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>`,
        active: route().current('dashboard'),
        show: true,
        children: [
            {
                name: 'Usuarios',
                route: 'users.index',
                active: route().current('users.*'),
            },
        ]
    },
    {
        name: 'Hostings',
        route: 'hosting-clients.index',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>`,
        active: route().current('hosting-clients.*'),
        show: true,
    },
    {
        name: 'Recursos Web',
        route: 'admin.webcontents.index',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>`,
        active: route().current('admin.webcontents.*'),
        show: true,
    },
]);

// --- Lógica para el Modo Oscuro ---
const isDarkMode = ref(false);

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('darkMode', isDarkMode.value);
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        if (localStorage.getItem('darkMode') === 'true' ||
           (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            isDarkMode.value = true;
        }
    }
});

watchEffect(() => {
    if (typeof window !== 'undefined') {
        if (isDarkMode.value) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
});


const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};
</script>

<template>
    <div>
        <Head :title="pageTitle" />
        <Banner />
        
        <!-- 4. Renderiza el componente del modal -->
        <PomodoroTimer />

        <div class="flex min-h-screen bg-gray-100 dark:bg-slate-950">
            <!-- Sidebar ahora solo se pasa la navegación -->
            <Sidebar :navigation="navigationMenu" />

            <!-- Navegación inferior para móviles -->
            <BottomNavBar :navigation="navigationMenu" />

            <div class="flex-1 flex flex-col w-full px-2">
                <!-- Barra de Navegación Superior -->
                <nav class="bg-white dark:bg-gray-800 shadow-md max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 rounded-2xl w-full">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-between items-center h-16">
                           <!-- Lado Izquierdo (o central) para el Timer -->
                            <div>
                               <transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95"
                                    enter-to-class="opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 scale-100"
                                    leave-to-class="opacity-0 scale-95"
                                >
                                    <div v-if="state.isRunning" class="flex items-center gap-x-2 px-3 py-1 rounded-full text-sm font-medium text-white"
                                        :class="state.isWorkSession ? 'bg-emerald-500/90' : 'bg-sky-500/90'">
                                        <svg v-if="state.isWorkSession" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
                                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l3 3m0 0l3-3m-3 3v-7.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span class="font-mono tracking-wider">{{ displayTime }}</span>
                                    </div>
                               </transition>
                            </div>
                            <!-- Lado Derecho para Dark Mode -->
                             <div class="flex items-center">
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-300 mr-3">Dark Mode</span>
                                <button @click="toggleDarkMode" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" :class="isDarkMode ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-700'">
                                    <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform" :class="{ 'translate-x-6': isDarkMode, 'translate-x-1': !isDarkMode }"/>
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Contenido de la Página -->
                <main class="mb-12 mt-2 lg:mb-0 lg:mx-auto h-[calc(100vh-9rem)] lg:h-[calc(100vh-7rem)] overflow-auto lg:w-[90%]">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
