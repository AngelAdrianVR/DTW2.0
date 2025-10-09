<script setup>
import Card from 'primevue/card';

defineProps({
    data: Object
});
</script>

<template>
    <Card class="shadow-md rounded-lg overflow-hidden h-full flex flex-col justify-center bg-white dark:bg-gray-800">
        <template #content>
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-purple-100 dark:bg-purple-900/50 mb-4">
                    <i class="pi pi-briefcase text-3xl text-purple-600 dark:text-purple-300"></i>
                </div>
                <p class="text-4xl font-bold text-gray-900 dark:text-white">{{ data.total }}</p>
                <p class="text-lg text-gray-500 dark:text-gray-400 mt-1">Proyectos Activos</p>
            </div>
        </template>
    </Card>
</template>
