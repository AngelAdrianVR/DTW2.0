<script setup>
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';

defineProps({
    data: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};
</script>

<template>
    <Card class="shadow-md rounded-lg overflow-hidden h-full bg-white dark:bg-gray-800">
        <template #title>
             <div class="flex items-center space-x-2">
                <i class="pi pi-users text-xl text-green-500"></i>
                <span class="text-gray-700 dark:text-gray-300">Clientes</span>
            </div>
        </template>
        <template #subtitle>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data.total }}</p>
            <p class="text-gray-500 dark:text-gray-400">Total registrados</p>
        </template>
        <template #content>
            <div class="mt-4">
                <h4 class="font-semibold text-sm text-gray-600 dark:text-gray-300 mb-2">Clientes con Deuda</h4>
                <div v-if="data.with_debt.length > 0">
                     <ul class="space-y-2 max-h-48 overflow-y-auto">
                        <li v-for="client in data.with_debt" :key="client.name" class="flex justify-between items-center text-sm p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ client.name }}</span>
                            <Tag severity="danger" :value="formatCurrency(client.debt)" rounded></Tag>
                        </li>
                    </ul>
                </div>
                <div v-else class="text-center py-4">
                    <i class="pi pi-check-circle text-green-500 text-3xl"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">¡Sin deudas pendientes!</p>
                </div>
            </div>
        </template>
    </Card>
</template>
