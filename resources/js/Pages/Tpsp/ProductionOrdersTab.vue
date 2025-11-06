<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';
import ProgressBar from 'primevue/progressbar'; // <-- Importar ProgressBar

const orders = ref([]);
const loading = ref(true);
const toast = useToast();

// --- Estado para Modales ---
// ... (código existente)
const progressModalVisible = ref(false);
const deliverModalVisible = ref(false);
const selectedOrder = ref(null);
const progressData = ref({ quantity: null });
const deliverData = ref({ delivery_date: null, unit_price: null });
const isSubmitting = ref(false);

// --- Opciones para el Dropdown de Estado ---
// ... (código existente)
const statusOptions = ref([
    { label: 'En Progreso', value: 'En Progreso' },
    { label: 'Cancelado', value: 'Cancelado' },
    // Omitimos 'Pendiente' y 'Completado' según tu solicitud
]);

const fetchOrders = async () => {
// ... (código existente)
    loading.value = true;
    try {
        const response = await axios.get('/tpsp/production-orders');
        orders.value = response.data;
    } catch (error) {
        console.error("Error fetching production orders:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las órdenes.', life: 3000 });
    } finally {
        loading.value = false;
    }
};

// --- Formateo de Fechas ---
// ... (código existente)
const formatDate = (dateString) => {
// ... (código existente)
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        // Prevenimos problemas de zona horaria al interpretar la fecha
        const utcDate = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
        
        return utcDate.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC' // Aseguramos consistencia
        }).replace('.', '').replace(' de ', '-'); // Limpieza para formato '07-Nov-2025'
    } catch (e) {
        console.error("Error formatting date:", e);
        return dateString;
    }
};

// --- NUEVO: Función para calcular el progreso ---
/**
 * Calcula el porcentaje de progreso de producción.
 * @param {number} produced - Cantidad producida
 * @param {number} requested - Cantidad solicitada
 */
const getProductionProgress = (produced, requested) => {
    if (!requested || requested === 0) {
        return 0;
    }
    const progress = (produced / requested) * 100;
    return parseFloat(progress.toFixed(2)); // Redondear a 2 decimales
};


// Mapeo de estados a colores de Tag
const getStatusSeverity = (status) => {
// ... (código existente)
    switch (status) {
        case 'Pendiente': return 'warning';
        case 'En Progreso': return 'info';
        case 'Completado': return 'success';
        case 'Cancelado': return 'danger';
        default: return 'secondary';
    }
};

// --- Manejadores de Modales ---

// Modal de Progreso
const openProgressModal = (order) => {
// ... (código existente)
    selectedOrder.value = order;
    progressData.value = { quantity: 1 }; // Default a 1
    progressModalVisible.value = true;
};

const submitAddProgress = async () => {
// ... (código existente)
    if (!selectedOrder.value || !progressData.value.quantity || progressData.value.quantity <= 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'La cantidad debe ser mayor a 0.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/${selectedOrder.value.id}/add-progress`;
        const response = await axios.post(url, progressData.value);

        // Actualizar la orden en la lista local
        const index = orders.value.findIndex(o => o.id === selectedOrder.value.id);
        if (index !== -1) {
            orders.value[index] = response.data;
        }
        
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Progreso agregado correctamente.', life: 3000 });
        progressModalVisible.value = false;
    } catch (error) {
        console.error("Error adding progress:", error);
        const detail = error.response?.data?.message || 'Error desconocido al agregar progreso.';
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};

// Modal de Entrega
const openDeliverModal = (order) => {
// ... (código existente)
    if (order.quantity_produced <= 0) {
        toast.add({ severity: 'warn', summary: 'Sin producción', detail: 'No se puede entregar una orden sin producción registrada.', life: 4000 });
        return;
    }
    selectedOrder.value = order;
    deliverData.value = { delivery_date: new Date(), unit_price: null };
    deliverModalVisible.value = true;
};

const submitDeliverOrder = async () => {
// ... (código existente)
    const { delivery_date, unit_price } = deliverData.value;
    if (!selectedOrder.value || !delivery_date || unit_price == null || unit_price < 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Complete todos los campos correctamente.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/${selectedOrder.value.id}/deliver`;
        const response = await axios.post(url, deliverData.value);

        // Actualizar la orden en la lista local
        const index = orders.value.findIndex(o => o.id === selectedOrder.value.id);
        if (index !== -1) {
            orders.value[index] = response.data;
        }

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden marcada como entregada y completada.', life: 3000 });
        deliverModalVisible.value = false;
    } catch (error) {
        console.error("Error delivering order:", error);
        const detail = error.response?.data?.message || 'Error desconocido al entregar la orden.';
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};

// --- Manejador de Dropdown de Estado ---
const onStatusChange = async (event, order) => {
// ... (código existente)
    const newStatus = event.value;
    if (!newStatus || newStatus === order.status) return;

    // Guardar el estado anterior en caso de error
    const oldStatus = order.status;
    // Actualización optimista
    order.status = newStatus;

    try {
        const url = `/tpsp/production-orders/${order.id}/status`;
        await axios.patch(url, { status: newStatus });
        toast.add({ severity: 'success', summary: 'Actualizado', detail: `Estado cambiado a ${newStatus}`, life: 3000 });
        // Opcional: recargar solo esta orden
        // fetchOrders(); // Opcional, la UI ya se actualizó
    } catch (error) {
        // Revertir en caso de error
        order.status = oldStatus;
        console.error("Error updating status:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 4000 });
    }
};


onMounted(fetchOrders);

</script>

<template>
    <div>
        <Toast />
        <Button label="Recargar" icon="pi pi-refresh" class="p-button-sm mb-3" @click="fetchOrders" :loading="loading" />
        
        <DataTable :value="orders" :loading="loading" responsiveLayout="scroll" stripedRows paginator :rows="10">
            
            <Column field="order_number" header="N° Orden" :sortable="true" style="min-width: 100px;"></Column>
            
            <Column field="product.name" header="Producto" style="min-width: 150px;"></Column>

            <!-- Fechas Formateadas -->
            <Column header="Creado" :sortable="true" field="created_at" style="min-width: 120px;">
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.created_at) }}
                </template>
            </Column>
            <Column header="Entrega" :sortable="true" field="due_date" style="min-width: 120px;">
                <template #body="slotProps">
                    {{ formatDate(slotProps.data.due_date) }}
                </template>
            </Column>

            <!-- 
                COLUMNAS "SOLICITADO" Y "PRODUCIDO" ELIMINADAS
                <Column field="quantity_requested" header="Solicitado" align="center"></Column>
                <Column field="quantity_produced" header="Producido" align="center"></Column>
            -->

            <!-- NUEVA COLUMNA DE PROGRESO -->
            <Column header="Progreso" style="min-width: 170px;">
                <template #body="slotProps">
                    <div class="flex flex-col">
                        <!-- La barra de progreso -->
                        <ProgressBar :value="getProductionProgress(slotProps.data.quantity_produced, slotProps.data.quantity_requested)" />
                        
                        <!-- Etiqueta de texto (Ej: 5 / 10) -->
                        <span class="text-xs text-center mt-1 font-semibold">
                            {{ slotProps.data.quantity_produced }} / {{ slotProps.data.quantity_requested }}
                        </span>
                    </div>
                </template>
            </Column>
            
            <!-- Estado (Tag) -->
            <Column field="status" header="Estado" style="min-width: 150px;">
                <template #body="slotProps">
                    <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" />
                </template>
            </Column>

            <!-- Dropdown para Cambiar Estado -->
            <Column header="Cambiar Estado" style="min-width: 170px;">
                <template #body="slotProps">
                    <!-- Deshabilitado si está Completado o Cancelado -->
                    <Dropdown 
                        v-if="slotProps.data.status !== 'Completado' && slotProps.data.status !== 'Cancelado'"
                        :modelValue="slotProps.data.status" 
                        :options="statusOptions" 
                        optionLabel="label" 
                        optionValue="value" 
                        placeholder="Cambiar estado"
                        class="p-inputtext-sm w-full"
                        @change="onStatusChange($event, slotProps.data)"
                    />
                    <span v-else class="text-gray-500 italic text-sm">No aplica</span>
                </template>
            </Column>

            <!-- Botones de Acción -->
            <Column header="Acciones" bodyStyle="text-align: center; overflow: visible;" style="min-width: 250px;">
                <template #body="slotProps">
                    <div class="flex gap-2 justify-center">
                         <!-- Deshabilitado si está Completado o Cancelado -->
                        <Button 
                            icon="pi pi-plus" 
                            label="Progreso"
                            class="p-button-sm p-button-info" 
                            @click="openProgressModal(slotProps.data)"
                            :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado'"
                        />
                        <Button 
                            icon="pi pi-check" 
                            label="Entregar"
                            class="p-button-sm p-button-success" 
                            @click="openDeliverModal(slotProps.data)"
                            :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado' || slotProps.data.quantity_produced !== slotProps.data.quantity_requested"
                        />
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Modal para Agregar Progreso -->
        <Dialog v-model:visible="progressModalVisible" modal header="Agregar Progreso de Producción" :style="{ width: '400px' }">
            <div class="p-fluid flex flex-col gap-4" v-if="selectedOrder">
                <div>
                    <span class="font-bold">{{ selectedOrder.product.name }}</span>
                    <br>
                    <span>Solicitado: {{ selectedOrder.quantity_requested }}</span>
                    <br>
                    <span>Producido hasta ahora: {{ selectedOrder.quantity_produced }}</span>
                </div>
                
                <div class="field">
                    <label for="progressQty">Cantidad a agregar:</label>
                    <InputNumber id="progressQty" v-model="progressData.quantity" mode="decimal" :min="1" :max="selectedOrder.quantity_requested - selectedOrder.quantity_produced" />
                    <small v-if="selectedOrder">Máximo: {{ selectedOrder.quantity_requested - selectedOrder.quantity_produced }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="progressModalVisible = false" />
                <Button label="Guardar Progreso" icon="pi pi-check" @click="submitAddProgress" :loading="isSubmitting" />
            </template>
        </Dialog>

        <!-- Modal para Entregar (Venta) -->
        <Dialog v-model:visible="deliverModalVisible" modal header="Registrar Entrega (Venta)" :style="{ width: '450px' }">
            <div class="p-fluid flex flex-col gap-4" v-if="selectedOrder">
                <div>
                    <span class="font-bold">{{ selectedOrder.product.name }}</span>
                    <br>
                    <span>Se entregarán <strong>{{ selectedOrder.quantity_produced }}</strong> unidades (total producido).</span>
                </div>

                <div class="field">
                    <label for="deliveryDate">Fecha de Entrega:</label>
                    <Calendar id="deliveryDate" v-model="deliverData.delivery_date" dateFormat="dd/mm/yy" showIcon />
                </div>
                
                <div class="field">
                    <label for="unitPrice">Precio Unitario del Kit (Venta):</label>
                    <InputNumber id="unitPrice" v-model="deliverData.unit_price" mode="currency" currency="MXN" locale="es-MX" />
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="deliverModalVisible = false" />
                <Button label="Completar y Entregar" icon="pi pi-check" class="p-button-success" @click="submitDeliverOrder" :loading="isSubmitting" />
            </template>
        </Dialog>

    </div>
</template>

<style scoped>
/* Estilos para asegurar que el dropdown no se corte en la tabla */
:deep(.p-datatable-tbody > tr > td) {
    overflow: visible;
}

/* Estilo para la barra de progreso */
:deep(.p-progressbar) {
    height: 1.25rem;
    background-color: #e9ecef;
}

:deep(.p-progressbar .p-progressbar-value) {
    background: #10b981; /* Un verde (success) */
}
</style>