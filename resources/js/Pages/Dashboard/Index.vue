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

// --- La lógica de formato se ha movido al nuevo componente InvoicedKpi.vue ---

</script>

<template>
    <AppLayout title="Dashboard">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Contenedor de KPIs Generales -->
                <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm overflow-hidden shadow-xl sm:rounded-lg p-5 mb-8">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">Resumen General</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        
                        <!-- Componentes Modulares Existentes -->
                        <QuotesKpi :data="kpis.quotes" />
                        <ClientsKpi :data="kpis.clients" />
                        <ProjectsKpi :data="kpis.projects" />
                        <HostingsKpi :data="kpis.hostings" />

                        <!-- KPI de Total Facturado reemplazado por el nuevo componente interactivo -->
                        <InvoicedKpi class="col-span-2" :data="kpis.clients" />

                    </div>
                </div>

                <!-- Sección de Desempeño (existente) -->
                <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm overflow-hidden shadow-xl sm:rounded-lg p-5">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">Desempeño del Equipo</h2>
                    <PerformanceSection :data="kpis.performance" />
                </div>


                <!-- Sección de Finanzas con el componente de gráfica -->
                <div class="bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm overflow-hidden shadow-xl sm:rounded-lg p-5 mt-8">
                     <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-6">Análisis Financiero</h2>
                     <FinancialChart :data="kpis.financials.income_chart" />
                </div>
                

            </div>
        </div>
    </AppLayout>
</template>

