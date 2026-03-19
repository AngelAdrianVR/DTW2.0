<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Pagination from '@/Components/MyComponents/Pagination.vue';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Tooltip from 'primevue/tooltip';
import InputText from 'primevue/inputtext';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import Card from 'primevue/card';

// --- DIRECTIVES ---
const vTooltip = Tooltip;

// --- PROPS ---
const props = defineProps({
    quotes: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    }
});

// --- HELPER FUNCTIONS ---
const debounce = (func, delay = 300) => {
    let timeout;
    return (...args) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(this, args);
        }, delay);
    };
};

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const menu = ref();
const selectedQuoteForMenu = ref(null);
const search = ref(props.filters.search || '');
const isPaymentDialogVisible = ref(false);
const selectedQuoteForPayment = ref(null);

// --- FORMS ---
const paymentForm = useForm({
    quote_id: null,
    client_id: null,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    receipt: null,
});

// --- WATCHERS ---
watch(search, debounce((value) => {
    router.get(route('quotes.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));


// --- MENU ACTIONS ---
const menuItems = computed(() => {
    if (!selectedQuoteForMenu.value) return [];

    const quote = selectedQuoteForMenu.value;
    let statusActions = [];

    if (quote.status === 'Pendiente') {
        statusActions.push({
            label: 'Marcar como Enviado',
            icon: 'pi pi-send',
            command: () => changeQuoteStatus(quote, 'Enviado')
        });
    } else if (quote.status === 'Enviado' || quote.status === 'Aceptado' || quote.status === 'Rechazado') {
        statusActions.push({
            label: 'Marcar como Aceptado',
            icon: 'pi pi-check',
            command: () => changeQuoteStatus(quote, 'Aceptado')
        }, {
            label: 'Marcar como Rechazado',
            icon: 'pi pi-times',
            command: () => changeQuoteStatus(quote, 'Rechazado')
        });
    }

    return [
        {
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(route('quotes.show', quote.id))
        },
        {
            label: 'Editar Cotización',
            icon: 'pi pi-pencil',
            command: () => router.get(route('quotes.edit', quote.id)),
            visible: !['Pagado'].includes(quote.status)
        },
        {
            label: 'Crear Proyecto',
            icon: 'pi pi-folder-plus',
            command: () => router.get(route('projects.create', { quote_id: quote.id })),
            visible: quote.status === 'Aceptado' && !quote.project_id
        },
        {
            label: 'Imprimir',
            icon: 'pi pi-print',
            command: () => window.open(route('quotes.print', quote.id), '_blank')
        },
        {
            label: 'Agregar Pago',
            icon: 'pi pi-dollar',
            command: () => openPaymentDialog(quote),
            visible: quote.status === 'Aceptado'
        },
        {
            label: 'Cambiar Estado',
            icon: 'pi pi-sort-alt',
            items: statusActions,
            visible: statusActions.length > 0
        },
        {
            separator: true,
        },
        {
            label: 'Eliminar Cotización',
            icon: 'pi pi-trash',
            command: () => confirmDeleteQuote(quote),
            visible: !['Aceptado', 'Pagado'].includes(quote.status)
        }
    ];
});

const toggleMenu = (event, quote) => {
    selectedQuoteForMenu.value = quote;
    menu.value.toggle(event);
};

// --- METHODS ---
const openPaymentDialog = (quote) => {
    selectedQuoteForPayment.value = quote;
    paymentForm.reset();
    paymentForm.quote_id = quote.id;
    paymentForm.client_id = quote.client_id;
    const remainingBalance = quote.final_amount - (quote.total_paid || 0);
    paymentForm.amount = remainingBalance > 0 ? remainingBalance : null;
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
};

const submitPayment = () => {
    paymentForm.post(route('client-payments.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Pago registrado correctamente', life: 3000 });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({ severity: 'error', summary: 'Error', detail: errorMessages || 'No se pudo registrar el pago.', life: 3000 });
        }
    });
};

const changeQuoteStatus = (quote, newStatus) => {
    router.put(route('quotes.updateStatus', { quote: quote.id }), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estado actualizado correctamente.', life: 3000 });
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).join(' ');
            toast.add({ severity: 'error', summary: 'Error', detail: errorMessage || 'No se pudo actualizar el estado.', life: 3000 });
        }
    });
};

const confirmDeleteQuote = (quote) => {
    confirm.require({
        message: `¿Estás seguro de que quieres eliminar la cotización "Cot-${quote.id}"?`,
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('quotes.destroy', { quote: quote.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización eliminada correctamente', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar la cotización.', life: 3000 });
                }
            });
        }
    });
};

const onRowClick = (event) => {
     router.get(route('quotes.show', event.data.id));
};

const rowClass = () => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition-colors';

// --- HELPERS ---
const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusSeverity = (status) => {
    const statuses = {
        'Pendiente': 'warn',
        'Enviado': 'info',
        'Aceptado': 'success',
        'Rechazado': 'danger',
        'Pagado': 'success'
    };
    return statuses[status] || 'secondary';
};

const getStatusIcon = (status) => {
    const icons = {
        'Pendiente': 'pi pi-clock',
        'Enviado': 'pi pi-send',
        'Aceptado': 'pi pi-check-circle',
        'Rechazado': 'pi pi-times-circle',
        'Pagado': 'pi pi-verified'
    };
    return icons[status] || 'pi pi-question-circle';
};

</script>

<template>
    <AppLayout title="Cotizaciones">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <header class="mb-8">
                    <div>
                        <h1 class="text-3xl font-bold dark:text-zinc-100 text-[#212121]">Módulo de Cotizaciones</h1>
                        <p class="text-gray-400 dark:text-zinc-400 mt-1">Gestiona todas tus cotizaciones y su estado.</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-zinc-900 shadow-sm border border-gray-100 dark:border-zinc-800 rounded-2xl p-4 md:p-6 overflow-hidden">
                    <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
                        <IconField class="w-full md:w-1/3">
                            <InputIcon class="pi pi-search text-gray-400" />
                            <InputText 
                                v-model="search" 
                                placeholder="Buscar por Folio, Cliente o Estado..." 
                                class="w-full" 
                            />
                        </IconField>
                        <Link :href="route('quotes.create')">
                            <Button label="Crear Cotización" icon="pi pi-plus" class="!text-[var(--primary-text-color)]" />
                        </Link>
                    </div>

                    <!-- Vista de Tabla para Escritorio -->
                    <div class="hidden md:block">
                        <DataTable :value="quotes.data" paginator :rows="15" stripedRows tableStyle="min-width: 50rem;"
                            @row-click="onRowClick" selectionMode="single" dataKey="id" :rowClass="rowClass" class="index-quotes-table">
                            <template #empty> <div class="p-4 text-center text-gray-500">No se encontraron cotizaciones.</div> </template>

                            <Column header="Folio" style="width: 10%">
                                <template #body="{ data }">
                                   <div class="flex items-center gap-2">
                                        <i :class="getStatusIcon(data.status)" :title="data.status" class="text-gray-400 dark:text-zinc-500"></i>
                                        <span class="text-gray-600 dark:text-zinc-400">Cot-{{ data.id }}</span>
                                   </div>
                                </template>
                            </Column>
                            <Column field="client" header="Cliente" sortable>
                                <template #body="{ data }">
                                    <div class="font-semibold text-gray-800 dark:text-zinc-200">{{ data.client?.name || data.client_name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-zinc-500">{{ data.client?.tax_id || data.origin }}</div>
                                </template>
                            </Column>
                             <Column field="title" header="Título" sortable>
                                 <template #body="{ data }"><span class="text-gray-700 dark:text-zinc-300">{{ data.title }}</span></template>
                             </Column>
                            <Column field="amount" header="Monto" sortable class="text-right">
                                <template #body="{ data }">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="font-semibold text-gray-800 dark:text-zinc-200">{{ formatCurrency(data.final_amount) }}</span>
                                        <i v-if="data.percentage_discount && data.percentage_discount > 0"
                                           class="pi pi-info-circle text-gray-400 cursor-pointer"
                                           v-tooltip.left="{
                                               value: `Subtotal: ${formatCurrency(data.amount)} <br/> Descuento: ${data.percentage_discount}% (${formatCurrency(data.amount - data.final_amount)})`,
                                               escape: false,
                                               class: 'custom-tooltip'
                                           }">
                                        </i>
                                    </div>
                                </template>
                            </Column>
                            <Column header="Pagado" sortable class="text-right">
                                <template #body="{ data }">
                                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ formatCurrency(data.total_paid) }}</span>
                                </template>
                            </Column>
                            <Column header="Saldo" sortable class="text-right">
                                <template #body="{ data }">
                                    <span class="font-bold" :class="[(data.final_amount - (data.total_paid || 0)) > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-400 dark:text-zinc-500']">
                                        {{ formatCurrency(data.final_amount - (data.total_paid || 0)) }}
                                    </span>
                                </template>
                            </Column>
                             <Column field="status" header="Estado" sortable>
                                <template #body="{ data }">
                                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                                </template>
                            </Column>
                             <Column field="project_id" header="Proyecto">
                                <template #body="{ data }">
                                    <p class="text-blue-500 hover:text-blue-400 hover:underline cursor-pointer" @click.stop="$inertia.visit(route('projects.show', data.project_id))" v-if="data.project_id">{{ data.project.name }}</p>
                                    <p v-else class="text-gray-400">-</p>
                                </template>
                            </Column>
                            <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                                <template #body="{ data }">
                                    <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                        aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" class="!text-gray-500 dark:!text-zinc-400 hover:!bg-gray-100 dark:hover:!bg-zinc-800" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                    <Pagination :links="quotes.links" />

                    <!-- Vista de Tarjetas para Móvil -->
                    <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <Card v-for="quote in quotes.data" :key="quote.id"
                            class="cursor-pointer dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm !rounded-xl" @click="onRowClick({data: quote})">
                            <template #title>
                                <div class="flex justify-between items-start">
                                    <span class="text-lg font-bold text-gray-800 dark:text-zinc-200">Cot-{{ quote.id }}</span>
                                    <Tag :value="quote.status" :severity="getStatusSeverity(quote.status)" />
                                </div>
                            </template>
                            <template #subtitle><span class="text-gray-500 dark:text-zinc-500">{{ quote.client?.name || quote.client_name }}</span></template>
                            <template #content>
                                <p class="font-semibold text-gray-700 dark:text-zinc-300 mb-2">{{ quote.title }}</p>
                                <ul class="space-y-2 text-sm text-gray-600 dark:text-zinc-400">
                                    <li class="flex justify-between border-t border-gray-100 dark:border-zinc-800 pt-2 mt-2">
                                        <span class="font-bold">Monto:</span>
                                        <span class="font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(quote.final_amount) }}</span>
                                    </li>
                                     <li class="flex justify-between">
                                        <span class="text-emerald-600 dark:text-emerald-400">Pagado:</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ formatCurrency(quote.total_paid) }}</span>
                                    </li>
                                    <li class="flex justify-between">
                                        <span class="font-semibold" :class="[(quote.final_amount - (quote.total_paid || 0)) > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-zinc-400']">Saldo:</span>
                                        <span class="font-semibold" :class="[(quote.final_amount - (quote.total_paid || 0)) > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-gray-700 dark:text-zinc-400']">{{ formatCurrency(quote.final_amount - (quote.total_paid || 0)) }}</span>
                                    </li>
                                </ul>
                            </template>
                            <template #footer>
                                <div class="flex justify-end">
                                    <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, quote)"
                                        aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" size="small" outlined />
                                </div>
                            </template>
                        </Card>
                         <div v-if="quotes.data.length === 0" class="text-center text-gray-500 dark:text-zinc-500 col-span-full mt-8">
                            No se encontraron cotizaciones.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diálogo Modal para Agregar Pago (Apple Style) -->
        <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '28rem' }"
            :pt="{ 
                root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0' }, 
                header: { class: 'pt-8 px-8 pb-0 bg-transparent rounded-t-[2rem] dark:text-zinc-100' }, 
                content: { class: 'px-8 pb-8 pt-4 bg-transparent rounded-b-[2rem]' } 
            }">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                        <i class="pi pi-dollar text-emerald-600 dark:text-emerald-400 text-lg font-bold"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-gray-800 dark:text-white tracking-tight">Registrar Pago</span>
                        <span class="text-xs text-gray-500 dark:text-zinc-400">Cot-{{ selectedQuoteForPayment?.id }}</span>
                    </div>
                </div>
            </template>
            <form @submit.prevent="submitPayment">
                <div class="flex flex-col gap-5 mt-2">
                    <div class="flex flex-col gap-2">
                        <label for="amount" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Monto del Pago <span class="text-red-500">*</span></label>
                        <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN"
                            locale="es-MX" class="!rounded-xl w-full" :class="{ 'p-invalid': paymentForm.errors.amount }" required />
                        <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="payment_date" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Fecha del Pago <span class="text-red-500">*</span></label>
                        <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" class="!rounded-xl w-full"
                            :class="{ 'p-invalid': paymentForm.errors.payment_date }" required />
                        <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="notes" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Notas (Opcional)</label>
                        <Textarea id="notes" v-model="paymentForm.notes" rows="2" class="!rounded-xl w-full" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="receipt" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Comprobante (Opcional)</label>
                        <input type="file" id="receipt" @input="paymentForm.receipt = $event.target.files[0]" 
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-zinc-800 dark:file:text-emerald-400 transition-colors cursor-pointer" 
                            accept=".pdf,.jpg,.jpeg,.png" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-3 mt-4 w-full">
                    <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" class="!rounded-xl font-medium" />
                    <Button label="Guardar Pago" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" class="!rounded-xl font-medium bg-emerald-600 border-emerald-600 hover:bg-emerald-700 !text-[var(--primary-text-color)]" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
.p-tag {
    text-transform: capitalize;
}
.p-datatable .p-column-header-content {
    justify-content: space-between;
}
.custom-tooltip .p-tooltip-text {
  text-align: left;
  white-space: pre-wrap;
}
.index-quotes-table {
    color:#212121 !important
}

</style>

<style>
/* Estilos globales para la tabla de INDEX */
.index-quotes-table .p-datatable-thead > tr > th {
    background-color: transparent!important;
    color: #52525b !important;
    
}

.index-quotes-table .p-datatable-tbody > tr { 
    background-color: transparent !important; 
}


html.dark .index-quotes-table .p-datatable-tbody > tr:not(:last-child) > td,
.dark .index-quotes-table .p-datatable-tbody > tr:not(:last-child) > td { 
    border-bottom: 1px solid #27272a !important; 
}
</style>