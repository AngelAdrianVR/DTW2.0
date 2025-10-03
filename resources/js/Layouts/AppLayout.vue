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
    // {
    //     name: 'Notifications',
    //     route: '#',
    //     icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>`,
    //     active: route().current('notifications'),
    //     show: true,
    // },
    {
        name: 'Profile',
        route: 'profile.show',
        icon: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" /></svg>`,
        active: route().current('profile.show'),
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
                <main class="mb-12 lg:mb-0">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
