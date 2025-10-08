<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Card from 'primevue/card';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Skeleton from 'primevue/skeleton';

const props = defineProps({
    data: Array
});

const selectedUser = ref(null);
const productivityData = ref(null);
const isLoading = ref(false);
const week = ref(getCurrentWeekString());

function getCurrentWeekString() {
    const now = new Date();
    const year = now.getFullYear();
    const onejan = new Date(year, 0, 1);
    const weekNumber = Math.ceil((((now.getTime() - onejan.getTime()) / 86400000) + onejan.getDay() + 1) / 7);
    return `${year}-W${String(weekNumber).padStart(2, '0')}`;
}

async function fetchProductivity(user) {
    if (selectedUser.value?.id === user.id) {
        selectedUser.value = null;
        productivityData.value = null;
        return;
    }
    
    selectedUser.value = user;
    isLoading.value = true;
    productivityData.value = null;

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

const productivityTableData = computed(() => {
    if (!productivityData.value?.week_data) return [];
    
    const dayTranslations = { Monday: 'Lunes', Tuesday: 'Martes', Wednesday: 'Miércoles', Thursday: 'Jueves', Friday: 'Viernes', Saturday: 'Sábado', Sunday: 'Domingo' };
    
    return Object.entries(productivityData.value.week_data).map(([day, data]) => ({
        day: dayTranslations[day] || day,
        activities: data.activities,
        hours: data.total_day_hours_formatted
    }));
});

function onWeekChange() {
    if (selectedUser.value) {
        fetchProductivity(selectedUser.value);
    }
}

</script>

<template>
    <div class="mt-8">
        <h3 class="text-xl font-bold text-gray-800 dark:text-gray-200 mb-4">Desempeño del Equipo</h3>
        
        <!-- User Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="user in data" :key="user.id" 
                @click="fetchProductivity(user)"
                :class="['rounded-2xl cursor-pointer transition-all duration-300 relative', user.is_active ? 'p-[2px] animated-border-bg' : '', { 'ring-4 ring-offset-2 ring-offset-gray-100 dark:ring-offset-gray-900 ring-blue-500/70': selectedUser?.id === user.id }]">
                <div class="bg-white dark:bg-gray-800 p-4 rounded-[14px] h-full">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-lg text-gray-900 dark:text-white">{{ user.name }} ({{ user.total_hours_formatted }})</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Último ingreso: {{ user.last_login_at }}</p>
                        </div>
                    </div>
                    <div class="flex space-x-4 mt-3 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center space-x-1" title="Tareas completadas">
                           <i class="pi pi-check-circle text-green-500"></i>
                           <span>{{ user.stats.completed }}</span>
                        </div>
                         <div class="flex items-center space-x-1" title="Tareas en proceso">
                           <i class="pi pi-spin pi-spinner text-blue-500"></i>
                           <span>{{ user.stats.in_progress }}</span>
                        </div>
                        <div class="flex items-center space-x-1" title="Tareas pendientes">
                           <i class="pi pi-clock text-yellow-500"></i>
                           <span>{{ user.stats.pending }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Productivity Section -->
        <div v-if="selectedUser" class="mt-8 bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-bold text-gray-800 dark:text-gray-200">Productividad de {{ selectedUser.name }}</h4>
                <div class="flex items-center gap-4">
                     <input type="week" v-model="week" @change="onWeekChange" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                     <Button icon="pi pi-times" severity="secondary" text rounded @click="selectedUser = null" aria-label="Cerrar" />
                </div>
            </div>

            <div v-if="isLoading" class="space-y-2">
                <Skeleton height="2rem" v-for="i in 7" :key="i" />
            </div>

            <div v-if="!isLoading && productivityData">
                 <div class="flex justify-between items-baseline mb-4">
                     <p class="font-semibold text-gray-600 dark:text-gray-300">{{ productivityData.week_label }}</p>
                     <p class="text-gray-800 dark:text-gray-100">Total de horas: <span class="font-bold">{{ productivityData.total_week_hours_formatted }}</span></p>
                 </div>

                <DataTable :value="productivityTableData" class="p-datatable-sm" responsiveLayout="scroll">
                    <Column field="day" header="Día" style="width: 15%"></Column>
                    <Column field="activities" header="Actividades">
                        <template #body="slotProps">
                            <pre class="font-sans whitespace-pre-wrap">{{ slotProps.data.activities }}</pre>
                        </template>
                    </Column>
                    <Column field="hours" header="Horas Trabajadas" style="width: 20%"></Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animated-border-bg {
  background: linear-gradient(120deg, #6215C0, #17EDF4, #6215C0);
  background-size: 400% 400%;
  animation: borderGradientMove 5s linear infinite;
}

@keyframes borderGradientMove {
  0% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

pre {
    font-family: inherit;
}
</style>

