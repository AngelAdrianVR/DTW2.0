<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import CustomWeekPicker from '@/Components/CustomWeekPicker.vue';

const props = defineProps({
    data: Array
});

const selectedUser = ref(null);
const productivityData = ref(null);
const isLoading = ref(false);

function getISOWeekStringFromDate(date) {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    return `${d.getUTCFullYear()}-W${String(weekNo).padStart(2, '0')}`;
}

const week = ref(getISOWeekStringFromDate(new Date()));

const formattedWeekLabel = computed(() => {
    if (!week.value) return 'Seleccionar semana';
    const [y, w] = week.value.split('-W');
    return `Semana ${parseInt(w)}, ${y}`;
});

function changeWeek(step) {
    if (!week.value) return;
    const [year, w] = week.value.split('-W').map(Number);
    const d = new Date(year, 0, 1 + (w - 1) * 7);
    d.setDate(d.getDate() + (step * 7));
    
    week.value = getISOWeekStringFromDate(d);
    onWeekChange();
}

async function fetchProductivity(user, isWeekChange = false) {
    if (selectedUser.value?.id === user.id && !isWeekChange) {
        selectedUser.value = null;
        productivityData.value = null;
        return;
    }
    
    selectedUser.value = user;
    isLoading.value = true;
    
    if (!isWeekChange) {
        productivityData.value = null;
    }

    try {
        const response = await axios.get(route('dashboard.performance', { user: user.id }), {
            params: { week: week.value }
        });
        productivityData.value = response.data;
    } catch (error) {
        console.error("Error fetching productivity data:", error);
    } finally {
        isLoading.value = false;
    }
}

function toggleProductivity(user) {
    fetchProductivity(user, false);
}

function onWeekChange() {
    if (selectedUser.value) {
        fetchProductivity(selectedUser.value, true);
    }
}

const productivityTableData = computed(() => {
    if (!productivityData.value?.week_data) return [];
    
    // El controlador manda "Monday,", "Tuesday,", etc. Así que agregamos las comas al diccionario
    const dayTranslations = { 
        'Monday,': 'Lunes,', 
        'Tuesday,': 'Martes,', 
        'Wednesday,': 'Miércoles,', 
        'Thursday,': 'Jueves,', 
        'Friday,': 'Viernes,', 
        'Saturday,': 'Sábado,', 
        'Sunday,': 'Domingo,' 
    };
    
    return productivityData.value.week_data.map(data => ({
        day: `${dayTranslations[data.day_name] || data.day_name} ${data.date}`,
        activities: data.activities,
        hours: data.total_day_hours_formatted
    }));
});
</script>

<template>
    <div class="mt-4">
        <!-- User Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="user in data" :key="user.id" 
                @click="toggleProductivity(user)"
                :class="['rounded-2xl cursor-pointer transition-all duration-300 relative', user.is_active ? 'p-[2px] animated-border-bg' : 'border border-gray-100 dark:border-zinc-800', { 'ring-2 ring-offset-2 ring-offset-gray-100 dark:ring-offset-zinc-950 ring-zinc-500 dark:ring-zinc-600': selectedUser?.id === user.id }]">
                
                <div class="bg-white dark:bg-zinc-900 p-5 rounded-[14px] h-full shadow-sm hover:shadow-md transition-shadow">
                    <!-- Cabecera -->
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ user.name }}</p>
                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1 bg-gray-100 dark:bg-zinc-800 px-2 py-0.5 rounded w-fit">{{ user.total_hours_formatted }}</p>
                        </div>
                        <div class="flex flex-col items-end">
                             <p class="text-[10px] text-gray-400 dark:text-gray-500">Último ingreso</p>
                             <p class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ user.last_login_at }}</p>
                        </div>
                    </div>
                    
                    <!-- Estadísticas / Menú inferior -->
                    <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-50 dark:border-zinc-800">
                        <div class="flex items-center space-x-1.5" title="Tareas completadas">
                           <i class="pi pi-check-circle text-emerald-500 text-sm"></i>
                           <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ user.stats.completed }}</span>
                        </div>
                        
                        <!-- Dropdown de Tareas En Proceso -->
                        <div class="relative group" @click.stop>
                            <div class="flex items-center space-x-1.5 cursor-pointer p-1 -m-1 rounded hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors" title="Ver tareas en curso">
                               <i class="pi pi-spin pi-spinner text-blue-500 text-sm"></i>
                               <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ user.stats.in_progress }}</span>
                            </div>
                            
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-56 sm:w-64 bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 shadow-xl rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="p-2 border-b border-gray-100 dark:border-zinc-700 bg-blue-50/50 dark:bg-blue-900/20 rounded-t-lg">
                                    <p class="text-[10px] font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider text-center">En Proceso Ahora</p>
                                </div>
                                <div class="p-1 max-h-40 overflow-y-auto custom-scrollbar">
                                    <template v-if="user.stats.in_progress_details?.length > 0">
                                        <Link v-for="task in user.stats.in_progress_details" :key="'prog-'+task.id" 
                                              :href="route('projects.show', task.project_id)" 
                                              class="block px-2 py-1.5 text-xs sm:text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded transition-colors truncate"
                                              :title="task.title">
                                            <i class="pi pi-play text-[10px] text-blue-500 mr-1"></i> {{ task.title }}
                                        </Link>
                                    </template>
                                    <div v-else class="px-2 py-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                                        Ninguna tarea activa
                                    </div>
                                </div>
                                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-px border-[6px] border-transparent border-t-white dark:border-t-zinc-800"></div>
                            </div>
                        </div>

                        <!-- Dropdown de Tareas Pendientes -->
                        <div class="relative group" @click.stop>
                            <div class="flex items-center space-x-1.5 cursor-pointer p-1 -m-1 rounded hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors" title="Ver tareas pendientes">
                               <i class="pi pi-clock text-amber-500 text-sm"></i>
                               <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ user.stats.pending }}</span>
                            </div>
                            
                            <div class="absolute bottom-full mb-2 left-1/2 -translate-x-1/2 w-56 sm:w-64 bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 shadow-xl rounded-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                                <div class="p-2 border-b border-gray-100 dark:border-zinc-700 bg-gray-50/50 dark:bg-zinc-800/50 rounded-t-lg">
                                    <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">Tareas Pendientes</p>
                                </div>
                                <div class="p-1 max-h-40 overflow-y-auto custom-scrollbar">
                                    <template v-if="user.stats.pending_details?.length > 0">
                                        <Link v-for="task in user.stats.pending_details" :key="'pend-'+task.id" 
                                              :href="route('projects.show', task.project_id)" 
                                              class="block px-2 py-1.5 text-xs sm:text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded transition-colors truncate"
                                              :title="task.title">
                                            • {{ task.title }}
                                        </Link>
                                    </template>
                                    <div v-else class="px-2 py-3 text-xs text-gray-500 dark:text-gray-400 text-center">
                                        Excelente, no hay pendientes
                                    </div>
                                </div>
                                <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-px border-[6px] border-transparent border-t-white dark:border-t-zinc-800"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productivity Detail Section -->
        <div v-if="selectedUser" class="mt-8 bg-white dark:bg-zinc-900 shadow-xl shadow-gray-200/50 dark:shadow-black/20 rounded-[24px] p-6 sm:p-8 border border-gray-100 dark:border-zinc-800 animation-fade-in relative">
            <div class="absolute inset-0 overflow-hidden rounded-[24px] pointer-events-none">
                <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/10 dark:bg-indigo-500/5 rounded-full blur-3xl"></div>
            </div>
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-5 relative z-50">
                <div>
                    <h4 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                        Productividad: <span class="text-indigo-500 dark:text-indigo-400">{{ selectedUser.name }}</span>
                    </h4>
                </div>
                
                <div class="flex items-center gap-3 w-full sm:w-auto">
                     <div class="flex items-center bg-gray-100/80 dark:bg-zinc-800/80 backdrop-blur-md rounded-full p-1 border border-gray-200/50 dark:border-zinc-700/50 shadow-inner group flex-1 sm:flex-none relative">
                         <button @click="changeWeek(-1)" class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white dark:hover:bg-zinc-700 hover:shadow-sm transition-all text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 relative z-20" aria-label="Semana anterior">
                             <i class="pi pi-chevron-left text-xs font-bold"></i>
                         </button>
                         
                         <CustomWeekPicker v-model="week" @update:modelValue="onWeekChange">
                             <template #trigger="{ toggle }">
                                 <div @click="toggle" class="relative flex items-center justify-center min-w-[140px] px-3 cursor-pointer z-20">
                                     <div class="flex items-center space-x-2 text-sm font-semibold text-gray-700 dark:text-gray-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                         <span class="select-none tracking-tight">{{ formattedWeekLabel }}</span>
                                         <i class="pi pi-calendar text-gray-400 group-hover:text-indigo-500 transition-colors text-sm"></i>
                                     </div>
                                 </div>
                             </template>
                         </CustomWeekPicker>
                         
                         <button @click="changeWeek(1)" class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-white dark:hover:bg-zinc-700 hover:shadow-sm transition-all text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 relative z-20" aria-label="Semana siguiente">
                             <i class="pi pi-chevron-right text-xs font-bold"></i>
                         </button>
                     </div>
                     
                     <button @click="selectedUser = null" class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-50 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-zinc-700 transition-all focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-zinc-600 shadow-sm flex-shrink-0 relative z-50" aria-label="Cerrar">
                         <i class="pi pi-times"></i>
                     </button>
                </div>
            </div>

            <div v-if="isLoading && !productivityData" class="space-y-4 relative z-10 py-2">
                <div class="flex items-center justify-between mb-4">
                     <div class="h-4 w-32 bg-gray-200 dark:bg-zinc-800 rounded animate-pulse"></div>
                     <div class="h-4 w-40 bg-gray-200 dark:bg-zinc-800 rounded animate-pulse"></div>
                </div>
                <div class="h-12 w-full bg-gray-100 dark:bg-zinc-800 rounded-xl animate-pulse" v-for="i in 4" :key="i"></div>
            </div>

            <div v-if="productivityData" class="animation-fade-in relative z-10">
                 <div class="flex justify-between items-baseline mb-4 px-1">
                     <p class="font-medium text-gray-500 dark:text-gray-400">{{ productivityData.week_label }}</p>
                     <p class="text-gray-800 dark:text-gray-200 text-sm">Total semana: <span class="font-bold text-lg ml-1">{{ productivityData.total_week_hours_formatted }}</span></p>
                 </div>

                <DataTable :value="productivityTableData" class="p-datatable-sm zinc-table" responsiveLayout="scroll">
                    <Column field="day" header="Día" style="width: 15%">
                        <template #body="{ data }">
                            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ data.day }}</span>
                        </template>
                    </Column>
                    <Column field="activities" header="Actividades">
                        <template #body="{ data }">
                            <pre class="font-sans whitespace-pre-wrap text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ data.activities }}</pre>
                        </template>
                    </Column>
                    <Column field="hours" header="Horas" style="width: 15%">
                        <template #body="{ data }">
                            <span class="font-mono font-medium text-gray-800 dark:text-gray-100 bg-gray-100 dark:bg-zinc-800 px-2 py-1 rounded">{{ data.hours }}</span>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animated-border-bg {
  background: linear-gradient(120deg, #52525b, #a1a1aa, #52525b);
  background-size: 200% 200%;
  animation: borderGradientMove 4s ease infinite;
}

@keyframes borderGradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

pre {
    font-family: inherit;
}

.animation-fade-in {
    animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d4d4d8; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #52525b; }

</style>

<style>
/* Estilos globales para PrimeVue DataTable 
  Al estar fuera de "scoped", Vue no altera las clases y el navegador lee la ruta exacta.
*/
.zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7 !important;
}

.zinc-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode 
  Agregamos html.dark para darle un "extra" de especificidad y ganarle a PrimeVue
*/
html.dark .zinc-table .p-datatable-thead > tr > th,
.dark .zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>