<script setup>
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import Button from 'primevue/button';
import Toast from 'primevue/toast';
import AppLayout from '@/Layouts/AppLayout.vue';

// Importa los componentes de las pestañas
import ProductionOrdersTab from './ProductionOrdersTab.vue';
import ProductsTab from './ProductsTab.vue';
import MovementsTab from './MovementsTab.vue';
import FinanceTab from './FinanceTab.vue';

// --- Componente modular de orden ---
import ProductionOrderModal from '@/Components/TPSP/ProductionOrderModal.vue';

const toast = useToast();
const displayNewOrderModal = ref(false);

// NUEVO: Llave maestra para forzar la recarga de la pestaña de órdenes
const ordersRefreshKey = ref(0);

// Estado para nuestras pestañas personalizadas
const activeTab = ref('orders');

const tabs = [
    { id: 'orders', label: 'Órdenes de Producción' },
    { id: 'products', label: 'Productos' },
    { id: 'movements', label: 'Movimientos' },
    { id: 'finance', label: 'Finanzas' }
];

const openNewOrderModal = () => {
    displayNewOrderModal.value = true;
};

const onOrderSaved = () => {
    ordersRefreshKey.value++;
};
</script>

<template>
    <AppLayout title="TPSP - Gestión de Inventario">
        <Toast />

        <!-- Modal Nueva Orden (Componente extraído) -->
        <ProductionOrderModal
            v-model:visible="displayNewOrderModal"
            @saved="onOrderSaved"
        />

        <!-- Contenedor Principal -->
        <div class="min-h-screen p-4 sm:p-6 lg:p-10">
            <div class="max-w-7xl mx-auto">
                
                <!-- Cabecera de Página -->
                <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-[#212121] dark:text-zinc-50">
                            Gestión de Inventario y Producción
                        </h1>
                        <p class="text-zinc-500 dark:text-zinc-400 mt-1">
                            Módulo TPSP
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                        <Button 
                            label="Página pública" 
                            icon="pi pi-external-link" 
                            @click="$inertia.visit(route('tpsp.public.inventory'))" 
                            class="!rounded-xl !bg-white dark:!bg-zinc-800 !text-zinc-700 dark:!text-zinc-200 !border-zinc-200 dark:!border-zinc-700 hover:!bg-zinc-50 dark:hover:!bg-zinc-700 shadow-sm w-full sm:w-auto"
                        />
                        <Button 
                            label="Nueva Orden" 
                            icon="pi pi-plus" 
                            @click="openNewOrderModal" 
                            class="!rounded-xl !text-[var(--primary-text-color)] w-full sm:w-auto"
                        />
                    </div>
                </div>

                <!-- Pestañas Personalizadas (Flotantes) -->
                <div class="mb-6">
                    <!-- Contenedor scrollable para móvil -->
                    <div class="flex overflow-x-auto gap-2 pb-2 hide-scrollbar -mx-4 px-4 sm:mx-0 sm:px-0">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'relative px-5 py-2.5 mt-3 rounded-lg text-md font-medium transition-all duration-300 whitespace-nowrap outline-none',
                                activeTab === tab.id 
                                    ? 'bg-white dark:bg-zinc-800 text-gray-600 dark:text-gray-100 shadow-md ring-1 ring-zinc-200/50 dark:ring-zinc-700/50 transform -translate-y-0.5' 
                                    : 'text-zinc-500 dark:text-zinc-400 bg-transparent hover:bg-zinc-200/50 dark:hover:bg-zinc-800/50 hover:text-zinc-800 dark:hover:text-zinc-200'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Contenido de las Pestañas -->
                <div class="relative min-h-[400px]">
                    <Transition name="fade" mode="out-in">
                        <div :key="activeTab">
                            <!-- NUEVO: Hemos añadido la propiedad :key para obligar a refrescar la vista de Órdenes -->
                            <ProductionOrdersTab v-if="activeTab === 'orders'" :key="ordersRefreshKey" />
                            <ProductsTab v-if="activeTab === 'products'" />
                            <MovementsTab v-if="activeTab === 'movements'" />
                            <FinanceTab v-if="activeTab === 'finance'" />
                        </div>
                    </Transition>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ocultar barra de desplazamiento en el contenedor de pestañas para mantenerlo limpio */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Transición suave al cambiar de pestaña */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(10px);
}

/* Estilos de inputs (se mantienen igual) */
:deep(.p-dropdown), :deep(.p-inputnumber-input), :deep(.p-inputtext) {
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}
:deep(.p-dropdown:hover), :deep(.p-inputnumber-input:hover), :deep(.p-inputtext:hover) {
    border-color: #a1a1aa !important; 
}
.dark :deep(.p-dropdown:hover), .dark :deep(.p-inputnumber-input:hover), .dark :deep(.p-inputtext:hover) {
    border-color: #52525b !important; 
}
:deep(.p-dropdown:focus-within), :deep(.p-inputnumber-input:focus), :deep(.p-inputtext:focus) {
    border-color: #3b82f6 !important; 
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    outline: none;
}
</style>