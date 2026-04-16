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
import Back from '@/Components/MyComponents/Back.vue';

const statusOptions = ref([
    { label: 'Prospecto', value: 'Prospecto' },
    { label: 'Cliente', value: 'Cliente' }
]);

const form = useForm({
    name: '',
    tax_id: '',
    address: '',
    status: 'Prospecto',
    source: '',
    contacts: [],
});

const addContact = () => {
    form.contacts.push({ name: '', email: '', phone: '', position: '' });
};

const removeContact = (index) => {
    form.contacts.splice(index, 1);
};

const submit = () => { form.post('/clients'); };

addContact();
</script>

<template>
    <AppLayout title="Crear Cliente">
        <!-- Reduje el padding vertical en móviles para aprovechar el espacio -->
        <div class="py-6 sm:py-12">
            <!-- Corregido el mx-40 que rompía la vista móvil -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-4 sm:mb-6">
                 <Link :href="route('clients.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
            </div>
            
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm rounded-2xl">
                        <template #title>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-zinc-100">Crear Nuevo Cliente</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-sm sm:text-base text-gray-500 dark:text-zinc-500">Completa la información para registrar un nuevo cliente en el sistema.</p>
                        </template>

                        <template #content>
                            <!-- SECCIÓN DATOS DEL CLIENTE -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mt-4 md:mt-6">
                                <div class="flex flex-col gap-2">
                                    <label for="name" class="font-semibold text-sm dark:text-zinc-300">Nombre de la Empresa <span class="text-red-500">*</span></label>
                                    <InputText id="name" v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
                                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="tax_id" class="font-semibold text-sm dark:text-zinc-300">RFC / Tax ID</label>
                                    <InputText id="tax_id" v-model="form.tax_id" :class="{ 'p-invalid': form.errors.tax_id }" />
                                    <small v-if="form.errors.tax_id" class="p-error">{{ form.errors.tax_id }}</small>
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label for="address" class="font-semibold text-sm dark:text-zinc-300">Dirección</label>
                                    <Textarea id="address" v-model="form.address" rows="3" :class="{ 'p-invalid': form.errors.address }" />
                                    <small v-if="form.errors.address" class="p-error">{{ form.errors.address }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="status" class="font-semibold text-sm dark:text-zinc-300">Estado <span class="text-red-500">*</span></label>
                                    <Dropdown id="status" v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Selecciona un estado" :class="{ 'p-invalid': form.errors.status }" />
                                    <small v-if="form.errors.status" class="p-error">{{ form.errors.status }}</small>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label for="source" class="font-semibold text-sm dark:text-zinc-300">Origen del Contacto</label>
                                    <InputText id="source" v-model="form.source" :class="{ 'p-invalid': form.errors.source }" placeholder="Ej. Sitio Web, Referencia"/>
                                    <small v-if="form.errors.source" class="p-error">{{ form.errors.source }}</small>
                                </div>
                            </div>

                            <Divider align="left" type="solid" class="my-6 md:my-8">
                                <b class="text-gray-500 dark:text-zinc-400 text-sm font-bold uppercase tracking-wider">Contactos</b>
                            </Divider>

                            <!-- SECCIÓN DE CONTACTOS DINÁMICOS -->
                            <div v-for="(contact, index) in form.contacts" :key="index" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center mb-6 p-4 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-100 dark:border-zinc-800 relative transition-all">
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label :for="'contact_name_' + index" class="font-semibold text-xs dark:text-zinc-400">Nombre Completo <span class="text-red-500">*</span></label>
                                    <InputText :id="'contact_name_' + index" v-model="contact.name" :class="{ 'p-invalid': form.errors[`contacts.${index}.name`] }" />
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-2">
                                    <label :for="'contact_email_' + index" class="font-semibold text-xs dark:text-zinc-400">Email</label>
                                    <InputText type="email" :id="'contact_email_' + index" v-model="contact.email" :class="{ 'p-invalid': form.errors[`contacts.${index}.email`] }" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <label :for="'contact_phone_' + index" class="font-semibold text-xs dark:text-zinc-400">Teléfono</label>
                                    <InputText :id="'contact_phone_' + index" v-model="contact.phone" :class="{ 'p-invalid': form.errors[`contacts.${index}.phone`] }" />
                                </div>
                                <div class="flex flex-col gap-2 md:col-span-4">
                                    <label :for="'contact_position_' + index" class="font-semibold text-xs dark:text-zinc-400">Puesto</label>
                                    <InputText :id="'contact_position_' + index" v-model="contact.position" :class="{ 'p-invalid': form.errors[`contacts.${index}.position`] }" />
                                </div>
                                <!-- Botón de eliminar adaptativo: Texto ancho en móvil, icono a la derecha en PC -->
                                <div class="md:col-span-1 flex justify-end items-end w-full h-full mt-2 md:mt-0 md:pb-1">
                                    <Button icon="pi pi-trash" label="Eliminar" severity="danger" outlined class="w-full md:hidden" @click="removeContact(index)" />
                                    <Button icon="pi pi-trash" severity="danger" text rounded @click="removeContact(index)" class="hidden md:inline-flex hover:bg-red-50 dark:hover:bg-red-900/20" />
                                </div>
                            </div>

                            <Button label="Agregar Otro Contacto" icon="pi pi-plus" severity="secondary" size="small" text @click="addContact" class="mt-2 w-full sm:w-auto"/>
                        </template>

                        <template #footer>
                            <!-- Botones en footer cambian a col-reverse en móviles para mejor experiencia táctil -->
                            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-4 sm:mt-6 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                <Link href="/clients" class="w-full sm:w-auto block">
                                    <Button label="Cancelar" severity="secondary" text class="w-full" />
                                </Link>
                                <Button label="Guardar Cliente" icon="pi pi-check" class="w-full sm:w-auto !text-[var(--primary-text-color)]" @click="submit" :loading="form.processing" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.p-inputtext, .p-dropdown, .p-textarea { width: 100%; }
.dark .p-inputtext, .dark .p-dropdown, .dark .p-textarea {
    background-color: #27272a; /* zinc-800 */
    color: #f4f4f5; /* zinc-100 */
    border-color: #3f3f46; /* zinc-700 */
}
.dark .p-inputtext:focus, .dark .p-dropdown:not(.p-disabled).p-focus, .dark .p-textarea:focus {
    border-color: var(--p-primary-color);
}
</style>