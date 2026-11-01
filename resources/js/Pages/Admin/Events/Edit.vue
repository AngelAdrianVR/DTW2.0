<script setup>
/**
 * Edición de un evento. Reutiliza el formulario del alta.
 */
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EventForm from './Partial/EventForm.vue';

defineProps({
    event: { type: Object, required: true },
});
</script>

<template>
    <AppLayout title="Editar evento">
        <Head :title="`Editar · ${event.spanish_title}`" />

        <div class="px-2 pb-10">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editar evento</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                        /eventos/{{ event.slug }} · {{ event.registrations_count ?? 0 }} registro(s)
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('admin.events.show', event.id)"
                        class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <i class="pi pi-users text-xs"></i>
                        Participantes
                    </Link>

                    <a
                        :href="`/eventos/${event.slug}`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <i class="pi pi-external-link text-xs"></i>
                        Ver en el sitio
                    </a>
                </div>
            </div>

            <EventForm :event="event" />
        </div>
    </AppLayout>
</template>
