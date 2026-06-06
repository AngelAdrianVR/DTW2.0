<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import ProgressBar from 'primevue/progressbar';
import axios from 'axios';

// --- Componentes modulares extraídos ---
import ProductionOrderModal from '@/Components/TPSP/ProductionOrderModal.vue';
import ProductionProgressModal from '@/Components/TPSP/ProductionProgressModal.vue';
import ProductionDeliveryModal from '@/Components/TPSP/ProductionDeliveryModal.vue';
import ProductionHistoryModal from '@/Components/TPSP/ProductionHistoryModal.vue';
import ProductionPaymentModal from '@/Components/TPSP/ProductionPaymentModal.vue';

const orders = ref([]);
const loading = ref(true);
const toast = useToast();
const confirm = useConfirm();

// --- Estado para Modales ---
const orderModalVisible = ref(false);
const progressModalVisible = ref(false);
const deliverModalVisible = ref(false);
const historyModalVisible = ref(false);
const paymentModalVisible = ref(false);

const selectedOrder = ref(null);
const selectedDelivery = ref(null);
const editingOrderData = ref(null);

const deliveryHistory = ref([]);
const loadingHistory = ref(false);

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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        const utcDate = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
        return utcDate.toLocaleDateString('es-MX', {
            day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC'
        }).replace('.', '').replace(' de ', '-');
    } catch (e) {
        return dateString;
    }
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const getProductionProgress = (produced, requested) => {
    if (!requested || requested === 0) return 0;
    return parseFloat(((produced / requested) * 100).toFixed(2));
};

const getDeliveryProgress = (delivered, requested) => {
    if (!requested || requested === 0) return 0;
    return parseFloat((((delivered || 0) / requested) * 100).toFixed(2));
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Pendiente': return 'warning';
        case 'En Progreso': return 'info';
        case 'Completado': return 'success';
        case 'Cancelado': return 'danger';
        default: return 'secondary';
    }
};

const getPaymentStatus = (delivery) => {
    const total = parseFloat(delivery.total_price) || 0;
    const paid = parseFloat(delivery.amount_paid) || 0;
    if (total === 0) return { label: 'Sin Costo', severity: 'info' };
    if (paid >= total) return { label: 'Pagado', severity: 'success' };
    if (paid > 0) return { label: 'Parcial', severity: 'warning' };
    return { label: 'Pendiente', severity: 'danger' };
};

const confirmDeleteOrder = (order) => {
    confirm.require({
        message: `¿Estás seguro de que deseas eliminar la orden #${order.order_number}? Esto no se puede deshacer.`,
        header: 'Eliminar Orden de Producción',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        accept: async () => {
            try {
                await axios.delete(`/tpsp/production-orders/${order.id}`);
                orders.value = orders.value.filter(o => o.id !== order.id);
                toast.add({ severity: 'success', summary: 'Eliminada', detail: 'Orden eliminada con éxito.', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se puede eliminar la orden.', life: 4000 });
            }
        }
    });
};

// --- Apertura de modales ---
const openNewOrderModal = () => {
    editingOrderData.value = null;
    orderModalVisible.value = true;
};

const openEditOrderModal = (order) => {
    editingOrderData.value = { ...order };
    orderModalVisible.value = true;
};

const openProgressModal = (order) => {
    selectedOrder.value = order;
    progressModalVisible.value = true;
};

const openDeliverModal = (order) => {
    const availableToDeliver = order.quantity_produced - (order.quantity_delivered || 0);
    if (availableToDeliver <= 0) {
        toast.add({ severity: 'warn', summary: 'Sin producción disponible', detail: 'No hay cantidad producida pendiente de entregar.', life: 4000 });
        return;
    }
    selectedOrder.value = order;
    deliverModalVisible.value = true;
};

const openHistoryModal = async (order) => {
    selectedOrder.value = order;
    historyModalVisible.value = true;
    loadingHistory.value = true;
    try {
        const response = await axios.get(`/tpsp/production-orders/${order.id}/deliveries`);
        deliveryHistory.value = response.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el historial de entregas.', life: 3000 });
    } finally {
        loadingHistory.value = false;
    }
};

const openPaymentModal = (delivery) => {
    selectedDelivery.value = delivery;
    paymentModalVisible.value = true;
};

// --- Handlers de eventos emitidos por los modales ---
const onOrderSaved = () => {
    fetchOrders();
};

const onProgressSaved = () => {
    fetchOrders();
};

const onDeliverySaved = () => {
    fetchOrders();
};

const onPaymentSaved = () => {
    if (selectedOrder.value) {
        openHistoryModal(selectedOrder.value);
    }
    fetchOrders();
};

// --- Cambio de estado ---
const onStatusChange = async (event, order) => {
    const newStatus = event.value;
    if (!newStatus || newStatus === order.status) return;
    const oldStatus = order.status;
    order.status = newStatus;
    try {
        await axios.patch(`/tpsp/production-orders/${order.id}/status`, { status: newStatus });
        toast.add({ severity: 'success', summary: 'Actualizado', detail: `Estado cambiado a ${newStatus}`, life: 3000 });
    } catch (error) {
        order.status = oldStatus;
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 4000 });
    }
};

onMounted(fetchOrders);
</script>

<template>
    <div class="pb-20 md:pb-0">
        <Toast />
        <ConfirmDialog :pt="{ root: { class: 'dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border-0 mx-3 sm:mx-0' }, header: { class: 'bg-white dark:bg-zinc-900 pb-0' }, content: { class: 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300' }, footer: { class: 'bg-white dark:bg-zinc-900 pt-0 flex gap-2 justify-end' } }" />

        <!-- Botón Nueva Orden (inline en la pestaña) -->
        <div class="flex justify-end mb-4">
            <Button
                label="Nueva Orden"
                icon="pi pi-plus"
                @click="openNewOrderModal"
                class="!rounded-xl !text-[var(--primary-text-color)]"
            />
        </div>

        <!-- Vista de Tabla (Escritorio) -->
        <div class="hidden md:block bg-white dark:bg-zinc-900 mt-2 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-800 p-2 sm:p-5 overflow-hidden">
            <DataTable :value="orders" :loading="loading" responsiveLayout="scroll" paginator :rows="10" class="apple-table" :rowsPerPageOptions="[10, 20, 50]">

                <Column field="order_number" header="Folio" :sortable="true" style="min-width: 100px;">
                     <template #body="{ data }"><span class="text-zinc-500 dark:text-zinc-400 font-medium">#{{ data.order_number }}</span></template>
                </Column>

                <Column field="product.name" header="Producto" style="min-width: 150px;">
                     <template #body="{ data }"><span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ data.product?.name }}</span></template>
                </Column>

                <Column header="Creado" :sortable="true" field="created_at" style="min-width: 110px;">
                    <template #body="slotProps">
                        <span class="text-zinc-600 dark:text-zinc-400">{{ formatDate(slotProps.data.created_at) }}</span>
                    </template>
                </Column>

                <Column header="Meta (F. Entrega)" :sortable="true" field="due_date" style="min-width: 130px;">
                    <template #body="slotProps">
                        <div class="flex flex-col">
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ slotProps.data.quantity_requested }} unid.</span>
                            <span class="text-xs text-zinc-500">{{ formatDate(slotProps.data.due_date) }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Avance" style="min-width: 180px;">
                    <template #body="slotProps">
                        <div class="flex flex-col gap-2 w-full">
                            <div class="flex items-center gap-2">
                                <span class="text-[0.65rem] uppercase tracking-wider font-bold text-blue-600 dark:text-blue-400 w-12">Prod.</span>
                                <ProgressBar :value="getProductionProgress(slotProps.data.quantity_produced, slotProps.data.quantity_requested)" class="custom-progress flex-1 h-1.5" :showValue="false" />
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 w-8 text-right">{{ slotProps.data.quantity_produced }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[0.65rem] uppercase tracking-wider font-bold text-emerald-600 dark:text-emerald-400 w-12">Entr.</span>
                                <ProgressBar :value="getDeliveryProgress(slotProps.data.quantity_delivered, slotProps.data.quantity_requested)" class="custom-progress delivery flex-1 h-1.5" :showValue="false" />
                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300 w-8 text-right">{{ slotProps.data.quantity_delivered || 0 }}</span>
                            </div>
                        </div>
                    </template>
                </Column>

                <Column field="status" header="Estado" style="min-width: 130px;">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="!rounded-md text-xs font-bold tracking-wide" />
                    </template>
                </Column>

                <Column header="Cambiar Estado" style="min-width: 150px;">
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
                        <span v-else class="text-zinc-400 dark:text-zinc-500 text-sm flex items-center gap-1.5 font-medium">
                            <i :class="slotProps.data.status === 'Cancelado' ? 'pi pi-times-circle' : 'pi pi-lock'" class="text-[0.7rem]"></i>
                            <template v-if="slotProps.data.status === 'Cancelado'">Cancelada</template>
                            <template v-else-if="slotProps.data.status === 'Completado'">
                                {{ (slotProps.data.total_price_sum > 0 && Number(slotProps.data.amount_paid_sum) >= Number(slotProps.data.total_price_sum)) ? 'Cerrada / Pagada' : 'Cerrada' }}
                            </template>
                        </span>
                    </template>
                </Column>

                <!-- Botones de Acción -->
                <Column header="Acciones" bodyStyle="text-align: right; overflow: visible;" style="min-width: 200px;">
                    <template #body="slotProps">
                        <div class="flex gap-1.5 justify-end">
                            <Button
                                icon="pi pi-pencil"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-zinc-50 dark:!bg-zinc-800 !text-zinc-600 dark:!text-zinc-400 hover:!bg-zinc-100 dark:hover:!bg-zinc-700 !border-0 transition-colors"
                                v-tooltip.top="'Editar Orden'"
                                @click="openEditOrderModal(slotProps.data)"
                            />
                            <Button
                                icon="pi pi-plus"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-blue-50 dark:!bg-blue-900/30 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 transition-colors"
                                v-tooltip.top="'Agregar Producción'"
                                @click="openProgressModal(slotProps.data)"
                                :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado'"
                            />
                            <Button
                                icon="pi pi-truck"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-600 dark:!text-emerald-400 hover:!bg-emerald-100 dark:hover:!bg-emerald-900/50 !border-0 transition-colors"
                                v-tooltip.top="'Registrar Entrega'"
                                @click="openDeliverModal(slotProps.data)"
                                :disabled="slotProps.data.status === 'Completado' || slotProps.data.status === 'Cancelado' || (slotProps.data.quantity_produced - (slotProps.data.quantity_delivered || 0)) <= 0"
                            />
                            <Button
                                icon="pi pi-list"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-600 dark:!text-indigo-400 hover:!bg-indigo-100 dark:hover:!bg-indigo-900/50 !border-0 transition-colors"
                                v-tooltip.top="'Historial Entregas / Pagos'"
                                @click="openHistoryModal(slotProps.data)"
                                :disabled="!slotProps.data.quantity_delivered"
                            />
                            <Button
                                icon="pi pi-trash"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-red-50 dark:!bg-red-900/30 !text-red-500 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 transition-colors"
                                v-tooltip.top="'Eliminar Orden'"
                                @click="confirmDeleteOrder(slotProps.data)"
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
            <div v-if="loading" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-3">
                <i class="pi pi-spin pi-spinner text-3xl"></i>
                <p>Cargando órdenes...</p>
            </div>
            <div v-else-if="orders.length === 0" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-2">
                <i class="pi pi-inbox text-3xl"></i>
                <p>No se encontraron órdenes.</p>
            </div>
            <div v-else class="flex flex-col gap-4">
                <div v-for="order in orders" :key="order.id" class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl p-4 shadow-sm">

                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <span class="text-[0.65rem] font-bold tracking-wider text-zinc-400 uppercase mb-0.5 block">Folio #{{ order.order_number }}</span>
                            <div class="font-bold text-zinc-900 dark:text-zinc-100 text-lg leading-tight">{{ order.product?.name }}</div>
                        </div>
                        <Tag :value="order.status" :severity="getStatusSeverity(order.status)" class="!rounded-md text-[0.65rem] font-bold tracking-wide" />
                    </div>

                    <div class="mb-4 bg-zinc-50 dark:bg-zinc-950 p-3 rounded-xl border border-zinc-100 dark:border-zinc-800/50">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-semibold text-zinc-500">Solicitado:</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ order.quantity_requested }} u.</span>
                        </div>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="text-[0.65rem] font-bold text-blue-600 w-10">PROD</span>
                            <ProgressBar :value="getProductionProgress(order.quantity_produced, order.quantity_requested)" class="custom-progress flex-1 h-1.5" :showValue="false" />
                            <span class="text-xs font-bold w-6 text-right">{{ order.quantity_produced }}</span>
                        </div>
                        <div class="flex items-center gap-2 mt-1.5">
                            <span class="text-[0.65rem] font-bold text-emerald-600 w-10">ENTR</span>
                            <ProgressBar :value="getDeliveryProgress(order.quantity_delivered, order.quantity_requested)" class="custom-progress delivery flex-1 h-1.5" :showValue="false" />
                            <span class="text-xs font-bold w-6 text-right">{{ order.quantity_delivered || 0 }}</span>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-4 text-xs">
                        <div class="flex-1 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 rounded-lg border border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400 block mb-0.5 font-medium">F. Creado</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex-1 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 rounded-lg border border-zinc-100 dark:border-zinc-800">
                            <span class="text-zinc-400 block mb-0.5 font-medium">F. Entrega</span>
                            <span class="font-bold text-zinc-700 dark:text-zinc-300">{{ formatDate(order.due_date) }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80">
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
                        <div v-else class="mb-3 px-3 py-2 bg-zinc-50 dark:bg-zinc-900/50 rounded-xl border border-zinc-100 dark:border-zinc-800 text-sm font-medium text-zinc-500 flex items-center justify-center gap-2">
                            <i :class="order.status === 'Cancelado' ? 'pi pi-times-circle' : 'pi pi-lock'"></i>
                            <span v-if="order.status === 'Cancelado'">Cancelada</span>
                            <span v-else-if="order.status === 'Completado'">
                                {{ (order.total_price_sum > 0 && Number(order.amount_paid_sum) >= Number(order.total_price_sum)) ? 'Cerrada / Pagada' : 'Cerrada' }}
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <Button icon="pi pi-pencil" class="!rounded-xl !bg-zinc-50 dark:!bg-zinc-800 !text-zinc-600 dark:!text-zinc-400 !border-0 flex-1 h-10" @click="openEditOrderModal(order)" />
                            <Button icon="pi pi-plus" class="!rounded-xl !bg-blue-50 dark:!bg-blue-900/30 !text-blue-700 dark:!text-blue-400 hover:!bg-blue-100 !border-0 flex-1 h-10" @click="openProgressModal(order)" :disabled="order.status === 'Completado' || order.status === 'Cancelado'" />
                            <Button icon="pi pi-truck" class="!rounded-xl !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-700 dark:!text-emerald-400 hover:!bg-emerald-100 !border-0 flex-1 h-10" @click="openDeliverModal(order)" :disabled="order.status === 'Completado' || order.status === 'Cancelado' || (order.quantity_produced - (order.quantity_delivered || 0)) <= 0" />
                            <Button icon="pi pi-list" class="!rounded-xl !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-700 dark:!text-indigo-400 hover:!bg-indigo-100 !border-0 flex-1 h-10" @click="openHistoryModal(order)" :disabled="!order.quantity_delivered" />
                            <Button icon="pi pi-trash" class="!rounded-xl !bg-red-50 dark:!bg-red-900/30 !text-red-500 !border-0 w-10 h-10 shrink-0" @click="confirmDeleteOrder(order)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODALES EXTRAÍDOS ==================== -->

        <ProductionOrderModal
            v-model:visible="orderModalVisible"
            :orderData="editingOrderData"
            @saved="onOrderSaved"
        />

        <ProductionProgressModal
            v-model:visible="progressModalVisible"
            :order="selectedOrder"
            @saved="onProgressSaved"
        />

        <ProductionDeliveryModal
            v-model:visible="deliverModalVisible"
            :order="selectedOrder"
            @saved="onDeliverySaved"
        />

        <ProductionHistoryModal
            v-model:visible="historyModalVisible"
            :order="selectedOrder"
            :deliveries="deliveryHistory"
            :loading="loadingHistory"
            @open-payment="openPaymentModal"
        />

        <ProductionPaymentModal
            v-model:visible="paymentModalVisible"
            :delivery="selectedDelivery"
            @saved="onPaymentSaved"
        />
    </div>
</template>

<style scoped>
:deep(.p-datatable-tbody > tr > td) {
    overflow: visible;
}

:deep(.custom-progress .p-progressbar) {
    background-color: #f4f4f5;
    border-radius: 9999px;
    overflow: hidden;
}
.dark :deep(.custom-progress .p-progressbar) {
    background-color: #27272a;
}
:deep(.custom-progress .p-progressbar-value) {
    background: #3b82f6;
    border-radius: 9999px;
}
:deep(.custom-progress.delivery .p-progressbar-value) {
    background: #10b981;
}
</style>

<style>
.apple-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7 !important;
    font-size: 0.8rem;
}
.apple-table .p-datatable-tbody > tr {
    background-color: transparent !important;
}
.apple-table .p-datatable-tbody > tr:not(:last-child) > td {
    border-bottom: 1px solid #f4f4f5 !important;
}
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

.zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #71717a !important;
    font-size: 0.75rem;
    text-transform: uppercase;
    border-bottom: 1px solid #e4e4e7 !important;
}
.zinc-table .p-datatable-tbody > tr:not(:last-child) > td {
    border-bottom: 1px solid #f4f4f5 !important;
}
.dark .zinc-table .p-datatable-thead > tr > th { border-bottom: 1px solid #27272a !important; color: #a1a1aa !important; }
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td { border-bottom: 1px solid #27272a !important; }
</style>
