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
import axios from 'axios';

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

// Este objeto es el que da el estilo "Apple" evitando el fondo cuadrado de los modales
const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' }, 
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

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
        
        <!-- Vista de Tabla (Escritorio) - Estilo Minimalista Apple -->
        <div class="hidden md:block bg-white dark:bg-zinc-900 mt-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-800 p-2 sm:p-5 overflow-hidden">
            <DataTable :value="orders" :loading="loading" responsiveLayout="scroll" paginator :rows="10" class="apple-table" :rowsPerPageOptions="[10, 20, 50]">
                
                <Column field="order_number" header="Folio" :sortable="true" style="min-width: 100px;">
                     <template #body="{ data }"><span class="text-zinc-500 dark:text-zinc-400 font-medium">#{{ data.order_number }}</span></template>
                </Column>
                
                <Column field="product.name" header="Producto" style="min-width: 150px;">
                     <template #body="{ data }"><span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ data.product.name }}</span></template>
                </Column>

                <!-- Fechas Formateadas -->
                <Column header="Creado" :sortable="true" field="created_at" style="min-width: 120px;">
                    <template #body="slotProps">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ formatDate(slotProps.data.created_at) }}</span>
                    </template>
                </Column>
                <Column header="Entrega" :sortable="true" field="due_date" style="min-width: 120px;">
                    <template #body="slotProps">
                         <span class="text-zinc-600 dark:text-zinc-400">{{ formatDate(slotProps.data.due_date) }}</span>
                    </template>
                </Column>

                <!-- Columna de Progreso -->
                <Column header="Progreso" style="min-width: 170px;">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-1.5">
                            <ProgressBar :value="getProductionProgress(slotProps.data.quantity_produced, slotProps.data.quantity_requested)" class="custom-progress h-2" :showValue="false" />
                            <span class="text-[0.7rem] uppercase tracking-wider text-center font-bold text-zinc-500 dark:text-zinc-400">
                                {{ slotProps.data.quantity_produced }} / {{ slotProps.data.quantity_requested }}
                            </span>
                        </div>
                    </template>
                </Column>
                
                <!-- Estado (Tag) -->
                <Column field="status" header="Estado" style="min-width: 130px;">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="!rounded-full !px-3 !py-1 text-xs font-semibold tracking-wide" />
                    </template>
                </Column>

                <!-- Dropdown para Cambiar Estado -->
                <Column header="Ajuste" style="min-width: 160px;">
                    <template #body="slotProps">
                        <Dropdown 
                            v-if="slotProps.data.status !== 'Completado' && slotProps.data.status !== 'Cancelado'"
                            :modelValue="slotProps.data.status" 
                            :options="statusOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Estado"
                            class="!rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:bg-zinc-950 p-inputtext-sm w-full shadow-sm"
                            @change="onStatusChange($event, slotProps.data)"
                        />
                        <span v-else class="text-zinc-400 dark:text-zinc-600 text-sm flex items-center gap-2">
                            <i class="pi pi-lock text-xs"></i> Cerrada
                        </span>
                    </template>
                </Column>

                <!-- Botones de Acción -->
                <Column header="Acciones" bodyStyle="text-align: center; overflow: visible;" style="min-width: 120px;">
                    <template #body="slotProps">
                        <div class="flex gap-2 justify-center">
                            <Button 
                                icon="pi pi-plus" 
                                class="!rounded-xl !w-9 !h-9 !p-0 !bg-blue-50 dark:!bg-blue-900/30 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 transition-colors" 
                                v-tooltip.top="'Agregar Progreso'"
                                @click="openProgressModal(slotProps.data)"
                                :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado'"
                            />
                            <Button 
                                icon="pi pi-check" 
                                class="!rounded-xl !w-9 !h-9 !p-0 !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-600 dark:!text-emerald-400 hover:!bg-emerald-100 dark:hover:!bg-emerald-900/50 !border-0 transition-colors" 
                                v-tooltip.top="'Entregar Orden'"
                                @click="openDeliverModal(slotProps.data)"
                                :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado' || slotProps.data.quantity_produced !== slotProps.data.quantity_requested"
                            />
                        </div>
                    </template>
                </Column>
                <template #empty>
                    <div class="text-center p-8 text-zinc-500 flex flex-col items-center gap-2">
                        <i class="pi pi-inbox text-3xl"></i>
                        <span>No hay órdenes de producción registradas.</span>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Vista de Tarjetas (Móvil) -->
        <div class="md:hidden mt-4">
            <!-- Estado de carga -->
            <div v-if="loading" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-3">
                <i class="pi pi-spin pi-spinner text-3xl"></i>
                <p>Cargando órdenes...</p>
            </div>
            <!-- Estado vacío -->
            <div v-else-if="orders.length === 0" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-2">
                <i class="pi pi-inbox text-3xl"></i>
                <p>No se encontraron órdenes.</p>
            </div>
            <!-- Lista de tarjetas -->
            <div v-else class="flex flex-col gap-4">
                <div v-for="order in orders" :key="order.id" class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    
                    <!-- Encabezado de la tarjeta -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <span class="text-xs font-medium text-zinc-400 block mb-1">Orden #{{ order.order_number }}</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100 text-lg">{{ order.product.name }}</div>
                        </div>
                        <Tag :value="order.status" :severity="getStatusSeverity(order.status)" class="!rounded-full !px-3 !py-1 text-xs font-semibold tracking-wide" />
                    </div>

                    <!-- Cuerpo: Progreso -->
                    <div class="mb-5 bg-zinc-50 dark:bg-zinc-950 p-3 rounded-xl border border-zinc-100 dark:border-zinc-800/50">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Progreso
                            </span>
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                {{ order.quantity_produced }} / {{ order.quantity_requested }}
                            </span>
                        </div>
                        <ProgressBar :value="getProductionProgress(order.quantity_produced, order.quantity_requested)" class="custom-progress h-1.5" :showValue="false" />
                    </div>

                    <!-- Información: Fechas -->
                    <div class="grid grid-cols-2 gap-3 mb-5 text-sm">
                        <div>
                            <span class="text-xs text-zinc-400 block mb-0.5">Creado</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-400 block mb-0.5">Entrega</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ formatDate(order.due_date) }}</span>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="pt-4 border-t border-zinc-100 dark:border-zinc-800">
                        <!-- Cambiar Estado -->
                        <Dropdown 
                            v-if="order.status !== 'Completado' && order.status !== 'Cancelado'"
                            :modelValue="order.status" 
                            :options="statusOptions" 
                            optionLabel="label" 
                            optionValue="value" 
                            placeholder="Cambiar estado"
                            class="!rounded-xl p-inputtext-sm w-full mb-3 dark:bg-zinc-950 dark:border-zinc-700 shadow-sm"
                            @change="onStatusChange($event, order)"
                        />
                        <span v-else class="text-zinc-400 dark:text-zinc-600 text-sm mb-4 flex justify-center items-center gap-2">
                            <i class="pi pi-lock text-xs"></i> Orden Finalizada
                        </span>
                        
                        <!-- Botones -->
                        <div class="flex gap-2">
                            <Button 
                                icon="pi pi-plus" 
                                label="Progreso"
                                class="!rounded-xl !bg-blue-50 dark:!bg-blue-900/30 !text-blue-700 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 flex-1 font-medium transition-colors" 
                                @click="openProgressModal(order)"
                                :disabled="order.status === 'Completado' || order.status === 'Cancelado'"
                            />
                            <Button 
                                icon="pi pi-check" 
                                label="Entregar"
                                class="!rounded-xl !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-700 dark:!text-emerald-400 hover:!bg-emerald-100 dark:hover:!bg-emerald-900/50 !border-0 flex-1 font-medium transition-colors" 
                                @click="openDeliverModal(order)"
                                :disabled="order.status === 'Completado' || order.status === 'Cancelado' || order.quantity_produced !== order.quantity_requested"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modal para Agregar Progreso -->
        <Dialog 
            v-model:visible="progressModalVisible" 
            modal 
            header="Agregar Progreso" 
            :style="{ width: '100%', maxWidth: '26rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2" v-if="selectedOrder">
                <div class="bg-zinc-50 dark:bg-zinc-950/50 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100 block mb-2 text-lg">{{ selectedOrder.product.name }}</span>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500">Meta: <strong class="text-zinc-700 dark:text-zinc-300">{{ selectedOrder.quantity_requested }}</strong></span>
                        <span class="text-zinc-500">Actual: <strong class="text-zinc-700 dark:text-zinc-300">{{ selectedOrder.quantity_produced }}</strong></span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="progressQty" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad Terminada (Nueva)</label>
                    <InputNumber 
                        id="progressQty" 
                        v-model="progressData.quantity" 
                        mode="decimal" 
                        :min="1" 
                        :max="selectedOrder.quantity_requested - selectedOrder.quantity_produced" 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 text-lg font-medium" 
                    />
                    <small class="text-zinc-500 ml-1">
                        Máximo a agregar: <span class="font-semibold">{{ selectedOrder.quantity_requested - selectedOrder.quantity_produced }}</span>
                    </small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="progressModalVisible = false" class="!px-5 !py-2.5 !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium mt-4" />
                <Button label="Guardar" @click="submitAddProgress" :loading="isSubmitting" class="!px-5 !py-2.5 !rounded-xl !text-[var(--primary-text-color)] font-medium mt-4" />
            </template>
        </Dialog>

        <!-- Modal para Entregar (Venta) -->
        <Dialog 
            v-model:visible="deliverModalVisible" 
            modal 
            header="Registrar Entrega" 
            :style="{ width: '100%', maxWidth: '28rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2" v-if="selectedOrder">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                    <span class="font-semibold text-emerald-800 dark:text-emerald-300 block mb-1">{{ selectedOrder.product.name }}</span>
                    <span class="text-sm text-emerald-600 dark:text-emerald-400">
                        Se entregarán <strong class="text-emerald-700 dark:text-emerald-300 text-base">{{ selectedOrder.quantity_produced }}</strong> unidades terminadas.
                    </span>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="deliveryDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha de Entrega</label>
                    <Calendar 
                        id="deliveryDate" 
                        v-model="deliverData.delivery_date" 
                        dateFormat="dd/mm/yy" 
                        showIcon 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" 
                        :pt="{ dropdownButton: { root: { class: '!rounded-r-xl dark:!bg-zinc-800 dark:!border-zinc-700' } } }"
                    />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="unitPrice" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Precio Unitario de Venta</label>
                    <InputNumber 
                        id="unitPrice" 
                        v-model="deliverData.unit_price" 
                        mode="currency" 
                        currency="MXN" 
                        locale="es-MX" 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 font-medium" 
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="deliverModalVisible = false" class="!px-5 !py-2.5 !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium mt-4" />
                <Button label="Completar" @click="submitDeliverOrder" :loading="isSubmitting" class="!px-5 !py-2.5 !rounded-xl !text-[var(--primary-text-color)] mt-4" />
            </template>
        </Dialog>

    </div>
</template>

<style scoped>
/* Estilos para asegurar que el dropdown no se corte en la tabla */
:deep(.p-datatable-tbody > tr > td) {
    overflow: visible;
}

/* Estilo para la barra de progreso minimalista */
:deep(.custom-progress .p-progressbar) {
    background-color: #f4f4f5;
    border-radius: 9999px;
    overflow: hidden;
}
.dark :deep(.custom-progress .p-progressbar) {
    background-color: #27272a;
}

:deep(.custom-progress .p-progressbar-value) {
    background: #3b82f6; /* blue-500 */
    border-radius: 9999px;
}
</style>

<style>
/* Estilos globales para PrimeVue DataTable 
  Al estar fuera de "scoped", Vue no altera las clases y el navegador lee la ruta exacta.
*/
.apple-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.apple-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.apple-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode 
  Agregamos html.dark para darle un "extra" de especificidad y ganarle a PrimeVue
*/
html.dark .apple-table .p-datatable-thead > tr > th,
.dark .apple-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .apple-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .apple-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>