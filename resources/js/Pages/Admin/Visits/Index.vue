<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomWeekPicker from '@/Components/CustomWeekPicker.vue';
import Chart from 'primevue/chart';

const props = defineProps({
    initial: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const mode = ref(props.filters.mode);
const week = ref(props.filters.week);
const month = ref(props.filters.month);

const data = ref(props.initial);
const loading = ref(false);

const palette = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#a855f7', '#84cc16', '#f97316'];

/* ------------------------------------------------------------------ */
/*  Formato                                                            */
/* ------------------------------------------------------------------ */
const numberFormatter = new Intl.NumberFormat('es-MX');

const formatNumber = (value) => numberFormatter.format(Number(value) || 0);

const formatDelta = (value) => {
    if (value === null || value === undefined) return '�?"';
    return `${value > 0 ? '+' : ''}${value}%`;
};

const deltaClass = (value) => {
    if (value === null || value === undefined || value === 0) {
        return 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
    }
    return value > 0
        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400'
        : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400';
};

const formatDuration = (seconds) => {
    const total = Math.round(Number(seconds) || 0);
    if (total < 60) return `${total}s`;

    const minutes = Math.floor(total / 60);
    const rest = total % 60;

    if (minutes < 60) return rest ? `${minutes}m ${rest}s` : `${minutes}m`;

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return remainingMinutes ? `${hours}h ${remainingMinutes}m` : `${hours}h`;
};

const percentageOf = (value, total) => {
    if (!total) return 0;
    return Math.min(100, Math.round((value / total) * 100));
};

/* ------------------------------------------------------------------ */
/*  Carga de datos                                                     */
/* ------------------------------------------------------------------ */
const fetchData = async () => {
    loading.value = true;

    try {
        const params = new URLSearchParams({
            mode: mode.value,
            week: week.value,
            month: month.value,
        });

        const response = await fetch(`${route('admin.visits.data')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) throw new Error('No se pudieron cargar las métricas.');

        data.value = await response.json();
    } catch (error) {
        console.error(error);
    } finally {
        loading.value = false;
    }
};

watch([mode, week, month], fetchData);

// Permite enlazar directamente a una sección de la página (#evolucion,
// #horario, #fuentes) dentro del contenedor con scroll del layout.
onMounted(() => {
    if (!window.location.hash) return;

    const target = document.querySelector(window.location.hash);

    if (target) {
        setTimeout(() => target.scrollIntoView({ block: 'start', behavior: 'auto' }), 250);
    }
});

const goToCurrentPeriod = () => {
    if (mode.value === 'week') {
        week.value = props.filters.current_week;
    } else {
        month.value = props.filters.current_month;
    }
};

/* ------------------------------------------------------------------ */
/*  Gráficas                                                           */
/* ------------------------------------------------------------------ */
const axisOptions = () => ({
    ticks: { color: '#a1a1aa', font: { size: 11 } },
    grid: { color: 'rgba(161, 161, 170, 0.14)', drawBorder: false },
});

const baseOptions = (overrides = {}) => ({
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: {
            labels: {
                color: '#a1a1aa',
                usePointStyle: true,
                pointStyle: 'circle',
                boxWidth: 7,
                boxHeight: 7,
                padding: 16,
                font: { size: 11 },
            },
        },
        tooltip: {
            backgroundColor: 'rgba(24, 24, 27, 0.92)',
            padding: 10,
            cornerRadius: 10,
            titleFont: { size: 11 },
            bodyFont: { size: 12 },
        },
    },
    ...overrides,
});

const evolutionChart = computed(() => ({
    labels: data.value.series.labels,
    datasets: [
        {
            label: 'Visitas',
            data: data.value.series.views,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.14)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#6366f1',
        },
        {
            label: 'Visitantes únicos',
            data: data.value.series.visitors,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.10)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            borderDash: [5, 4],
            pointRadius: 0,
            pointHoverRadius: 5,
            pointHoverBackgroundColor: '#10b981',
        },
    ],
}));

const evolutionOptions = computed(() =>
    baseOptions({
        scales: {
            x: { ...axisOptions(), ticks: { ...axisOptions().ticks, maxRotation: 0, autoSkipPadding: 12 } },
            y: { ...axisOptions(), beginAtZero: true, ticks: { ...axisOptions().ticks, precision: 0 } },
        },
    })
);

const hourlyChart = computed(() => ({
    labels: data.value.hourly.labels,
    datasets: [
        {
            label: 'Visitas',
            data: data.value.hourly.views,
            backgroundColor: 'rgba(99, 102, 241, 0.75)',
            hoverBackgroundColor: '#6366f1',
            borderRadius: 6,
            maxBarThickness: 26,
        },
    ],
}));

const hourlyOptions = computed(() =>
    baseOptions({
        plugins: { legend: { display: false }, tooltip: baseOptions().plugins.tooltip },
        scales: {
            x: {
                ...axisOptions(),
                grid: { display: false },
                ticks: { ...axisOptions().ticks, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
            },
            y: { ...axisOptions(), beginAtZero: true, ticks: { ...axisOptions().ticks, precision: 0 } },
        },
    })
);

const weekdayChart = computed(() => ({
    labels: data.value.weekdays.labels,
    datasets: [
        {
            label: 'Visitas',
            data: data.value.weekdays.views,
            backgroundColor: 'rgba(6, 182, 212, 0.7)',
            hoverBackgroundColor: '#06b6d4',
            borderRadius: 6,
            maxBarThickness: 34,
        },
    ],
}));

const weekdayOptions = computed(() =>
    baseOptions({
        plugins: { legend: { display: false }, tooltip: baseOptions().plugins.tooltip },
        scales: {
            x: { ...axisOptions(), grid: { display: false }, ticks: { ...axisOptions().ticks, maxRotation: 0 } },
            y: { ...axisOptions(), beginAtZero: true, ticks: { ...axisOptions().ticks, precision: 0 } },
        },
    })
);

const devicesChart = computed(() => ({
    labels: data.value.devices.map((item) => item.label),
    datasets: [
        {
            data: data.value.devices.map((item) => item.value),
            backgroundColor: palette,
            borderWidth: 0,
            hoverOffset: 6,
        },
    ],
}));

const devicesOptions = computed(() =>
    baseOptions({
        cutout: '64%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#a1a1aa',
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 7,
                    boxHeight: 7,
                    padding: 14,
                    font: { size: 11 },
                },
            },
            tooltip: baseOptions().plugins.tooltip,
        },
    })
);

const referrersChart = computed(() => ({
    labels: data.value.referrers.map((item) => item.label),
    datasets: [
        {
            data: data.value.referrers.map((item) => item.views),
            backgroundColor: palette,
            borderWidth: 0,
            hoverOffset: 6,
        },
    ],
}));

const referrersOptions = computed(() =>
    baseOptions({
        cutout: '64%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    color: '#a1a1aa',
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 7,
                    boxHeight: 7,
                    padding: 12,
                    font: { size: 11 },
                },
            },
            tooltip: baseOptions().plugins.tooltip,
        },
    })
);

const kpis = computed(() => data.value.kpis);
const maxPageViews = computed(() => Math.max(1, ...(data.value.top_pages.map((page) => page.views) || [0])));
const maxBrowserViews = computed(() => Math.max(1, ...(data.value.browsers.map((browser) => browser.views) || [0])));
const hasData = computed(() => kpis.value.views > 0);

const weekTriggerLabel = computed(() => {
    const [year, number] = String(week.value).split('-W');
    return `Semana ${number} · ${year}`;
});
</script>

<template>
    <AppLayout title="Métricas de Visitas">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Encabezado / filtros -->
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Métricas de Visitas</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                            Panorama del tráfico del sitio público, con bots excluidos de los cálculos.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <!-- Selector Semana / Mes -->
                        <div class="inline-flex rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800">
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                                :class="mode === 'week'
                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-zinc-700 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                @click="mode = 'week'"
                            >
                                Semana
                            </button>
                            <button
                                type="button"
                                class="rounded-lg px-4 py-2 text-sm font-medium transition-colors"
                                :class="mode === 'month'
                                    ? 'bg-white text-gray-900 shadow-sm dark:bg-zinc-700 dark:text-white'
                                    : 'text-gray-500 hover:text-gray-800 dark:text-zinc-400 dark:hover:text-zinc-200'"
                                @click="mode = 'month'"
                            >
                                Mes
                            </button>
                        </div>

                        <!-- Selector de fecha -->
                        <CustomWeekPicker v-if="mode === 'week'" v-model="week">
                            <template #trigger="{ toggle }">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 whitespace-nowrap rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800 sm:w-auto"
                                    @click="toggle"
                                >
                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                    </svg>
                                    {{ weekTriggerLabel }}
                                </button>
                            </template>
                        </CustomWeekPicker>

                        <div v-else class="relative">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <input
                                v-model="month"
                                type="month"
                                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-4 text-sm font-medium text-gray-700 shadow-sm outline-none transition-colors focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20 sm:w-56"
                            >
                        </div>

                        <button
                            type="button"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800"
                            @click="goToCurrentPeriod"
                        >
                            Actual
                        </button>
                    </div>
                </div>

                <div class="relative space-y-6 transition-opacity" :class="{ 'pointer-events-none opacity-60': loading }">
                    <!-- Indicador de carga -->
                    <div v-if="loading" class="absolute inset-x-0 -top-2 z-20 flex justify-center">
                        <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-gray-500 shadow-md dark:bg-zinc-800 dark:text-zinc-300">
                            <i class="pi pi-spin pi-spinner text-indigo-500"></i>
                            Actualizando�?�
                        </span>
                    </div>

                    <!-- Aviso sin datos -->
                    <div
                        v-if="!hasData"
                        class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                        </svg>
                        <p>
                            Aún no hay visitas registradas en <strong>{{ data.period.label }}</strong>. Las visitas al sitio público se registrarán automáticamente a partir de ahora.
                        </p>
                    </div>

                    <!-- KPIs principales -->
                    <div class="grid grid-cols-2 gap-4 xl:grid-cols-3">
                        <!-- Visitas -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="deltaClass(kpis.views_delta)">
                                    {{ formatDelta(kpis.views_delta) }}
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ formatNumber(kpis.views) }}</p>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Visitas totales</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">vs. {{ data.period.previous_label }} ({{ formatNumber(kpis.previous_views) }})</p>
                        </div>

                        <!-- Visitantes únicos -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                    </svg>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="deltaClass(kpis.visitors_delta)">
                                    {{ formatDelta(kpis.visitors_delta) }}
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ formatNumber(kpis.visitors) }}</p>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Visitantes únicos</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">vs. {{ data.period.previous_label }} ({{ formatNumber(kpis.previous_visitors) }})</p>
                        </div>

                        <!-- Vistas por visitante -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59" />
                                    </svg>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ formatNumber(kpis.sessions) }} sesiones
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ kpis.views_per_visitor }}</p>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Vistas por visitante</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">Profundidad media de navegación</p>
                        </div>

                        <!-- Rebote -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                                    </svg>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ 100 - kpis.bounce_rate }}% engagement
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ kpis.bounce_rate }}%</p>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Tasa de rebote</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">Sesiones con una sola página vista</p>
                        </div>

                        <!-- Duración media -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    {{ formatNumber(kpis.pages) }} páginas
                                </span>
                            </div>
                            <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ formatDuration(kpis.avg_session_seconds) }}</p>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">Duración media de sesión</p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-zinc-500">Tiempo entre la primera y última visita</p>
                        </div>

                        <!-- Nuevos vs recurrentes -->
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 019.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <p class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ formatNumber(kpis.new_visitors) }}</p>
                                <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">nuevos</span>
                            </div>
                            <p class="text-sm font-medium text-gray-500 dark:text-zinc-400">{{ formatNumber(kpis.returning_visitors) }} visitantes recurrentes</p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div
                                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400"
                                    :style="{ width: `${percentageOf(kpis.new_visitors, kpis.visitors)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Highlights del periodo -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-zinc-500">Mejor día</p>
                                <p class="text-sm font-semibold leading-tight text-gray-900 dark:text-white">{{ kpis.best_day.label }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">{{ formatNumber(kpis.best_day.views) }} visitas</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-zinc-500">Hora pico</p>
                                <p class="text-sm font-semibold leading-tight text-gray-900 dark:text-white">{{ kpis.peak_hour.label }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">{{ formatNumber(kpis.peak_hour.views) }} visitas</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-zinc-500">Páginas distintas</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatNumber(kpis.pages) }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">Vistas en el periodo</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-zinc-500">Sesiones</p>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ formatNumber(kpis.sessions) }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">{{ formatNumber(kpis.bounce_rate) }}% de rebote</p>
                            </div>
                        </div>
                    </div>

                    <!-- Evolución + dispositivos -->
                    <div id="evolucion" class="grid scroll-mt-24 grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Evolución de visitas</h3>
                                    <p class="text-xs text-gray-500 dark:text-zinc-400">{{ data.period.label }}</p>
                                </div>
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                                    {{ formatNumber(kpis.views) }} visitas
                                </span>
                            </div>
                            <div class="h-72">
                                <Chart type="line" :data="evolutionChart" :options="evolutionOptions" />
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Dispositivos</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">Cómo navegan tus visitantes</p>
                            </div>
                            <div v-if="data.devices.length" class="h-72">
                                <Chart type="doughnut" :data="devicesChart" :options="devicesOptions" />
                            </div>
                            <div v-else class="flex h-72 items-center justify-center text-sm text-gray-400 dark:text-zinc-500">
                                Sin datos en el periodo
                            </div>
                        </div>
                    </div>

                    <!-- Horas + páginas top -->
                    <div id="horario" class="grid scroll-mt-24 grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="flex flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:col-span-2">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Visitas por hora</h3>
                                    <p class="text-xs text-gray-500 dark:text-zinc-400">Hora pico: {{ kpis.peak_hour.label }}</p>
                                </div>
                            </div>
                            <div class="h-80">
                                <Chart type="bar" :data="hourlyChart" :options="hourlyOptions" />
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Páginas más visitadas</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">Top del periodo seleccionado</p>
                            </div>

                            <div v-if="data.top_pages.length" class="space-y-3.5">
                                <div v-for="page in data.top_pages" :key="page.path">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-700 dark:text-zinc-200">{{ page.label }}</span>
                                        <span class="shrink-0 text-xs font-semibold text-gray-500 dark:text-zinc-400">{{ formatNumber(page.views) }}</span>
                                    </div>
                                    <p class="truncate text-[11px] text-gray-400 dark:text-zinc-500">{{ page.path }}</p>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-indigo-500" :style="{ width: `${(page.views / maxPageViews) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex h-40 items-center justify-center text-sm text-gray-400 dark:text-zinc-500">
                                Sin datos en el periodo
                            </div>
                        </div>
                    </div>

                    <!-- Días + fuentes + navegadores -->
                    <div id="fuentes" class="grid scroll-mt-24 grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Por día de la semana</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">Mejor día: {{ kpis.best_day.label }}</p>
                            </div>
                            <div class="h-56">
                                <Chart type="bar" :data="weekdayChart" :options="weekdayOptions" />
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Fuentes de tráfico</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">De dónde llegan tus visitantes</p>
                            </div>
                            <div v-if="data.referrers.length" class="h-56">
                                <Chart type="doughnut" :data="referrersChart" :options="referrersOptions" />
                            </div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-gray-400 dark:text-zinc-500">
                                Sin datos en el periodo
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4">
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Navegadores</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">Tecnología de tus visitantes</p>
                            </div>

                            <div v-if="data.browsers.length" class="space-y-4">
                                <div v-for="browser in data.browsers" :key="browser.label">
                                    <div class="flex items-baseline justify-between">
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-gray-700 dark:text-zinc-200">{{ browser.label }}</span>
                                        <span class="shrink-0 text-xs font-semibold text-gray-500 dark:text-zinc-400">{{ formatNumber(browser.views) }}</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-cyan-500" :style="{ width: `${(browser.views / maxBrowserViews) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="flex h-40 items-center justify-center text-sm text-gray-400 dark:text-zinc-500">
                                Sin datos en el periodo
                            </div>
                        </div>
                    </div>

                    <p class="pb-2 text-center text-xs text-gray-400 dark:text-zinc-500">
                        Los bots, crawlers y peticiones internas del panel quedan excluidos de estas métricas.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Chart.js necesita que el contenedor tenga altura definida para ocupar
   todo el alto del panel (h-72, h-80, h-56) y no quedarse en su alto nativo. */
:deep(.p-chart) {
    height: 100%;
}
</style>
