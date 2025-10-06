<script setup>
import { ref, computed, onMounted, watchEffect } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import Sidebar from '@/Components/MyComponents/SideBar.vue';
import BottomNavBar from '@/Components/MyComponents/BottomNavBar.vue';

defineProps({
    title: String,
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
    // Nuevo grupo CRM
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
        <Head :title="title" />
        <Banner />

        <div class="flex min-h-screen bg-gray-100 dark:bg-slate-950">
            <!-- Sidebar ahora solo se pasa la navegación -->
            <Sidebar :navigation="navigationMenu" />

            <!-- Navegación inferior para móviles -->
            <BottomNavBar :navigation="navigationMenu" />

            <div class="flex-1 flex flex-col w-full px-2">
                <!-- Barra de Navegación Superior -->
                <nav class="bg-white dark:bg-gray-800 shadow-md max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-3 rounded-2xl w-full">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex justify-end items-center h-16">
                            <!-- Switch de Dark Mode movido aquí -->
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
