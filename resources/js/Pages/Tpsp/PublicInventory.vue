<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import axios from 'axios';

// --- Props ---
// Se asume que el backend (ver instrucciones) enviará la variable 'products' 
// inyectando 'required_quantity' y 'requiring_orders' dinámicamente.
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

// --- Estado para Historial de Órdenes de Producción ---
const productionOrders = ref([]);
const loadingOrders = ref(true);
const expandedRows = ref({});
const orderDeliveries = ref({});
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

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Pendiente': return 'warning';
        case 'En Progreso': return 'info';
        case 'Completado': return 'success';
        case 'Cancelado': return 'danger';
        default: return 'secondary';
    }
};

const onImageError = (productId) => {
    if (productId) {
        imageErrorState.value[productId] = true;
    }
};

// --- Lógica de Órdenes (Historial Expansible) ---
const fetchProductionOrders = async () => {
    loadingOrders.value = true;
    try {
        // Asumiendo que esta ruta es accesible para ver el historial general de las órdenes
        const response = await axios.get('/tpsp/production-orders');
        // Filtramos si no queremos ver los cancelados
        productionOrders.value = response.data.filter(o => o.status !== 'Cancelado'); 
    } catch (error) {
        console.error("Error fetching production orders:", error);
    } finally {
        loadingOrders.value = false;
    }
};

const onRowExpand = async (event) => {
    const orderId = event.data.id;
    // Evitar consultar repetidas veces si ya se tiene la info
    if (!orderDeliveries.value[orderId]) {
        try {
            const response = await axios.get(`/tpsp/production-orders/${orderId}/deliveries`);
            orderDeliveries.value[orderId] = response.data;
        } catch (error) {
            console.error("Error fetching order deliveries:", error);
            orderDeliveries.value[orderId] = [];
        }
    }
};

onMounted(() => {
    fetchProductionOrders();
});
</script>

<template>
    <Head title="Inventario y Entregas" />
    
    <div class="container mx-auto p-4 sm:p-6 max-w-7xl min-h-screen bg-gray-50 dark:bg-black">

        <!-- TÍTULO PRINCIPAL -->
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-zinc-100 mb-6 sm:mb-8">Inventario Público</h1>

        <!-- SECCIÓN DE PRODUCTOS GENERALES (Grid hacia abajo) -->
        <div v-if="otherProducts.length > 0" class="mb-10">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">
                Insumos, Materiales y Empaques
            </h2>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 lg:gap-4">
                <div v-for="product in otherProducts" :key="product.id" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden">
                    <!-- Imagen cuadrada compacta -->
                    <div class="w-full aspect-square bg-gray-100 dark:bg-zinc-950 relative">
                        <img 
                            v-if="product.media && product.media[0]?.original_url && !imageErrorState[product.id]"
                            draggable="false"
                            :src="product.media[0]?.original_url" 
                            @error="onImageError(product.id)"
                            :alt="product.name" 
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600">
                            <i class="pi pi-image" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    
                    <!-- Información y Lógica de Faltantes/Requeridos -->
                    <div class="p-3 flex flex-col flex-1">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-zinc-100 leading-tight mb-2 line-clamp-2" :title="product.name">{{ product.name }}</h3>
                        
                        <div class="flex justify-between items-center mb-2 mt-auto">
                            <span class="text-xs text-gray-500 dark:text-zinc-400 font-medium">Existencia:</span>
                            <span class="font-bold text-gray-800 dark:text-zinc-200">{{ product.stock.toLocaleString() }}</span>
                        </div>

                        <!-- Indicadores visuales Destinado / Faltante -->
                        <div class="pt-2 border-t border-gray-100 dark:border-zinc-800 text-[10px] sm:text-[11px] leading-tight">
                            <template v-if="product.required_quantity > 0">
                                <div v-if="product.stock >= product.required_quantity" class="text-emerald-600 dark:text-emerald-400 font-semibold flex items-start gap-1">
                                    <i class="pi pi-check-circle mt-0.5 text-[10px]"></i>
                                    <span>Hay suficientes (Piden {{ product.required_quantity }})</span>
                                </div>
                                <div v-else class="text-red-500 dark:text-red-400 font-semibold flex items-start gap-1">
                                    <i class="pi pi-exclamation-circle mt-0.5 text-[10px]"></i>
                                    <span>Faltan {{ product.required_quantity - product.stock }} (Piden {{ product.required_quantity }})</span>
                                </div>
                                <div class="text-gray-400 dark:text-zinc-500 mt-1" v-if="product.requiring_orders?.length">
                                    En uso para órdenes: 
                                    <span v-for="ord in product.requiring_orders" :key="ord.order_number" class="mr-1 font-medium text-gray-500 dark:text-zinc-400">#{{ ord.order_number }}</span>
                                </div>
                            </template>
                            <template v-else>
                                <span class="text-gray-400 dark:text-zinc-500 italic flex items-center gap-1">
                                    <i class="pi pi-info-circle text-[10px]"></i> Disponible, sin reservas
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN DE KITS TERMINADOS (Grid hacia abajo) -->
        <div v-if="finishedKits.length > 0" class="mb-10">
            <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">
                Kits Terminados
            </h2>
            
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 lg:gap-4">
                <div v-for="product in finishedKits" :key="product.id" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl shadow-sm hover:shadow-md transition-shadow flex flex-col overflow-hidden">
                    <div class="w-full aspect-square bg-gray-100 dark:bg-zinc-950 relative">
                        <img 
                            v-if="product.media && product.media[0]?.original_url && !imageErrorState[product.id]"
                            draggable="false"
                            :src="product.media[0]?.original_url" 
                            @error="onImageError(product.id)"
                            :alt="product.name" 
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-400 dark:text-zinc-600">
                            <i class="pi pi-image" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                    
                    <div class="p-3 flex flex-col flex-1">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-zinc-100 leading-tight mb-2 line-clamp-2" :title="product.name">{{ product.name }}</h3>
                        
                        <div class="flex justify-between items-center mb-2 mt-auto">
                            <span class="text-xs text-gray-500 dark:text-zinc-400 font-medium">Existencia:</span>
                            <span class="font-bold text-gray-800 dark:text-zinc-200">{{ product.stock.toLocaleString() }}</span>
                        </div>

                        <!-- Lógica de En Curso para Kits terminados (Lo que se está fabricando ahora mismo) -->
                        <div v-if="product.production_orders && product.production_orders.length > 0" class="border-t border-gray-100 dark:border-zinc-800 pt-2 mt-2 space-y-2">
                            <div v-for="order in product.production_orders" :key="order.id">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[9px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wide">
                                        <i class="pi pi-spin pi-cog mr-0.5"></i> Fab #{{ order.order_number.replace('TPSP-', '') }}
                                    </span>
                                    <span class="text-[9px] text-gray-500 dark:text-zinc-400 font-medium">
                                        {{ order.quantity_produced }} / {{ order.quantity_requested }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-1 overflow-hidden">
                                    <div class="bg-blue-500 h-1 rounded-full" :style="{ width: (order.quantity_requested > 0 ? (order.quantity_produced / order.quantity_requested * 100) : 0) + '%' }"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- SECCIÓN HISTORIAL EXPANDIBLE (Órdenes -> Entregas) -->
        <h2 class="text-xl sm:text-2xl font-semibold text-gray-800 dark:text-zinc-200 mb-4 pb-2 border-b border-gray-200 dark:border-zinc-800">
            Progreso y Entregas por Orden
        </h2>

        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-zinc-800 shadow-sm bg-white dark:bg-zinc-900">
            <DataTable 
                :value="productionOrders" 
                v-model:expandedRows="expandedRows" 
                @rowExpand="onRowExpand" 
                :loading="loadingOrders" 
                dataKey="id"
                responsiveLayout="scroll" 
                :rows="10" 
                :paginator="true"
                class="zinc-table"
            >
                <Column expander style="width: 3rem" />
                
                <Column field="order_number" header="Folio" style="min-width: 100px;">
                     <template #body="{ data }"><span class="text-gray-500 dark:text-zinc-400 font-medium">#{{ data.order_number }}</span></template>
                </Column>

                <Column field="product.name" header="Producto" style="min-width: 150px;">
                     <template #body="{ data }"><span class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.product?.name || 'Desconocido' }}</span></template>
                </Column>

                <Column header="Progreso" style="min-width: 150px;">
                    <template #body="{ data }">
                        <div class="flex flex-col text-sm">
                            <span class="text-gray-600 dark:text-zinc-400">
                                <strong class="text-gray-800 dark:text-zinc-200">{{ data.quantity_delivered || 0 }}</strong> entregados de <strong>{{ data.quantity_requested }}</strong>
                            </span>
                        </div>
                    </template>
                </Column>

                <Column header="Falta Entregar" style="min-width: 130px;">
                    <template #body="{ data }">
                        <span class="font-bold" :class="(data.quantity_requested - (data.quantity_delivered || 0)) > 0 ? 'text-red-500' : 'text-emerald-500'">
                            {{ Math.max(0, data.quantity_requested - (data.quantity_delivered || 0)) }}
                        </span>
                    </template>
                </Column>

                <Column field="status" header="Estado" style="min-width: 120px;">
                    <template #body="{ data }">
                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="!rounded-md text-[10px] sm:text-xs font-bold tracking-wide" />
                    </template>
                </Column>

                <!-- Expansión: Historial de Entregas Individuales -->
                <template #expansion="{ data }">
                    <div class="p-4 sm:p-6 bg-gray-50 dark:bg-zinc-950 border-t border-b border-gray-100 dark:border-zinc-800">
                        <h5 class="font-bold mb-3 text-sm text-gray-800 dark:text-zinc-200">
                            Historial de Entregas para la Orden #{{ data.order_number }}
                        </h5>
                        
                        <div v-if="!orderDeliveries[data.id]" class="text-gray-500 text-sm flex items-center gap-2">
                            <i class="pi pi-spin pi-spinner"></i> Cargando entregas...
                        </div>
                        
                        <DataTable 
                            v-else-if="orderDeliveries[data.id].length > 0" 
                            :value="orderDeliveries[data.id]" 
                            class="zinc-table-mini bg-white dark:bg-zinc-900 rounded-lg overflow-hidden border border-gray-200 dark:border-zinc-800 shadow-sm"
                        >
                            <Column field="created_at" header="Fecha de Entrega">
                                <template #body="slotProps">
                                    <span class="text-sm font-medium text-gray-700 dark:text-zinc-300">{{ formatDisplayDate(slotProps.data.created_at) }}</span>
                                </template>
                            </Column>
                            <Column field="quantity" header="Cantidad Entregada">
                                <template #body="slotProps">
                                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                        +{{ Math.abs(slotProps.data.quantity).toLocaleString() }}
                                    </span>
                                </template>
                            </Column>
                        </DataTable>
                        
                        <div v-else class="text-gray-500 dark:text-zinc-400 text-sm italic bg-white dark:bg-zinc-900 p-3 rounded-lg border border-gray-200 dark:border-zinc-800">
                            No se han registrado entregas todavía para esta orden de producción.
                        </div>
                    </div>
                </template>
            </DataTable>
        </div>
    </div>
</template>

<style scoped>
/* Zinc Theme Overrides for PrimeVue DataTable */
:deep(.zinc-table .p-datatable-thead > tr > th),
:deep(.zinc-table-mini .p-datatable-thead > tr > th) {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
    font-size: 0.8rem;
    text-transform: uppercase;
}
.dark :deep(.zinc-table .p-datatable-thead > tr > th),
.dark :deep(.zinc-table-mini .p-datatable-thead > tr > th) {
    background-color: #18181b !important; /* zinc-950 */
    color: #a1a1aa !important; /* zinc-400 */
    border-bottom: 1px solid #27272a; /* zinc-800 */
}
:deep(.zinc-table .p-datatable-tbody > tr),
:deep(.zinc-table-mini .p-datatable-tbody > tr) {
    background-color: transparent !important;
    color: inherit;
}
:deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td),
:deep(.zinc-table-mini .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #f4f4f5;
}
.dark :deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td),
.dark :deep(.zinc-table-mini .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #27272a;
}
</style>