<script setup>
import { ref } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import QuotePreview from '@/Components/QuotePreview.vue';

// --- PROPS ---
const props = defineProps({
    quote: {
        type: Object,
        required: true
    },
});

// --- CURRENCY TOGGLE (Local State) ---
const currencyView = ref('MXN');

const printQuote = () => {
    window.print();
};
</script>

<template>
    <Head :title="`COT-${quote.id} - ${(quote.client?.name || 'Cliente').replace('DTW2', 'DTW')}`" />
    
    <div class="min-h-screen bg-gray-100 dark:bg-zinc-950 py-6 print:bg-white print:p-0 print:py-0 font-sans text-gray-800 flex flex-col items-center">
        
        <!-- Barra de Herramientas Superior -->
        <div class="w-full max-w-[21cm] mb-4 print:hidden bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-800 p-3 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <Link :href="route('quotes.index')">
                    <Button icon="pi pi-arrow-left" severity="secondary" text rounded class="dark:text-zinc-300"/>
                </Link>
                
                <div class="flex gap-1 bg-gray-100 dark:bg-zinc-800 p-1 rounded-lg" v-if="quote.amount_usd">
                    <button @click="currencyView = 'MXN'" :class="['px-3 py-1 text-xs font-bold rounded-md transition-colors', currencyView === 'MXN' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700']">MXN</button>
                    <button @click="currencyView = 'USD'" :class="['px-3 py-1 text-xs font-bold rounded-md transition-colors', currencyView === 'USD' ? 'bg-white shadow text-blue-600' : 'text-gray-500 hover:text-gray-700']">USD</button>
                </div>
            </div>
            
            <Button @click="printQuote" label="Imprimir Documento" icon="pi pi-print" severity="contrast" class="!rounded-lg" size="small" />
        </div>

        <!-- Contenedor estricto para impresión -->
        <div class="print-container text-left w-full max-w-[21cm] bg-white shadow-xl sm:rounded-none print:shadow-none print:max-w-none print:w-full">
            <QuotePreview 
                :data="quote" 
                :client="quote.client" 
                :quoteId="quote.id" 
                :createdAt="quote.created_at" 
                :currency="currencyView" 
            />
        </div>

    </div>
</template>

<style scoped>
/* Solución forzada para evitar que las palabras se rompan a la mitad.
  Usamos :deep(*) para que aplique a todo el contenido dentro de QuotePreview.vue 
*/
.print-container :deep(p),
.print-container :deep(span),
.print-container :deep(div),
.print-container :deep(td),
.print-container :deep(th),
.print-container :deep(li) {
    word-break: normal !important; /* Evita romper a mitad de la palabra */
    overflow-wrap: break-word !important; /* Salta de línea la palabra completa si no cabe */
    white-space: normal !important;
    hyphens: none !important; /* Desactiva los guiones automáticos por si el navegador los pone */
}

@media print {
    /* Forzamos a Chrome/Edge a quitar los márgenes de fábrica */
    @page { 
        margin: 0 !important; 
        size: letter portrait; /* o A4 portrait */
    }
    
    body { 
        background-color: white !important; 
        -webkit-print-color-adjust: exact !important; 
        print-color-adjust: exact !important; 
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Como quitamos los márgenes físicos del navegador, le agregamos un padding interno 
       para que la tinta no llegue exactamente al ras del papel */
    .print-container {
        padding: 0.5cm 0cm !important;
        box-sizing: border-box !important;
        width: 100% !important;
        height: auto !important;
    }
}
</style>