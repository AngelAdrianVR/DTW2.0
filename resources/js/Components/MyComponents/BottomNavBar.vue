<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps({
    navigation: Array,
});

const isMenuOpen = ref(false);

const mainActions = [
    { name: 'Task', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>' },
    { name: 'Note', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l5.447 2.724A1 1 0 0021 16.382V5.618a1 1 0 00-1.447-.894L15 7m-6 10h6" /></svg>' },
    { name: 'Tag', icon: '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 8v-5z" /></svg>' },
]

</script>

<template>
    <div class="lg:hidden">
        <!-- Menú flotante de acciones rápidas -->
        <div 
            v-if="isMenuOpen" 
            class="fixed bottom-20 left-1/2 -translate-x-1/2 flex items-center gap-4 transition-transform duration-300 ease-in-out"
        >
            <button v-for="action in mainActions" :key="action.name" class="bg-white dark:bg-gray-700 text-gray-900 dark:text-white p-4 rounded-full shadow-lg hover:bg-blue-500 hover:text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div v-html="action.icon"></div>
            </button>
        </div>

        <!-- Barra de navegación inferior principal -->
        <div class="fixed bottom-0 left-0 z-40 w-full h-16 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
            <div class="grid h-full grid-cols-5 max-w-lg mx-auto">
                <!-- Items de Navegación (2 a la izquierda) -->
                <Link v-for="item in navigation.slice(0, 2)" :key="item.name" :href="item.route ? route(item.route) : '#'" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                    <div class="w-6 h-6 mb-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500" :class="{'text-blue-600 dark:text-blue-500': item.active}" v-html="item.icon"></div>
                </Link>

                <!-- Botón de acción central -->
                <div class="flex items-center justify-center">
                    <button @click="isMenuOpen = !isMenuOpen" type="button" class="inline-flex items-center justify-center w-14 h-14 font-medium bg-blue-600 rounded-full hover:bg-blue-700 group focus:ring-4 focus:ring-blue-300 focus:outline-none dark:focus:ring-blue-800 shadow-lg">
                        <svg v-if="!isMenuOpen" class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <svg v-else class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        <span class="sr-only">New item</span>
                    </button>
                </div>

                <!-- Items de Navegación (2 a la derecha) -->
                <Link v-for="item in navigation.slice(2, 4)" :key="item.name" :href="item.route ? route(item.route) : '#'" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 dark:hover:bg-gray-800 group">
                     <div class="w-6 h-6 mb-1 text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-500" :class="{'text-blue-600 dark:text-blue-500': item.active}" v-html="item.icon"></div>
                </Link>
            </div>
        </div>
    </div>
</template>
