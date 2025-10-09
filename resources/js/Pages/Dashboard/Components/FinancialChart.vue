<script setup>
import { ref, onMounted, watch } from 'vue';
import Chart from 'primevue/chart';
import Dropdown from 'primevue/dropdown';

// Se reciben los datos iniciales (del año actual) como una propiedad.
const props = defineProps({
    data: Object,
});

const chartData = ref();
const chartOptions = ref();
const selectedYear = ref(new Date().getFullYear());
const isLoading = ref(false);

// Genera una lista de años para el selector, desde el 2020 hasta el actual.
const years = ref(Array.from({ length: new Date().getFullYear() - 2019 }, (_, i) => new Date().getFullYear() - i));

// Formatea los datos del controlador al formato que espera PrimeVue Chart.
const setChartData = (data) => {
    if (!data) return null;
    return {
        labels: data.labels,
        datasets: data.datasets.map(dataset => ({
            ...dataset,
            fill: true,
            tension: dataset.tension || 0.4,
        }))
    };
};

// Configura las opciones visuales y de interacción del gráfico.
const setChartOptions = () => {
    const documentStyle = getComputedStyle(document.documentElement);
    const textColor = documentStyle.getPropertyValue('--p-text-color') || '#495057';
    const textColorSecondary = documentStyle.getPropertyValue('--p-text-muted-color') || '#6c757d';
    const surfaceBorder = documentStyle.getPropertyValue('--p-content-border-color') || '#dee2e6';

    return {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: { labels: { color: textColor } },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) label += ': ';
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            x: {
                ticks: { color: textColorSecondary },
                grid: { color: surfaceBorder }
            },
            y: {
                beginAtZero: true,
                ticks: {
                    color: textColorSecondary,
                    callback: function(value) {
                       return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', notation: 'compact' }).format(value);
                    }
                },
                grid: { color: surfaceBorder }
            }
        }
    };
}

// Al montar el componente, se configuran los datos y opciones iniciales.
onMounted(() => {
    chartData.value = setChartData(props.data);
    chartOptions.value = setChartOptions();
});

// Observa cambios en el año seleccionado para solicitar nuevos datos al servidor.
watch(selectedYear, async (newYear) => {
    if (!newYear) return;

    isLoading.value = true;
    try {
        const response = await fetch(`/dashboard/financials?year=${newYear}`);
        if (!response.ok) throw new Error('La respuesta del servidor no fue exitosa.');
        const newData = await response.json();
        chartData.value = setChartData(newData);
    } catch (error) {
        console.error("Error al obtener los datos financieros:", error);
        // Aquí podrías mostrar una notificación de error al usuario.
    } finally {
        isLoading.value = false;
    }
});

</script>

<template>
    <div>
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Ingresos Anuales</h3>
            <Dropdown v-model="selectedYear" :options="years" placeholder="Año" class="w-32" />
        </div>
        <div class="h-96 relative">
            <div v-if="isLoading" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-50 flex items-center justify-center rounded-lg z-10">
                <i class="pi pi-spin pi-spinner text-3xl text-blue-500"></i>
            </div>
            <Chart type="line" :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
