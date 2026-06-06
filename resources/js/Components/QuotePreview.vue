<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: { type: Object, required: true }, // Recibe tanto el form como la quote
    client: { type: Object, default: () => ({}) },
    quoteId: { type: [String, Number], default: 'XXXX' },
    createdAt: { type: [String, Date], required: true },
    currency: { type: String, default: 'MXN' }
});

// --- VARIABLES CALCULADAS ---
const activeAmount = computed(() => {
    if (props.currency === 'USD' && props.data.amount_usd) {
        return parseFloat(props.data.amount_usd) || 0;
    }
    return parseFloat(props.data.amount) || 0;
});

const activeTotalWithDiscount = computed(() => {
    const amount = activeAmount.value;
    const discount = parseFloat(props.data.percentage_discount) || 0;
    if (discount <= 0 || discount > 100) return amount;
    return amount - (amount * discount / 100);
});

// ISR Retention (RESICO): 1.25% on subtotal, only for Persona Moral + needs_invoice
const isrRetention = computed(() => {
    if (!props.data.needs_invoice) return 0;
    if (!props.client || props.client.regimen_fiscal !== 'persona_moral') return 0;
    return Math.round(activeTotalWithDiscount.value * 0.0125 * 100) / 100;
});

const ivaAmount = computed(() => {
    if (!props.data.needs_invoice) return 0;
    return Math.round(activeTotalWithDiscount.value * 0.16 * 100) / 100;
});

const grandTotal = computed(() => {
    let total = activeTotalWithDiscount.value;
    if (props.data.needs_invoice) {
        total = total + ivaAmount.value;
    }
    total = total - isrRetention.value;
    return total;
});

const formattedCreatedAt = computed(() => {
    const date = new Date(props.createdAt || Date.now());
    return isNaN(date) ? '---' : date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
});

const validUntilFormatted = computed(() => {
    if (!props.data.valid_until) return '---';
    const date = new Date(props.data.valid_until);
    return isNaN(date) ? '---' : date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
});

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    const locale = props.currency === 'USD' ? 'en-US' : 'es-MX';
    return new Intl.NumberFormat(locale, { style: 'currency', currency: props.currency }).format(value);
};

// Sanitizador ULTRA REFORZADO para evitar palabras cortadas y forzar texto a la izquierda
const cleanDescription = computed(() => {
    if (!props.data.description) return '<p class="text-gray-400 italic">Descripción de los servicios...</p>';
    let html = props.data.description;
    
    // Limpieza agresiva de estilos problemáticos inyectados por el editor Quill
    // Eliminamos cualquier instrucción que rompa palabras
    html = html.replace(/word-break:\s*[^;]+;?/gi, '');
    html = html.replace(/word-wrap:\s*[^;]+;?/gi, '');
    html = html.replace(/overflow-wrap:\s*[^;]+;?/gi, '');
    // Eliminamos justificados forzados para garantizar la alineación a la izquierda
    html = html.replace(/text-align:\s*justify;?/gi, '');
    // Forzamos que las palabras se mantengan intactas y el texto vaya a la izquierda
    html = html.replace(/white-space:\s*[^;]+;?/gi, '');
    
    return html;
});
</script>

<template>
    <!-- 
      Se cambió el 'absolute top-0' por 'border-t-[10px]'. 
      Esto asegura que si la impresora mete un margen blanco, el color morado no se corte,
      sino que se vea como el borde superior de un documento estructurado.
    -->
    <div class="relative w-full bg-white text-[#1a1a1a] font-sans overflow-hidden">
        
        <!-- Detalle de Lujo (Marca de agua sutil en la esquina) -->
        <div class="absolute top-2 right-0 w-full h-20 bg-[#6c5b7b] opacity-[0.08] rounded-[0px] pointer-events-none"></div>

        <div class="px-6 py-3 relative z-10">
            <!-- Header -->
            <header class="flex justify-between items-end mb-2 pb-4 border-b border-gray-100">
                <div>
                    <img src="/images/black_logo.png" alt="DTW Logo" class="h-[68px] object-contain" />
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-black tracking-tight uppercase text-[#1a1a1a] mb-0.5">Cotización</h1>
                    <div class="flex items-center justify-end gap-3 text-[10px] text-gray-500 font-medium">
                        <span class="text-[#6c5b7b] font-bold bg-[#6c5b7b]/10 px-2 py-0.5 rounded border border-[#6c5b7b]/20">COT-{{ String(quoteId).padStart(4, '0') }}</span>
                        <span>Emisión: <span class="font-bold text-gray-700">{{ formattedCreatedAt }}</span></span>
                        <span>Vence: <span class="font-bold text-gray-700">{{ validUntilFormatted }}</span></span>
                    </div>
                </div>
            </header>

            <!-- Datos del Cliente y Proyecto -->
            <section class="grid grid-cols-[1fr_1.2fr] gap-6 mb-6 mt-2">
                <!-- Caja de cliente con diseño premium -->
                <div class="bg-[#fff] border-l-4 border-[#6c5b7b] p-2 rounded-r-lg">
                    <h3 class="text-[10px] font-bold text-[#6c5b7b] uppercase tracking-widest mb-1.5">Cliente:</h3>
                    <p class="text-[13px] font-extrabold text-gray-900 mb-0.5">{{ client?.name || 'Cliente Mostrador' }}</p>
                    <p v-if="client?.address" class="text-[10px] text-gray-600 leading-tight mt-1">{{ client.address }}</p>
                    <p v-if="client?.tax_id" class="text-[10px] text-gray-500 mt-1.5 font-mono bg-white inline-block px-1 border border-gray-200 rounded">RFC: {{ client.tax_id }}</p>
                </div>
                <div class="flex flex-col justify-center px-2">
                    <h3 class="text-[10px] font-bold text-[#6c5b7b] uppercase tracking-widest mb-1">Cotización:</h3>
                    <p class="text-base font-bold text-gray-900 leading-tight">{{ data.title || 'Título del Proyecto...' }}</p>
                </div>
            </section>

            <!-- Descripción Principal -->
            <main class="mb-6">
                <h3 class="text-[10px] font-bold text-gray-800 uppercase tracking-widest mb-2 border-b border-gray-200 pb-1">Descripción de los Servicios</h3>
                <!-- Se agrega la clase contenido-html para procesar el texto de Quill -->
                <div class="contenido-html text-gray-800 text-[11px] leading-relaxed" v-html="cleanDescription"></div>
            </main>

            <!-- Metodología y Beneficios -->
            <section v-if="data.show_process || data.show_benefits" class="grid grid-cols-2 gap-5 mb-6 border-t border-gray-100 pt-4 break-inside-avoid">
                <div v-if="data.show_process">
                    <h4 class="text-[10px] font-bold text-[#6c5b7b] uppercase mb-1">Metodología</h4>
                    <p class="text-[10px] text-gray-600 text-left leading-relaxed">
                        El proyecto inicia con el diseño de vistas para aprobación. Una vez validado, procedemos al desarrollo y programación. Finalmente, realizamos el despliegue en producción y pruebas de calidad, incluyendo capacitación y soporte.
                    </p>
                </div>
                <div v-if="data.show_benefits" :class="!data.show_process ? 'col-span-2' : ''">
                    <h4 class="text-[10px] font-bold text-[#6c5b7b] uppercase mb-1">Beneficios Incluidos</h4>
                    <ul class="text-[10px] text-gray-600 grid grid-cols-2 gap-x-2 gap-y-1 list-disc list-inside">
                        <li>Diseño Adaptable (Móvil)</li>
                        <li>Respaldos Automáticos</li>
                        <li>Acceso Remoto Seguro</li>
                        <li>Escalabilidad Garantizada</li>
                    </ul>
                </div>
            </section>

            <!-- Pie de página (Condiciones, Datos Bancarios y Totales) -->
            <footer class="grid grid-cols-[1fr_250px] gap-6 pt-4 border-t-2 border-[#1a1a1a] break-inside-avoid items-start">
                
                <div class="space-y-4">
                    <div>
                        <h4 class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-1">Condiciones de Pago</h4>
                        <p class="text-[11px] text-gray-800 font-medium leading-snug">{{ data.payment_type || data.Payment_type || 'No especificado' }}</p>
                    </div>
                    
                    <div v-if="data.show_bank_info" class="bg-[#fafafa] p-3 rounded-md border border-gray-100">
                        <h4 class="text-[9px] font-bold text-[#6c5b7b] uppercase mb-1.5">Datos Bancarios</h4>
                        <div class="text-[10px] text-gray-600 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1">
                            <span class="text-gray-400">Banco:</span> <span class="font-bold text-gray-900">NU México</span>
                            <span class="text-gray-400">Beneficiario:</span> <span>Miguel Osvaldo Vázquez Rodríguez</span>
                            <span class="text-gray-400">Cuenta:</span> <span>00017049244</span>
                            <span class="text-gray-400">CLABE:</span> <span class="font-bold text-gray-900">638180000170492445</span>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Totales (Estilo Factura) -->
                <div class="w-full">
                    <div class="text-right mb-3 pb-2 border-b border-gray-200">
                        <h4 class="text-[9px] font-bold text-gray-500 uppercase tracking-widest mb-0.5">Tiempo Estimado</h4>
                        <p class="text-[11px] text-[#1a1a1a] font-bold">{{ data.work_days || 0 }} días hábiles</p>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex justify-between text-[11px] text-gray-600">
                            <span>Subtotal:</span>
                            <span class="font-medium text-gray-900">{{ formatCurrency(activeAmount) }}</span>
                        </div>
                        <div v-if="data.percentage_discount > 0" class="flex justify-between text-[11px] text-[#6c5b7b]">
                            <span>Descuento ({{ data.percentage_discount }}%):</span>
                            <span>- {{ formatCurrency((activeAmount * data.percentage_discount) / 100) }}</span>
                        </div>
                        <div v-if="data.show_tax_breakdown && data.needs_invoice" class="flex justify-between text-[11px] text-blue-600">
                            <span>IVA (16%):</span>
                            <span>+ {{ formatCurrency(ivaAmount) }}</span>
                        </div>
                        <div v-if="data.show_tax_breakdown && isrRetention > 0" class="flex justify-between text-[11px] text-amber-600">
                            <span>Retención ISR (1.25%):</span>
                            <span>- {{ formatCurrency(isrRetention) }}</span>
                        </div>
                        
                        <!-- Total Final -->
                        <div class="flex justify-between items-center bg-[#1a1a1a] text-white p-2.5 rounded-lg mt-2 shadow-sm">
                            <span class="text-[10px] font-bold uppercase tracking-wider">Total Final</span>
                            <span class="text-[15px] font-black leading-none">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                        <p class="text-[10px] text-gray-900 text-right mt-1.5 uppercase tracking-wide">
                            <span v-if="data.show_tax_breakdown && isrRetention > 0">IVA incluido. Retención ISR (RESICO) aplicada. Moneda: {{ currency === 'USD' ? 'USD' : 'MXN' }}.</span>
                            <span v-else-if="data.show_tax_breakdown && data.needs_invoice">IVA incluido. Moneda: {{ currency === 'USD' ? 'USD' : 'MXN' }}.</span>
                            <span v-else>Más IVA. Moneda: {{ currency === 'USD' ? 'USD' : 'MXN' }}.</span>
                        </p>
                    </div>
                </div>
            </footer>
            
            <!-- Marca de agua inferior -->
            <div class="text-center mt-4 pt-4 border-t border-gray-100 text-[8px] text-gray-400 uppercase tracking-widest font-semibold break-inside-avoid">
                <p class="mb-1">Esta cotización no incluye costos adicionales por cambios en el alcance original.</p>
                <p>dtw.com.mx • contacto@dtw.com.mx • Zapopan, Jalisco, MX</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Corrección estricta para el desbordamiento de textos y forzar alineación a la izquierda */
.contenido-html { 
    width: 100%; 
    max-width: 100%;
    text-align: left !important;
}
.contenido-html :deep(*) {
    /* word-break: normal asegura que las palabras no se corten a la mitad */
    word-break: normal !important;
    /* overflow-wrap: break-word permite pasar la palabra ABAJO si es muy larga */
    overflow-wrap: break-word !important; 
    word-wrap: break-word !important;
    white-space: normal !important; 
    max-width: 100%;
    /* Obligamos siempre a alineación izquierda */
    text-align: left !important; 
    hyphens: none !important;
}
.contenido-html :deep(img) { max-width: 100%; height: auto; }
.contenido-html :deep(p) { margin-bottom: 0.6em !important; }
.contenido-html :deep(ul) { list-style-type: disc !important; padding-left: 1.5em !important; margin-bottom: 0.6em !important; }
.contenido-html :deep(ol) { list-style-type: decimal !important; padding-left: 1.5em !important; margin-bottom: 0.6em !important; }
.contenido-html :deep(li) { margin-bottom: 0.25em !important; }
.contenido-html :deep(strong) { font-weight: 700 !important; color: #1a1a1a; } 
.contenido-html :deep(em) { font-style: italic !important; }
</style>