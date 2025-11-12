<script setup>
import { ref, onMounted, onUnmounted, shallowRef, computed } from 'vue';
import axios from 'axios';
import Chart from 'primevue/chart';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import { endOfMonth, startOfMonth, formatISO } from 'date-fns';

// --- Estado ---
const loading = ref(true);
const filterStartDate = ref(null);
const filterEndDate = ref(null);

// Estado para modo oscuro
const isDarkMode = ref(false);

// Usamos shallowRef para los datos del gráfico por rendimiento
const chartData = shallowRef({
    labels: [],
    datasets: []
});

// --- Funciones de Fecha ---

// Formatea una fecha para la API (YYYY-MM-DD)
function formatDateForAPI(date) {
    if (!date) return null;
    // Asegura que la fecha se interprete como local antes de formatear
    const localDate = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    return formatISO(localDate, { representation: 'date' });
}

// --- Lógica de Datos ---

const fetchFinancialData = async () => {
    loading.value = true;
    
    // Ya no se validan aquí, el backend los acepta nulos
    const params = {};
    if (filterStartDate.value) {
        params.date_start = formatDateForAPI(filterStartDate.value);
    }
    if (filterEndDate.value) {
        params.date_end = formatDateForAPI(filterEndDate.value);
    }

    try {
        const response = await axios.get(route('tpsp.financials'), { params });
        
        // Procesar datos para el gráfico
        const labels = response.data.map(item => {
            // Formatear fecha para mostrar (ej: 07-Nov)
            const date = new Date(item.date); // La fecha ya viene como YYYY-MM-DD
            // Ajustar por zona horaria para evitar desfase de un día
            const userTimezoneDate = new Date(date.valueOf() + date.getTimezoneOffset() * 60000);
            return userTimezoneDate.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
        });
        
        // const data = response.data.map(item => item.total); // <-- LÍNEA ANTIGUA
        
        // --- INICIO DE LA CORRECCIÓN ---
        // El API retorna `total` como string. Debemos convertirlo a número.
        // Usamos parseFloat() y `|| 0` para asegurar que siempre sea un número
        // y evitar NaN si `item.total` es null o undefined.
        const data = response.data.map(item => parseFloat(item.total) || 0);
        // --- FIN DE LA CORRECCIÓN ---

        // Actualizar chartData
        chartData.value = {
            labels: labels,
            datasets: [
                {
                    label: 'Ventas Totales',
                    data: data,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 2,
                    tension: 0.1, // Línea ligeramente curva
                    fill: true
                }
            ]
        };
        
    } catch (error) {
        console.error("Error fetching financial data:", error);
    } finally {
        loading.value = false;
    }
};

// NUEVO: Cargar todas las ventas
const fetchHistoricalData = () => {
    filterStartDate.value = null;
    filterEndDate.value = null;
    fetchFinancialData();
};

// NUEVO: Calcular total de ventas
const totalSales = computed(() => {
    const data = chartData.value.datasets[0]?.data;
    if (!data || data.length === 0) {
        // --- CORRECCIÓN 2 ---
        // Devolver '0' formateado para consistencia
        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(0);
    }
    // Ahora `data` es un array de números puros, por lo que `reduce` funciona correctamente.
    const total = data.reduce((acc, value) => acc + value, 0);
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(total);
});

// --- Opciones del Gráfico (Reactivo a Dark Mode) ---

const chartOptions = computed(() => {
    const textColor = isDarkMode.value ? 'rgba(255, 255, 255, 0.87)' : '#495057';
    const gridColor = isDarkMode.value ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(context.parsed.y);
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: textColor, // Color de texto eje Y
                    callback: function(value) {
                        return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN', maximumFractionDigits: 0 }).format(value);
                    }
                },
                grid: {
                    color: gridColor // Color de rejilla eje Y
                }
            },
            x: {
                ticks: {
                    color: textColor, // Color de texto eje X
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: {
                    color: gridColor // Color de rejilla eje X
                }
            }
        }
    };
});


// --- Manejador de Dark Mode ---
let mediaQueryMatcher;

const updateDarkMode = (e) => {
    isDarkMode.value = e.matches;
};

// --- Ciclo de Vida ---
onMounted(() => {
    // Establecer filtros por defecto al mes actual
    const today = new Date();
    filterStartDate.value = startOfMonth(today);
    filterEndDate.value = endOfMonth(today);
    
    // Cargar datos iniciales
    fetchFinancialData();

    // Configurar listener de Dark Mode
    if (window.matchMedia) {
        mediaQueryMatcher = window.matchMedia('(prefers-color-scheme: dark)');
        isDarkMode.value = mediaQueryMatcher.matches;
        mediaQueryMatcher.addEventListener('change', updateDarkMode);
    }
});

onUnmounted(() => {
    if (mediaQueryMatcher) {
        mediaQueryMatcher.removeEventListener('change', updateDarkMode);
    }
});

</script>

<template>
    <!-- Contenedor principal sensible al modo oscuro -->
    <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
        
        <!-- BARRA DE FILTROS -->
        <!-- Tarjeta sensible al modo oscuro -->
        <div class="mb-6 p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Contenedor flex para filtros y total -->
            <div class="flex flex-wrap gap-6 items-end justify-between">
                
                <!-- Grupo de Filtros -->
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex flex-col gap-2">
                        <!-- Etiqueta sensible al modo oscuro -->
                        <label for="filterStart" class="font-bold text-sm text-gray-700 dark:text-gray-300">Fecha Inicio</label>
                        <!-- CORRECCIÓN: v_model a v-model -->
                        <Calendar id="filterStart" v-model="filterStartDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Desde" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="filterEnd" class="font-bold text-sm text-gray-700 dark:text-gray-300">Fecha Fin</label>
                        <Calendar id="filterEnd" v-model="filterEndDate" dateFormat="dd/mm/yy" :showIcon="true" placeholder="Hasta" />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button label="Filtrar" icon="pi pi-filter" @click="fetchFinancialData" :loading="loading" />
                        <!-- NUEVO BOTÓN: Histórico -->
                        <Button label="Ver Histórico" icon="pi pi-calendar-times" @click="fetchHistoricalData" :loading="loading" class="p-button-outlined" />
                    </div>
                </div>

                <!-- NUEVO: Total de Ventas -->
                <div class="p-4 bg-green-50 dark:bg-green-900 border-l-4 border-green-500 dark:border-green-400 rounded-r-lg">
                    <label class="block text-sm font-medium text-green-700 dark:text-green-300">Total en Rango</label>
                    <span class="text-2xl font-bold text-green-800 dark:text-green-200">{{ totalSales }}</span>
                </div>

            </div>
        </div>

        <!-- CONTENEDOR DE GRÁFICA -->
        <!-- Tarjeta sensible al modo oscuro -->
        <div class="relative h-[50vh] p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- La clave :key es importante para forzar a Chart.js a redibujar cuando las opciones cambian -->
            <Chart type="line" :data="chartData" :options="chartOptions" :key="isDarkMode" class="w-full h-full" />
            
            <!-- Indicador de Carga (sensible al modo oscuro) -->
            <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 bg-opacity-75 dark:bg-opacity-75">
                <i class="pi pi-spin pi-spinner" style="font-size: 3rem; color: #4b5563;"></i>
            </div>

             <!-- Mensaje de no datos (sensible al modo oscuro) -->
            <div v-if="!loading && chartData.datasets[0]?.data.length === 0" class="absolute inset-0 flex items-center justify-center bg-white dark:bg-gray-800 bg-opacity-50 dark:bg-opacity-50">
                <span class="text-gray-500 dark:text-gray-400 font-semibold">No se encontraron datos de ventas en este rango.</span>
            </div>
        </div>
    </div>
</template>