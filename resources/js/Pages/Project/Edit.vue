<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';

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
    project: Object,
    clients: Array,
    users: Array,
    quotes: Array,
});

// --- FORM ---
// Inicializa el formulario con los datos del proyecto que se está editando.
const form = useForm({
    name: props.project.name,
    quote_id: props.project.quote_id,
    client_id: props.project.client_id,
    description: props.project.description,
    // Convierte las fechas a objetos Date para que el componente Calendar las reconozca
    start_date: props.project.start_date ? new Date(props.project.start_date) : null,
    end_date: props.project.end_date ? new Date(props.project.end_date) : null,
    budget: props.project.budget,
    // Mapea el array de miembros a un array de sus IDs para el MultiSelect
    member_ids: props.project.members.map(member => member.id),
});

// --- METHODS ---
const submit = () => {
    // Usa el método PUT para actualizar el recurso
    form.put(route('projects.update', props.project.id));
};
</script>

<template>
    <AppLayout title="Editar Proyecto">
        <div class="p-4 sm:p-6 lg:p-8">
            <Back :route="'projects.index'" />
            <div class="max-w-4xl mx-auto mt-4">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-gray-800 dark:border-gray-700">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Editar Proyecto</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-600 dark:text-gray-400">Actualiza la información del proyecto.</p>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="quote_id" class="font-semibold dark:text-gray-300">Cotización Vinculada (Opcional)</label>
                                     <!-- El dropdown está deshabilitado si el proyecto ya tiene una cotización para evitar cambios accidentales -->
                                    <Dropdown id="quote_id" v-model="form.quote_id" :options="props.quotes" filter
                                        optionValue="id" placeholder="Selecciona una cotización aceptada"
                                        :class="{ 'p-invalid': form.errors.quote_id }" showClear
                                        :disabled="!!props.project.quote_id">
                                        <template #option="{ option }">
                                            <span>{{ option.title }} (Folio: {{ option.id }})</span>
                                        </template>
                                        <template #value="{ value, placeholder }">
                                            <span v-if="value">{{ props.quotes.find(q => q.id === value)?.title }} (Folio: {{ value }})</span>
                                            <span v-else>{{ placeholder }}</span>
                                        </template>
                                    </Dropdown>
                                    <small class="text-yellow-500 text-xs mt-1" v-if="props.project.quote_id">Para cambiar la cotización, primero debes desvincularla.</small>
                                    <small v-if="form.errors.quote_id" class="p-error">{{ form.errors.quote_id }}</small>
                                </div>

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="name" class="font-semibold dark:text-gray-300">Nombre del Proyecto <span class="text-red-500">*</span></label>
                                    <InputText id="name" v-model="form.name" :class="{ 'p-invalid': form.errors.name }" :disabled="!!form.quote_id" />
                                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="client_id" class="font-semibold dark:text-gray-300">Cliente</label>
                                    <Dropdown id="client_id" v-model="form.client_id" :options="props.clients" filter optionLabel="name" optionValue="id" placeholder="Selecciona un cliente" :class="{ 'p-invalid': form.errors.client_id }" showClear :disabled="!!form.quote_id" />
                                    <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                     <label for="budget" class="font-semibold dark:text-gray-300">Presupuesto</label>
                                     <InputNumber id="budget" v-model="form.budget" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': form.errors.budget }" :disabled="!!form.quote_id" />
                                     <small v-if="form.errors.budget" class="p-error">{{ form.errors.budget }}</small>
                                </div>

                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="description" class="font-semibold dark:text-gray-300">Descripción</label>
                                    <Textarea id="description" v-model="form.description" rows="4" :class="{ 'p-invalid': form.errors.description }" />
                                    <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="start_date" class="font-semibold dark:text-gray-300">Fecha de Inicio</label>
                                    <Calendar id="start_date" v-model="form.start_date" dateFormat="dd/mm/yy" :class="{ 'p-invalid': form.errors.start_date }" />
                                    <small v-if="form.errors.start_date" class="p-error">{{ form.errors.start_date }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="end_date" class="font-semibold dark:text-gray-300">Fecha de Fin</label>
                                    <Calendar id="end_date" v-model="form.end_date" dateFormat="dd/mm/yy" :class="{ 'p-invalid': form.errors.end_date }" />
                                    <small v-if="form.errors.end_date" class="p-error">{{ form.errors.end_date }}</small>
                                </div>

                                 <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="member_ids" class="font-semibold dark:text-gray-300">Miembros del Equipo</label>
                                    <MultiSelect id="member_ids" v-model="form.member_ids" :options="props.users" filter optionLabel="name" optionValue="id" placeholder="Asigna miembros al proyecto" :class="{ 'p-invalid': form.errors.member_ids }" class="w-full" />
                                     <small v-if="form.errors.member_ids" class="p-error">{{ form.errors.member_ids }}</small>
                                </div>

                            </div>
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-2 mt-6">
                                <Link :href="route('projects.index')">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button label="Actualizar Proyecto" icon="pi pi-check" @click="submit" :loading="form.processing" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.p-inputtext, .p-dropdown, .p-textarea, .p-calendar, .p-inputnumber, .p-multiselect {
    width: 100%;
}
</style>
