<script setup>
import { ref } from 'vue';
import Card from 'primevue/card';
import Dialog from 'primevue/dialog';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

defineProps({
    data: Object
});

const showModal = ref(false);
const selectedStatus = ref('');
const loading = ref(false);
const projectsList = ref([]);

// Mapeo del texto de la UI al valor exacto en la base de datos
const statusMap = {
    'En proceso': 'En proceso',
    'Pendientes': 'Pendiente',
    'Completados': 'Completado',
    'Pausados': 'Pausado',
    'Cancelados': 'Cancelado'
};

const openModal = async (statusLabel) => {
    selectedStatus.value = statusLabel;
    showModal.value = true;
    loading.value = true;
    projectsList.value = [];

    try {
        const dbStatus = statusMap[statusLabel];
        // Utilizamos Axios para obtener los proyectos filtrados
        const response = await axios.get(route('dashboard.projects.data'), {
            params: { status: dbStatus }
        });
        projectsList.value = response.data;
    } catch (error) {
        console.error("Error al cargar proyectos:", error);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Card class="shadow-sm rounded-3xl overflow-hidden h-full flex flex-col justify-between bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
        <template #content>
            <div class="flex flex-col h-full">
                <!-- Cabecera y Total -->
                <div class="text-center pb-4 border-b border-gray-100 dark:border-zinc-800/50">
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-purple-50 dark:bg-purple-900/20 mb-3 border border-purple-100 dark:border-purple-800/30">
                        <i class="pi pi-briefcase text-2xl text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ data.total }}</p>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wide">Proyectos Registrados</p>
                </div>

                <!-- Desglose de Estado -->
                <div class="flex flex-col gap-2 mt-4 pt-1">
                    <div class="grid grid-cols-3 gap-2">
                        <!-- En Proceso -->
                        <button @click="openModal('En proceso')" class="flex flex-col items-center justify-center p-2 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-transparent hover:border-blue-200 dark:hover:border-blue-800/50 transition-all cursor-pointer group" title="Ver proyectos En Proceso">
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">{{ data.en_proceso || 0 }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                                <span class="text-[9px] font-semibold text-blue-600/80 dark:text-blue-400/80 uppercase">Curso</span>
                            </div>
                        </button>
                        
                        <!-- Pendientes -->
                        <button @click="openModal('Pendientes')" class="flex flex-col items-center justify-center p-2 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-transparent hover:border-amber-200 dark:hover:border-amber-800/50 transition-all cursor-pointer group" title="Ver proyectos Pendientes">
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform">{{ data.pendientes || 0 }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                                <span class="text-[9px] font-semibold text-amber-600/80 dark:text-amber-400/80 uppercase">Pendiente</span>
                            </div>  
                        </button>

                        <!-- Completados -->
                        <button @click="openModal('Completados')" class="flex flex-col items-center justify-center p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-transparent hover:border-emerald-200 dark:hover:border-emerald-800/50 transition-all cursor-pointer group" title="Ver proyectos Completados">
                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">{{ data.completados || 0 }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                <span class="text-[9px] font-semibold text-emerald-600/80 dark:text-emerald-400/80 uppercase">Completados</span>
                            </div>
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <!-- Pausados -->
                        <button @click="openModal('Pausados')" class="flex flex-col items-center justify-center p-2 rounded-xl bg-slate-100 dark:bg-slate-600/10 border border-transparent hover:border-slate-200 dark:hover:border-slate-500/50 transition-all cursor-pointer group" title="Ver proyectos Pausados">
                            <span class="text-lg font-bold text-slate-600 dark:text-slate-400 group-hover:scale-110 transition-transform">{{ data.pausados || 0 }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-slate-500"></div>
                                <span class="text-[9px] font-semibold text-slate-600/80 dark:text-slate-400/80 uppercase">Pausados</span>
                            </div>
                        </button>

                        <!-- Cancelados -->
                        <button @click="openModal('Cancelados')" class="flex flex-col items-center justify-center p-2 rounded-xl bg-red-50 dark:bg-red-900/10 border border-transparent hover:border-red-200 dark:hover:border-red-800/50 transition-all cursor-pointer group" title="Ver proyectos Cancelados">
                            <span class="text-lg font-bold text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform">{{ data.cancelados || 0 }}</span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                <span class="text-[9px] font-semibold text-red-600/80 dark:text-red-400/80 uppercase">Cancelados</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal para ver Proyectos por Estado -->
            <Dialog 
                v-model:visible="showModal" 
                modal 
                :header="`Proyectos - ${selectedStatus}`" 
                :style="{ width: '90vw', maxWidth: '500px' }" 
                :breakpoints="{ '641px': '100vw' }"
            >
                <div class="p-1">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Listado de proyectos en estado: <span class="lowercase font-semibold">{{ selectedStatus }}</span>.</p>
                    
                    <div v-if="loading" class="flex justify-center py-8">
                        <i class="pi pi-spin pi-spinner text-3xl text-purple-300"></i>
                    </div>
                    
                    <ul v-else-if="projectsList.length > 0" class="space-y-2 max-h-[50vh] overflow-y-auto custom-scrollbar pr-2">
                        <li v-for="(project, index) in projectsList" :key="project.id" class="flex items-center text-sm p-3 bg-gray-50 dark:bg-zinc-800/40 rounded-xl border border-gray-100 dark:border-zinc-800">
                            <div class="flex items-center justify-center w-6 h-6 rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 text-xs font-bold mr-3 flex-shrink-0">
                                {{ index + 1 }}
                            </div>
                            <div class="flex-1 min-w-0 pr-2">
                                <p class="text-gray-800 dark:text-gray-200 font-bold truncate text-sm">{{ project.name }}</p>
                                <p class="text-gray-500 dark:text-gray-400 text-xs truncate mt-0.5">
                                    <i class="pi pi-user mr-1 text-[10px]"></i> 
                                    {{ project.client?.name || 'Interno' }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 ml-2">
                                <Link :href="route('projects.show', project.id)" class="p-1.5 bg-white dark:bg-zinc-700 rounded-lg shadow-sm text-gray-400 hover:text-purple-500 border border-gray-100 dark:border-zinc-600 transition-colors" title="Ir al proyecto">
                                    <i class="pi pi-arrow-up-right text-xs"></i>
                                </Link>
                            </div>
                        </li>
                    </ul>

                    <div v-else class="text-center py-6 bg-gray-50 dark:bg-zinc-800/30 rounded-lg border border-dashed border-gray-200 dark:border-zinc-700">
                        <i class="pi pi-inbox text-purple-500 text-2xl mb-1 opacity-50"></i>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">No hay proyectos con este estado</p>
                    </div>
                </div>
            </Dialog>
        </template>
    </Card>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 4px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #52525b; }
</style>