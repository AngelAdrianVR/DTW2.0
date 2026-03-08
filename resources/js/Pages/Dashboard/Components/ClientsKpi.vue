<script setup>
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps({
    data: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};
</script>

<template>
    <Card class="shadow-sm rounded-2xl overflow-hidden h-full bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
        <template #title>
             <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-lg">
                    <i class="pi pi-users text-lg text-emerald-500"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-200 text-base font-semibold">Clientes</span>
            </div>
        </template>
        <template #subtitle>
            <div class="mt-2">
                <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ data.total }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Total registrados</p>
            </div>
        </template>
        <template #content>
            <div class="mt-6">
                <h4 class="font-bold text-xs text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wider">Con Deuda Pendiente</h4>
                <div v-if="data.with_debt.length > 0">
                     <ul class="space-y-2 max-h-40 overflow-y-auto custom-scrollbar pr-1">
                        <li v-for="client in data.with_debt" :key="client.name" class="flex justify-between items-center text-sm p-2 bg-gray-50 dark:bg-zinc-800/50 rounded-lg border border-gray-100 dark:border-zinc-700/50">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2">{{ client.name }}</span>
                            <Tag severity="danger" :value="formatCurrency(client.debt)" class="!text-xs"></Tag>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-center py-6 bg-gray-50 dark:bg-zinc-800/30 rounded-lg border border-dashed border-gray-200 dark:border-zinc-700">
                    <i class="pi pi-check-circle text-emerald-500 text-2xl mb-1"></i>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">¡Sin deudas pendientes!</p>
                </div>
            </div>
        </template>
    </Card>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #52525b; }
</style>