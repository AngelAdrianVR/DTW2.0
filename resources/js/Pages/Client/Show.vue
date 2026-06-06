<script setup>
import { ref, computed } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import Dropdown from 'primevue/dropdown';
import Tooltip from 'primevue/tooltip';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
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
const confirm = useConfirm();
const isPaymentDialogVisible = ref(false);

const paymentForm = useForm({
    client_id: props.client.id,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    quote_id: null,
    receipt: null, // Agregado para soportar adjuntos
});

const documentForm = useForm({
    document: null,
});

const isUploadingDocument = ref(false);

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

const onDocumentSelected = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    documentForm.document = file;
    documentForm.post(route('clients.documents.upload', props.client.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            event.target.value = '';
            documentForm.reset();
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Documento subido correctamente.', life: 3000 });
        },
        onError: (errors) => {
            event.target.value = '';
            documentForm.reset();
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo subir el documento. Verifica el formato.', life: 3000 });
        }
    });
};

const deleteDocument = (mediaId, documentName) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar el documento "${documentName}"?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        accept: () => {
            useForm({}).delete(route('clients.documents.delete', { client: props.client.id, media: mediaId }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Documento eliminado correctamente.', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el documento.', life: 3000 });
                }
            });
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

// --- FUNCIONES PARA CONTACTOS ---
const cleanForCall = (phone) => {
    if (!phone) return '';
    return phone.replace(/[^0-9+]/g, '');
};

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
                <ConfirmDialog />

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
                                    <li><strong class="font-semibold text-gray-800 dark:text-zinc-100">Régimen Fiscal:</strong> {{ client.regimen_fiscal === 'persona_moral' ? 'Persona Moral' : 'Persona Física' }}</li>
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
                                        
                                        <p class="text-md text-gray-600 dark:text-zinc-400 mt-1 flex items-center gap-2">
                                            <i class="pi pi-envelope text-xs"></i> 
                                            <a :href="'mailto:' + contact.email" class="hover:underline text-blue-600 dark:text-blue-400">
                                                {{ contact.email }}
                                            </a>
                                        </p>
                                        
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

                        <!-- Documents -->
                        <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                            <template #title>
                                <div class="flex items-center gap-2 text-gray-800 dark:text-zinc-100 font-bold text-lg">
                                    <i class="pi pi-folder-open"></i>
                                    <span>Documentos</span>
                                </div>
                            </template>
                            <template #content>
                                <!-- Upload Button -->
                                <div class="mb-4">
                                    <label class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-900/50 transition-colors cursor-pointer font-medium text-sm">
                                        <i class="pi pi-upload"></i>
                                        <span>Subir Documento</span>
                                        <input type="file" @change="onDocumentSelected" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                                    </label>
                                </div>

                                <!-- Document List -->
                                <ul v-if="client.media && client.media.length > 0" class="space-y-3">
                                    <li v-for="doc in client.media" :key="doc.id" class="flex items-center justify-between gap-3 border-b border-gray-100 dark:border-zinc-800 pb-3 last:border-b-0">
                                        <a :href="doc.original_url" target="_blank" class="flex items-center gap-3 flex-1 min-w-0 hover:bg-gray-50 dark:hover:bg-zinc-800 rounded-lg p-1.5 -m-1.5 transition-colors group">
                                            <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                                <i class="pi pi-file text-blue-600 dark:text-blue-400"></i>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-sm font-medium text-gray-800 dark:text-zinc-200 truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                    {{ doc.name }}
                                                </p>
                                                <p class="text-xs text-gray-400 dark:text-zinc-500">
                                                    {{ new Date(doc.created_at).toLocaleDateString('es-MX', { year: 'numeric', month: 'short', day: 'numeric' }) }}
                                                </p>
                                            </div>
                                        </a>
                                        <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                                            @click="deleteDocument(doc.id, doc.name)"
                                            v-tooltip.top="'Eliminar documento'" />
                                    </li>
                                </ul>
                                <p v-else class="text-gray-500 dark:text-zinc-500 text-sm text-center py-4">
                                    <i class="pi pi-info-circle mr-1"></i> No hay documentos adjuntos.
                                </p>
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
                                        <template #body="{data}">
                                            <Link :href="route('quotes.show', data.id)" class="text-blue-500 hover:underline dark:text-blue-400 font-medium">
                                                Cot-{{ data.id }}
                                            </Link>
                                        </template>
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
                                    <Column header="Cotización">
                                        <template #body="{ data }">
                                            <Link v-if="data.quote" :href="route('quotes.show', data.quote.id)" class="text-blue-500 hover:underline dark:text-blue-400 font-medium">
                                                {{ data.quote.folio || 'Cot-' + data.quote.id }}
                                            </Link>
                                            <span v-else class="text-gray-400 dark:text-zinc-500 italic">N/A</span>
                                        </template>
                                    </Column>

                                    <!-- COLUMNA CORREGIDA: Comprobante múltiple soporte de rutas -->
                                    <Column header="Comprobante" headerStyle="text-align: center" bodyStyle="text-align: center">
                                        <template #body="{ data }">
                                            <!-- Si usa Spatie Media Library -->
                                            <a v-if="data.media && data.media.length > 0" :href="data.media[0].original_url" target="_blank" class="text-gray-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 transition-colors" title="Ver comprobante">
                                                <i class="pi pi-file-pdf text-xl"></i>
                                            </a>
                                            <!-- Si se envía manual o estándar a través del sistema de Laravel -->
                                            <a v-else-if="data.receipt_path || data.receipt" :href="(data.receipt_path ? ('/storage/' + data.receipt_path) : ('/storage/' + data.receipt))" target="_blank" class="text-gray-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 transition-colors" title="Ver comprobante">
                                                <i class="pi pi-file-pdf text-xl"></i>
                                            </a>
                                            <!-- Fallback si se genera una URL en un atributo virtual -->
                                            <a v-else-if="data.receipt_url" :href="data.receipt_url" target="_blank" class="text-gray-500 hover:text-blue-600 dark:text-zinc-400 dark:hover:text-blue-400 transition-colors" title="Ver comprobante">
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

                <!-- Add Payment Dialog (Apple Style) -->
                <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '28rem' }"
                    :pt="{ 
                        root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0' }, 
                        header: { class: 'pt-8 px-8 pb-0 bg-transparent rounded-t-[2rem] dark:text-zinc-100' }, 
                        content: { class: 'px-8 pb-8 pt-4 bg-transparent rounded-b-[2rem]' } 
                    }">
                    <template #header>
                        <div class="flex items-center gap-3 w-full">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                                <i class="pi pi-dollar text-emerald-600 dark:text-emerald-400 text-lg font-bold"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xl font-bold text-gray-800 dark:text-white tracking-tight">Registrar Pago</span>
                                <span class="text-xs text-gray-500 dark:text-zinc-400">Para: {{ client.name }}</span>
                            </div>
                        </div>
                    </template>
                    <form @submit.prevent="submitPayment">
                        <div class="flex flex-col gap-5 mt-2">
                            <div class="flex flex-col gap-2">
                                <label for="quote" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Asociar a Cotización (Opcional)</label>
                                <Dropdown id="quote" v-model="paymentForm.quote_id" :options="quoteOptions"
                                    optionLabel="label" optionValue="id" placeholder="Selecciona una cotización" class="!rounded-xl w-full"
                                    :class="{ 'p-invalid': paymentForm.errors.quote_id }" showClear />
                                <small v-if="paymentForm.errors.quote_id" class="p-error">{{ paymentForm.errors.quote_id }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="amount" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Monto del Pago <span class="text-red-500">*</span></label>
                                <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN"
                                    locale="es-MX" class="!rounded-xl w-full" :class="{ 'p-invalid': paymentForm.errors.amount }" required />
                                <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="payment_date" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Fecha del Pago <span class="text-red-500">*</span></label>
                                <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" class="!rounded-xl w-full"
                                    :class="{ 'p-invalid': paymentForm.errors.payment_date }" required />
                                <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label for="notes" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Notas (Opcional)</label>
                                <Textarea id="notes" v-model="paymentForm.notes" rows="2" class="!rounded-xl w-full" />
                            </div>
                            <!-- Nuevo input de Comprobante -->
                            <div class="flex flex-col gap-2">
                                <label for="receipt" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Comprobante (Opcional)</label>
                                <input type="file" id="receipt" @input="paymentForm.receipt = $event.target.files[0]" 
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-zinc-800 dark:file:text-emerald-400 transition-colors cursor-pointer" 
                                    accept=".pdf,.jpg,.jpeg,.png" />
                            </div>
                        </div>
                    </form>
                    <template #footer>
                        <div class="flex justify-end gap-3 mt-4 w-full">
                            <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" class="!rounded-xl font-medium" />
                            <Button label="Guardar Pago" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" class="!rounded-xl font-medium bg-emerald-600 border-emerald-600 hover:bg-emerald-700 !text-[var(--primary-text-color)]" />
                        </div>
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