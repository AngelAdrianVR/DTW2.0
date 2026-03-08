<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import QuotesKpi from './Components/QuotesKpi.vue';
import ClientsKpi from './Components/ClientsKpi.vue';
import ProjectsKpi from './Components/ProjectsKpi.vue';
import HostingsKpi from './Components/HostingsKpi.vue';
import PerformanceSection from './Components/PerformanceSection.vue';
import FinancialChart from './Components/FinancialChart.vue';
import InvoicedKpi from './Components/InvoicedKpi.vue';

// Se reciben los props del controlador de Laravel.
const props = defineProps({
    kpis: Object,
});
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- 1. SECCIÓN DE DESEMPEÑO (Prioridad Alta) -->
                <!-- Le damos un borde un poco más destacado o un fondo sutilmente diferente para marcar jerarquía -->
                <section class="bg-white/60 dark:bg-zinc-900/60 backdrop-blur-md overflow-hidden shadow-sm border border-indigo-100 dark:border-zinc-700/50 rounded-[2rem] p-6 relative">
                    <!-- Decoración sutil de fondo -->
                    <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 rounded-full bg-indigo-50 dark:bg-indigo-900/10 blur-3xl -z-10 pointer-events-none"></div>
                    
                    <div class="flex items-center gap-3 mb-6 pl-1">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <i class="pi pi-users text-xl m-2"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 dark:text-white">Desempeño del Equipo</h2>
                    </div>

                    <PerformanceSection :data="kpis.performance" />
                </section>

                <!-- 2. RESUMEN GENERAL OPERATIVO -->
                <section class="bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md overflow-hidden shadow-sm border border-gray-100 dark:border-zinc-800 rounded-[2rem] p-6">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6 pl-1">Resumen Operativo</h2>

                    <div class="flex flex-col gap-5">
                        <!-- Fila Superior: Cotizaciones (Grande) y Facturación -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <div class="lg:col-span-2">
                                <QuotesKpi :data="kpis.quotes" />
                            </div>
                            <div class="lg:col-span-1">
                                <InvoicedKpi class="h-full" :data="kpis.clients" />
                            </div>
                        </div>

                        <!-- Fila Inferior: KPIs Rápidos (3 columnas) -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <ProjectsKpi :data="kpis.projects" />
                            <HostingsKpi :data="kpis.hostings" />
                            <ClientsKpi :data="kpis.clients" />
                        </div>
                    </div>
                </section>

                <!-- 3. ANÁLISIS FINANCIERO -->
                <section class="bg-white/50 dark:bg-zinc-900/50 backdrop-blur-md overflow-hidden shadow-sm border border-gray-100 dark:border-zinc-800 rounded-[2rem] p-6">
                     <div class="flex items-center justify-between mb-6 pl-1">
                         <h2 class="text-xl font-bold text-gray-800 dark:text-white">Análisis Financiero</h2>
                         <span class="text-xs text-gray-400 dark:text-zinc-500 uppercase tracking-widest font-semibold">Reporte Anual</span>
                     </div>
                     <FinancialChart :data="kpis.financials.income_chart" />
                </section>

            </div>
        </div>
    </AppLayout>
</template>