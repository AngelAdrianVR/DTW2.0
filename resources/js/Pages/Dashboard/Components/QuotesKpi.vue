<script setup>
import { ref, onMounted } from 'vue';
import Card from 'primevue/card';
import Chart from 'primevue/chart';

const props = defineProps({
    data: Object,
});

const chartData = ref();
const chartOptions = ref();

onMounted(() => {
    const documentStyle = getComputedStyle(document.documentElement);
    
    chartData.value = {
        labels: ['Aceptadas', 'Rechazadas', 'Pendientes'],
        datasets: [
            {
                data: [props.data.accepted, props.data.rejected, props.data.pending],
                backgroundColor: [
                    '#86EFAC', // Un verde claro
                    '#FCA5A5', // Un rojo claro
                    '#FDE047'  // Un amarillo claro
                ],
                hoverBackgroundColor: [
                    '#4ADE80',
                    '#F87171',
                    '#FACC15'
                ]
            }
        ]
    };

    chartOptions.value = {
        plugins: {
            legend: {
                labels: {
                    usePointStyle: true,
                    color: documentStyle.getPropertyValue('--text-color-secondary') || '#6B7280'
                }
            }
        }
    };
});
</script>

<template>
    <Card class="shadow-md rounded-lg overflow-hidden h-full bg-white dark:bg-gray-800">
        <template #title>
            <div class="flex items-center space-x-2">
                <i class="pi pi-file-edit text-xl text-blue-500 dark:text-blue-400"></i>
                <span class="text-gray-700 dark:text-gray-300">Cotizaciones</span>
            </div>
        </template>
        <template #subtitle>
            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data.total }}</p>
            <p class="text-gray-500 dark:text-gray-400">Total registradas</p>
        </template>
        <template #content>
            <div class="flex justify-center">
                <Chart type="doughnut" :data="chartData" :options="chartOptions" class="w-full md:w-48" />
            </div>
        </template>
    </Card>
</template>

