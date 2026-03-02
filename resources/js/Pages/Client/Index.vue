<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Card from 'primevue/card';

// --- PROPS ---
const props = defineProps({
    clients: {
        type: Array,
        required: true,
    }
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const isPaymentDialogVisible = ref(false);
const selectedClient = ref(null);
const menu = ref();
const selectedClientForMenu = ref(null);

// --- FORMS ---
const paymentForm = useForm({
    client_id: null,
    quote_id: null, 
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});

// --- COMPUTED PROPERTIES ---
const clientsWithBalance = computed(() => {
    return props.clients.map(client => ({
        ...client,
        balance: (client.total_billed || 0) - (client.total_paid || 0)
    }));
});

const quoteOptions = computed(() => {
    if (!selectedClient.value?.quotes) return [];
    
    return selectedClient.value.quotes
        .map(q => ({
            ...q,
            balance: q.final_amount - (q.total_paid || 0)
        }))
        .filter(q => q.balance > 0.01) 
        .map(q => ({
            id: q.id,
            label: `Cot-${q.id} - ${q.title} (Saldo: ${formatCurrency(q.balance)})`
        }));
});

// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedClientForMenu.value) return [];
    const client = selectedClientForMenu.value;
    return [
        { label: 'Agregar Pago', icon: 'pi pi-dollar', command: () => openPaymentDialog(client) },
        { label: 'Ver Detalles', icon: 'pi pi-eye', command: () => router.get(`/clients/${client.id}`) },
        { label: 'Editar Cliente', icon: 'pi pi-pencil', command: () => router.get(`/clients/${client.id}/edit`) },
        { separator: true },
        { label: 'Eliminar Cliente', icon: 'pi pi-trash', command: () => confirmDeleteClient(client) }
    ];
});

const toggleMenu = (event, client) => {
    selectedClientForMenu.value = client;
    menu.value.toggle(event);
};

// --- METHODS ---
const confirmDeleteClient = (client) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar "${client.name}"? Esta acción no se puede desahacer.`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]' ,
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        accept: () => { deleteClient(client); }
    });
};

const deleteClient = (client) => {
    router.delete(`/clients/${client.id}`, {
        preserveScroll: true,
        onSuccess: () => { toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cliente eliminado correctamente', life: 3000 }); },
        onError: () => { toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar al cliente.', life: 3000 }); }
    });
};

const openPaymentDialog = (client) => {
    selectedClient.value = client;
    paymentForm.reset();
    paymentForm.client_id = client.id;
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
    selectedClient.value = null;
};

const submitPayment = () => {
    paymentForm.post(route('client-payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Pago registrado correctamente', life: 3000 });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({ severity: 'error', summary: 'Error', detail: errorMessages || 'No se pudo registrar el pago. Revisa los datos.', life: 3000 });
        }
    });
};

const onRowClick = (event) => { router.get(`/clients/${event.data.id}`); };
const rowClass = () => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors';

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const getStatusSeverity = (status) => (status === 'Cliente' ? 'success' : 'info');
</script>

<template>
    <AppLayout title="Clientes">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-zinc-100 text-[#212121]">Módulo de Clientes</h1>
                        <p class="text-gray-400 dark:text-zinc-400 mt-1">Gestiona la información y finanzas de tus clientes.</p>
                    </div>
                    <Link href="/clients/create">
                        <Button label="Crear Cliente" icon="pi pi-plus" class="!text-[var(--primary-text-color)]" />
                    </Link>
                </header>

                <!-- Vista de Tabla para Escritorio -->
                <div class="hidden md:block bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <DataTable :value="clientsWithBalance" paginator :rows="15" tableStyle="min-width: 50rem;"
                        @row-click="onRowClick" selectionMode="single" dataKey="id" :rowClass="rowClass" class="index-client-table">
                        <template #empty> <div class="p-4 text-center text-gray-500">No se encontraron clientes.</div> </template>

                        <Column field="id" header="ID" sortable style="width: 5%">
                            <template #body="{ data }"><span class="text-gray-500 dark:text-zinc-500">#{{ data.id }}</span></template>
                        </Column>
                        <Column field="name" header="Cliente" sortable>
                            <template #body="{ data }">
                                <div class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.name }}</div>
                                <div class="text-sm text-gray-500 dark:text-zinc-500">{{ data.tax_id }}</div>
                            </template>
                        </Column>
                        <Column field="status" header="Estado" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column field="total_billed" header="Total Facturado" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="text-blue-600 dark:text-blue-400">{{ formatCurrency(data.total_billed) }}</span>
                            </template>
                        </Column>
                        <Column field="total_paid" header="Total Pagado" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.total_paid) }}</span>
                            </template>
                        </Column>
                        <Column field="balance" header="Balance" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="font-bold" :class="[data.balance > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-zinc-400']">
                                    {{ formatCurrency(data.balance) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                            <template #body="{ data }">
                                <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                    aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" class="!text-gray-500 dark:!text-zinc-400 hover:!bg-gray-100 dark:hover:!bg-zinc-800"/>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                <!-- Vista de Tarjetas para Móvil -->
                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card v-for="client in clientsWithBalance" :key="client.id"
                        class="cursor-pointer dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm !rounded-xl" @click="router.get(`/clients/${client.id}`)">
                        <template #title>
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-lg font-bold text-gray-800 dark:text-zinc-100">{{ client.name }}</span>
                                <Tag :value="client.status" :severity="getStatusSeverity(client.status)" />
                            </div>
                        </template>
                        <template #subtitle><span class="text-gray-500 dark:text-zinc-500 text-sm">ID: {{ client.id }} - {{ client.tax_id }}</span></template>
                        <template #content>
                            <ul class="space-y-2 text-sm text-gray-600 dark:text-zinc-400 mt-2">
                                <li class="flex justify-between"><span>Facturado:</span> <span class="text-blue-600 dark:text-blue-400">{{ formatCurrency(client.total_billed) }}</span></li>
                                <li class="flex justify-between"><span>Pagado:</span> <span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(client.total_paid) }}</span></li>
                                <li class="flex justify-between border-t border-gray-100 dark:border-zinc-800 pt-2 mt-2">
                                    <span class="font-bold dark:text-zinc-300">Balance:</span>
                                    <span class="font-bold" :class="[client.balance > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-zinc-300']">
                                        {{ formatCurrency(client.balance) }}
                                    </span>
                                </li>
                            </ul>
                        </template>
                        <template #footer>
                             <div class="flex justify-end">
                                <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, client)"
                                    aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" size="small" outlined />
                            </div>
                        </template>
                    </Card>
                    <div v-if="clients.length === 0" class="text-center text-gray-500 dark:text-zinc-500 col-span-full mt-8">
                        No se encontraron clientes.
                    </div>
                </div>

                <!-- Diálogo Modal para Agregar Pago -->
                <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '25rem' }" 
                    :pt="{ root: { class: 'dark:bg-zinc-900 dark:border-zinc-700' }, header: { class: 'dark:bg-zinc-900 dark:text-zinc-200' }, content: { class: 'dark:bg-zinc-900' }, footer: { class: 'dark:bg-zinc-900' } }">
                    <template #header>
                        <div class="flex flex-col">
                            <h3 class="text-lg font-semibold dark:text-zinc-100">Registrar Pago</h3>
                            <p class="text-sm text-gray-500 dark:text-zinc-500">Para: {{ selectedClient?.name }}</p>
                        </div>
                    </template>
                    <form @submit.prevent="submitPayment">
                        <div class="flex flex-col gap-4 p-4">
                             <div class="flex flex-col gap-2">
                                <label for="quote" class="dark:text-zinc-300">Asociar a Cotización (Opcional)</label>
                                <Dropdown id="quote" v-model="paymentForm.quote_id" :options="quoteOptions"
                                    optionLabel="label" optionValue="id" placeholder="Selecciona una cotización" class="w-full"
                                    :class="{ 'p-invalid': paymentForm.errors.quote_id }" showClear />
                                <small v-if="paymentForm.errors.quote_id" class="p-error">{{ paymentForm.errors.quote_id }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="amount" class="dark:text-zinc-300">Monto del Pago</label>
                                <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN"
                                    locale="es-MX" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                                <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="payment_date" class="dark:text-zinc-300">Fecha del Pago</label>
                                <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd"
                                    :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                                <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="notes" class="dark:text-zinc-300">Notas (Opcional)</label>
                                <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
                            </div>
                        </div>
                    </form>
                    <template #footer>
                        <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" />
                        <Button label="Guardar Pago" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" class="!text-[var(--primary-text-color)]" />
                    </template>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos para asegurar que el autocompletado del navegador no altere el diseño */
.p-inputtext, .p-inputnumber-input {
    width: 100% !important;
}
</style>

<style>
/* Estilos globales para la tabla de INDEX */
.index-client-table .p-datatable-thead > tr > th {
    background-color: #212121 !important;
    color: #d0d0d0 !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.index-client-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.index-client-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode para INDEX (Con el fondo claro que querías) */
html.dark .index-client-table .p-datatable-thead > tr > th,
.dark .index-client-table .p-datatable-thead > tr > th {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .index-client-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .index-client-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>