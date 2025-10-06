<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    }
});
</script>

<template>
    <div v-if="links.length > 3">
        <div class="flex flex-wrap justify-end mt-4 -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <!-- Mostramos un span no clickeable si la URL es nula -->
                <div
                    v-if="link.url === null"
                    class="px-4 py-3 mb-1 mr-1 text-sm leading-4 text-gray-400 border rounded dark:text-gray-500 dark:border-gray-600"
                    v-html="link.label"
                />
                <!-- Mostramos el componente Link de Inertia si la URL existe -->
                <Link
                    v-else
                    class="px-4 py-3 mb-1 mr-1 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:border-indigo-400 dark:focus:text-indigo-400"
                    :class="{ 'bg-blue-600 text-white dark:bg-blue-700': link.active }"
                    :href="link.url"
                    v-html="link.label"
                    preserve-scroll
                />
            </template>
        </div>
    </div>
</template>
