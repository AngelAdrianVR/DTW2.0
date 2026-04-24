<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';

const props = defineProps({
    data: Object,
});

const showModal = ref(false);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value || 0);
};

const grandTotalFormatted = computed(() => formatCurrency(props.data?.total_invoiced));
// Nuevas propiedades computadas para los formatos
const totalIvaFormatted = computed(() => formatCurrency(props.data?.total_iva));
const totalUtilityFormatted = computed(() => formatCurrency(props.data?.total_utility));

// Filtramos solo los 2 clientes principales para el KPI
const topClients = computed(() => {
    return props.data?.invoiced_per_client?.slice(0, 2) || [];
});
</script>

<template>
    <!-- KPI Facturado con estilo Zinc, ajustado para ser una columna vertical sólida -->
    <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 h-full flex flex-col transition-all hover:shadow-md relative overflow-hidden">
        <!-- Decoración Background -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>

        <div class="p-6 pb-2 flex-shrink-0">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                    <i class="pi pi-wallet text-emerald-600 dark:text-emerald-400 text-xl mb-1 mx-1 mt-1"></i>
                </div>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">Total Facturado</h3>
            </div>
            <p class="font-extrabold text-4xl text-gray-900 dark:text-white tracking-tight break-all">{{ grandTotalFormatted }}</p>
            
            <!-- NUEVO DESGLOSE: Utilidad e IVA -->
            <div class="flex items-center gap-4 mt-3 pt-3 border-t border-gray-100 dark:border-zinc-800">
                <div>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-0.5">Utilidad</p>
                    <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ totalUtilityFormatted }}</p>
                </div>
                <div class="w-px h-6 bg-gray-200 dark:bg-zinc-700"></div>
                <div>
                    <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-bold tracking-wider mb-0.5">IVA (16%)</p>
                    <p class="text-sm font-bold text-gray-600 dark:text-gray-300">{{ totalIvaFormatted }}</p>
                </div>
            </div>
        </div>
        
        <!-- Lista de desglose (Solo top 2) -->
        <div class="flex-1 px-6 pb-6 min-h-0 overflow-hidden flex flex-col">
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 flex-1 min-h-0 flex flex-col">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Principales Clientes</h4>
                
                <div class="overflow-y-auto custom-scrollbar flex-1 pr-1 flex flex-col">
                     <ul v-if="topClients.length > 0" class="space-y-2">
                        <li v-for="client in topClients" :key="client.name" class="flex justify-between items-center text-sm p-2.5 bg-gray-50 dark:bg-zinc-800/40 rounded-xl border border-transparent hover:border-gray-200 dark:hover:border-zinc-700 transition-colors">
                            <span class="text-gray-600 dark:text-gray-300 truncate pr-2 font-medium text-xs">{{ client.name }}</span>
                            <span class="font-bold text-gray-800 dark:text-emerald-400 whitespace-nowrap text-xs">{{ formatCurrency(client.total) }}</span>
                        </li>
                    </ul>
                    <div v-else class="flex-1 flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600 py-4">
                        <i class="pi pi-inbox text-2xl mb-1 opacity-50"></i>
                        <p class="text-xs">Sin registros</p>
                    </div>

                    <!-- Botón para abrir el modal si hay más de 2 clientes -->
                    <button 
                        v-if="data?.invoiced_per_client?.length > 2" 
                        @click="showModal = true"
                        class="mt-2 pt-3 w-full py-2 bg-transparent hover:bg-gray-50 dark:hover:bg-zinc-800/50 rounded-xl text-xs font-bold text-emerald-600 dark:text-emerald-500 transition-colors flex items-center justify-center gap-1.5"
                    >
                        Ver todos ({{ data.invoiced_per_client.length }})
                        <i class="pi pi-external-link text-[10px]"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para ver todos los clientes -->
        <Dialog 
            v-model:visible="showModal" 
            modal 
            header="Facturación por Cliente" 
            :style="{ width: '90vw', maxWidth: '500px' }" 
            :breakpoints="{ '641px': '100vw' }"
        >
            <div class="p-1">
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Desglose completo de clientes facturados de mayor a menor.</p>
                <ul v-if="data?.invoiced_per_client?.length > 0" class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2">
                    <li v-for="(client, index) in data.invoiced_per_client" :key="client.name" class="flex items-center text-sm p-3 bg-gray-50 dark:bg-zinc-800/40 rounded-xl border border-gray-100 dark:border-zinc-800">
                        <div class="flex items-center justify-center w-6 h-6 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold mr-3 flex-shrink-0">
                            {{ index + 1 }}
                        </div>
                        <span class="text-gray-700 dark:text-gray-200 truncate pr-2 font-medium flex-1">{{ client.name }}</span>
                        <span class="font-bold text-gray-900 dark:text-emerald-400 whitespace-nowrap">{{ formatCurrency(client.total) }}</span>
                    </li>
                </ul>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #3f3f46; }
</style>