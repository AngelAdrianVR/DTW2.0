<script setup>
import { computed } from 'vue';
import Accordion from 'primevue/accordion';
import AccordionTab from 'primevue/accordiontab';

const props = defineProps({
    data: Object,
});

// Función reutilizable para dar formato de moneda MXN.
const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value || 0);
};

// Calcula el gran total facturado y lo formatea.
const grandTotalFormatted = computed(() => formatCurrency(props.data?.total_invoiced));

</script>

<template>
    <!-- Contenedor principal del KPI -->
    <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-500 dark:text-gray-400 mb-2">Total Facturado</h3>
        
        <!-- Componente Acordeón de PrimeVue para el desglose -->
        <Accordion :activeIndex="null" class="[&>div]:rounded-lg">
            <AccordionTab>
                <!-- La cabecera del acordeón siempre está visible y muestra el total. -->
                <template #header>
                    <span class="flex items-center justify-between w-full">
                        <span class="font-bold text-3xl text-gray-900 dark:text-gray-100">{{ grandTotalFormatted }}</span>
                        <span class="text-sm text-gray-400 dark:text-gray-500 hidden sm:inline">Ver desglose</span>
                         <i class="pi pi-chevron-down text-gray-400 dark:text-gray-500 sm:hidden"></i>
                    </span>
                </template>
                
                <!-- El contenido del acordeón solo es visible al expandirlo. -->
                <div class="max-h-60 overflow-y-auto pr-2 -mr-2">
                     <ul v-if="data?.invoiced_per_client?.length > 0" class="space-y-3 mt-2">
                        <li v-for="client in data.invoiced_per_client" :key="client.name" class="flex justify-between items-center text-sm">
                            <span class="text-gray-700 dark:text-gray-300 truncate pr-2">{{ client.name }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">{{ formatCurrency(client.total) }}</span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        No hay datos de facturación para mostrar.
                    </p>
                </div>
            </AccordionTab>
        </Accordion>
        
        <p class="text-sm text-gray-400 dark:text-gray-500 mt-3">Suma de cotizaciones aceptadas.</p>
    </div>
</template>

<style>
/* Estilos para personalizar el acordeón de PrimeVue y que se vea más integrado. */
.p-accordion-header-link {
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
}
.p-accordion-content {
    padding: 0 !important;
    border: none !important;
}
</style>
