<script setup>
import { ref, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    visible: Boolean,
    delivery: { type: Object, default: null },
});

const emit = defineEmits(['update:visible', 'saved']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const isSubmitting = ref(false);
const amountPaid = ref(0);
const paymentDate = ref(new Date());

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const formatCurrency = (value) => {
    if (value === null || value === undefined) return '-';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

watch(() => props.visible, (val) => {
    if (val && props.delivery) {
        amountPaid.value = props.delivery.amount_paid !== null ? parseFloat(props.delivery.amount_paid) : 0;
        paymentDate.value = props.delivery.payment_date ? new Date(props.delivery.payment_date) : new Date();
    }
});

const setFullPayment = () => {
    if (props.delivery) {
        amountPaid.value = parseFloat(props.delivery.total_price);
    }
};

const submit = async () => {
    if (amountPaid.value === null || amountPaid.value < 0) {
        toast.add({ severity: 'warn', summary: 'Datos inválidos', detail: 'Ingrese un monto válido.', life: 3000 });
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.put(`/tpsp/production-orders/deliveries/${props.delivery.id}/pay`, {
            amount_paid: amountPaid.value,
            payment_date: paymentDate.value,
        });
        toast.add({ severity: 'success', summary: 'Pago registrado', detail: 'Se actualizó el pago de la entrega correctamente.', life: 3000 });
        visible.value = false;
        emit('saved');
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo registrar el pago.', life: 4000 });
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Registrar Pago"
        :style="{ width: '100%', maxWidth: '24rem', margin: '1rem' }"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-4 mt-2" v-if="delivery">
            <div class="bg-amber-50 dark:bg-amber-900/10 p-4 rounded-2xl border border-amber-100 dark:border-amber-800/30 text-sm">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-amber-700 dark:text-amber-400 font-medium">Total de la Entrega:</span>
                    <span class="font-bold text-amber-900 dark:text-amber-200">{{ formatCurrency(delivery.total_price) }}</span>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <div class="flex justify-between items-end">
                    <label for="amountPaid" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Monto Pagado / Abonado</label>
                    <button @click="setFullPayment" class="text-[0.65rem] font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer">Liquidar todo</button>
                </div>
                <InputNumber
                    id="amountPaid"
                    v-model="amountPaid"
                    mode="currency" currency="MXN" locale="es-MX"
                    class="w-full"
                    inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3 font-bold text-center text-lg"
                />
            </div>

            <div class="flex flex-col gap-2">
                <label for="paymentDate" class="text-sm font-medium text-zinc-700 dark:text-zinc-300 ml-1">Fecha del Pago</label>
                <Calendar
                    id="paymentDate"
                    v-model="paymentDate"
                    dateFormat="dd/mm/yy"
                    class="w-full"
                    inputClass="!w-full !rounded-xl !border-zinc-200 dark:!border-zinc-700 dark:!bg-zinc-950 dark:!text-zinc-100 shadow-sm p-3"
                />
            </div>
        </div>

        <template #footer>
            <Button label="Cancelar" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !text-zinc-600 dark:!text-zinc-300 hover:!bg-zinc-100 dark:hover:!bg-zinc-800 !bg-transparent !border-0 font-medium" />
            <Button label="Guardar Pago" @click="submit" :loading="isSubmitting" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-[var(--primary-color)] !text-[var(--primary-text-color)]" />
        </template>
    </Dialog>
</template>
