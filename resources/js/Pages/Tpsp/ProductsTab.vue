<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';
import ConfirmDialog from 'primevue/confirmdialog';
import Image from 'primevue/image';
import Tag from 'primevue/tag';
import axios from 'axios';

// --- Componentes modulares extraídos ---
import ProductFormModal from '@/Components/TPSP/ProductFormModal.vue';
import KitComponentsModal from '@/Components/TPSP/KitComponentsModal.vue';
import StockAdjustmentModal from '@/Components/TPSP/StockAdjustmentModal.vue';
import StockHistoryModal from '@/Components/TPSP/StockHistoryModal.vue';

const toast = useToast();
const confirm = useConfirm();
const products = ref([]);
const loading = ref(true);

// --- ESTADOS PARA FILTROS Y BÚSQUEDA ---
const searchQuery = ref('');
const selectedCategoryFilter = ref(null);

// Opciones
const productCategories = ref(['Material', 'Insumo', 'Empaque', 'Producto Terminado']);

// Estado de modales
const productDialog = ref(false);
const editingProductData = ref(null);

const stockMovementDialog = ref(false);
const viewMovementsDialog = ref(false);
const selectedProductForStock = ref(null);
const selectedProductMovements = ref([]);
const movementsLoading = ref(false);

const kitComponentsDialog = ref(false);
const selectedKitDetails = ref({ kit: null, components: [] });
const loadingComponentsForm = ref(false);
const newKitComponent = ref({ component_product_id: null, quantity_required: 1 });

// --- ESTADO PARA RECETAS (KITS) ---
const kitComponentsMap = ref({});

// Computed: Productos disponibles para ser componentes (excluyendo el kit actual)
const availableComponents = computed(() => {
    return products.value.filter(p => selectedKitDetails.value.kit && p.id !== selectedKitDetails.value.kit.id && !p.is_kit);
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
            newMap[kits[index].id] = result.data;
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

// Computed: Filtrar e Inyectar el stock fabricable
const filteredProducts = computed(() => {
    let result = products.value;
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        result = result.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.sku && p.sku.toLowerCase().includes(q))
        );
    }
    if (selectedCategoryFilter.value) {
        result = result.filter(p => p.category === selectedCategoryFilter.value);
    }
    return result.map(p => {
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
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace('.', '');
};

const getMovementSeverity = (type) => {
    if (['Compra', 'Entrada_Produccion', 'Entrada de material'].includes(type)) return 'success';
    if (['Venta', 'Consumo_Produccion'].includes(type)) return 'danger';
    return 'info';
};

// --- APERTURA DE MODALES ---

const openNew = () => {
    editingProductData.value = null;
    productDialog.value = true;
};

const openEditModal = (productData) => {
    editingProductData.value = { ...productData };
    productDialog.value = true;
};

const onProductSaved = async (savedInfo) => {
    await fetchProducts();
    // Si el producto guardado es kit, forzar apertura de receta si no tiene insumos
    if (savedInfo && savedInfo.isKit && savedInfo.productId) {
        const existingComponents = kitComponentsMap.value[savedInfo.productId] || [];
        if (existingComponents.length === 0) {
            const kitProduct = products.value.find(p => p.id === savedInfo.productId);
            if (kitProduct) {
                toast.add({ severity: 'info', summary: 'Paso Obligatorio', detail: 'Por favor, define al menos un insumo para este producto.', life: 5000 });
                openKitComponents(kitProduct);
            }
        }
    }
};

const confirmDeleteProduct = (productData) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar "${productData.name}"?`,
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Sí, Eliminar',
        rejectLabel: 'Cancelar',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
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

// --- STOCK ---
const openStockModal = (productData) => {
    selectedProductForStock.value = productData;
    stockMovementDialog.value = true;
};

const onStockSaved = () => {
    fetchProducts();
    fetchAllKitComponents();
};

const openMovementsModal = async (productData) => {
    selectedProductForStock.value = productData;
    viewMovementsDialog.value = true;
    movementsLoading.value = true;
    try {
        const res = await axios.get(`/tpsp/products/${productData.id}/movements`);
        selectedProductMovements.value = res.data;
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el historial' });
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

const closeKitComponentsDialog = () => {
    if (selectedKitDetails.value.components.length === 0) {
        toast.add({ severity: 'warn', summary: 'Acción Denegada', detail: 'Debes agregar al menos un insumo para continuar.', life: 4000 });
        return;
    }
    kitComponentsDialog.value = false;
};

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

// Handlers emitidos por KitComponentsModal
const onAddComponent = async (componentData) => {
    if (!selectedKitDetails.value.kit || !componentData.component_product_id) {
        toast.add({ severity: 'warn', summary: 'Faltan datos', detail: 'Selecciona un componente a añadir.', life: 3000 });
        return;
    }
    try {
        await axios.post(`/tpsp/products/${selectedKitDetails.value.kit.id}/components`, componentData);
        toast.add({ severity: 'success', summary: 'Añadido', detail: 'Componente agregado a la receta.', life: 3000 });
        await loadKitComponentsForDialog(selectedKitDetails.value.kit.id);
        fetchAllKitComponents();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo agregar el componente', life: 3000 });
    }
};

const onUpdateComponent = async (component) => {
    try {
        await axios.put(`/tpsp/components/${component.id}`, { quantity_required: component.quantity_required });
        toast.add({ severity: 'success', summary: 'Actualizado', detail: 'Cantidad de componente actualizada', life: 3000 });
        fetchAllKitComponents();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar la cantidad', life: 3000 });
        loadKitComponentsForDialog(selectedKitDetails.value.kit.id);
    }
};

const onDeleteComponent = (component) => {
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
    <div class="pb-20 md:pb-0">
        <Toast />
        <ConfirmDialog :pt="{ root: { class: 'dark:bg-zinc-900 rounded-3xl overflow-hidden shadow-2xl border-0 mx-3 sm:mx-0' }, header: { class: 'bg-white dark:bg-zinc-900 pb-0' }, content: { class: 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300' }, footer: { class: 'bg-white dark:bg-zinc-900 pt-0 flex gap-2 justify-end' } }" />

        <div class="grid">
            <div class="col-12">
                <!-- Header de Sección -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-3 px-2">
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

                <!-- SECCIÓN DE BÚSQUEDA Y FILTROS -->
                <div class="flex flex-col sm:flex-row gap-3 mb-4 px-2">
                    <div class="relative flex-1 sm:max-w-md">
                        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 z-10"></i>
                        <InputText
                            v-model="searchQuery"
                            placeholder="Buscar por nombre o SKU..."
                            class="w-full pl-10 !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm !h-[42px]"
                        />
                    </div>
                    <Dropdown
                        v-model="selectedCategoryFilter"
                        :options="productCategories"
                        placeholder="Todas las categorías"
                        :showClear="true"
                        class="w-full sm:w-56 !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm !h-[42px] flex items-center"
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
                                    width="40" height="40" preview
                                    imageClass="rounded-xl object-cover h-10 w-10 shadow-sm border border-zinc-100 dark:border-zinc-800"
                                />
                            </template>
                        </Column>

                        <Column field="name" header="Nombre" :sortable="true" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                        {{ data.name }}
                                        <i v-if="data.is_public" class="pi pi-eye ml-1 text-emerald-500" v-tooltip.top="'Visible en Público'" style="font-size: 0.8rem;"></i>
                                        <i v-else class="pi pi-eye-slash ml-1 text-zinc-400" v-tooltip.top="'Oculto en Público'" style="font-size: 0.8rem;"></i>
                                    </span>
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
                        <template #empty>
                            <div class="text-center p-6 text-zinc-500">
                                <i class="pi pi-search text-3xl mb-3 block"></i>
                                <span class="text-sm">No se encontraron productos que coincidan con la búsqueda.</span>
                            </div>
                        </template>
                    </DataTable>
                </div>

                <!-- Vista de Tarjetas (Móvil) -->
                <div class="md:hidden mt-2">
                    <div v-if="loading" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-3">
                        <i class="pi pi-spin pi-spinner text-3xl"></i>
                        <p>Cargando productos...</p>
                    </div>
                    <div v-else-if="filteredProducts.length === 0" class="text-center p-8 text-zinc-400 flex flex-col items-center gap-2">
                        <i class="pi pi-search text-3xl"></i>
                        <p>No se encontraron resultados.</p>
                    </div>
                    <div v-else class="flex flex-col gap-4">
                        <div v-for="product in filteredProducts" :key="product.id" class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-2xl p-4 shadow-sm flex flex-col gap-4">

                            <div class="flex gap-4 items-center">
                                <Image
                                    :src="product.image_url || 'https://placehold.co/120x120/F4F4F5/A1A1AA?text=Sin+Foto'"
                                    alt="Imagen" width="64" height="64" preview
                                    imageClass="rounded-xl object-cover w-16 h-16 shadow-sm border border-zinc-100 dark:border-zinc-800"
                                />
                                <div class="flex-1">
                                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100 text-lg leading-tight">
                                        {{ product.name }}
                                        <i v-if="product.is_public" class="pi pi-eye ml-1 text-emerald-500" style="font-size: 0.8rem;"></i>
                                        <i v-else class="pi pi-eye-slash ml-1 text-zinc-400" style="font-size: 0.8rem;"></i>
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                        <span class="text-xs font-medium text-zinc-500">{{ product.sku || 'S/N' }}</span>
                                        <span class="text-[0.65rem] uppercase tracking-wider font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 px-2 py-0.5 rounded-md border border-zinc-200 dark:border-zinc-700/50">{{ product.category }}</span>
                                    </div>
                                </div>
                            </div>

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

                            <div class="flex gap-2 justify-between mt-1 pt-3 border-t border-zinc-100 dark:border-zinc-800/50">
                                <div class="flex gap-2">
                                    <Button v-if="product.is_kit" icon="pi pi-sitemap" label="Receta"
                                        class="!rounded-xl !h-10 !px-3 !bg-purple-50 dark:!bg-purple-900/30 !text-purple-600 dark:!text-purple-400 hover:!bg-purple-100 dark:hover:!bg-purple-900/50 !border-0 transition-colors shadow-none text-sm font-semibold"
                                        @click="openKitComponents(product)" />
                                    <Button icon="pi pi-history" class="!rounded-xl !w-10 !h-10 !p-0 !bg-indigo-50 dark:!bg-indigo-900/30 !text-indigo-600 dark:!text-indigo-400 hover:!bg-indigo-100 dark:hover:!bg-indigo-900/50 !border-0 transition-colors shadow-none"
                                        @click="openMovementsModal(product)" />
                                    <Button icon="pi pi-arrows-h" class="!rounded-xl !w-10 !h-10 !p-0 !bg-blue-50 dark:!bg-blue-900/30 !text-blue-600 dark:!text-blue-400 hover:!bg-blue-100 dark:hover:!bg-blue-900/50 !border-0 transition-colors shadow-none"
                                        @click="openStockModal(product)" />
                                </div>
                                <div class="flex gap-2">
                                    <Button icon="pi pi-pencil" class="!rounded-xl !w-10 !h-10 !p-0 !bg-amber-50 dark:!bg-amber-900/30 !text-amber-600 dark:!text-amber-400 hover:!bg-amber-100 dark:hover:!bg-amber-900/50 !border-0 transition-colors shadow-none"
                                        @click="openEditModal(product)" />
                                    <Button icon="pi pi-trash" class="!rounded-xl !w-10 !h-10 !p-0 !bg-red-50 dark:!bg-red-900/30 !text-red-600 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 transition-colors shadow-none"
                                        @click="confirmDeleteProduct(product)" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== MODALES EXTRAÍDOS ==================== -->

        <ProductFormModal
            v-model:visible="productDialog"
            :productData="editingProductData"
            @saved="onProductSaved"
        />

        <KitComponentsModal
            v-model:visible="kitComponentsDialog"
            :kit="selectedKitDetails.kit"
            :availableComponents="availableComponents"
            :components="selectedKitDetails.components"
            :loading="loadingComponentsForm"
            :canClose="selectedKitDetails.components.length > 0 && !loadingComponentsForm"
            @add-component="onAddComponent"
            @update-component="onUpdateComponent"
            @delete-component="onDeleteComponent"
            @abort="abortKitCreation"
        />

        <StockAdjustmentModal
            v-model:visible="stockMovementDialog"
            :product="selectedProductForStock"
            @saved="onStockSaved"
        />

        <StockHistoryModal
            v-model:visible="viewMovementsDialog"
            :product="selectedProductForStock"
            :movements="selectedProductMovements"
            :loading="movementsLoading"
        />
    </div>
</template>

<style scoped>
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
    box-shadow: 0 0 0 2px rgba(121, 34, 236, 0.2) !important;
    outline: none;
}
:deep(.p-dialog) {
    margin: 1rem;
    max-height: 90vh;
}
</style>

<style>
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
html.dark .zinc-table .p-datatable-thead > tr > th,
.dark .zinc-table .p-datatable-thead > tr > th {
    color: #a1a1aa !important;
    border-bottom: 1px solid #27272a !important;
}
html.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .zinc-table .p-datatable-tbody > tr:not(:last-child) > td {
    border-bottom: 1px solid #27272a !important;
}
html.dark .p-inputtext,
.dark .p-inputtext,
html.dark .p-dropdown,
.dark .p-dropdown {
    background-color: #09090b !important;
    border-color: #3f3f46 !important;
    color: #f4f4f5 !important;
}
html.dark .p-dropdown .p-dropdown-label,
.dark .p-dropdown .p-dropdown-label {
    color: #f4f4f5 !important;
}
html.dark .p-dropdown .p-dropdown-trigger,
.dark .p-dropdown .p-dropdown-trigger {
    color: #a1a1aa !important;
}
</style>
