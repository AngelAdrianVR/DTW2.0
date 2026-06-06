<script setup>
import { computed } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    visible: Boolean,
    product: { type: Object, default: null },
    movements: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['update:visible']);

const visible = computed({
    get: () => props.visible,
    set: (val) => emit('update:visible', val),
});

const appleModalStyles = {
    root: { class: 'bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl overflow-hidden border-0' },
    header: { class: 'px-6 py-5 border-b border-zinc-100 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md text-xl font-semibold text-zinc-900 dark:text-zinc-100' },
    content: { class: 'p-6 bg-white dark:bg-zinc-900' },
    footer: { class: 'px-6 py-4 bg-zinc-50 dark:bg-zinc-900/50 flex flex-col sm:flex-row justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800' },
    mask: { class: 'backdrop-blur-sm bg-zinc-900/30 dark:bg-zinc-900/70 transition-all duration-300' }
};

const formatDateTime = (dateString) => {
    if (!dateString) return '';
    const d = new Date(dateString);
    return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }).replace('.', '');
};

const getMovementSeverity = (type) => {
    if (['Compra', 'Entrada_Produccion', 'Entrada de material', 'Devolución de producto'].includes(type)) return 'success';
    if (['Venta', 'Consumo_Produccion'].includes(type)) return 'danger';
    return 'info';
};

</script>

<template>
    <Dialog
        v-model:visible="visible"
        :style="{ width: '100%', maxWidth: '48rem' }"
        header="Historial de Movimientos"
        :modal="true"
        :pt="appleModalStyles"
        :dismissableMask="true"
    >
        <div class="flex flex-col gap-5 mt-2">
            <div v-if="product" class="flex items-center gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-2xl border border-zinc-100 dark:border-zinc-800/80 mb-2">
                <div class="bg-indigo-50 dark:bg-indigo-900/20 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800/30">
                    <i class="pi pi-history text-indigo-500 dark:text-indigo-400 text-xl"></i>
                </div>
                <div class="flex flex-col">
                    <span class="font-bold text-zinc-900 dark:text-zinc-100">{{ product.name }}</span>
                    <span class="text-sm text-zinc-500">Últimos registros de stock Físico</span>
                </div>
            </div>

            <div class="border border-zinc-100 dark:border-zinc-800 rounded-2xl overflow-hidden">
                <DataTable :value="movements" :loading="loading" class="zinc-table" :paginator="true" :rows="5" responsiveLayout="scroll">
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
            <Button label="Cerrar Historial" @click="visible = false" class="!px-5 !py-3 w-full sm:w-auto !rounded-xl !bg-zinc-100 dark:!bg-zinc-800 hover:!bg-zinc-200 dark:hover:!bg-zinc-700 !text-zinc-800 dark:!text-zinc-200 !border-0 font-medium transition-colors" />
        </template>
    </Dialog>
</template>
