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
                    <Link :href="route('users.index')" class="text-sm text-gray-500 dark:text-gray-400 flex items-center mb-4">
                        <i class="pi pi-arrow-left mr-2"></i>
                        Regresar a Usuarios
                    </Link>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <img :src="user.profile_photo_url" alt="" class="object-cover size-24 rounded-full border-2 border-gray-300 dark:border-gray-600" />
                            <div>
                                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-200">{{ user.name }}</h1>
                                <p class="text-gray-500 dark:text-gray-400 mt-1">{{ user.email }}</p>
                            </div>
                        </div>
                         <div class="flex items-center gap-2">
                            <Link :href="route('users.edit', user.id)">
                                <Button label="Editar Perfil" icon="pi pi-pencil" severity="secondary" outlined />
                            </Link>
                        </div>
                    </div>
                </header>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
                    <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Tareas Completadas</span></template>
                        <template #content>
                            <div class="flex items-center gap-3">
                                <i class="pi pi-check-square text-2xl text-green-500"></i>
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.completedTasksCount }}</p>
                            </div>
                        </template>
                    </Card>
                     <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Tiempo Invertido (HH:MM)</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-clock text-2xl text-blue-500"></i>
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.totalTimeInvested }}</p>
                            </div>
                        </template>
                    </Card>
                     <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Cotizaciones Creadas</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-file-edit text-2xl text-purple-500"></i>
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.quotesCount }}</p>
                            </div>
                        </template>
                    </Card>
                    <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                        <template #title><span class="text-gray-500 dark:text-gray-400 text-sm font-normal">Proyectos Asignados</span></template>
                        <template #content>
                             <div class="flex items-center gap-3">
                                <i class="pi pi-briefcase text-2xl text-orange-500"></i>
                                <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ stats.projectsCount }}</p>
                            </div>
                        </template>
                    </Card>
                </div>


                <!-- Tabs -->
                <Card class="bg-white dark:bg-gray-800 shadow-md rounded-lg">
                    <template #content>
                        <TabView>
                            <TabPanel header="Información General">
                                <div class="p-4">
                                    <ul class="space-y-4 text-gray-700 dark:text-gray-300">
                                        <li><strong class="font-semibold w-40 inline-block">Nombre:</strong> {{ user.name }}</li>
                                        <li><strong class="font-semibold w-40 inline-block">Email:</strong> {{ user.email }}</li>
                                        <li><strong class="font-semibold w-40 inline-block">Miembro desde:</strong> {{ formatDate(user.created_at) }}</li>
                                        <li>
                                            <strong class="font-semibold w-40 inline-block">Último Ingreso:</strong>
                                            <span v-if="user.last_login_at">{{ formatDate(user.last_login_at, true) }}</span>
                                            <span v-else class="text-gray-500">No registrado</span>
                                        </li>
                                    </ul>
                                </div>
                            </TabPanel>
                            <TabPanel header="Tareas Asignadas">
                                <DataTable :value="user.assigned_tasks" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;">
                                    <template #empty> Este usuario no tiene tareas asignadas. </template>
                                    <Column field="title" header="Título" sortable></Column>
                                    <Column field="project.name" header="Proyecto" sortable>
                                        <template #body="{ data }">
                                            {{ data.project?.name || 'N/A' }}
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
                                     <Column field="total_hours_invested" header="Tiempo Invertido" sortable></Column>
                                </DataTable>
                            </TabPanel>
                            <TabPanel header="Proyectos">
                                 <DataTable :value="user.projects" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;">
                                    <template #empty> Este usuario no está en ningún proyecto. </template>
                                    <Column field="name" header="Nombre del Proyecto" sortable></Column>
                                    <Column field="client.name" header="Cliente" sortable>
                                         <template #body="{ data }">
                                            {{ data.client?.name || 'N/A' }}
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
                                 <DataTable :value="user.quotes" stripedRows responsiveLayout="scroll" paginator :rows="10" tableStyle="min-width: 50rem;">
                                    <template #empty> Este usuario no ha creado cotizaciones. </template>
                                     <Column field="id" header="Folio" sortable>
                                        <template #body="{data}">Cot-{{ data.id }}</template>
                                    </Column>
                                    <Column field="title" header="Título" sortable></Column>
                                     <Column field="client.name" header="Cliente" sortable>
                                         <template #body="{ data }">
                                            {{ data.client?.name || 'N/A' }}
                                        </template>
                                    </Column>
                                    <Column field="final_amount" header="Monto" sortable>
                                        <template #body="{ data }">{{ formatCurrency(data.final_amount) }}</template>
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

<style>
.p-card .p-card-content {
    padding-top: 0;
}
.p-card .p-card-title {
    margin-bottom: 1rem;
}
.p-tabview-nav {
    border-bottom: 1px solid #dee2e6; /* PrimeVue default */
}
.dark .p-tabview-nav {
     border-bottom: 1px solid #4a5568; /* Dark mode border */
}

</style>
