<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";

// Layouts and Components
import AppLayout from '@/Layouts/AppLayout.vue';

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
import Dropdown from 'primevue/dropdown';
import Paginator from 'primevue/paginator';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';

// --- PROPS ---
const props = defineProps({
    projects: {
        type: Object,
        required: true,
    },
    clients: {
        type: Array,
        default: () => [],
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
const selectedStatus = ref(props.filters.status || null);
const selectedClient = ref(props.filters.client_id || null);
const menu = ref();
const selectedProjectForMenu = ref(null);

const statusOptions = ['Pendiente', 'En proceso', 'Completado', 'Pausado', 'Cancelado'];
const clientOptions = computed(() => {
    return [
        { id: 'interno', name: 'Proyecto Interno' },
        ...props.clients
    ];
});

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
watch([search, selectedStatus, selectedClient], debounce(([newSearch, newStatus, newClient]) => {
    router.get(route('projects.index'), { 
        search: newSearch, 
        status: newStatus, 
        client_id: newClient,
        page: 1 // Reseteamos la página a la 1 cuando cambian los filtros
    }, {
        preserveState: true,
        replace: true,
    });
}, 300));

// --- PAGINATION ACTION ---
const onPageChange = (event) => {
    router.get(route('projects.index'), {
        search: search.value,
        status: selectedStatus.value,
        client_id: selectedClient.value,
        page: event.page + 1
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

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
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
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
        'Pendiente': 'warning',
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

const rowClass = () => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors';

</script>

<template>
    <AppLayout title="Proyectos">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-6 sm:mb-8 text-center sm:text-left">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold dark:text-zinc-100 text-[#212121]">Módulo de Proyectos</h1>
                        <p class="text-sm sm:text-base text-gray-400 dark:text-zinc-400 mt-1">Gestiona todos tus proyectos, tareas y el tiempo invertido.</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl p-4 md:p-6 overflow-hidden">
                    
                    <!-- Barra de Búsqueda y Filtros adaptativa -->
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 lg:gap-8 mb-6">
                        <div class="flex flex-col md:flex-row flex-1 gap-3 w-full">
                            <IconField class="flex-1 w-full">
                                <InputIcon class="pi pi-search" />
                                <InputText 
                                    v-model="search" 
                                    placeholder="Buscar proyecto..." 
                                    class="w-full" 
                                />
                            </IconField>
                            <Dropdown v-model="selectedStatus" :options="statusOptions" placeholder="Estado" class="w-full md:w-48" showClear />
                            <Dropdown v-model="selectedClient" :options="clientOptions" optionLabel="name" optionValue="id" placeholder="Cliente" class="w-full md:w-56" showClear filter />
                        </div>
                        <Link :href="route('projects.create')" class="w-full lg:w-auto shrink-0 mt-2 lg:mt-0">
                            <Button label="Crear Proyecto" icon="pi pi-plus" class="w-full !text-[var(--primary-text-color)]" />
                        </Link>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block">
                        <DataTable :value="projects.data" stripedRows dataKey="id" @row-click="onRowClick" class="zinc-table" :row-class="rowClass" paginator :rows="10">
                            <template #empty> <div class="p-6 text-center text-gray-500">No se encontraron proyectos.</div> </template>

                            <Column header="ID" style="width: 5%">
                                <template #body="{ data }">
                                        <span class="font-bold text-gray-700 dark:text-zinc-300">#{{ data.id }}</span>
                                </template>
                            </Column>

                            <Column header="Proyecto" style="width: 30%">
                                <template #body="{ data }">
                                   <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 dark:text-zinc-100">{{ data.name }}</span>
                                            <Tag :value="data.status" :severity="getStatusSeverity(data.status)" class="!text-[10px] !px-2 uppercase" />
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-zinc-500">{{ data.client?.name || 'Proyecto Interno' }}</span>
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
                                            class="border-2 border-white dark:border-zinc-800"
                                        />
                                    </AvatarGroup>
                                     <span v-else class="text-xs text-gray-400">Sin asignar</span>
                                </template>
                            </Column>

                            <Column header="Progreso" style="width: 20%">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2.5">
                                            <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: getProjectProgress(data) + '%' }"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">{{ getProjectProgress(data) }}%</span>
                                    </div>
                                    <div class="text-[10px] sm:text-xs text-gray-400 dark:text-zinc-500 mt-1">
                                        {{ data.completed_tasks_count }} / {{ data.tasks_count }} tareas
                                    </div>
                                </template>
                            </Column>

                            <Column header="Horas Invertidas" style="width: 15%">
                                <template #body="{ data }">
                                    <span class="font-mono text-sm sm:text-lg text-gray-700 dark:text-zinc-300">{{ formatMinutes(data.total_invested_minutes) }}</span>
                                </template>
                            </Column>

                            <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                                <template #body="{ data }">
                                    <Button icon="pi pi-ellipsis-v" text rounded @click.stop="toggleMenu($event, data)" class="!text-gray-500 dark:!text-zinc-400 hover:!bg-gray-100 dark:hover:!bg-zinc-800"/>
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                     <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                    <!-- Mobile Card View -->
                    <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                        <Card v-for="project in projects.data" :key="project.id" @click="onRowClick({data: project})" class="cursor-pointer dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm !rounded-xl transition-all hover:shadow-md">
                            <template #title>
                                <div class="flex justify-between items-start gap-2">
                                    <span class="text-base sm:text-lg font-bold text-gray-800 dark:text-zinc-100 truncate pr-2">{{ project.name }}</span>
                                    <Tag :value="project.status" :severity="getStatusSeverity(project.status)" rounded class="!text-[9px] sm:!text-[10px] uppercase tracking-wider shrink-0" />
                                </div>
                            </template>
                             <template #subtitle>
                                 <span class="text-xs sm:text-sm text-gray-500 dark:text-zinc-500">{{ project.client?.name || 'Proyecto Interno' }}</span>
                             </template>
                            <template #content>
                                <div class="mb-4">
                                     <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300 mb-1.5 flex justify-between">
                                        <span>Progreso</span>
                                        <span>{{ getProjectProgress(project) }}%</span>
                                     </p>
                                     <div class="w-full bg-gray-200 dark:bg-zinc-700 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" :style="{ width: getProjectProgress(project) + '%' }"></div>
                                    </div>
                                    <p class="text-[10px] text-gray-400 mt-1 text-right">{{ project.completed_tasks_count }} / {{ project.tasks_count }} tareas</p>
                                </div>
                                <ul class="space-y-2 text-gray-700 dark:text-zinc-300 text-sm">
                                    <li class="flex justify-between items-center border-t border-gray-100 dark:border-zinc-800 pt-3 mt-2">
                                        <span class="font-bold text-gray-500 dark:text-zinc-400 text-xs uppercase tracking-wider">Tiempo Invertido:</span>
                                        <span class="font-bold font-mono text-base text-blue-600 dark:text-blue-400">{{ formatMinutes(project.total_invested_minutes) }}</span>
                                    </li>
                                </ul>
                            </template>
                             <template #footer>
                                <div class="flex justify-end pt-2 border-t border-gray-50 dark:border-zinc-800/50 mt-1">
                                    <Button label="Opciones" icon="pi pi-bars" @click.stop="toggleMenu($event, project)" severity="secondary" outlined size="small" class="w-full sm:w-auto" />
                                </div>
                            </template>
                        </Card>
                         <div v-if="projects.data.length === 0" class="text-center text-gray-500 dark:text-zinc-500 col-span-full py-8 bg-gray-50 dark:bg-zinc-800/30 rounded-xl">
                            <i class="pi pi-folder-open text-3xl mb-3 text-gray-300 dark:text-zinc-600"></i>
                            <p>No se encontraron proyectos con esos filtros.</p>
                        </div>
                    </div>

                    <!-- Paginación universal de PrimeVue -->
                    <Paginator 
                        v-if="projects.total > projects.per_page"
                        :first="(projects.current_page - 1) * projects.per_page" 
                        :rows="projects.per_page" 
                        :totalRecords="projects.total" 
                        @page="onPageChange"
                        class="mt-6 sm:mt-8 border-t border-gray-100 dark:border-zinc-800 pt-4"
                        :template="{
                            '640px': 'PrevPageLink CurrentPageReport NextPageLink',
                            '960px': 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink',
                            '1200px': 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink'
                        }"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
.p-avatar-group .p-avatar {
    border: 2px solid #fff;
}
.dark .p-avatar-group .p-avatar {
    border-color: #18181b; /* zinc-950 */
}

/* Zinc Theme Overrides for PrimeVue DataTable */
.zinc-table .p-datatable-thead > tr > th {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
}
.dark .zinc-table .p-datatable-thead > tr > th {
    background-color: #18181b !important; /* zinc-950 */
    color: #a1a1aa !important; /* zinc-400 */
    border-bottom: 1px solid #27272a; /* zinc-800 */
}
.zinc-table .p-datatable-tbody > tr {
    background-color: transparent !important;
    color: inherit;
}
.zinc-table .p-datatable-tbody > tr:not(:last-child) > td {
    border-bottom: 1px solid #f4f4f5;
}
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td {
    border-bottom: 1px solid #27272a;
}

/* Input overrides for dark mode */
.p-inputtext, .p-dropdown { width: 100%; }
.dark .p-inputtext, .dark .p-dropdown {
    background-color: #27272a; /* zinc-800 */
    color: #f4f4f5; /* zinc-100 */
    border-color: #3f3f46; /* zinc-700 */
}
</style>