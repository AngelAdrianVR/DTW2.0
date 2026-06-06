<script setup>
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Image from 'primevue/image';
import AppleInputNumber from '@/Components/AppleInputNumber.vue';

const props = defineProps({
    visible: Boolean,
    kit: { type: Object, default: null },
    availableComponents: { type: Array, default: () => [] },
    components: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    canClose: { type: Boolean, default: true },
});

const emit = defineEmits([
    'update:visible',
    'add-component',
    'update-component',
    'delete-component',
    'abort',
]);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const newComponent = ref({ component_product_id: null, quantity_required: 1 });

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0 w-full mx-2 sm:mx-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const onAdd = () => {
    emit('add-component', { ...newComponent.value });
    newComponent.value = { component_product_id: null, quantity_required: 1 };
};

const onUpdate = (component) => {
    emit('update-component', component);
};

const onDelete = (component) => {
    emit('delete-component', component);
};

const onAbort = () => {
    emit('abort');
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :style="{ width: '100%', maxWidth: '45rem' }"
        header="Receta / Componentes"
        :modal="true"
        :pt="appleModalStyles"
        :dismissableMask="canClose && !loading"
        :closable="canClose && !loading"
        :closeOnEscape="canClose && !loading"
    >
        <div class="flex flex-col mt-2" v-if="kit">

            <!-- Encabezado del producto padre -->
            <div class="flex items-center gap-4 bg-purple-50 dark:bg-purple-900/10 p-4 rounded-2xl border border-purple-100 dark:border-purple-800/30 mb-5">
                <Image
                    :src="kit.image_url || 'https://placehold.co/100x100/F4F4F5/A1A1AA?text=S/F'"
                    alt="Imagen"
                    width="50" height="50"
                    imageClass="rounded-lg object-cover h-[50px] w-[50px] shadow-sm border border-white dark:border-zinc-800"
                />
                <div class="flex flex-col">
                    <span class="text-xs font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400 mb-0.5">Producto a Fabricar</span>
                    <span class="font-bold text-lg text-zinc-900 dark:text-zinc-100 leading-tight">{{ kit.name }}</span>
                </div>
            </div>

            <!-- ADVERTENCIA AMARILLA (Obligación de insumo) -->
            <div v-if="components.length === 0 && !loading" class="mb-5 bg-amber-50 dark:bg-amber-900/20 p-4 rounded-2xl border border-amber-200 dark:border-amber-800/30 flex items-start gap-3">
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
                        v-model="newComponent.component_product_id"
                        :options="availableComponents"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Buscar material o insumo..."
                        filter
                        class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-900 shadow-sm !h-[48px] flex items-center"
                    />
                </div>
                <div class="w-full sm:w-32 flex flex-col gap-2">
                    <AppleInputNumber
                        v-model="newComponent.quantity_required" :min="0.01" :allowDecimals="true"
                    />
                </div>
                <div class="w-full sm:w-auto flex items-end">
                    <Button label="Añadir" icon="pi pi-plus" @click="onAdd" class="w-full !rounded-xl !bg-zinc-900 dark:!bg-zinc-100 !text-white dark:!text-zinc-900 hover:!bg-zinc-800 dark:hover:!bg-white !border-0 shadow-sm px-4 !h-[48px]" />
                </div>
            </div>

            <!-- Lista de Componentes Actuales -->
            <h4 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3 ml-1">Insumos Requeridos ({{ components.length }})</h4>

            <div v-if="loading" class="text-center p-6">
                <i class="pi pi-spin pi-spinner text-2xl text-zinc-400"></i>
            </div>

            <div v-else-if="components.length === 0" class="text-center p-8 bg-zinc-50 dark:bg-zinc-950 border border-dashed border-zinc-200 dark:border-zinc-800 rounded-2xl">
                <i class="pi pi-inbox text-3xl text-zinc-300 mb-2"></i>
                <p class="text-sm text-zinc-500">Este producto no tiene insumos configurados aún.</p>
            </div>

            <!-- Tabla (Escritorio) -->
            <div v-else class="hidden sm:block border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                <DataTable :value="components" class="zinc-table" responsiveLayout="scroll">
                    <Column header="Insumo">
                        <template #body="{ data }">
                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ data.component_product?.name || 'Desconocido' }}</span>
                        </template>
                    </Column>
                    <Column header="Stock Disp.">
                        <template #body="{ data }">
                            <span class="text-sm text-zinc-500">{{ data.component_product?.stock || 0 }} {{ data.component_product?.unit_of_measure || '' }}</span>
                        </template>
                    </Column>
                    <Column header="Req. p/Unidad" style="width: 140px;">
                        <template #body="slotProps">
                            <AppleInputNumber
                                v-model="slotProps.data.quantity_required"
                                :min="0.01"
                                :allowDecimals="true"
                                size="sm"
                            />
                        </template>
                    </Column>
                    <Column header="" style="width: 110px; text-align: right;">
                        <template #body="slotProps">
                            <div class="flex justify-end gap-1">
                                <Button icon="pi pi-check" class="!w-8 !h-8 !p-0 p-button-rounded p-button-text p-button-success" v-tooltip.top="'Guardar Cantidad'" @click="onUpdate(slotProps.data)" />
                                <Button icon="pi pi-trash" class="!w-8 !h-8 !p-0 p-button-rounded p-button-text p-button-danger" v-tooltip.top="'Quitar'" @click="onDelete(slotProps.data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>

            <!-- Vista Móvil para Insumos -->
            <div v-if="!loading && components.length > 0" class="sm:hidden flex flex-col gap-3">
                <div v-for="comp in components" :key="comp.id" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3 shadow-sm flex flex-col gap-3">
                    <div class="flex justify-between items-start">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm leading-tight pr-4">{{ comp.component_product?.name || 'Desconocido' }}</span>
                        <Button icon="pi pi-trash" class="!w-8 !h-8 !p-0 !bg-red-50 dark:!bg-red-900/20 !text-red-500 !border-0 shrink-0" @click="onDelete(comp)" />
                    </div>
                    <div class="flex items-end justify-between gap-3">
                        <div class="flex flex-col">
                            <span class="text-[0.65rem] text-zinc-500 uppercase tracking-wider mb-1">Stock Disp.</span>
                            <span class="text-sm font-medium">{{ comp.component_product?.stock || 0 }} <span class="text-xs">{{ comp.component_product?.unit_of_measure || '' }}</span></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <span class="text-[0.65rem] text-zinc-500 uppercase tracking-wider mb-1">Requerido</span>
                                <div class="w-28">
                                    <AppleInputNumber
                                        v-model="comp.quantity_required"
                                        :min="0.01"
                                        :allowDecimals="true"
                                        size="sm"
                                    />
                                </div>
                            </div>
                            <Button icon="pi pi-check" class="!w-9 !h-9 !mt-4 !p-0 !bg-emerald-50 dark:!bg-emerald-900/20 !text-emerald-600 !border-0 shrink-0" @click="onUpdate(comp)" />
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <template #footer>
            <div class="flex flex-col sm:flex-row justify-between w-full gap-3">
                <Button
                    v-if="components.length === 0 && !loading"
                    label="Abortar y Eliminar Producto"
                    icon="pi pi-trash"
                    @click="onAbort"
                    class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-red-50 dark:!bg-red-900/30 !text-red-600 dark:!text-red-400 hover:!bg-red-100 dark:hover:!bg-red-900/50 !border-0 font-medium transition-colors"
                />
                <div v-else class="hidden sm:block"></div>

                <Button
                    label="Guardar y Cerrar Panel"
                    @click="visible = false"
                    :disabled="components.length === 0 || loading"
                    class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-900 dark:!bg-zinc-100 !text-white dark:!text-zinc-900 hover:!bg-zinc-800 dark:hover:!bg-white !border-0 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                />
            </div>
        </template>
    </Dialog>
</template>
