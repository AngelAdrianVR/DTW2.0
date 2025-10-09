<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Editor from 'primevue/editor';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

// --- PROPS ---
const props = defineProps({
    clients: {
        type: Array,
        required: true
    },
    quote: {
        type: Object,
        required: true
    }
});

// --- STATE MANAGEMENT ---
// Inicializamos el formulario con los datos de la cotización existente.
const form = useForm({
    client_id: props.quote.client_id,
    title: props.quote.title,
    description: props.quote.description,
    amount: props.quote.amount,
    valid_until: new Date(props.quote.valid_until), // Convertimos la fecha a un objeto Date
    payment_type: props.quote.Payment_type, // Ojo: El nombre de la propiedad viene con 'P' mayúscula
    work_days: props.quote.work_days,
    percentage_discount: props.quote.percentage_discount,
    show_process: Boolean(props.quote.show_process),
    show_benefits: Boolean(props.quote.show_benefits),
    show_bank_info: Boolean(props.quote.show_bank_info),
});

// --- Opciones para el select de tipo de pago ---
const paymentOptions = ref([
    'Pago en una sola exhibición',
    '2 pagos (50% al inicio y 50% a la entrega del proyecto)',
    '3 pagos ( 30% al inicio, 40% al desarrollo y 30% a la entrega)'
]);


// --- COMPUTED PROPERTIES ---
const selectedClient = computed(() => {
    if (!form.client_id) return null;
    return props.clients.find(c => c.id === form.client_id);
});

const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});

const validUntilFormatted = computed(() => {
    if (!form.valid_until) return '';
    const date = new Date(form.valid_until);
    return date.toLocaleDateString('es-ES', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });
});

// Calcula el total con el descuento aplicado
const totalWithDiscount = computed(() => {
    const amount = form.amount || 0;
    const discount = form.percentage_discount || 0;
    if (discount <= 0 || discount > 100) {
        return amount;
    }
    const discountAmount = (amount * discount) / 100;
    return amount - discountAmount;
});

// --- METHODS ---
// Actualizamos el método para que use PUT a la ruta de update
const submit = () => {
    form.put(route('quotes.update', props.quote.id), {
        preserveScroll: true,
    });
};

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) {
        value = 0;
    }
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

</script>

<template>
    <AppLayout title="Editar Cotización">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                        
                        <!-- Columna del Formulario -->
                        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                            <header class="mb-6">
                                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Editar Cotización #{{ quote.quote_code }}</h1>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">Modifica los datos para actualizar la cotización.</p>
                            </header>

                            <div class="space-y-6">
                                <!-- Cliente -->
                                <div class="flex flex-col gap-2">
                                    <label for="client" class="font-semibold dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
                                    <Dropdown id="client" v-model="form.client_id" :options="clients" optionLabel="name" optionValue="id" placeholder="Selecciona un cliente" class="w-full" :class="{ 'p-invalid': form.errors.client_id }" />
                                    <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                </div>
                                
                                <!-- Título -->
                                <div class="flex flex-col gap-2">
                                    <label for="title" class="font-semibold dark:text-gray-300">Título de la Cotización <span class="text-red-500">*</span></label>
                                    <InputText id="title" v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
                                    <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                                </div>

                                <!-- Monto, Descuento y Válido hasta -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="amount" class="font-semibold dark:text-gray-300">Monto Total <span class="text-red-500">*</span></label>
                                        <InputNumber id="amount" v-model="form.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': form.errors.amount }" />
                                        <small v-if="form.errors.amount" class="p-error">{{ form.errors.amount }}</small>
                                    </div>
                                     <div class="flex flex-col gap-2">
                                        <label for="percentage_discount" class="font-semibold dark:text-gray-300">Descuento (%)</label>
                                        <InputNumber id="percentage_discount" v-model="form.percentage_discount" :min="0" :max="100" suffix=" %" :class="{ 'p-invalid': form.errors.percentage_discount }" />
                                        <small v-if="form.errors.percentage_discount" class="p-error">{{ form.errors.percentage_discount }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label for="valid_until" class="font-semibold dark:text-gray-300">Válido hasta <span class="text-red-500">*</span></label>
                                        <DatePicker id="valid_until" v-model="form.valid_until" dateFormat="dd/mm/yy" :class="{ 'p-invalid': form.errors.valid_until }" />
                                        <small v-if="form.errors.valid_until" class="p-error">{{ form.errors.valid_until }}</small>
                                    </div>
                                </div>

                                <!-- Días Hábiles y Forma de Pago -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="work_days" class="font-semibold dark:text-gray-300">Días Hábiles del Proyecto <span class="text-red-500">*</span></label>
                                        <InputNumber id="work_days" v-model="form.work_days" :min="1" :class="{ 'p-invalid': form.errors.work_days }" />
                                        <small v-if="form.errors.work_days" class="p-error">{{ form.errors.work_days }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label for="payment_type" class="font-semibold dark:text-gray-300">Forma de Pago <span class="text-red-500">*</span></label>
                                        <Dropdown id="payment_type" v-model="form.payment_type" :options="paymentOptions" placeholder="Selecciona una forma de pago" class="w-full" :class="{ 'p-invalid': form.errors.payment_type }" />
                                        <small v-if="form.errors.payment_type" class="p-error">{{ form.errors.payment_type }}</small>
                                    </div>
                                </div>

                                <!-- Descripción -->
                                <div class="flex flex-col gap-2">
                                    <label class="font-semibold dark:text-gray-300">Descripción / Servicios <span class="text-red-500">*</span></label>
                                    <Editor v-model="form.description" editorStyle="height: 250px" :class="{ 'p-invalid': form.errors.description }">
                                        <template #toolbar>
                                            <span class="ql-formats">
                                                <button class="ql-bold"></button>
                                                <button class="ql-italic"></button>
                                                <button class="ql-underline"></button>
                                                <button class="ql-list" value="ordered"></button>
                                                <button class="ql-list" value="bullet"></button>
                                                <button class="ql-link"></button>
                                            </span>
                                        </template>
                                    </Editor>
                                    <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                                </div>

                                <!-- Checkboxes para mostrar secciones -->
                                <div class="border-t pt-4">
                                     <h3 class="font-semibold dark:text-gray-300 mb-2">Secciones a mostrar en la cotización</h3>
                                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_process" inputId="show_process" :binary="true" />
                                            <label for="show_process" class="ml-2 text-sm dark:text-gray-300"> Mostrar Proceso </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_benefits" inputId="show_benefits" :binary="true" />
                                            <label for="show_benefits" class="ml-2 text-sm dark:text-gray-300"> Mostrar Beneficios </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_bank_info" inputId="show_bank_info" :binary="true" />
                                            <label for="show_bank_info" class="ml-2 text-sm dark:text-gray-300"> Mostrar Datos Bancarios </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Acciones -->
                            <div class="flex justify-end gap-2 mt-8">
                                <Link :href="route('quotes.index')">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button type="submit" label="Actualizar Cotización" icon="pi pi-check" :loading="form.processing" />
                            </div>
                        </div>

                        <!-- Columna de la Vista Previa -->
                        <div class="bg-white shadow-lg rounded-lg p-8 font-sans text-sm text-gray-700">
                           <header class="flex justify-between items-start pb-6 border-b">
                                <div>
                                   <img src="/images/black_logo.png" alt="Logo de la Empresa" class="h-16">
                                </div>
                                <div class="text-right">
                                    <h2 class="text-2xl font-bold uppercase text-gray-800">Cotización</h2>
                                    <p class="font-mono mt-1">Folio: {{ quote.quote_code }}</p>
                                    <p class="mt-2">Fecha de Emisión: <span class="font-semibold">{{ todayFormatted }}</span></p>
                                    <p>Válido hasta: <span class="font-semibold">{{ validUntilFormatted }}</span></p>
                                </div>
                           </header>
                           
                           <section class="mt-6">
                               <h3 class="font-semibold text-gray-800">Cliente:</h3>
                               <div v-if="selectedClient">
                                   <p class="font-bold">{{ selectedClient.name }}</p>
                                   <p>{{ selectedClient.address }}</p>
                                   <p>RFC: {{ selectedClient.tax_id }}</p>
                               </div>
                               <div v-else>
                                   <p class="text-gray-500">Selecciona un cliente para ver sus datos.</p>
                               </div>
                           </section>
                           
                           <section class="mt-6">
                               <h3 class="text-base font-bold text-gray-800 bg-gray-100 p-2 rounded-t-md">{{ form.title || 'Título de la cotización' }}</h3>
                               <div class="prose max-w-none p-4 border rounded-b-md" v-html="form.description"></div>
                           </section>

                            <section class="mt-6 space-y-4 text-xs">
                                <div>
                                    <h4 class="font-bold text-gray-800">Duración</h4>
                                    <p>La entrega estimada para la implementación final del proyecto es {{ form.work_days || '...' }} días hábiles, iniciando a partir del primer pago al inicio del proyecto.</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Condiciones de pago</h4>
                                    <p>{{ form.payment_type || '...' }}</p>
                                    <p>Esta cotización no incluye costos adicionales que puedan surgir debido a cambios significativos en el alcance del proyecto.</p>
                                </div>
                            </section>

                           <section v-if="form.show_process" class="mt-6 text-xs">
                                <h4 class="font-bold text-gray-800">Proceso</h4>
                                <p>
                                    El proyecto inicia con el diseño de todas las vistas de la aplicación para aprobación del cliente. Una vez aprobado, se procede con la programación y desarrollo. Finalmente, la aplicación se despliega en la nube y se entrega, corrigiendo cualquier error funcional. Se incluye capacitación online para hasta 5 usuarios y un año de soporte técnico integral para asegurar el funcionamiento óptimo del sistema.
                                </p>
                           </section>

                           <section v-if="form.show_benefits" class="mt-6 text-xs">
                                <h4 class="font-bold text-gray-800">Beneficios de adquirir el software</h4>
                                <ul class="list-disc list-inside space-y-1">
                                    <li><b>Compatibilidad Total:</b> Funciona en computadoras, laptops, tablets y móviles.</li>
                                    <li><b>Seguridad en la Nube:</b> Datos protegidos con respaldos automáticos.</li>
                                    <li><b>Acceso Remoto:</b> Accede a tu información desde cualquier lugar.</li>
                                    <li><b>Escalabilidad:</b> El sistema crece junto a tu empresa.</li>
                                    <li><b>Soporte Técnico:</b> Asistencia eficiente para resolver cualquier duda.</li>
                                    <li><b>Personalización:</b> Adaptamos el sistema a los colores y logo de tu marca.</li>
                                    <li>Un solo pago, usuarios ilimitados.</li>
                                </ul>
                           </section>

                           <footer class="mt-8 border-t pt-6">
                               <!-- Lógica de Totales con Descuento -->
                               <div class="w-full sm:w-2/3 md:w-1/2 ml-auto text-right space-y-2 text-gray-800">
                                   <div class="flex justify-between">
                                       <span class="font-semibold">Subtotal:</span>
                                       <span>{{ formatCurrency(form.amount) }}</span>
                                   </div>
                                   <div v-if="form.percentage_discount > 0" class="flex justify-between text-red-600">
                                       <span class="font-semibold">Descuento ({{ form.percentage_discount }}%):</span>
                                       <span>- {{ formatCurrency((form.amount * form.percentage_discount) / 100) }}</span>
                                   </div>
                                   <div class="flex justify-between border-t pt-2 mt-2">
                                       <span class="text-lg font-bold">Total:</span>
                                       <span class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalWithDiscount) }} <span class="text-base font-normal">MXN</span></span>
                                   </div>
                                    <p class="text-xs text-gray-500 mt-1 text-right">Los precios no incluyen IVA.</p>
                               </div>
                               
                               <div v-if="form.show_bank_info" class="mt-6 bg-gray-100 p-4 rounded-lg text-xs">
                                   <h4 class="font-bold text-gray-800 mb-2">Datos para la realización de pagos</h4>
                                   <div class="grid grid-cols-2 gap-x-4">
                                       <p><b>Nombre del beneficiario:</b></p><p>Miguel Osvaldo Vázquez Rodríguez</p>
                                       <p><b>Banco:</b></p><p>NU México</p>
                                       <p><b>Número de cuenta:</b></p><p>00017049244</p>
                                       <p><b>Clabe:</b></p><p>638180000170492445</p>
                                   </div>
                               </div>
                           </footer>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Estilos para que el editor de PrimeVue se vea bien en modo oscuro */
.dark .p-editor-container .p-editor-toolbar {
    background-color: #374151; /* gray-700 */
    border-color: #4b5563; /* gray-600 */
}
.dark .p-editor-container .p-editor-content {
    background-color: #1f2937; /* gray-800 */
    border-color: #4b5563 !important; /* gray-600 */
    color: #f3f4f6; /* gray-100 */
}
.dark .p-editor-container .ql-snow .ql-stroke {
    stroke: #d1d5db; /* gray-300 */
}
.dark .p-editor-container .ql-snow .ql-fill {
    fill: #d1d5db; /* gray-300 */
}

/* Estilos para la vista previa de la cotización */
.prose {
    font-size: 1rem;
    line-height: 1.75;
}
.prose ul {
    list-style-type: disc;
    padding-left: 1.5rem;
}
.prose li p {
    margin: 0;
}
</style>
