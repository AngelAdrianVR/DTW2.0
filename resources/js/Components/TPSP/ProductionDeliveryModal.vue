<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
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
const quantity = ref(null);
const deliveryDate = ref(new Date());
const unitPrice = ref(null);

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const availableToDeliver = () => {
    if (!props.order) return 0;
    return props.order.quantity_produced - (props.order.quantity_delivered || 0);
};

watch(() => props.visible, (val) => {
    if (val && props.order) {
        quantity.value = availableToDeliver();
        deliveryDate.value = new Date();
        unitPrice.value = null;
    }
});

const submit = async () => {
    if (!props.order || !deliveryDate.value || unitPrice.value == null || unitPrice.value < 0 || !quantity.value || quantity.value <= 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Complete todos los campos correctamente.', life: 3000 });
        return;
    }

    if (quantity.value > availableToDeliver()) {
        toast.add({ severity: 'error', summary: 'Cantidad excedida', detail: `Solo puedes entregar hasta ${availableToDeliver()} unidades.`, life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.post(`/tpsp/production-orders/${props.order.id}/deliver`, {
            quantity: quantity.value,
            delivery_date: deliveryDate.value,
            unit_price: unitPrice.value,
        });
        toast.add({ severity: 'success', summary: 'Éxito', detail: 'Entrega registrada correctamente.', life: 3000 });
        visible.value = false;
        emit('saved');
    } catch (error) {
        const detail = error.response?.data?.message || 'Error desconocido al entregar la orden.';
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
        header="Registrar Entrega (Venta)"
        :style="{ width: '100%', maxWidth: '28rem', margin: '1rem' }"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-5 mt-2" v-if="order">
            <div class="bg-emerald-50 dark:bg-emerald-900/20 p-4 rounded-2xl border border-emerald-100 dark:border-emerald-800/30">
                <span class="font-bold text-emerald-800 dark:text-emerald-300 block mb-2">{{ order.product.name }}</span>
                <div class="grid grid-cols-2 gap-2 text-xs text-emerald-700 dark:text-emerald-400">
                    <div class="flex flex-col"><span class="opacity-70">Total Producido:</span><span class="font-bold text-sm">{{ order.quantity_produced }}</span></div>
                    <div class="flex flex-col"><span class="opacity-70">Ya Entregado:</span><span class="font-bold text-sm">{{ order.quantity_delivered || 0 }}</span></div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex justify-between items-end">
                    <label for="deliverQty" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Cantidad a Entregar Hoy</label>
                    <span class="text-xs text-emerald-600 font-bold bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 rounded-md">Disp: {{ availableToDeliver() }}</span>
                </div>
                <InputNumber
                    id="deliverQty"
                    v-model="quantity"
                    mode="decimal" :min="1" :max="availableToDeliver()"
                    class="w-full"
                    inputClass="!w-full !rounded-xl !border-emerald-200 dark:!border-emerald-800/50 dark:!bg-zinc-950 dark:!text-emerald-400 shadow-sm p-3 text-lg font-bold text-center"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                    <label for="deliveryDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha</label>
                    <Calendar
                        id="deliveryDate"
                        v-model="deliveryDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3"
                    />
                </div>

                <div class="flex flex-col gap-2">
                    <label for="unitPrice" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Precio Unit.</label>
                    <InputNumber
                        id="unitPrice"
                        v-model="unitPrice"
                        mode="currency" currency="MXN" locale="es-MX"
                        class="w-full"
                        inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 font-medium"
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
            <Button label="Registrar Entrega" @click="submit" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-[var(--primary-text-color)]" />
        </template>
    </Dialog>
</template>
