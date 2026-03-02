<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dropdown from 'primevue/dropdown';
import Tooltip from 'primevue/tooltip';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';

const vTooltip = Tooltip;

const props = defineProps({
    client: { type: Object, required: true },
    total_billed: { type: [Number, String], required: true },
    total_paid: { type: [Number, String], required: true }
});

const toast = useToast();
const isPaymentDialogVisible = ref(false);

const paymentForm = useForm({
    client_id: props.client.id,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    quote_id: null,
});

const balance = computed(() => {
    return parseFloat(props.total_billed) - parseFloat(props.total_paid);
});

const quoteOptions = computed(() => {
    if (!props.client?.quotes) return [];
    return props.client.quotes
        .map(q => ({ ...q, balance: q.final_amount - (q.total_paid || 0) }))
        .filter(q => ['Aceptado', 'Pagado'].includes(q.status) && q.balance > 0.01) 
        .map(q => ({ id: q.id, label: `Cot-${q.id} - ${q.title} (Saldo: ${formatCurrency(q.balance)})` }));
});

const openPaymentDialog = () => {
    paymentForm.reset();
    paymentForm.client_id = props.client.id;
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => { isPaymentDialogVisible.value = false; };

const submitPayment = () => {
    paymentForm.post(route('client-payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Pago registrado correctamente', life: 3000 });
        },
        onError: (errors) => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo registrar el pago. Revisa los datos.', life: 3000 });
        }
    });
};

const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(0);
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(numericValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' });
};

const getStatusSeverity = (status) => (status === 'Cliente' ? 'success' : 'info');

const getQuoteStatusSeverity = (status) => {
    const statuses = { 'Pendiente': 'info', 'Enviado': 'warn', 'Aceptado': 'success', 'Pagado': 'success', 'Rechazado': 'danger' };
    return statuses[status] || 'secondary';
};

// --- NUEVAS FUNCIONES PARA CONTACTOS ---

// Limpia el teléfono para hacer llamadas (deja solo números y el signo +)
const cleanForCall = (phone) => {
    if (!phone) return '';
    return phone.replace(/[^0-9+]/g, '');
};

// Limpia el teléfono para WhatsApp (deja estrictamente solo números)
const cleanForWhatsApp = (phone) => {
    if (!phone) return '';
    return phone.replace(/[^0-9]/g, '');
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
                        <div class="max-w-4xl mx-auto mb-6">
                            <Link :href="route('clients.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                                <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                            </Link>
                        </div>
                        <div class="flex items-center gap-4">
                             <h1 class="text-3xl font-bold text-[#212121] dark:text-zinc-100">{{ client.name }}</h1>
                             <Tag :value="client.status" :severity="getStatusSeverity(client.status)" rounded />
                        </div>
                        <p class="text-gray-500 dark:text-zinc-400 mt-1">ID: {{ client.id }} - {{ client.tax_id }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Link :href="route('clients.edit', client.id)">
                            <Button label="Editar" icon="pi pi-pencil" severity="secondary" outlined />
                        </Link>
                        <Button label="Agregar Pago" icon="pi pi-dollar" class="!text-[var(--primary-text-color)]" @click="openPaymentDialog"/>
                    </div>
                </header>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                    <!-- Left Column: Info & Financials -->
                    <div class="lg:col-span-1 flex flex-col gap-5">
                        <!-- Financial Summary -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4">
                            <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                                <template #title><span class="text-gray-500 dark:text-zinc-500 text-sm font-bold uppercase tracking-wider">Total Facturado</span></template>
                                <template #content>
                                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(total_billed) }}</p>
                                </template>
                            </Card>
                             <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                                <template #title><span class="text-gray-500 dark:text-zinc-500 text-sm font-bold uppercase tracking-wider">Total Pagado</span></template>
                                <template #content>
                                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(total_paid) }}</p>
                                </template>
                            </Card>
                             <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                                <template #title><span class="text-gray-500 dark:text-zinc-500 text-sm font-bold uppercase tracking-wider">Balance</span></template>
                                <template #content>
                                    <p class="text-3xl font-bold" :class="[balance > 0.01 ? 'text-red-500 dark:text-red-400' : 'text-gray-800 dark:text-zinc-200']">
                                        {{ formatCurrency(balance) }}
                                    </p>
                                </template>
                            </Card>
                        </div>

                         <!-- Client Details -->
                        <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                            <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-zinc-100 font-bold text-lg">
                                    <i class="pi pi-id-card"></i>
                                    <span>Información</span>
                                </div>
                            </template>
                            <template #content>
                                <ul class="space-y-3 text-gray-600 dark:text-zinc-300 text-sm">
                                    <li v-if="client.address"><strong class="font-semibold text-gray-800 dark:text-zinc-100">Dirección:</strong> {{ client.address }}</li>
                                    <li v-if="client.source"><strong class="font-semibold text-gray-800 dark:text-zinc-100">Fuente:</strong> {{ client.source }}</li>
                                    <li><strong class="font-semibold text-gray-800 dark:text-zinc-100">Registrado:</strong> {{ formatDate(client.created_at) }}</li>
                                </ul>
                            </template>
                        </Card>

                        <!-- Contacts -->
                        <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                            <template #title>
                                 <div class="flex items-center gap-2 text-gray-800 dark:text-zinc-100 font-bold text-lg">
                                    <i class="pi pi-users"></i>
                                    <span>Contactos</span>
                                </div>
                            </template>
                            <template #content>
                                <ul v-if="client.contacts.length > 0" class="space-y-4">
                                    <li v-for="contact in client.contacts" :key="contact.id" class="border-b border-gray-100 dark:border-zinc-800 pb-3 last:border-b-0">
                                        <p class="font-bold text-gray-800 dark:text-zinc-200">{{ contact.name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-zinc-500 uppercase tracking-wide">{{ contact.position }}</p>
                                        
                                        <!-- Correo Electrónico Clickeable -->
                                        <p class="text-md text-gray-600 dark:text-zinc-400 mt-1 flex items-center gap-2">
                                            <i class="pi pi-envelope text-xs"></i> 
                                            <a :href="'mailto:' + contact.email" class="hover:underline text-blue-600 dark:text-blue-400">
                                                {{ contact.email }}
                                            </a>
                                        </p>
                                        
                                        <!-- Teléfono Clickeable y WhatsApp -->
                                        <div class="text-md text-gray-600 dark:text-zinc-400 flex items-center gap-2 mt-2">
                                            <i class="pi pi-phone text-xs"></i> 
                                            <a :href="'tel:' + cleanForCall(contact.phone)" class="hover:underline text-blue-600 dark:text-blue-400 mr-2" title="Llamar">
                                                {{ contact.phone }}
                                            </a>
                                            <a v-if="contact.phone" :href="'https://wa.me/' + cleanForWhatsApp(contact.phone)" target="_blank" class="text-green-500 hover:text-green-600 dark:text-green-400 transition-colors" title="Enviar WhatsApp">
                                                <i class="pi pi-whatsapp"></i>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                                <p v-else class="text-gray-500 dark:text-zinc-500 text-sm text-center py-2">No hay contactos registrados.</p>
                            </template>
                        </Card>

                    </div>

                    <!-- Right Column: Tables -->
                    <div class="lg:col-span-2 flex flex-col gap-5">

                        <!-- Quotes -->
                        <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                            <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-zinc-100 font-bold text-lg p-2">
                                    <i class="pi pi-file-edit"></i>
                                    <span>Cotizaciones</span>
                                </div>
                            </template>
                            <template #content>
                                <DataTable :value="client.quotes" responsiveLayout="scroll" paginator :rows="5" tableStyle="min-width: 50rem;" class="zinc-table">
                                    <template #empty> <div class="p-4 text-center text-gray-500">No se encontraron cotizaciones.</div> </template>
                                    <Column field="id" header="Folio" sortable>
                                        <template #body="{data}"><span class="text-gray-600 dark:text-zinc-400">Cot-{{ data.id }}</span></template>
                                    </Column>
                                    <Column field="title" header="Título" sortable>
                                        <template #body="{data}"><span class="text-gray-800 dark:text-zinc-200 font-medium">{{ data.title }}</span></template>
                                    </Column>
                                    <Column field="final_amount" header="Monto" sortable bodyClass="text-right">
                                        <template #body="{ data }">
                                            <div class="flex items-center justify-end gap-2">
                                                <span class="text-gray-800 dark:text-zinc-200">{{ formatCurrency(data.final_amount) }}</span>
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
                                        <template #body="{ data }"><span class="text-gray-600 dark:text-zinc-400">{{ formatDate(data.valid_until) }}</span></template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>

                        <!-- Payments -->
                        <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                             <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-zinc-100 font-bold text-lg p-2">
                                    <i class="pi pi-history"></i>
                                    <span>Historial de Pagos</span>
                                </div>
                            </template>
                            <template #content>
                                 <DataTable :value="client.payments" responsiveLayout="scroll" paginator :rows="5" tableStyle="min-width: 50rem;" class="zinc-table">
                                    <template #empty> <div class="p-4 text-center text-gray-500">No se han registrado pagos.</div> </template>
                                    <Column field="payment_date" header="Fecha de Pago" sortable>
                                         <template #body="{ data }"><span class="text-gray-600 dark:text-zinc-400">{{ formatDate(data.payment_date) }}</span></template>
                                    </Column>
                                    <Column field="amount" header="Monto" sortable bodyClass="text-right">
                                        <template #body="{ data }">
                                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.amount) }}</span>
                                        </template>
                                    </Column>

                                    <!-- NUEVA COLUMNA: Cotización -->
                                    <Column header="Cotización">
                                        <template #body="{ data }">
                                            <Link v-if="data.quote" :href="route('quotes.show', data.quote.id)" class="text-blue-500 hover:underline dark:text-blue-400 font-medium">
                                                {{ data.quote.folio || 'Cot-' + data.quote.id }}
                                            </Link>
                                            <span v-else class="text-gray-400 dark:text-zinc-500 italic">N/A</span>
                                        </template>
                                    </Column>

                                    <!-- NUEVA COLUMNA: Comprobante (Soporte para Spatie Media Library) -->
                                    <Column header="Comprobante" bodyClass="text-center">
                                        <template #body="{ data }">
                                            <a v-if="data.media && data.media.length > 0" :href="data.media[0].original_url" target="_blank" class="text-gray-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 transition-colors" title="Ver comprobante">
                                                <i class="pi pi-file-pdf text-xl"></i>
                                            </a>
                                            <!-- Fallback en caso de que lo mandes como un string simple llamado comprobante -->
                                            <a v-else-if="data.comprobante" :href="'/storage/' + data.comprobante" target="_blank" class="text-gray-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 transition-colors" title="Ver comprobante">
                                                <i class="pi pi-file-pdf text-xl"></i>
                                            </a>
                                            <span v-else class="text-gray-300 dark:text-zinc-700">-</span>
                                        </template>
                                    </Column>

                                    <Column field="notes" header="Notas">
                                        <template #body="{ data }"><span class="text-gray-500 dark:text-zinc-500 text-sm">{{ data.notes || '-' }}</span></template>
                                    </Column>
                                </DataTable>
                            </template>
                        </Card>

                    </div>
                </div>

                <!-- Add Payment Dialog -->
                <Dialog v-model:visible="isPaymentDialogVisible" modal :header="`Registrar Pago para ${client.name}`" :style="{ width: '30rem' }"
                    :pt="{ root: { class: 'dark:bg-zinc-900 dark:border-zinc-700' }, header: { class: 'dark:bg-zinc-900 dark:text-zinc-200' }, content: { class: 'dark:bg-zinc-900' }, footer: { class: 'dark:bg-zinc-900' } }">
                    <form @submit.prevent="submitPayment" class="p-fluid space-y-4 pt-4">
                        <div class="flex flex-col">
                            <label for="quote" class="mb-2 font-semibold dark:text-zinc-300">Asociar a Cotización (Opcional)</label>
                            <Dropdown id="quote" v-model="paymentForm.quote_id" :options="quoteOptions"
                                optionLabel="label" optionValue="id" placeholder="Selecciona una cotización con saldo"
                                class="w-full" :class="{ 'p-invalid': paymentForm.errors.quote_id }" showClear />
                            <small v-if="paymentForm.errors.quote_id" class="p-error mt-1">{{ paymentForm.errors.quote_id }}</small>
                        </div>
                
                        <div class="flex flex-col">
                            <label for="amount" class="mb-2 font-semibold dark:text-zinc-300">Monto del Pago</label>
                            <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                            <small v-if="paymentForm.errors.amount" class="p-error mt-1">{{ paymentForm.errors.amount }}</small>
                        </div>
                
                        <div class="flex flex-col">
                            <label for="payment_date" class="mb-2 font-semibold dark:text-zinc-300">Fecha del Pago</label>
                            <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                            <small v-if="paymentForm.errors.payment_date" class="p-error mt-1">{{ paymentForm.errors.payment_date }}</small>
                        </div>
                        
                        <div class="flex flex-col">
                            <label for="notes" class="mb-2 font-semibold dark:text-zinc-300">Notas (Opcional)</label>
                            <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
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
.custom-tooltip .p-tooltip-text {
  text-align: left;
  white-space: pre-wrap;
}

:deep(.p-card .p-card-content) { padding-top: 0; }
:deep(.p-card .p-card-title) { margin-bottom: 0.5rem; }

/* Zinc Theme Overrides for PrimeVue DataTable */
:deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: transparent !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
}

:deep(.zinc-table .p-datatable-tbody > tr) { background-color: transparent !important; }
:deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) { border-bottom: 1px solid #f4f4f5; }
</style>

<style>
/* Estilos globales para PrimeVue DataTable 
  Al estar fuera de "scoped", Vue no altera las clases y el navegador lee la ruta exacta.
*/
.zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.zinc-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode 
  Agregamos html.dark para darle un "extra" de especificidad y ganarle a PrimeVue
*/
html.dark .zinc-table .p-datatable-thead > tr > th,
.dark .zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>