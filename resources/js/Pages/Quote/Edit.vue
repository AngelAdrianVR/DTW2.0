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
import Back from '@/Components/MyComponents/Back.vue';

// --- PROPS ---
const props = defineProps({
    clients: { type: Array, required: true },
    quote: { type: Object, required: true }
});

// --- STATE MANAGEMENT ---
const form = useForm({
    client_id: props.quote.client_id,
    title: props.quote.title,
    description: props.quote.description,
    amount: props.quote.amount,
    valid_until: new Date(props.quote.valid_until),
    payment_type: props.quote.payment_type || props.quote.Payment_type || '', 
    work_days: props.quote.work_days,
    percentage_discount: props.quote.percentage_discount,
    show_process: Boolean(props.quote.show_process),
    show_benefits: Boolean(props.quote.show_benefits),
    show_bank_info: Boolean(props.quote.show_bank_info),
});

const paymentOptions = ref([
    'Pago en una sola exhibición',
    '2 pagos (50% al inicio y 50% a la entrega del proyecto)',
    '3 pagos ( 30% al inicio, 40% al desarrollo y 30% a la entrega)'
]);

const selectedClient = computed(() => {
    if (!form.client_id) return null;
    return props.clients.find(c => c.id === form.client_id);
});

const todayFormatted = computed(() => {
    return new Date().toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
});

const validUntilFormatted = computed(() => {
    if (!form.valid_until) return '';
    const date = new Date(form.valid_until);
    return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'long', year: 'numeric' });
});

const totalWithDiscount = computed(() => {
    const amount = form.amount || 0;
    const discount = form.percentage_discount || 0;
    if (discount <= 0 || discount > 100) return amount;
    const discountAmount = (amount * discount) / 100;
    return amount - discountAmount;
});

const submit = () => {
    form.put(route('quotes.update', props.quote.id), { preserveScroll: true });
};

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};
</script>

<template>
    <AppLayout title="Editar Cotización">
        <div class="py-12">
            <div class="max-w-4xl mx-20 mb-6">
                 <Link :href="route('quotes.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
            </div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                        
                        <!-- Columna del Formulario -->
                        <div class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl p-6">
                            <header class="mb-6">
                                <h1 class="text-2xl font-bold text-gray-800 dark:text-zinc-100">Editar Cotización</h1>
                                <p class="text-gray-500 dark:text-zinc-500 mt-1">Folio: COT-{{ quote.id }}</p>
                            </header>

                            <div class="space-y-6">
                                <div class="flex flex-col gap-2">
                                    <label for="client" class="font-semibold text-sm dark:text-zinc-300">Cliente <span class="text-red-500">*</span></label>
                                    <Dropdown id="client" v-model="form.client_id" :options="clients" optionLabel="name" optionValue="id" placeholder="Selecciona un cliente" class="w-full" :class="{ 'p-invalid': form.errors.client_id }" filter />
                                    <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                </div>
                                
                                <div class="flex flex-col gap-2">
                                    <label for="title" class="font-semibold text-sm dark:text-zinc-300">Título <span class="text-red-500">*</span></label>
                                    <InputText id="title" v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
                                    <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="amount" class="font-semibold text-sm dark:text-zinc-300">Monto <span class="text-red-500">*</span></label>
                                        <InputNumber id="amount" v-model="form.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': form.errors.amount }" />
                                        <small v-if="form.errors.amount" class="p-error">{{ form.errors.amount }}</small>
                                    </div>
                                     <div class="flex flex-col gap-2">
                                        <label for="percentage_discount" class="font-semibold text-sm dark:text-zinc-300">Descuento (%)</label>
                                        <InputNumber id="percentage_discount" v-model="form.percentage_discount" :min="0" :max="100" suffix=" %" :class="{ 'p-invalid': form.errors.percentage_discount }" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label for="valid_until" class="font-semibold text-sm dark:text-zinc-300">Válido hasta <span class="text-red-500">*</span></label>
                                        <DatePicker id="valid_until" v-model="form.valid_until" dateFormat="dd/mm/yy" :class="{ 'p-invalid': form.errors.valid_until }" />
                                        <small v-if="form.errors.valid_until" class="p-error">{{ form.errors.valid_until }}</small>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label for="work_days" class="font-semibold text-sm dark:text-zinc-300">Días Hábiles <span class="text-red-500">*</span></label>
                                        <InputNumber id="work_days" v-model="form.work_days" :min="1" :class="{ 'p-invalid': form.errors.work_days }" />
                                        <small v-if="form.errors.work_days" class="p-error">{{ form.errors.work_days }}</small>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label for="payment_type" class="font-semibold text-sm dark:text-zinc-300">Pago <span class="text-red-500">*</span></label>
                                        <Dropdown id="payment_type" v-model="form.payment_type" :options="paymentOptions" placeholder="Selecciona..." class="w-full" :class="{ 'p-invalid': form.errors.payment_type }" />
                                        <small v-if="form.errors.payment_type" class="p-error">{{ form.errors.payment_type }}</small>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label class="font-semibold text-sm dark:text-zinc-300">Descripción <span class="text-red-500">*</span></label>
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

                                <div class="border-t border-gray-100 dark:border-zinc-800 pt-4">
                                     <h3 class="font-semibold text-sm dark:text-zinc-300 mb-3">Secciones a mostrar</h3>
                                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_process" inputId="show_process" :binary="true" />
                                            <label for="show_process" class="ml-2 text-sm text-gray-700 dark:text-zinc-400 cursor-pointer"> Mostrar Proceso </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_benefits" inputId="show_benefits" :binary="true" />
                                            <label for="show_benefits" class="ml-2 text-sm text-gray-700 dark:text-zinc-400 cursor-pointer"> Mostrar Beneficios </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_bank_info" inputId="show_bank_info" :binary="true" />
                                            <label for="show_bank_info" class="ml-2 text-sm text-gray-700 dark:text-zinc-400 cursor-pointer"> Mostrar Datos Bancarios </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            
                            <div class="flex justify-end gap-3 mt-8 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                <Link :href="route('quotes.index')">
                                    <Button label="Cancelar" severity="secondary" text />
                                </Link>
                                <Button type="submit" label="Actualizar Cotización" icon="pi pi-check" :loading="form.processing" class="!text-[var(--primary-text-color)]" />
                            </div>
                        </div>

                        <!-- Columna de la Vista Previa -->
                        <div class="bg-white shadow-xl rounded-none sm:rounded-lg p-8 font-sans text-sm text-gray-700 relative">
                           <div class="absolute top-0 right-0 bg-zinc-800 text-white text-[10px] uppercase font-bold px-3 py-1 rounded-bl-lg rounded-tr-lg">Vista Previa</div>
                           
                           <header class="flex justify-between items-start pb-6 border-b border-gray-100">
                                <div><img src="/images/black_logo.png" alt="Logo" class="h-12 object-contain"></div>
                                <div class="text-right">
                                    <h2 class="text-2xl font-bold uppercase text-gray-800">Cotización</h2>
                                    <p class="font-mono mt-1 text-gray-500">Folio: COT-{{ quote.id }}</p>
                                    <p class="mt-2 text-xs">Fecha: <span class="font-semibold">{{ todayFormatted }}</span></p>
                                    <p class="text-xs">Vence: <span class="font-semibold">{{ validUntilFormatted }}</span></p>
                                </div>
                           </header>
                           
                           <section class="mt-6 bg-gray-50 p-4 rounded-lg">
                               <h3 class="font-bold text-gray-800 text-xs uppercase mb-2">Cliente</h3>
                               <div v-if="selectedClient">
                                   <p class="font-bold text-lg text-gray-900">{{ selectedClient.name }}</p>
                                   <p class="text-gray-600">{{ selectedClient.address }}</p>
                                   <p class="text-gray-500 text-xs mt-1">RFC: {{ selectedClient.tax_id }}</p>
                               </div>
                               <div v-else><p class="text-gray-400 italic">Selecciona un cliente...</p></div>
                           </section>
                           
                           <section class="mt-6">
                               <h3 class="text-base font-bold text-gray-900 mb-2">{{ form.title || 'Título del Proyecto' }}</h3>
                               <div class="prose prose-sm max-w-none text-gray-600" v-html="form.description || '...'"></div>
                           </section>

                            <section class="mt-6 grid grid-cols-2 gap-4 text-xs">
                                <div><h4 class="font-bold text-gray-800 uppercase">Entrega</h4><p>{{ form.work_days || '0' }} días hábiles</p></div>
                                <div><h4 class="font-bold text-gray-800 uppercase">Condiciones</h4><p>{{ form.payment_type || 'No especificado' }}</p></div>
                            </section>

                           <section v-if="form.show_process" class="mt-6 text-xs">
                                <h4 class="font-bold text-gray-800">Proceso</h4>
                                <p>El proyecto inicia con el diseño de todas las vistas...</p>
                           </section>

                           <section v-if="form.show_benefits" class="mt-6 text-xs">
                                <h4 class="font-bold text-gray-800">Beneficios</h4>
                                <ul class="list-disc list-inside space-y-1"><li>Compatibilidad Total</li><li>Seguridad en la Nube</li></ul>
                           </section>

                           <footer class="mt-8 border-t border-gray-100 pt-6">
                               <div class="w-full sm:w-1/2 ml-auto text-right space-y-1">
                                   <div class="flex justify-between text-gray-600"><span>Subtotal:</span><span>{{ formatCurrency(form.amount) }}</span></div>
                                   <div v-if="form.percentage_discount > 0" class="flex justify-between text-red-500 text-xs"><span>Descuento ({{ form.percentage_discount }}%):</span><span>- {{ formatCurrency((form.amount * form.percentage_discount) / 100) }}</span></div>
                                   <div class="flex justify-between border-t border-gray-200 pt-2 mt-2"><span class="font-bold text-gray-800">Total:</span><span class="text-xl font-bold text-blue-600">{{ formatCurrency(totalWithDiscount) }}</span></div>
                               </div>
                               
                               <div v-if="form.show_bank_info" class="mt-6 bg-gray-100 p-4 rounded-lg text-xs">
                                   <h4 class="font-bold text-gray-800 mb-2">Datos Bancarios</h4>
                                   <div class="grid grid-cols-2 gap-x-4"><p><b>Banco:</b></p><p>NU México</p><p><b>Cuenta:</b></p><p>00017049244</p></div>
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
/* Mismos estilos que CreateQuote.vue */
.p-inputtext, .p-dropdown, .p-textarea { width: 100%; }
.dark .p-inputtext, .dark .p-dropdown, .dark .p-textarea {
    background-color: #27272a; 
    color: #f4f4f5; 
    border-color: #3f3f46; 
}
.dark .p-inputtext:focus, .dark .p-dropdown:not(.p-disabled).p-focus, .dark .p-textarea:focus { border-color: var(--p-primary-color); }
.dark .p-editor-container .p-editor-toolbar { background-color: #27272a; border-color: #3f3f46; }
.dark .p-editor-container .p-editor-content { background-color: #18181b; border-color: #3f3f46 !important; color: #f4f4f5; }
.dark .p-editor-container .ql-snow .ql-stroke { stroke: #d4d4d8; }
.dark .p-editor-container .ql-snow .ql-fill { fill: #d4d4d8; }
.prose ul { list-style-type: disc; padding-left: 1.5rem; }
</style>