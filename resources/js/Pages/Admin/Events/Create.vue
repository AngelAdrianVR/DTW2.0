<script setup>
/**
 * Alta de un evento. El formulario es el mismo que usa la edición.
 */
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EventForm from './Partial/EventForm.vue';
</script>

<template>
    <AppLayout title="Nuevo evento">
        <Head title="Nuevo evento" />

        <div class="px-2 pb-10">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Nuevo evento</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                    Define el reto, los premios, la ventana de registro y el dato extra que pedirás a los participantes.
                </p>
            </div>

            <EventForm />
        </div>
    </AppLayout>
</template>
