<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, usePage, useForm, router, Link } from '@inertiajs/vue3';
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
import InputSwitch from 'primevue/inputswitch';
import InputNumber from 'primevue/inputnumber';
import ConfirmDialog from 'primevue/confirmdialog';
import Toast from 'primevue/toast';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';

const page = usePage();

const confirm = useConfirm();
const toast = useToast();

const project = computed(() => page.props.project);
const totalTimeInvested = computed(() => page.props.totalTimeInvested);
const allUsers = computed(() => page.props.users || []);

// --- Reloj en Vivo para Tareas en Progreso ---
const currentTime = ref(new Date());
let timerInterval;

onMounted(() => {
    timerInterval = setInterval(() => {
        currentTime.value = new Date();
    }, 60000); 
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});

const getOngoingDuration = (startTimeStr) => {
    if (!startTimeStr) return '0 m';
    const start = new Date(startTimeStr);
    const diffMs = currentTime.value - start;
    if (diffMs <= 0) return '0 m';
    
    const totalMinutes = Math.floor(diffMs / 60000);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    
    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }
    return `${minutes} m`;
};

// --- Funciones de formato y limpieza ---
const cleanText = (text) => {
    if (!text) return '';
    try {
        const parsed = JSON.parse(text);
        if (parsed && parsed.ops) {
            return parsed.ops.map(op => op.insert).join('');
        }
    } catch (e) {}
    
    const doc = new DOMParser().parseFromString(text, 'text/html');
    return doc.body.textContent || "";
};

const formatMinutesToHours = (totalMinutes) => {
    if (!totalMinutes || totalMinutes <= 0) {
        return '00:00';
    }
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
};

const formatDate = (dateString) => {
    if (!dateString) return '--';
    const options = { month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const options = { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
};

const timeInvestedByUser = computed(() => {
    if (!project.value || !project.value.tasks) return [];
    
    const timeByUser = project.value.tasks.reduce((acc, task) => {
        if (task.assignee && task.total_invested_minutes > 0) {
            const userId = task.assignee.id;
            if (!acc[userId]) {
                acc[userId] = { user: task.assignee, totalMinutes: 0 };
            }
            acc[userId].totalMinutes += task.total_invested_minutes;
        }
        return acc;
    }, {});

    return Object.values(timeByUser).map(item => ({
        ...item,
        formattedTime: formatMinutesToHours(item.totalMinutes),
    })).sort((a, b) => b.totalMinutes - a.totalMinutes);
});

// --- Lógica para calcular la diferencia de horas ---
const timeDifference = computed(() => {
    if (!project.value || !project.value.budgeted_hours) return null;
    
    const budgetedMinutes = project.value.budgeted_hours * 60;
    const investedMinutes = project.value.tasks ? project.value.tasks.reduce((sum, task) => sum + (task.total_invested_minutes || 0), 0) : 0;
    
    const diffMinutes = budgetedMinutes - investedMinutes;
    const isOverBudget = diffMinutes < 0;
    const absDiff = Math.abs(diffMinutes);
    
    const hours = Math.floor(absDiff / 60);
    const minutes = absDiff % 60;
    
    return {
        isOverBudget,
        text: `${hours}h ${String(minutes).padStart(2, '0')}m`,
        rawDiff: diffMinutes
    };
});

// --- State para Modales ---
const isCreateTaskModalVisible = ref(false);
const isEditTaskModalVisible = ref(false);
const selectedTask = ref(null);

// --- Formularios ---
const createTaskForm = useForm({
    project_id: project.value?.id,
    title: '',
    description: '',
    assigned_to: null,
    start_date: null,
    end_date: null,
    is_high_priority: false,
});

const editTaskForm = useForm({
    title: '',
    description: '',
    assigned_to: null,
    start_date: null,
    end_date: null,
    is_high_priority: false,
    time_logs: [],
});

// --- Manejo de Modales ---
const openCreateTaskModal = () => {
    createTaskForm.reset();
    createTaskForm.project_id = project.value.id;
    isCreateTaskModalVisible.value = true;
};

const submitCreateTask = () => {
    createTaskForm.post(route('tasks.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            isCreateTaskModalVisible.value = false;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Tarea creada correctamente', life: 3000 });
        },
    });
};

const openEditTaskModal = (task) => {
    selectedTask.value = task;
    editTaskForm.title = task.title;
    editTaskForm.description = task.description;
    editTaskForm.assigned_to = task.assignee?.id || null;
    editTaskForm.start_date = task.start_date ? new Date(task.start_date) : null;
    editTaskForm.end_date = task.end_date ? new Date(task.end_date) : null;
    editTaskForm.is_high_priority = Boolean(task.is_high_priority);
    editTaskForm.time_logs = task.time_logs ? JSON.parse(JSON.stringify(task.time_logs)) : [];
    
    isEditTaskModalVisible.value = true;
};

const submitUpdateTask = () => {
    if (!selectedTask.value) return;
    editTaskForm.put(route('tasks.update', { task: selectedTask.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
             isEditTaskModalVisible.value = false;
             toast.add({ severity: 'success', summary: 'Éxito', detail: 'Tarea y horas actualizadas', life: 3000 });
        },
    });
};

const submitDeleteTask = () => {
    if (!selectedTask.value) return;
    
    confirm.require({
        group: 'delete-task',
        header: '¿Eliminar Tarea?',
        message: `Estás a punto de eliminar la tarea "${selectedTask.value.title}". El tiempo invertido se descontará automáticamente del proyecto. Esta acción no se puede deshacer.`,
        accept: () => {
            router.delete(route('tasks.destroy', { task: selectedTask.value.id }), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    isEditTaskModalVisible.value = false;
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Tarea eliminada exitosamente', life: 3000 });
                },
            });
        }
    });
};

// --- Lógica Kanban (Desktop & Mobile Touch) ---
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
    
    if (taskToMove) {
        taskToMove.status = newStatus;
    }

    router.patch(route('tasks.updateStatus', { task: draggedTask.value.id }), {
        status: newStatus,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            if (taskToMove) {
                taskToMove.status = originalStatus;
            }
            console.error("Error al actualizar la tarea:", errors);
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado', life: 3000 });
        },
        onFinish: () => {
            draggedTask.value = null;
        }
    });
};

// --- Touch Events (Mobile Drag & Drop) ---
const touchState = ref({
    task: null,
    clone: null,
    startX: 0,
    startY: 0,
    offsetX: 0,
    offsetY: 0,
    timer: null,
    isDragging: false,
    originalElement: null
});

const onTouchStart = (e, task) => {
    if (e.touches.length !== 1) return;
    const touch = e.touches[0];
    const target = e.currentTarget;
    const rect = target.getBoundingClientRect();
    
    touchState.value = {
        task: task,
        startX: touch.clientX,
        startY: touch.clientY,
        offsetX: touch.clientX - rect.left,
        offsetY: touch.clientY - rect.top,
        clone: null,
        originalElement: target,
        isDragging: false
    };
    
    // Timer para diferenciar entre tap y drag
    touchState.value.timer = setTimeout(() => {
        touchState.value.isDragging = true;
        const clone = target.cloneNode(true);
        touchState.value.clone = clone;
        
        // Estilar clon visual
        clone.style.position = 'fixed';
        clone.style.left = `${touch.clientX - touchState.value.offsetX}px`;
        clone.style.top = `${touch.clientY - touchState.value.offsetY}px`;
        clone.style.width = `${rect.width}px`;
        clone.style.zIndex = '9999';
        clone.style.opacity = '0.9';
        clone.style.pointerEvents = 'none'; // Clave para poder encontrar la columna debajo
        clone.style.transform = 'scale(1.05) rotate(2deg)';
        clone.style.boxShadow = '0 25px 50px -12px rgba(0, 0, 0, 0.25)';
        clone.style.transition = 'none';
        
        document.body.appendChild(clone);
        target.style.opacity = '0.4';
        
        // Pequeña vibración en móviles si lo soportan
        if (navigator.vibrate) navigator.vibrate(50);
    }, 300); // 300ms de press para que inicie el drag
};

const onTouchMove = (e) => {
    if (!touchState.value.task) return;
    const touch = e.touches[0];
    
    // Si aún no está arrastrando formalmente, checamos si el usuario scrolleó
    if (!touchState.value.isDragging) {
        const dx = touch.clientX - touchState.value.startX;
        const dy = touch.clientY - touchState.value.startY;
        if (Math.abs(dx) > 10 || Math.abs(dy) > 10) {
            clearTimeout(touchState.value.timer); // Cancelar drag (fue un scroll normal)
            touchState.value.task = null;
        }
        return;
    }
    
    e.preventDefault(); // Evitar scroll de la página si estamos arrastrando
    
    if (touchState.value.clone) {
        touchState.value.clone.style.left = `${touch.clientX - touchState.value.offsetX}px`;
        touchState.value.clone.style.top = `${touch.clientY - touchState.value.offsetY}px`;
    }
};

const onTouchEnd = (e) => {
    if (!touchState.value.task) return;
    clearTimeout(touchState.value.timer);
    
    if (touchState.value.isDragging) {
        e.preventDefault(); // Evitar el trigger del clic tras soltar
        
        const touch = e.changedTouches[0];
        
        // Ocultar clon temporalmente para ver qué hay debajo
        if (touchState.value.clone) {
            touchState.value.clone.style.display = 'none';
        }
        
        const elementBelow = document.elementFromPoint(touch.clientX, touch.clientY);
        
        if (touchState.value.clone) {
            document.body.removeChild(touchState.value.clone);
        }
        
        if (touchState.value.originalElement) {
            touchState.value.originalElement.style.opacity = '1';
        }
        
        // Buscar la columna kanban y disparar la actualización
        const column = elementBelow ? elementBelow.closest('.kanban-column') : null;
        if (column) {
            const newStatus = column.getAttribute('data-status');
            if (newStatus && newStatus !== touchState.value.task.status) {
                draggedTask.value = touchState.value.task;
                onDrop(newStatus);
            }
        }
    }
    
    // Reset estado
    touchState.value = { task: null, clone: null, originalElement: null };
};

const getStatusSeverity = (status) => {
    switch (status) {
        case 'Activo': case 'En proceso': return 'info';
        case 'Completado': return 'success';
        case 'Pendiente': return 'warn';
        case 'Cancelado': return 'danger';
        default: return 'secondary';
    }
};

const getKanbanHeaderStyle = (status) => {
     switch (status) {
        case 'Pendiente': return {
            container: 'bg-gray-100 border-gray-200', 
            square: 'bg-gray-500', 
            text: 'text-gray-700'
        };
        case 'En proceso': return {
            container: 'bg-blue-50 border-blue-100', 
            square: 'bg-blue-500', 
            text: 'text-blue-800'
        };
        case 'Completada': return {
            container: 'bg-emerald-50 border-emerald-100', 
            square: 'bg-emerald-500', 
            text: 'text-emerald-800'
        };
        default: return {
            container: 'bg-gray-50 border-gray-200',
            square: 'bg-gray-400',
            text: 'text-gray-600'
        };
    }
}
</script>

<template>
    <AppLayout :title="project.name">
        <Head :title="'Proyecto: ' + project.name" />

        <div class="py-1">
             <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-4">
                 <Link :href="route('projects.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
             </div>

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Toast />
                <ConfirmDialog />
                
                <!-- Modern Headless ConfirmDialog -->
                <ConfirmDialog group="delete-task">
                    <template #container="{ message, acceptCallback, rejectCallback }">
                        <div class="flex flex-col items-center p-8 bg-white dark:bg-zinc-900 rounded-[2rem] shadow-2xl border border-gray-100 dark:border-zinc-800 w-[90vw] max-w-sm text-center">
                            <div class="w-20 h-20 rounded-full bg-red-50 dark:bg-red-500/10 flex items-center justify-center mb-6 shadow-inner">
                                <i class="pi pi-exclamation-triangle text-4xl text-red-500"></i>
                            </div>
                            <span class="font-bold text-2xl block mb-2 text-gray-800 dark:text-white">{{ message.header }}</span>
                            <p class="mb-8 text-gray-600 dark:text-gray-400 leading-relaxed text-sm">{{ message.message }}</p>
                            <div class="flex items-center gap-3 w-full">
                                <Button label="Cancelar" outlined severity="secondary" @click="rejectCallback" class="w-full !rounded-xl font-semibold"></Button>
                                <Button label="Eliminar" severity="danger" @click="acceptCallback" class="w-full !rounded-xl bg-red-500 border-red-500 hover:bg-red-600 font-semibold shadow-lg shadow-red-500/30"></Button>
                            </div>
                        </div>
                    </template>
                </ConfirmDialog>

                <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-[2rem] p-6 md:p-8 border border-gray-100 dark:border-zinc-800">

                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-4">
                                <Tag :value="project.status" :severity="getStatusSeverity(project.status)"/>
                                
                                <div class="flex items-center gap-2 text-cyan-600 dark:text-cyan-400 bg-cyan-50 dark:bg-cyan-900/20 px-3 py-1 rounded-full border border-cyan-100 dark:border-cyan-800 shadow-sm">
                                    <i class="pi pi-clock" style="font-size: 1rem"></i>
                                    <span class="font-bold text-base">{{ totalTimeInvested }}</span>
                                    
                                    <template v-if="project.budgeted_hours">
                                        <span class="w-px h-4 bg-cyan-200 dark:bg-cyan-800 mx-1"></span>
                                        <span class="text-sm font-semibold opacity-90" v-tooltip.top="'Horas Presupuestadas'">{{ project.budgeted_hours }}h presup.</span>
                                        
                                        <span class="w-px h-4 bg-cyan-200 dark:bg-cyan-800 mx-1"></span>
                                        <span v-if="timeDifference" 
                                              class="text-xs font-bold flex items-center gap-1 px-1.5 py-0.5 rounded-md"
                                              :class="timeDifference.isOverBudget ? 'bg-red-100 text-red-600 dark:bg-red-900/40 dark:text-red-400' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'"
                                              v-tooltip.top="timeDifference.isOverBudget ? 'Tiempo excedido' : 'Tiempo restante'">
                                            <i class="pi" :class="timeDifference.isOverBudget ? 'pi-arrow-up' : 'pi-arrow-down'" style="font-size: 0.65rem"></i>
                                            {{ timeDifference.text }}
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 mt-4">
                                <div v-for="item in timeInvestedByUser" :key="item.user.id" class="flex items-center gap-2 bg-gray-50 dark:bg-zinc-800/50 rounded-full pl-1 pr-3 py-1 border border-gray-100 dark:border-zinc-700 shadow-sm">
                                    <Avatar :image="item.user.profile_photo_url" shape="circle" size="normal" v-tooltip.top="item.user.name" />
                                    <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">{{ item.formattedTime }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <Button icon="pi pi-pencil" class="p-button-outlined p-button-secondary p-button-rounded" v-tooltip.top="'Editar Detalles'" @click="$inertia.visit(route('projects.edit', project.id))" />
                            <Button label="Nueva tarea" icon="pi pi-plus" class="p-button-rounded p-button-primary !text-[var(--primary-text-color)] flex-1 md:flex-none" @click="openCreateTaskModal" />
                        </div>
                    </div>

                    <TabView class="custom-tabview">
                        <TabPanel header="Tareas">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                                <!-- Clase kanban-column y data-status agregados para detectar el drop en móviles -->
                                <div v-for="status in statuses" :key="status" 
                                     class="flex flex-col bg-gray-50/50 dark:bg-zinc-800/30 rounded-3xl border border-gray-100 dark:border-zinc-800/50 h-full kanban-column" 
                                     :data-status="status"
                                     @dragover.prevent @drop="onDrop(status)">
                                    
                                    <div class="p-4 sticky top-0 bg-transparent z-10 flex justify-center pointer-events-none">
                                        <div class="flex items-center gap-3 px-4 py-2 rounded-xl border shadow-sm transition-all bg-white dark:bg-zinc-900 border-gray-200 dark:border-zinc-800" :class="getKanbanHeaderStyle(status).container">
                                            <div class="w-3.5 h-3.5 rounded-md shadow-sm" :class="getKanbanHeaderStyle(status).square"></div>
                                            <span class="text-lg font-bold font-mono tracking-tight" :class="getKanbanHeaderStyle(status).text">
                                                {{ tasksByStatus[status].length }}
                                            </span>
                                            <span class="text-[10px] font-bold uppercase tracking-wider opacity-60 ml-1 translate-y-[1px]" :class="getKanbanHeaderStyle(status).text">
                                                {{ status }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="px-3 pb-3 space-y-3 overflow-y-auto max-h-[55vh] custom-scrollbar overflow-x-hidden">
                                        <div v-for="task in tasksByStatus[status]" :key="task.id"
                                             draggable="true"
                                             @dragstart="onDragStart(task)"
                                             @touchstart="onTouchStart($event, task)"
                                             @touchmove="onTouchMove($event)"
                                             @touchend="onTouchEnd($event)"
                                             @click="openEditTaskModal(task)"
                                             class="group bg-white dark:bg-zinc-900 p-5 rounded-2xl border border-gray-200/50 dark:border-zinc-800 shadow-sm cursor-grab active:cursor-grabbing hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 relative select-none touch-manipulation">

                                            <div class="flex justify-between items-start mb-2 pointer-events-none">
                                                 <div class="flex items-start gap-2 flex-1">
                                                    <i v-if="task.is_high_priority" class="pi pi-flag-fill text-red-500 mt-1 drop-shadow-sm" style="font-size: 0.95rem;" v-tooltip.top="'Prioridad Alta'"></i>
                                                    <p class="font-bold text-gray-800 dark:text-gray-100 text-sm leading-snug group-hover:text-cyan-600 dark:group-hover:text-cyan-400 transition-colors" :class="{'line-through text-gray-400 dark:text-gray-500': task.status === 'Completada'}">
                                                        {{ task.title }}
                                                    </p>
                                                </div>
                                            </div>

                                            <p v-if="task.description" class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4 leading-relaxed pointer-events-none">
                                                {{ cleanText(task.description) }}
                                            </p>
                                            
                                            <div class="flex flex-col gap-3 pt-3 mt-3 border-t border-gray-50 dark:border-zinc-800/50 pointer-events-none">
                                                <div class="flex items-center justify-between text-[10px] font-semibold tracking-wide text-gray-500 dark:text-gray-400 bg-gray-50/80 dark:bg-zinc-800/50 px-2 py-1.5 rounded-lg border border-gray-100 dark:border-zinc-700/50">
                                                    <div class="flex items-center gap-1.5" v-tooltip.top="'Inicio'">
                                                        <i class="pi pi-play text-gray-500 text-[9px]"></i>
                                                        <span>{{ formatDate(task.start_date) }}</span>
                                                    </div>
                                                    <div class="w-px h-3 bg-gray-200 dark:bg-zinc-700"></div>
                                                    <div class="flex items-center gap-1.5" v-tooltip.top="'Término'">
                                                        <i class="pi pi-check-square text-gray-500 text-[9px]"></i>
                                                        <span>{{ formatDate(task.end_date) }}</span>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex items-center justify-between pointer-events-none">
                                                    <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 text-xs font-bold border border-cyan-100 dark:border-cyan-800 shadow-sm" v-tooltip.top="'Tiempo Invertido'">
                                                        <i class="pi pi-clock" style="font-size: 0.7rem"></i>
                                                        <span>{{ task.total_hours_invested || '00:00' }} hrs</span>
                                                    </div>
                                                    
                                                    <Avatar v-if="task.assignee" :image="task.assignee.profile_photo_url" shape="circle" size="small" class="border-2 border-white dark:border-zinc-800 shadow-sm" v-tooltip.left="task.assignee.name" />
                                                    <div v-else class="w-6 h-6 rounded-full bg-gray-100 dark:bg-zinc-800 flex items-center justify-center text-gray-400 border border-gray-200 dark:border-zinc-700">
                                                        <i class="pi pi-user text-[10px]"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="tasksByStatus[status].length === 0" class="flex flex-col items-center justify-center py-12 opacity-40">
                                            <i class="pi pi-inbox text-3xl text-gray-300 dark:text-gray-600 mb-2"></i>
                                            <p class="text-xs font-medium text-gray-400 dark:text-gray-500">Vacío</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        
                        <TabPanel header="Detalles">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-4">
                                <Card class="col-span-1 md:col-span-2 shadow-none border border-gray-200 dark:border-zinc-800 !bg-transparent overflow-hidden w-full">
                                    <template #title>Descripción</template>
                                    <template #content>
                                        <p class="text-gray-600 dark:text-gray-300 leading-relaxed whitespace-pre-wrap break-words break-all sm:break-words">
                                            {{ project.description ? cleanText(project.description) : 'No hay descripción disponible.' }}
                                        </p>
                                    </template>
                                </Card>
                                <div class="space-y-6">
                                     <div class="bg-gray-50 dark:bg-zinc-800/30 rounded-2xl p-6 border border-gray-200 dark:border-zinc-800">
                                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Información Clave</h5>
                                        <ul class="space-y-4 text-sm">
                                            <li class="flex justify-between">
                                                <span class="text-gray-500">Cliente</span>
                                                <span class="font-medium text-gray-800 dark:text-white">{{ project.client?.name || 'N/A' }}</span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-500">Presupuesto</span>
                                                <span class="font-mono font-medium text-gray-800 dark:text-white">${{ Number(project.budget).toLocaleString() }}</span>
                                            </li>
                                            <li class="h-px bg-gray-200 dark:bg-zinc-700 w-full my-2"></li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-500">Inicio</span>
                                                <span class="text-gray-800 dark:text-white">{{ formatDate(project.start_date) }}</span>
                                            </li>
                                            <li class="flex justify-between">
                                                <span class="text-gray-500">Entrega</span>
                                                <span class="text-gray-800 dark:text-white">{{ formatDate(project.end_date) }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 border border-gray-200 dark:border-zinc-800">
                                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Equipo</h5>
                                         <div class="flex flex-wrap gap-2">
                                            <div v-for="member in project.members" :key="member.id" class="flex items-center gap-2 bg-gray-50 dark:bg-zinc-800 p-1.5 pr-3 rounded-full border border-gray-100 dark:border-zinc-700">
                                                <Avatar :image="member.profile_photo_url" shape="circle" size="small" />
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-200">{{ member.name.split(' ')[0] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>

        <Dialog v-model:visible="isCreateTaskModalVisible" modal header=" " :style="{ width: '34rem' }" 
            :pt="{ 
                root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0' },
                header: { class: 'pt-8 px-8 pb-0 bg-transparent rounded-t-[2rem]' },
                content: { class: 'px-8 pb-8 pt-4 bg-transparent rounded-b-[2rem]' }
            }">
            <template #header>
                <div class="flex items-center gap-3 w-full mb-3">
                    <div class="w-10 h-10 rounded-full bg-cyan-100 dark:bg-cyan-900/50 flex items-center justify-center">
                        <i class="pi pi-plus text-cyan-600 dark:text-cyan-400 text-lg font-bold"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800 dark:text-white tracking-tight">Nueva tarea</span>
                </div>
            </template>
            <form @submit.prevent="submitCreateTask" class="flex flex-col gap-2 mt-4">
                <div class="flex flex-col gap-2">
                    <label for="title" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Título <span class="text-red-500">*</span></label>
                    <InputText id="title" v-model="createTaskForm.title" class="w-full !rounded-xl" :class="{'p-invalid': createTaskForm.errors.title }" placeholder="Ej: Diseñar mockup" required />
                </div>
                 <div class="flex flex-col gap-2">
                    <label for="description" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Descripción</label>
                    <Textarea id="description" v-model="createTaskForm.description" rows="3" class="w-full !rounded-xl" placeholder="Detalles de la tarea..." />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="assignee" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Asignar a <span class="text-red-500">*</span></label>
                    <Dropdown id="assignee" v-model="createTaskForm.assigned_to" :options="allUsers" optionLabel="name" optionValue="id" placeholder="Seleccionar" class="w-full !rounded-xl" :class="{'p-invalid': createTaskForm.errors.assigned_to }" filter required />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="start_date" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Inicio</label>
                        <Calendar id="start_date" v-model="createTaskForm.start_date" dateFormat="yy-mm-dd" class="w-full !rounded-xl" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="end_date" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Término</label>
                        <Calendar id="end_date" v-model="createTaskForm.end_date" dateFormat="yy-mm-dd" class="w-full !rounded-xl" />
                    </div>
                </div>
                 <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-gray-100 dark:border-zinc-700 mt-2">
                     <div class="flex items-center gap-3">
                         <i class="pi pi-flag-fill" :class="createTaskForm.is_high_priority ? 'text-red-500 drop-shadow-sm' : 'text-gray-300 dark:text-gray-600'"></i>
                         <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Marcar como Alta Prioridad</span>
                     </div>
                     <InputSwitch v-model="createTaskForm.is_high_priority" />
                </div>
                <div class="flex justify-end gap-3 mt-4">
                    <Button label="Cancelar" text severity="secondary" class="!rounded-xl font-medium" @click="isCreateTaskModalVisible = false" />
                    <Button label="Crear tarea" icon="pi pi-check" type="submit" :loading="createTaskForm.processing" class="!rounded-xl font-medium !text-[var(--primary-text-color)]" />
                </div>
            </form>
        </Dialog>

        <Dialog v-model:visible="isEditTaskModalVisible" modal header=" " :style="{ width: '50rem' }" 
            :pt="{ 
                root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0' },
                header: { class: 'pt-8 px-8 pb-0 bg-transparent rounded-t-[2rem]' },
                content: { class: 'px-8 pb-8 pt-4 bg-transparent rounded-b-[2rem]' }
            }">
             <template #header>
                <div class="flex items-center gap-3 w-full mb-4">
                    <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-500/20 flex items-center justify-center">
                        <i class="pi pi-pencil text-gray-600 dark:text-gray-400 text-lg font-bold"></i>
                    </div>
                    <span class="text-2xl font-bold text-gray-800 dark:text-white tracking-tight">Editar Tarea</span>
                </div>
            </template>
            <form @submit.prevent="submitUpdateTask" class="flex flex-col gap-2 mt-1">
                <div class="flex flex-col gap-2">
                    <label for="edit-title" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Título <span class="text-red-500">*</span></label>
                    <InputText id="edit-title" v-model="editTaskForm.title" class="w-full !rounded-xl" :class="{'p-invalid': editTaskForm.errors.title }" required />
                </div>
                 <div class="flex flex-col gap-2">
                    <label for="edit-description" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Descripción</label>
                    <Textarea id="edit-description" v-model="editTaskForm.description" rows="5" class="w-full !rounded-xl" />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="edit-assignee" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Asignar a <span class="text-red-500">*</span></label>
                    <Dropdown id="edit-assignee" v-model="editTaskForm.assigned_to" :options="allUsers" optionLabel="name" optionValue="id" placeholder="Seleccionar" class="w-full !rounded-xl" :class="{'p-invalid': editTaskForm.errors.assigned_to }" filter required />
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <label for="edit-start_date" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Inicio</label>
                        <Calendar id="edit-start_date" v-model="editTaskForm.start_date" dateFormat="yy-mm-dd" class="w-full !rounded-xl" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="edit-end_date" class="text-sm font-semibold text-gray-700 dark:text-gray-300">Término</label>
                        <Calendar id="edit-end_date" v-model="editTaskForm.end_date" dateFormat="yy-mm-dd" class="w-full !rounded-xl" />
                    </div>
                </div>

                <!-- Sección: Edición de Tiempos Registrados -->
                <div v-if="editTaskForm.time_logs && editTaskForm.time_logs.length > 0" class="flex flex-col gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="flex justify-between items-center mb-1">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tiempos Registrados</label>
                        <span class="text-[10px] bg-gray-100 dark:bg-zinc-800 text-gray-500 px-2 py-1 rounded-full font-bold uppercase">{{ editTaskForm.time_logs.length }} sesiones</span>
                    </div>
                    
                    <div class="max-h-44 overflow-y-auto overflow-x-hidden pr-2 custom-scrollbar flex flex-col gap-2.5">
                        <div v-for="(log, index) in editTaskForm.time_logs" :key="log.id" class="flex items-center justify-between bg-gray-50/50 dark:bg-zinc-800/30 p-3 rounded-2xl border border-gray-200/60 dark:border-zinc-700 shadow-sm transition-all hover:bg-gray-50">
                            <div class="flex flex-col">
                                <span class="text-xs text-gray-600 dark:text-gray-300 font-semibold"><i class="pi pi-calendar mr-1.5 text-gray-400"></i>{{ formatDateTime(log.start_time) }}</span>
                                <span class="text-[11px] font-medium text-gray-500 dark:text-gray-500 ml-4.5 mt-0.5" v-if="log.end_time">Terminó a las {{ formatDateTime(log.end_time).split(',')[1] }}</span>
                                <span class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400 ml-4 mt-0.5 animate-pulse" v-else>
                                    En progreso ({{ getOngoingDuration(log.start_time) }})...
                                </span>
                            </div>
                            <div class="flex items-center gap-2">
                                <InputNumber fluid v-model="editTaskForm.time_logs[index].duration_minutes" :min="0" suffix=" min" class="w-24 !rounded-lg" inputClass="!text-sm !py-1.5 font-bold text-center bg-white dark:bg-zinc-900 border-gray-200" :disabled="!log.end_time" />
                            </div>
                        </div>
                    </div>
                    <small class="text-gray-400 text-[11px] mt-1"><i class="pi pi-info-circle mr-1"></i>Puedes ajustar los minutos de las sesiones terminadas.</small>
                </div>

                <div class="flex items-center justify-between bg-gray-50 dark:bg-zinc-800/50 p-4 rounded-2xl border border-gray-100 dark:border-zinc-700 mt-2">
                     <div class="flex items-center gap-3">
                         <i class="pi pi-flag-fill" :class="editTaskForm.is_high_priority ? 'text-red-500 drop-shadow-sm' : 'text-gray-300 dark:text-gray-600'"></i>
                         <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Marcar como Alta Prioridad</span>
                     </div>
                     <InputSwitch v-model="editTaskForm.is_high_priority" />
                </div>
                
                <div class="flex justify-between mt-6 bg-gray-50 dark:bg-zinc-900/50 -mx-8 -mb-8 p-6 rounded-b-[2rem] border-t border-gray-100 dark:border-zinc-800">
                    <Button label="Eliminar" icon="pi pi-trash" severity="danger" text @click="submitDeleteTask" class="!rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 font-semibold px-4" />
                    <div class="flex gap-3">
                        <Button label="Cancelar" text severity="secondary" class="!rounded-xl font-medium" @click="isEditTaskModalVisible = false" />
                        <Button label="Guardar" icon="pi pi-check" type="submit" :loading="editTaskForm.processing" class="!text-[var(--primary-text-color)] !rounded-xl" />
                    </div>
                </div>
            </form>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
:deep(.p-tabview-nav-content) { background: transparent !important; }
:deep(.p-tabview-nav) { background: transparent !important; border-bottom: 1px solid theme('colors.gray.100') !important; padding: 0 0.5rem !important; }
.dark :deep(.p-tabview-nav) { border-bottom-color: theme('colors.zinc.800') !important; }

:deep(.p-tabview-nav-link) {
    background: transparent !important; border: none !important; border-bottom: 2px solid transparent !important;
    color: theme('colors.gray.500') !important; font-weight: 600; font-size: 0.95rem; padding: 1rem 1rem !important; transition: all 0.2s;
}

/* Modales diseño limpio/Apple */
:deep(.p-dialog-header) { color: theme('colors.gray.800') !important; }
.dark :deep(.p-dialog-header) { color: theme('colors.white') !important; }
:deep(.p-dialog-content) { color: theme('colors.gray.600') !important; }
.dark :deep(.p-dialog-content) { color: theme('colors.gray.300') !important; }
</style>

<style>
.custom-scrollbar {
    scrollbar-width: thin; /* Firefox */
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent; /* Firefox */
}

/* Webkit (Chrome, Safari, Edge) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 20px;
    border: 2px solid transparent; /* Crea un efecto de margen */
    background-clip: content-box;
    transition: background-color 0.3s;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.8);
}

/* Modo oscuro para el scrollbar */
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: rgba(82, 82, 91, 0.5); /* zinc-600 con opacidad */
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: rgba(82, 82, 91, 0.8);
}
/* Ocultar flechas explícitamente */
.custom-scrollbar::-webkit-scrollbar-button {
    display: none;
    width: 0;
    height: 0;
}
</style>