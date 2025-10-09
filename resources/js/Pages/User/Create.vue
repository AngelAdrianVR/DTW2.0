<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Back from '@/Components/MyComponents/Back.vue';

// PrimeVue Components
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Card from 'primevue/card';

// --- FORM ---
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

// --- METHODS ---
const submit = () => {
    // La opción onFinish limpia los campos de contraseña después de un envío exitoso.
    form.post(route('users.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AppLayout title="Crear Usuario">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Este componente Back asume que tienes una ruta 'users.index' -->
            <Back :route="'dashboard'" />
            <div class="max-w-2xl mx-auto mt-4">
                <form @submit.prevent="submit">
                    <Card class="dark:bg-gray-800 dark:border-gray-700">
                        <template #title>
                            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200">Crear Nuevo Usuario</h2>
                        </template>
                        <template #subtitle>
                            <p class="text-gray-600 dark:text-gray-400">Completa los datos para registrar un nuevo miembro del equipo.</p>
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

                                <div class="flex flex-col gap-2">
                                    <label for="password" class="font-semibold dark:text-gray-300">Contraseña <span class="text-red-500">*</span></label>
                                    <Password id="password" v-model="form.password" :feedback="true" toggleMask :class="{ 'p-invalid': form.errors.password }" />
                                    <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
                                </div>

                                <div class="flex flex-col gap-2">
                                    <label for="password_confirmation" class="font-semibold dark:text-gray-300">Confirmar Contraseña <span class="text-red-500">*</span></label>
                                    <Password id="password_confirmation" v-model="form.password_confirmation" :feedback="false" toggleMask :class="{ 'p-invalid': form.errors.password_confirmation }" />
                                </div>

                            </div>
                        </template>

                        <template #footer>
                            <div class="flex justify-end gap-2 mt-6">
                                <!-- Cambia la ruta si no tienes 'users.index' -->
                                <Link :href="route('dashboard')">
                                    <Button label="Cancelar" severity="secondary" outlined />
                                </Link>
                                <Button label="Guardar Usuario" icon="pi pi-check" @click="submit" :loading="form.processing" />
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
.p-inputtext, .p-password {
    width: 100%;
}
</style>
