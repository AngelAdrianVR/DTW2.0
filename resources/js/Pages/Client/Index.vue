<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';

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
const menu = ref(); // Referencia para el componente de menú
const selectedClientForMenu = ref(null); // Cliente seleccionado para las acciones del menú

// --- FORMS ---
const paymentForm = useForm({
    client_id: null,
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

// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedClientForMenu.value) return [];
    const client = selectedClientForMenu.value;
    return [
        {
            label: 'Agregar Pago',
            icon: 'pi pi-dollar',
            command: () => openPaymentDialog(client)
        },
        {
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(`/clients/${client.id}`)
        },
        {
            label: 'Editar Cliente',
            icon: 'pi pi-pencil',
            command: () => router.get(`/clients/${client.id}/edit`)
        },
        {
            separator: true
        },
        {
            label: 'Eliminar Cliente',
            icon: 'pi pi-trash',
            command: () => confirmDeleteClient(client)
        }
    ];
});

/**
 * Muestra/oculta el menú de acciones para un cliente específico.
 * @param {object} event - El evento de clic.
 * @param {object} client - El objeto del cliente.
 */
const toggleMenu = (event, client) => {
    selectedClientForMenu.value = client;
    menu.value.toggle(event);
};

// --- METHODS ---

/**
 * Muestra un diálogo de confirmación antes de eliminar un cliente.
 * @param {object} client - El cliente a eliminar.
 */
const confirmDeleteClient = (client) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar a "${client.name}"? Esta acción no se puede deshacer.`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            deleteClient(client);
        }
    });
};

/**
 * Envía la petición para eliminar al cliente.
 * @param {object} client - El cliente a eliminar.
 */
const deleteClient = (client) => {
    router.delete(`/clients/${client.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Cliente eliminado correctamente',
                life: 3000
            });
        },
        onError: () => {
             toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo eliminar al cliente.',
                life: 3000
            });
        }
    });
};


/**
 * Abre el diálogo modal para agregar un pago.
 * @param {object} client - El cliente seleccionado.
 */
const openPaymentDialog = (client) => {
    selectedClient.value = client;
    paymentForm.reset();
    paymentForm.client_id = client.id;
    isPaymentDialogVisible.value = true;
};

/**
 * Cierra el diálogo modal de agregar pago.
 */
const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
    selectedClient.value = null;
};

/**
 * Envía el formulario para registrar el pago.
 */
const submitPayment = () => {
    paymentForm.post(route('client-payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Pago registrado correctamente',
                life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo registrar el pago. Revisa los datos.',
                life: 3000
            });
        }
    });
};

/**
 * Navega a la página de detalles del cliente al hacer clic en una fila.
 * @param {object} event - El evento de clic de la fila de DataTable.
 */
const onRowClick = (event) => {
    router.get(`/clients/${event.data.id}`);
};

/**
 * Devuelve la clase CSS para las filas de la tabla para indicar que son clickeables.
 */
const rowClass = () => 'cursor-pointer';

/**
 * Formatea un valor numérico como moneda.
 * @param {number} value - El valor a formatear.
 */
const formatCurrency = (value) => {
    if (value === null || isNaN(value)) {
        value = 0;
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
};

/**
 * Devuelve una clase de severidad para el estado del cliente.
 * @param {string} status - El estado del cliente.
 */
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
                        <h1 class="text-3xl font-bold dark:text-gray-200 text-gray-800">Módulo de Clientes</h1>
                        <p class="text-gray-400 mt-1">Gestiona la información y finanzas de tus clientes.</p>
                    </div>
                    <Link href="/clients/create">
                        <Button label="Crear Cliente" icon="pi pi-plus" />
                    </Link>
                </header>

                <!-- Vista de Tabla para Escritorio -->
                <div class="hidden md:block">
                    <DataTable :value="clientsWithBalance" stripedRows paginator :rows="10" tableStyle="min-width: 50rem;"
                        @row-click="onRowClick" selectionMode="single" dataKey="id" :rowClass="rowClass">
                        <template #empty> No se encontraron clientes. </template>

                        <Column field="id" header="ID" sortable style="width: 5%"></Column>
                        <Column field="name" header="Cliente" sortable>
                            <template #body="{ data }">
                                <div class="font-semibold">{{ data.name }}</div>
                                <div class="text-sm text-gray-500">{{ data.tax_id }}</div>
                            </template>
                        </Column>
                        <Column field="status" header="Estado" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                            </template>
                        </Column>
                        <Column field="total_billed" header="Total Facturado" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="text-blue-600">{{ formatCurrency(data.total_billed) }}</span>
                            </template>
                        </Column>
                        <Column field="total_paid" header="Total Pagado" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="text-green-600">{{ formatCurrency(data.total_paid) }}</span>
                            </template>
                        </Column>
                        <Column field="balance" header="Balance" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="font-bold" :class="[data.balance > 0 ? 'text-red-600' : 'text-gray-700']">
                                    {{ formatCurrency(data.balance) }}
                                </span>
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                            <template #body="{ data }">
                                <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                    aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Menú de Acciones -->
                <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                <!-- Vista de Tarjetas para Móvil -->
                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card v-for="client in clientsWithBalance" :key="client.id"
                        class="cursor-pointer" @click="router.get(`/clients/${client.id}`)">
                        <template #title>
                            <div class="flex justify-between items-start">
                                <span class="text-lg font-bold">{{ client.name }}</span>
                                <Tag :value="client.status" :severity="getStatusSeverity(client.status)" />
                            </div>
                        </template>
                        <template #subtitle>ID: {{ client.id }} - {{ client.tax_id }}</template>
                        <template #content>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex justify-between"><span>Facturado:</span> <span class="text-blue-600">{{
                                    formatCurrency(client.total_billed) }}</span></li>
                                <li class="flex justify-between"><span>Pagado:</span> <span class="text-green-600">{{
                                    formatCurrency(client.total_paid) }}</span></li>
                                <li class="flex justify-between border-t pt-2 mt-2">
                                    <span class="font-bold">Balance:</span>
                                    <span class="font-bold" :class="[client.balance > 0 ? 'text-red-600' : 'text-gray-800']">
                                        {{ formatCurrency(client.balance) }}
                                    </span>
                                </li>
                            </ul>
                        </template>
                        <template #footer>
                             <div class="flex justify-end">
                                <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, client)"
                                    aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" />
                            </div>
                        </template>
                    </Card>
                    <div v-if="clients.length === 0" class="text-center text-gray-500 col-span-full mt-8">
                        No se encontraron clientes.
                    </div>
                </div>

                <!-- Diálogo Modal para Agregar Pago -->
                <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '25rem' }">
                    <template #header>
                        <div class="flex flex-col">
                            <h3 class="text-lg font-semibold">Registrar Pago</h3>
                            <p class="text-sm text-gray-500">Para: {{ selectedClient?.name }}</p>
                        </div>
                    </template>
                    <form @submit.prevent="submitPayment">
                        <div class="flex flex-col gap-4 p-4">
                            <div class="flex flex-col gap-2">
                                <label for="amount">Monto del Pago</label>
                                <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="USD"
                                    locale="en-US" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                                <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="payment_date">Fecha del Pago</label>
                                <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd"
                                    :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                                <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="notes">Notas (Opcional)</label>
                                <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
                            </div>
                        </div>
                    </form>
                    <template #footer>
                        <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" />
                        <Button label="Guardar Pago" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" />
                    </template>
                </Dialog>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Estilos para asegurar que el autocompletado del navegador no altere el diseño */
.p-inputtext, .p-inputnumber-input {
    width: 100% !important;
}

/* Ajustes finos para la apariencia de los componentes de PrimeVue */
body {
    font-family: 'Inter', sans-serif;
}
</style>

