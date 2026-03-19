<script setup>
import { computed } from 'vue';
import Card from 'primevue/card';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: Object
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

// Formatea la fecha para que aparezca por ejemplo: "11 oct"
const formatDate = (dateString) => {
    if (!dateString) return '';
    // Añadimos T00:00:00 para evitar que el ajuste de zona horaria cambie el día
    const date = new Date(dateString + 'T00:00:00');
    return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'short' }).format(date);
};

// Capitalizamos el mes actual para el título, ej: "Octubre"
const currentMonthName = computed(() => {
    if (!props.data?.current_month_name) return 'este mes';
    return props.data.current_month_name.charAt(0).toUpperCase() + props.data.current_month_name.slice(1);
});
</script>

<template>
    <Card class="shadow-sm rounded-2xl overflow-hidden h-full flex flex-col bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
        <template #title>
             <div class="flex items-center space-x-2">
                <div class="p-1.5 bg-cyan-50 dark:bg-cyan-900/30 rounded-lg">
                    <i class="pi pi-server text-lg text-cyan-500"></i>
                </div>
                <span class="text-gray-700 dark:text-gray-200 text-base font-semibold">Hostings</span>
            </div>
        </template>
        <template #subtitle>
            <div class="mt-2 flex justify-between items-end">
                <div>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight">{{ data.total }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide">Total registrados</p>
                </div>
            </div>
        </template>
        <template #content>
            <div class="mt-6 flex-1 flex flex-col h-full min-h-0">
                <h4 class="font-bold text-xs text-gray-400 dark:text-gray-500 mb-3 uppercase tracking-wider">Próximos pagos de {{ currentMonthName }}</h4>
                
                <div class="flex-1 flex flex-col overflow-hidden">
                    <ul v-if="data.upcoming && data.upcoming.length > 0" class="space-y-2 overflow-y-auto custom-scrollbar pr-1 max-h-40">
                        <li v-for="hosting in data.upcoming" :key="hosting.id" class="flex justify-between items-center text-sm p-2.5 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-100 dark:border-zinc-700/50 hover:border-gray-200 dark:hover:border-zinc-600 transition-colors">
                            <div class="flex flex-col overflow-hidden mr-2">
                                <span class="font-medium text-gray-700 dark:text-gray-300 truncate text-xs">{{ hosting.client_name }}</span>
                                <span class="text-[10px] text-cyan-600 dark:text-cyan-400 font-bold uppercase">{{ formatDate(hosting.date) }}</span>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="font-bold text-gray-900 dark:text-white text-xs">{{ formatCurrency(hosting.amount) }}</span>
                                <Link :href="route('hosting-clients.show', hosting.id)" title="Ir al hosting" class="text-gray-400 hover:text-cyan-500 dark:hover:text-cyan-400 transition-colors p-1.5 bg-white dark:bg-zinc-700 rounded-lg shadow-sm border border-gray-100 dark:border-zinc-600">
                                    <i class="pi pi-arrow-up-right text-[10px]"></i>
                                </Link>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="text-center py-6 bg-gray-50 dark:bg-zinc-800/30 rounded-lg border border-dashed border-gray-200 dark:border-zinc-700 h-full flex flex-col justify-center">
                        <i class="pi pi-check-circle text-cyan-500 text-2xl mb-1"></i>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">¡Sin pagos próximos este mes!</p>
                    </div>
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