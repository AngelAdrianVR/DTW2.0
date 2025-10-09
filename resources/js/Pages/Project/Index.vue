<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Layouts and Components
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/MyComponents/Pagination.vue';

// PrimeVue Components
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import Avatar from 'primevue/avatar';
import AvatarGroup from 'primevue/avatargroup';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Card from 'primevue/card';

// --- PROPS ---
const props = defineProps({
    projects: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    }
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const search = ref(props.filters.search || '');
const menu = ref();
const selectedProjectForMenu = ref(null);

// --- HELPERS ---
const debounce = (func, delay = 300) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
};

// --- WATCHERS ---
watch(search, debounce((value) => {
    router.get(route('projects.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedProjectForMenu.value) return [];
    const project = selectedProjectForMenu.value;
    return [
        {
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(route('projects.show', project.id))
        },
        {
            label: 'Editar Proyecto',
            icon: 'pi pi-pencil',
            command: () => router.get(route('projects.edit', project.id))
        },
        {
            separator: true
        },
        {
            label: 'Eliminar Proyecto',
            icon: 'pi pi-trash',
            command: () => confirmDeleteProject(project)
        }
    ];
});

const toggleMenu = (event, project) => {
    selectedProjectForMenu.value = project;
    menu.value.toggle(event);
};

// --- METHODS ---
const formatMinutes = (totalMinutes) => {
    if (totalMinutes === null || isNaN(totalMinutes) || totalMinutes <= 0) {
        return '0h 0m';
    }
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours}h ${minutes}m`;
};

const confirmDeleteProject = (project) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar el proyecto "${project.name}"?`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('projects.destroy', { project: project.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Éxito',
                        detail: 'Proyecto eliminado correctamente',
                        life: 3000
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'No se pudo eliminar el proyecto.',
                        life: 3000
                    });
                }
            });
        }
    });
};

const getProjectProgress = (project) => {
    if (!project.tasks_count || project.tasks_count === 0) {
        return 0;
    }
    return Math.round((project.completed_tasks_count / project.tasks_count) * 100);
};

const getStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'warn',
        'En proceso': 'info',
        'Completado': 'success',
        'Pausado': 'secondary',
        'Cancelado': 'danger',
    };
    return statuses[status] || 'secondary';
};

const onRowClick = (event) => {
     router.get(route('projects.show', event.data.id));
};

</script>

<template>
    <AppLayout title="Proyectos">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-8">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-gray-200 text-gray-800">Módulo de Proyectos</h1>
                        <p class="text-gray-400 mt-1">Gestiona todos tus proyectos, tareas y el tiempo invertido.</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 md:p-6">
                    <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
                        <span class="p-input-icon-left w-full md:w-1/3 flex items-center space-x-2">
                            <i class="pi pi-search" />
                            <InputText v-model="search" placeholder="Buscar por Nombre, Cliente o Estado..." class="w-full" />
                        </span>
                        <Link :href="route('projects.create')">
                            <Button label="Crear Proyecto" icon="pi pi-plus" />
                        </Link>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block">
                        <DataTable :value="projects.data" stripedRows dataKey="id"
                            @row-click="onRowClick" class="p-datatable-customers"
                            :row-class="() => 'cursor-pointer'">
                            <template #empty> No se encontraron proyectos. </template>

                            <Column header="ID" style="width: 4%">
                                <template #body="{ data }">
                                        <span class="font-bold">{{ data.id }}</span>
                                </template>
                            </Column>

                            <Column header="Proyecto" style="width: 30%">
                                <template #body="{ data }">
                                   <div class="flex flex-col">
                                        <span class="font-bold">{{ data.name }}</span>
                                        <span class="text-sm text-gray-500">{{ data.client?.name || 'Proyecto Interno' }}</span>
                                   </div>
                                </template>
                            </Column>

                            <Column header="Miembros" style="width: 15%">
                                <template #body="{ data }">
                                    <AvatarGroup v-if="data.members.length > 0">
                                        <Avatar v-for="member in data.members" :key="member.id"
                                            :image="member.profile_photo_url"
                                            shape="circle"
                                            :aria-label="member.name"
                                            v-tooltip="member.name"
                                        />
                                    </AvatarGroup>
                                     <span v-else class="text-xs text-gray-400">Sin asignar</span>
                                </template>
                            </Column>

                            <Column header="Progreso" style="width: 20%">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: getProjectProgress(data) + '%' }"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-500">{{ getProjectProgress(data) }}%</span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ data.completed_tasks_count }} / {{ data.tasks_count }} tareas
                                    </div>
                                </template>
                            </Column>

                            <Column header="Horas Invertidas" style="width: 15%">
                                <template #body="{ data }">
                                    <span class="font-mono text-lg">{{ formatMinutes(data.total_invested_minutes) }}</span>
                                </template>
                            </Column>

                            <Column field="status" header="Estado" style="width: 10%">
                                <template #body="{ data }">
                                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" rounded />
                                </template>
                            </Column>

                            <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                                <template #body="{ data }">
                                    <Button icon="pi pi-ellipsis-v" text rounded @click.stop="toggleMenu($event, data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                     <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                    <!-- Mobile Card View -->
                    <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <Card v-for="project in projects.data" :key="project.id" @click="onRowClick({data: project})">
                            <template #title>
                                <div class="flex justify-between items-start">
                                    <span class="text-lg font-bold">{{ project.name }}</span>
                                    <Tag :value="project.status" :severity="getStatusSeverity(project.status)" rounded />
                                </div>
                            </template>
                             <template #subtitle>{{ project.client?.name || 'Proyecto Interno' }}</template>
                            <template #content>
                                <div class="mb-4">
                                     <p class="font-semibold text-gray-700 dark:text-gray-300 mb-1">Progreso ({{ getProjectProgress(project) }}%)</p>
                                     <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                                        <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: getProjectProgress(project) + '%' }"></div>
                                    </div>
                                </div>
                                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <li class="flex justify-between border-t pt-2 mt-2">
                                        <span class="font-bold">Horas:</span>
                                        <span class="font-bold font-mono text-blue-600">{{ formatMinutes(project.total_invested_minutes) }}</span>
                                    </li>
                                </ul>
                            </template>
                             <template #footer>
                                <div class="flex justify-end">
                                    <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, project)" severity="secondary" />
                                </div>
                            </template>
                        </Card>
                         <div v-if="projects.data.length === 0" class="text-center text-gray-500 col-span-full mt-8">
                            No se encontraron proyectos.
                        </div>
                    </div>

                    <Pagination :links="projects.links" class="mt-6" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.p-avatar-group .p-avatar {
    border: 2px solid #fff; /* O el color de fondo de tu app */
}
.p-datatable .p-datatable-tbody > tr {
    cursor: pointer;
}
</style>
