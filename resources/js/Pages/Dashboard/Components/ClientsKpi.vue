<script setup>
import { ref, computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: Object
});

const showModal = ref(false);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

// Filtramos solo los 2 clientes con mayor deuda
const topDebtors = computed(() => {
    return props.data?.with_debt?.slice(0, 2) || [];
});
</script>

<template>
    <Card class="shadow-sm rounded-3xl overflow-hidden h-full bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 flex flex-col">
        <template #title>
             <div class="flex items-center space-x-2 gap-1">
                <div class="p-2 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl">
                    <i class="pi pi-users text-xl text-emerald-500 mb-1 mx-1 mt-1"></i>
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
            <div class="mt-6 flex-1 flex flex-col h-full">
                <h4 class="font-bold text-xs text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wider">Con Deuda Pendiente</h4>
                
                <div class="flex-1 flex flex-col">
                    <ul v-if="topDebtors.length > 0" class="space-y-2">
                        <li v-for="client in topDebtors" :key="client.id" class="flex justify-between items-center text-sm p-2 bg-gray-50 dark:bg-zinc-800/50 rounded-lg border border-gray-100 dark:border-zinc-700/50 hover:border-gray-200 dark:hover:border-zinc-600 transition-colors">
                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate mr-2 flex-1 text-xs">{{ client.name }}</span>
                            <div class="flex items-center gap-2">
                                <Tag severity="danger" :value="formatCurrency(client.debt)" class="!text-xs"></Tag>
                                <Link :href="route('clients.show', client.id)" title="Ir al cliente" class="text-gray-400 hover:text-emerald-500 dark:hover:text-emerald-400 transition-colors">
                                    <i class="pi pi-arrow-up-right text-xs"></i>
                                </Link>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="text-center py-6 bg-gray-50 dark:bg-zinc-800/30 rounded-lg border border-dashed border-gray-200 dark:border-zinc-700">
                        <i class="pi pi-check-circle text-emerald-500 text-2xl mb-1"></i>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">¡Sin deudas pendientes!</p>
                    </div>

                    <!-- Botón para abrir el modal si hay más de 2 deudores -->
                    <button 
                        v-if="data?.with_debt?.length > 2" 
                        @click="showModal = true"
                        class="mt-2 pt-4 w-full py-2 bg-transparent hover:bg-gray-50 dark:hover:bg-zinc-800/50 rounded-xl text-xs font-bold text-emerald-600 dark:text-emerald-500 transition-colors flex items-center justify-center gap-1.5"
                    >
                        Ver todos ({{ data.with_debt.length }})
                        <i class="pi pi-external-link text-[10px]"></i>
                    </button>
                </div>
            </div>

            <!-- Modal para ver todos los clientes con deuda -->
            <Dialog 
                v-model:visible="showModal" 
                modal 
                header="Clientes con Deuda Pendiente" 
                :style="{ width: '90vw', maxWidth: '500px' }" 
                :breakpoints="{ '641px': '100vw' }"
            >
                <div class="p-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Lista completa de clientes con pagos pendientes ordenados de mayor a menor deuda.</p>
                    <ul v-if="data?.with_debt?.length > 0" class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2">
                        <li v-for="(client, index) in data.with_debt" :key="client.id" class="flex items-center text-sm p-3 bg-gray-50 dark:bg-zinc-800/40 rounded-xl border border-gray-100 dark:border-zinc-800">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold mr-3 flex-shrink-0">
                                {{ index + 1 }}
                            </div>
                            <span class="text-gray-700 dark:text-gray-200 truncate pr-2 font-medium flex-1">{{ client.name }}</span>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-red-600 dark:text-red-400 whitespace-nowrap">{{ formatCurrency(client.debt) }}</span>
                                <Link :href="route('clients.show', client.id)" class="p-1.5 bg-white dark:bg-zinc-700 rounded-lg shadow-sm text-gray-400 hover:text-emerald-500 border border-gray-100 dark:border-zinc-600 transition-colors" title="Ir al cliente">
                                    <i class="pi pi-arrow-up-right text-xs"></i>
                                </Link>
                            </div>
                        </li>
                    </ul>
                </div>
            </Dialog>
        </template>
    </Card>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #52525b; }
</style>