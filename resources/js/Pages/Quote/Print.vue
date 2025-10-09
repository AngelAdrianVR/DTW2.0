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
    if (!dateString) return '';
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
    const amount = props.quote.amount || 0;
    const discount = props.quote.percentage_discount || 0;
    if (discount <= 0 || discount > 100) {
        return amount;
    }
    const discountAmount = (amount * discount) / 100;
    return amount - discountAmount;
});

// --- METHODS ---
const printQuote = () => {
    window.print();
};

</script>

<template>
    <Head :title="`Cotización ${quote.quote_code}`" />
    <div class="py-6 print:bg-white font-sans">
        <!-- Botones de Acción (se ocultan al imprimir) -->
        <div class="max-w-4xl mx-auto mb-4 px-4 sm:px-6 lg:px-8 print:hidden">
            <div class="flex justify-between items-center">
                <Link :href="route('quotes.index')">
                    <Button label="Volver al Listado" icon="pi pi-arrow-left" severity="secondary" outlined />
                </Link>
                <Button @click="printQuote" label="Imprimir Cotización" icon="pi pi-print" />
            </div>
        </div>

        <!-- Hoja de Cotización -->
        <div id="quote-sheet" class="max-w-4xl mx-auto bg-white shadow-2xl rounded-lg relative">
            <!-- Marca de agua de fondo -->
            <div class="absolute inset-0 flex items-center justify-center z-0 opacity-5 pointer-events-none print:hidden">
                 <img src="/images/black_logo.png" alt="Logo Watermark" class="h-64 transform -rotate-12">
            </div>

            <div class="relative z-10">
                <!-- Encabezado con decoración -->
                <header class="relative p-4 md:p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <img src="/images/black_logo.png" alt="Logo de la Empresa" class="h-16">
                             <div class="mt-3 text-xs text-gray-500">
                                <p>Zapopan, Jalisco, México</p>
                                <p>contacto@dtw.com.mx</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <h2 class="text-3xl font-bold uppercase text-gray-800 tracking-wider">Cotización</h2>
                            <p class="font-mono mt-1 text-gray-500">{{ quote.quote_code }}</p>
                            <p class="mt-3 text-sm">Fecha de Emisión: <span class="font-semibold text-gray-700">{{ formatDate(quote.created_at) }}</span></p>
                            <p class="text-sm">Válido hasta: <span class="font-semibold text-gray-700">{{ formatDate(quote.valid_until) }}</span></p>
                        </div>
                    </div>
                </header>
                
                <main class="p-4 md:p-6">
                    <!-- Información del Cliente -->
                    <section class="pb-4 border-b-2 border-dashed">
                        <h3 class="text-sm uppercase font-semibold text-gray-500">Cotización para:</h3>
                        <div v-if="quote.client">
                            <p class="text-xl font-bold text-gray-800">{{ quote.client.name }}</p>
                        </div>
                        <div v-else>
                           <p class="text-gray-500">Cliente no especificado.</p>
                        </div>
                    </section>
                    
                    <!-- Descripción del Servicio -->
                    <section class="mt-6">
                        <div class="border-2 border-gray-100 rounded-lg">
                            <h3 class="text-base font-bold text-gray-800 bg-gray-50 p-3 rounded-t-md">{{ quote.title }}</h3>
                            <div class="prose max-w-none p-3 text-gray-700" v-html="quote.description"></div>
                        </div>
                    </section>

                    <!-- Detalles del Proyecto -->
                     <section class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                        <div class="bg-gray-50 p-3 rounded-lg border-l-4 border-gray-300">
                            <h4 class="font-bold text-gray-800 mb-2">Duración del Proyecto</h4>
                            <p class="text-gray-600">La entrega estimada para la implementación final del proyecto es <strong>{{ quote.work_days }} días hábiles</strong>, iniciando a partir del primer pago al inicio del proyecto.</p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border-l-4 border-gray-300">
                            <h4 class="font-bold text-gray-800 mb-2">Condiciones de Pago</h4>
                            <p class="text-gray-600">{{ quote.Payment_type }}</p>
                            <p class="text-gray-500 text-xs mt-1">Esta cotización no incluye costos adicionales por cambios significativos en el alcance del proyecto.</p>
                        </div>
                    </section>

                    <!-- Secciones Adicionales -->
                    <section v-if="quote.show_process" class="mt-6 text-sm">
                        <h4 class="font-bold text-gray-800 border-b pb-1 mb-2">Nuestro Proceso</h4>
                        <p class="text-gray-600">
                            El proyecto inicia con el diseño de todas las vistas de la aplicación para aprobación del cliente. Una vez aprobado, se procede con la programación y desarrollo. Finalmente, la aplicación se despliega en la nube y se entrega, corrigiendo cualquier error funcional. Se incluye capacitación online para hasta 5 usuarios y un año de soporte técnico integral para asegurar el funcionamiento óptimo del sistema.
                        </p>
                    </section>

                    <section v-if="quote.show_benefits" class="mt-6 text-sm">
                        <h4 class="font-bold text-gray-800 border-b pb-1 mb-2">Beneficios de Adquirir el Software</h4>
                        <ul class="list-disc list-inside space-y-1 text-gray-600">
                            <li><b>Compatibilidad Total:</b> Funciona en computadoras, laptops, tablets y móviles.</li>
                            <li><b>Seguridad en la Nube:</b> Datos protegidos con respaldos automáticos.</li>
                            <li><b>Acceso Remoto:</b> Accede a tu información desde cualquier lugar.</li>
                            <li><b>Escalabilidad:</b> El sistema crece junto a tu empresa.</li>
                            <li><b>Soporte Técnico:</b> Asistencia eficiente para resolver cualquier duda.</li>
                            <li><b>Personalización:</b> Adaptamos el sistema a los colores y logo de tu marca.</li>
                            <li>Un solo pago, usuarios ilimitados.</li>
                        </ul>
                    </section>
                </main>
                
                <!-- Footer con Totales -->
                <footer class="p-6 bg-gray-50 rounded-b-lg">
                    <div class="border-t-2 border-gray-200 border-dashed pt-4">
                         <!-- Totales -->
                        <div class="w-full sm:w-2/3 md:w-1/2 ml-auto text-right space-y-2 text-gray-700">
                            <div class="flex justify-between">
                                <span class="font-semibold">Subtotal:</span>
                                <span>{{ formatCurrency(quote.amount) }}</span>
                            </div>
                            <div v-if="quote.percentage_discount > 0" class="flex justify-between text-red-600">
                                <span class="font-semibold">Descuento ({{ quote.percentage_discount }}%):</span>
                                <span>- {{ formatCurrency((quote.amount * quote.percentage_discount) / 100) }}</span>
                            </div>
                            <div class="flex justify-between items-center bg-white p-2 rounded-md shadow-inner">
                                <span class="text-lg font-bold text-gray-800">Total:</span>
                                <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalWithDiscount) }} <span class="text-sm font-normal">MXN</span></span>
                            </div>
                            <p class="text-xs text-gray-500 text-right">Los precios no incluyen IVA.</p>
                        </div>
                    </div>

                    <!-- Datos Bancarios -->
                    <div v-if="quote.show_bank_info" class="mt-6 border border-gray-200 bg-white p-3 rounded-lg text-xs">
                        <h4 class="font-bold text-gray-800 mb-2">Datos para la realización de pagos</h4>
                        <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-gray-600">
                            <p><b>Beneficiario:</b></p><p>Miguel Osvaldo Vázquez Rodríguez</p>
                            <p><b>Banco:</b></p><p>NU México</p>
                            <p><b>No. de cuenta:</b></p><p>00017049244</p>
                            <p><b>Clabe:</b></p><p>638180000170492445</p>
                        </div>
                    </div>
                    
                    <div class="text-center text-gray-500 text-xs mt-8">
                        <p>Gracias por su confianza.</p>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>

<style>
/* Estilos para la vista previa de la cotización */
.prose {
    font-size: 0.875rem; /* Reducido de 1rem */
    line-height: 1.6;    /* Reducido de 1.75 */
}
.prose p, .prose ul {
    margin-top: 0.5em;
    margin-bottom: 0.5em;
}
.prose ul {
    list-style-type: disc;
    padding-left: 1.25rem;
}
.prose li p {
    margin: 0;
}

/* Estilos específicos para impresión */
@media print {
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .print\:hidden {
        display: none;
    }
    .print\:bg-white {
        background-color: white;
    }
    #quote-sheet {
        box-shadow: none;
        border-radius: 0;
        margin: 0;
        max-width: 100%;
    }
    .prose {
        color: #000 !important;
    }
    /* Oculta fondos que no sean blancos para ahorrar tinta */
    .bg-gray-50, .bg-gray-100 {
        background-color: white !important;
    }
}
</style>
