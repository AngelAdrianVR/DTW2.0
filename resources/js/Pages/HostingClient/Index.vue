<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';


// --- PROPS ---
const props = defineProps({
    hostingClients: {
        type: Array,
        required: true,
    }
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const menu = ref();
const selectedHostingClientForMenu = ref(null);
const isPaymentDialogVisible = ref(false);
const selectedHostingClientForPayment = ref(null);

// --- FORMS ---
const paymentForm = useForm({
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
});


// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedHostingClientForMenu.value) return [];
    const hc = selectedHostingClientForMenu.value;

    const items = [
        {
            label: 'Agregar Pago',
            icon: 'pi pi-dollar',
            command: () => openPaymentDialog(hc)
        },
        {
            label: 'Editar Registro',
            icon: 'pi pi-pencil',
            command: () => router.get(route('hosting-clients.edit', hc.id))
        },
    ];

    if (hc.status === 'Activo') {
        items.push({
            label: 'Cancelar Servicio',
            icon: 'pi pi-times-circle',
            command: () => confirmUpdateStatus(hc, 'Cancelado')
        });
    } else {
         items.push({
            label: 'Reactivar Servicio',
            icon: 'pi pi-check-circle',
            command: () => confirmUpdateStatus(hc, 'Activo')
        });
    }

    items.push(
        { separator: true },
        {
            label: 'Eliminar Registro',
            icon: 'pi pi-trash',
            command: () => confirmDeleteHostingClient(hc)
        }
    );
    
    return items;
});


const toggleMenu = (event, hostingClient) => {
    selectedHostingClientForMenu.value = hostingClient;
    menu.value.toggle(event);
};

// --- METHODS ---
const confirmUpdateStatus = (hostingClient, newStatus) => {
    const action = newStatus === 'Activo' ? 'reactivar' : 'cancelar';
     confirm.require({
        message: `¿Estás seguro de que quieres ${action} el servicio para "${hostingClient.client.name}"?`,
        header: 'Confirmación de cambio de estado',
        icon: 'pi pi-info-circle',
        accept: () => {
            updateStatus(hostingClient, newStatus);
        }
    });
};

const updateStatus = (hostingClient, newStatus) => {
    router.patch(route('hosting-clients.status.update', hostingClient.id), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
             toast.add({
                severity: 'success', summary: 'Éxito',
                detail: 'Estado actualizado correctamente', life: 3000
            });
        },
        onError: () => {
            toast.add({
                severity: 'error', summary: 'Error',
                detail: 'No se pudo actualizar el estado.', life: 3000
            });
        }
    });
};


const confirmDeleteHostingClient = (hostingClient) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar el registro de hosting para "${hostingClient.client.name}"?`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            deleteHostingClient(hostingClient);
        }
    });
};

const deleteHostingClient = (hostingClient) => {
    router.delete(route('hosting-clients.destroy', hostingClient.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success', summary: 'Éxito',
                detail: 'Registro de hosting eliminado', life: 3000
            });
        },
        onError: () => {
             toast.add({
                severity: 'error', summary: 'Error',
                detail: 'No se pudo eliminar el registro.', life: 3000
            });
        }
    });
};

const openPaymentDialog = (hostingClient) => {
    selectedHostingClientForPayment.value = hostingClient;
    paymentForm.reset();
    paymentForm.amount = hostingClient.payment_amount; // Pre-fill with the default amount
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
    selectedHostingClientForPayment.value = null;
};

const submitPayment = () => {
    if (!selectedHostingClientForPayment.value) return;
    
    paymentForm.post(route('hosting-clients.payments.store', selectedHostingClientForPayment.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({
                severity: 'success', summary: 'Éxito',
                detail: 'Pago registrado correctamente', life: 3000
            });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({
                severity: 'error', summary: 'Error',
                detail: errorMessages || 'No se pudo registrar el pago.', life: 3000
            });
        }
    });
};


const onRowClick = (event) => {
    router.get(route('hosting-clients.show', event.data.id));
};

const rowClass = () => 'cursor-pointer';

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getStatusSeverity = (status) => {
    const statusMap = { 'Activo': 'success', 'Suspendido': 'warning', 'Cancelado': 'danger' };
    return statusMap[status] || 'info';
};
</script>

<template>
    <AppLayout title="Clientes de Hosting">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-gray-200 text-gray-800">Módulo de Hosting</h1>
                        <p class="text-gray-400 mt-1">Gestiona los servicios de hosting de tus clientes.</p>
                    </div>
                    <Link :href="route('hosting-clients.create')">
                        <Button label="Añadir Servicio" icon="pi pi-plus" />
                    </Link>
                </header>

                <!-- Desktop Table View -->
                <div class="hidden md:block">
                    <DataTable :value="hostingClients" stripedRows paginator :rows="15" tableStyle="min-width: 50rem;"
                        @row-click="onRowClick" selectionMode="single" dataKey="id" :rowClass="rowClass">
                        <template #empty> No se encontraron servicios de hosting. </template>

                        <Column field="client.name" header="Cliente" sortable>
                            <template #body="{ data }">
                                <div class="font-semibold">{{ data.client.name }}</div>
                            </template>
                        </Column>
                         <Column field="service_provider" header="Proveedor" sortable></Column>
                        <Column field="next_payment_date" header="Próximo Pago" sortable>
                            <template #body="{ data }">
                                {{ formatDate(data.next_payment_date) }}
                            </template>
                        </Column>
                        <Column field="payment_amount" header="Monto" sortable class="text-right">
                            <template #body="{ data }">
                                <span class="text-green-600">{{ formatCurrency(data.payment_amount) }}</span>
                            </template>
                        </Column>
                         <Column field="billing_cycle" header="Ciclo" sortable></Column>
                        <Column field="status" header="Estado" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
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

                <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                 <!-- Mobile Card View -->
                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card v-for="hosting in hostingClients" :key="hosting.id" @click="onRowClick({data: hosting})">
                         <template #title>
                            <div class="flex justify-between items-start">
                                <span class="text-lg font-bold">{{ hosting.client.name }}</span>
                                <Tag :value="hosting.status" :severity="getStatusSeverity(hosting.status)" />
                            </div>
                        </template>
                        <template #subtitle>{{ hosting.service_provider }}</template>
                        <template #content>
                            <ul class="space-y-2 text-gray-700">
                                <li class="flex justify-between"><span>Próximo Pago:</span> <span>{{ formatDate(hosting.next_payment_date) }}</span></li>
                                 <li class="flex justify-between border-t pt-2 mt-2">
                                    <span class="font-bold">Monto:</span>
                                    <span class="font-bold text-green-600">
                                        {{ formatCurrency(hosting.payment_amount) }} ({{ hosting.billing_cycle }})
                                    </span>
                                </li>
                            </ul>
                        </template>
                        <template #footer>
                             <div class="flex justify-end">
                                <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, hosting)"
                                    aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" />
                            </div>
                        </template>
                    </Card>
                    <div v-if="hostingClients.length === 0" class="text-center text-gray-500 col-span-full mt-8">
                        No se encontraron servicios de hosting.
                    </div>
                </div>


                <!-- Payment Dialog -->
                 <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '25rem' }">
                    <template #header>
                        <div class="flex flex-col">
                            <h3 class="text-lg font-semibold">Registrar Pago de Hosting</h3>
                            <p class="text-sm text-gray-500">Para: {{ selectedHostingClientForPayment?.client?.name }}</p>
                        </div>
                    </template>
                    <form @submit.prevent="submitPayment">
                        <div class="flex flex-col gap-4 p-4">
                            <div class="flex flex-col gap-2">
                                <label for="amount">Monto del Pago</label>
                                <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                                <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="payment_date">Fecha del Pago</label>
                                <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
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

