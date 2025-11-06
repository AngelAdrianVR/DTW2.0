<script setup>
import { ref, onMounted } from 'vue';
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
    }
});

// --- Estado ---
const movements = ref([]);
const loading = ref(true);
const filterStartDate = ref(null);
const filterEndDate = ref(null);
// NUEVO: Estado para rastrear errores de carga de imágenes
const imageErrorState = ref({});

// --- Helpers de Formato ---

function formatDisplayDate(dateString) {
    if (!dateString) return '---';
    const date = new Date(dateString);
    const datePart = date.toLocaleDateString('es-ES', { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric' 
    });
    const timePart = date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: true 
    });
    return `${datePart.replace('.', '')}, ${timePart}`;
}

function formatDateForAPI(date) {
    if (!date) return null;
    let d = new Date(date);
    d = new Date(d.getTime() - (d.getTimezoneOffset() * 60000));
    return d.toISOString().split('T')[0];
}

// NUEVO: Función para marcar un error de imagen
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
    
    <!-- 
      CONTENEDOR PRINCIPAL MEJORADO:
      - Añadido 'bg-gray-50' para un fondo sutil.
      - 'min-h-screen' para que el fondo cubra toda la altura.
      - 'p-6' para más espaciado.
    -->
    <div class="container mx-auto p-6 max-w-7xl bg-gray-50 min-h-screen">

        <!-- TÍTULO PRINCIPAL MEJORADO: -->
        <h1 class="text-3xl font-semibold text-gray-900 mb-8">Inventario y Ventas</h1>

        <!-- SECCIÓN DE PRODUCTOS -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 pb-2 border-b">Productos en Existencia</h2>
        
        <!-- 
          SCROLL HORIZONTAL IMPLEMENTADO:
          - Se usa 'flex' y 'overflow-x-auto' para crear el contenedor de scroll.
          - 'space-x-4' añade espaciado entre las tarjetas.
          - Cada tarjeta tiene un ancho fijo ('w-60') y 'flex-shrink-0' para evitar que se encoja.
        -->
        <div class="flex overflow-x-auto space-x-4 py-4 mb-8">
            <Card v-for="product in products" :key="product.id" class="overflow-hidden rounded-lg shadow-sm flex-shrink-0 w-52">
                <template #header>
                    <!-- 
                      CONTENEDOR DE IMAGEN MEJORADO:
                      - 'h-48' fija la altura para evitar saltos de contenido (CLS).
                      - 'v-if' muestra la imagen solo si existe la URL y no hay error.
                      - 'v-else' muestra el placeholder con ícono y texto.
                    -->
                    <div class="w-full h-48">
                        <img 
                            v-if="product.media[0]?.original_url && !imageErrorState[product.id]"
                            draggable="false"
                            :src="product.media[0]?.original_url" 
                            @error="onImageError(product.id)"
                            :alt="product.name" 
                            class="w-full h-full object-cover"
                        />
                        <!-- Placeholder Mejorado -->
                        <div v-else class="w-full h-full bg-slate-100 flex flex-col items-center justify-center text-slate-500">
                            <i class="pi pi-image" style="font-size: 2.5rem;"></i>
                            <span class="mt-2 text-sm font-medium">Imagen no disponible</span>
                        </div>
                    </div>
                </template>
                <template #title>
                    <span class="text-lg font-semibold">{{ product.name }}</span>
                </template>
                <template #content>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Existencia:</span>
                        <!-- Estilo de stock más sutil -->
                        <span class="text-lg font-semibold text-gray-800">{{ product.stock.toLocaleString() }}</span>
                    </div>
                </template>
            </Card>
        </div>

        <!-- SECCIÓN DE MOVIMIENTOS -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-4 pb-2 border-b">Historial de Entregas</h2>

        <!-- 
          BARRA DE FILTROS MEJORADA:
          - Diseño más limpio sin fondo ni bordes, solo espaciado.
          - 'items-end' alinea los botones correctamente.
        -->
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
          CONTENEDOR DE TABLA:
          - Se envuelve la tabla en un 'div' con bordes redondeados y sombra
            para un aspecto de "tarjeta" moderno.
        -->
        <div class="rounded-lg overflow-hidden border border-gray-200 shadow-sm">
            <DataTable :value="movements" :loading="loading" 
                responsiveLayout="scroll" :rows="20" :paginator="true"
                dataKey="id"
                class="p-datatable-customers"> <!-- PrimeVue a veces necesita clases BEM para overrides -->
                
                <Column header="Producto">
                    <template #body="slotProps">
                        <div class="flex items-center gap-3">
                            <!-- 
                              PLACEHOLDER EN TABLA:
                              - Misma lógica 'v-if'/'v-else' para la imagen.
                              - Tamaño reducido ('size-10') y 'rounded-md'.
                            -->
                            <div class="size-10 rounded-md flex-shrink-0">
                                <img v-if="slotProps.data.product?.media[0]?.original_url && !imageErrorState[slotProps.data.product?.id]"
                                    draggable="false"
                                    :src="slotProps.data.product?.media[0]?.original_url"
                                    @error="onImageError(slotProps.data.product?.id)"
                                    :alt="slotProps.data.product?.name"
                                    class="w-full h-full object-cover rounded-md"
                                />
                                <!-- Placeholder de ícono pequeño -->
                                <div v-else class="w-full h-full rounded-md bg-slate-100 flex items-center justify-center text-slate-400">
                                    <i class="pi pi-image" style="font-size: 1.25rem;"></i>
                                </div>
                            </div>
                            <span class="font-medium">{{ slotProps.data.product?.name || 'Producto no encontrado' }}</span>
                        </div>
                    </template>
                </Column>
                
                <Column field="created_at" header="Fecha de entrega" :sortable="true">
                    <template #body="slotProps">
                        {{ formatDisplayDate(slotProps.data.created_at) }}
                    </template>
                </Column>

                <Column header="Cantidad entregada" style="width: 15rem">
                    <template #body="slotProps">
                        <span class="font-semibold text-green-600">
                            {{ Math.abs(slotProps.data.quantity).toLocaleString() }}
                        </span>
                    </template>
                </Column>

            </DataTable>
        </div>
    </div>
</template>

<style scoped>
/* Estilos adicionales si son necesarios */
.container {
    font-family: 'Inter', sans-serif;
}

/* Estilos globales de PrimeVue (si no usas 'unstyled'):
  Estos 'deep' selectors ayudan a forzar estilos de Tailwind
  dentro de los componentes de PrimeVue.
*/

/* Cabeceras de la tabla más limpias */
:deep(.p-datatable .p-datatable-thead > tr > th) {
    background-color: #f9fafb; /* bg-gray-50 */
    color: #374151; /* text-gray-700 */
    font-weight: 600; /* font-semibold */
    border-color: #e5e7eb; /* border-gray-200 */
}

/* Celdas de la tabla */
:deep(.p-datatable .p-datatable-tbody > tr > td) {
    border-color: #e5e7eb; /* border-gray-200 */
}

/* Paginador */
:deep(.p-paginator) {
    border-top: 1px solid #e5e7eb; /* border-t border-gray-200 */
    background-color: #ffffff; /* bg-white */
}

/* Estilos de la tarjeta (Card) de PrimeVue */
:deep(.p-card .p-card-title) {
    font-size: 1.125rem; /* text-lg */
    font-weight: 600; /* font-semibold */
}
:deep(.p-card .p-card-content) {
    padding-top: 0.5rem; /* pt-2 */
    padding-bottom: 0.5rem; /* pb-2 */
}
</style>