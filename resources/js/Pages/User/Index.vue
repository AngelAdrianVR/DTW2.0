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
            command: () => router.get(route('users.edit', user.id)) // Descomentar cuando la ruta exista
        },
        // {
        //     label: 'Cambiar Contraseña',
        //     icon: 'pi pi-key',
        //     // command: () => router.get(route('users.password.edit', user.id)) // Descomentar cuando la ruta exista
        // },
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

const rowClass = () => 'cursor-pointer';

const toggleMenu = (event, user) => {
    selectedUserForMenu.value = user;
    menu.value.toggle(event);
};

// --- METHODS ---
const confirmDeleteUser = (user) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar a "${user.name}"? Esta acción no se puede deshacer.`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            deleteUser(user);
        }
    });
};

const deleteUser = (user) => {
    // router.delete(route('users.destroy', user.id), { // Descomentar cuando la ruta exista
    //     preserveScroll: true,
    //     onSuccess: () => {
    //         toast.add({
    //             severity: 'success',
    //             summary: 'Éxito',
    //             detail: 'Usuario eliminado correctamente',
    //             life: 3000
    //         });
    //     },
    //     onError: () => {
    //          toast.add({
    //             severity: 'error',
    //             summary: 'Error',
    //             detail: 'No se pudo eliminar al usuario.',
    //             life: 3000
    //         });
    //     }
    // });
    // Alerta temporal mientras no exista la ruta
     toast.add({ severity: 'info', summary: 'Información', detail: `Funcionalidad para eliminar a ${user.name} aún no implementada.`, life: 3000 });
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
                        <h1 class="text-3xl font-bold dark:text-gray-200 text-gray-800">Módulo de Usuarios</h1>
                        <p class="text-gray-400 mt-1">Administra los miembros de tu equipo y sus accesos.</p>
                    </div>
                    <Link :href="route('users.create')">
                        <Button label="Crear Usuario" icon="pi pi-plus" />
                    </Link>
                </header>

                <!-- Vista de Tabla para Escritorio -->
                <div class="hidden md:block">
                    <DataTable @row-click="onRowClick" :rowClass="rowClass" :value="users.data" stripedRows paginator :rows="10" :totalRecords="users.total"
                               tableStyle="min-width: 50rem;" dataKey="id">
                        <template #empty> No se encontraron usuarios. </template>

                        <Column field="id" header="ID" sortable style="width: 5%"></Column>
                        <Column field="name" header="Nombre" sortable>
                             <template #body="{ data }">
                                <div class="flex items-center gap-3">
                                    <img :src="data.profile_photo_url" :alt="data.name" class="w-8 h-8 rounded-full" />
                                    <div>
                                        <div class="font-semibold">{{ data.name }}</div>
                                        <div class="text-sm text-gray-500">{{ data.email }}</div>
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
                                {{ formatDate(data.created_at) }}
                            </template>
                        </Column>
                        <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                            <template #body="{ data }">
                                <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                    aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" />
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                <!-- Vista de Tarjetas para Móvil -->
                <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <Card v-for="user in users.data" :key="user.id">
                        <template #title>
                            <div class="flex justify-between items-start">
                                <span class="text-lg font-bold">{{ user.name }}</span>
                                <Tag :value="user.email_verified_at ? 'Verificado' : 'Pendiente'"
                                     :severity="getStatusSeverity(user.email_verified_at)" />
                            </div>
                        </template>
                        <template #subtitle>{{ user.email }}</template>
                        <template #content>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Registrado: {{ formatDate(user.created_at) }}</p>
                        </template>
                        <template #footer>
                             <div class="flex justify-end">
                                <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, user)"
                                    aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" />
                            </div>
                        </template>
                    </Card>
                     <div v-if="users.data.length === 0" class="text-center text-gray-500 col-span-full mt-8">
                        No se encontraron usuarios.
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
