<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';

// Helper local para evitar importación por ahora
function formatCurrency(value) {
    if (value === null || value === undefined) {
        return '---';
    }
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(value);
}

// Formato: "07-Nov-2025, 11:40 AM"
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
    // Limpia el '.' que 'es-ES' agrega a 'nov.'
    return `${datePart.replace('.', '')}, ${timePart}`;
}

// --- AÑADIDO: Helper para formatear fecha para la API (YYYY-MM-DD) ---
function formatDateForAPI(date) {
    if (!date) return null;
    let d = new Date(date);
    // Ajustar por la zona horaria local para evitar errores de un día
    d = new Date(d.getTime() - (d.getTimezoneOffset() * 60000));
    return d.toISOString().split('T')[0];
}


const toast = useToast();
const movements = ref([]);
const allProducts = ref([]);
const loading = ref(true);

// Opciones de tu migración
const movementTypes = ref(['Compra', 'Consumo_Produccion', 'Entrada_Produccion', 'Venta', 'Ajuste']);

const newMovement = ref({
    product_id: null,
    type: null,
    quantity: 0,
    unit_price: 0
});

// --- Refs para los nuevos filtros de backend ---
const filterType = ref(null);
const filterStartDate = ref(null);
const filterEndDate = ref(null);


// --- NUEVO COMPUTED PARA TOTAL DE VENTAS ---
const historicTotalSales = computed(() => {
    return movements.value
        .filter(m => m.type === 'Venta' && m.total_price)
        .reduce((acc, m) => acc + parseFloat(m.total_price), 0);
});

// --- ACTUALIZADO: fetchMovements ahora usa los filtros ---
const fetchMovements = async () => {
    loading.value = true;
    
    // Construir parámetros de consulta
    const params = new URLSearchParams();
    if (filterType.value) {
        params.append('type', filterType.value);
    }
    const apiStartDate = formatDateForAPI(filterStartDate.value);
    if (apiStartDate) {
        params.append('date_start', apiStartDate);
    }
    const apiEndDate = formatDateForAPI(filterEndDate.value);
    if (apiEndDate) {
        params.append('date_end', apiEndDate);
    }

    try {
        const response = await axios.get(`/tpsp/inventory-movements?${params.toString()}`);
        movements.value = response.data.data; 
    } catch (error) {
        console.error("Error fetching movements:", error);
    } finally {
        loading.value = false;
    }
};

// --- AÑADIDO: Función para limpiar filtros ---
const clearFilters = () => {
    filterType.value = null;
    filterStartDate.value = null;
    filterEndDate.value = null;
    fetchMovements(); // Volver a cargar sin filtros
};


const fetchAllProducts = async () => {
    try {
        const response = await axios.get('/tpsp/products');
        allProducts.value = response.data;
    } catch (error) {
        console.error("Error fetching products:", error);
    }
};

const addMovement = async () => {
    const movementData = { ...newMovement.value };
    if ((movementData.type === 'Venta' || movementData.type === 'Consumo_Produccion') && movementData.quantity > 0) {
        movementData.quantity = -movementData.quantity;
    }
    
    if (movementData.type === 'Venta') {
        movementData.total_price = Math.abs(movementData.quantity) * (movementData.unit_price || 0);
    } else {
        movementData.unit_price = null; 
    }

    try {
        await axios.post('/tpsp/inventory-movements', movementData);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Movimiento registrado', life: 3000 });
        newMovement.value = { product_id: null, type: null, quantity: 0, unit_price: 0 };
        fetchMovements(); // Recargar movimientos (respetará los filtros actuales)

    } catch (error) {
        console.error("Error adding movement:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo registrar el movimiento', life: 3000 });
    }
};

const totalPrice = computed(() => {
    if (newMovement.value.type === 'Venta' && newMovement.value.quantity && newMovement.value.unit_price) {
        return Math.abs(newMovement.value.quantity) * newMovement.value.unit_price;
    }
    return 0;
});

onMounted(() => {
    fetchMovements();
    fetchAllProducts();
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-1">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 h-[460px]">
                <h3 class="text-lg font-bold mb-4 text-gray-800 dark:text-zinc-100">Registrar Movimiento</h3>
                
                <div class="flex flex-col gap-4">
                    <div class="field">
                        <label for="movType" class="dark:text-zinc-300 block mb-1">Tipo de Movimiento</label>
                        <Dropdown id="movType" v-model="newMovement.type" :options="movementTypes" placeholder="Seleccione tipo" class="w-full dark:bg-zinc-950 dark:border-zinc-700" />
                    </div>
                    <div class="field">
                        <label for="movProduct" class="dark:text-zinc-300 block mb-1">Producto</label>
                        <Dropdown id="movProduct" v-model="newMovement.product_id" :options="allProducts" optionLabel="name" optionValue="id" placeholder="Seleccione producto" class="w-full dark:bg-zinc-950 dark:border-zinc-700" />
                    </div>
                    <div class="field">
                        <label for="movQuantity" class="dark:text-zinc-300 block mb-1">Cantidad</label>
                        <InputNumber id="movQuantity" v-model="newMovement.quantity" mode="decimal" inputClass="w-full dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100" />
                        <small class="dark:text-zinc-500 block mt-1">Positivo para entradas (Compra), Negativo o Positivo para salidas (Venta, Consumo).</small>
                    </div>
                    
                    <div class="field" v-if="newMovement.type === 'Venta'">
                        <label for="movPrice" class="dark:text-zinc-300 block mb-1">Precio Unitario (Venta)</label>
                        <InputNumber id="movPrice" v-model="newMovement.unit_price" mode="currency" currency="MXN" locale="es-MX" inputClass="w-full dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100" />
                    </div>
                    <div class="text-xl font-bold text-gray-800 dark:text-zinc-200 mt-2" v-if="totalPrice > 0">
                        Total: {{ formatCurrency(totalPrice) }}
                    </div>
                    
                    <Button label="Registrar Movimiento" icon="pi pi-save" @click="addMovement" class="mt-2 !text-[var(--primary-text-color)]" />
                </div>
            </div>
        </div>
        
        <div class="md:col-span-2">
            
            <div class="flex justify-between items-center mb-4 p-4 bg-gray-50 dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl">
                <span class="text-lg font-bold text-gray-700 dark:text-zinc-100">Venta Histórica Total</span>
                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(historicTotalSales) }}</span>
            </div>

            <!-- --- AÑADIDO: Barra de Filtros --- -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-xl border border-gray-100 dark:border-zinc-800 mb-4">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label for="filterType" class="font-bold block mb-1 dark:text-zinc-300">Tipo Movimiento</label>
                        <Dropdown id="filterType" v-model="filterType" :options="movementTypes" placeholder="Todos los tipos" :showClear="true" class="w-full dark:bg-zinc-950 dark:border-zinc-700" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label for="filterStart" class="font-bold block mb-1 dark:text-zinc-300">Fecha Inicio</label>
                        <Calendar id="filterStart" v-model="filterStartDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Desde" inputClass="dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100" />
                    </div>
                    <div class="flex-1 min-w-[150px]">
                        <label for="filterEnd" class="font-bold block mb-1 dark:text-zinc-300">Fecha Fin</label>
                        <Calendar id="filterEnd" v-model="filterEndDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Hasta" inputClass="dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100" />
                    </div>
                    <div class="flex gap-2">
                        <Button icon="pi pi-filter" @click="fetchMovements" v-tooltip="'Filtrar'" class="!text-[var(--primary-text-color)]" />
                        <Button icon="pi pi-filter-slash" @click="clearFilters" severity="secondary" outlined v-tooltip="'Limpiar'" />
                    </div>
                </div>
            </div>

            <!-- Vista de Tabla (Escritorio) - Oculta en pantallas pequeñas -->
            <div class="hidden md:block bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                <DataTable :value="movements" :loading="loading" 
                    responsiveLayout="scroll" :rows="10" :paginator="true"
                    dataKey="id"
                    class="movements-table">
                    
                    <Column field="created_at" header="Fecha" :sortable="true">
                        <template #body="slotProps">
                            <span class="text-gray-600 dark:text-zinc-400 text-sm">{{ formatDisplayDate(slotProps.data.created_at) }}</span>
                        </template>
                    </Column>
                    <Column field="product.name" header="Producto">
                         <template #body="{ data }"><span class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.product.name }}</span></template>
                    </Column>
                    <Column field="type" header="Tipo" :sortable="true">
                         <template #body="{ data }"><span class="text-sm dark:text-zinc-300">{{ data.type }}</span></template>
                    </Column>

                    <Column field="quantity" header="Cantidad">
                        <template #body="slotProps">
                            <span :class="slotProps.data.quantity > 0 ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-red-500 font-bold'">
                                {{ slotProps.data.quantity }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column field="unit_price" header="Precio">
                        <template #body="slotProps">
                            <span class="dark:text-zinc-400 text-sm">{{ formatCurrency(slotProps.data.unit_price) }}</span>
                        </template>
                    </Column>
                    <Column field="total_price" header="Total">
                        <template #body="slotProps">
                            <span class="dark:text-zinc-200 font-medium">{{ formatCurrency(slotProps.data.total_price) }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Vista de Tarjetas (Móvil) - Oculta en pantallas medianas y grandes -->
            <div class="md:hidden">
                <!-- Estado de carga -->
                <div v-if="loading" class="text-center p-4 dark:text-zinc-400">
                    <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                    <p>Cargando movimientos...</p>
                </div>
                <!-- Estado vacío -->
                <div v-else-if="movements.length === 0" class="text-center p-4 text-gray-600 dark:text-zinc-400">
                    <p>No se encontraron movimientos (revise los filtros).</p>
                </div>
                <!-- Lista de tarjetas -->
                <div v-else class="flex flex-col gap-3">
                    <div v-for="move in movements" :key="move.id" 
                        class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl overflow-hidden shadow-sm relative pl-3">
                        
                        <!-- Indicador de color -->
                        <div class="absolute left-0 top-0 bottom-0 w-1.5" :class="move.quantity > 0 ? 'bg-emerald-500' : 'bg-red-500'"></div>
                        
                        <div class="p-4">
                            <!-- Header: Producto y Tipo -->
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-lg text-gray-800 dark:text-zinc-100">{{ move.product.name }}</span>
                                <span class="text-xs uppercase bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 px-1.5 py-0.5 rounded">{{ move.type }}</span>
                            </div>

                            <!-- Body: Cantidad y Precios -->
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-2xl font-bold" :class="move.quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'">
                                    {{ move.quantity > 0 ? '+' : '' }}{{ move.quantity }}
                                </span>
                                
                                <div class="flex flex-col items-end">
                                    <span v-if="move.total_price && move.total_price > 0" class="font-bold text-gray-800 dark:text-zinc-200">
                                        Total: {{ formatCurrency(move.total_price) }}
                                    </span>
                                    <span v-if="move.unit_price && move.unit_price > 0" class="text-xs text-gray-500 dark:text-zinc-500">
                                        ({{ formatCurrency(move.unit_price) }} c/u)
                                    </span>
                                </div>
                            </div>

                            <!-- Footer: Fecha -->
                            <div class="pt-2 border-t border-gray-100 dark:border-zinc-800">
                                <span class="text-xs text-gray-500 dark:text-zinc-500">{{ formatDisplayDate(move.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
/* Zinc Theme Overrides for PrimeVue DataTable */
.movements-table .p-datatable-thead > tr > th {
    background-color: #212121 !important;
    color: #d0d0d0 !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.movements-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.movements-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode 
  Agregamos html.dark para darle un "extra" de especificidad y ganarle a PrimeVue
*/
html.dark .movements-table .p-datatable-thead > tr > th,
.dark .movements-table .p-datatable-thead > tr > th {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .movements-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .movements-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>