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
import ConfirmDialog from 'primevue/confirmdialog'; 
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm'; 
import ProgressBar from 'primevue/progressbar';
import axios from 'axios';

const orders = ref([]);
const loading = ref(true);
const toast = useToast();
const confirm = useConfirm();

// --- Estado para Modales ---
const progressModalVisible = ref(false);
const deliverModalVisible = ref(false);
const historyModalVisible = ref(false); 
const paymentModalVisible = ref(false); // Novedad: Modal para el pago

const selectedOrder = ref(null);
const selectedDelivery = ref(null); // Entrega seleccionada para pagar

const progressData = ref({ quantity: null });
const deliverData = ref({ quantity: null, delivery_date: null, unit_price: null });
const paymentData = ref({ amount_paid: null, payment_date: null }); // Novedad

const deliveryHistory = ref([]);
const loadingHistory = ref(false);
const isSubmitting = ref(false);

// --- Opciones para el Dropdown de Estado ---
const statusOptions = ref([
    { label: 'En Progreso', value: 'En Progreso' },
    { label: 'Cancelado', value: 'Cancelado' },
]);

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' }, 
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
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

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        // Ajuste para evitar desfaces en visualización local
        const utcDate = new Date(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate());
        
        return utcDate.toLocaleDateString('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC'
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

// Determinar el estado visual del pago en el historial
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
                toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se puede eliminar la orden (puede que tenga progreso o entregas registradas).', life: 4000 });
            }
        }
    });
};

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

        // Actualizar la orden y mantener los sum de pagos anteriores
        const index = orders.value.findIndex(o => o.id === selectedOrder.value.id);
        if (index !== -1) {
            orders.value[index] = { ...orders.value[index], ...response.data };
        }
        
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Progreso agregado correctamente.', life: 3000 });
        progressModalVisible.value = false;
    } catch (error) {
        const detail = error.response?.data?.message || 'Error desconocido al agregar progreso.';
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};

const openDeliverModal = (order) => {
    const availableToDeliver = order.quantity_produced - (order.quantity_delivered || 0);
    
    if (availableToDeliver <= 0) {
        toast.add({ severity: 'warn', summary: 'Sin producción disponible', detail: 'No hay cantidad producida pendiente de entregar.', life: 4000 });
        return;
    }
    
    selectedOrder.value = order;
    deliverData.value = { 
        quantity: availableToDeliver,
        delivery_date: new Date(), 
        unit_price: null 
    };
    deliverModalVisible.value = true;
};

const submitDeliverOrder = async () => {
    const { quantity, delivery_date, unit_price } = deliverData.value;
    const availableToDeliver = selectedOrder.value.quantity_produced - (selectedOrder.value.quantity_delivered || 0);

    if (!selectedOrder.value || !delivery_date || unit_price == null || unit_price < 0 || !quantity || quantity <= 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Complete todos los campos correctamente.', life: 3000 });
        return;
    }

    if (quantity > availableToDeliver) {
        toast.add({ severity: 'error', summary: 'Cantidad excedida', detail: `Solo puedes entregar hasta ${availableToDeliver} unidades.`, life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/${selectedOrder.value.id}/deliver`;
        await axios.post(url, deliverData.value);

        // Recargamos todo para recalcular total_price_sum con la nueva entrega
        await fetchOrders(); 

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Entrega registrada correctamente.', life: 3000 });
        deliverModalVisible.value = false;
    } catch (error) {
        const detail = error.response?.data?.message || 'Error desconocido al entregar la orden.';
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
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

// --- Novedad: Modales y Lógica de Pago por Entrega ---
const openPaymentModal = (delivery) => {
    selectedDelivery.value = delivery;
    paymentData.value = {
        amount_paid: delivery.amount_paid !== null ? parseFloat(delivery.amount_paid) : 0,
        payment_date: delivery.payment_date ? new Date(delivery.payment_date) : new Date()
    };
    paymentModalVisible.value = true;
};

const setFullPayment = () => {
    if (selectedDelivery.value) {
        paymentData.value.amount_paid = parseFloat(selectedDelivery.value.total_price);
    }
};

const submitPayment = async () => {
    if (paymentData.value.amount_paid === null || paymentData.value.amount_paid < 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Ingrese un monto válido.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        const url = `/tpsp/production-orders/deliveries/${selectedDelivery.value.id}/pay`;
        const response = await axios.put(url, paymentData.value);
        
        // Actualizamos la fila del historial sin cerrar el modal principal
        const index = deliveryHistory.value.findIndex(d => d.id === selectedDelivery.value.id);
        if (index !== -1) {
            deliveryHistory.value[index] = response.data;
        }
        
        toast.add({ severity: 'success', summary: 'Pago registrado', detail: 'Se actualizó el pago de la entrega correctamente.', life: 3000 });
        paymentModalVisible.value = false;
        
        // Recargamos las ordenes principales en el fondo para que "Cerrada / Pagada" se aplique
        fetchOrders();

    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo registrar el pago.', life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};

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
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 4000 });
    }
};

onMounted(fetchOrders);

</script>

<template>
    <div class="pb-20 md:pb-0">
        <Toast />
        <ConfirmDialog :pt="{ root: { class: 'dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border-0 mx-3 sm:mx-0' }, header: { class: 'bg-white dark:bg-zinc-900 pb-0' }, content: { class: 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300' }, footer: { class: 'bg-white dark:bg-zinc-900 pt-0 flex gap-2 justify-end' } }" />
        
        <!-- Vista de Tabla (Escritorio) -->
        <div class="hidden md:block bg-white dark:bg-zinc-900 mt-4 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-800 p-2 sm:p-5 overflow-hidden">
            <DataTable :value="orders" :loading="loading" responsiveLayout="scroll" paginator :rows="10" class="apple-table" :rowsPerPageOptions="[10, 20, 50]">
                
                <Column field="order_number" header="Folio" :sortable="true" style="min-width: 100px;">
                     <template #body="{ data }"><span class="text-zinc-500 dark:text-zinc-400 font-medium">#{{ data.order_number }}</span></template>
                </Column>
                
                <Column field="product.name" header="Producto" style="min-width: 150px;">
                     <template #body="{ data }"><span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ data.product.name }}</span></template>
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
                <Column header="Acciones" bodyStyle="text-align: right; overflow: visible;" style="min-width: 160px;">
                    <template #body="slotProps">
                        <div class="flex gap-1.5 justify-end">
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
                            <div class="font-bold text-zinc-900 dark:text-zinc-100 text-lg leading-tight">{{ order.product.name }}</div>
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
                            <Button icon="pi pi-plus" class="!rounded-xl !bg-blue-50 dark:!bg-blue-900/30 !text-blue-700 dark:!text-blue-400 hover:!bg-blue-100 !border-0 flex-1 h-10" @click="openProgressModal(order)" :disabled="order.status === 'Completado' || order.status === 'Cancelado'" />
                            <Button icon="pi pi-truck" class="!rounded-xl !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-700 dark:!text-emerald-400 hover:!bg-emerald-100 !border-0 flex-1 h-10" @click="openDeliverModal(order)" :disabled="order.status === 'Completado' || order.status === 'Cancelado' || (order.quantity_produced - (order.quantity_delivered || 0)) <= 0" />
                            <Button icon="pi pi-list" class="!rounded-xl !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-700 dark:!text-indigo-400 hover:!bg-indigo-100 !border-0 flex-1 h-10" @click="openHistoryModal(order)" :disabled="!order.quantity_delivered" />
                            <Button icon="pi pi-trash" class="!rounded-xl !bg-red-50 dark:!bg-red-900/30 !text-red-500 !border-0 w-10 h-10 shrink-0" @click="confirmDeleteOrder(order)" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL: AGREGAR PROGRESO (Producción) -->
        <Dialog 
            v-model:visible="progressModalVisible" 
            modal 
            header="Agregar Producción" 
            :style="{ width: '100%', maxWidth: '26rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2" v-if="selectedOrder">
                <div class="bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                    <span class="font-bold text-blue-900 dark:text-blue-100 block mb-2 text-lg">{{ selectedOrder.product.name }}</span>
                    <div class="flex justify-between text-sm">
                        <span class="text-blue-600/80">Meta: <strong class="text-blue-800 dark:text-blue-200">{{ selectedOrder.quantity_requested }}</strong></span>
                        <span class="text-blue-600/80">Producido: <strong class="text-blue-800 dark:text-blue-200">{{ selectedOrder.quantity_produced }}</strong></span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="progressQty" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad Nueva a Reportar</label>
                    <InputNumber 
                        id="progressQty" 
                        v-model="progressData.quantity" 
                        mode="decimal" :min="1" :max="selectedOrder.quantity_requested - selectedOrder.quantity_produced" 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 text-lg font-medium text-center" 
                    />
                    <small class="text-zinc-500 ml-1 text-center mt-1">
                        Máximo que falta producir: <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ selectedOrder.quantity_requested - selectedOrder.quantity_produced }}</span>
                    </small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="progressModalVisible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
                <Button label="Guardar Producción" @click="submitAddProgress" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium" />
            </template>
        </Dialog>

        <!-- MODAL: REGISTRAR ENTREGA -->
        <Dialog 
            v-model:visible="deliverModalVisible" 
            modal 
            header="Registrar Entrega (Venta)" 
            :style="{ width: '100%', maxWidth: '28rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2" v-if="selectedOrder">
                <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                    <span class="font-bold text-emerald-800 dark:text-emerald-300 block mb-2">{{ selectedOrder.product.name }}</span>
                    <div class="grid grid-cols-2 gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                        <div class="flex flex-col"><span class="opacity-70">Total Producido:</span><span class="font-bold text-sm">{{ selectedOrder.quantity_produced }}</span></div>
                        <div class="flex flex-col"><span class="opacity-70">Ya Entregado:</span><span class="font-bold text-sm">{{ selectedOrder.quantity_delivered || 0 }}</span></div>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-end">
                        <label for="deliverQty" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Entregar Hoy</label>
                        <span class="text-xs text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">Disp: {{ selectedOrder.quantity_produced - (selectedOrder.quantity_delivered || 0) }}</span>
                    </div>
                    <InputNumber 
                        id="deliverQty" 
                        v-model="deliverData.quantity" 
                        mode="decimal" :min="1" :max="selectedOrder.quantity_produced - (selectedOrder.quantity_delivered || 0)"
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-emerald-200 dark:!border-emerald-800/50 dark:!bg-zinc-950 dark:!text-emerald-600 dark:!text-emerald-400 shadow-sm p-3 text-lg font-bold text-center" 
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="deliveryDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha</label>
                        <Calendar 
                            id="deliveryDate" 
                            v-model="deliverData.delivery_date" 
                            dateFormat="dd/mm/yy" 
                            class="w-full"
                            inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" 
                        />
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <label for="unitPrice" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Precio Unit.</label>
                        <InputNumber 
                            id="unitPrice" 
                            v-model="deliverData.unit_price" 
                            mode="currency" currency="MXN" locale="es-MX" 
                            class="w-full"
                            inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 font-medium" 
                        />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="deliverModalVisible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
                <Button label="Registrar Entrega" @click="submitDeliverOrder" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)]" />
            </template>
        </Dialog>

        <!-- MODAL: HISTORIAL DE ENTREGAS Y PAGOS -->
        <Dialog 
            v-model:visible="historyModalVisible" 
            modal 
            header="Historial de Entregas y Pagos" 
            :style="{ width: '100%', maxWidth: '55rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-4 mt-2" v-if="selectedOrder">
                <div class="flex items-center gap-3 mb-2">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                        <i class="pi pi-list text-indigo-500 dark:text-indigo-400 text-xl"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg m-0">{{ selectedOrder.product.name }}</h4>
                        <span class="text-sm text-zinc-500">Orden #{{ selectedOrder.order_number }}</span>
                    </div>
                </div>

                <div class="border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <DataTable :value="deliveryHistory" :loading="loadingHistory" class="zinc-table" responsiveLayout="scroll">
                        <Column field="created_at" header="Fecha Entrega" style="min-width: 100px;">
                            <template #body="{ data }">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ formatDate(data.created_at) }}</span>
                            </template>
                        </Column>
                        <Column field="quantity" header="Unid." style="min-width: 60px;">
                            <template #body="{ data }">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">+{{ Math.abs(data.quantity) }}</span>
                            </template>
                        </Column>
                        
                        <!-- Sección Total Costo -->
                        <Column header="Total Venta" style="min-width: 100px;">
                            <template #body="{ data }">
                                <span class="font-bold text-zinc-800 dark:text-zinc-200 block">{{ formatCurrency(data.total_price) }}</span>
                                <span class="text-[0.65rem] text-zinc-500">{{ formatCurrency(data.unit_price) }} c/u</span>
                            </template>
                        </Column>

                        <!-- Novedad: Sección Pagos -->
                        <Column header="Abonado" style="min-width: 100px;">
                            <template #body="{ data }">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.amount_paid || 0) }}</span>
                            </template>
                        </Column>
                        <Column header="Est. Pago" style="min-width: 90px;">
                            <template #body="{ data }">
                                <Tag :value="getPaymentStatus(data).label" :severity="getPaymentStatus(data).severity" class="!rounded-md text-[0.65rem] font-bold" />
                            </template>
                        </Column>
                        <Column field="payment_date" header="Fecha Pago" style="min-width: 100px;">
                            <template #body="{ data }">
                                <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ data.payment_date ? formatDate(data.payment_date) : '-' }}</span>
                            </template>
                        </Column>
                        
                        <!-- Novedad: Botón para pagar -->
                        <Column header="Acciones" bodyStyle="text-align: right;" style="min-width: 60px;">
                            <template #body="{ data }">
                                <Button 
                                    icon="pi pi-dollar" 
                                    class="!rounded-xl !w-8 !h-8 !p-0 !bg-amber-50 dark:!bg-amber-900/30 !text-amber-600 dark:!text-amber-400 hover:!bg-amber-100 dark:hover:!bg-amber-900/50 !border-0" 
                                    v-tooltip.top="'Registrar Pago'"
                                    @click="openPaymentModal(data)"
                                />
                            </template>
                        </Column>

                        <template #empty>
                            <div class="text-center p-6 text-zinc-500 text-sm">No hay entregas registradas aún.</div>
                        </template>
                    </DataTable>
                </div>
            </div>
            
            <template #footer>
                <Button label="Cerrar Historial" @click="historyModalVisible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 hover:!bg-zinc-200 dark:hover:!bg-zinc-700 !text-zinc-800 dark:!text-zinc-200 !border-0 font-medium transition-colors" />
            </template>
        </Dialog>

        <!-- NOVEDAD - MODAL: REGISTRAR PAGO POR ENTREGA -->
        <Dialog 
            v-model:visible="paymentModalVisible" 
            modal 
            header="Registrar Pago" 
            :style="{ width: '100%', maxWidth: '24rem', margin: '1rem' }"
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-4 mt-2" v-if="selectedDelivery">
                <div class="bg-amber-50 dark:bg-amber-900/10 p-4 rounded-2xl border border-amber-100 dark:border-amber-800/30 text-sm">
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-amber-700 dark:text-amber-400 font-medium">Total de la Entrega:</span>
                        <span class="font-bold text-amber-900 dark:text-amber-200">{{ formatCurrency(selectedDelivery.total_price) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <div class="flex justify-between items-end">
                        <label for="amountPaid" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Monto Pagado / Abonado</label>
                        <button @click="setFullPayment" class="text-[0.65rem] font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">Liquidar todo</button>
                    </div>
                    <InputNumber 
                        id="amountPaid" 
                        v-model="paymentData.amount_paid" 
                        mode="currency" currency="MXN" locale="es-MX" 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 font-bold text-center text-lg" 
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="paymentDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha del Pago</label>
                    <Calendar 
                        id="paymentDate" 
                        v-model="paymentData.payment_date" 
                        dateFormat="dd/mm/yy" 
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" 
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="paymentModalVisible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
                <Button label="Guardar Pago" @click="submitPayment" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-[var(--primary-color)] !text-[var(--primary-text-color)]" />
            </template>
        </Dialog>

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