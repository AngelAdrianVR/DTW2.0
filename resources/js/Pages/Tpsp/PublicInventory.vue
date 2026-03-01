<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Paginator from 'primevue/paginator';

// --- Props ---
const props = defineProps({
    products: {
        type: Array,
        default: () => []
    }
});

// --- Computed Properties for Product Filtering ---
const otherProducts = computed(() => {
    return props.products.filter(p => p.category !== 'Kit Terminado');
});

const finishedKits = computed(() => {
    return props.products.filter(p => p.category === 'Kit Terminado');
});

// --- Estado ---
const movements = ref([]);
const loading = ref(true);
const filterStartDate = ref(null);
const filterEndDate = ref(null);
const imageErrorState = ref({});

// --- Helpers de Formato ---
function formatDisplayDate(dateString) {
    if (!dateString) return '---';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    }).replace('.', ''); 
}

function formatDateForAPI(date) {
    if (!date) return null;
    let d = new Date(date);
    d = new Date(d.getTime() - (d.getTimezoneOffset() * 60000));
    return d.toISOString().split('T')[0];
}

const onImageError = (productId) => {
    if (productId) {
        imageErrorState.value[productId] = true;
    }
};

// --- Lógica de Datos ---
const fetchMovements = async () => {
    loading.value = true;
    const params = {};
    
    const apiStartDate = formatDateForAPI(filterStartDate.value);
    if (apiStartDate) {
        params.date_start = apiStartDate;
    }
    const apiEndDate = formatDateForAPI(filterEndDate.value);
    if (apiEndDate) {
        params.date_end = apiEndDate;
    }

    try {
        const response = await axios.get(route('tpsp.public.sales-history', params));
        movements.value = response.data.data; 
    } catch (error) {
        console.error("Error fetching sales movements:", error);
    } finally {
        loading.value = false;
    }
};

const groupedMovements = computed(() => {
    const groups = new Map();
    
    for (const movement of movements.value) {
        const dateKey = formatDisplayDate(movement.created_at);
        
        if (!groups.has(dateKey)) {
            groups.set(dateKey, {
                date: dateKey,
                originalDate: new Date(movement.created_at), 
                items: [] 
            });
        }
        
        groups.get(dateKey).items.push({
            product: movement.product,
            quantity: movement.quantity,
            id: movement.id 
        });
    }
    
    return Array.from(groups.values())
        .sort((a, b) => b.originalDate.getTime() - a.originalDate.getTime());
});

const clearFilters = () => {
    filterStartDate.value = null;
    filterEndDate.value = null;
    fetchMovements();
};

onMounted(() => {
    fetchMovements();
});
</script>

<template>
    <Head title="Inventario y entregas" />
    
    <div class="container mx-auto p-6 max-w-7xl min-h-screen bg-gray-50 dark:bg-black">

        <!-- TÍTULO PRINCIPAL -->
        <h1 class="text-3xl font-bold text-gray-900 dark:text-zinc-100 mb-8">Inventario y Entregas</h1>

        <!-- SECCIÓN DE PRODUCTOS (GENERALES) -->
        <div v-if="otherProducts.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">Productos en Existencia</h2>
            
            <div class="flex overflow-x-auto space-x-4 py-4 mb-8">
                <Card v-for="product in otherProducts" :key="product.id" class="overflow-hidden rounded-xl shadow-sm flex-shrink-0 w-52 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
                    <template #header>
                        <div class="w-full h-48 bg-gray-100 dark:bg-zinc-950">
                            <img 
                                v-if="product.media[0]?.original_url && !imageErrorState[product.id]"
                                draggable="false"
                                :src="product.media[0]?.original_url" 
                                @error="onImageError(product.id)"
                                :alt="product.name" 
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600">
                                <i class="pi pi-image" style="font-size: 2.5rem;"></i>
                                <span class="mt-2 text-sm font-medium">Sin imagen</span>
                            </div>
                        </div>
                    </template>
                    <template #title>
                        <span class="text-lg font-bold text-gray-900 dark:text-zinc-100 block truncate" :title="product.name">{{ product.name }}</span>
                    </template>
                    <template #content>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-gray-600 dark:text-zinc-400 text-sm">Existencia:</span>
                            <span class="text-lg font-bold text-gray-800 dark:text-zinc-200">{{ product.stock.toLocaleString() }}</span>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- ***** SECCIÓN DE KITS TERMINADOS ***** -->
        <div v-if="finishedKits.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">Existencias de kits terminados</h2>
            
            <div class="flex overflow-x-auto space-x-4 py-4 mb-8">
                <Card v-for="product in finishedKits" :key="product.id" class="overflow-hidden rounded-xl shadow-sm flex-shrink-0 w-52 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
                    <template #header>
                        <div class="w-full h-48 bg-gray-100 dark:bg-zinc-950">
                            <img 
                                v-if="product.media[0]?.original_url && !imageErrorState[product.id]"
                                draggable="false"
                                :src="product.media[0]?.original_url" 
                                @error="onImageError(product.id)"
                                :alt="product.name" 
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600">
                                <i class="pi pi-image" style="font-size: 2.5rem;"></i>
                                <span class="mt-2 text-sm font-medium">Sin imagen</span>
                            </div>
                        </div>
                    </template>
                    <template #title>
                        <span class="text-lg font-bold text-gray-900 dark:text-zinc-100 block truncate" :title="product.name">{{ product.name }}</span>
                    </template>
                    <template #content>
                        <!-- Existencia -->
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-gray-600 dark:text-zinc-400 text-sm">Existencia:</span>
                            <span class="text-lg font-bold text-gray-800 dark:text-zinc-200">{{ product.stock.toLocaleString() }}</span>
                        </div>

                        <!-- Pedidos en Curso -->
                        <div v-if="product.production_orders && product.production_orders.length > 0" 
                             class="border-t border-gray-100 dark:border-zinc-800 pt-3 mt-3 space-y-3">
                            
                            <div v-for="order in product.production_orders" :key="order.id">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wide">
                                        <i class="pi pi-spin pi-cog mr-1" style="font-size: 0.8rem; vertical-align: middle;"></i>
                                        En curso
                                    </span>
                                </div>
                                
                                <div class="text-xs text-gray-500 dark:text-zinc-500 my-1">
                                    {{ order.quantity_produced.toLocaleString() }} / {{ order.quantity_requested.toLocaleString() }}
                                </div>
                                
                                <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-blue-500 h-1.5 rounded-full" 
                                         :style="{ width: (order.quantity_requested > 0 ? (order.quantity_produced / order.quantity_requested * 100) : 0) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>


        <!-- SECCIÓN DE MOVIMIENTOS -->
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">Historial de Entregas</h2>

        <!-- Filtros -->
        <div class="flex flex-wrap gap-4 mb-6 items-end">
            <div class="flex flex-col gap-2">
                <label for="filterStart" class="font-bold text-sm text-gray-700 dark:text-zinc-300">Fecha Inicio</label>
                <Calendar id="filterStart" v-model="filterStartDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Desde" inputClass="dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-100" />
            </div>
            <div class="flex flex-col gap-2">
                <label for="filterEnd" class="font-bold text-sm text-gray-700 dark:text-zinc-300">Fecha Fin</label>
                <Calendar id="filterEnd" v-model="filterEndDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Hasta" inputClass="dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-100" />
            </div>
            <div class="flex items-end gap-2">
                <Button label="Filtrar" icon="pi pi-filter" @click="fetchMovements" />
                <Button label="Limpiar" icon="pi pi-filter-slash" @click="clearFilters" class="p-button-outlined" />
            </div>
        </div>

        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900">
            <DataTable :value="groupedMovements" :loading="loading" 
                responsiveLayout="scroll" :rows="10" :paginator="true"
                dataKey="date"
                class="zinc-table">
                
                <!-- Columna de Fecha (Agrupada) -->
                <Column header="Fecha de entrega" :sortable="true" sortField="originalDate" style="width: 12rem">
                    <template #body="slotProps">
                        <span class="font-bold text-base text-gray-800 dark:text-zinc-200">{{ slotProps.data.date }}</span>
                    </template>
                </Column>

                <!-- Columna de Productos (Agrupados) -->
                <Column header="Productos Entregados">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-4 py-2">
                            <div v-for="item in slotProps.data.items" :key="item.id" 
                                 class="flex items-center gap-3 flex-wrap">
                                
                                <!-- Imagen del Producto -->
                                <div class="size-10 rounded-lg flex-shrink-0 bg-gray-100 dark:bg-zinc-950 overflow-hidden">
                                    <img v-if="item.product?.media[0]?.original_url && !imageErrorState[item.product?.id]"
                                        draggable="false"
                                        :src="item.product?.media[0]?.original_url"
                                        @error="onImageError(item.product?.id)"
                                        :alt="item.product?.name"
                                        class="w-full h-full object-cover"
                                    />
                                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400 dark:text-zinc-600">
                                        <i class="pi pi-image" style="font-size: 1rem;"></i>
                                    </div>
                                </div>
                                
                                <!-- Nombre del Producto -->
                                <span class="font-medium text-gray-700 dark:text-zinc-300 flex-1 min-w-[150px]">{{ item.product?.name || 'Producto no encontrado' }}</span>
                                
                                <!-- Cantidad Entregada -->
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 ml-auto pl-4">
                                    {{ Math.abs(item.quantity).toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
/* Zinc Theme Overrides for PrimeVue DataTable */
:deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
}
.dark :deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #18181b !important; /* zinc-950 */
    color: #a1a1aa !important; /* zinc-400 */
    border-bottom: 1px solid #27272a; /* zinc-800 */
}
:deep(.zinc-table .p-datatable-tbody > tr) {
    background-color: transparent !important;
    color: inherit;
}
:deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #f4f4f5;
}
.dark :deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #27272a;
}
</style>