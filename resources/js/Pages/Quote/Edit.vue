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
import QuotePreview from '@/Components/QuotePreview.vue';

const props = defineProps({
    clients: { type: Array, required: true },
    quote: { type: Object, required: true }
});

const form = useForm({
    client_id: props.quote.client_id,
    title: props.quote.title,
    description: props.quote.description,
    amount: props.quote.amount,
    amount_usd: props.quote.amount_usd, 
    valid_until: new Date(props.quote.valid_until),
    payment_type: props.quote.payment_type || props.quote.Payment_type || '', 
    work_days: props.quote.work_days,
    budgeted_hours: props.quote.budgeted_hours,
    percentage_discount: props.quote.percentage_discount,
    show_process: Boolean(props.quote.show_process),
    show_benefits: Boolean(props.quote.show_benefits),
    show_bank_info: Boolean(props.quote.show_bank_info),
    show_tax_breakdown: Boolean(props.quote.show_tax_breakdown),
    needs_invoice: Boolean(props.quote.needs_invoice),
});

const paymentOptions = ref([
    'Pago en una sola exhibición',
    '2 pagos (50% al inicio y 50% a la entrega del proyecto)',
    '3 pagos (30% al inicio, 40% al desarrollo y 30% a la entrega)'
]);

const selectedClient = computed(() => {
    if (!form.client_id) return null;
    return props.clients.find(c => c.id === form.client_id);
});

const submit = () => {
    form.put(route('quotes.update', props.quote.id), { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Editar Cotización">
        <div class="py-12 bg-zinc-150 dark:bg-zinc-950 min-h-screen">
            <div class="max-w-[90rem] mx-auto sm:px-6 lg:px-8">
                
                <div class="mb-4">
                     <Link :href="route('quotes.index')" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100 transition-colors">
                        <i class="pi pi-arrow-left mr-2 text-xs"></i> Volver a Cotizaciones
                    </Link>
                </div>

                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] xl:grid-cols-[1fr_550px] gap-8 items-start">
                        
                        <!-- Formulario -->
                        <div class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl p-6 lg:p-8 apple-form">
                            <header class="mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800 flex justify-between items-center">
                                <div>
                                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-zinc-100 tracking-tight">Editar Cotización</h1>
                                    <p class="text-xs font-mono text-gray-500 dark:text-zinc-500 mt-1">Folio: COT-{{ quote.id }}</p>
                                </div>
                                <Button type="submit" label="Actualizar" icon="pi pi-refresh" :loading="form.processing" class="!rounded-xl p-button-sm !text-[var(--primary-text-color)]" />
                            </header>

                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex flex-col gap-1 relative">
                                        <label for="client" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Cliente <span class="text-red-500">*</span></label>
                                        <Dropdown id="client" v-model="form.client_id" :options="clients" optionLabel="name" optionValue="id" placeholder="Buscar cliente..." class="w-full" :class="{ 'p-invalid': form.errors.client_id }" filter />
                                        
                                        <div v-if="selectedClient && (selectedClient.tax_id || selectedClient.address)" class="mt-2 bg-gray-50 dark:bg-zinc-800/50 p-2.5 rounded-lg border border-gray-100 dark:border-zinc-800 text-[11px] text-gray-600 dark:text-gray-400 leading-tight">
                                            <p v-if="selectedClient.tax_id" class="font-medium text-gray-700 dark:text-gray-300">RFC: <span class="font-normal">{{ selectedClient.tax_id }}</span></p>
                                            <p v-if="selectedClient.address" class="mt-0.5">{{ selectedClient.address }}</p>
                                        </div>
                                        <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                    </div>
                                    
                                    <div class="flex flex-col gap-1">
                                        <label for="title" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Título del Proyecto <span class="text-red-500">*</span></label>
                                        <InputText id="title" v-model="form.title" :class="{ 'p-invalid': form.errors.title }" />
                                        <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50/50 dark:bg-zinc-800/20 p-4 rounded-xl border border-gray-50 dark:border-zinc-800/50">
                                    <div class="flex flex-col gap-1">
                                        <label for="amount" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Monto MXN <span class="text-red-500">*</span></label>
                                        <InputNumber id="amount" v-model="form.amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': form.errors.amount }" />
                                        <small v-if="form.errors.amount" class="p-error">{{ form.errors.amount }}</small>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="amount_usd" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider flex justify-between">
                                            <span>Monto USD</span> <span class="font-normal normal-case text-gray-400">Opcional</span>
                                        </label>
                                        <InputNumber id="amount_usd" v-model="form.amount_usd" mode="currency" currency="USD" locale="en-US" :class="{ 'p-invalid': form.errors.amount_usd }" />
                                    </div>
                                     <div class="flex flex-col gap-1">
                                        <label for="percentage_discount" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Descuento (%)</label>
                                        <InputNumber id="percentage_discount" v-model="form.percentage_discount" :min="0" :max="100" suffix=" %" :class="{ 'p-invalid': form.errors.percentage_discount }" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div class="flex flex-col gap-1">
                                        <label for="work_days" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Días de Entrega <span class="text-red-500">*</span></label>
                                        <InputNumber id="work_days" v-model="form.work_days" :min="1" suffix=" días" :class="{ 'p-invalid': form.errors.work_days }" />
                                        <small v-if="form.errors.work_days" class="p-error">{{ form.errors.work_days }}</small>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="budgeted_hours" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider flex justify-between">
                                            <span>Horas Presup.</span> <span class="font-normal normal-case text-gray-400">Info Interna</span>
                                        </label>
                                        <InputNumber id="budgeted_hours" v-model="form.budgeted_hours" :min="1" suffix=" hrs" :class="{ 'p-invalid': form.errors.budgeted_hours }" />
                                        <small v-if="form.errors.budgeted_hours" class="p-error">{{ form.errors.budgeted_hours }}</small>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="valid_until" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Vigencia <span class="text-red-500">*</span></label>
                                        <DatePicker id="valid_until" v-model="form.valid_until" dateFormat="dd/mm/yy" :class="{ 'p-invalid': form.errors.valid_until }" />
                                        <small v-if="form.errors.valid_until" class="p-error">{{ form.errors.valid_until }}</small>
                                    </div>
                                    <div class="flex flex-col gap-1 sm:col-span-2 lg:col-span-1">
                                        <label for="payment_type" class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Condiciones de Pago <span class="text-red-500">*</span></label>
                                        <Dropdown id="payment_type" v-model="form.payment_type" :options="paymentOptions" editable placeholder="Selecciona o escribe..." class="w-full" :class="{ 'p-invalid': form.errors.payment_type }" />
                                        <small v-if="form.errors.payment_type" class="p-error">{{ form.errors.payment_type }}</small>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-1">
                                    <label class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Detalles del Servicio <span class="text-red-500">*</span></label>
                                    <Editor v-model="form.description" editorStyle="height: 250px" :class="{ 'p-invalid': form.errors.description }">
                                        <template #toolbar>
                                            <span class="ql-formats">
                                                <select class="ql-header">
                                                    <option value="1">Título 1</option>
                                                    <option value="2">Título 2</option>
                                                    <option selected>Párrafo</option>
                                                </select>
                                                <select class="ql-size">
                                                    <option value="small">Pequeña</option>
                                                    <option selected>Normal</option>
                                                    <option value="large">Grande</option>
                                                </select>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-bold" title="Negrita"></button>
                                                <button class="ql-italic" title="Cursiva"></button>
                                                <button class="ql-underline" title="Subrayado"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <select class="ql-align" title="Alineación"></select>
                                                <select class="ql-color" title="Color de texto"></select>
                                                <select class="ql-background" title="Color de fondo"></select>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-list" value="ordered" title="Lista numerada"></button>
                                                <button class="ql-list" value="bullet" title="Lista viñetas"></button>
                                            </span>
                                            <span class="ql-formats">
                                                <button class="ql-clean" title="Limpiar formato basura"></button>
                                            </span>
                                        </template>
                                    </Editor>
                                    <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                                </div>

                                <div class="bg-gray-50/50 dark:bg-zinc-800/30 p-4 rounded-xl border border-gray-100 dark:border-zinc-800">
                                     <h3 class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Incluir Secciones Extra</h3>
                                    <div class="flex flex-wrap gap-x-6 gap-y-3">
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_process" inputId="show_process" :binary="true" />
                                            <label for="show_process" class="ml-2 text-sm text-gray-700 dark:text-zinc-300 cursor-pointer"> Proceso de Trabajo </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_benefits" inputId="show_benefits" :binary="true" />
                                            <label for="show_benefits" class="ml-2 text-sm text-gray-700 dark:text-zinc-300 cursor-pointer"> Beneficios </label>
                                        </div>
                                        <div class="flex items-center">
                                            <Checkbox v-model="form.show_bank_info" inputId="show_bank_info" :binary="true" />
                                            <label for="show_bank_info" class="ml-2 text-sm text-gray-700 dark:text-zinc-300 cursor-pointer"> Datos Bancarios </label>
                                        </div>
                                        <div class="flex items-center gap-x-6 gap-y-3 ml-auto">
                                            <div class="flex items-center">
                                                <Checkbox v-model="form.show_tax_breakdown" inputId="show_tax_breakdown" :binary="true" />
                                                <label for="show_tax_breakdown" class="ml-2 text-sm font-bold text-amber-600 cursor-pointer" v-tooltip.top="'Muestra el desglose de IVA y retenciones en la plantilla impresa.'"> Mostrar Desglose Fiscal </label>
                                            </div>
                                            <div class="flex items-center">
                                                <Checkbox v-model="form.needs_invoice" inputId="needs_invoice" :binary="true" />
                                                <label for="needs_invoice" class="ml-2 text-sm font-bold text-blue-600 cursor-pointer" v-tooltip.top="'Agrega el 16% de IVA interno para que coincida con los pagos.'"> Se Factura (+IVA) </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VISTA PREVIA IMPORTADA -->
                        <div class="sticky top-6 shadow-2xl rounded-xl border border-gray-200 overflow-hidden h-fit max-h-[calc(100vh-3rem)] overflow-y-auto hidden lg:block">
                           <div class="absolute top-0 right-0 bg-[#6c5b7b] text-white text-[9px] uppercase tracking-widest font-bold px-3 py-1 rounded-bl-xl z-20 shadow-md">Vista Previa Real</div>
                           <QuotePreview :data="form" :client="selectedClient" :quoteId="quote.id" :createdAt="quote.created_at" currency="MXN" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Estilos del Formulario */
.apple-form .p-inputtext, .apple-form .p-dropdown, .apple-form .p-inputnumber-input, .apple-form .p-datepicker-input { background-color: #f9fafb; border: 1px solid #f3f4f6; border-radius: 0.5rem; padding: 0.6rem 0.75rem; font-size: 0.875rem; transition: all 0.2s ease; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.02); }
.apple-form .p-inputtext:focus, .apple-form .p-dropdown.p-focus, .apple-form .p-inputnumber-input:focus, .apple-form .p-datepicker-input:focus { background-color: #ffffff; border-color: var(--p-primary-color); box-shadow: 0 0 0 3px rgba(121, 34, 236, 0.2); }
.apple-form .p-dropdown { padding: 0; }
.apple-form .p-dropdown-label { padding: 0.6rem 0.75rem; }
.apple-form .p-editor-container .p-editor-toolbar { background-color: #f9fafb; border: 1px solid #f3f4f6; border-radius: 0.5rem 0.5rem 0 0; border-bottom: none; }
.apple-form .p-editor-container .p-editor-content { border: 1px solid #f3f4f6; border-radius: 0 0 0.5rem 0.5rem; font-size: 0.875rem; }
.dark .apple-form .p-inputtext, .dark .apple-form .p-dropdown, .dark .apple-form .p-inputnumber-input, .dark .apple-form .p-datepicker-input { background-color: #27272a; border-color: #3f3f46; color: #f4f4f5; }
.dark .apple-form .p-inputtext:focus, .dark .apple-form .p-dropdown.p-focus { background-color: #18181b; }
.dark .apple-form .p-editor-container .p-editor-toolbar { background-color: #27272a; border-color: #3f3f46; }
.dark .apple-form .p-editor-container .p-editor-content { background-color: #18181b; border-color: #3f3f46 !important; color: #f4f4f5; }
</style>