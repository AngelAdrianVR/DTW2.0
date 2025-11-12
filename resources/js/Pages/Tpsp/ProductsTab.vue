<script setup>
import { ref, onMounted, computed } from 'vue'; // Import 'computed'
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import FileUpload from 'primevue/fileupload';
import Dialog from 'primevue/dialog';
import ConfirmDialog from 'primevue/confirmdialog';
import Image from 'primevue/image';
import Textarea from 'primevue/textarea'; // Para las notas del movimiento

const toast = useToast();
const confirm = useConfirm();
const products = ref([]);
const loading = ref(true);

// Opciones de tus migraciones
const productCategories = ref(['Material', 'Insumo', 'Empaque', 'Kit Terminado']);
const unitsOfMeasure = ref(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro']);
const movementTypes = ref(['Ajuste', 'Compra', 'Venta', 'Entrada_Produccion', 'Consumo_Produccion', 'Entrada de material']);

const getFreshProduct = () => ({
    id: null,
    name: '',
    sku: '',
    category: null,
    unit_of_measure: 'Pieza',
    stock: 0,
    is_kit: false,
    image: null,
    image_url: null,
});

const product = ref(getFreshProduct());
const fileUploadRef = ref(null); // Referencia al componente FileUpload
const isEditing = ref(false);
const productDialog = ref(false); // Controla el modal de producto

// --- Nuevo: Estado para Modal de Movimiento de Stock ---
const stockMovementDialog = ref(false);
const selectedProductForStock = ref(null);
const movementData = ref({
    product_id: null,
    quantity: 0,
    type: 'Ajuste',
    notes: '',
    reference_type: 'App\Models\tpspProduct',
});
const movementLoading = ref(false);
// --- Fin de Nuevo Estado ---

const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/tpsp/products');
        products.value = response.data;
    } catch (error) {
        console.error("Error fetching products:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los productos', life: 3000 });
    } finally {
        loading.value = false;
    }
};

onMounted(fetchProducts);

// --- Nuevo: Computed property para filtrar la tabla ---
const filteredProducts = computed(() => {
    // return products.value.filter(p => !p.is_kit); //no mostrar kits
    return products.value;
});

// --- Funciones del CRUD (sin cambios) ---

const openNew = () => {
    product.value = getFreshProduct();
    isEditing.value = false;
    productDialog.value = true;
};

const openEditModal = (productData) => {
    product.value = { ...productData };
    isEditing.value = true;
    productDialog.value = true;
};

const hideDialog = () => {
    productDialog.value = false;
    product.value = getFreshProduct();
    if (fileUploadRef.value) {
        fileUploadRef.value.clear();
    }
};

const saveProduct = async () => {
    const formData = new FormData();
    formData.append('name', product.value.name);
    formData.append('sku', product.value.sku || '');
    formData.append('category', product.value.category);
    formData.append('unit_of_measure', product.value.unit_of_measure);
    formData.append('stock', product.value.stock);
    formData.append('is_kit', product.value.is_kit ? 1 : 0);

    if (fileUploadRef.value && fileUploadRef.value.files.length > 0) {
        formData.append('image', fileUploadRef.value.files[0]);
    }

    try {
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            const response = await axios.post(`/tpsp/products/${product.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const index = products.value.findIndex(p => p.id === product.value.id);
            products.value[index] = response.data;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto actualizado', life: 3000 });

        } else {
            const response = await axios.post('/tpsp/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            products.value.unshift(response.data);
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto agregado', life: 3000 });
        }
        hideDialog();
    } catch (error) {
        console.error("Error saving product:", error.response?.data || error);
        let errors = error.response?.data?.errors;
        let detail = 'No se pudo guardar el producto.';
        if (errors) {
            detail = Object.values(errors).map(e => e.join(' ')).join(' ');
        }
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 5000 });
    }
};

const confirmDeleteProduct = (productData) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar "${productData.name}"?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await deleteProduct(productData);
        },
    });
};

const deleteProduct = async (productData) => {
    try {
        await axios.delete(`/tpsp/products/${productData.id}`);
        products.value = products.value.filter(p => p.id !== productData.id);
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto eliminado', life: 3000 });
    } catch (error) {
        console.error("Error deleting product:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el producto', life: 3000 });
    }
};

// --- Nuevas Funciones para Movimiento de Stock ---

const openStockModal = (productData) => {
    selectedProductForStock.value = productData;
    movementData.value = {
        product_id: productData.id,
        quantity: 0,
        type: 'Ajuste',
        notes: ''
    };
    stockMovementDialog.value = true;
};

const hideStockModal = () => {
    stockMovementDialog.value = false;
    selectedProductForStock.value = null;
    movementLoading.value = false;
};

const saveStockMovement = async () => {
    if (movementData.value.quantity === 0) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'La cantidad no puede ser cero', life: 3000 });
        return;
    }

    movementLoading.value = true;
    try {
        await axios.post('/tpsp/inventory-movements', movementData.value);
        
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Movimiento de inventario registrado', life: 3000 });
        
        await fetchProducts(); 
        
        hideStockModal();

    } catch (error) {
        console.error("Error saving stock movement:", error.response?.data || error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo registrar el movimiento', life: 5000 });
    } finally {
        movementLoading.value = false;
    }
};

</script>

<template>
    <Toast />
    <ConfirmDialog />

    <div class="grid">
        <div class="col-12">
            <Card>
                <template #title>
                    <div class="flex justify-between items-center">
                        Gestión de Materiales e Insumos
                        <Button label="Nuevo Producto" icon="pi pi-plus" @click="openNew" />
                    </div>
                </template>
                <template #content>
                    <!-- Vista de Tabla (Escritorio) - Oculta en pantallas pequeñas -->
                    <div class="hidden md:block">
                        <DataTable :value="filteredProducts" :loading="loading" responsiveLayout="scroll" :rows="10" :paginator="true">
                            
                            <Column field="image_url" header="Imagen">
                                <template #body="slotProps">
                                    <Image 
                                        :src="slotProps.data.image_url || 'https://placehold.co/60x60/EEE/31343C?text=Sin+Foto'" 
                                        alt="Imagen del producto" 
                                        width="100" 
                                        height="100" 
                                        preview 
                                        imageClass="border-round"
                                    />
                                </template>
                            </Column>

                            <Column field="name" header="Nombre" :sortable="true"></Column>
                            <Column field="sku" header="SKU"></Column>
                            <Column field="category" header="Categoría"></Column>
                            <Column field="stock" header="Stock Actual">
                                <template #body="slotProps">
                                    {{ slotProps.data.stock }} {{ slotProps.data.unit_of_measure }}
                                </template>
                            </Column>
                            
                            <Column header="Acciones" :exportable="false" style="min-width:16rem">
                                <template #body="slotProps">
                                    <Button 
                                        icon="pi pi-arrows-h" 
                                        class="p-button-rounded p-button-info mr-2" 
                                        v-tooltip.top="'Ajustar Stock'"
                                        @click="openStockModal(slotProps.data)" 
                                    />
                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-success mr-2" @click="openEditModal(slotProps.data)" />
                                    <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeleteProduct(slotProps.data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <!-- Vista de Tarjetas (Móvil) - Oculta en pantallas medianas y grandes -->
                    <div class="md:hidden">
                        <!-- Estado de carga -->
                        <div v-if="loading" class="text-center p-4">
                            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
                            <p>Cargando productos...</p>
                        </div>
                        <!-- Estado vacío -->
                        <div v-else-if="filteredProducts.length === 0" class="text-center p-4">
                            <p>No se encontraron productos.</p>
                        </div>
                        <!-- Lista de tarjetas -->
                        <div v-else>
                            <div v-for="product in filteredProducts" :key="product.id" class="product-card bg-gray-100 dark:bg-gray-800">
                                
                                <!-- Imagen -->
                                <div class="product-image-wrapper">
                                    <Image 
                                        :src="product.image_url || 'https://placehold.co/80x80/EEE/31343C?text=Sin+Foto'" 
                                        alt="Imagen del producto" 
                                        width="80" 
                                        height="80" 
                                        preview 
                                        imageClass="border-round"
                                    />
                                </div>
                                
                                <!-- Detalles del Producto -->
                                <div class="product-details">
                                    <div class="product-info">
                                        <span class="product-name text-gray-500 dark:text-gray-300">{{ product.name }}</span>
                                        <span class="product-sku">SKU: {{ product.sku || 'N/A' }}</span>
                                        <span class="product-category">{{ product.category }}</span>
                                    </div>
                                    
                                    <div class="product-stock">
                                        <span class="stock-label">Stock:</span>
                                        <span class="stock-value">{{ product.stock }} {{ product.unit_of_measure }}</span>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <Button 
                                            icon="pi pi-arrows-h" 
                                            class="p-button-rounded p-button-info" 
                                            v-tooltip.top="'Ajustar Stock'"
                                            @click="openStockModal(product)" 
                                        />
                                        <Button icon="pi pi-pencil" class="p-button-rounded p-button-success" @click="openEditModal(product)" />
                                        <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeleteProduct(product)" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </template>
            </Card>
        </div>
    </div>

    <!-- Modal (Dialog) para Crear y Editar Producto (Sin cambios) -->
    <Dialog v-model:visible="productDialog" :style="{width: '600px'}" :header="isEditing ? 'Editar Producto' : 'Nuevo Producto'" :modal="true" class="p-fluid">
        
        <div class="grid">
            <div class="field col-12 md:col-7 flex flex-col">
                <label for="productName">Nombre</label>
                <InputText id="productName" v-model.trim="product.name" required autofocus />
            </div>
            <div class="field col-12 md:col-7 flex flex-col">
                <label for="productSku">SKU</label>
                <InputText id="productSku" v-model.trim="product.sku" />
            </div>
            <div class="field col-12 md:col-7 flex flex-col">
                <label for="productCategory">Categoría</label>
                <Dropdown id="productCategory" v-model="product.category" :options="productCategories" placeholder="Seleccione categoría" />
            </div>
            <div class="field col-12 md:col-7 flex flex-col">
                <label for="productUnit">Unidad de Medida</label>
                <Dropdown id="productUnit" v-model="product.unit_of_measure" :options="unitsOfMeasure" placeholder="Seleccione unidad" />
            </div>
            <div class="field col-12 md:col-7 flex flex-col">
                <label for="productStock">Stock Inicial</label>
                <InputNumber id="productStock" v-model="product.stock" mode="decimal" />
            </div>
            <div class="field col-12 md:col-7 flex align-items-center pt-4">
                <InputSwitch v-model="product.is_kit" inputId="productIsKit" />
                <label for="productIsKit" class="ml-2">¿Es un Kit Terminado?</label>
            </div>
        </div>

        <div class="field col-12">
            <label for="productImage">Imagen del Producto</label>
            <FileUpload 
                ref="fileUploadRef" 
                name="image" 
                :auto="false" 
                :multiple="false" 
                accept="image/*" 
                :maxFileSize="2000000"
                chooseLabel="Seleccionar"
                uploadLabel="Subir (se hace al guardar)"
                cancelLabel="Quitar"
                :customUpload="true" 
                @uploader="saveProduct" 
            >
                <template #empty>
                    <div class="flex align-items-center flex-column">
                        <i class="pi pi-image mt-3 p-5" style="font-size: 5rem; border-radius: 8px; border: 1px dashed var(--surface-d);"></i>
                        <p class="mt-4 mb-0">Arrastra una imagen aquí o haz clic.</p>
                        <Image v-if="isEditing && product.image_url" :src="product.image_url" alt="Imagen actual" width="150" class="mt-2" />
                    </div>
                </template>
            </FileUpload>
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="hideDialog"/>
            <Button :label="isEditing ? 'Actualizar' : 'Guardar'" icon="pi pi-check" @click="saveProduct" />
        </template>
    </Dialog>


    <!-- Nuevo: Modal (Dialog) para Movimiento de Stock -->
    <Dialog v-model:visible="stockMovementDialog" :style="{width: '450px'}" header="Ajustar Stock de Inventario" :modal="true" class="p-fluid">
        
        <div v-if="selectedProductForStock" class="mb-3">
            <strong>Producto:</strong> {{ selectedProductForStock.name }} <br/>
            <strong>Stock Actual:</strong> {{ selectedProductForStock.stock }} {{ selectedProductForStock.unit_of_measure }}
        </div>
        
        <div class="field">
            <label for="movementQuantity">Cantidad a Mover</label>
            <InputNumber 
                id="movementQuantity" 
                v-model="movementData.quantity" 
                mode="decimal"
                :allowEmpty="false"
                showButtons
            />
            <small>Usa números positivos (ej. <strong>10</strong>) para agregar stock (compras, ajustes) y números negativos (ej. <strong>-5</strong>) para quitar (mermas, ventas manuales).</small>
        </div>

        <div class="field">
            <label for="movementType">Tipo de Movimiento</label>
            <Dropdown id="movementType" v-model="movementData.type" :options="movementTypes" placeholder="Seleccione un tipo" />
        </div>

        <div class="field">
            <label for="movementNotes">Notas (Opcional)</label>
            <Textarea id="movementNotes" v-model="movementData.notes" rows="3" />
        </div>

        <template #footer>
            <Button label="Cancelar" icon="pi pi-times" class="p-button-text" @click="hideStockModal"/>
            <Button 
                label="Registrar Movimiento" 
                icon="pi pi-check" 
                @click="saveStockMovement" 
                :loading="movementLoading"
            />
        </template>
    </Dialog>

</template>

<style scoped>
.formgrid.grid {
    margin-top: 1rem;
}
.field {
    margin-bottom: 1rem;
}

/* --- ESTILOS PARA TARJETAS DE PRODUCTO (MÓVIL) --- */

.product-card {
    display: flex;
    gap: 0.7rem; /* Espacio entre imagen y detalles */
    padding: 1rem;
    margin-bottom: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.product-image-wrapper {
    flex-shrink: 0; /* Evita que la imagen se encoja */
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-details {
    flex: 1; /* Ocupa el espacio restante */
    display: flex;
    flex-direction: column;
    justify-content: space-between; /* Distribuye el contenido verticalmente */
}

.product-info {
    display: flex;
    flex-direction: column;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 700;
}

.product-sku,
.product-category {
    font-size: 0.875rem;
    color: #64748b; /* slate-500 */
}

.product-stock {
    margin-top: 0.75rem;
}

.stock-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569; /* slate-600 */
}

.stock-value {
    font-size: 1rem;
    font-weight: 700;
    margin-left: 0.5rem;
}

.product-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    border-top: 1px solid #f1f5f9; /* slate-100 */
    padding-top: 0.75rem;
}

/* Ajuste para que los botones de acciones en móvil no sean tan grandes */
.product-actions :deep(.p-button) {
    height: 2.5rem;
    width: 2.5rem;
}

</style>