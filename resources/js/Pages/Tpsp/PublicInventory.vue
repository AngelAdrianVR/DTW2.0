<script setup>
import { ref, onMounted, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Paginator from 'primevue/paginator'; // Usado por DataTable

// --- Props ---
const props = defineProps({
    products: {
        type: Array,
        default: () => []
        // Cada producto ahora puede incluir un array 'production_orders'
    }
});

// --- Computed Properties for Product Filtering ---
// Filtra los productos que NO son 'Kit Terminado'
const otherProducts = computed(() => {
    return props.products.filter(p => p.category !== 'Kit Terminado');
});

// Filtra los productos que SÍ son 'Kit Terminado'
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

/**
 * CAMBIO: Formatea la fecha para mostrar solo día, mes y año.
 */
function formatDisplayDate(dateString) {
    if (!dateString) return '---';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: 'short', // 'short' para "nov."
        year: 'numeric' 
    }).replace('.', ''); // Quita el punto de "nov." si lo hubiera
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

/**
 * NUEVO: Computed property para agrupar los movimientos por fecha.
 * Esto transforma la lista plana de movimientos en una lista agrupada.
 */
const groupedMovements = computed(() => {
    const groups = new Map();
    
    // 1. Agrupar movimientos en un Map
    for (const movement of movements.value) {
        // Usamos formatDisplayDate para obtener una clave de fecha consistente
        const dateKey = formatDisplayDate(movement.created_at);
        
        if (!groups.has(dateKey)) {
            groups.set(dateKey, {
                date: dateKey,
                originalDate: new Date(movement.created_at), // Guardamos la fecha original para ordenar
                items: [] // Array para los items de esta fecha
            });
        }
        
        // Añadir el item al grupo correspondiente
        groups.get(dateKey).items.push({
            product: movement.product,
            quantity: movement.quantity,
            id: movement.id // ID único para el :key del v-for
        });
    }
    
    // 2. Convertir el Map a un Array y ordenarlo por fecha (más nuevas primero)
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
    
    <!-- CAMBIO: Quitado 'dark:bg-gray-900', asegurando fondo claro -->
    <div class="container mx-auto p-6 max-w-7xl bg-gray-50 min-h-screen">

        <!-- TÍTULO PRINCIPAL (sin cambios de dark mode) -->
        <h1 class="text-3xl font-semibold text-gray-900 mb-8">Inventario y Entregas</h1>

        <!-- SECCIÓN DE PRODUCTOS (GENERALES) -->
        <div v-if="otherProducts.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Productos en Existencia</h2>
            
            <div class="flex overflow-x-auto space-x-4 py-4 mb-8">
                <!-- CAMBIO: Quitado 'dark:bg-gray-800' de Card -->
                <Card v-for="product in otherProducts" :key="product.id" class="overflow-hidden rounded-lg shadow-sm flex-shrink-0 w-52 bg-white">
                    <template #header>
                        <div class="w-full h-48">
                            <img 
                                v-if="product.media[0]?.original_url && !imageErrorState[product.id]"
                                draggable="false"
                                :src="product.media[0]?.original_url" 
                                @error="onImageError(product.id)"
                                :alt="product.name" 
                                class="w-full h-full object-cover"
                            />
                            <!-- CAMBIO: Placeholder claro -->
                            <div v-else class="w-full h-full bg-slate-100 flex flex-col items-center justify-center text-slate-500">
                                <i class="pi pi-image" style="font-size: 2.5rem;"></i>
                                <span class="mt-2 text-sm font-medium">Imagen no disponible</span>
                            </div>
                        </div>
                    </template>
                    <template #title>
                        <span class="text-lg font-semibold text-gray-900">{{ product.name }}</span>
                    </template>
                    <template #content>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Existencia:</span>
                            <span class="text-lg font-semibold text-gray-800">{{ product.stock.toLocaleString() }}</span>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- ***** SECCIÓN DE KITS TERMINADOS (MODIFICADA) ***** -->
        <div v-if="finishedKits.length > 0">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Existencias de kits terminados</h2>
            
            <div class="flex overflow-x-auto space-x-4 py-4 mb-8">
                <!-- CAMBIO: Quitado 'dark:bg-gray-800' de Card -->
                <Card v-for="product in finishedKits" :key="product.id" class="overflow-hidden rounded-lg shadow-sm flex-shrink-0 w-52 bg-white">
                    <template #header>
                        <div class="w-full h-48">
                            <img 
                                v-if="product.media[0]?.original_url && !imageErrorState[product.id]"
                                draggable="false"
                                :src="product.media[0]?.original_url" 
                                @error="onImageError(product.id)"
                                :alt="product.name" 
                                class="w-full h-full object-cover"
                            />
                            <!-- CAMBIO: Placeholder claro -->
                            <div v-else class="w-full h-full bg-slate-100 flex flex-col items-center justify-center text-slate-500">
                                <i class="pi pi-image" style="font-size: 2.5rem;"></i>
                                <span class="mt-2 text-sm font-medium">Imagen no disponible</span>
                            </div>
                        </div>
                    </template>
                    <template #title>
                        <span class="text-lg font-semibold text-gray-900">{{ product.name }}</span>
                    </template>
                    <template #content>
                        <!-- Existencia (actual) -->
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Existencia:</span>
                            <span class="text-lg font-semibold text-gray-800">{{ product.stock.toLocaleString() }}</span>
                        </div>

                        <!-- 
                          NUEVO: Sección de Pedidos en Curso.
                          Se muestra si el array 'production_orders' existe y tiene elementos.
                        -->
                        <div v-if="product.production_orders && product.production_orders.length > 0" 
                             class="border-t border-gray-200 pt-3 mt-3 space-y-3">
                            
                            <!-- Itera sobre cada orden activa para este producto -->
                            <div v-for="order in product.production_orders" :key="order.id">
                                <!-- Indicador de Pedido en curso -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-blue-600">
                                        <!-- Icono de engrane girando -->
                                        <i class="pi pi-spin pi-cog mr-2" style="font-size: 0.9rem; vertical-align: middle;"></i>
                                        <span style="vertical-align: middle;">Pedido en curso</span>
                                    </span>
                                </div>
                                
                                <!-- Texto de progreso -->
                                <div class="text-xs text-gray-500 my-1">
                                    Hecho: {{ order.quantity_produced.toLocaleString() }} / {{ order.quantity_requested.toLocaleString() }}
                                </div>
                                
                                <!-- Barra de Progreso (visual) -->
                                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                                    <div class="bg-blue-500 h-2 rounded-full" 
                                         :style="{ width: (order.quantity_requested > 0 ? (order.quantity_produced / order.quantity_requested * 100) : 0) + '%' }">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
        <!-- ***** FIN DE LA SECCIÓN MODIFICADA ***** -->


        <!-- SECCIÓN DE MOVIMIENTOS -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Historial de Entregas</h2>

        <!-- Filtros (sin cambios de dark mode) -->
        <div class="flex flex-wrap gap-4 mb-6 items-end">
            <div class="flex flex-col gap-2">
                <label for="filterStart" class="font-bold text-sm text-gray-700">Fecha Inicio</label>
                <Calendar id="filterStart" v-model="filterStartDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Desde" />
            </div>
            <div class="flex flex-col gap-2">
                <label for="filterEnd" class="font-bold text-sm text-gray-700">Fecha Fin</label>
                <Calendar id="filterEnd" v-model="filterEndDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Hasta" />
            </div>
            <div class="flex items-end gap-2">
                <Button label="Filtrar" icon="pi pi-filter" @click="fetchMovements" />
                <Button label="Limpiar" icon="pi pi-filter-slash" @click="clearFilters" class="p-button-outlined" />
            </div>
        </div>

        <!-- 
          CAMBIO: DataTable ahora usa 'groupedMovements'
          y tiene una estructura de columnas diferente.
        -->
        <div class="rounded-lg overflow-hidden border border-gray-200 shadow-sm bg-white">
            <DataTable :value="groupedMovements" :loading="loading" 
                responsiveLayout="scroll" :rows="10" :paginator="true"
                dataKey="date"
                class="p-datatable-customers">
                
                <!-- Columna de Fecha (Agrupada) -->
                <Column header="Fecha de entrega" :sortable="true" sortField="originalDate" style="width: 12rem">
                    <template #body="slotProps">
                        <!-- Muestra la fecha formateada que actúa como cabecera del grupo -->
                        <span class="font-semibold text-base text-gray-800">{{ slotProps.data.date }}</span>
                    </template>
                </Column>

                <!-- Columna de Productos (Agrupados) -->
                <Column header="Productos Entregados">
                    <template #body="slotProps">
                        <!-- 
                          Itera sobre el array 'items' de cada grupo de fecha.
                          Cada 'item' es un producto entregado ese día.
                        -->
                        <div class="flex flex-col gap-4 py-2">
                            <div v-for="item in slotProps.data.items" :key="item.id" 
                                 class="flex items-center gap-3 flex-wrap">
                                
                                <!-- Imagen del Producto -->
                                <div class="size-10 rounded-md flex-shrink-0">
                                    <img v-if="item.product?.media[0]?.original_url && !imageErrorState[item.product?.id]"
                                        draggable="false"
                                        :src="item.product?.media[0]?.original_url"
                                        @error="onImageError(item.product?.id)"
                                        :alt="item.product?.name"
                                        class="w-full h-full object-cover rounded-md"
                                    />
                                    <div v-else class="w-full h-full rounded-md bg-slate-100 flex items-center justify-center text-slate-400">
                                        <i class="pi pi-image" style="font-size: 1.25rem;"></i>
                                    </div>
                                </div>
                                
                                <!-- Nombre del Producto -->
                                <span class="font-medium text-gray-700 flex-1 min-w-[150px]">{{ item.product?.name || 'Producto no encontrado' }}</span>
                                
                                <!-- Cantidad Entregada (movida aquí) -->
                                <span class="font-semibold text-green-600 ml-auto pl-4">
                                    {{ Math.abs(item.quantity).toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </template>
                </Column>
                
                <!-- La columna de Cantidad individual ya no es necesaria, se movió adentro de Productos -->

            </DataTable>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
.container {
    font-family: 'Inter', sans-serif;
}

/* Estilos globales de PrimeVue (Estilos LIGEROS).
  No se necesitan estilos 'dark:'.
*/

/* Cabeceras de la tabla limpias */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f9fafb; /* bg-gray-50 */
    color: #374151; /* text-gray-700 */
    font-weight: 600; /* font-semibold */
    border-color: #e5e7eb; /* border-gray-200 */
    border-bottom-width: 2px;
}

/* Celdas de la tabla */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    border-color: #e5e7eb; /* border-gray-200 */
    vertical-align: top; /* Alinea la fecha en la parte superior */
}

/* Filas de la tabla (para hover) */
:deep(.p-datatable .p-datatable-tbody > tr:not(.p-datatable-emptymessage):hover) {
    background-color: #fcfcfd; /* Un hover muy sutil */
}

/* Paginador */
:deep(.p-paginator) {
    border-top: 1px solid #e5e7eb; /* border-t border-gray-200 */
    background-color: #ffffff; /* bg-white */
}

/* Estilos de la tarjeta (Card) de PrimeVue */
:deep(.p-card) {
    background: #ffffff; /* Fondo blanco explícito */
    color: #374151; /* Texto oscuro */
}
:deep(.p-card .p-card-title) {
    font-size: 1.125rem; /* text-lg */
    font-weight: 600; /* font-semibold */
    color: #111827; /* text-gray-900 */
}
:deep(.p-card .p-card-content) {
    padding-top: 0.5rem; /* pt-2 */
    padding-bottom: 0.5rem; /* pb-2 */
}

/* Estilos para el Calendario (asegura modo claro) */
:deep(.p-datepicker) {
    background: #ffffff;
    border: 1px solid #e5e7eb;
}
:deep(.p-datepicker .p-datepicker-header) {
    background: #f9fafb;
    color: #374151;
    border-bottom: 1px solid #e5e7eb;
}
</style>