<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
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
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0 w-full mx-2 sm:mx-0' }, 
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

// Opciones
const productCategories = ref(['Material', 'Insumo', 'Empaque', 'Kit Terminado', 'Corte', 'Doblado']);
const unitsOfMeasure = ref(['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo','Metro','Rollo','Litro']);
const movementTypes = ref(['Ajuste', 'Compra', 'Venta', 'Entrada_Produccion', 'Consumo_Produccion', 'Entrada de material']);

const getFreshProduct = () => ({
    id: null,
    name: '',
    sku: '',
    category: 'Insumo',
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
const movementData = ref({ product_id: null, quantity: 0, type: 'Ajuste', notes: '' });
const movementLoading = ref(false);

// --- ESTADO Y FUNCIONES PARA COMPONENTES / RECETAS (KITS MERGE) ---
const kitComponentsMap = ref({});
const kitComponentsDialog = ref(false);
const selectedKitDetails = ref({ kit: null, components: [] });
const loadingComponentsForm = ref(false);
const newKitComponent = ref({ component_product_id: null, quantity_required: 1 });

// Automarcar como compuesto si la categoría lo sugiere
watch(() => product.value.category, (newVal) => {
    if (newVal === 'Kit Terminado' || newVal === 'Corte' || newVal === 'Doblado') {
        product.value.is_kit = true;
    }
});

// Computed: Productos disponibles para ser componentes (excluyendo el kit actual)
const availableComponents = computed(() => {
    return products.value.filter(p => selectedKitDetails.value.kit && p.id !== selectedKitDetails.value.kit.id);
});

// Cargar Componentes de todos los kits para la vista general
const fetchAllKitComponents = async () => {
    const kits = products.value.filter(p => p.is_kit);
    if (kits.length === 0) return;

    const newMap = {};
    try {
        const promises = kits.map(kit => axios.get(`/tpsp/products/${kit.id}/components`));
        const results = await Promise.all(promises);
        
        results.forEach((result, index) => {
            const kitId = kits[index].id;
            newMap[kitId] = result.data;
        });
        
        kitComponentsMap.value = newMap;
    } catch (error) {
        console.error("Error al cargar componentes globales:", error);
    }
};

const fetchProducts = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/tpsp/products');
        products.value = response.data;
        await fetchAllKitComponents();
    } catch (error) {
        console.error("Error fetching products:", error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los productos', life: 3000 });
    } finally {
        loading.value = false;
    }
};

onMounted(fetchProducts);

// Computed: Inyectar el stock fabricable a la lista de productos
const filteredProducts = computed(() => {
    return products.value.map(p => {
        if (p.is_kit) {
            const components = kitComponentsMap.value[p.id];
            let calculable_stock = 0;
            let isDataReady = components !== undefined;

            if (isDataReady && components.length > 0) {
                const stockPerComponent = components.map(comp => {
                    const compProduct = products.value.find(prod => prod.id === comp.component_product_id);
                    const currentStock = compProduct ? compProduct.stock : 0;
                    return Math.floor(currentStock / comp.quantity_required);
                });
                calculable_stock = Math.min(...stockPerComponent);
            } else if (isDataReady && components.length === 0) {
                calculable_stock = 0;
            }

            return { ...p, calculable_stock: isDataReady ? calculable_stock : '...', components: components || [] };
        }
        return p;
    });
});

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
        let savedProduct = null;
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            const response = await axios.post(`/tpsp/products/${product.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const index = products.value.findIndex(p => p.id === product.value.id);
            products.value[index] = response.data;
            savedProduct = response.data;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto actualizado', life: 3000 });
        } else {
            const response = await axios.post('/tpsp/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            products.value.unshift(response.data);
            savedProduct = response.data;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto agregado', life: 3000 });
        }
        
        hideDialog();
        
        // REGLA: Si el producto guardado es compuesto, asegurar que tenga insumos, si no, forzar la receta.
        if (savedProduct.is_kit) {
            const existingComponents = kitComponentsMap.value[savedProduct.id] || [];
            if (existingComponents.length === 0) {
                toast.add({ severity: 'info', summary: 'Paso Obligatorio', detail: 'Por favor, define al menos un insumo para este producto.', life: 5000 });
                openKitComponents(savedProduct);
            } else {
                fetchAllKitComponents();
            }
        } else {
            fetchAllKitComponents();
        }

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
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]' ,
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        accept: async () => {
            try {
                await axios.delete(`/tpsp/products/${productData.id}`);
                products.value = products.value.filter(p => p.id !== productData.id);
                toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto eliminado', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el producto. Verifica que no esté en uso.', life: 4000 });
            }
        },
    });
};

const openStockModal = (productData) => {
    selectedProductForStock.value = productData;
    movementData.value = { product_id: productData.id, quantity: 0, type: 'Ajuste', notes: '' };
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
        const response = await axios.post(`/tpsp/products/${movementData.value.product_id}/adjust-stock`, {
            quantity: movementData.value.quantity,
            type: movementData.value.type,
            notes: movementData.value.notes
        });
        
        const index = products.value.findIndex(p => p.id === response.data.id);
        if (index !== -1) {
            products.value[index] = response.data;
        }

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Inventario actualizado correctamente', life: 3000 });
        hideStockModal();
        fetchAllKitComponents(); // Actualizar el fabricable de otros productos si afectó a un insumo

    } catch (error) {
        console.error("Error ajustando stock:", error.response?.data || error);
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo registrar el movimiento', life: 5000 });
    } finally {
        movementLoading.value = false;
    }
};

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

// --- GESTIÓN DE COMPONENTES / RECETAS ---

const loadKitComponentsForDialog = async (kitId) => {
    loadingComponentsForm.value = true;
    try {
        const response = await axios.get(`/tpsp/products/${kitId}/components`);
        const mappedComponents = response.data.map(comp => {
            const fullProduct = products.value.find(p => p.id === comp.component_product_id);
            return {
                ...comp,
                component_product: fullProduct || { name: 'Desconocido', stock: 0, unit_of_measure: '' }
            };
        });
        selectedKitDetails.value.components = mappedComponents;
        kitComponentsMap.value[kitId] = response.data;
    } catch (error) {
        console.error("Error fetching kit components:", error);
    } finally {
        loadingComponentsForm.value = false;
    }
};

const openKitComponents = (productData) => {
    selectedKitDetails.value.kit = productData;
    selectedKitDetails.value.components = [];
    newKitComponent.value = { component_product_id: null, quantity_required: 1 };
    kitComponentsDialog.value = true;
    loadKitComponentsForDialog(productData.id);
};

// Cierra el panel de componentes (solo si tiene componentes)
const closeKitComponentsDialog = () => {
    if (selectedKitDetails.value.components.length === 0) {
        toast.add({ severity: 'warn', summary: 'Acción Denegada', detail: 'Debes agregar al menos un insumo para continuar.', life: 4000 });
        return;
    }
    kitComponentsDialog.value = false;
};

// Aborta la creación del Kit y lo elimina si no se configuraron insumos
const abortKitCreation = () => {
    confirm.require({
        message: '¿Estás seguro de cancelar? Se eliminará este producto ya que no puede existir un producto compuesto sin insumos.',
        header: 'Cancelar Creación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar Producto',
        rejectLabel: 'Volver',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100 dark:hover:!bg-zinc-800',
        accept: async () => {
            try {
                await axios.delete(`/tpsp/products/${selectedKitDetails.value.kit.id}`);
                products.value = products.value.filter(p => p.id !== selectedKitDetails.value.kit.id);
                kitComponentsDialog.value = false;
                toast.add({ severity: 'success', summary: 'Cancelado', detail: 'Producto eliminado correctamente.', life: 3000 });
            } catch (error) {
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el producto.', life: 4000 });
            }
        }
    });
};

const addKitComponent = async () => {
    if (!selectedKitDetails.value.kit || !newKitComponent.value.component_product_id) {
        toast.add({ severity: 'warn', summary: 'Faltan datos', detail: 'Selecciona un componente a añadir.', life: 3000 });
        return;
    }
    try {
        await axios.post(`/tpsp/products/${selectedKitDetails.value.kit.id}/components`, newKitComponent.value);
        toast.add({ severity: 'success', summary: 'Añadido', detail: 'Componente agregado a la receta.', life: 3000 });
        
        newKitComponent.value = { component_product_id: null, quantity_required: 1 };
        await loadKitComponentsForDialog(selectedKitDetails.value.kit.id);
        fetchAllKitComponents(); // Recalcular fabricables globales
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo agregar el componente', life: 3000 });
    }
};

const updateKitComponent = (component) => {
    axios.put(`/tpsp/components/${component.id}`, { quantity_required: component.quantity_required })
    .then(() => {
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Cantidad de componente actualizada', life: 3000 });
        fetchAllKitComponents();
    })
    .catch(error => {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cantidad', life: 3000 });
        loadKitComponentsForDialog(selectedKitDetails.value.kit.id);
    });
};

const deleteKitComponent = (component) => {
    // REGLA: No dejar que el usuario elimine el último componente
    if (selectedKitDetails.value.components.length <= 1) {
        toast.add({ severity: 'error', summary: 'Acción Denegada', detail: 'El producto debe tener al menos un insumo. Añade otro antes de eliminar este.', life: 5000 });
        return;
    }

    confirm.require({
        message: `¿Quitar "${component.component_product.name}" de la receta?`,
        header: 'Remover Componente',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remover',
        rejectLabel: 'Cancelar',
        accept: () => {
            axios.delete(`/tpsp/components/${component.id}`)
            .then(() => {
                toast.add({ severity: 'success', summary: 'Removido', detail: 'Componente eliminado.', life: 3000 });
                loadKitComponentsForDialog(selectedKitDetails.value.kit.id);
                fetchAllKitComponents();
            })
            .catch(error => {
                toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo remover el componente', life: 3000 });
            });
        }
    });
};

</script>

<template>
    <div class="pb-20 md:pb-0"> <!-- Espacio extra para móvil -->
        <Toast />
        <ConfirmDialog :pt="{ root: { class: 'dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border-0 mx-3 sm:mx-0' }, header: { class: 'bg-white dark:bg-zinc-900 pb-0' }, content: { class: 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300' }, footer: { class: 'bg-white dark:bg-zinc-900 pt-0 flex gap-2 justify-end' } }" />

        <div class="grid">
            <div class="col-12">
                <!-- Header de Sección -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-5 px-2">
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                            Catálogo de Productos y Recetas
                        </h2>
                        <p class="text-sm text-zinc-500 mt-1">Gestiona inventario, cortes, doblado y kits fabricables.</p>
                    </div>
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
                        
                        <Column field="image_url" header="IMG" style="width: 4rem; text-align: center;">
                            <template #body="slotProps">
                                <Image 
                                    :src="slotProps.data.image_url || 'https://placehold.co/100x100/F4F4F5/A1A1AA?text=S/F'" 
                                    alt="Imagen" 
                                    width="40" 
                                    height="40" 
                                    preview 
                                    imageClass="rounded-xl object-cover h-10 w-10 shadow-sm border border-zinc-100 dark:border-zinc-800"
                                />
                            </template>
                        </Column>

                        <Column field="name" header="Nombre" :sortable="true" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ data.name }}</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-zinc-400">{{ data.sku || 'Sin SKU' }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[0.65rem] font-bold uppercase tracking-wider bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                            {{ data.category }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </Column>
                        
                        <Column field="stock" header="Físico" style="min-width: 100px;">
                            <template #body="slotProps">
                                <div class="flex items-baseline gap-1.5">
                                    <span class="font-bold text-base" :class="slotProps.data.stock > 0 ? 'text-zinc-800 dark:text-zinc-200' : 'text-red-500'">
                                        {{ slotProps.data.stock }}
                                    </span> 
                                    <span class="text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-500">{{ slotProps.data.unit_of_measure }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Fabricable" style="min-width: 110px;">
                            <template #body="slotProps">
                                <div v-if="slotProps.data.is_kit">
                                    <Tag :severity="slotProps.data.calculable_stock > 0 ? 'success' : 'danger'" class="!rounded-md">
                                        <span class="font-bold text-xs">{{ slotProps.data.calculable_stock }} unid.</span>
                                    </Tag>
                                </div>
                                <span v-else class="text-zinc-300 dark:text-zinc-700 text-xs">-</span>
                            </template>
                        </Column>
                        
                        <!-- Acciones -->
                        <Column header="Acciones" :exportable="false" style="min-width:18rem" bodyStyle="text-align: right; overflow: visible;">
                            <template #body="slotProps">
                                <div class="flex gap-2 justify-end">
                                    <!-- Botón Receta / Componentes -->
                                    <Button 
                                        v-if="slotProps.data.is_kit"
                                        icon="pi pi-sitemap" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-purple-50 dark:!bg-purple-900/30 !text-purple-600 dark:!text-purple-400 hover:!bg-purple-100 dark:hover:!bg-purple-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Ver Receta / Componentes'"
                                        @click="openKitComponents(slotProps.data)" 
                                    />
                                    <Button 
                                        icon="pi pi-history" 
                                        class="!rounded-xl !w-9 !h-9 !p-0 !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-600 dark:!text-indigo-400 hover:!bg-indigo-100 dark:hover:!bg-indigo-900/50 !border-0 transition-colors shadow-none" 
                                        v-tooltip.top="'Historial'"
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
                                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                        <span class="text-xs font-medium text-zinc-500">{{ product.sku || 'S/N' }}</span>
                                        <span class="text-[0.65rem] uppercase tracking-wider font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/50">{{ product.category }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Inferior: Stock Físico y Fabricable -->
                            <div class="grid grid-cols-2 gap-3 mt-1">
                                <div class="flex flex-col bg-zinc-50 dark:bg-zinc-950 px-3 py-2 rounded-xl border border-zinc-100 dark:border-zinc-800">
                                    <span class="text-[0.65rem] uppercase tracking-wider font-semibold text-zinc-400 mb-0.5"><i class="pi pi-box mr-1 text-[0.6rem]"></i> Físico</span>
                                    <span class="font-bold text-lg text-zinc-800 dark:text-zinc-200 leading-none">
                                        {{ product.stock }} <span class="text-xs font-medium text-zinc-500 uppercase">{{ product.unit_of_measure }}</span>
                                    </span>
                                </div>

                                <div v-if="product.is_kit" class="flex flex-col bg-emerald-50/50 dark:bg-emerald-900/10 px-3 py-2 rounded-xl border border-emerald-100 dark:border-emerald-800/30">
                                    <span class="text-[0.65rem] uppercase tracking-wider font-semibold text-emerald-600 dark:text-emerald-400 mb-0.5"><i class="pi pi-wrench mr-1 text-[0.6rem]"></i> Fabricable</span>
                                    <span class="font-bold text-lg text-emerald-700 dark:text-emerald-300 leading-none">
                                        {{ product.calculable_stock }} <span class="text-xs font-medium text-emerald-600/70 uppercase">U.</span>
                                    </span>
                                </div>
                                <div v-else class="flex items-center justify-center bg-zinc-50/50 dark:bg-zinc-950/30 px-3 py-2 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800/50 opacity-60">
                                    <span class="text-xs text-zinc-400 font-medium italic">No fabricable</span>
                                </div>
                            </div>
                            
                            <!-- Acciones (Móvil) -->
                            <div class="flex gap-2 justify-between mt-1 pt-3 border-t border-zinc-100 dark:border-zinc-800/50">
                                <div class="flex gap-2">
                                     <Button 
                                        v-if="product.is_kit"
                                        icon="pi pi-sitemap" 
                                        label="Receta"
                                        class="!rounded-xl !h-10 !px-3 !bg-purple-50 dark:!bg-purple-900/30 !text-purple-600 dark:!text-purple-400 hover:!bg-purple-100 dark:hover:!bg-purple-900/50 !border-0 transition-colors shadow-none text-sm font-semibold" 
                                        @click="openKitComponents(product)" 
                                    />
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
                                </div>
                                <div class="flex gap-2">
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
            :style="{width: '100%', maxWidth: '40rem'}" 
            :header="isEditing ? 'Editar Producto' : 'Nuevo Producto'" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-3">
                <div class="flex flex-col gap-2 md:col-span-2">
                    <label for="productName" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">
                        Nombre del Producto <span class="text-red-500">*</span>
                    </label>
                    <InputText id="productName" v-model.trim="product.name" required autofocus class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" />
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="productSku" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">SKU / Clave (Opcional)</label>
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
                    <label for="productStock" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Stock Físico Inicial</label>
                    <InputNumber id="productStock" v-model="product.stock" mode="decimal" class="w-full" inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3" />
                </div>

                <div class="flex flex-col gap-2 md:col-span-2 mt-2 bg-purple-50/50 dark:bg-purple-900/10 p-4 rounded-2xl border border-purple-100 dark:border-purple-800/30">
                    <div class="flex items-center gap-3">
                        <InputSwitch v-model="product.is_kit" inputId="productIsKit" />
                        <label for="productIsKit" class="text-sm font-bold text-purple-900 dark:text-purple-100 cursor-pointer">
                            Producto Compuesto / Fabricable
                        </label>
                    </div>
                    <p class="text-xs text-purple-700/80 dark:text-purple-300/70 ml-11 mt-1 leading-snug">
                        Activa esto si el producto es un Kit, Tela Cortada, Doblada o requiere insumos para fabricarse. Podrás definir su receta al guardar.
                    </p>
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
                        class="apple-fileupload !text-[var(--primary-text-color)]"
                        :pt="{ root: { class: 'w-full' }, buttonbar: { class: 'hidden' }, content: { class: '!p-0 !border-0 bg-transparent' } }"
                    >
                        <template #empty>
                            <div class="flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-950/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors cursor-pointer" @click="$refs.fileUploadRef.choose()">
                                <i class="pi pi-image text-3xl mb-3 text-zinc-400"></i>
                                <p class="mb-0 font-medium text-sm text-center">Toca aquí para subir una imagen</p>
                                <p class="text-xs text-zinc-400 mt-1">PNG, JPG hasta 2MB</p>
                                <Image v-if="isEditing && product.image_url" :src="product.image_url" alt="Imagen actual" width="80" class="mt-4 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" />
                            </div>
                        </template>
                    </FileUpload>
                </div>
            </div>

            <template #footer>
                <Button label="Cancelar" @click="hideDialog" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors" />
                <Button :label="isEditing ? 'Actualizar Producto' : 'Guardar y Continuar'" @click="saveProduct" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all" />
            </template>
        </Dialog>


        <!-- NUEVO: Modal (Dialog) de Gestión de Componentes / Receta (AHORA BLOQUEABLE) -->
        <Dialog 
            v-model:visible="kitComponentsDialog" 
            :style="{width: '100%', maxWidth: '45rem'}" 
            header="Receta / Componentes" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="selectedKitDetails.components.length > 0 && !loadingComponentsForm"
            :closable="selectedKitDetails.components.length > 0 && !loadingComponentsForm"
            :closeOnEscape="selectedKitDetails.components.length > 0 && !loadingComponentsForm"
        >
            <div class="flex flex-col mt-2" v-if="selectedKitDetails.kit">
                
                <!-- Encabezado del producto padre -->
                <div class="flex items-center gap-4 bg-purple-50 dark:bg-purple-900/10 p-4 rounded-2xl border border-purple-100 dark:border-purple-800/30 mb-5">
                    <Image 
                        :src="selectedKitDetails.kit.image_url || 'https://placehold.co/100x100/F4F4F5/A1A1AA?text=S/F'" 
                        alt="Imagen" 
                        width="50" height="50" 
                        imageClass="rounded-lg object-cover h-[50px] w-[50px] shadow-sm border border-white dark:border-zinc-800"
                    />
                    <div class="flex flex-col">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-0.5">Producto a Fabricar</span>
                        <span class="font-bold text-lg text-zinc-900 dark:text-zinc-100 leading-tight">{{ selectedKitDetails.kit.name }}</span>
                    </div>
                </div>

                <!-- ADVERTENCIA AMARILLA (Obligación de insumo) -->
                <div v-if="selectedKitDetails.components.length === 0 && !loadingComponentsForm" class="mb-5 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/30 flex items-start gap-3">
                    <i class="pi pi-exclamation-triangle text-amber-600 dark:text-amber-400 mt-0.5 text-xl"></i>
                    <div class="flex flex-col">
                        <span class="text-amber-800 dark:text-amber-300 font-bold text-sm">Insumo Obligatorio</span>
                        <span class="text-amber-700 dark:text-amber-400 text-xs mt-0.5">Para completar el registro de este producto compuesto, debes añadir al menos un insumo a la receta. No podrás cerrar esta ventana hasta hacerlo.</span>
                    </div>
                </div>

                <!-- Formulario Añadir -->
                <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3 ml-1">Añadir Insumo a la Receta</h4>
                <div class="flex flex-col sm:flex-row gap-3 mb-6 bg-zinc-50 dark:bg-zinc-950 p-3 sm:p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800">
                    <div class="flex-1 flex flex-col gap-2">
                        <Dropdown 
                            v-model="newKitComponent.component_product_id" 
                            :options="availableComponents" 
                            optionLabel="name" 
                            optionValue="id" 
                            placeholder="Buscar material o insumo..." 
                            filter
                            class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-white dark:!bg-zinc-900 shadow-sm" 
                        />
                    </div>
                    <div class="w-full sm:w-32 flex flex-col gap-2">
                        <InputNumber 
                            v-model="newKitComponent.quantity_required" 
                            mode="decimal" :min="0.01" :minFractionDigits="0" :maxFractionDigits="3"
                            placeholder="Cant."
                            class="w-full"
                            inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-white dark:!bg-zinc-900 dark:!text-zinc-100 shadow-sm" 
                        />
                    </div>
                    <div class="w-full sm:w-auto flex items-end">
                        <Button label="Añadir" icon="pi pi-plus" @click="addKitComponent" class="w-full !rounded-xl !bg-zinc-900 dark:!bg-zinc-100 !text-white dark:!text-zinc-900 hover:!bg-zinc-800 dark:hover:!bg-white !border-0 shadow-sm px-4 py-3" />
                    </div>
                </div>

                <!-- Lista de Componentes Actuales -->
                <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3 ml-1">Insumos Requeridos ({{ selectedKitDetails.components.length }})</h4>
                
                <div v-if="loadingComponentsForm" class="text-center p-6">
                    <i class="pi pi-spin pi-spinner text-2xl text-zinc-400"></i>
                </div>
                
                <div v-else-if="selectedKitDetails.components.length === 0" class="text-center p-8 bg-zinc-50 dark:bg-zinc-950 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
                    <i class="pi pi-inbox text-3xl text-zinc-300 mb-2"></i>
                    <p class="text-sm text-zinc-500">Este producto no tiene insumos configurados aún.</p>
                </div>

                <!-- Tabla (Oculta en móviles muy chicos, preferimos tarjetas en móvil) -->
                <div v-else class="hidden sm:block border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <DataTable :value="selectedKitDetails.components" class="zinc-table" responsiveLayout="scroll">
                        <Column header="Insumo">
                            <template #body="{ data }">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ data.component_product.name }}</span>
                            </template>
                        </Column>
                        <Column header="Stock Disp.">
                            <template #body="{ data }">
                                <span class="text-sm text-zinc-500">{{ data.component_product.stock }} {{ data.component_product.unit_of_measure }}</span>
                            </template>
                        </Column>
                        <Column header="Req. p/Unidad" style="width: 140px;">
                            <template #body="slotProps">
                                <InputNumber 
                                    v-model="slotProps.data.quantity_required" 
                                    mode="decimal" :min="0.01" :maxFractionDigits="3"
                                    class="w-full"
                                    inputClass="!w-full !rounded-lg !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 text-center !py-1.5"
                                />
                            </template>
                        </Column>
                        <Column header="" style="width: 110px; text-align: right;">
                            <template #body="slotProps">
                                <div class="flex justify-end gap-1">
                                    <Button icon="pi pi-check" class="!w-8 !h-8 !p-0 p-button-rounded p-button-text p-button-success" v-tooltip.top="'Guardar Cantidad'" @click="updateKitComponent(slotProps.data)" />
                                    <Button icon="pi pi-trash" class="!w-8 !h-8 !p-0 p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Quitar'" @click="deleteKitComponent(slotProps.data)" />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <!-- Vista Móvil para Insumos -->
                <div v-if="!loadingComponentsForm && selectedKitDetails.components.length > 0" class="sm:hidden flex flex-col gap-3">
                    <div v-for="comp in selectedKitDetails.components" :key="comp.id" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 shadow-sm flex flex-col gap-3">
                        <div class="flex justify-between items-start">
                            <span class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm leading-tight pr-4">{{ comp.component_product.name }}</span>
                            <Button icon="pi pi-trash" class="!w-8 !h-8 !p-0 !bg-red-50 dark:!bg-red-900/20 !text-red-500 !border-0 shrink-0" @click="deleteKitComponent(comp)" />
                        </div>
                        <div class="flex items-end justify-between gap-3">
                            <div class="flex flex-col">
                                <span class="text-[0.65rem] text-zinc-500 uppercase tracking-wider mb-1">Stock Disp.</span>
                                <span class="text-sm font-medium">{{ comp.component_product.stock }} <span class="text-xs">{{ comp.component_product.unit_of_measure }}</span></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex flex-col">
                                    <span class="text-[0.65rem] text-zinc-500 uppercase tracking-wider mb-1">Requerido</span>
                                    <InputNumber 
                                        v-model="comp.quantity_required" 
                                        mode="decimal" :min="0.01" :maxFractionDigits="3"
                                        class="w-20"
                                        inputClass="!w-full !rounded-lg !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-50 dark:!bg-zinc-950 dark:!text-zinc-100 text-center !py-1"
                                    />
                                </div>
                                <Button icon="pi pi-check" class="!w-9 !h-9 !mt-4 !p-0 !bg-emerald-50 dark:!bg-emerald-900/20 !text-emerald-600 !border-0 shrink-0" @click="updateKitComponent(comp)" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            
            <template #footer>
                <div class="flex flex-col sm:flex-row justify-between w-full gap-3">
                    <Button 
                        v-if="selectedKitDetails.components.length === 0 && !loadingComponentsForm" 
                        label="Abortar y Eliminar Producto" 
                        icon="pi pi-trash" 
                        @click="abortKitCreation" 
                        class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-red-50 dark:!bg-red-900/30 !text-red-600 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 font-medium transition-colors" 
                    />
                    <div v-else class="hidden sm:block"></div>
                    
                    <Button 
                        label="Guardar y Cerrar Panel" 
                        @click="closeKitComponentsDialog" 
                        :disabled="selectedKitDetails.components.length === 0 || loadingComponentsForm"
                        class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-900 dark:!bg-zinc-100 !text-white dark:!text-zinc-900 hover:!bg-zinc-800 dark:hover:!bg-white !border-0 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed" 
                    />
                </div>
            </template>
        </Dialog>


        <!-- Modal (Dialog) para Ajustar Inventario Físico (Mantener Inalterado en funcionalidad) -->
        <Dialog 
            v-model:visible="stockMovementDialog" 
            :style="{width: '100%', maxWidth: '28rem'}" 
            header="Ajuste de Inventario Físico" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2">
                <div v-if="selectedProductForStock" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
                    <div class="bg-white dark:bg-zinc-900 px-3 pt-3 pb-2 rounded-xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
                        <i class="pi pi-box text-zinc-400" style="font-size: 18px;"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ selectedProductForStock.name }}</span>
                        <span class="text-md text-zinc-500">Stock Físico: <strong class="text-zinc-700 dark:text-zinc-300">{{ selectedProductForStock.stock }} {{ selectedProductForStock.unit_of_measure }}</strong></span>
                    </div>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="movementQuantity" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Ajustar</label>
                    <InputNumber 
                        id="movementQuantity" 
                        v-model="movementData.quantity" 
                        mode="decimal" :allowEmpty="false" showButtons class="w-full"
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
                <Button label="Cancelar" @click="hideStockModal" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors" />
                <Button label="Confirmar Ajuste" @click="saveStockMovement" :loading="movementLoading" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all" />
            </template>
        </Dialog>

        <!-- Historial de Movimientos -->
        <Dialog 
            v-model:visible="viewMovementsDialog" 
            :style="{width: '100%', maxWidth: '48rem'}" 
            header="Historial de Movimientos" 
            :modal="true" 
            :pt="appleModalStyles"
            :dismissableMask="true"
        >
            <div class="flex flex-col gap-5 mt-2">
                <div v-if="selectedProductForStock" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 mb-2">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                        <i class="pi pi-history text-indigo-500 dark:text-indigo-400 text-xl"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ selectedProductForStock.name }}</span>
                        <span class="text-sm text-zinc-500">Últimos registros de stock Físico</span>
                    </div>
                </div>

                <div class="border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                    <DataTable :value="selectedProductMovements" :loading="movementsLoading" class="zinc-table" :paginator="true" :rows="5" responsiveLayout="scroll">
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
                <Button label="Cerrar Historial" @click="viewMovementsDialog = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 hover:!bg-zinc-200 dark:hover:!bg-zinc-700 !text-zinc-800 dark:!text-zinc-200 !border-0 font-medium transition-colors" />
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

/* Asegurar que modales no topen en móvil */
:deep(.p-dialog) {
    margin: 1rem;
    max-height: 90vh;
}
</style>

<style>
/* Zinc Theme Overrides for PrimeVue DataTable */
.zinc-table .p-datatable-thead > tr > th {
    background-color: transparent !important;
    color: #52525b !important;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    border-bottom: 1px solid #e4e4e7 !important;
    padding: 0.75rem 1rem !important;
}

.zinc-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}

.zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #f4f4f5 !important; 
}

/* Reglas de Dark Mode */
html.dark .zinc-table .p-datatable-thead > tr > th,
.dark .zinc-table .p-datatable-thead > tr > th {
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a !important;
}

html.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>