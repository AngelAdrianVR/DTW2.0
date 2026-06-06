<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: Boolean,
    order: { type: Object, default: null },
    deliveries: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible', 'open-payment']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
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

const getPaymentStatus = (delivery) => {
    const total = parseFloat(delivery.total_price) || 0;
    const paid = parseFloat(delivery.amount_paid) || 0;
    if (total === 0) return { label: 'Sin Costo', severity: 'info' };
    if (paid >= total) return { label: 'Pagado', severity: 'success' };
    if (paid > 0) return { label: 'Parcial', severity: 'warning' };
    return { label: 'Pendiente', severity: 'danger' };
};

</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Historial de Entregas y Pagos"
        :style="{ width: '100%', maxWidth: '55rem', margin: '1rem' }"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-4 mt-2" v-if="order">
            <div class="flex items-center gap-3 mb-2">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                    <i class="pi pi-list text-indigo-500 dark:text-indigo-400 text-xl"></i>
                </div>
                <div>
                    <h4 class="font-bold text-zinc-900 dark:text-zinc-100 text-lg m-0">{{ order.product.name }}</h4>
                    <span class="text-sm text-zinc-500">Orden #{{ order.order_number }}</span>
                </div>
            </div>

            <div class="border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                <DataTable :value="deliveries" :loading="loading" class="zinc-table" responsiveLayout="scroll">
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
                    <Column header="Total Venta" style="min-width: 100px;">
                        <template #body="{ data }">
                            <span class="font-bold text-zinc-800 dark:text-zinc-200 block">{{ formatCurrency(data.total_price) }}</span>
                            <span class="text-[0.65rem] text-zinc-500">{{ formatCurrency(data.unit_price) }} c/u</span>
                        </template>
                    </Column>
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
                    <Column header="Acciones" bodyStyle="text-align: right;" style="min-width: 60px;">
                        <template #body="{ data }">
                            <Button
                                icon="pi pi-dollar"
                                class="!rounded-xl !w-8 !h-8 !p-0 !bg-amber-50 dark:!bg-amber-900/30 !text-amber-600 dark:!text-amber-400 hover:!bg-amber-100 dark:hover:!bg-amber-900/50 !border-0"
                                v-tooltip.top="'Registrar Pago'"
                                @click="emit('open-payment', data)"
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
            <Button label="Cerrar Historial" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 hover:!bg-zinc-200 dark:hover:!bg-zinc-700 !text-zinc-800 dark:!text-zinc-200 !border-0 font-medium transition-colors" />
        </template>
    </Dialog>
</template>
