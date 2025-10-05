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
        <div class="flex flex-wrap justify-end mt-4">
            <Link
                v-for="(link, key) in links"
                :key="key"
                :href="link.url"
                class="px-4 py-2 mx-1 text-sm rounded-md"
                :class="{
                    'bg-blue-500 text-white': link.active,
                    'bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600': !link.active,
                    'opacity-50 cursor-not-allowed': !link.url
                }"
                v-html="link.label"
                :disabled="!link.url"
                preserve-scroll
            />
        </div>
    </div>
</template>
