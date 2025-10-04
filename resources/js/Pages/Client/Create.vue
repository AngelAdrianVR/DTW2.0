<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue'; // Asegúrate que la ruta a tu layout sea correcta
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Back from '@/Components/MyComponents/Back.vue';

// Opciones para el campo 'status'
const statusOptions = ref([
    { label: 'Prospecto', value: 'Prospecto' },
    { label: 'Cliente', value: 'Cliente' }
]);

// Inicialización del formulario con useForm de Inertia
const form = useForm({
    name: '',
    tax_id: '',
    address: '',
    status: 'Prospecto', // Valor por defecto
    source: '',
    contacts: [], // Array para almacenar los contactos
});

// --- FUNCIONES DE CONTACTOS ---
/**
 * Agrega un nuevo objeto de contacto vacío al formulario.
 */
const addContact = () => {
    form.contacts.push({
        name: '',
        email: '',
        phone: '',
        position: '',
    });
};

/**
 * Elimina un contacto del formulario según su índice.
 * @param {number} index - El índice del contacto a eliminar.
 */
const removeContact = (index) => {
    form.contacts.splice(index, 1);
};

// Función para enviar el formulario
const submit = () => {
    form.post('/clients');
};

// Agregar un contacto inicial al cargar el componente
addContact();
</script>

<template>
    <AppLayout title="Crear Cliente">
        <div class="py-12">
            <Back />
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-gray-800 dark:border-gray-700">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Crear Nuevo Cliente</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-600 dark:text-gray-400">Completa la información para registrar un nuevo cliente en el sistema.</p>
                        </template>

                        <template #content>
                            <!-- SECCIÓN DATOS DEL CLIENTE -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="font-semibold dark:text-gray-300">Nombre de la Empresa <span class="text-red-500">*</span></label>
                                    <InputText id="name" v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
                                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="tax_id" class="font-semibold dark:text-gray-300">RFC / Tax ID</label>
                                    <InputText id="tax_id" v-model="form.tax_id" :class="{ 'p-invalid': form.errors.tax_id }" />
                                    <small v-if="form.errors.tax_id" class="p-error">{{ form.errors.tax_id }}</small>
                                </div>
                                <div class="flex flex-col gap-2 col-span-2">
                                    <label for="address" class="font-semibold dark:text-gray-300">Dirección</label>
                                    <Textarea id="address" v-model="form.address" rows="3" :class="{ 'p-invalid': form.errors.address }" />
                                    <small v-if="form.errors.address" class="p-error">{{ form.errors.address }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="status" class="font-semibold dark:text-gray-300">Estado <span class="text-red-500">*</span></label>
                                    <Dropdown id="status" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Selecciona un estado" :class="{ 'p-invalid': form.errors.status }" />
                                    <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="source" class="font-semibold dark:text-gray-300">Origen del Contacto</label>
                                    <InputText id="source" v-model="form.source" :class="{ 'p-invalid': form.errors.source }" placeholder="Ej. Sitio Web, Referencia"/>
                                    <small v-if="form.errors.source" class="p-error">{{ form.errors.source }}</small>
                                </div>
                            </div>

                            <Divider align="left" type="solid" class="my-8">
                                <b class="text-gray-700 dark:text-gray-300">Contactos</b>
                            </Divider>

                            <!-- SECCIÓN DE CONTACTOS DINÁMICOS -->
                            <div v-for="(contact, index) in form.contacts" :key="index" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg relative">
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label :for="'contact_name_' + index" class="font-semibold text-sm dark:text-gray-300">Nombre Completo <span class="text-red-500">*</span></label>
                                    <InputText :id="'contact_name_' + index" v-model="contact.name" :class="{ 'p-invalid': form.errors[`contacts.${index}.name`] }" />
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label :for="'contact_email_' + index" class="font-semibold text-sm dark:text-gray-300">Email</label>
                                    <InputText type="email" :id="'contact_email_' + index" v-model="contact.email" :class="{ 'p-invalid': form.errors[`contacts.${index}.email`] }" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label :for="'contact_phone_' + index" class="font-semibold text-sm dark:text-gray-300">Teléfono</label>
                                    <InputText :id="'contact_phone_' + index" v-model="contact.phone" :class="{ 'p-invalid': form.errors[`contacts.${index}.phone`] }" />
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-4">
                                    <label :for="'contact_position_' + index" class="font-semibold text-sm dark:text-gray-300">Puesto</label>
                                    <InputText :id="'contact_position_' + index" v-model="contact.position" :class="{ 'p-invalid': form.errors[`contacts.${index}.position`] }" />
                                </div>
                                <div class="md:col-span-1 flex justify-end">
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeContact(index)" />
                                </div>
                            </div>

                            <Button label="Agregar Otro Contacto" icon="pi pi-plus" severity="secondary" outlined @click="addContact" />
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-2 mt-6">
                                <Link href="/clients">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button label="Guardar Cliente" icon="pi pi-check" @click="submit" :loading="form.processing" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Ajustes para que los inputs ocupen todo el ancho disponible */
.p-inputtext, .p-dropdown, .p-textarea {
    width: 100%;
}
/* PrimeVue por defecto puede no tener estilos oscuros para los inputs.
   Esto es un override simple, pero lo ideal es usar un tema de PrimeVue oscuro. */
.dark .p-inputtext, .dark .p-dropdown, .dark .p-textarea {
    background-color: #374151; /* gray-700 */
    color: #f3f4f6; /* gray-100 */
    border-color: #4b5563; /* gray-600 */
}
</style>

