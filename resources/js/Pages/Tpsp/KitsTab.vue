<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm'; // Importar para confirmación
import Card from 'primevue/card';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dialog from 'primevue/dialog';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog'; // Importar componente de diálogo

const toast = useToast();
const confirm = useConfirm(); // Inicializar servicio de confirmación
const selectedKitId = ref(null);

// --- Manejo de Productos Modificado ---
const allProducts = ref([]);
const allKits = ref([]);
const allComponents = ref([]);
// --- Fin de Modificación ---

const currentComponents = ref([]);
const loadingForm = ref(false);
const loadingTable = ref(true);

const newKitComponent = ref({
    component_product_id: null,
    quantity_required: 1
});

// --- Nuevo: Estado para la tabla de Kits ---
const kitComponentsMap = ref({});
const kitDetailsDialog = ref(false);
const selectedKitDetails = ref({ kit: null, components: [] });
// --- Fin de Nuevo Estado ---


// Carga TODOS los productos (Kits y Materiales)
const fetchProducts = async () => {
    try {
        const response = await axios.get('/tpsp/products');
        allProducts.value = response.data;
        allKits.value = allProducts.value.filter(p => p.is_kit);
        allComponents.value = allProducts.value.filter(p => !p.is_kit);
    } catch (error) {
        console.error("Error fetching products:", error);
    }
};

// Carga los componentes del kit seleccionado (para el formulario)
const fetchKitComponentsForm = async (kitId) => {
    if (!kitId) {
        currentComponents.value = [];
        return;
    }
    loadingForm.value = true;
    try {
        const response = await axios.get(`/tpsp/products/${kitId}/components`);
        // Cargar los datos del producto asociado para el nombre
        currentComponents.value = response.data.map(comp => ({
            ...comp,
            // Asegurarse de que component_product esté cargado
            component_product: comp.component_product || { name: 'Componente no encontrado' } 
        }));
    } catch (error) {
        console.error("Error fetching kit components:", error);
    } finally {
        loadingForm.value = false;
    }
};

// --- Nuevo: Carga los componentes de TODOS los kits para la tabla ---
const fetchAllKitComponents = async () => {
    if (allKits.value.length === 0) {
        loadingTable.value = false;
        return;
    }

    loadingTable.value = true;
    const newMap = {};
    
    try {
        const componentPromises = allKits.value.map(kit => 
            axios.get(`/tpsp/products/${kit.id}/components`)
        );
        
        const results = await Promise.all(componentPromises);
        
        results.forEach((result, index) => {
            const kitId = allKits.value[index].id;
            newMap[kitId] = result.data;
        });
        
        kitComponentsMap.value = newMap;

    } catch (error) {
        console.error("Error fetching all kit components:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo calcular el stock de kits', life: 3000 });
    } finally {
        loadingTable.value = false;
    }
};

// Observa cambios en el kit seleccionado para recargar el *formulario*
watch(selectedKitId, (newId) => {
    fetchKitComponentsForm(newId);
});

// --- Nuevo: Computed Property para la Tabla de Kits ---
const allKitsWithStock = computed(() => {
    return allKits.value.map(kit => {
        const components = kitComponentsMap.value[kit.id];
        
        let calculable_stock = 0;
        let isDataReady = components !== undefined;

        if (isDataReady && components.length > 0) {
            const stockPerComponent = components.map(comp => {
                const componentProduct = allComponents.value.find(p => p.id === comp.component_product_id);
                const componentStock = componentProduct ? componentProduct.stock : 0;
                return Math.floor(componentStock / comp.quantity_required);
            });
            calculable_stock = Math.min(...stockPerComponent);

        } else if (isDataReady && components.length === 0) {
            calculable_stock = 0;
        }

        return {
            ...kit,
            calculable_stock: isDataReady ? calculable_stock : 'Calculando...',
            components: components || []
        };
    });
});
// --- Fin de Computed Property ---


const addKitComponent = async () => {
    if (!selectedKitId.value || !newKitComponent.value.component_product_id) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'Seleccione un kit y un componente', life: 3000 });
        return;
    }
    try {
        await axios.post(`/tpsp/products/${selectedKitId.value}/components`, newKitComponent.value);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Componente agregado al kit', life: 3000 });
        
        newKitComponent.value = { component_product_id: null, quantity_required: 1 };
        fetchKitComponentsForm(selectedKitId.value);
        
        // Recargar los datos de la tabla también
        fetchAllKitComponents(); 

    } catch (error) {
        console.error("Error adding kit component:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo agregar el componente', life: 3000 });
    }
};

// --- INICIO: NUEVAS FUNCIONES ---

/**
 * Actualiza la cantidad de un componente de kit.
 */
const updateKitComponent = (component) => {
    axios.put(`/tpsp/components/${component.id}`, {
        quantity_required: component.quantity_required
    })
    .then(() => {
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Cantidad de componente actualizada', life: 3000 });
        fetchAllKitComponents();
    })
    .catch(error => {
        console.error("Error updating kit component:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cantidad', life: 3000 });
        fetchKitComponentsForm(selectedKitId.value);
    });
};

/**
 * Elimina un componente de un kit.
 */
const deleteKitComponent = (component) => {
    confirm.require({
        message: `¿Está seguro de que desea eliminar el componente "${component.component_product.name}" de este kit?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            axios.delete(`/tpsp/components/${component.id}`)
            .then(() => {
                toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Componente eliminado del kit', life: 3000 });
                fetchKitComponentsForm(selectedKitId.value);
                fetchAllKitComponents();
            })
            .catch(error => {
                console.error("Error deleting kit component:", error);
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el componente', life: 3000 });
            });
        }
    });
};

/**
 * Elimina un kit completo (producto).
 */
const deleteKit = (kit) => {
    confirm.require({
        message: `¿Está seguro de que desea eliminar el kit "${kit.name}"? Esta acción es permanente y borrará su definición de componentes.`,
        header: 'Eliminar Kit Permanentemente',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar Kit',
        rejectLabel: 'Cancelar',
        accept: () => {
            axios.delete(`/tpsp/products/${kit.id}`)
            .then(() => {
                toast.add({ severity: 'success', summary: 'Kit Eliminado', detail: `El kit "${kit.name}" ha sido eliminado.`, life: 3000 });
                
                if (selectedKitId.value === kit.id) {
                    selectedKitId.value = null;
                }
                
                fetchProducts().then(() => {
                    fetchAllKitComponents();
                });
            })
            .catch(error => {
                console.error("Error deleting kit:", error);
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el kit. Verifique que no esté en uso.', life: 3000 });
            });
        }
    });
};

// --- FIN: NUEVAS FUNCIONES ---


// --- Nuevo: Funciones para el Modal de Detalles ---
const openKitDetails = (kitData) => {
    const componentsWithDetails = kitData.components.map(comp => {
        const product = allComponents.value.find(p => p.id === comp.component_product_id);
        return {
            ...comp,
            component_name: product ? product.name : 'Componente no encontrado',
            component_stock: product ? product.stock : 0,
            unit_of_measure: product ? product.unit_of_measure : 'N/A'
        };
    });
    
    selectedKitDetails.value = {
        kit: kitData,
        components: componentsWithDetails
    };
    
    kitDetailsDialog.value = true;
};
// --- Fin de Funciones del Modal ---


// Carga inicial
onMounted(async () => {
    await fetchProducts();
    await fetchAllKitComponents();
});

</script>

<template>
    <!-- Componente de Confirmación (Debe existir uno en la plantilla) -->
    <ConfirmDialog></ConfirmDialog>

    <!-- Formulario de Edición de Kits (Existente) -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 mb-6">
        <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-zinc-100">Configuración de Componentes de Kit</h3>
        
        <div class="p-fluid form-grid">
            <div class="field col-12 flex flex-col">
                <label for="kitProduct" class="dark:text-zinc-300 font-semibold mb-2">1. Seleccionar Kit para Editar Componentes</label>
                <Dropdown id="kitProduct" v-model="selectedKitId" :options="allKits" optionLabel="name" optionValue="id" placeholder="Seleccione un kit para definir" class="dark:bg-zinc-950 dark:border-zinc-700" />
            </div>
        </div>
        
        <div v-if="selectedKitId" class="mt-6 border-t border-gray-100 dark:border-zinc-800 pt-6">
            <h4 class="mb-4 text-lg font-semibold text-gray-800 dark:text-zinc-200">2. Agregar Componentes</h4>
            <div class="flex flex-wrap gap-4 items-end mb-6">
                <div class="flex-1 min-w-[200px] flex flex-col">
                    <label for="kitComponent" class="dark:text-zinc-300 mb-2">Componente</label>
                    <Dropdown id="kitComponent" v-model="newKitComponent.component_product_id" :options="allComponents" optionLabel="name" optionValue="id" placeholder="Seleccione componente" class="dark:bg-zinc-950 dark:border-zinc-700 w-full" />
                </div>
                <div class="w-40 flex flex-col">
                    <label for="kitComponentQty" class="dark:text-zinc-300 mb-2">Cantidad</label>
                    <InputNumber id="kitComponentQty" v-model="newKitComponent.quantity_required" mode="decimal" :min="0.01" :minFractionDigits="2" inputClass="dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100" />
                </div>
                <div class="pb-1">
                    <Button label="Agregar" icon="pi pi-plus" @click="addKitComponent" class="!text-[var(--primary-text-color)]"/>
                </div>
            </div>

            <h4 class="mt-8 mb-4 text-lg font-semibold text-gray-800 dark:text-zinc-200">Componentes Actuales del Kit</h4>
            
            <!-- Tabla de Componentes Actuales (MODIFICADA) -->
            <div class="rounded-xl overflow-hidden border border-gray-100 dark:border-zinc-800">
                <DataTable 
                    v-if="currentComponents.length > 0"
                    :value="currentComponents" 
                    :loading="loadingForm" 
                    dataKey="id"
                    responsiveLayout="scroll"
                    class="zinc-table"
                >
                    <Column field="component_product.name" header="Nombre Componente">
                         <template #body="{ data }"><span class="dark:text-zinc-200">{{ data.component_product.name }}</span></template>
                    </Column>
                    
                    <Column field="quantity_required" header="Cantidad Requerida">
                        <template #body="slotProps">
                            <InputNumber 
                                v-model="slotProps.data.quantity_required" 
                                mode="decimal" 
                                :min="0.01" 
                                :minFractionDigits="2"
                                class="p-inputtext-sm w-32"
                                inputClass="dark:bg-zinc-950 dark:border-zinc-700 dark:text-zinc-100 text-center"
                            />
                        </template>
                    </Column>

                    <Column header="Acciones" :exportable="false" style="min-width:10rem">
                        <template #body="slotProps">
                            <Button 
                                icon="pi pi-check" 
                                class="p-button-rounded p-button-success mr-2 p-button-text" 
                                v-tooltip.top="'Actualizar Cantidad'"
                                @click="updateKitComponent(slotProps.data)" 
                            />
                            <Button 
                                icon="pi pi-trash" 
                                class="p-button-rounded p-button-danger p-button-text" 
                                v-tooltip.top="'Eliminar Componente'"
                                @click="deleteKitComponent(slotProps.data)" 
                            />
                        </template>
                    </Column>
                </DataTable>
                
                <!-- Mensaje si no hay componentes -->
                <div v-else-if="!loadingForm && selectedKitId" class="text-center p-8 bg-gray-50 dark:bg-zinc-950 text-gray-500 dark:text-zinc-500">
                    <i class="pi pi-info-circle text-2xl mb-2"></i>
                    <p>Este kit aún no tiene componentes definidos.</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Nueva: Tabla de Resumen de Kits -->
    <!-- Se agregó la clase 'kit-summary-card' para poder apuntar a ella con CSS -->
    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 p-6 kit-summary-card">
        <h3 class="text-xl font-bold mb-4 text-gray-800 dark:text-zinc-100">Resumen de Kits y Stock Fabricable</h3>

        <!-- Vista de Tabla (Escritorio) - Oculta en pantallas pequeñas -->
        <div class="hidden md:block rounded-xl overflow-hidden border border-gray-100 dark:border-zinc-800">
            <DataTable 
                :value="allKitsWithStock" 
                :loading="loadingTable" 
                responsiveLayout="scroll" 
                :rows="10" 
                :paginator="true"
                class="zinc-table"
            >
                <Column field="image_url" header="Imagen">
                    <template #body="slotProps">
                        <Image 
                            :src="slotProps.data.image_url || 'https://placehold.co/60x60/EEE/31343C?text=Sin+Foto'" 
                            alt="Imagen del kit" 
                            width="50" 
                            height="50" 
                            preview 
                            imageClass="rounded-lg object-cover w-12 h-12 border dark:border-zinc-700"
                        />
                    </template>
                </Column>
                <Column field="name" header="Nombre Kit" :sortable="true">
                     <template #body="{ data }"><span class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.name }}</span></template>
                </Column>
                <Column field="calculable_stock" header="Stock Fabricable" :sortable="true">
                    <template #body="slotProps">
                        <Tag 
                            :severity="slotProps.data.calculable_stock > 0 ? 'success' : 'danger'" 
                            :value="slotProps.data.calculable_stock"
                        ></Tag>
                        <span class="ml-2 text-gray-500 dark:text-zinc-400 text-sm">Kits</span>
                    </template>
                </Column>
                
                <Column header="Acciones" :exportable="false" style="min-width:10rem">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-eye" 
                            class="p-button-rounded p-button-info mr-2 p-button-text" 
                            v-tooltip.top="'Ver Detalles y Componentes'"
                            @click="openKitDetails(slotProps.data)" 
                            :disabled="!slotProps.data.components || slotProps.data.components.length === 0"
                        />
                        <Button 
                            icon="pi pi-trash" 
                            class="p-button-rounded p-button-danger p-button-text" 
                            v-tooltip.top="'Eliminar Kit Permanentemente'"
                            @click="deleteKit(slotProps.data)" 
                        />
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Vista de Tarjetas (Móvil) - Oculta en pantallas medianas y grandes -->
        <div class="md:hidden">
            <!-- Estado de carga -->
            <div v-if="loadingTable" class="text-center p-4 dark:text-zinc-400">
                <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                <p>Cargando kits...</p>
            </div>
            <!-- Estado vacío -->
            <div v-else-if="allKitsWithStock.length === 0" class="text-center p-4 dark:text-zinc-400">
                <p>No se encontraron kits definidos.</p>
            </div>
            <!-- Lista de tarjetas -->
            <!-- Se agregó un contenedor con padding ligero 'px-2 pt-2' para que las tarjetas no peguen a los bordes -->
            <div v-else class="flex flex-col gap-4">
                <div v-for="kit in allKitsWithStock" :key="kit.id" 
                        class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl p-4 shadow-sm flex gap-4">
                    
                    <!-- Imagen -->
                    <div class="flex-shrink-0">
                        <Image 
                            :src="kit.image_url || 'https://placehold.co/80x80/EEE/31343C?text=Sin+Foto'" 
                            alt="Imagen del kit" 
                            width="80" 
                            height="80" 
                            preview 
                            imageClass="rounded-lg object-cover w-20 h-20 border dark:border-zinc-700"
                        />
                    </div>
                    
                    <!-- Detalles del Kit -->
                    <div class="flex-1 flex flex-col justify-between">
                        <div>
                            <span class="font-bold text-gray-800 dark:text-zinc-100 text-lg leading-tight block">{{ kit.name }}</span>
                            <span class="text-xs text-gray-500 dark:text-zinc-500">SKU: {{ kit.sku || 'N/A' }}</span>
                        </div>
                        
                        <div class="mt-2 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-zinc-400 font-medium">Fabricable:</span>
                            <Tag 
                                :severity="kit.calculable_stock > 0 ? 'success' : 'danger'" 
                                :value="kit.calculable_stock"
                            ></Tag>
                        </div>
                        
                        <div class="mt-3 flex gap-2 justify-end">
                            <Button 
                                icon="pi pi-eye" 
                                class="p-button-rounded p-button-info p-button-sm" 
                                outlined
                                @click="openKitDetails(kit)" 
                                :disabled="!kit.components || kit.components.length === 0"
                            />
                            <Button 
                                icon="pi pi-trash" 
                                class="p-button-rounded p-button-danger p-button-sm" 
                                outlined
                                @click="deleteKit(kit)" 
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Nuevo: Modal (Dialog) para Detalles del Kit -->
    <Dialog v-model:visible="kitDetailsDialog" :style="{width: '650px'}" header="Detalles del Kit" :modal="true"
        :pt="{ root: { class: 'dark:bg-zinc-900 dark:border-zinc-700' }, header: { class: 'dark:bg-zinc-900 dark:text-zinc-200' }, content: { class: 'dark:bg-zinc-900' }, footer: { class: 'dark:bg-zinc-900' } }">
        
        <div v-if="selectedKitDetails.kit" class="mb-6 flex align-items-center gap-4 bg-gray-50 dark:bg-zinc-950 p-4 rounded-xl border dark:border-zinc-800">
            <Image 
                :src="selectedKitDetails.kit.image_url || 'https://placehold.co/80x80/EEE/31343C?text=Sin+Foto'" 
                alt="Imagen del kit" 
                width="80" 
                height="80" 
                imageClass="rounded-lg object-cover w-20 h-20 border dark:border-zinc-700"
            />
            <div>
                <h3 class="mt-0 mb-1 font-bold text-xl text-gray-800 dark:text-zinc-100">{{ selectedKitDetails.kit.name }}</h3>
                <Tag 
                    :severity="selectedKitDetails.kit.calculable_stock > 0 ? 'success' : 'danger'" 
                >
                    <span class="font-bold">Stock Fabricable: {{ selectedKitDetails.kit.calculable_stock }}</span>
                </Tag>
            </div>
        </div>

        <h4 class="mb-3 font-semibold text-gray-800 dark:text-zinc-200">Componentes del Kit</h4>
        <div class="rounded-lg overflow-hidden border dark:border-zinc-800">
            <DataTable :value="selectedKitDetails.components" class="zinc-table">
                <Column field="component_name" header="Componente">
                     <template #body="{ data }"><span class="dark:text-zinc-200">{{ data.component_name }}</span></template>
                </Column>
                <Column field="component_stock" header="Stock Actual">
                     <template #body="slotProps">
                        <span class="dark:text-zinc-300">{{ slotProps.data.component_stock }} {{ slotProps.data.unit_of_measure }}</span>
                    </template>
                </Column>
                <Column field="quantity_required" header="Requerido por Kit">
                     <template #body="{ data }"><span class="font-bold dark:text-zinc-200">{{ data.quantity_required }}</span></template>
                </Column>
            </DataTable>
        </div>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" class="p-button-text !text-gray-500 dark:!text-zinc-400" @click="kitDetailsDialog = false"/>
        </template>
    </Dialog>

</template>

<style scoped>
/* Zinc Theme Overrides for PrimeVue DataTable */
:deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #f4f4f5 !important;
    color: #52525b !important;
    border-bottom: 1px solid #e4e4e7;
}
.dark :deep(.zinc-table .p-datatable-thead > tr > th) {
    background-color: #18181b !important; /* zinc-950 */
    color: #a1a1aa !important; /* zinc-400 */
    border-bottom: 1px solid #27272a; /* zinc-800 */
}
:deep(.zinc-table .p-datatable-tbody > tr) {
    background-color: transparent !important;
    color: inherit;
}
:deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #f4f4f5;
}
.dark :deep(.zinc-table .p-datatable-tbody > tr:not(:last-child) > td) {
    border-bottom: 1px solid #27272a;
}
</style>