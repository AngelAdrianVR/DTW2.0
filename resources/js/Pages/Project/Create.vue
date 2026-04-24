<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import MultiSelect from 'primevue/multiselect';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Card from 'primevue/card';

// --- PROPS ---
const props = defineProps({
    clients: Array,
    users: Array,
    quotes: Array, // Cotizaciones aceptadas y sin proyecto
    quoteIdFromUrl: Number, // Recibe el ID de la cotización desde el controlador
});

// --- FORM ---
const form = useForm({
    name: '',
    // Se inicializa el quote_id con el valor que viene de la URL
    quote_id: props.quoteIdFromUrl || null,
    client_id: null,
    description: '',
    start_date: null,
    end_date: null,
    budget: null,
    budgeted_hours: null,
    member_ids: [],
});

// --- FUNCIONES AUXILIARES ---
// Función para limpiar etiquetas HTML o formatos de texto enriquecido
const cleanText = (text) => {
    if (!text) return '';
    try {
        const parsed = JSON.parse(text);
        if (parsed && parsed.ops) {
            return parsed.ops.map(op => op.insert).join('');
        }
    } catch (e) {}
    
    const doc = new DOMParser().parseFromString(text, 'text/html');
    return doc.body.textContent || "";
};

// --- WATCHER ---
watch(() => form.quote_id, (newQuoteId) => {
    if (newQuoteId) {
        const selectedQuote = props.quotes.find(q => q.id === newQuoteId);
        if (selectedQuote) {
            form.name = selectedQuote.title || `Proyecto para Cotización #${selectedQuote.id}`;
            form.client_id = selectedQuote.client_id;
            form.budget = selectedQuote.final_amount;
            form.budgeted_hours = selectedQuote.budgeted_hours; // <-- AUTO RELLENAR
            form.description = cleanText(selectedQuote.description);
        }
    } else {
        form.reset('name', 'client_id', 'budget', 'budgeted_hours', 'description'); // <-- LIMPIAR AL QUITAR COTA
    }
}, {
    immediate: true
});


// --- METHODS ---
const submit = () => {
    form.post(route('projects.store'));
};
</script>

<template>
    <AppLayout title="Crear Proyecto">
        <div class="py-4 sm:py-6 lg:py-8 px-4 sm:px-6 lg:px-8">
            
            <!-- Botón de retroceso moderno e integrado -->
            <div class="max-w-4xl mx-auto mb-6">
                 <Link :href="route('projects.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
            </div>

            <div class="max-w-4xl mx-auto">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm rounded-2xl">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-zinc-100">Crear Nuevo Proyecto</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-500 dark:text-zinc-500">Completa la información para registrar un nuevo proyecto.</p>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="quote_id" class="font-semibold text-sm dark:text-zinc-300">Crear desde Cotización (Opcional)</label>
                                    <Dropdown id="quote_id" v-model="form.quote_id" :options="props.quotes" filter
                                        :filterFields="['title', 'id']" optionLabel="title" optionValue="id" placeholder="Selecciona una cotización aceptada"
                                        :class="{ 'p-invalid': form.errors.quote_id }" showClear class="!rounded-xl">
                                        <template #option="{ option }">
                                            <span>{{ option.title }} (Folio: {{ option.id }})</span>
                                        </template>
                                        <template #value="{ value, placeholder }">
                                            <span v-if="value">{{ props.quotes.find(q => q.id === value)?.title }} (Folio: {{ value }})</span>
                                            <span v-else>{{ placeholder }}</span>
                                        </template>
                                    </Dropdown>
                                    <small v-if="form.errors.quote_id" class="p-error">{{ form.errors.quote_id }}</small>
                                </div>

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="name" class="font-semibold text-sm dark:text-zinc-300">Nombre del Proyecto <span class="text-red-500">*</span></label>
                                    <InputText id="name" v-model="form.name" class="!rounded-xl" :class="{ 'p-invalid': form.errors.name }" :disabled="!!form.quote_id" />
                                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="client_id" class="font-semibold text-sm dark:text-zinc-300">Cliente</label>
                                    <Dropdown id="client_id" v-model="form.client_id" :options="props.clients" filter optionLabel="name" optionValue="id" placeholder="Selecciona un cliente" class="!rounded-xl" :class="{ 'p-invalid': form.errors.client_id }" showClear :disabled="!!form.quote_id" />
                                    <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                </div>

                               <div class="flex flex-col gap-2">
                                     <label for="budget" class="font-semibold text-sm dark:text-zinc-300">Presupuesto</label>
                                     <InputNumber id="budget" v-model="form.budget" mode="currency" currency="MXN" locale="es-MX" class="!rounded-xl" :class="{ 'p-invalid': form.errors.budget }" :disabled="!!form.quote_id" />
                                     <small v-if="form.errors.budget" class="p-error">{{ form.errors.budget }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                     <label for="budgeted_hours" class="font-semibold text-sm dark:text-zinc-300">Horas Presupuestadas</label>
                                     <InputNumber id="budgeted_hours" v-model="form.budgeted_hours" suffix=" hrs" class="!rounded-xl" :class="{ 'p-invalid': form.errors.budgeted_hours }" />
                                     <small v-if="form.errors.budgeted_hours" class="p-error">{{ form.errors.budgeted_hours }}</small>
                                </div>

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="description" class="font-semibold text-sm dark:text-zinc-300">Descripción</label>
                                    <Textarea id="description" v-model="form.description" rows="4" class="!rounded-xl" :class="{ 'p-invalid': form.errors.description }" />
                                    <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="start_date" class="font-semibold text-sm dark:text-zinc-300">Fecha de Inicio</label>
                                    <Calendar id="start_date" v-model="form.start_date" dateFormat="dd/mm/yy" class="!rounded-xl" :class="{ 'p-invalid': form.errors.start_date }" />
                                    <small v-if="form.errors.start_date" class="p-error">{{ form.errors.start_date }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="end_date" class="font-semibold text-sm dark:text-zinc-300">Fecha de Fin</label>
                                    <Calendar id="end_date" v-model="form.end_date" dateFormat="dd/mm/yy" class="!rounded-xl" :class="{ 'p-invalid': form.errors.end_date }" />
                                    <small v-if="form.errors.end_date" class="p-error">{{ form.errors.end_date }}</small>
                                </div>

                                 <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="member_ids" class="font-semibold text-sm dark:text-zinc-300">Miembros del Equipo</label>
                                    <MultiSelect id="member_ids" v-model="form.member_ids" :options="props.users" filter optionLabel="name" optionValue="id" placeholder="Asigna miembros al proyecto" class="w-full !rounded-xl" :class="{ 'p-invalid': form.errors.member_ids }" />
                                     <small v-if="form.errors.member_ids" class="p-error">{{ form.errors.member_ids }}</small>
                                </div>

                            </div>
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-3 pt-6 mt-2 border-t border-gray-100 dark:border-zinc-800">
                                <Link :href="route('projects.index')">
                                    <Button label="Cancelar" severity="secondary" text class="!rounded-xl font-medium" />
                                </Link>
                                <Button label="Guardar Proyecto" icon="pi pi-check" @click="submit" :loading="form.processing" class="!rounded-xl font-medium !text-[var(--primary-text-color)]" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.p-inputtext, .p-dropdown, .p-textarea, .p-calendar, .p-inputnumber, .p-multiselect { width: 100%; }
.dark .p-inputtext, .dark .p-dropdown, .dark .p-textarea, .dark .p-multiselect {
    background-color: #27272a; /* zinc-800 */
    color: #f4f4f5; /* zinc-100 */
    border-color: #3f3f46; /* zinc-700 */
}
</style>