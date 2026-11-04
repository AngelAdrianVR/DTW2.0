<script setup>
/**
 * Back-office · listado de eventos.
 *
 * Tabla con búsqueda, filtro por publicación y las acciones del día a día:
 * editar, ver participantes, publicar/ocultar y eliminar.
 */
import { ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import Pagination from '@/Components/MyComponents/Pagination.vue';
import { formatEventDate } from '@/Composables/useEvents';

const props = defineProps({
    events: { type: Object, required: true },
    filters: { type: Object, default: () => ({ search: '', status: 'all' }) },
});

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'all');

const statusOptions = [
    { label: 'Todos', value: 'all' },
    { label: 'Publicados', value: 'published' },
    { label: 'Borradores', value: 'draft' },
];

const STATUS_META = {
    draft: { label: 'Borrador', severity: 'secondary' },
    upcoming: { label: 'Próximamente', severity: 'info' },
    open: { label: 'Registro abierto', severity: 'success' },
    closed: { label: 'Registro cerrado', severity: 'warn' },
    finished: { label: 'Finalizado', severity: 'contrast' },
};

const statusMeta = (value) => STATUS_META[value] || STATUS_META.draft;

/** El flash compartido por HandleInertiaRequests alimenta los avisos. */
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.message) {
            toast.add({
                severity: flash.type === 'error' ? 'error' : 'success',
                summary: flash.type === 'error' ? 'Error' : 'Listo',
                detail: flash.message,
                life: 3200,
            });
        }
    },
    { deep: true, immediate: true },
);

let debounce = null;

function applyFilters() {
    clearTimeout(debounce);

    debounce = setTimeout(() => {
        router.get(
            route('admin.events.index'),
            { search: search.value, status: status.value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 350);
}

function togglePublish(event) {
    router.patch(route('admin.events.publish', event.id), {}, { preserveScroll: true });
}

function destroyEvent(event) {
    confirm.require({
        message: `¿Eliminar "${event.spanish_title}" y sus ${event.registrations_count ?? 0} registro(s)? Esta acción no se puede deshacer.`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(route('admin.events.destroy', event.id), { preserveScroll: true });
        },
    });
}
</script>

<template>
    <AppLayout title="Eventos">
        <Head title="Eventos" />

        <Toast />
        <ConfirmDialog />

        <div class="px-2 pb-10">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Eventos</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                        Convoca a tu comunidad, recibe registros y publica a los ganadores.
                    </p>
                </div>

                <Link
                    :href="route('admin.events.create')"
                    class="inline-flex w-fit items-center gap-2 rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <i class="pi pi-plus text-xs"></i>
                    Nuevo evento
                </Link>
            </div>

            <!-- Filtros -->
            <div
                class="mt-6 flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 md:flex-row md:items-center"
            >
                <span class="relative flex-1">
                    <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                    <InputText
                        v-model="search"
                        placeholder="Buscar por título o URL"
                        class="w-full pl-9"
                        @input="applyFilters"
                    />
                </span>

                <Dropdown
                    v-model="status"
                    :options="statusOptions"
                    option-label="label"
                    option-value="value"
                    class="w-full md:w-48"
                    @change="applyFilters"
                />

                <span class="text-xs font-medium text-gray-500 dark:text-zinc-400">
                    {{ events.total }} evento(s)
                </span>
            </div>

            <!-- Tabla -->
            <div
                class="mt-4 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <DataTable :value="events.data" data-key="id" responsive-layout="scroll" striped-rows>
                    <Column header="Evento">
                        <template #body="{ data }">
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-16 shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-zinc-800">
                                    <img
                                        v-if="data.image_url"
                                        :src="data.image_url"
                                        :alt="data.spanish_title"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900 dark:text-white">
                                        {{ data.spanish_title }}
                                    </p>
                                    <p class="truncate text-xs text-gray-500 dark:text-zinc-400">
                                        /eventos/{{ data.slug }}
                                    </p>
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Fecha">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-700 dark:text-zinc-200">
                                {{ formatEventDate(data.event_date, 'es') || '—' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Estatus">
                        <template #body="{ data }">
                            <Tag :value="statusMeta(data.status).label" :severity="statusMeta(data.status).severity" />
                        </template>
                    </Column>

                    <Column header="Registros">
                        <template #body="{ data }">
                            <span class="text-sm font-semibold text-gray-700 dark:text-zinc-200">
                                {{ data.registrations_count ?? 0 }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Ganadores">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-700 dark:text-zinc-200">{{ data.winners_total ?? 0 }}</span>
                        </template>
                    </Column>

                    <Column header="Acciones" style="width: 12rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-pencil"
                                    severity="secondary"
                                    text
                                    rounded
                                    aria-label="Editar"
                                    @click="router.visit(route('admin.events.edit', data.id))"
                                />
                                <Button
                                    icon="pi pi-users"
                                    severity="secondary"
                                    text
                                    rounded
                                    aria-label="Ver participantes"
                                    @click="router.visit(route('admin.events.show', data.id))"
                                />
                                <Button
                                    :icon="data.is_published ? 'pi pi-eye-slash' : 'pi pi-eye'"
                                    severity="secondary"
                                    text
                                    rounded
                                    :aria-label="data.is_published ? 'Ocultar' : 'Publicar'"
                                    @click="togglePublish(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    aria-label="Eliminar"
                                    @click="destroyEvent(data)"
                                />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="py-10 text-center text-sm text-gray-500 dark:text-zinc-400">
                            Todavía no hay eventos. Crea el primero para abrir una convocatoria.
                        </div>
                    </template>
                </DataTable>
            </div>

            <Pagination :links="events.links" />
        </div>
    </AppLayout>
</template>
