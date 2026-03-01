<script setup>
import { ref, computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Avatar from 'primevue/avatar';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

// --- PROPS ---
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    }
});

// --- HELPERS ---
const formatDate = (dateString, includeTime = false) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return date.toLocaleDateString('es-MX', options);
};

const getTaskStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'info',
        'En Progreso': 'warn',
        'Completada': 'success',
        'Bloqueada': 'danger',
    };
    return statuses[status] || 'secondary';
};

const getProjectStatusSeverity = (status) => {
    const statuses = {
        'Activo': 'success',
        'Pausado': 'warn',
        'Completado': 'info',
        'Cancelado': 'danger',
    };
    return statuses[status] || 'secondary';
};

const getQuoteStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'info',
        'Enviado': 'warn',
        'Aceptado': 'success',
        'Pagado': 'success',
        'Rechazado': 'danger',
    };
    return statuses[status] || 'secondary';
};

const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) {
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(0);
    }
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(numericValue);
};

</script>

<template>
    <Head :title="`Detalle de ${user.name}`" />
    <AppLayout title="Detalle de Usuario">
        <div class="p-4 sm:p-6 lg:p-8 min-h-screen">
            <div class="max-w-7xl mx-auto">

                <!-- Header -->
                <header class="mb-8">
                    <div class="max-w-4xl mx-30 mb-6">
                        <Link :href="route('users.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                            <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                        </Link>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img :src="user.profile_photo_url" alt="" class="object-cover size-24 rounded-full border-4 border-white dark:border-zinc-800 shadow-md" />
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800 dark:text-zinc-100">{{ user.name }}</h1>
                                <p class="text-gray-500 dark:text-zinc-400 mt-1">{{ user.email }}</p>
                            </div>
                        </div>
                         <div class="flex items-center gap-2">
                            <Link :href="route('users.edit', user.id)">
                                <Button label="Editar Perfil" icon="pi pi-pencil" severity="secondary" outlined rounded class="!bg-white dark:!bg-transparent" />
                            </Link>
                        </div>
                    </div>
                </header>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                        <template #title><span class="text-gray-500 dark:text-zinc-500 text-xs font-bold uppercase tracking-wider">Tareas Completadas</span></template>
                        <template #content>
                            <div class="flex items-center gap-3">
                                <i class="pi pi-check-square text-2xl text-emerald-500"></i>
                                <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ stats.completedTasksCount }}</p>
                            </div>
                        </template>
                    </Card>
                     <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                        <template #title><span class="text-gray-500 dark:text-zinc-500 text-xs font-bold uppercase tracking-wider">Tiempo Invertido (HH:MM)</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-clock text-2xl text-blue-500"></i>
                                <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ stats.totalTimeInvested }}</p>
                            </div>
                        </template>
                    </Card>
                     <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                        <template #title><span class="text-gray-500 dark:text-zinc-500 text-xs font-bold uppercase tracking-wider">Cotizaciones</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-file-edit text-2xl text-purple-500"></i>
                                <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ stats.quotesCount }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl">
                        <template #title><span class="text-gray-500 dark:text-zinc-500 text-xs font-bold uppercase tracking-wider">Proyectos</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-briefcase text-2xl text-orange-500"></i>
                                <p class="text-3xl font-bold text-gray-900 dark:text-zinc-100">{{ stats.projectsCount }}</p>
                            </div>
                        </template>
                    </Card>
                </div>


                <!-- Tabs -->
                <Card class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <template #content>
                        <TabView class="custom-tabview">
                            <TabPanel header="Información General">
                                <div class="p-4">
                                    <ul class="space-y-4 text-gray-700 dark:text-zinc-300">
                                        <li><strong class="font-semibold w-40 inline-block text-gray-900 dark:text-zinc-100">Nombre:</strong> {{ user.name }}</li>
                                        <li><strong class="font-semibold w-40 inline-block text-gray-900 dark:text-zinc-100">Email:</strong> {{ user.email }}</li>
                                        <li><strong class="font-semibold w-40 inline-block text-gray-900 dark:text-zinc-100">Miembro desde:</strong> {{ formatDate(user.created_at) }}</li>
                                        <li>
                                            <strong class="font-semibold w-40 inline-block text-gray-900 dark:text-zinc-100">Último Ingreso:</strong>
                                            <span v-if="user.last_login_at">{{ formatDate(user.last_login_at, true) }}</span>
                                            <span v-else class="text-gray-500 dark:text-zinc-500">No registrado</span>
                                        </li>
                                    </ul>
                                </div>
                            </TabPanel>
                            <TabPanel header="Tareas Asignadas">
                                <DataTable :value="user.assigned_tasks" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;" class="zinc-table">
                                    <template #empty> <div class="p-4 text-center text-gray-500">Este usuario no tiene tareas asignadas.</div> </template>
                                    <Column field="title" header="Título" sortable>
                                        <template #body="{ data }"><span class="font-medium text-gray-700 dark:text-zinc-200">{{ data.title }}</span></template>
                                    </Column>
                                    <Column field="project.name" header="Proyecto" sortable>
                                        <template #body="{ data }">
                                            <span class="text-gray-600 dark:text-zinc-400">{{ data.project?.name || 'N/A' }}</span>
                                        </template>
                                    </Column>
                                    <Column field="due_date" header="Fecha Límite" sortable>
                                        <template #body="{ data }">{{ formatDate(data.due_date) }}</template>
                                    </Column>
                                    <Column field="status" header="Estado" sortable>
                                        <template #body="{ data }">
                                            <Tag :value="data.status" :severity="getTaskStatusSeverity(data.status)" />
                                        </template>
                                    </Column>
                                     <Column field="total_hours_invested" header="Tiempo Invertido" sortable>
                                         <template #body="{ data }"><span class="text-gray-600 dark:text-zinc-300">{{ data.total_hours_invested || '00:00' }}</span></template>
                                     </Column>
                                </DataTable>
                            </TabPanel>
                            <TabPanel header="Proyectos">
                                 <DataTable :value="user.projects" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;" class="zinc-table">
                                    <template #empty> <div class="p-4 text-center text-gray-500">Este usuario no está en ningún proyecto.</div> </template>
                                    <Column field="name" header="Nombre del Proyecto" sortable>
                                        <template #body="{ data }"><span class="font-medium text-gray-700 dark:text-zinc-200">{{ data.name }}</span></template>
                                    </Column>
                                    <Column field="client.name" header="Cliente" sortable>
                                         <template #body="{ data }">
                                            <span class="text-gray-600 dark:text-zinc-400">{{ data.client?.name || 'N/A' }}</span>
                                        </template>
                                    </Column>
                                    <Column field="status" header="Estado" sortable>
                                        <template #body="{ data }">
                                            <Tag :value="data.status" :severity="getProjectStatusSeverity(data.status)" />
                                        </template>
                                    </Column>
                                    <Column field="pivot.role_in_project" header="Rol en Proyecto" sortable></Column>
                                </DataTable>
                            </TabPanel>
                             <TabPanel header="Cotizaciones">
                                 <DataTable :value="user.quotes" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;" class="zinc-table">
                                    <template #empty> <div class="p-4 text-center text-gray-500">Este usuario no ha creado cotizaciones.</div> </template>
                                     <Column field="id" header="Folio" sortable>
                                        <template #body="{data}"><span class="text-gray-500 dark:text-zinc-400">Cot-{{ data.id }}</span></template>
                                    </Column>
                                    <Column field="title" header="Título" sortable>
                                        <template #body="{data}"><span class="font-medium text-gray-700 dark:text-zinc-200">{{ data.title }}</span></template>
                                    </Column>
                                     <Column field="client.name" header="Cliente" sortable>
                                         <template #body="{ data }">
                                            <span class="text-gray-600 dark:text-zinc-400">{{ data.client?.name || 'N/A' }}</span>
                                        </template>
                                    </Column>
                                    <Column field="final_amount" header="Monto" sortable>
                                        <template #body="{ data }"><span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.final_amount) }}</span></template>
                                    </Column>
                                    <Column field="status" header="Estado" sortable>
                                        <template #body="{ data }">
                                            <Tag :value="data.status" :severity="getQuoteStatusSeverity(data.status)" />
                                        </template>
                                    </Column>
                                </DataTable>
                            </TabPanel>
                        </TabView>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.p-card .p-card-content { padding-top: 0; }
.p-card .p-card-title { margin-bottom: 1rem; }

/* TabView Customization for Zinc Theme */
:deep(.custom-tabview .p-tabview-nav-content) { background: transparent !important; }
:deep(.custom-tabview .p-tabview-nav) {
    background: transparent !important;
    border-bottom: 1px solid #e4e4e7 !important; /* zinc-200 */
}
.dark :deep(.custom-tabview .p-tabview-nav) { border-bottom-color: #27272a !important; /* zinc-800 */ }

:deep(.custom-tabview .p-tabview-nav-link) {
    background: transparent !important;
    border: none !important;
    border-bottom: 2px solid transparent !important;
    color: #71717a !important; /* zinc-500 */
    font-weight: 600;
    transition: all 0.2s;
}
.dark :deep(.custom-tabview .p-tabview-nav-link) { color: #a1a1aa !important; /* zinc-400 */ }

:deep(.custom-tabview .p-tabview-nav-link:hover) { color: #3b82f6 !important; } /* blue-500 */
.dark :deep(.custom-tabview .p-tabview-nav-link:hover) { color: #60a5fa !important; } /* blue-400 */

:deep(.custom-tabview .p-tabview-selected .p-tabview-nav-link) {
    color: #2563eb !important; /* blue-600 */
    border-bottom-color: #2563eb !important;
}
.dark :deep(.custom-tabview .p-tabview-selected .p-tabview-nav-link) {
    color: #60a5fa !important; /* blue-400 */
    border-bottom-color: #60a5fa !important;
}
:deep(.custom-tabview .p-tabview-ink-bar) { display: none !important; }
:deep(.custom-tabview .p-tabview-panels) { background: transparent !important; padding: 1.5rem 0 !important; }

/* Zinc Theme Overrides for PrimeVue DataTable */
:deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
}
.dark :deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #18181b !important;
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a;
}
:deep(.zinc-table .p-datatable-tbody > tr) {
    background-color: transparent !important;
    color: inherit;
}
:deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #f4f4f5;
}
.dark :deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #27272a;
}
</style>