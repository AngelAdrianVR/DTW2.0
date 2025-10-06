<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dropdown from 'primevue/dropdown';
import Tooltip from 'primevue/tooltip';

// --- DIRECTIVES ---
const vTooltip = Tooltip;

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
    quote_id: null,
});

// --- COMPUTED PROPERTIES ---
const balance = computed(() => {
    // El total_billed ya viene con los descuentos calculados desde el controlador.
    return parseFloat(props.total_billed) - parseFloat(props.total_paid);
});

/**
 * MODIFICACIÓN: El cálculo del saldo de cada cotización en el dropdown ahora usa 'final_amount'.
 * El accesor 'final_amount' viene automáticamente desde el modelo de Laravel.
 */
const quoteOptions = computed(() => {
    if (!props.client?.quotes) return [];
    
    return props.client.quotes
        .map(q => ({
            ...q,
            balance: q.final_amount - (q.total_paid || 0)
        }))
        .filter(q => ['Aceptado', 'Pagado'].includes(q.status) && q.balance > 0.01) 
        .map(q => ({
            id: q.id,
            label: `Cot-${q.id} - ${q.title} (Saldo: ${formatCurrency(q.balance)})`
        }));
});


// --- METHODS ---
const openPaymentDialog = () => {
    paymentForm.reset();
    paymentForm.client_id = props.client.id;
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
};

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
const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(0);
    }
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(numericValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const getStatusSeverity = (status) => (status === 'Cliente' ? 'success' : 'info');

const getQuoteStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'info',
        'Enviado': 'warn',
        'Aceptado': 'success',
        'Pagado': 'success',
        'Rechazado': 'danger',
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
                                    <p class="text-2xl font-bold" :class="[balance > 0.01 ? 'text-red-500 dark:text-red-400' : 'text-gray-800 dark:text-gray-200']">
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
                                    <Column field="id" header="Folio" sortable>
                                        <template #body="{data}">Cot-{{ data.id }}</template>
                                    </Column>
                                    <Column field="title" header="Título" sortable></Column>
                                    <!-- MODIFICACIÓN: Columna de Monto actualizada -->
                                    <Column field="final_amount" header="Monto" sortable>
                                        <template #body="{ data }">
                                            <div class="flex items-center justify-start gap-2">
                                                <span>{{ formatCurrency(data.final_amount) }}</span>
                                                <i v-if="data.percentage_discount && data.percentage_discount > 0"
                                                   class="pi pi-info-circle text-gray-400 cursor-pointer"
                                                   v-tooltip.left="{
                                                       value: `Subtotal: ${formatCurrency(data.amount)} <br/> Descuento: ${data.percentage_discount}% (${formatCurrency(data.amount - data.final_amount)})`,
                                                       escape: false,
                                                       class: 'custom-tooltip'
                                                   }">
                                                </i>
                                            </div>
                                        </template>
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
                    <form @submit.prevent="submitPayment" class="p-fluid space-y-4 pt-4">
                        <div class="flex flex-col">
                            <label for="quote" class="mb-2 font-semibold">Asociar a Cotización (Opcional)</label>
                            <Dropdown id="quote" v-model="paymentForm.quote_id" :options="quoteOptions"
                                optionLabel="label" optionValue="id" placeholder="Selecciona una cotización con saldo"
                                class="w-full" :class="{ 'p-invalid': paymentForm.errors.quote_id }" showClear />
                            <small v-if="paymentForm.errors.quote_id" class="p-error mt-1">{{ paymentForm.errors.quote_id }}</small>
                        </div>
                
                        <div class="flex flex-col">
                            <label for="amount" class="mb-2 font-semibold">Monto del Pago</label>
                            <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                            <small v-if="paymentForm.errors.amount" class="p-error mt-1">{{ paymentForm.errors.amount }}</small>
                        </div>
                
                        <div class="flex flex-col">
                            <label for="payment_date" class="mb-2 font-semibold">Fecha del Pago</label>
                            <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                            <small v-if="paymentForm.errors.payment_date" class="p-error mt-1">{{ paymentForm.errors.payment_date }}</small>
                        </div>
                        
                        <div class="flex flex-col">
                            <label for="notes" class="mb-2 font-semibold">Notas (Opcional)</label>
                            <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
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
/* Estilos globales para el tooltip personalizado, similar a IndexQuote */
.custom-tooltip .p-tooltip-text {
  text-align: left;
  white-space: pre-wrap;
}

.p-card .p-card-content {
    padding-top: 0;
}
.p-card .p-card-title {
    margin-bottom: 1rem;
}
</style>
