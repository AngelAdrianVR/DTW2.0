<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import Button from 'primevue/button';

// --- PROPS ---
const props = defineProps({
    quote: {
        type: Object,
        required: true
    },
});

// --- HELPERS ---
const formatDate = (dateString) => {
    if (!dateString) return '---';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
};

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) {
        value = 0;
    }
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

// --- COMPUTED PROPERTIES ---
const totalWithDiscount = computed(() => {
    const amount = parseFloat(props.quote.amount) || 0;
    const discount = parseFloat(props.quote.percentage_discount) || 0;
    if (discount <= 0 || discount > 100) {
        return amount;
    }
    const discountAmount = (amount * discount) / 100;
    return amount - discountAmount;
});

// Helper para obtener el tipo de pago con fallback (mayúscula/minúscula)
const paymentType = computed(() => {
    return props.quote.payment_type || props.quote.Payment_type || 'No especificado';
});

// --- METHODS ---
const printQuote = () => {
    window.print();
};
</script>

<template>
    <Head :title="`COT-${quote.id} - ${quote.client?.name || 'Cliente'}`" />
    
    <!-- Contenedor Principal: Se adapta al fondo oscuro de la app cuando se ve en pantalla -->
    <div class="min-h-screen bg-gray-100 dark:bg-zinc-950 py-4 print:bg-white print:p-0 font-sans text-gray-800">
        
        <!-- Barra de Herramientas (Visible solo en pantalla) -->
        <div class="max-w-[21cm] mx-auto mb-4 px-4 print:hidden flex justify-between items-center">
            <Link :href="route('quotes.index')">
                <Button label="Regresar" icon="pi pi-arrow-left" severity="secondary" text rounded class="dark:text-zinc-300 dark:hover:bg-zinc-800"/>
            </Link>
            <Button @click="printQuote" label="Imprimir Documento" icon="pi pi-print" severity="contrast" rounded />
        </div>

        <!-- La Hoja de Papel (Se mantiene blanca siempre para simular el documento) -->
        <div class="max-w-[21cm] mx-auto bg-white shadow-xl rounded-none sm:rounded-xl overflow-hidden print:shadow-none print:rounded-none print:w-full print:max-w-none">
            
            <!-- 1. HEADER -->
            <header class="p-4 md:p-5 pb-3 flex justify-between items-start border-b border-gray-100">
                <div class="flex flex-col">
                    <img src="/images/black_logo.png" alt="Logo Empresa" class="h-12 w-auto object-contain mb-2">
                    <div class="text-xs text-gray-500 space-y-0.5">
                        <p>Zapopan, Jalisco, México</p>
                        <p>contacto@dtw.com.mx</p>
                    </div>
                </div>
                <div class="text-right">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight uppercase">Cotización</h1>
                    <p class="text-lg font-mono text-gray-500 mt-0">COT-{{ String(quote.id).padStart(4, '0') }}</p>
                </div>
            </header>

            <!-- 2. RESUMEN FINANCIERO Y CLIENTE -->
            <section class="bg-gray-50 m-4 mt-2 rounded-xl p-3 print:bg-gray-50 print:break-inside-avoid">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    
                    <!-- Columna Izquierda -->
                    <div class="flex flex-col justify-center">
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest">Cliente:</h3>
                            <p class="text-lg font-bold text-gray-900 leading-none">{{ quote.client?.name || 'Cliente Mostrador' }}</p>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">{{ quote.client?.address || 'Dirección no registrada' }}</p>
                        <p v-if="quote.client?.tax_id" class="text-xs text-gray-500">RFC: {{ quote.client.tax_id }}</p>

                        <div class="mt-3 grid grid-cols-2 gap-4">
                            <div class="flex items-center gap-2">
                                <p class="text-xs text-gray-400 font-bold uppercase">Emisión:</p>
                                <p class="text-sm font-medium">{{ formatDate(quote.created_at) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <p class="text-xs text-gray-400 font-bold uppercase">Vencimiento:</p>
                                <p class="text-sm font-medium text-gray-900">{{ formatDate(quote.valid_until) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="flex flex-col justify-center">
                        <div class="bg-white rounded-lg p-3 border border-gray-100 shadow-sm print:border-gray-200">
                            <div class="space-y-1 mb-2 pb-2 border-b border-gray-100">
                                <div class="flex justify-between text-sm text-gray-600">
                                    <span>Subtotal</span>
                                    <span class="font-medium">{{ formatCurrency(quote.amount) }}</span>
                                </div>
                                <div v-if="quote.percentage_discount > 0" class="flex justify-between text-sm text-green-500">
                                    <span>Descuento ({{ quote.percentage_discount }}%)</span>
                                    <span>- {{ formatCurrency((quote.amount * quote.percentage_discount) / 100) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="text-sm font-bold text-gray-500 uppercase pb-1">Total</span>
                                <span class="text-2xl font-extrabold text-blue-600 print:text-black leading-none">{{ formatCurrency(totalWithDiscount) }}</span>
                            </div>
                             <p class="text-[10px] text-gray-400 text-right mt-0.5">IVA no incluido</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 3. DETALLES DEL PROYECTO -->
            <main class="px-6 md:px-8 pb-4 space-y-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-1">{{ quote.title }}</h2>
                    <div class="h-1 w-12 bg-blue-500 rounded-full mb-3 print:bg-black"></div>
                    <!-- FIX: Agregado 'break-words whitespace-pre-wrap' para ajustar texto largo -->
                    <div class="prose prose-sm max-w-none text-gray-700 leading-snug break-words whitespace-pre-wrap" v-html="quote.description"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-100 break-inside-avoid">
                    <div>
                        <h4 class="flex items-center text-sm font-bold text-gray-900 uppercase mb-1">
                            <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px]"><i class="pi pi-calendar"></i></span>
                            Tiempo de Ejecución
                        </h4>
                        <p class="text-sm text-gray-600 ml-7 leading-tight">
                            Estimado en <span class="font-bold text-gray-900">{{ quote.work_days }} días hábiles</span> a partir de la confirmación.
                        </p>
                    </div>
                    <div>
                        <h4 class="flex items-center text-sm font-bold text-gray-900 uppercase mb-1">
                            <span class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center mr-2 text-[10px]"><i class="pi pi-wallet"></i></span>
                            Condiciones de Pago
                        </h4>
                        <p class="text-sm text-gray-600 ml-7 font-medium leading-tight">
                            {{ paymentType }}
                        </p>
                    </div>
                </div>

                <div v-if="quote.show_process || quote.show_benefits" class="grid grid-cols-1 gap-4 pt-4 border-t border-gray-100 break-inside-avoid">
                    <div v-if="quote.show_process">
                        <h4 class="text-sm font-bold text-gray-900 uppercase mb-1">Metodología</h4>
                        <p class="text-sm text-gray-600 text-justify leading-snug">
                            El proyecto inicia con el diseño de vistas para aprobación. Una vez validado, procedemos al desarrollo y programación. Finalmente, realizamos el despliegue en producción y pruebas de calidad, incluyendo capacitación y garantía de soporte.
                        </p>
                    </div>
                    <div v-if="quote.show_benefits">
                        <h4 class="text-sm font-bold text-gray-900 uppercase mb-1">Beneficios</h4>
                        <ul class="text-sm text-gray-600 grid grid-cols-2 gap-x-2 gap-y-1 list-inside list-disc leading-snug">
                            <li>Compatibilidad Multi-dispositivo</li>
                            <li>Respaldos Automáticos</li>
                            <li>Acceso Remoto Seguro</li>
                            <li>Escalabilidad Garantizada</li>
                            <li>Soporte Técnico Prioritario</li>
                            <li>Personalización de Marca</li>
                        </ul>
                    </div>
                </div>
            </main>

            <!-- 4. FOOTER -->
            <footer class="bg-gray-50 px-6 md:px-8 py-4 border-t border-gray-200 break-inside-avoid print:bg-white print:border-t-2 print:border-black">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div v-if="quote.show_bank_info">
                        <h4 class="text-xs font-bold text-gray-900 uppercase mb-2">Instrucciones de Pago</h4>
                        <div class="bg-white border border-gray-200 rounded-md p-3 text-xs text-gray-600 print:border-gray-300">
                            <div class="grid grid-cols-[80px_1fr] gap-y-0.5">
                                <span class="text-gray-400">Banco:</span> <span class="font-bold text-gray-900">NU México</span>
                                <span class="text-gray-400">Beneficiario:</span> <span class="font-bold text-gray-900">Miguel Osvaldo Vázquez Rodríguez</span>
                                <span class="text-gray-400">Cuenta:</span> <span class="font-mono">00017049244</span>
                                <span class="text-gray-400">CLABE:</span> <span class="font-mono font-bold text-gray-900">638180000170492445</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4 text-[12px] text-gray-500">
                    <p>Esta cotización no incluye costos adicionales que puedan surgir debido a cambios significativos en el alcance del proyecto.</p>
                </div>
            </footer>
        </div>
    </div>
</template>

<style scoped>
/* Estilos específicos para impresión */
@media print {
    @page {
        margin: 0.5cm; /* Márgenes pequeños para aprovechar la hoja */
        size: auto;
    }
    
    body {
        background-color: white;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Asegurar que los colores de fondo (como el gris del resumen) se impriman */
    .bg-gray-50 {
        background-color: #f9fafb !important;
    }
    
    /* Evitar que elementos clave se corten entre páginas */
    .break-inside-avoid {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

/* Tipografía Prose Limpia y Compacta */
.prose {
    font-size: 0.9rem;
    line-height: 1.4; /* COMPACTADO: Reduce la altura de línea general */
}

/* COMPACTADO: Reduce drásticamente el espacio entre párrafos */
.prose :deep(p) {
    margin-bottom: 0.4em !important; 
}

.prose :deep(ul) {
    list-style-type: disc;
    padding-left: 1.25em;
    margin-bottom: 0.4em;
}

.prose :deep(li) {
    margin-bottom: 0.2em;
}
</style>