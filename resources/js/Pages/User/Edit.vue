<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Card from 'primevue/card';

// --- PROPS ---
const props = defineProps({
    user: Object,
});

// --- FORM ---
// Inicializa el formulario con los datos del usuario.
const form = useForm({
    name: props.user.name,
    email: props.user.email,
});

// --- METHODS ---
const submit = () => {
    // Usa el método PUT para actualizar el recurso.
    form.put(route('users.update', props.user.id));
};
</script>

<template>
    <AppLayout title="Editar Usuario">
        <div class="p-4 sm:p-6 lg:p-8">
            <Back :route="'users.index'" />
            <div class="max-w-2xl mx-auto mt-4">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-gray-800 dark:border-gray-700">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Editar Usuario</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-600 dark:text-gray-400">Actualiza la información del usuario.</p>
                        </template>

                        <template #content>
                            <div class="space-y-6 mt-6">

                                <div class="flex flex-col gap-2">
                                    <label for="name" class="font-semibold dark:text-gray-300">Nombre Completo <span class="text-red-500">*</span></label>
                                    <InputText id="name" v-model="form.name" :class="{ 'p-invalid': form.errors.name }" />
                                    <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="email" class="font-semibold dark:text-gray-300">Correo Electrónico <span class="text-red-500">*</span></label>
                                    <InputText id="email" v-model="form.email" :class="{ 'p-invalid': form.errors.email }" />
                                    <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
                                </div>

                            </div>
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-2 mt-6">
                                <Link :href="route('users.index')">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button label="Actualizar Usuario" icon="pi pi-check" @click="submit" :loading="form.processing" />
                            </div>
                        </template>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Asegura que los componentes de PrimeVue ocupen todo el ancho */
.p-inputtext {
    width: 100%;
}
</style>
