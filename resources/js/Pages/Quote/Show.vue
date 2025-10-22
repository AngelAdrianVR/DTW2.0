<script setup>
import { computed } from 'vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';

// --- PROPS ---
const props = defineProps({
    quote: {
        type: Object,
        required: true
    },
});

// --- FORMULARIO PARA SUBIR ARCHIVOS ---
const form = useForm({
    invoice_file: null,
});

const submitInvoice = () => {
    form.post(route('quotes.invoices.store', props.quote.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('invoice_file');
            const fileInput = document.getElementById('invoice-input');
            if (fileInput) {
                fileInput.value = '';
            }
        },
    });
};

// --- COMPUTED PROPERTIES ---
const invoices = computed(() => {
    return props.quote.media?.filter(file => file.collection_name === 'invoices') || [];
});

const totalWithDiscount = computed(() => {
    const amount = parseFloat(props.quote.amount) || 0;
    const discount = parseFloat(props.quote.percentage_discount) || 0;
    if (discount <= 0 || discount > 100) return amount;
    const discountAmount = (amount * discount) / 100;
    return amount - discountAmount;
});

const getStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'info',
        'Enviado': 'warn',
        'Aceptado': 'success',
        'Rechazado': 'danger',
    };
    return statuses[status] || 'secondary';
};


// --- MÉTODOS ---
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) return '$0.00';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(numericValue);
};

const deleteFile = (fileId) => {
    if (confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
        router.delete(route('quotes.invoices.destroy', { quote: props.quote.id, media: fileId }), {
            preserveScroll: true,
        });
    }
};

</script>

<template>
    <Head :title="`Detalle de Cotización ${quote.quote_code}`" />
    <AppLayout title="Detalle de Cotización">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <header class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <Link :href="route('quotes.index')" class="text-sm text-gray-500 dark:text-gray-400 flex items-center mb-2">
                            <i class="pi pi-arrow-left mr-2"></i>
                            Regresar a Cotizaciones
                        </Link>
                        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">
                            Cotización <span class="font-mono">{{ quote.quote_code }}</span>
                        </h1>
                    </div>
                    <div class="flex items-center gap-2">
                         <Link :href="route('quotes.print', quote.id)" target="_blank">
                            <Button label="Imprimir" icon="pi pi-print" severity="secondary" outlined />
                        </Link>
                        <Link :href="route('quotes.edit', quote.id)">
                            <Button label="Editar" icon="pi pi-pencil" />
                        </Link>
                    </div>
                </header>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Columna Izquierda: Detalles y Finanzas -->
                    <div class="lg:col-span-2 flex flex-col gap-6">
                        <Card>
                            <template #title>
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-bold text-gray-800 dark:text-gray-200">{{ quote.title }}</span>
                                    <Tag :value="quote.status" :severity="getStatusSeverity(quote.status)" />
                                </div>
                            </template>
                            <template #content>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-700">
                                    <div>
                                        <h4 class="font-semibold text-gray-500 dark:text-gray-400 text-sm">CLIENTE</h4>
                                        <p class="text-lg dark:text-white">{{ quote.client?.name || 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-500 dark:text-gray-400 text-sm">VÁLIDO HASTA</h4>
                                        <p class="text-lg dark:text-white">{{ formatDate(quote.valid_until) }}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-500 dark:text-gray-400 text-sm">DURACIÓN PROYECTO</h4>
                                        <p class="text-lg dark:text-white">{{ quote.work_days }} días hábiles</p>
                                    </div>
                                     <div>
                                        <h4 class="font-semibold text-gray-500 dark:text-gray-400 text-sm">TIPO DE PAGO</h4>
                                        <p class="text-lg dark:text-white">{{ quote.payment_type }}</p>
                                    </div>
                                </div>
                                <div class="mt-6 border-t pt-4">
                                     <h4 class="font-semibold text-gray-500 dark:text-gray-400 text-sm mb-2">DESCRIPCIÓN</h4>
                                     <div class="prose max-w-none dark:text-white" v-html="quote.description"></div>
                                </div>
                            </template>
                        </Card>

                        <Card>
                             <template #title><span class="font-bold">Finanzas</span></template>
                             <template #content>
                                 <div class="space-y-3">
                                    <div class="flex justify-between items-center text-lg">
                                        <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                        <span class="font-medium">{{ formatCurrency(quote.amount) }}</span>
                                    </div>
                                     <div v-if="quote.percentage_discount > 0" class="flex justify-between items-center text-lg text-red-600">
                                        <span class="text-gray-600">Descuento ({{ quote.percentage_discount }}%):</span>
                                        <span class="font-medium">- {{ formatCurrency((quote.amount * quote.percentage_discount) / 100) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-2xl font-bold border-t pt-3 mt-3">
                                        <span class="text-gray-800 dark:text-gray-200">Total:</span>
                                        <span class="text-blue-600">{{ formatCurrency(totalWithDiscount) }}</span>
                                    </div>
                                 </div>
                             </template>
                        </Card>
                    </div>

                    <!-- Columna Derecha: Archivos -->
                    <div class="lg:col-span-1">
                        <Card class="h-full">
                            <template #title><span class="font-bold">Facturas y Archivos</span></template>
                            <template #content>
                                <form @submit.prevent="submitInvoice" class="mb-6">
                                    <div class="flex items-center space-x-2">
                                        <input id="invoice-input" type="file" @input="form.invoice_file = $event.target.files[0]" class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-2 file:py-2 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"/>
                                        <Button type="submit" icon="pi pi-upload" rounded :disabled="form.processing || !form.invoice_file" :loading="form.processing" />
                                    </div>
                                    <ProgressBar v-if="form.progress" :value="form.progress.percentage" class="mt-3 h-2" />
                                    <p v-if="form.errors.invoice_file" class="text-sm text-red-600 mt-2">{{ form.errors.invoice_file }}</p>
                                </form>

                                <div v-if="invoices.length > 0">
                                    <ul class="space-y-2">
                                        <li v-for="file in invoices" :key="file.id" class="px-3 py-2 flex items-center justify-between text-sm rounded-lg border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <a :href="file.original_url" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 font-medium flex items-center truncate mr-2">
                                                <i class="pi pi-file mr-2 text-gray-400"></i>
                                                <span class="truncate">{{ file.file_name }}</span>
                                            </a>
                                            <Button @click="deleteFile(file.id)" icon="pi pi-trash" severity="danger" text rounded />
                                        </li>
                                    </ul>
                                </div>
                                <div v-else class="text-center text-gray-500 dark:text-gray-400 py-4">
                                    <i class="pi pi-folder-open text-2xl mb-2"></i>
                                    <p>No hay archivos adjuntos.</p>
                                </div>
                            </template>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.prose {
    font-size: 1rem;
    line-height: 1.75;
    /* Propiedades para manejar el desbordamiento de texto */
    overflow-wrap: break-word;
    word-wrap: break-word;
    word-break: break-word;
}
.prose :first-child {
    margin-top: 0;
}
.prose :last-child {
    margin-bottom: 0;
}
</style>
