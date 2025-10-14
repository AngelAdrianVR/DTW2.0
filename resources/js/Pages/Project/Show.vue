<script setup>
import { ref, computed } from 'vue';
import { Head, usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

// Import PrimeVue components
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Card from 'primevue/card';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Calendar from 'primevue/calendar';
import Dropdown from 'primevue/dropdown';
import Back from '@/Components/MyComponents/Back.vue';

const { props } = usePage();

// Usamos `computed` para que las variables reaccionen automáticamente cuando Inertia
// actualice las props después de una acción (como el cambio de estado).
// Este enfoque es excelente y es la forma correcta de hacerlo.
const project = computed(() => props.project);
const totalTimeInvested = computed(() => props.totalTimeInvested);

// --- Funciones de formato ---
const formatMinutesToHours = (totalMinutes) => {
    if (!totalMinutes || totalMinutes <= 0) {
        return '00:00';
    }
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    // Usamos padStart para asegurar que siempre haya dos dígitos (e.g., 01:05)
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
};

// --- Nueva propiedad computada para el desglose de tiempo por usuario ---
const timeInvestedByUser = computed(() => {
    if (!project.value || !project.value.tasks) {
        return [];
    }

    // Usamos reduce para agrupar los minutos por usuario
    const timeByUser = project.value.tasks.reduce((acc, task) => {
        // Nos aseguramos de que la tarea tenga un usuario asignado y minutos invertidos
        if (task.assignee && task.total_invested_minutes > 0) {
            const userId = task.assignee.id;

            // Si es la primera vez que vemos a este usuario, lo inicializamos en el acumulador
            if (!acc[userId]) {
                acc[userId] = {
                    user: task.assignee, // Guardamos el objeto completo del usuario
                    totalMinutes: 0,
                };
            }
            // Sumamos los minutos de la tarea actual
            acc[userId].totalMinutes += task.total_invested_minutes;
        }
        return acc;
    }, {});

    // Convertimos el objeto de usuarios en un array, formateamos el tiempo y lo ordenamos
    return Object.values(timeByUser).map(item => ({
        ...item,
        formattedTime: formatMinutesToHours(item.totalMinutes),
    })).sort((a, b) => b.totalMinutes - a.totalMinutes); // Ordenamos de mayor a menor tiempo
});


// --- State para Modales ---
const isCreateTaskModalVisible = ref(false);
const isEditTaskModalVisible = ref(false);
const selectedTask = ref(null);

// --- Formulario para Crear Tarea ---
const createTaskForm = useForm({
    project_id: project.value.id,
    title: '',
    description: '',
    assigned_to: null,
    due_date: null,
});

// --- Formulario para Editar Tarea ---
const editTaskForm = useForm({
    title: '',
    description: '',
    assigned_to: null,
    due_date: null,
});

// --- Funciones para manejar Modales y Formularios ---
const openCreateTaskModal = () => {
    createTaskForm.reset();
    isCreateTaskModalVisible.value = true;
};

const submitCreateTask = () => {
    createTaskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            window.location.reload();
            // isCreateTaskModalVisible.value = false;
        },
    });
};

const openEditTaskModal = (task) => {
    selectedTask.value = task;
    editTaskForm.title = task.title;
    editTaskForm.description = task.description;
    editTaskForm.assigned_to = task.assignee?.id || null;
    editTaskForm.due_date = task.due_date ? new Date(task.due_date) : null;
    isEditTaskModalVisible.value = true;
};

const submitUpdateTask = () => {
    if (!selectedTask.value) return;
    editTaskForm.put(route('tasks.update', { task: selectedTask.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isEditTaskModalVisible.value = false;
        },
    });
};

const submitDeleteTask = () => {
    if (!selectedTask.value) return;
    router.delete(route('tasks.destroy', { task: selectedTask.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            isEditTaskModalVisible.value = false;
        },
    });
};

// --- Lógica de Kanban (Drag and Drop) ---
const statuses = ['Pendiente', 'En proceso', 'Completada'];

const tasksByStatus = computed(() => {
    const groupedTasks = { 'Pendiente': [], 'En proceso': [], 'Completada': [] };
    if (project.value && project.value.tasks) {
        project.value.tasks.forEach(task => {
            if (groupedTasks[task.status]) {
                groupedTasks[task.status].push(task);
            }
        });
    }
    return groupedTasks;
});

const draggedTask = ref(null);

const onDragStart = (task) => {
    draggedTask.value = task;
};

const onDrop = (newStatus) => {
    if (!draggedTask.value || draggedTask.value.status === newStatus) {
        draggedTask.value = null;
        return;
    }

    const taskToMove = project.value.tasks.find(t => t.id === draggedTask.value.id);
    const originalStatus = taskToMove.status;
    
    // Actualización optimista: movemos la tarea en la UI inmediatamente.
    if (taskToMove) {
        taskToMove.status = newStatus;
    }

    // --- CAMBIO CLAVE ---
    // Hacemos la petición al backend para que actualice el estado y el tiempo.
    router.patch(route('tasks.updateStatus', { task: draggedTask.value.id }), {
        status: newStatus,
    }, {
        preserveScroll: true,
        // ¡OJO! No usamos 'preserveState: true'.
        // Esto es fundamental. Al omitirlo, Inertia.js solicitará las props actualizadas
        // del servidor después de que el controlador haga la redirección.
        // Así es como 'project' y 'totalTimeInvested' se refrescan.
        onSuccess: () => {
            window.location.reload();
            // La UI se actualiza sola gracias a las propiedades computadas.
            // ¡No hay que hacer nada aquí!
        },
        onError: (errors) => {
            // Si hay un error en el backend, revertimos el cambio en la UI
            // para mantener la consistencia.
            if (taskToMove) {
                taskToMove.status = originalStatus;
            }
            console.error("Error al actualizar la tarea:", errors);
            // Aquí podrías mostrar una notificación de error al usuario.
        },
        onFinish: () => {
            // Limpiamos la tarea arrastrada, sin importar si fue éxito o error.
            draggedTask.value = null;
        }
    });
};


// --- Funciones de formato ---
const getStatusSeverity = (status) => {
    switch (status) {
        case 'Activo': return 'success';
        case 'Pausado': return 'warning';
        case 'Cancelado': return 'danger';
        default: return 'info';
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'short', day: 'numeric', timeZone: 'UTC' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};
</script>

<template>
    <Head :title="'Proyecto: ' + project.name" />

    <AppLayout>
        <div class="py-1">
            <Back :route="'projects.index'" />
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 md:p-8">

                    <!-- Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-2">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">{{ project.name }}</h1>
                            <!-- Total Time -->
                            <div class="flex items-center gap-3 text-cyan-600 dark:text-cyan-400 mb-3">
                                <i class="pi pi-clock" style="font-size: 1.2rem"></i>
                                <span class="text-lg font-semibold">{{ totalTimeInvested }}</span>
                                <span class="text-gray-500 dark:text-gray-400 text-sm">(Total del proyecto)</span>
                            </div>

                            <!-- Breakdown by user -->
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-2">
                                <div v-for="item in timeInvestedByUser" :key="item.user.id" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                    <Avatar :image="item.user.profile_photo_url" shape="circle" size="small" v-tooltip.top="item.user.name" />
                                    <span class="font-medium">{{ item.formattedTime }}</span>
                                </div>
                            </div>
                        </div>
                        <Button label="Crear Tarea" icon="pi pi-plus" class="p-button-raised p-button-cyan mt-4 md:mt-0" @click="openCreateTaskModal" />
                    </div>

                    <!-- Tabs -->
                    <TabView>
                        <TabPanel header="Tareas">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-4">
                                <!-- Kanban Columns -->
                                <div v-for="status in statuses" :key="status" class="bg-gray-100 dark:bg-gray-700/50 rounded-lg p-4 kanban-column" @dragover.prevent @drop="onDrop(status)">
                                    <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-4 border-b-2 pb-2" :class="{
                                            'border-blue-500': status === 'Pendiente',
                                            'border-orange-500': status === 'En proceso',
                                            'border-green-500': status === 'Completada'
                                        }">
                                        {{ status.toUpperCase() }}
                                        <span class="text-sm font-normal bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-full px-2 py-0.5 ml-2">
                                            {{ tasksByStatus[status].length }}
                                        </span>
                                    </h3>

                                    <div class="space-y-3 max-h-[45vh] overflow-y-auto">
                                        <!-- Task Cards -->
                                        <div v-for="task in tasksByStatus[status]" :key="task.id"
                                             draggable="true"
                                             @dragstart="onDragStart(task)"
                                             @click="openEditTaskModal(task)"
                                             class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md cursor-pointer active:cursor-grabbing transition-shadow duration-200 hover:shadow-lg hover:shadow-cyan-500/10 dark:hover:shadow-cyan-500/20">

                                            <div class="flex justify-between items-start">
                                                <p class="font-semibold text-gray-800 dark:text-white" :class="{'line-through text-gray-500 dark:text-gray-400': task.status === 'Completada'}">
                                                    {{ task.title }}
                                                </p>
                                                <i v-if="task.status === 'Completada'" class="pi pi-check-circle text-green-500"></i>
                                            </div>

                                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-2 truncate">{{ task.description || 'Sin descripción' }}</p>
                                            
                                            <div class="flex justify-between items-center mt-3 text-sm text-gray-500 dark:text-gray-400">
                                                <div class="flex items-center gap-4">
                                                    <span>{{ formatDate(task.due_date) }}</span>
                                                    <div class="flex items-center gap-1">
                                                        <i class="pi pi-clock text-xs"></i>
                                                        <span>{{ task.total_hours_invested }}</span>
                                                    </div>
                                                </div>
                                                
                                                <Avatar v-if="task.assignee" :image="task.assignee.profile_photo_url" shape="circle" v-tooltip.top="task.assignee.name" />
                                                <Avatar v-else icon="pi pi-user" shape="circle" class="bg-gray-200 dark:bg-gray-600" v-tooltip.top="'Sin asignar'" />
                                            </div>
                                        </div>

                                        <!-- Empty State -->
                                        <div v-if="tasksByStatus[status].length === 0" class="text-center text-gray-400 dark:text-gray-500 pt-16">
                                            <i class="pi pi-folder-open text-4xl"></i>
                                            <p class="mt-2">No hay tareas</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel header="Información del Proyecto">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-gray-800 dark:text-white mt-4">
                                <Card class="col-span-1 md:col-span-2">
                                    <template #title>Descripción General</template>
                                    <template #content>
                                        <p class="text-gray-600 dark:text-gray-300">{{ project.description || 'No hay descripción para este proyecto.' }}</p>
                                    </template>
                                </Card>
                                <Card>
                                    <template #title>Detalles</template>
                                    <template #content>
                                        <ul class="space-y-3">
                                            <li class="flex justify-between">
                                                <span class="font-semibold text-gray-500 dark:text-gray-400">Cliente:</span>
                                                <span>{{ project.client?.name }}</span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="font-semibold text-gray-500 dark:text-gray-400">Estado:</span>
                                                <Tag :value="project.status" :severity="getStatusSeverity(project.status)" />
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="font-semibold text-gray-500 dark:text-gray-400">Presupuesto:</span>
                                                <span>${{ Number(project.budget).toLocaleString() }}</span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="font-semibold text-gray-500 dark:text-gray-400">Inicio:</span>
                                                <span>{{ formatDate(project.start_date) }}</span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="font-semibold text-gray-500 dark:text-gray-400">Fin:</span>
                                                <span>{{ formatDate(project.end_date) }}</span>
                                            </li>
                                        </ul>
                                    </template>
                                </Card>
                                <Card class="col-span-1 md:col-span-3">
                                    <template #title>Miembros del Equipo</template>
                                    <template #content>
                                        <div class="flex flex-wrap gap-4">
                                            <div v-for="member in project.members" :key="member.id" class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 p-2 rounded-full">
                                                <Avatar :image="member.profile_photo_url" shape="circle" />
                                                <span class="text-gray-800 dark:text-gray-200 pr-2">{{ member.name }}</span>
                                            </div>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>

        <!-- Dialog para CREAR una nueva tarea -->
        <Dialog v-model:visible="isCreateTaskModalVisible" modal header="Crear Nueva Tarea" :style="{ width: '50vw' }" :breakpoints="{ '960px': '75vw', '641px': '100vw' }">
            <form @submit.prevent="submitCreateTask" class="pt-4">
                <div class="p-fluid flex flex-col gap-6">
                    <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="title">Título de la tarea</label>
                            <InputText id="title" v-model="createTaskForm.title" :class="{'p-invalid': createTaskForm.errors.title }" />
                        </span>
                        <small v-if="createTaskForm.errors.title" class="p-error">{{ createTaskForm.errors.title }}</small>
                    </div>
                     <div class="field ">
                        <span class="p-float-label flex flex-col">
                            <label for="description">Descripción</label>
                            <Textarea id="description" v-model="createTaskForm.description" rows="3" />
                        </span>
                    </div>
                    <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="assignee">Asignar a</label>
                            <Dropdown id="assignee" v-model="createTaskForm.assigned_to" :options="project.members" optionLabel="name" optionValue="id" placeholder="Seleccionar miembro" />
                        </span>
                    </div>
                    <div class="field flex flex-col">
                        <span class="p-float-label flex flex-col">
                            <label for="due_date">Fecha de entrega</label>
                            <Calendar id="due_date" v-model="createTaskForm.due_date" dateFormat="yy-mm-dd" />
                        </span>
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" @click="isCreateTaskModalVisible = false" class="p-button-text" />
                <Button label="Guardar Tarea" icon="pi pi-check" @click="submitCreateTask" :loading="createTaskForm.processing" autofocus />
            </template>
        </Dialog>

        <!-- Dialog para EDITAR una tarea -->
        <Dialog v-model:visible="isEditTaskModalVisible" modal header="Editar Tarea" :style="{ width: '50vw' }" :breakpoints="{ '960px': '75vw', '641px': '100vw' }">
            <form @submit.prevent="submitUpdateTask" class="pt-4">
                <div class="p-fluid flex flex-col gap-6">
                    <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="edit-title">Título de la tarea</label>
                            <InputText id="edit-title" v-model="editTaskForm.title" :class="{'p-invalid': editTaskForm.errors.title }" />
                        </span>
                        <small v-if="editTaskForm.errors.title" class="p-error">{{ editTaskForm.errors.title }}</small>
                    </div>
                     <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="edit-description">Descripción</label>
                            <Textarea id="edit-description" v-model="editTaskForm.description" rows="3" />
                        </span>
                    </div>
                    <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="edit-assignee">Asignar a</label>
                            <Dropdown id="edit-assignee" v-model="editTaskForm.assigned_to" :options="project.members" optionLabel="name" optionValue="id" placeholder="Seleccionar miembro" />
                        </span>
                    </div>
                    <div class="field">
                        <span class="p-float-label flex flex-col">
                            <label for="edit-due_date">Fecha de entrega</label>
                            <Calendar id="edit-due_date" v-model="editTaskForm.due_date" dateFormat="yy-mm-dd" />
                        </span>
                    </div>
                </div>
            </form>
            <template #footer>
                 <Button label="Eliminar" icon="pi pi-trash" @click="submitDeleteTask" class="p-button-danger p-button-text" />
                <div class="flex justify-end gap-2">
                    <Button label="Cancelar" icon="pi pi-times" @click="isEditTaskModalVisible = false" class="p-button-text" />
                    <Button label="Actualizar" icon="pi pi-check" @click="submitUpdateTask" :loading="editTaskForm.processing" autofocus />
                </div>
            </template>
        </Dialog>
    </AppLayout>
</template>

<style>
/* Custom styles for PrimeVue TabView to support Light & Dark Mode */
.p-tabview .p-tabview-nav {
    background: transparent;
    border: none;
    border-bottom: 1px solid theme('colors.gray.200');
}
.dark .p-tabview .p-tabview-nav {
    border-bottom-color: theme('colors.gray.700');
}

.p-tabview .p-tabview-nav-link {
    background: transparent !important;
    color: theme('colors.gray.500') !important;
    border: none !important;
    transition: color 0.2s;
}
.dark .p-tabview .p-tabview-nav-link {
    color: theme('colors.gray.400') !important;
}

.p-tabview .p-tabview-nav-link:not(.p-disabled):focus {
    box-shadow: none !important;
}

.p-tabview .p-tabview-nav-link:hover {
    color: theme('colors.gray.800') !important;
}
.dark .p-tabview .p-tabview-nav-link:hover {
    color: theme('colors.white') !important;
}

.p-tabview .p-tabview-nav .p-tabview-ink-bar {
    background-color: theme('colors.cyan.500') !important;
    height: 3px;
}
.dark .p-tabview .p-tabview-nav .p-tabview-ink-bar {
     background-color: theme('colors.cyan.400') !important;
}

.p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
    color: theme('colors.cyan.600') !important;
}
.dark .p-tabview .p-tabview-nav li.p-highlight .p-tabview-nav-link {
    color: theme('colors.white') !important;
}

.p-tabview .p-tabview-panels {
    background: transparent;
    padding: 1.25rem 0;
}

/* Custom Card Styles */
.p-card {
    background-color: theme('colors.white') !important;
    color: theme('colors.gray.700') !important;
    box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    border-radius: 0.5rem;
}
.dark .p-card {
    background-color: #374151 !important; /* gray-700 */
    color: #f3f4f6 !important; /* gray-100 */
}

.p-card .p-card-title {
    color: theme('colors.gray.900') !important;
    font-size: 1.25rem;
}
.dark .p-card .p-card-title {
    color: theme('colors.white') !important;
}

/* Ensure PrimeVue Dialog follows theme */
.p-dialog .p-dialog-header {
    background: theme('colors.gray.50') !important;
    color: theme('colors.gray.800') !important;
}
.dark .p-dialog .p-dialog-header {
    background: theme('colors.gray.800') !important;
    color: theme('colors.gray.50') !important;
}
.p-dialog .p-dialog-content {
    background: theme('colors.white') !important;
}
.dark .p-dialog .p-dialog-content {
    background: theme('colors.gray.900') !important;
}
.p-dialog .p-dialog-footer {
    background: theme('colors.gray.50') !important;
}
.dark .p-dialog .p-dialog-footer {
    background: theme('colors.gray.800') !important;
}
</style>
