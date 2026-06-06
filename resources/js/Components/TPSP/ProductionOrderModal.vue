<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import InputNumber from 'primevue/inputnumber';
import InputText from 'primevue/inputtext';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    visible: Boolean,
    orderData: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const isEditing = ref(false);
const kitProducts = ref([]);
const loading = ref(false);
const warnings = ref([]);

const form = ref({
    product_id: null,
    quantity_requested: 1,
    due_date: null,
});

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const fetchKitProducts = async () => {
    try {
        const response = await axios.get('/tpsp/products', { params: { is_kit: true } });
        kitProducts.value = response.data;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los kits', life: 3000 });
    }
};

const resetForm = () => {
    form.value = { product_id: null, quantity_requested: 1, due_date: null };
    isEditing.value = false;
    warnings.value = [];
};

// Al abrir: si hay orderData, es edición; si no, es creación
watch(() => props.visible, async (val) => {
    if (val) {
        await fetchKitProducts();
        if (props.orderData) {
            isEditing.value = true;
            form.value = {
                product_id: props.orderData.product_id,
                quantity_requested: props.orderData.quantity_requested,
                due_date: props.orderData.due_date || null,
            };
        } else {
            resetForm();
        }
    }
});

const submit = async () => {
    loading.value = true;
    warnings.value = [];
    try {
        if (isEditing.value) {
            const response = await axios.put(`/tpsp/production-orders/${props.orderData.id}`, form.value);
            if (response.data.warnings && response.data.warnings.length > 0) {
                warnings.value = response.data.warnings;
                toast.add({ severity: 'warn', summary: 'Actualizado con alertas', detail: 'Revisa las advertencias de inventario.', life: 8000 });
            } else {
                toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden actualizada correctamente.', life: 3000 });
                emit('update:visible', false);
                emit('saved');
            }
        } else {
            const response = await axios.post('/tpsp/production-orders', form.value);
            if (response.data.warnings && response.data.warnings.length > 0) {
                warnings.value = response.data.warnings;
                toast.add({ severity: 'warn', summary: 'Creado con alertas', detail: 'Revisa las advertencias de inventario.', life: 8000 });
            } else {
                toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden de producción creada.', life: 3000 });
                emit('update:visible', false);
                emit('saved');
            }
        }
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo guardar la orden.', life: 4000 });
    } finally {
        loading.value = false;
    }
};

const acknowledgeWarnings = () => {
    warnings.value = [];
    emit('update:visible', false);
    emit('saved');
};

</script>

<template>
    <Dialog
        v-model:visible="visible"
        :modal="true"
        :header="isEditing ? 'Editar Orden de Producción' : 'Nueva Orden de Producción'"
        :style="{ width: '100%', maxWidth: '32rem', margin: '1rem' }"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-5 mt-2">
            <div class="flex flex-col gap-2">
                <label for="orderProduct" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Producto Terminado</label>
                <Dropdown
                    id="orderProduct"
                    v-model="form.product_id"
                    :options="kitProducts"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Selecciona un producto"
                    :disabled="isEditing"
                    class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm"
                    :pt="{ input: { class: 'dark:text-zinc-200' }, panel: { class: 'rounded-xl shadow-lg border-0 dark:bg-zinc-800' } }"
                />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="flex flex-col gap-2">
                    <label for="orderQuantity" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Producir</label>
                    <InputNumber
                        id="orderQuantity"
                        v-model="form.quantity_requested"
                        mode="decimal"
                        :min="1"
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3"
                    />
                </div>
                <div class="flex flex-col gap-2">
                    <label for="orderDueDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha de entrega</label>
                    <InputText
                        id="orderDueDate"
                        v-model="form.due_date"
                        type="date"
                        class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3"
                    />
                </div>
            </div>

            <!-- Panel de Advertencias de Inventario -->
            <div v-if="warnings.length > 0" class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 rounded-2xl p-4 flex flex-col gap-3">
                <div class="flex items-center gap-2">
                    <i class="pi pi-exclamation-triangle text-amber-600 dark:text-amber-400 text-lg"></i>
                    <span class="font-bold text-amber-800 dark:text-amber-300 text-sm">Alertas de Inventario</span>
                </div>
                <ul class="list-disc list-inside flex flex-col gap-1.5">
                    <li v-for="(w, i) in warnings" :key="i" class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">{{ w }}</li>
                </ul>
                <p class="text-[0.65rem] text-amber-600/70 dark:text-amber-500/60 mt-1">
                    La orden se guardó correctamente. Los materiales se ajustaron. Verifica que el proveedor entregue el faltante.
                </p>
            </div>
        </div>
        <template #footer>
            <template v-if="warnings.length > 0">
                <Button label="Entendido, Cerrar" @click="acknowledgeWarnings" class="!px-5 !py-2.5 !rounded-xl !bg-amber-500 hover:!bg-amber-600 !text-white !border-0 font-medium mt-4" />
            </template>
            <template v-else>
                <Button label="Cancelar" @click="visible = false" class="!px-5 !py-2.5 !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium mt-4" />
                <Button :label="isEditing ? 'Actualizar Orden' : 'Crear Orden'" @click="submit" :loading="loading" class="!px-5 !py-2.5 !rounded-xl !text-[var(--primary-text-color)] font-medium mt-4" />
            </template>
        </template>
    </Dialog>
</template>
