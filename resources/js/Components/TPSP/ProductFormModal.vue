<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import InputSwitch from 'primevue/inputswitch';
import FileUpload from 'primevue/fileupload';
import Image from 'primevue/image';
import axios from 'axios';
import AppleInputNumber from '@/Components/AppleInputNumber.vue';

const toast = useToast();

const props = defineProps({
    visible: Boolean,
    productData: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const isEditing = ref(false);
const product = ref(getFreshProduct());
const fileUploadRef = ref(null);

const productCategories = ['Material', 'Insumo', 'Empaque', 'Producto Terminado'];
const unitsOfMeasure = ['Pieza', 'Mililitro', 'Gramo', 'Kit', 'Kilogramo', 'Metro', 'Rollo', 'Litro'];

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0 w-full mx-2 sm:mx-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

function getFreshProduct() {
    return {
        id: null,
        name: '',
        sku: '',
        category: 'Insumo',
        unit_of_measure: 'Pieza',
        stock: 0,
        is_kit: false,
        is_public: true,
        image: null,
        image_url: null,
    };
}

watch(() => props.visible, (val) => {
    if (val) {
        if (props.productData && props.productData.id) {
            product.value = { ...props.productData };
            isEditing.value = true;
        } else {
            product.value = getFreshProduct();
            isEditing.value = false;
        }
        if (fileUploadRef.value) {
            fileUploadRef.value.clear();
        }
    }
});

// Auto-mark kit when category is "Producto Terminado"
watch(() => product.value.category, (newVal) => {
    if (newVal === 'Producto Terminado') {
        product.value.is_kit = true;
    }
});

const submit = async () => {
    const formData = new FormData();
    formData.append('name', product.value.name);
    formData.append('sku', product.value.sku || '');
    formData.append('category', product.value.category);
    formData.append('unit_of_measure', product.value.unit_of_measure);
    formData.append('stock', product.value.stock);
    formData.append('is_kit', product.value.is_kit ? 1 : 0);
    formData.append('is_public', product.value.is_public ? 1 : 0);

    if (fileUploadRef.value && fileUploadRef.value.files.length > 0) {
        formData.append('image', fileUploadRef.value.files[0]);
    }

    try {
        if (isEditing.value) {
            formData.append('_method', 'PUT');
            await axios.post(`/tpsp/products/${product.value.id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto actualizado', life: 3000 });
        } else {
            await axios.post('/tpsp/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Producto agregado', life: 3000 });
        }
        visible.value = false;
        emit('saved', { isKit: product.value.is_kit, productId: product.value.id });
    } catch (error) {
        console.error("Error saving product:", error.response?.data || error);
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo guardar el producto', life: 5000 });
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :style="{ width: '100%', maxWidth: '40rem' }"
        :header="isEditing ? 'Editar Producto' : 'Nuevo Producto'"
        :modal="true"
        :pt="appleModalStyles"
        :dismissableMask="true"

    >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="flex flex-col gap-2 md:col-span-2">
                <label for="productName" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">
                    Nombre del Producto <span class="text-red-500">*</span>
                </label>
                <InputText id="productName" v-model.trim="product.name" required autofocus class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm !h-[48px] px-3" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="productSku" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">SKU / Clave (Opcional)</label>
                <InputText id="productSku" v-model.trim="product.sku" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm !h-[48px] px-3" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="productCategory" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Categoría</label>
                <Dropdown id="productCategory" v-model="product.category" :options="productCategories" placeholder="Seleccione categoría" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm !h-[48px] flex items-center" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="productUnit" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Unidad de Medida</label>
                <Dropdown id="productUnit" v-model="product.unit_of_measure" :options="unitsOfMeasure" placeholder="Seleccione unidad" class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm !h-[48px] flex items-center" />
            </div>

            <div class="flex flex-col gap-2">
                <label for="productStock" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Stock Físico Inicial</label>
                <AppleInputNumber v-model="product.stock" :allowDecimals="true" class="!border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm" />
            </div>

            <!-- SWITCH DE COMPUESTO / KIT -->
            <div class="flex flex-col gap-2 md:col-span-2 mt-0 bg-purple-50/50 dark:bg-purple-900/10 p-4 rounded-2xl border border-purple-100 dark:border-purple-800/30">
                <div class="flex items-center gap-3">
                    <InputSwitch v-model="product.is_kit" inputId="productIsKit" />
                    <label for="productIsKit" class="text-sm font-bold text-purple-900 dark:text-purple-100 cursor-pointer">
                        Producto Compuesto / Fabricable
                    </label>
                </div>
            </div>

            <!-- SWITCH DE VISIBILIDAD PÚBLICA -->
            <div class="flex flex-col gap-2 md:col-span-2 mt-0 bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                <div class="flex items-center gap-3">
                    <InputSwitch v-model="product.is_public" inputId="productIsPublic" />
                    <label for="productIsPublic" class="text-sm font-bold text-blue-900 dark:text-blue-100 cursor-pointer">
                        Visible en Catálogo Público
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 md:col-span-2 mt-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Imagen del Producto</label>

                <div v-if="isEditing && product.image_url" class="flex flex-col items-center sm:items-start bg-zinc-50 dark:bg-zinc-900/50 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800 w-full mb-2">
                    <span class="text-xs text-zinc-500 mb-3 font-medium uppercase tracking-wider">Imagen Actual (Clic para ampliar)</span>
                    <Image
                        :src="product.image_url"
                        alt="Imagen actual"
                        width="140"
                        preview
                        imageClass="rounded-xl shadow-md border border-zinc-200 dark:border-zinc-700 object-cover h-[140px] w-[140px] cursor-pointer hover:opacity-90 transition-opacity"
                    />
                </div>

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
                    @uploader="submit"
                    class="apple-fileupload !text-[var(--primary-text-color)]"
                    :pt="{ root: { class: 'w-full' }, buttonbar: { class: 'hidden' }, content: { class: '!p-0 !border-0 bg-transparent' } }"
                >
                    <template #empty>
                        <div class="flex flex-col items-center justify-center p-8 bg-zinc-50 dark:bg-zinc-950/50 rounded-2xl border-2 border-dashed border-zinc-200 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors cursor-pointer" @click="$refs.fileUploadRef.choose()">
                            <i class="pi pi-image text-3xl mb-3 text-zinc-400"></i>
                            <p class="mb-0 font-medium text-sm text-center">Toca aquí para subir una imagen {{ isEditing && product.image_url ? 'nueva' : '' }}</p>
                            <p class="text-xs text-zinc-400 mt-1">PNG, JPG hasta 2MB</p>
                        </div>
                    </template>
                </FileUpload>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors" />
            <Button :label="isEditing ? 'Actualizar Producto' : 'Guardar y Continuar'" @click="submit" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all" />
        </template>
    </Dialog>
</template>
