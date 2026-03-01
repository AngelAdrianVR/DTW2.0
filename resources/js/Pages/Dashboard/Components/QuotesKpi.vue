<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Chart from 'primevue/chart';
import Dialog from 'primevue/dialog';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import axios from 'axios';

const props = defineProps({
    data: {
        type: Object,
        required: true,
        default: () => ({ total: 0, accepted: 0, rejected: 0, pending: 0, sent: 0, paid: 0 })
    },
});

const toast = useToast();
const chartData = ref();
const chartOptions = ref();

// --- Estados para el Modal ---
const detailsDialogVisible = ref(false);
const selectedStatusTitle = ref('');
const loadingDetails = ref(false);
const quotesList = ref([]);

// --- Mapeo de Estados (Key -> DB Value) ---
const statusLabelMap = {
    'paid': 'Pagado',
    'accepted': 'Aceptado',
    'pending': 'Pendiente',
    'sent': 'Enviado',
    'rejected': 'Rechazado'
};

// --- Utilidad para obtener color del borde según el tema ---
const getBorderColor = () => {
    // Si estamos en el navegador y tiene la clase 'dark', usamos Zinc-900 (#18181b), si no, blanco
    if (typeof document !== 'undefined' && document.documentElement.classList.contains('dark')) {
        return '#18181b'; 
    }
    return '#ffffff';
};

// --- Configuración del Gráfico ---
const updateChart = () => {
    const borderColor = getBorderColor();

    chartData.value = {
        labels: ['Pagadas', 'Aceptadas', 'Pendientes', 'Enviadas', 'Rechazadas'],
        datasets: [
            {
                data: [
                    props.data.paid || 0,
                    props.data.accepted || 0,
                    props.data.pending || 0,
                    props.data.sent || 0,
                    props.data.rejected || 0
                ],
                backgroundColor: ['#10B981', '#3B82F6', '#F59E0B', '#A8A29E', '#EF4444'],
                hoverBackgroundColor: ['#34D399', '#60A5FA', '#FBBF24', '#D6D3D1', '#F87171'],
                borderWidth: 4,
                borderColor: borderColor, // <--- Aquí aplicamos el color dinámico
                hoverOffset: 4
            }
        ]
    };

    chartOptions.value = {
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return ` ${context.label}: ${context.raw}`;
                    }
                }
            }
        },
        cutout: '75%',
        responsive: true,
        maintainAspectRatio: false
    };
};

watch(() => props.data, updateChart, { immediate: true });

// --- Observer para detectar cambio de tema en vivo ---
let themeObserver = null;

onMounted(() => {
    updateChart(); // Asegurar render inicial correcto
    
    // Crear un observador para ver si cambia la clase 'dark' en el <html>
    if (typeof document !== 'undefined') {
        themeObserver = new MutationObserver(() => {
            updateChart(); // Recalcular colores si cambia el tema
        });
        
        themeObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });
    }
});

onUnmounted(() => {
    if (themeObserver) {
        themeObserver.disconnect();
    }
});

// --- Lógica del Modal y Carga de Datos ---
const openDetailsModal = async (statusKey, title) => {
    selectedStatusTitle.value = title;
    detailsDialogVisible.value = true;
    loadingDetails.value = true;
    quotesList.value = [];

    const statusFilter = statusLabelMap[statusKey];

    try {
        const response = await axios.get(route('dashboard.quotes.data'), {
            params: { status: statusFilter }
        });
        quotesList.value = response.data;
    } catch (error) {
        console.error("Error cargando cotizaciones:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar la información.', life: 3000 });
    } finally {
        loadingDetails.value = false;
    }
};

const goToQuote = (id) => {
    router.get(route('quotes.show', id));
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value || 0);
};

// Configuración de las tarjetas de estado
const statuses = computed(() => [
    { key: 'paid', label: 'Pagadas', color: 'bg-emerald-500', text: 'text-emerald-600 dark:text-emerald-400', bg: 'bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/40', icon: 'pi pi-verified' },
    { key: 'accepted', label: 'Aceptadas', color: 'bg-blue-500', text: 'text-blue-600 dark:text-blue-400', bg: 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40', icon: 'pi pi-check-circle' },
    { key: 'pending', label: 'Pendientes', color: 'bg-amber-500', text: 'text-amber-600 dark:text-amber-400', bg: 'bg-amber-50 dark:bg-amber-900/20 hover:bg-amber-100 dark:hover:bg-amber-900/40', icon: 'pi pi-clock' },
    { key: 'sent', label: 'Enviadas', color: 'bg-stone-400', text: 'text-stone-600 dark:text-stone-400', bg: 'bg-stone-100 dark:bg-zinc-800 hover:bg-stone-200 dark:hover:bg-zinc-700', icon: 'pi pi-send' },
    { key: 'rejected', label: 'Rechazadas', color: 'bg-red-500', text: 'text-red-600 dark:text-red-400', bg: 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40', icon: 'pi pi-times-circle' },
]);
</script>

<template>
    <!-- Card Principal -->
    <Card class="shadow-sm rounded-[2rem] overflow-hidden h-full bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 flex flex-col transition-all duration-300 hover:shadow-md">
        <template #title>
            <div class="flex items-center justify-between mb-2 px-1">
                <div class="flex items-center space-x-4">
                    <div class="p-2.5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl">
                        <i class="pi pi-file text-xl text-indigo-500 dark:text-indigo-400 mx-2"></i>
                    </div>
                    <div>
                        <span class="block text-gray-800 dark:text-zinc-100 text-lg font-bold leading-tight">Cotizaciones</span>
                        <span class="text-xs text-gray-500 dark:text-zinc-500 font-normal">Resumen mensual</span>
                    </div>
                </div>
                <div class="flex flex-col items-end">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ data.total || 0 }}</span>
                    <span class="text-[10px] uppercase tracking-wider text-gray-400 font-bold">Total</span>
                </div>
            </div>
        </template>
        
        <template #content>
            <div class="flex flex-col sm:flex-row gap-6 h-full items-center">
                <!-- Gráfico Donut -->
                <div class="flex justify-center relative h-32 w-32 sm:w-40 sm:h-40 shrink-0">
                    <Chart type="doughnut" :data="chartData" :options="chartOptions" class="h-full w-full" />
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                        <i class="pi pi-chart-pie text-gray-300 dark:text-zinc-700 text-2xl"></i>
                    </div>
                </div>

                <!-- Lista Interactiva -->
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-2 w-full">
                    <button 
                        v-for="status in statuses" 
                        :key="status.key"
                        @click="openDetailsModal(status.key, status.label)"
                        class="relative flex flex-col p-2.5 rounded-xl transition-all duration-200 border border-transparent hover:scale-[1.02] active:scale-[0.98] group text-left min-h-[80px]"
                        :class="[status.bg]"
                    >
                        <div class="flex justify-between items-start mb-1">
                            <i :class="[status.icon, status.text]" class="text-base opacity-80"></i>
                            <span class="text-lg font-bold text-gray-800 dark:text-zinc-100">{{ data[status.key] || 0 }}</span>
                        </div>
                        <span class="text-[10px] font-semibold uppercase tracking-wide opacity-70 truncate" :class="status.text">{{ status.label }}</span>
                    </button>
                </div>
            </div>
        </template>
    </Card>

    <!-- Modal de Detalles Unificado y Limpio -->
    <Dialog 
        v-model:visible="detailsDialogVisible" 
        modal 
        dismissableMask
        :style="{ width: '75vw' }" 
        :breakpoints="{ '960px': '85vw', '641px': '95vw' }"
        :pt="{ 
            root: { class: 'bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl border-none overflow-hidden' },
            header: { class: 'bg-white dark:bg-zinc-900 dark:text-zinc-100 px-8 py-6 pb-2 border-none' }, 
            content: { class: 'bg-white dark:bg-zinc-900 px-8 py-2 border-none' },
            footer: { class: 'bg-white dark:bg-zinc-900 px-8 py-6 pt-2 border-none' },
            closeButton: { class: 'text-gray-400 dark:text-zinc-500 hover:text-gray-600 dark:hover:text-zinc-300 transition-colors focus:ring-0 hover:bg-transparent' },
            mask: { class: 'backdrop-blur-sm bg-gray-900/20' } 
        }"
    >
        <template #header>
            <div class="flex flex-col gap-1">
                <h3 class="font-bold text-2xl text-gray-800 dark:text-white tracking-tight">Cotizaciones {{ selectedStatusTitle }}</h3>
                <p class="text-sm text-gray-500 dark:text-zinc-400 font-medium">Listado detallado de registros en sistema.</p>
            </div>
        </template>

        <div v-if="loadingDetails" class="flex flex-col justify-center items-center py-20 space-y-4">
            <i class="pi pi-spin pi-spinner text-6xl text-indigo-200 opacity-50"></i>
        </div>

        <div v-else class="min-h-[300px]">
            <DataTable 
                :value="quotesList" 
                paginator 
                :rows="6" 
                class="zinc-table-clean" 
                responsiveLayout="scroll"
                :rowHover="false"
            >
                <template #empty>
                    <div class="flex flex-col items-center justify-center py-12 text-gray-400 dark:text-zinc-600">
                        <i class="pi pi-inbox text-4xl mb-3 opacity-30"></i>
                        <p>No hay cotizaciones para mostrar.</p>
                    </div>
                </template>
                
                <!-- Folio Real (Cot-{id}) -->
                <Column field="id" header="Folio" sortable style="width: 15%">
                    <template #body="slotProps">
                        <span class="font-bold text-gray-700 dark:text-zinc-300 text-base">
                            Cot-{{ slotProps.data.id }}
                        </span>
                    </template>
                </Column>
                
                <!-- Cliente -->
                <Column field="client.name" header="Cliente" sortable>
                    <template #body="slotProps">
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-800 dark:text-zinc-200 text-sm">
                                {{ slotProps.data.client?.name || 'Sin Cliente' }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-zinc-500 font-medium mt-0.5">
                                {{ slotProps.data.client?.tax_id }}
                            </span>
                        </div>
                    </template>
                </Column>
                
                <!-- Título (Oculto en móviles muy pequeños) -->
                <Column field="title" header="Proyecto" class="hidden md:table-cell">
                    <template #body="slotProps">
                        <span class="text-gray-500 dark:text-zinc-400 text-sm truncate block max-w-[200px]">
                            {{ slotProps.data.title }}
                        </span>
                    </template>
                </Column>
                
                <!-- Monto Alineado a la Derecha -->
                <Column field="final_amount" header="Monto" sortable class="text-right">
                    <template #header>
                        <div class="w-full text-right"></div>
                    </template>
                    <template #body="slotProps">
                        <div class="text-right w-full">
                            <span class="font-bold text-gray-900 dark:text-white text-base">
                                {{ formatCurrency(slotProps.data.final_amount) }}
                            </span>
                            <div v-if="slotProps.data.percentage_discount > 0" class="text-[10px] text-emerald-500 font-bold mt-0.5">
                                -{{ slotProps.data.percentage_discount }}% desc.
                            </div>
                        </div>
                    </template>
                </Column>

                <!-- Botón de Acción -->
                <Column bodyClass="text-center" style="width: 80px">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-arrow-right" 
                            text 
                            rounded 
                            @click="goToQuote(slotProps.data.id)"
                            class="!text-gray-400 hover:!text-indigo-600 dark:!text-zinc-500 dark:hover:!text-indigo-400 transition-colors w-10 h-10 hover:!bg-transparent"
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <template #footer>
            <div class="flex justify-end pt-2">
                <Button label="Cerrar" text @click="detailsDialogVisible = false" class="!text-gray-500 hover:!text-gray-800 dark:!text-zinc-400 dark:hover:!text-white hover:!bg-transparent font-medium" />
            </div>
        </template>
    </Dialog>
</template>

<style scoped>
/* ESTILOS DE TABLA MINIMALISTA Y LIMPIA */

/* Header transparente, texto pequeño y mayúsculas */
:deep(.zinc-table-clean .p-datatable-thead > tr > th) {
    background-color: transparent !important;
    color: #a1a1aa !important; /* zinc-400 */
    text-transform: uppercase;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #f4f4f5; /* zinc-100 */
    padding: 1rem 0.5rem;
}

.dark :deep(.zinc-table-clean .p-datatable-thead > tr > th) {
    color: #71717a !important; /* zinc-500 */
    border-bottom: 1px solid #27272a;
}

/* Filas sin background explícito */
:deep(.zinc-table-clean .p-datatable-tbody > tr) {
    background-color: transparent !important;
    transition: none !important; /* Quitar transición para evitar flashes */
}

/* ANULAR HOVER COMPLETAMENTE */
:deep(.zinc-table-clean .p-datatable-tbody > tr:hover) {
    background-color: transparent !important;
    color: inherit !important;
}

/* Celdas con espaciado cómodo */
:deep(.zinc-table-clean .p-datatable-tbody > tr > td) {
    padding: 1.25rem 0.5rem;
    border-bottom: 1px solid #f4f4f5;
    vertical-align: middle;
}

.dark :deep(.zinc-table-clean .p-datatable-tbody > tr > td) {
    border-color: #27272a; /* zinc-800 subtle border */
}

/* Paginador minimalista integrado */
:deep(.p-paginator) {
    background: transparent !important;
    border: none !important;
    padding-top: 1.5rem;
    justify-content: flex-end;
}
.dark :deep(.p-paginator .p-paginator-page) { color: #52525b; }
.dark :deep(.p-paginator .p-paginator-page.p-highlight) { 
    color: #18181b; /* zinc-950 */
    background-color: #f4f4f5; /* zinc-100 */
    font-weight: bold;
}
</style>