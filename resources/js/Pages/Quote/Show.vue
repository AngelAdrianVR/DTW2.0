<script setup>
import { computed } from 'vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
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

// Mapeo de colores para el estilo "Badge" moderno
const getStatusClasses = (status) => {
    const base = "px-3 py-1 rounded-full text-xs font-semibold tracking-wide border";
    const styles = {
        'Pendiente': 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
        'Enviado': 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
        'Aceptado': 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
        'Rechazado': 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-800',
    };
    return `${base} ${styles[status] || 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800 dark:text-gray-400'}`;
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
    <Head :title="`Cotización #${quote.id}`" />
    
    <AppLayout title="Detalle de Cotización">
        <div class="min-h-screen  pb-12 font-sans selection:bg-blue-100 dark:selection:bg-blue-900">
            <!-- Top Navigation Bar / Breadcrumb area -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-6">
                <nav class="flex items-center text-sm text-gray-500 mb-6">
                    <Link :href="route('quotes.index')" class="group flex items-center hover:text-blue-600 transition-colors duration-200">
                        <div class="w-8 h-8 rounded-full bg-white dark:bg-gray-800 flex items-center justify-center shadow-sm border border-gray-200 dark:border-gray-700 mr-3 group-hover:border-blue-200">
                            <i class="pi pi-arrow-left text-xs"></i>
                        </div>
                        <span class="font-medium">Cotizaciones</span>
                    </Link>
                </nav>

                <!-- Header Content -->
                <header class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                Cotización #{{ quote.id }}
                            </h1>
                            <span :class="getStatusClasses(quote.status)">
                                {{ quote.status }}
                            </span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 text-lg">
                            {{ quote.title }}
                        </p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                         <a :href="route('quotes.print', quote.id)" target="_blank" class="no-underline">
                            <Button label="Imprimir" icon="pi pi-print" severity="secondary" outlined rounded class="!bg-white dark:!bg-transparent !border-gray-300 dark:!border-gray-600 !text-gray-700 dark:!text-gray-300 shadow-sm" />
                        </a>
                        <Link :href="route('quotes.edit', quote.id)">
                            <Button label="Editar" icon="pi pi-pencil" rounded class="!bg-blue-600 !border-blue-600 hover:!bg-blue-700 shadow-md shadow-blue-200 dark:shadow-none" />
                        </Link>
                    </div>
                </header>
            </div>

            <!-- Main Content Grid -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    
                    <!-- Columna Izquierda: Información Principal (8/12 cols) -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Tarjeta de Detalles -->
                        <section class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-[0_2px_20px_rgba(0,0,0,0.04)] border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="p-8">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                                    <i class="pi pi-info-circle mr-2 text-blue-500"></i>
                                    Información General
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-8 gap-x-12">
                                    <div class="group">
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Cliente</h4>
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-lg mr-3">
                                                {{ quote.client?.name ? quote.client.name.charAt(0) : '?' }}
                                            </div>
                                            <p class="text-lg font-medium text-gray-900 dark:text-white">{{ quote.client?.name || 'Sin Cliente' }}</p>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Vigencia</h4>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                            <i class="pi pi-calendar mr-2 text-gray-400"></i>
                                            {{ formatDate(quote.valid_until) }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Duración Estimada</h4>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                            <i class="pi pi-clock mr-2 text-gray-400"></i>
                                            {{ quote.work_days }} días hábiles
                                        </p>
                                    </div>

                                     <div>
                                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Método de Pago</h4>
                                        <p class="text-lg font-medium text-gray-900 dark:text-white flex items-center">
                                            <i class="pi pi-wallet mr-2 text-gray-400"></i>
                                            {{ quote.payment_type }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-10 pt-8 border-t border-gray-100 dark:border-gray-700">
                                     <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Descripción del Proyecto</h4>
                                     <div class="prose prose-blue prose-lg max-w-none text-gray-600 dark:text-gray-300 leading-relaxed" v-html="quote.description"></div>
                                </div>
                            </div>
                        </section>

                         <!-- Sección de Archivos (Móvil/Tablet: Se muestra aquí para flujo natural, Desktop: Se mantiene en columna derecha si se prefiere, pero la UI moderna suele agrupar contexto) -->
                         <!-- En este diseño mantendremos la estructura de 2 columnas original del usuario pero mejorada visualmente -->
                    </div>

                    <!-- Columna Derecha: Finanzas y Acciones (4/12 cols) -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- Tarjeta de Finanzas -->
                        <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-[0_4px_25px_rgba(0,0,0,0.05)] border border-gray-100 dark:border-gray-700 overflow-hidden relative">
                            <!-- Decoración de fondo -->
                            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 rounded-full bg-blue-50 dark:bg-blue-900/20 blur-2xl pointer-events-none"></div>

                            <div class="p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6">Resumen Financiero</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center text-gray-600 dark:text-gray-400">
                                        <span>Subtotal</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(quote.amount) }}</span>
                                    </div>
                                    
                                    <div v-if="quote.percentage_discount > 0" class="flex justify-between items-center text-rose-500 bg-rose-50 dark:bg-rose-900/10 px-3 py-2 rounded-lg -mx-3">
                                        <span class="flex items-center text-sm"><i class="pi pi-tag mr-2"></i> Descuento ({{ quote.percentage_discount }}%)</span>
                                        <span class="font-bold">- {{ formatCurrency((quote.amount * quote.percentage_discount) / 100) }}</span>
                                    </div>

                                    <div class="pt-6 mt-2 border-t border-dashed border-gray-200 dark:border-gray-700">
                                        <div class="flex justify-between items-end">
                                            <span class="text-gray-500 dark:text-gray-400 text-sm font-medium uppercase tracking-wide">Total a Pagar</span>
                                            <span class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight">{{ formatCurrency(totalWithDiscount) }}</span>
                                        </div>
                                        <p class="text-right text-xs text-gray-400 mt-1">Impuestos no incluidos</p>
                                    </div>
                                </div>
                            </div>
                            <!-- Botón rápido de acción principal -->
                             <!-- <div class="px-6 pb-6">
                                <a :href="`mailto:${quote.client?.email}?subject=Cotización ${quote.id}&body=Adjunto cotización...`" class="block w-full">
                                    <Button label="Enviar por Correo" icon="pi pi-send" class="w-full !bg-gray-900 dark:!bg-white !text-white dark:!text-gray-900 !border-none hover:!bg-gray-700 dark:hover:!bg-gray-200" rounded />
                                </a>
                            </div> -->
                        </div>

                        <!-- Tarjeta de Archivos -->
                        <div class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center">
                                <i class="pi pi-paperclip mr-2 text-gray-400"></i>
                                Adjuntos y Facturas
                            </h3>

                            <form @submit.prevent="submitInvoice" class="mb-6 relative group">
                                <div class="relative flex items-center justify-center w-full">
                                    <label for="invoice-input" class="flex flex-col items-center justify-center w-full h-28 border-2 border-gray-200 border-dashed rounded-2xl cursor-pointer bg-gray-50 dark:bg-gray-800/50 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 group-hover:border-blue-300">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i v-if="!form.invoice_file" class="pi pi-cloud-upload text-3xl text-gray-400 mb-2 group-hover:text-blue-500 transition-colors"></i>
                                            <i v-else class="pi pi-check-circle text-3xl text-emerald-500 mb-2"></i>
                                            <p v-if="!form.invoice_file" class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Click para subir</span> factura</p>
                                            <p v-else class="text-sm text-emerald-600 font-medium truncate max-w-[80%]">{{ form.invoice_file.name }}</p>
                                        </div>
                                        <input id="invoice-input" type="file" @input="form.invoice_file = $event.target.files[0]" class="hidden" accept=".pdf,.xml,.jpg,.png,.jpeg" />
                                    </label>
                                </div>
                                <div v-if="form.invoice_file" class="mt-3 animate-fade-in-up">
                                    <Button type="submit" label="Subir Archivo" icon="pi pi-upload" class="w-full" :loading="form.processing" rounded />
                                </div>
                                <p v-if="form.errors.invoice_file" class="text-xs text-red-500 mt-2 text-center">{{ form.errors.invoice_file }}</p>
                                <ProgressBar v-if="form.progress" :value="form.progress.percentage" class="mt-3 h-1" :showValue="false" />
                            </form>

                            <div class="space-y-3">
                                <div v-if="invoices.length > 0">
                                    <ul class="space-y-3">
                                        <li v-for="file in invoices" :key="file.id" class="group flex items-center justify-between p-3 rounded-xl bg-white dark:bg-gray-700/50 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-all duration-200">
                                            <a :href="file.original_url" target="_blank" rel="noopener noreferrer" class="flex items-center flex-1 min-w-0 mr-3">
                                                <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0 text-red-500">
                                                    <i class="pi pi-file-pdf text-xl"></i>
                                                </div>
                                                <div class="ml-3 flex-1 min-w-0">
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate group-hover:text-blue-600 transition-colors">
                                                        {{ file.file_name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500 truncate">{{ (file.size / 1024).toFixed(2) }} KB</p>
                                                </div>
                                            </a>
                                            <Button @click="deleteFile(file.id)" icon="pi pi-trash" class="!w-8 !h-8 !p-0 !text-gray-400 hover:!text-red-500 hover:!bg-red-50 dark:hover:!bg-red-900/30 transition-colors" text rounded />
                                        </li>
                                    </ul>
                                </div>
                                <div v-else class="text-center py-8 px-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                                    <i class="pi pi-folder-open text-gray-300 dark:text-gray-600 text-4xl mb-3 block"></i>
                                    <p class="text-sm text-gray-500">No hay archivos adjuntos aún.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Transiciones suaves personalizadas */
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Tipografía mejorada para el contenido HTML */
.prose {
    line-height: 1.8;
}
.prose h1, .prose h2, .prose h3 {
    color: inherit;
    font-weight: 700;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
}
.prose ul {
    list-style-type: disc;
    padding-left: 1.5em;
    margin-top: 1em;
    margin-bottom: 1em;
}
.prose p {
    margin-bottom: 1em;
}
</style>