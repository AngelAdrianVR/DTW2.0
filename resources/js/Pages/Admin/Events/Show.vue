<script setup>
/**
 * Back-office · participantes y ganadores de un evento.
 *
 * Los ganadores se marcan a mano (no hay sorteo automático): desde la fila del
 * participante el diálogo llega precargado, y también se pueden capturar
 * ganadores que llegaron por otro canal.
 */
import { ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import Pagination from '@/Components/MyComponents/Pagination.vue';
import { formatEventDate } from '@/Composables/useEvents';

const props = defineProps({
    event: { type: Object, required: true },
    registrations: { type: Object, required: true },
    winners: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ search: '' }) },
});

const page = usePage();
const confirm = useConfirm();
const toast = useToast();

const search = ref(props.filters.search || '');

const STATUS_META = {
    draft: { label: 'Borrador', severity: 'secondary' },
    upcoming: { label: 'Próximamente', severity: 'info' },
    open: { label: 'Registro abierto', severity: 'success' },
    closed: { label: 'Registro cerrado', severity: 'warn' },
    finished: { label: 'Finalizado', severity: 'contrast' },
};

const statusMeta = (value) => STATUS_META[value] || STATUS_META.draft;

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
            route('admin.events.show', props.event.id),
            { search: search.value },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 350);
}

/* ------------------------------------------------------------------ */
/*  Ganadores                                                          */
/* ------------------------------------------------------------------ */
const winnerDialog = ref(false);
const editingWinner = ref(null);

const winnerForm = useForm({
    event_registration_id: null,
    name: '',
    business_name: '',
    spanish_prize: '',
    english_prize: '',
    spanish_note: '',
    english_note: '',
    awarded_at: '',
    is_published: true,
    sort_order: 0,
});

const toDate = (value) => (value ? String(value).slice(0, 10) : '');

function openNewWinner(registration = null) {
    editingWinner.value = null;
    winnerForm.reset();
    winnerForm.clearErrors();
    winnerForm.is_published = true;
    winnerForm.sort_order = props.winners.length;
    winnerForm.awarded_at = toDate(props.event.event_date);

    if (registration) {
        winnerForm.event_registration_id = registration.id;
        winnerForm.name = registration.name;
        winnerForm.business_name = registration.business_name || '';
    }

    winnerDialog.value = true;
}

function openEditWinner(winner) {
    editingWinner.value = winner;
    winnerForm.clearErrors();
    winnerForm.event_registration_id = winner.event_registration_id;
    winnerForm.name = winner.name;
    winnerForm.business_name = winner.business_name || '';
    winnerForm.spanish_prize = winner.spanish_prize;
    winnerForm.english_prize = winner.english_prize || '';
    winnerForm.spanish_note = winner.spanish_note || '';
    winnerForm.english_note = winner.english_note || '';
    winnerForm.awarded_at = toDate(winner.awarded_at);
    winnerForm.is_published = Boolean(winner.is_published);
    winnerForm.sort_order = winner.sort_order ?? 0;
    winnerDialog.value = true;
}

function saveWinner() {
    if (editingWinner.value) {
        winnerForm.put(
            route('admin.events.winners.update', [props.event.id, editingWinner.value.id]),
            { preserveScroll: true, onSuccess: () => (winnerDialog.value = false) },
        );

        return;
    }

    winnerForm.post(route('admin.events.winners.store', props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            winnerDialog.value = false;
            winnerForm.reset();
        },
    });
}

function destroyWinner(winner) {
    confirm.require({
        message: `¿Quitar a "${winner.name}" de los ganadores?`,
        header: 'Confirmar',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(
                route('admin.events.winners.destroy', [props.event.id, winner.id]),
                { preserveScroll: true },
            );
        },
    });
}

function destroyRegistration(registration) {
    confirm.require({
        message: `¿Eliminar el registro de "${registration.name}"? Esta acción no se puede deshacer.`,
        header: 'Confirmar eliminación',
        icon: 'pi pi-exclamation-triangle',
        accept: () => {
            router.delete(
                route('admin.events.registrations.destroy', [props.event.id, registration.id]),
                { preserveScroll: true },
            );
        },
    });
}

const whatsappLink = (phone) => `https://wa.me/${String(phone || '').replace(/\D+/g, '')}`;
</script>

<template>
    <AppLayout :title="event.spanish_title">
        <Head :title="`Participantes · ${event.spanish_title}`" />

        <Toast />
        <ConfirmDialog />

        <div class="px-2 pb-10">
            <!-- Encabezado -->
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <Link
                        :href="route('admin.events.index')"
                        class="text-xs font-semibold text-gray-500 transition hover:text-gray-800 dark:text-zinc-400 dark:hover:text-white"
                    >
                        ← Eventos
                    </Link>

                    <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                        {{ event.spanish_title }}
                    </h1>

                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <Tag :value="statusMeta(event.status).label" :severity="statusMeta(event.status).severity" />
                        <span class="text-xs text-gray-500 dark:text-zinc-400">
                            {{ formatEventDate(event.event_date, 'es') || 'Sin fecha' }}
                        </span>
                        <a
                            :href="`/eventos/${event.slug}`"
                            target="_blank"
                            rel="noopener"
                            class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            Ver en el sitio
                            <i class="pi pi-external-link text-[0.6rem]"></i>
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button
                        label="Editar evento"
                        icon="pi pi-pencil"
                        severity="secondary"
                        outlined
                        @click="router.visit(route('admin.events.edit', event.id))"
                    />
                    <Button label="Agregar ganador" icon="pi pi-plus" @click="openNewWinner()" />
                </div>
            </div>

            <!-- Indicadores -->
            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-400">
                        Registros
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        {{ event.registrations_count ?? 0 }}
                    </p>
                </div>

                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-400">
                        Ganadores
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">{{ winners.length }}</p>
                </div>

                <div
                    class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-zinc-400">
                        Cierra el registro
                    </p>
                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ formatEventDate(event.registration_ends_at, 'es') || 'Sin cierre' }}
                    </p>
                </div>
            </div>

            <!-- Ganadores -->
            <section
                class="mt-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Ganadores</h2>
                    <Button
                        label="Agregar"
                        icon="pi pi-plus"
                        size="small"
                        severity="secondary"
                        outlined
                        @click="openNewWinner()"
                    />
                </div>

                <ul v-if="winners.length" class="mt-4 divide-y divide-gray-100 dark:divide-zinc-800">
                    <li v-for="winner in winners" :key="winner.id" class="flex flex-wrap items-center gap-3 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900 dark:text-white">
                                {{ winner.name }}
                                <span
                                    v-if="winner.business_name"
                                    class="text-xs font-normal text-gray-500 dark:text-zinc-400"
                                >
                                    · {{ winner.business_name }}
                                </span>
                            </p>
                            <p class="text-xs text-gray-500 dark:text-zinc-400">
                                {{ winner.spanish_prize }}
                                <span v-if="winner.spanish_note"> · {{ winner.spanish_note }}</span>
                            </p>
                        </div>

                        <Tag v-if="!winner.is_published" value="Oculto" severity="secondary" />

                        <Button
                            icon="pi pi-pencil"
                            text
                            rounded
                            severity="secondary"
                            aria-label="Editar ganador"
                            @click="openEditWinner(winner)"
                        />
                        <Button
                            icon="pi pi-trash"
                            text
                            rounded
                            severity="danger"
                            aria-label="Eliminar ganador"
                            @click="destroyWinner(winner)"
                        />
                    </li>
                </ul>

                <p v-else class="mt-4 text-sm text-gray-500 dark:text-zinc-400">
                    Aún no hay ganadores cargados. Puedes marcarlos desde la lista de participantes.
                </p>
            </section>

            <!-- Participantes -->
            <section
                class="mt-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        Participantes
                        <span class="ml-1 text-sm font-normal text-gray-500 dark:text-zinc-400">
                            ({{ registrations.total }})
                        </span>
                    </h2>

                    <span class="relative md:w-72">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400"></i>
                        <InputText
                            v-model="search"
                            placeholder="Buscar por nombre, negocio o teléfono"
                            class="w-full pl-9"
                            @input="applyFilters"
                        />
                    </span>
                </div>

                <DataTable
                    :value="registrations.data"
                    data-key="id"
                    responsive-layout="scroll"
                    striped-rows
                    class="mt-4"
                >
                    <Column header="Participante">
                        <template #body="{ data }">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ data.name }}</p>
                                <p v-if="data.business_name" class="text-xs text-gray-500 dark:text-zinc-400">
                                    {{ data.business_name }}
                                </p>
                            </div>
                        </template>
                    </Column>

                    <Column header="Teléfono">
                        <template #body="{ data }">
                            <a
                                :href="whatsappLink(data.phone)"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                            >
                                <i class="pi pi-whatsapp"></i>
                                {{ data.phone }}
                            </a>
                        </template>
                    </Column>

                    <Column v-if="event.custom_field_label_es" :header="event.custom_field_label_es">
                        <template #body="{ data }">
                            <span class="text-sm text-gray-700 dark:text-zinc-200">
                                {{ data.custom_answer || '—' }}
                            </span>
                        </template>
                    </Column>

                    <Column header="Registro">
                        <template #body="{ data }">
                            <span class="text-xs text-gray-500 dark:text-zinc-400">{{ data.created_at }}</span>
                        </template>
                    </Column>

                    <Column header="Acciones" style="width: 8rem">
                        <template #body="{ data }">
                            <div class="flex items-center gap-1">
                                <Button
                                    icon="pi pi-trophy"
                                    severity="success"
                                    text
                                    rounded
                                    aria-label="Marcar como ganador"
                                    @click="openNewWinner(data)"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    severity="danger"
                                    text
                                    rounded
                                    aria-label="Eliminar registro"
                                    @click="destroyRegistration(data)"
                                />
                            </div>
                        </template>
                    </Column>

                    <template #empty>
                        <div class="py-10 text-center text-sm text-gray-500 dark:text-zinc-400">
                            Todavía no hay registros para este evento.
                        </div>
                    </template>
                </DataTable>

                <Pagination :links="registrations.links" />
            </section>
        </div>

        <!-- Diálogo de ganador -->
        <Dialog
            v-model:visible="winnerDialog"
            modal
            :header="editingWinner ? 'Editar ganador' : 'Nuevo ganador'"
            :style="{ width: '34rem', maxWidth: '95vw' }"
        >
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Nombre *</label>
                    <InputText v-model="winnerForm.name" class="w-full" />
                    <p v-if="winnerForm.errors.name" class="mt-1 text-xs text-red-600">
                        {{ winnerForm.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Negocio</label>
                    <InputText v-model="winnerForm.business_name" class="w-full" />
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Premio (ES) *
                        </label>
                        <InputText v-model="winnerForm.spanish_prize" class="w-full" />
                        <p v-if="winnerForm.errors.spanish_prize" class="mt-1 text-xs text-red-600">
                            {{ winnerForm.errors.spanish_prize }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Prize (EN)
                        </label>
                        <InputText v-model="winnerForm.english_prize" class="w-full" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Nota (ES)
                        </label>
                        <Textarea v-model="winnerForm.spanish_note" rows="2" class="w-full" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Note (EN)
                        </label>
                        <Textarea v-model="winnerForm.english_note" rows="2" class="w-full" />
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Fecha de entrega
                        </label>
                        <input
                            v-model="winnerForm.awarded_at"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Orden</label>
                        <InputText v-model="winnerForm.sort_order" type="number" min="0" class="w-full" />
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="winnerForm.is_published" input-id="winner_published" :binary="true" />
                    <label for="winner_published" class="text-sm text-gray-700 dark:text-zinc-200">
                        Mostrar en el sitio
                    </label>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" severity="secondary" text @click="winnerDialog = false" />
                <Button
                    :label="editingWinner ? 'Guardar' : 'Agregar'"
                    icon="pi pi-check"
                    :loading="winnerForm.processing"
                    @click="saveWinner"
                />
            </template>
        </Dialog>
    </AppLayout>
</template>
