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
            // Asegurarse de que component_product esté cargado (parece que ya lo está por tu captura)
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
 * Se llama desde la tabla de "Componentes Actuales".
 */
const updateKitComponent = (component) => {
    // La cantidad se actualiza mediante v-model en el InputNumber
    axios.put(`/tpsp/components/${component.id}`, {
        quantity_required: component.quantity_required
    })
    .then(() => {
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Cantidad de componente actualizada', life: 3000 });
        // Recalcular el stock fabricable de la tabla principal
        fetchAllKitComponents();
    })
    .catch(error => {
        console.error("Error updating kit component:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cantidad', life: 3000 });
        // Recargar los datos del formulario para revertir el cambio visual
        fetchKitComponentsForm(selectedKitId.value);
    });
};

/**
 * Elimina un componente de un kit.
 * Se llama desde la tabla de "Componentes Actuales".
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
                // Recargar datos del formulario
                fetchKitComponentsForm(selectedKitId.value);
                // Recalcular el stock fabricable
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
 * Se llama desde la tabla de "Resumen de Kits".
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
            // Usa la ruta del TpspProductController para eliminar el producto
            axios.delete(`/tpsp/products/${kit.id}`)
            .then(() => {
                toast.add({ severity: 'success', summary: 'Kit Eliminado', detail: `El kit "${kit.name}" ha sido eliminado.`, life: 3000 });
                
                // Si el kit eliminado era el seleccionado, limpiarlo
                if (selectedKitId.value === kit.id) {
                    selectedKitId.value = null;
                }
                
                // Recargar toda la información
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
    <Card>
        <template #title>Configuración de Componentes de Kit</template>
        <template #content>
            <div class="p-fluid form-grid">
                <div class="field col-12 flex flex-col">
                    <label for="kitProduct">1. Seleccionar Kit para Editar Componentes</label>
                    <Dropdown id="kitProduct" v-model="selectedKitId" :options="allKits" optionLabel="name" optionValue="id" placeholder="Seleccione un kit para definir" />
                </div>
            </div>
            
            <div v-if="selectedKitId">
                <hr class="my-4">
                <h4 class="mb-3">2. Agregar Componentes</h4>
                <div class="p-fluid form-grid">
                    <div class="field col-6 flex flex-col">
                        <label for="kitComponent">Componente</label>
                        <Dropdown id="kitComponent" v-model="newKitComponent.component_product_id" :options="allComponents" optionLabel="name" optionValue="id" placeholder="Seleccione componente" />
                    </div>
                    <div class="field col-4">
                        <label for="kitComponentQty" class="mr-5">Cantidad Requerida</label>
                        <InputNumber id="kitComponentQty" v-model="newKitComponent.quantity_required" mode="decimal" :min="0.01" :minFractionDigits="2" />
                    </div>
                    <div class="field col-2" style="align-self: flex-end;">
                        <Button label="Agregar" icon="pi pi-plus" @click="addKitComponent" />
                    </div>
                </div>

                <h4 class="mt-5">Componentes Actuales del Kit</h4>
                
                <!-- Tabla de Componentes Actuales (MODIFICADA) -->
                <DataTable 
                    v-if="currentComponents.length > 0"
                    :value="currentComponents" 
                    :loading="loadingForm" 
                    dataKey="id"
                    responsiveLayout="scroll"
                >
                    <Column field="component_product.name" header="Nombre Componente"></Column>
                    
                    <Column field="quantity_required" header="Cantidad Requerida">
                        <template #body="slotProps">
                            <InputNumber 
                                v-model="slotProps.data.quantity_required" 
                                mode="decimal" 
                                :min="0.01" 
                                :minFractionDigits="2"
                                class="p-inputtext-sm"
                                style="width: 100px;"
                            />
                        </template>
                    </Column>

                    <Column header="Acciones" :exportable="false" style="min-width:10rem">
                        <template #body="slotProps">
                            <Button 
                                icon="pi pi-check" 
                                class="p-button-rounded p-button-success mr-2" 
                                v-tooltip.top="'Actualizar Cantidad'"
                                @click="updateKitComponent(slotProps.data)" 
                            />
                            <Button 
                                icon="pi pi-trash" 
                                class="p-button-rounded p-button-danger" 
                                v-tooltip.top="'Eliminar Componente'"
                                @click="deleteKitComponent(slotProps.data)" 
                            />
                        </template>
                    </Column>
                </DataTable>
                
                <!-- Mensaje si no hay componentes -->
                <div v-else-if="!loadingForm && selectedKitId" class="text-center p-4 border-round bg-gray-100 text-gray-600">
                    Este kit aún no tiene componentes definidos.
                </div>

            </div>
        </template>
    </Card>

    <!-- Nueva: Tabla de Resumen de Kits -->
    <Card class="mt-4">
        <template #title>Resumen de Kits y Stock Fabricable</template>
        <template #content>
            <DataTable 
                :value="allKitsWithStock" 
                :loading="loadingTable" 
                responsiveLayout="scroll" 
                :rows="10" 
                :paginator="true"
            >
                <Column field="image_url" header="Imagen">
                    <template #body="slotProps">
                        <Image 
                            :src="slotProps.data.image_url || 'https://placehold.co/60x60/EEE/31343C?text=Sin+Foto'" 
                            alt="Imagen del kit" 
                            width="60" 
                            height="60" 
                            preview 
                            imageClass="border-round"
                        />
                    </template>
                </Column>
                <Column field="name" header="Nombre Kit" :sortable="true"></Column>
                <Column field="calculable_stock" header="Stock Fabricable" :sortable="true">
                    <template #body="slotProps">
                        <Tag 
                            :severity="slotProps.data.calculable_stock > 0 ? 'success' : 'danger'" 
                            :value="slotProps.data.calculable_stock"
                        ></Tag>
                        <span class="ml-2">Kits</span>
                    </template>
                </Column>
                
                <!-- Columna de Acciones (MODIFICADA) -->
                <Column header="Acciones" :exportable="false" style="min-width:10rem">
                    <template #body="slotProps">
                        <Button 
                            icon="pi pi-eye" 
                            class="p-button-rounded p-button-info mr-2" 
                            v-tooltip.top="'Ver Detalles y Componentes'"
                            @click="openKitDetails(slotProps.data)" 
                            :disabled="!slotProps.data.components || slotProps.data.components.length === 0"
                        />
                        <Button 
                            icon="pi pi-trash" 
                            class="p-button-rounded p-button-danger" 
                            v-tooltip.top="'Eliminar Kit Permanentemente'"
                            @click="deleteKit(slotProps.data)" 
                        />
                    </template>
                </Column>
            </DataTable>
        </template>
    </Card>


    <!-- Nuevo: Modal (Dialog) para Detalles del Kit -->
    <Dialog v-model:visible="kitDetailsDialog" :style="{width: '650px'}" header="Detalles del Kit" :modal="true">
        
        <div v-if="selectedKitDetails.kit" class="mb-4 flex align-items-center">
            <Image 
                :src="selectedKitDetails.kit.image_url || 'https://placehold.co/80x80/EEE/31343C?text=Sin+Foto'" 
                alt="Imagen del kit" 
                width="120" 
                height="120" 
                imageClass="border-round mr-3"
            />
            <div>
                <h3 class="mt-0 mb-1">{{ selectedKitDetails.kit.name }}</h3>
                <Tag 
                    :severity="selectedKitDetails.kit.calculable_stock > 0 ? 'success' : 'danger'" 
                >
                    <strong>Stock Fabricable: {{ selectedKitDetails.kit.calculable_stock }}</strong>
                </Tag>
            </div>
        </div>

        <h4>Componentes del Kit</h4>
        <DataTable :value="selectedKitDetails.components">
            <Column field="component_name" header="Componente"></Column>
            <Column field="component_stock" header="Stock Actual">
                 <template #body="slotProps">
                    {{ slotProps.data.component_stock }} {{ slotProps.data.unit_of_measure }}
                </template>
            </Column>
            <Column field="quantity_required" header="Requerido por Kit"></Column>
        </DataTable>

        <template #footer>
            <Button label="Cerrar" icon="pi pi-times" class="p-button-text" @click="kitDetailsDialog = false"/>
        </template>
    </Dialog>

</template>

<style scoped>
.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1.5rem;
}
.field.col-12 { grid-column: 1 / -1; }
.field.col-6 { grid-column: span 3; }
.field.col-4 { grid-column: span 2; }
.field.col-2 { grid-column: span 1; }

/* Asegurar que los headers de los modales se vean bien */
h3.mt-0.mb-1 {
    margin: 0 0 0.5rem 0 !important;
}

/* Forzar que los botones en la tabla no se separen */
.p-datatable .p-button {
    margin-right: 0.5rem;
}
</style>