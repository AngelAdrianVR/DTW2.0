<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    navigation: Array, // La lista de módulos de navegación
});

// Comprueba el estado guardado en localStorage al iniciar, o lo establece como 'true' por defecto.
const isExpanded = ref(JSON.parse(localStorage.getItem('sidebarExpanded')) ?? true);

const logout = () => {
    router.post(route('logout'));
};

// Función para alternar el estado y guardarlo en localStorage.
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
    localStorage.setItem('sidebarExpanded', isExpanded.value);
}

// Asegura que el estado inicial se guarde en localStorage si no existe.
onMounted(() => {
    if (localStorage.getItem('sidebarExpanded') === null) {
        localStorage.setItem('sidebarExpanded', isExpanded.value);
    }
});

</script>

<template>
    <!-- Contenedor principal de la barra lateral. Oculto en pantallas pequeñas (lg) y visible en grandes. -->
    <aside
        class="hidden lg:flex flex-col h-[calc(100vh-2rem)] m-4 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 transition-all duration-300 ease-in-out rounded-2xl shadow-2xl"
        :class="isExpanded ? 'w-64' : 'w-20'"
    >
        <!-- Sección del Logo y Botón para colapsar/expandir -->
        <div class="flex items-center h-16 px-4 border-b border-gray-200 dark:border-gray-800" :class="isExpanded ? 'justify-between' : 'justify-center'">
            <div class="flex items-center" v-if="isExpanded">
                <Link :href="route('dashboard')">
                    <ApplicationMark class="block h-9 w-auto" />
                </Link>
            </div>
            
            <button @click="toggleExpand" class="p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-800 dark:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Menú de Navegación -->
        <nav class="flex-1 py-4 space-y-2">
            <template v-for="item in navigation" :key="item.name">
                <div v-if="item.show" class="px-4">
                    <Link
                        :href="item.route ? route(item.route) : '#'"
                        class="flex items-center p-2 rounded-lg transition-colors duration-200"
                        :class="{
                            'bg-blue-500/10 border-l-4 border-blue-400 text-blue-600 dark:text-white shadow-lg': item.active,
                            'hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white': !item.active,
                        }"
                    >
                        <div v-html="item.icon" class="h-6 w-6"></div>
                        <span v-if="isExpanded" class="ml-4 font-medium">{{ item.name }}</span>
                    </Link>
                </div>
            </template>
        </nav>

        <!-- Menú Desplegable del Perfil de Usuario -->
        <div class="p-4 border-t border-gray-200 dark:border-gray-800">
             <Dropdown align="top" width="48">
                <template #trigger>
                    <button class="flex items-center w-full text-left p-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800">
                        <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                        <div v-if="isExpanded" class="ml-3">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $page.props.auth.user.name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $page.props.auth.user.email }}</p>
                        </div>
                    </button>
                </template>
                <template #content>
                    <div class="block px-4 py-2 text-xs text-gray-400">Manage Account</div>
                    <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                    <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">API Tokens</DropdownLink>
                    <div class="border-t border-gray-200 dark:border-gray-600" />
                    <form @submit.prevent="logout">
                        <DropdownLink as="button">Log Out</DropdownLink>
                    </form>
                </template>
            </Dropdown>
        </div>
    </aside>
</template>
