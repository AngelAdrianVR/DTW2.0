<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Calendar from 'primevue/calendar';
import InputNumber from 'primevue/inputnumber';
import Back from '@/Components/MyComponents/Back.vue';

// --- PROPS ---
const props = defineProps({
    hostingClient: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        required: true,
    }
});

// --- OPTIONS FOR DROPDOWNS ---
const statusOptions = ref([
    { label: 'Activo', value: 'Activo' },
    { label: 'Suspendido', value: 'Suspendido' },
    { label: 'Cancelado', value: 'Cancelado' }
]);

const billingCycleOptions = ref([
    { label: 'Mensual', value: 'Mensual' },
    { label: 'Anual', value: 'Anual' }
]);

// --- FORM INITIALIZATION ---
// Transformamos el array de URLs a un array de objetos para el formulario.
const urlsForForm = props.hostingClient.hosted_urls ? props.hostingClient.hosted_urls.map(url => ({ url })) : [];

// Helper function to format date strings from Laravel (YYYY-MM-DDTHH:MM:SSZ)
// to a format the Calendar component understands (YYYY-MM-DD).
const formatDateForInput = (dateString) => {
    if (!dateString) return null;
    return dateString.split('T')[0];
};

const form = useForm({
    client_id: props.hostingClient.client_id,
    service_provider: props.hostingClient.service_provider,
    start_date: formatDateForInput(props.hostingClient.start_date),
    next_payment_date: formatDateForInput(props.hostingClient.next_payment_date),
    payment_amount: props.hostingClient.payment_amount,
    billing_cycle: props.hostingClient.billing_cycle,
    hosted_urls: urlsForForm,
    status: props.hostingClient.status,
    notes: props.hostingClient.notes,
});

// --- DYNAMIC URLS FUNCTIONS ---
const addUrl = () => {
    form.hosted_urls.push({ url: '' });
};

const removeUrl = (index) => {
    form.hosted_urls.splice(index, 1);
};

// --- SUBMIT FUNCTION ---
const submit = () => {
    form.put(route('hosting-clients.update', props.hostingClient.id));
};

</script>

<template>
    <AppLayout title="Editar Servicio de Hosting">
        <div class="py-12">
            <Back :href="route('hosting-clients.index')" />
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-gray-800 dark:border-ray-700">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Editar Servicio de Hosting</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-600 dark:text-gray-400">Actualiza la información del servicio.</p>
                        </template>

                        <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                               <div class="flex flex-col gap-2">
                                    <label for="client_id" class="font-semibold dark:text-gray-300">Cliente <span class="text-red-500">*</span></label>
                                    <Dropdown id="client_id" v-model="form.client_id" :options="props.clients" optionLabel="name" optionValue="id" placeholder="Selecciona un cliente" filter :class="{ 'p-invalid': form.errors.client_id }" />
                                    <small v-if="form.errors.client_id" class="p-error">{{ form.errors.client_id }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="service_provider" class="font-semibold dark:text-gray-300">Proveedor <span class="text-red-500">*</span></label>
                                    <InputText id="service_provider" v-model="form.service_provider" :class="{ 'p-invalid': form.errors.service_provider }" />
                                    <small v-if="form.errors.service_provider" class="p-error">{{ form.errors.service_provider }}</small>
                                </div>
                                 <div class="flex flex-col gap-2">
                                    <label for="start_date" class="font-semibold dark:text-gray-300">Fecha de Inicio <span class="text-red-500">*</span></label>
                                    <Calendar id="start_date" v-model="form.start_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': form.errors.start_date }" />
                                    <small v-if="form.errors.start_date" class="p-error">{{ form.errors.start_date }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="next_payment_date" class="font-semibold dark:text-gray-300">Próximo Pago <span class="text-red-500">*</span></label>
                                    <Calendar id="next_payment_date" v-model="form.next_payment_date" dateFormat="yy-mm-dd" :class="{ 'p-invalid': form.errors.next_payment_date }" />
                                    <small v-if="form.errors.next_payment_date" class="p-error">{{ form.errors.next_payment_date }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="payment_amount" class="font-semibold dark:text-gray-300">Monto <span class="text-red-500">*</span></label>
                                    <InputNumber id="payment_amount" v-model="form.payment_amount" mode="currency" currency="MXN" locale="es-MX" :class="{ 'p-invalid': form.errors.payment_amount }" />
                                    <small v-if="form.errors.payment_amount" class="p-error">{{ form.errors.payment_amount }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="billing_cycle" class="font-semibold dark:text-gray-300">Ciclo de Pago <span class="text-red-500">*</span></label>
                                    <Dropdown id="billing_cycle" v-model="form.billing_cycle" :options="billingCycleOptions" optionLabel="label" optionValue="value" :class="{ 'p-invalid': form.errors.billing_cycle }" />
                                    <small v-if="form.errors.billing_cycle" class="p-error">{{ form.errors.billing_cycle }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="status" class="font-semibold dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                                    <Dropdown id="status" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" :class="{ 'p-invalid': form.errors.status }" />
                                    <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
                                </div>
                                 <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="notes" class="font-semibold dark:text-gray-300">Notas</label>
                                    <Textarea id="notes" v-model="form.notes" rows="3" :class="{ 'p-invalid': form.errors.notes }" />
                                    <small v-if="form.errors.notes" class="p-error">{{ form.errors.notes }}</small>
                                </div>
                            </div>

                             <Divider align="left" type="solid" class="my-8">
                                <b class="text-gray-700 dark:text-gray-300">URLs Alojadas</b>
                            </Divider>

                            <div v-for="(item, index) in form.hosted_urls" :key="index" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg relative">
                               <div class="flex flex-col gap-2 md:col-span-4">
                                    <label :for="'url_' + index" class="font-semibold text-sm dark:text-gray-300">URL</label>
                                    <InputText :id="'url_' + index" v-model="item.url" :class="{ 'p-invalid': form.errors[`hosted_urls.${index}.url`] }" placeholder="https://ejemplo.com"/>
                                     <small v-if="form.errors[`hosted_urls.${index}.url`]" class="p-error">{{ form.errors[`hosted_urls.${index}.url`] }}</small>
                                </div>
                                <div class="md:col-span-1 flex justify-end">
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeUrl(index)" />
                                </div>
                            </div>

                            <Button label="Agregar Otra URL" icon="pi pi-plus" severity="secondary" outlined @click="addUrl" />
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-2 mt-6">
                                <Link :href="route('hosting-clients.index')">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button label="Actualizar Servicio" icon="pi pi-check" @click="submit" :loading="form.processing" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

