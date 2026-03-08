<script setup>
import Card from 'primevue/card';

defineProps({
    data: Object
});
</script>

<template>
    <Card class="shadow-sm rounded-2xl overflow-hidden h-full flex flex-col justify-center bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
        <template #content>
            <div class="text-center py-4">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-2xl bg-cyan-50 dark:bg-cyan-900/20 mb-4 border border-cyan-100 dark:border-cyan-800/30">
                     <i class="pi pi-server text-3xl text-cyan-600 dark:text-cyan-400"></i>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ data.total }}</p>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wide">Hostings Hospedados</p>
            </div>
        </template>
    </Card>
</template>