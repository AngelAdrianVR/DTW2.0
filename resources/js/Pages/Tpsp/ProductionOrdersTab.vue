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
import ProgressBar from 'primevue/progressbar';

const orders = ref([]);
const loading = ref(true);
const toast = useToast();

// --- Estado para Modales ---
const progressModalVisible = ref(false);
const deliverModalVisible = ref(false);
const selectedOrder = ref(null);
const progressData = ref({ quantity: null });
const deliverData = ref({ delivery_date: null, unit_price: null });
const isSubmitting = ref(false);

// --- Opciones para el Dropdown de Estado ---
const statusOptions = ref([
    { label: 'En Progreso', value: 'En Progreso' },
    { label: 'Cancelado', value: 'Cancelado' },
]);

const fetchOrders = async () => {
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
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        const utcDate = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
        
        return utcDate.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC'
        }).replace('.', '').replace(' de ', '-');
    } catch (e) {
        console.error("Error formatting date:", e);
        return dateString;
    }
};

// --- Cálculo de progreso ---
const getProductionProgress = (produced, requested) => {
    if (!requested || requested === 0) {
        return 0;
    }
    const progress = (produced / requested) * 100;
    return parseFloat(progress.toFixed(2));
};

// Mapeo de estados a colores de Tag
const getStatusSeverity = (status) => {
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
    selectedOrder.value = order;
    progressData.value = { quantity: 1 };
    progressModalVisible.value = true;
};

const submitAddProgress = async () => {
    if (!selectedOrder.value || !progressData.value.quantity || progressData.value.quantity <= 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'La cantidad debe ser mayor a 0.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/${selectedOrder.value.id}/add-progress`;
        const response = await axios.post(url, progressData.value);

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
    if (order.quantity_produced <= 0) {
        toast.add({ severity: 'warn', summary: 'Sin producción', detail: 'No se puede entregar una orden sin producción registrada.', life: 4000 });
        return;
    }
    selectedOrder.value = order;
    deliverData.value = { delivery_date: new Date(), unit_price: null };
    deliverModalVisible.value = true;
};

const submitDeliverOrder = async () => {
    const { delivery_date, unit_price } = deliverData.value;
    if (!selectedOrder.value || !delivery_date || unit_price == null || unit_price < 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Complete todos los campos correctamente.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/${selectedOrder.value.id}/deliver`;
        const response = await axios.post(url, deliverData.value);

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
    const newStatus = event.value;
    if (!newStatus || newStatus === order.status) return;

    const oldStatus = order.status;
    order.status = newStatus;

    try {
        const url = `/tpsp/production-orders/${order.id}/status`;
        await axios.patch(url, { status: newStatus });
        toast.add({ severity: 'success', summary: 'Actualizado', detail: `Estado cambiado a ${newStatus}`, life: 3000 });
    } catch (error) {
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
        
        <!-- Vista de Tabla (Escritorio) - Oculta en pantallas pequeñas -->
        <div class="hidden md:block">
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

                <!-- Columna de Progreso -->
                <Column header="Progreso" style="min-width: 170px;">
                    <template #body="slotProps">
                        <div class="flex flex-col">
                            <ProgressBar :value="getProductionProgress(slotProps.data.quantity_produced, slotProps.data.quantity_requested)" />
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
        </div>

        <!-- Vista de Tarjetas (Móvil) - Oculta en pantallas medianas y grandes -->
        <div class="md:hidden">
            <!-- Estado de carga -->
            <div v-if="loading" class="text-center p-4">
                <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                <p>Cargando órdenes...</p>
            </div>
            <!-- Estado vacío -->
            <div v-else-if="orders.length === 0" class="text-center p-4">
                <p>No se encontraron órdenes.</p>
            </div>
            <!-- Lista de tarjetas -->
            <div v-else>
                <div v-for="order in orders" :key="order.id" class="order-card">
                    
                    <!-- Encabezado de la tarjeta: Número, Producto y Estado -->
                    <div class="card-header">
                        <div>
                            <span class="order-number">Orden #{{ order.order_number }}</span>
                            <div class="product-name">{{ order.product.name }}</div>
                        </div>
                        <Tag :value="order.status" :severity="getStatusSeverity(order.status)" />
                    </div>

                    <!-- Cuerpo: Progreso -->
                    <div class="progress-section">
                        <span class="progress-label">
                            Progreso: {{ order.quantity_produced }} / {{ order.quantity_requested }}
                        </span>
                        <ProgressBar :value="getProductionProgress(order.quantity_produced, order.quantity_requested)" style="height: 1rem" />
                    </div>

                    <!-- Información: Fechas -->
                    <div class="info-grid">
                        <div>
                            <span class="info-label">Creado:</span>
                            <span class="info-value">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div>
                            <span class="info-label">Entrega:</span>
                            <span class="info-value">{{ formatDate(order.due_date) }}</span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="action-section">
                        <!-- Cambiar Estado -->
                        <Dropdown 
                            v-if="order.status !== 'Completado' && order.status !== 'Cancelado'"
                            :modelValue="order.status" 
                            :options="statusOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Cambiar estado"
                            class="p-inputtext-sm w-full mb-3"
                            @change="onStatusChange($event, order)"
                        />
                        <span v-else class="text-gray-500 italic text-sm mb-3 block">Estado no modificable</span>
                        
                        <!-- Botones -->
                        <div class="flex gap-2 justify-between">
                            <Button 
                                icon="pi pi-plus" 
                                label="Progreso"
                                class="p-button-sm p-button-info flex-1" 
                                @click="openProgressModal(order)"
                                :disabled="order.status === 'Completado' || order.status === 'Cancelado'"
                            />
                            <Button 
                                icon="pi pi-check" 
                                label="Entregar"
                                class="p-button-sm p-button-success flex-1" 
                                @click="openDeliverModal(order)"
                                :disabled="order.status === 'Completado' || order.status === 'Cancelado' || order.quantity_produced !== order.quantity_requested"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>


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

/* Estilos para las tarjetas de órdenes en móvil */
.order-card {
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.order-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #334155; /* slate-700 */
    display: block;
}

.product-name {
    font-size: 1rem;
    font-weight: 600;
    color: #475569; /* slate-600 */
}

.progress-section {
    margin-bottom: 1rem;
}

.progress-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.info-label {
    display: block;
    font-weight: 600;
    color: #64748b; /* slate-500 */
}

.info-value {
    display: block;
}

.action-section {
    border-top: 1px solid #f1f5f9; /* slate-100 */
    padding-top: 1rem;
}
</style>