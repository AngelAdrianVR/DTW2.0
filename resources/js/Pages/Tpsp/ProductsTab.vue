<script setup>
import { ref, onMounted, computed } from 'vue';
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
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';
import axios from 'axios';

const toast = useToast();
const confirm = useConfirm();
const products = ref([]);
const loading = ref(true);

// Estilos reutilizables tipo "Apple" para evitar fondos cuadrados en Modales
const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' }, 
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

// Opciones
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
const fileUploadRef = ref(null);
const isEditing = ref(false);
const productDialog = ref(false);

// Estado de Modales de Inventario
const stockMovementDialog = ref(false);
const viewMovementsDialog = ref(false);
const selectedProductForStock = ref(null);
const selectedProductMovements = ref([]);
const movementsLoading = ref(false);
const movementData = ref({
    product_id: null,
    quantity: 0,
    type: 'Ajuste',
    notes: '',
});
const movementLoading = ref(false);

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

const filteredProducts = computed(() => {
    return products.value;
});

// Helper de fechas
const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' }).replace('.', '');
};

const getMovementSeverity = (type) => {
    if (['Compra', 'Entrada_Produccion', 'Entrada de material'].includes(type)) return 'success';
    if (['Venta', 'Consumo_Produccion'].includes(type)) return 'danger';
    return 'info';
};

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
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo guardar el producto', life: 5000 });
    }
};

const confirmDeleteProduct = (productData) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar "${productData.name}"?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-300 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
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
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el producto', life: 3000 });
    }
};

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

// --- Modificado: Guarda y actualiza la UI automáticamente ---
const saveStockMovement = async () => {
    if (movementData.value.quantity === 0) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'La cantidad no puede ser cero', life: 3000 });
        return;
    }

    movementLoading.value = true;
    try {
        // Usa la nueva ruta
        const response = await axios.post(`/tpsp/products/${movementData.value.product_id}/adjust-stock`, {
            quantity: movementData.value.quantity,
            type: movementData.value.type,
            notes: movementData.value.notes
        });
        
        // Actualizamos el producto localmente sin recargar toda la tabla
        const index = products.value.findIndex(p => p.id === response.data.id);
        if (index !== -1) {
            products.value[index] = response.data;
        }

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Inventario actualizado correctamente', life: 3000 });
        hideStockModal();

    } catch (error) {
        console.error("Error ajustando stock:", error.response?.data || error);
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo registrar el movimiento', life: 5000 });
    } finally {
        movementLoading.value = false;
    }
};

// --- NUEVO: Función para ver movimientos ---
const openMovementsModal = async (productData) => {
    selectedProductForStock.value = productData;
    viewMovementsDialog.value = true;
    movementsLoading.value = true;
    
    try {
        const res = await axios.get(`/tpsp/products/${productData.id}/movements`);
        selectedProductMovements.value = res.data;
    } catch (e) {
        toast.add({severity: 'error', summary: 'Error', detail: 'No se pudo cargar el historial'});
    } finally {
        movementsLoading.value = false;
    }
};

</script>

<template>
    <div>
        <Toast />
        <ConfirmDialog :pt="{ root: { class: 'dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border-0' }, header: { class: 'bg-white dark:bg-zinc-900 pb-0' }, content: { class: 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300' }, footer: { class: 'bg-white dark:bg-zinc-900 pt-0' } }" />

        <div class="grid">
            <div class="col-12">
                <!-- Header de Sección -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 px-2">
                    <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                        Materiales e Insumos
                    </h2>
                    <Button 
                        label="Nuevo Producto" 
                        icon="pi pi-plus" 
                        @click="openNew" 
                        class="!rounded-xl !bg-zinc-900 dark:!bg-zinc-100 !text-white dark:!text-zinc-900 hover:!bg-zinc-800 dark:hover:!bg-white !border-0 shadow-md shadow-zinc-900/10 w-full sm:w-auto px-5 py-2.5 font-medium transition-all"
                    />
                </div>

                <!-- Vista de Tabla (Escritorio) -->
                <div class="hidden md:block bg-white dark:bg-zinc-900 mt-2 rounded-3xl shadow-sm border border-zinc-100 dark:border-zinc-800 overflow-hidden p-2 sm:p-5">
                    <DataTable :value="filteredProducts" :loading="loading" responsiveLayout="scroll" :rows="10" :paginator="true" class="apple-table" :rowsPerPageOptions="[10, 20, 50]">
                        
                        <Column field="image_url" header="Imagen" style="width: 5rem; text-align: center;">
                            <template #body="slotProps">
                                <Image 
                                    :src="slotProps.data.image_url || 'https://placehold.co/100x100/F4F4F5/A1A1AA?text=Sin+Foto'" 
                                    alt="Imagen" 
                                    width="44" 
                                    height="44" 
                                    preview 
                                    imageClass="rounded-xl object-cover h-11 w-11 shadow-sm border border-zinc-100 dark:border-zinc-800"
                                />
                            </template>
                        </Column>

                        <Column field="name" header="Nombre" :sortable="true">
                            <template #body="{ data }">
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ data.name }}</span>
                            </template>
                        </Column>
                        <Column field="sku" header="SKU">
                            <template #body="{ data }">
                                <span class="font-medium text-zinc-500 dark:text-zinc-400 text-sm">{{ data.sku || '-' }}</span>
                            </template>
                        </Column>
                        <Column field="category" header="Categoría">
                            <template #body="{ data }">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700/50">
                                    {{ data.category }}
                                </span>
                            </template>
                        </Column>
                        <Column field="stock" header="Stock Actual" style="min-width: 100px;">
                            <template #body="slotProps">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-bold text-base" :class="slotProps.data.stock > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                        {{ slotProps.data.stock }}
                                    </span> 
                                    <span class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500">{{ slotProps.data.unit_of_measure }}</span>
                                </div>
                            </template>
                        </Column>
                        
                        <!-- Acciones Modernas -->
                        <Column header="Acciones" :exportable="false" style="min-width:16rem" bodyStyle="text-align: center; overflow: visible;">
                            <template #body="slotProps">
                                <div class="flex gap-2 justify-center">
                                    <!-- Botón Historial (NUEVO) -->
                                    <Button 
                                        icon="pi pi-history" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-600 dark:!text-indigo-400 hover:!bg-indigo-100 dark:hover:!bg-indigo-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Ver Historial de Movimientos'"
                                        @click="openMovementsModal(slotProps.data)" 
                                    />
                                    <Button 
                                        icon="pi pi-arrows-h" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-blue-50 dark:!bg-blue-900/30 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Ajustar Stock'"
                                        @click="openStockModal(slotProps.data)" 
                                    />
                                    <Button 
                                        icon="pi pi-pencil" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-amber-50 dark:!bg-amber-900/30 !text-amber-600 dark:!text-amber-400 hover:!bg-amber-100 dark:hover:!bg-amber-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Editar'"
                                        @click="openEditModal(slotProps.data)" 
                                    />
                                    <Button 
                                        icon="pi pi-trash" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-red-50 dark:!bg-red-900/30 !text-red-600 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Eliminar'"
                                        @click="confirmDeleteProduct(slotProps.data)" 
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Vista de Tarjetas (Móvil) -->
                <div class="md:hidden mt-2">
                    <div v-if="loading" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-3">
                        <i class="pi pi-spin pi-spinner text-3xl"></i>
                        <p>Cargando productos...</p>
                    </div>
                    <div v-else-if="filteredProducts.length === 0" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-2">
                        <i class="pi pi-inbox text-3xl"></i>
                        <p>No se encontraron productos.</p>
                    </div>
                    <div v-else class="flex flex-col gap-4">
                        <div v-for="product in filteredProducts" :key="product.id" class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl p-4 shadow-sm flex flex-col gap-4">
                            
                            <!-- Header Info -->
                            <div class="flex gap-4 items-center">
                                <Image 
                                    :src="product.image_url || 'https://placehold.co/120x120/F4F4F5/A1A1AA?text=Sin+Foto'" 
                                    alt="Imagen" 
                                    width="64" 
                                    height="64" 
                                    preview 
                                    imageClass="rounded-xl object-cover w-16 h-16 shadow-sm border border-zinc-100 dark:border-zinc-800"
                                />
                                <div class="flex-1">
                                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 text-lg leading-tight">{{ product.name }}</h3>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="text-xs font-medium text-zinc-500">{{ product.sku || 'S/N' }}</span>
                                        <span class="text-[0.65rem] uppercase tracking-wider font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/50">{{ product.category }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Separador -->
                            <div class="h-px w-full bg-zinc-100 dark:bg-zinc-800/50"></div>

                            <!-- Inferior: Stock y Acciones -->
                            <div class="flex justify-between items-center">
                                <div class="flex flex-col bg-zinc-50 dark:bg-zinc-950 px-3 py-1.5 rounded-lg border border-zinc-100 dark:border-zinc-800">
                                    <span class="text-[0.65rem] uppercase tracking-wider font-semibold text-zinc-400 mb-0.5">Stock Disponible</span>
                                    <span class="font-bold text-lg text-zinc-800 dark:text-zinc-200 leading-none">
                                        {{ product.stock }} <span class="text-xs font-medium text-zinc-500 uppercase">{{ product.unit_of_measure }}</span>
                                    </span>
                                </div>
                                
                                <div class="flex gap-2">
                                    <Button 
                                        icon="pi pi-history" 
                                        class="!rounded-xl !w-10 !h-10 !p-0 !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-600 dark:!text-indigo-400 hover:!bg-indigo-100 dark:hover:!bg-indigo-900/50 !border-0 transition-colors shadow-none" 
                                        @click="openMovementsModal(product)" 
                                    />
                                    <Button 
                                        icon="pi pi-arrows-h" 
                                        class="!rounded-xl !w-10 !h-10 !p-0 !bg-blue-50 dark:!bg-blue-900/30 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 transition-colors shadow-none" 
                                        @click="openStockModal(product)" 
                                    />
                                    <Button 
                                        icon="pi pi-pencil" 
                                        class="!rounded-xl !w-10 !h-10 !p-0 !bg-amber-50 dark:!bg-amber-900/30 !text-amber-600 dark:!text-amber-400 hover:!bg-amber-100 dark:hover:!bg-amber-900/50 !border-0 transition-colors shadow-none" 
                                        @click="openEditModal(product)" 
                                    />
                                    <Button 
                                        icon="pi pi-trash" 
                                        class="!rounded-xl !w-10 !h-10 !p-0 !bg-red-50 dark:!bg-red-900/30 !text-red-600 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 transition-colors shadow-none" 
                                        @click="confirmDeleteProduct(product)" 
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal (Dialog) para Crear y Editar Producto -->
        <Dialog 
            v-model:visible="productDialog" 
            :style="{width: '100%', maxWidth: '40rem', margin: '1rem'}" 
            :header="isEditing ? 'Editar Producto' : 'Nuevo Producto'" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-3">
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="productName" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Nombre del Producto</label>
                    <InputText id="productName" v-model.trim="product.name" required autofocus class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="productSku" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">SKU (Opcional)</label>
                    <InputText id="productSku" v-model.trim="product.sku" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="productCategory" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Categoría</label>
                    <Dropdown id="productCategory" v-model="product.category" :options="productCategories" placeholder="Seleccione categoría" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm" />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="productUnit" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Unidad de Medida</label>
                    <Dropdown id="productUnit" v-model="product.unit_of_measure" :options="unitsOfMeasure" placeholder="Seleccione unidad" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm" />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="productStock" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Stock Inicial</label>
                    <InputNumber id="productStock" v-model="product.stock" mode="decimal" class="w-full" inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" />
                </div>

                <div class="flex items-center gap-3 pt-2 md:col-span-2">
                    <InputSwitch v-model="product.is_kit" inputId="productIsKit" />
                    <label for="productIsKit" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 cursor-pointer">Marcar como Kit Terminado</label>
                </div>

                <div class="flex flex-col gap-2 md:col-span-2 mt-2">
                    <label for="productImage" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Imagen del Producto</label>
                    <FileUpload 
                        ref="fileUploadRef" 
                        name="image" 
                        :auto="false" 
                        :multiple="false" 
                        accept="image/*" 
                        :maxFileSize="2000000"
                        chooseLabel="Elegir Archivo"
                        uploadLabel="Subir"
                        cancelLabel="Cancelar"
                        :customUpload="true" 
                        @uploader="saveProduct" 
                        class="apple-fileupload"
                        :pt="{ root: { class: 'w-full' }, buttonbar: { class: 'hidden' }, content: { class: '!p-0 !border-0 bg-transparent' } }"
                    >
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-950/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors cursor-pointer" @click="$refs.fileUploadRef.choose()">
                                <i class="pi pi-image text-3xl mb-3 text-zinc-400"></i>
                                <p class="mb-0 font-medium text-sm">Arrastra una imagen o haz clic para subir</p>
                                <p class="text-xs text-zinc-400 mt-1">PNG, JPG, GIF hasta 2MB</p>
                                <Image v-if="isEditing && product.image_url" :src="product.image_url" alt="Imagen actual" width="80" class="mt-4 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" />
                            </div>
                        </template>
                    </FileUpload>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="hideDialog" class="!px-5 !py-2.5 !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors mt-4" />
                <Button :label="isEditing ? 'Actualizar' : 'Guardar Producto'" @click="saveProduct" class="!px-5 !py-2.5 !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all mt-4" />
            </template>
        </Dialog>


        <!-- Modal (Dialog) para Ajustar Inventario -->
        <Dialog 
            v-model:visible="stockMovementDialog" 
            :style="{width: '100%', maxWidth: '28rem', margin: '1rem'}" 
            header="Ajuste de Inventario" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2">
                <!-- Tarjeta de Producto -->
                <div v-if="selectedProductForStock" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
                    <div class="bg-white dark:bg-zinc-900 px-3 pt-3 pb-2 rounded-xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
                        <i class="pi pi-box text-zinc-400" style="font-size: 18px;"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ selectedProductForStock.name }}</span>
                        <span class="text-md text-zinc-500">Stock Actual: <strong class="text-zinc-700 dark:text-zinc-300">{{ selectedProductForStock.stock }} {{ selectedProductForStock.unit_of_measure }}</strong></span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="movementQuantity" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Ajustar</label>
                    <InputNumber 
                        id="movementQuantity" 
                        v-model="movementData.quantity" 
                        mode="decimal"
                        :allowEmpty="false"
                        showButtons
                        class="w-full"
                        inputClass="!w-full !rounded-xl !bg-zinc-50 dark:!bg-zinc-950 dark:!border-zinc-700 dark:!text-zinc-100 p-3 text-center text-lg font-medium"
                        :pt="{ 
                            incrementButton: { class: '!text-zinc-600 dark:!text-zinc-300 hover:!bg-transparent' },
                            decrementButton: { class: '!text-zinc-600 dark:!text-zinc-300 hover:!bg-transparent' }
                        }"
                    />
                    <div class="flex justify-between items-center text-[0.90rem] mt-1 px-1">
                        <span class="text-red-500/80 bg-red-50 dark:bg-red-900/10 px-2 py-0.5 rounded-md font-medium"><i class="pi pi-minus mr-1" style="font-size: 0.8rem "></i>Negativo = Salida</span>
                        <span class="text-emerald-600/80 bg-emerald-50 dark:bg-emerald-900/10 px-2 py-0.5 rounded-md font-medium"><i class="pi pi-plus mr-1" style="font-size: 0.8rem;"></i>Positivo = Entrada</span>
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <label for="movementType" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Motivo / Tipo de Movimiento</label>
                    <Dropdown 
                        id="movementType" 
                        v-model="movementData.type" 
                        :options="movementTypes" 
                        placeholder="Seleccione un motivo" 
                        class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm" 
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="movementNotes" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Notas u Observaciones <span class="text-zinc-400 font-normal">(Opcional)</span></label>
                    <Textarea 
                        id="movementNotes" 
                        v-model="movementData.notes" 
                        rows="3" 
                        class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 resize-none" 
                        placeholder="Escribe algún detalle de este ajuste..."
                    />
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="hideStockModal" class="!px-5 !py-2.5 !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors mt-4" />
                <Button 
                    label="Confirmar Ajuste" 
                    @click="saveStockMovement" 
                    :loading="movementLoading"
                    class="!px-5 !py-2.5 !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all mt-4" 
                />
            </template>
        </Dialog>


        <!-- NUEVO: Modal (Dialog) para Historial de Movimientos -->
        <Dialog 
            v-model:visible="viewMovementsDialog" 
            :style="{width: '100%', maxWidth: '48rem', margin: '1rem'}" 
            header="Historial de Movimientos" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2">
                <!-- Tarjeta Info -->
                <div v-if="selectedProductForStock" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 mb-2">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                        <i class="pi pi-history text-indigo-500 dark:text-indigo-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ selectedProductForStock.name }}</span>
                        <span class="text-sm text-zinc-500">
                            Últimos registros de stock para este producto
                        </span>
                    </div>
                </div>

                <!-- Tabla de Historial -->
                <div class="border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <DataTable :value="selectedProductMovements" :loading="movementsLoading" class="apple-table" :paginator="true" :rows="5" responsiveLayout="scroll">
                        <Column field="created_at" header="Fecha" style="width: 25%">
                            <template #body="{ data }">
                                <span class="text-sm text-zinc-600 dark:text-zinc-300 whitespace-nowrap">{{ formatDateTime(data.created_at) }}</span>
                            </template>
                        </Column>
                        <Column field="type" header="Motivo" style="width: 25%">
                            <template #body="{ data }">
                                <Tag :value="data.type" :severity="getMovementSeverity(data.type)" class="!rounded-md text-xs tracking-wider" />
                            </template>
                        </Column>
                        <Column field="quantity" header="Cantidad" style="width: 15%">
                            <template #body="{ data }">
                                <span class="font-bold text-sm" :class="data.quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500'">
                                    {{ data.quantity > 0 ? '+' : '' }}{{ data.quantity }}
                                </span>
                            </template>
                        </Column>
                        <Column field="notes" header="Notas" style="width: 35%">
                            <template #body="{ data }">
                                <span v-if="data.notes" class="text-sm text-zinc-500 dark:text-zinc-400">{{ data.notes }}</span>
                                <span v-else class="text-xs text-zinc-300 dark:text-zinc-700 italic">Sin comentarios</span>
                            </template>
                        </Column>
                        <template #empty>
                            <div class="text-center p-6 text-zinc-500">
                                <i class="pi pi-inbox text-2xl mb-2 block"></i>
                                <span class="text-sm">No hay movimientos registrados.</span>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
            
            <template #footer>
                <Button label="Cerrar Historial" @click="viewMovementsDialog = false" class="!px-5 !py-2.5 !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 hover:!bg-zinc-200 dark:hover:!bg-zinc-700 !text-zinc-800 dark:!text-zinc-200 !border-0 font-medium transition-colors mt-4" />
            </template>
        </Dialog>

    </div>
</template>

<style scoped>
/* Estilos minimalistas "Apple" para DataTable */
:deep(.apple-table .p-datatable-thead > tr > th) {
    background-color: transparent !important;
    color: #71717a !important; 
    font-weight: 600;
    font-size: 0.80rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid #f4f4f5; 
    padding: 1rem 0.75rem;
}
.dark :deep(.apple-table .p-datatable-thead > tr > th) {
    border-bottom-color: #27272a; 
    color: #a1a1aa !important; 
}

:deep(.apple-table .p-datatable-tbody > tr) {
    background-color: transparent !important;
    transition: background-color 0.2s;
}
:deep(.apple-table .p-datatable-tbody > tr:hover) {
    background-color: transparent !important; 
}
.dark :deep(.apple-table .p-datatable-tbody > tr:hover) {
    background-color: #18181b !important; 
}

:deep(.apple-table .p-datatable-tbody > tr > td) {
    border-bottom: 1px solid #f4f4f5;
    padding: 1rem 0.75rem;
    color: #3f3f46; 
    font-size: 0.95rem;
}
.dark :deep(.apple-table .p-datatable-tbody > tr > td) {
    border-bottom-color: #27272a;
    color: #d4d4d8; 
}

/* Efectos en Inputs Genéricos */
:deep(.p-dropdown), :deep(.p-inputtext), :deep(.p-textarea) {
    font-family: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}
:deep(.p-dropdown:hover), :deep(.p-inputtext:hover), :deep(.p-textarea:hover) {
    border-color: #a1a1aa !important; 
}
.dark :deep(.p-dropdown:hover), .dark :deep(.p-inputtext:hover), .dark :deep(.p-textarea:hover) {
    border-color: #52525b !important; 
}
:deep(.p-dropdown:focus-within), :deep(.p-inputtext:focus), :deep(.p-textarea:focus) {
    border-color: #5d5dba !important; 
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    outline: none;
}
:deep(.p-inputnumber-input:focus) {
    box-shadow: none !important;
    border-color: #5d5dba !important;
}
.dark :deep(.p-inputnumber-input:focus) {
    border-color: #3f3f46 !important;
}
</style>