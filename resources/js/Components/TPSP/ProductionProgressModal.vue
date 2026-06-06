<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    visible: Boolean,
    order: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const isSubmitting = ref(false);
const quantity = ref(1);

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

watch(() => props.visible, (val) => {
    if (val) {
        quantity.value = 1;
    }
});

const maxAllowed = () => {
    if (!props.order) return 0;
    return props.order.quantity_requested - props.order.quantity_produced;
};

const submit = async () => {
    if (!props.order || !quantity.value || quantity.value <= 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'La cantidad debe ser mayor a 0.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.post(`/tpsp/production-orders/${props.order.id}/add-progress`, { quantity: quantity.value });
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Progreso agregado correctamente.', life: 3000 });
        visible.value = false;
        emit('saved');
    } catch (error) {
        const detail = error.response?.data?.message || 'Error desconocido al agregar progreso.';
        toast.add({ severity: 'error', summary: 'Error', detail: detail, life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Agregar Producción"
        :style="{ width: '100%', maxWidth: '26rem', margin: '1rem' }"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-5 mt-2" v-if="order">
            <div class="bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-800/30">
                <span class="font-bold text-blue-900 dark:text-blue-100 block mb-2 text-lg">{{ order.product.name }}</span>
                <div class="flex justify-between text-sm">
                    <span class="text-blue-600/80">Meta: <strong class="text-blue-800 dark:text-blue-200">{{ order.quantity_requested }}</strong></span>
                    <span class="text-blue-600/80">Producido: <strong class="text-blue-800 dark:text-blue-200">{{ order.quantity_produced }}</strong></span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label for="progressQty" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad Nueva a Reportar</label>
                <InputNumber
                    id="progressQty"
                    v-model="quantity"
                    mode="decimal" :min="1" :max="maxAllowed()"
                    class="w-full"
                    inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 text-lg font-medium text-center"
                />
                <small class="text-zinc-500 ml-1 text-center mt-1">
                    Máximo que falta producir: <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ maxAllowed() }}</span>
                </small>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
            <Button label="Guardar Producción" @click="submit" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)] font-medium" />
        </template>
    </Dialog>
</template>
