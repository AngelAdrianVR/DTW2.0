<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';

// PrimeVue Components
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import Tag from 'primevue/tag';
import Card from 'primevue/card';

// --- PROPS ---
const props = defineProps({
    users: {
        type: Object, // Ahora es un objeto de paginación
        required: true,
    }
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const menu = ref();
const selectedUserForMenu = ref(null);

// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedUserForMenu.value) return [];
    const user = selectedUserForMenu.value;
    return [
        {
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(route('users.show', user.id))
        },
        {
            label: 'Editar Usuario',
            icon: 'pi pi-pencil',
            command: () => router.get(route('users.edit', user.id))
        },
        {
            separator: true
        },
        {
            label: 'Eliminar Usuario',
            icon: 'pi pi-trash',
            command: () => confirmDeleteUser(user)
        }
    ];
});

const onRowClick = (event) => {
     router.get(route('users.show', event.data.id));
};

const rowClass = () => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors';

const toggleMenu = (event, user) => {
    selectedUserForMenu.value = user;
    menu.value.toggle(event);
};

// --- METHODS ---
const confirmDeleteUser = (user) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar a "${user.name}"? Esta acción no se puede deshacer.`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            deleteUser(user);
        }
    });
};

const deleteUser = (user) => {
    router.delete(route('users.destroy', user.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Usuario eliminado correctamente',
                life: 3000
            });
        },
        onError: () => {
             toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'No se pudo eliminar al usuario.',
                life: 3000
            });
        }
    });
};

const formatDate = (value) => {
    if (!value) return 'No verificado';
    const date = new Date(value);
    return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const getStatusSeverity = (isVerified) => (isVerified ? 'success' : 'warn');
</script>

<template>
    <AppLayout title="Usuarios">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-8 flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-zinc-100 text-[#212121]">Módulo de Usuarios</h1>
                        <p class="text-gray-400 dark:text-zinc-400 mt-1">Administra los miembros de tu equipo y sus accesos.</p>
                    </div>
                    <Link :href="route('users.create')">
                        <Button label="Crear Usuario" icon="pi pi-plus" class="!text-[var(--primary-text-color)]" />
                    </Link>
                </header>

                <!-- Vista de Tabla para Escritorio -->
                <div class="hidden md:block bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                    <DataTable @row-click="onRowClick" :rowClass="rowClass" :value="users.data" stripedRows paginator :rows="10" :totalRecords="users.total"
                               tableStyle="min-width: 50rem;" dataKey="id" class="index-user-table">
                        <template #empty> <div class="p-4 text-center text-gray-500">No se encontraron usuarios.</div> </template>

                        <Column field="id" header="ID" sortable style="width: 5%">
                             <template #body="{ data }"><span class="text-gray-500 dark:text-zinc-500 font-bold">#{{ data.id }}</span></template>
                        </Column>
                        <Column field="name" header="Nombre" sortable>
                             <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <img :src="data.profile_photo_url" :alt="data.name" class="w-8 h-8 rounded-full border border-gray-200 dark:border-zinc-700" />
                                    <div>
                                        <div class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-zinc-500">{{ data.email }}</div>
                                    </div>
                                </div>
                            </template>
                        </Column>
                         <Column field="email_verified_at" header="Verificado" sortable>
                            <template #body="{ data }">
                                <Tag :value="data.email_verified_at ? 'Verificado' : 'Pendiente'"
                                     :severity="getStatusSeverity(data.email_verified_at)" />
                            </template>
                        </Column>
                        <Column field="created_at" header="Fecha de Registro" sortable>
                            <template #body="{ data }">
                                <span class="text-gray-600 dark:text-zinc-400">{{ formatDate(data.created_at) }}</span>
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                            <template #body="{ data }">
                                <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                    aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" class="!text-gray-500 dark:!text-zinc-400 hover:!bg-gray-100 dark:hover:!bg-zinc-800" />
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                <!-- Vista de Tarjetas para Móvil -->
                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card v-for="user in users.data" :key="user.id" class="cursor-pointer dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm !rounded-xl" @click="onRowClick({data: user})">
                        <template #title>
                            <div class="flex justify-between items-start">
                                <div class="flex items-center gap-3">
                                    <img :src="user.profile_photo_url" :alt="user.name" class="w-8 h-8 rounded-full" />
                                    <span class="text-lg font-bold text-gray-800 dark:text-zinc-100">{{ user.name }}</span>
                                </div>
                                <Tag :value="user.email_verified_at ? 'Verificado' : 'Pendiente'"
                                     :severity="getStatusSeverity(user.email_verified_at)" />
                            </div>
                        </template>
                        <template #subtitle><span class="text-gray-500 dark:text-zinc-500 text-sm">{{ user.email }}</span></template>
                        <template #content>
                            <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2">Registrado: {{ formatDate(user.created_at) }}</p>
                        </template>
                        <template #footer>
                             <div class="flex justify-end">
                                <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, user)"
                                    aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" size="small" outlined />
                            </div>
                        </template>
                    </Card>
                     <div v-if="users.data.length === 0" class="text-center text-gray-500 dark:text-zinc-500 col-span-full mt-8">
                        No se encontraron usuarios.
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>


<style>
/* Estilos globales para la tabla de INDEX */
.index-user-table .p-datatable-thead > tr > th {
    background-color: #212121 !important;
    color: #d0d0d0 !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.index-user-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.index-user-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode para INDEX (Con el fondo claro que querías) */
html.dark .index-user-table .p-datatable-thead > tr > th,
.dark .index-user-table .p-datatable-thead > tr > th {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .index-user-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .index-user-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>