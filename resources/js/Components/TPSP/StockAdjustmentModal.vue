<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import AppleInputNumber from '@/Components/AppleInputNumber.vue';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    visible: Boolean,
    product: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const movementTypes = ['Ajuste', 'Compra', 'Venta', 'Entrada_Produccion', 'Consumo_Produccion', 'Entrada de material'];

const movementData = ref({ product_id: null, quantity: 0, type: 'Ajuste', notes: '' });
const movementLoading = ref(false);

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

watch(() => props.visible, (val) => {
    if (val && props.product) {
        movementData.value = { product_id: props.product.id, quantity: 0, type: 'Ajuste', notes: '' };
        movementLoading.value = false;
    }
});

const submit = async () => {
    if (movementData.value.quantity === 0) {
        toast.add({ severity: 'warn', summary: 'Advertencia', detail: 'La cantidad no puede ser cero', life: 3000 });
        return;
    }

    movementLoading.value = true;
    try {
        await axios.post(`/tpsp/products/${movementData.value.product_id}/adjust-stock`, {
            quantity: movementData.value.quantity,
            type: movementData.value.type,
            notes: movementData.value.notes,
        });

        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Inventario actualizado correctamente', life: 3000 });
        visible.value = false;
        emit('saved');
    } catch (error) {
        console.error("Error ajustando stock:", error.response?.data || error);
        toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'No se pudo registrar el movimiento', life: 5000 });
    } finally {
        movementLoading.value = false;
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        :style="{ width: '100%', maxWidth: '28rem' }"
        header="Ajuste de Inventario Físico"
        :modal="true"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-5 mt-2">
            <div v-if="product" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80">
                <div class="bg-white dark:bg-zinc-900 px-3 pt-3 pb-2 rounded-xl border border-zinc-100 dark:border-zinc-800 shadow-sm">
                    <i class="pi pi-box text-zinc-400" style="font-size: 18px;"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ product.name }}</span>
                    <span class="text-md text-zinc-500">Stock Físico: <strong class="text-zinc-700 dark:text-zinc-300">{{ product.stock }} {{ product.unit_of_measure }}</strong></span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="movementQuantity" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Ajustar</label>
                <AppleInputNumber
                    v-model="movementData.quantity" :allowDecimals="true" class="!border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm"
                />
                <div class="flex justify-between items-center text-[0.90rem] mt-1 px-1">
                    <span class="text-red-500/80 bg-red-50 dark:bg-red-900/10 px-2 py-0.5 rounded-md font-medium"><i class="pi pi-minus mr-1" style="font-size: 0.8rem"></i>Negativo = Salida</span>
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
                    class="w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 shadow-sm !h-[48px] flex items-center"
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
            <Button label="Cancelar" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium transition-colors" />
            <Button label="Confirmar Ajuste" @click="submit" :loading="movementLoading" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium transition-all" />
        </template>
    </Dialog>
</template>
