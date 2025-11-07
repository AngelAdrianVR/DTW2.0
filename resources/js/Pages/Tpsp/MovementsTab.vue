<script setup>
import { ref, onMounted, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar'; // <--- AÑADIDO: Importar Calendar

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
    <div class="grid">
        <div class="col-12 md:col-4">
            <Card>
                <template #title>Registrar Movimiento</template>
                <template #content>
                    <div class="p-fluid flex flex-column gap-3">
                        <div class="field">
                            <label for="movType">Tipo de Movimiento</label>
                            <Dropdown id="movType" v-model="newMovement.type" :options="movementTypes" placeholder="Seleccione tipo" />
                        </div>
                        <div class="field">
                            <label for="movProduct">Producto</label>
                            <Dropdown id="movProduct" v-model="newMovement.product_id" :options="allProducts" optionLabel="name" optionValue="id" placeholder="Seleccione producto" />
                        </div>
                        <div class="field flex flex-col">
                            <label for="movQuantity">Cantidad</label>
                            <InputNumber id="movQuantity" v-model="newMovement.quantity" mode="decimal" />
                            <small>Positivo para entradas (Compra), Negativo o Positivo para salidas (Venta, Consumo).</small>
                        </div>
                        
                        <div class="field" v-if="newMovement.type === 'Venta'">
                            <label for="movPrice">Precio Unitario (Venta)</label>
                            <InputNumber id="movPrice" v-model="newMovement.unit_price" mode="currency" currency="MXN" locale="es-MX" />
                        </div>
                        <div class="total-price" v-if="totalPrice > 0">
                            Total: {{ formatCurrency(totalPrice) }}
                        </div>
                        
                    </div>
                        <Button label="Registrar Movimiento" icon="pi pi-save" @click="addMovement" />
                </template>
            </Card>
        </div>
        <div class="col-12 md:col-8">
            
            <div class="flex justify-content-end align-items-center mb-3 p-3 bg-gray-100 dark:bg-gray-800 border-round">
                <span class="text-xl font-bold dark:text-gray-100 text-gray-700">Venta Histórica Total: </span>
                <span class="text-xl font-bold text-green-600 dark:text-green-300 ml-2">{{ formatCurrency(historicTotalSales) }}</span>
            </div>

            <!-- --- AÑADIDO: Barra de Filtros --- -->
            <div class="flex flex-wrap gap-3 mb-3 p-3 border-round bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700">
                <div class="flex flex-column gap-2">
                    <label for="filterType" class="font-bold">Tipo Movimiento</label>
                    <Dropdown id="filterType" v-model="filterType" :options="movementTypes" placeholder="Todos los tipos" :showClear="true" style="width: 200px;" />
                </div>
                <div class="flex flex-column gap-2">
                    <label for="filterStart" class="font-bold">Fecha Inicio</label>
                    <Calendar id="filterStart" v-model="filterStartDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Desde" />
                </div>
                <div class="flex flex-column gap-2">
                    <label for="filterEnd" class="font-bold">Fecha Fin</label>
                    <Calendar id="filterEnd" v-model="filterEndDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Hasta" />
                </div>
                <div class="flex align-items-end gap-2">
                    <Button label="Filtrar" icon="pi pi-filter" @click="fetchMovements" />
                    <Button label="Limpiar" icon="pi pi-filter-slash" @click="clearFilters" class="p-button-outlined" />
                </div>
            </div>

            <!-- Vista de Tabla (Escritorio) - Oculta en pantallas pequeñas -->
            <div class="hidden md:block">
                <DataTable :value="movements" :loading="loading" 
                    responsiveLayout="scroll" :rows="20" :paginator="true"
                    dataKey="id">
                    
                    <Column field="created_at" header="Fecha" :sortable="true">
                        <template #body="slotProps">
                            {{ formatDisplayDate(slotProps.data.created_at) }}
                        </template>
                    </Column>
                    <Column field="product.name" header="Producto"></Column>
                    <Column field="type" header="Tipo" :sortable="true"></Column>

                    <Column field="quantity" header="Cantidad">
                        <template #body="slotProps">
                            <span :class="slotProps.data.quantity > 0 ? 'text-green-500' : 'text-red-500'">
                                {{ slotProps.data.quantity }}
                            </span>
                        </template>
                    </Column>
                    
                    <Column field="unit_price" header="Precio Unit.">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.unit_price) }}
                        </template>
                    </Column>
                    <Column field="total_price" header="Monto Total">
                        <template #body="slotProps">
                            {{ formatCurrency(slotProps.data.total_price) }}
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Vista de Tarjetas (Móvil) - Oculta en pantallas medianas y grandes -->
            <div class="md:hidden">
                <!-- Estado de carga -->
                <div v-if="loading" class="text-center p-4">
                    <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                    <p>Cargando movimientos...</p>
                </div>
                <!-- Estado vacío -->
                <div v-else-if="movements.length === 0" class="text-center p-4 text-gray-600 dark:text-gray-400">
                    <p>No se encontraron movimientos (revise los filtros).</p>
                </div>
                <!-- Lista de tarjetas -->
                <div v-else class="px-1 pt-2">
                    <div v-for="move in movements" :key="move.id" 
                        class="movement-card bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                        
                        <!-- Indicador de color -->
                        <div class="quantity-indicator" :class="move.quantity > 0 ? 'bg-green-500' : 'bg-red-500'"></div>
                        
                        <!-- Detalles del Movimiento -->
                        <div class="movement-details">
                            <!-- Header: Producto y Tipo -->
                            <div class="card-header">
                                <span class="product-name text-slate-800 dark:text-slate-100">{{ move.product.name }}</span>
                                <span class="movement-type text-slate-500 dark:text-slate-400">{{ move.type }}</span>
                            </div>

                            <!-- Body: Cantidad y Precios -->
                            <div class="card-body">
                                <span class="quantity" :class="move.quantity > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    {{ move.quantity > 0 ? '+' : '' }}{{ move.quantity }}
                                </span>
                                
                                <div class="price-info text-slate-700 dark:text-slate-300">
                                    <span v-if="move.total_price && move.total_price > 0" class="total-price">
                                        Total: {{ formatCurrency(move.total_price) }}
                                    </span>
                                    <span v-if="move.unit_price && move.unit_price > 0" class="unit-price dark:text-slate-400">
                                        ({{ formatCurrency(move.unit_price) }} c/u)
                                    </span>
                                </div>
                            </div>

                            <!-- Footer: Fecha y Notas -->
                            <div class="card-footer border-t border-slate-100 dark:border-slate-700">
                                <span class="date text-slate-500 dark:text-slate-400">{{ formatDisplayDate(move.created_at) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.gap-3 {
    gap: 1.5rem;
}
.total-price {
    font-weight: bold;
    font-size: 1.1rem;
    margin-top: 10px;
}

/* --- ESTILOS PARA TARJETAS DE MOVIMIENTO (MÓVIL) --- */
.movement-card {
    display: flex;
    position: relative;
    padding: 0.75rem 1rem 0.75rem 1.25rem; /* Espacio para el indicador */
    margin-bottom: 0.75rem;
    border-radius: 8px;
    overflow: hidden; /* Para que el indicador se alinee bien */
}

.quantity-indicator {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 6px;
}

.movement-details {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 700;
    line-height: 1.3;
}

.movement-type {
    font-size: 0.8rem;
    font-weight: 500;
    text-transform: uppercase;
    flex-shrink: 0;
    margin-left: 0.5rem;
}

.card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.25rem;
}

.quantity {
    font-size: 1.5rem;
    font-weight: 700;
}

.price-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.9rem;
}

.total-price {
    font-weight: 600;
}

.unit-price {
    font-size: 0.8rem;
    color: #64748b; /* slate-500 */
}
/* La clase 'dark:text-slate-400' se aplica directamente en el template */

.card-footer {
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    /* El borde se aplica con Tailwind en el template */
}

.date {
    font-size: 0.8rem;
}
</style>