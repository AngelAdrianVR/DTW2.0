<script setup>
import { computed } from 'vue';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';

const props = defineProps({
    data: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value || 0);
};

const grandTotalFormatted = computed(() => formatCurrency(props.data?.total_invoiced));
</script>

<template>
    <!-- KPI Facturado con estilo Zinc, ajustado para ser una columna vertical sólida -->
    <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 h-full flex flex-col transition-all hover:shadow-md relative overflow-hidden">
        <!-- Decoración Background -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/5 rounded-full blur-2xl -mr-10 -mt-10 pointer-events-none"></div>

        <div class="p-6 pb-2 flex-shrink-0">
            <div class="flex items-center gap-2 mb-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-xl">
                    <i class="pi pi-wallet text-emerald-600 dark:text-emerald-400 text-lg"></i>
                </div>
                <h3 class="text-base font-bold text-gray-700 dark:text-gray-300">Total Facturado</h3>
            </div>
            <p class="font-extrabold text-4xl text-gray-900 dark:text-white tracking-tight break-all">{{ grandTotalFormatted }}</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2 font-medium">Cotizaciones pagadas este ciclo</p>
        </div>
        
        <!-- Lista de desglose (Ahora siempre visible en desktop o con scroll más elegante) -->
        <div class="flex-1 px-6 pb-6 min-h-0 overflow-hidden flex flex-col">
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800 flex-1 min-h-0 flex flex-col">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Principales Clientes</h4>
                
                <div class="overflow-y-auto custom-scrollbar flex-1 pr-1 space-y-2">
                     <ul v-if="data?.invoiced_per_client?.length > 0" class="space-y-2">
                        <li v-for="client in data.invoiced_per_client" :key="client.name" class="flex justify-between items-center text-sm p-2.5 bg-gray-50 dark:bg-zinc-800/40 rounded-xl border border-transparent hover:border-gray-200 dark:hover:border-zinc-700 transition-colors">
                            <span class="text-gray-600 dark:text-gray-300 truncate pr-2 font-medium text-xs">{{ client.name }}</span>
                            <span class="font-bold text-gray-800 dark:text-emerald-400 whitespace-nowrap text-xs">{{ formatCurrency(client.total) }}</span>
                        </li>
                    </ul>
                    <div v-else class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600">
                        <i class="pi pi-inbox text-2xl mb-1 opacity-50"></i>
                        <p class="text-xs">Sin registros</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 3px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #3f3f46; }
</style>