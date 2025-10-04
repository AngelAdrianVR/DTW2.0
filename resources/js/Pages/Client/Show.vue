<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';

// --- PROPS ---
const props = defineProps({
    client: {
        type: Object,
        required: true,
    },
    total_billed: {
        type: [Number, String],
        required: true,
    },
    total_paid: {
        type: [Number, String],
        required: true,
    }
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const isPaymentDialogVisible = ref(false);

// --- FORMS ---
const paymentForm = useForm({
    client_id: props.client.id,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    quote_id: null, // Opcional, para asociar un pago a una cotización
});

// --- COMPUTED PROPERTIES ---
const balance = computed(() => {
    return parseFloat(props.total_billed) - parseFloat(props.total_paid);
});

// --- METHODS ---

/**
 * Abre el diálogo modal para agregar un pago.
 */
const openPaymentDialog = () => {
    paymentForm.reset();
    paymentForm.client_id = props.client.id;
    isPaymentDialogVisible.value = true;
};

/**
 * Cierra el diálogo modal de agregar pago.
 */
const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
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
            // Opcional: Recargar los datos para ver el pago reflejado inmediatamente
            // router.reload({ only: ['client', 'total_paid'] });
        },
        onError: (errors) => {
            console.error("Payment Error:", errors);
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo registrar el pago. Revisa los datos.',
                life: 3000
            });
        }
    });
};

// --- HELPERS ---

/**
 * Formatea un valor numérico como moneda.
 * @param {number} value - El valor a formatear.
 */
const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(0);
    }
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numericValue);
};

/**
 * Formatea una fecha.
 * @param {string} dateString - La fecha en formato string.
 */
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};


/**
 * Devuelve una clase de severidad para el estado del cliente.
 * @param {string} status - El estado del cliente.
 */
const getStatusSeverity = (status) => (status === 'Cliente' ? 'success' : 'info');

/**
 * Devuelve una clase de severidad para el estado de la cotización.
 */
const getQuoteStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'info',
        'Aceptada': 'success',
        'Rechazada': 'danger',
        'Expirada': 'warning'
    };
    return statuses[status] || 'secondary';
};

</script>

<template>
    <Head :title="`Detalle de ${client.name}`" />
    <AppLayout title="Detalle del Cliente">
        <div class="p-4 sm:p-6 lg:p-8 min-h-screen">
            <div class="max-w-7xl mx-auto">
                <Toast />

                <!-- Header -->
                <header class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <Link :href="route('clients.index')" class="text-sm text-gray-500 dark:text-gray-400 hover:underline flex items-center mb-2">
                            <i class="pi pi-arrow-left mr-2"></i>
                            Regresar a Clientes
                        </Link>
                        <div class="flex items-center gap-4">
                             <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ client.name }}</h1>
                             <Tag :value="client.status" :severity="getStatusSeverity(client.status)" rounded />
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 mt-1">ID: {{ client.id }} - {{ client.tax_id }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('clients.edit', client.id)">
                            <Button label="Editar" icon="pi pi-pencil" severity="secondary" outlined />
                        </Link>
                        <Button label="Agregar Pago" icon="pi pi-dollar" @click="openPaymentDialog"/>
                    </div>
                </header>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <!-- Left Column: Info & Financials -->
                    <div class="lg:col-span-1 flex flex-col gap-5">
                        <!-- Financial Summary -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                            <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                                <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Total Facturado</span></template>
                                <template #content>
                                    <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(total_billed) }}</p>
                                </template>
                            </Card>
                             <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                                <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Total Pagado</span></template>
                                <template #content>
                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(total_paid) }}</p>
                                </template>
                            </Card>
                             <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                                <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Balance</span></template>
                                <template #content>
                                    <p class="text-2xl font-bold" :class="[balance > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-800 dark:text-gray-200']">
                                        {{ formatCurrency(balance) }}
                                    </p>
                                </template>
                            </Card>
                        </div>

                         <!-- Client Details -->
                        <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                            <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                    <i class="pi pi-id-card"></i>
                                    <span>Información del Cliente</span>
                                </div>
                            </template>
                            <template #content>
                                <ul class="space-y-3 text-gray-600 dark:text-gray-300">
                                    <li v-if="client.address"><strong class="font-semibold">Dirección:</strong> {{ client.address }}</li>
                                    <li v-if="client.source"><strong class="font-semibold">Fuente:</strong> {{ client.source }}</li>
                                    <li><strong class="font-semibold">Registrado:</strong> {{ formatDate(client.created_at) }}</li>
                                </ul>
                            </template>
                        </Card>

                        <!-- Contacts -->
                        <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                            <template #title>
                                 <div class="flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                    <i class="pi pi-users"></i>
                                    <span>Contactos</span>
                                </div>
                            </template>
                            <template #content>
                                <ul v-if="client.contacts.length > 0" class="space-y-4">
                                    <li v-for="contact in client.contacts" :key="contact.id" class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-b-0">
                                        <p class="font-bold text-gray-800 dark:text-gray-200">{{ contact.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ contact.position }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ contact.email }}</p>
                                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ contact.phone }}</p>
                                    </li>
                                </ul>
                                <p v-else class="text-gray-500 dark:text-gray-400">No hay contactos registrados.</p>
                            </template>
                        </Card>

                    </div>

                    <!-- Right Column: Tables -->
                    <div class="lg:col-span-2 flex flex-col gap-5">

                        <!-- Quotes -->
                        <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                            <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                    <i class="pi pi-file-edit"></i>
                                    <span>Cotizaciones</span>
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="client.quotes" stripedRows responsiveLayout="scroll" paginator :rows="5" tableStyle="min-width: 50rem;">
                                    <template #empty> No se encontraron cotizaciones. </template>
                                    <Column field="quote_code" header="Código" sortable></Column>
                                    <Column field="title" header="Título" sortable></Column>
                                    <Column field="amount" header="Monto" sortable>
                                        <template #body="{ data }">{{ formatCurrency(data.amount) }}</template>
                                    </Column>
                                    <Column field="status" header="Estado" sortable>
                                        <template #body="{ data }">
                                            <Tag :value="data.status" :severity="getQuoteStatusSeverity(data.status)" />
                                        </template>
                                    </Column>
                                    <Column field="valid_until" header="Válida Hasta" sortable>
                                        <template #body="{ data }">{{ formatDate(data.valid_until) }}</template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>

                        <!-- Payments -->
                        <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                             <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-gray-200">
                                    <i class="pi pi-history"></i>
                                    <span>Historial de Pagos</span>
                                </div>
                            </template>
                            <template #content>
                                 <DataTable :value="client.payments" stripedRows responsiveLayout="scroll" paginator :rows="5" tableStyle="min-width: 50rem;">
                                    <template #empty> No se han registrado pagos. </template>
                                    <Column field="payment_date" header="Fecha de Pago" sortable>
                                         <template #body="{ data }">{{ formatDate(data.payment_date) }}</template>
                                    </Column>
                                    <Column field="amount" header="Monto" sortable class="text-right">
                                        <template #body="{ data }">
                                            <span class="font-semibold text-green-600 dark:text-green-400">{{ formatCurrency(data.amount) }}</span>
                                        </template>
                                    </Column>
                                    <Column field="notes" header="Notas"></Column>
                                </DataTable>
                            </template>
                        </Card>

                    </div>
                </div>

                 <!-- Add Payment Dialog -->
                <Dialog v-model:visible="isPaymentDialogVisible" modal :header="`Registrar Pago para ${client.name}`" :style="{ width: '30rem' }">
                    <form @submit.prevent="submitPayment" class="p-fluid">
                         <div class="field mt-4">
                            <span class="p-float-label">
                                <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="USD" locale="en-US" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                                <label for="amount">Monto del Pago</label>
                            </span>
                            <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                        </div>
                        <div class="field mt-4">
                             <span class="p-float-label">
                                <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                                 <label for="payment_date">Fecha del Pago</label>
                             </span>
                            <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                        </div>
                        <div class="field mt-4">
                             <span class="p-float-label">
                                <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
                                <label for="notes">Notas (Opcional)</label>
                             </span>
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

<style scoped>
/* Scoped styles for this component */
.p-card .p-card-content {
    padding-top: 0;
}
.p-card .p-card-title {
    margin-bottom: 1rem;
}
</style>

