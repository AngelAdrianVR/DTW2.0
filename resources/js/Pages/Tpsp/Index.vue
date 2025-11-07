<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import Toast from 'primevue/toast';
import AppLayout from '@/Layouts/AppLayout.vue';

// Importa los componentes de las pestañas
import ProductionOrdersTab from './ProductionOrdersTab.vue';
import ProductsTab from './ProductsTab.vue';
import KitsTab from './KitsTab.vue';
import MovementsTab from './MovementsTab.vue';

// Lógica para el modal de "Nueva Orden"
const toast = useToast();
const displayNewOrderModal = ref(false);
const kitProducts = ref([]);
const newOrder = ref({
    product_id: null,
    quantity_requested: 1,
    due_date: null
});

// Carga los productos que son "Kits" para el dropdown
const fetchKitProducts = async () => {
    try {
        // Asumiendo que tu API puede filtrar por `is_kit`
        // Si no, tendrás que cargar todos y filtrar en el frontend
        const response = await axios.get('/tpsp/products', { params: { is_kit: true } });
        kitProducts.value = response.data;
    } catch (error) {
        console.error("Error fetching kit products:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los kits', life: 3000 });
    }
};

const createProductionOrder = async () => {
    try {
        await axios.post('/tpsp/production-orders', newOrder.value);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden de producción creada', life: 3000 });
        displayNewOrderModal.value = false;
        // Aquí podrías emitir un evento para que ProductionOrdersTab recargue
    } catch (error) {
        console.error("Error creating production order:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo crear la orden', life: 3000 });
    }
};

// Carga los kits cuando el componente se monta
onMounted(fetchKitProducts);

</script>

<template>

    <AppLayout title="TPSP - Gestión de Inventario y Producción">
        <Toast />

        <!-- Diálogo (modal) para crear orden de producción -->
        <Dialog header="Crear Nueva Orden de Producción" v-model:visible="displayNewOrderModal" :modal="true" :style="{width: '50vw'}" 
            :breakpoints="{'700px': '90vw'}">
            <div class="p-fluid form-grid">
                <div class="field col-12 flex flex-col">
                    <label for="orderProduct">Producto (Kit)</label>
                    <Dropdown id="orderProduct" v-model="newOrder.product_id" :options="kitProducts" optionLabel="name" optionValue="id" placeholder="Seleccione un kit" />
                </div>
                <div class="field col-6">
                    <label for="orderQuantity">Cantidad a Producir</label>
                    <InputNumber id="orderQuantity" v-model="newOrder.quantity_requested" mode="decimal" :min="1" />
                </div>
                <div class="field col-6 flex flex-col">
                    <label for="orderDueDate">Fecha de entrega</label>
                    <InputText id="orderDueDate" v-model="newOrder.due_date" type="date" />
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" icon="pi pi-times" @click="displayNewOrderModal = false" class="p-button-text" />
                <Button label="Crear Orden" icon="pi pi-check" @click="createProductionOrder" />
            </template>
        </Dialog>

        <!-- Contenedor Principal con Pestañas -->
        <Card>
            <template #title>
                <div class="flex justify-between items-center">
                    <span>Módulo TPSP - Gestión de Inventario y Producción</span>
                </div>
                <div class="flex justify-end space-x-3 mt-3">
                    <Button label="Página publica" icon="pi pi-plus" @click="$inertia.visit(route('tpsp.public.inventory'))" />
                    <Button label="Nueva Orden de Producción" icon="pi pi-plus" @click="displayNewOrderModal = true" />
                </div>
            </template>
            <template #content>
                <TabView>
                    <TabPanel header="Órdenes de Producción">
                        <ProductionOrdersTab />
                    </TabPanel>
                    <TabPanel header="Productos">
                        <ProductsTab />
                    </TabPanel>
                    <TabPanel header="Kits">
                        <KitsTab />
                    </TabPanel>
                    <TabPanel header="Movimientos de Inventario">
                        <MovementsTab />
                    </TabPanel>
                </TabView>
            </template>
        </Card>
    </AppLayout>
</template>

<style scoped>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}
.field.col-12 {
    grid-column: 1 / 3;
}
</style>