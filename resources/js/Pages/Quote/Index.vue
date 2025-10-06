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

// --- DIRECTIVES ---
// Directiva para el tooltip de PrimeVue
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
    } else if (quote.status === 'Enviado') {
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
            label: 'Agregar Pago',
            icon: 'pi pi-dollar',
            command: () => openPaymentDialog(quote),
            visible: quote.status === 'Aceptado'
        },
        {
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(route('quotes.show', quote.id))
        },
        {
            label: 'Editar Cotización',
            icon: 'pi pi-pencil',
            command: () => router.get(route('quotes.edit', quote.id)),
            visible: !['Aceptado', 'Pagado'].includes(quote.status)
        },
        {
            label: 'Cambiar Estado',
            icon: 'pi pi-sort-alt',
            items: statusActions,
            visible: statusActions.length > 0
        },
        {
            separator: true,
            visible: quote.status === 'Aceptado'
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
    // MODIFICACIÓN: Se usa 'final_amount' para calcular el saldo restante real.
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
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Pago registrado correctamente',
                life: 3000
            });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessages || 'No se pudo registrar el pago. Revisa los datos.',
                life: 3000
            });
        }
    });
};

const changeQuoteStatus = (quote, newStatus) => {
    router.put(route('quotes.updateStatus', { quote: quote.id }), { status: newStatus }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Éxito',
                detail: 'Estado actualizado correctamente.',
                life: 3000
            });
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).join(' ');
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: errorMessage || 'No se pudo actualizar el estado.',
                life: 3000
            });
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
                    toast.add({
                        severity: 'success',
                        summary: 'Éxito',
                        detail: 'Cotización eliminada correctamente',
                        life: 3000
                    });
                },
                onError: () => {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'No se pudo eliminar la cotización.',
                        life: 3000
                    });
                }
            });
        }
    });
};

const onRowClick = (event) => {
     router.get(route('quotes.show', event.data.id));
};

const rowClass = () => 'cursor-pointer';

// --- HELPERS ---
const formatCurrency = (value) => {
    if (value === null || isNaN(value)) {
        value = 0;
    }
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    return date.toLocaleDateString('es-MX', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
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
                        <h1 class="text-3xl font-bold dark:text-gray-200 text-gray-800">Módulo de Cotizaciones</h1>
                        <p class="text-gray-400 mt-1">Gestiona todas tus cotizaciones y su estado.</p>
                    </div>
                </header>

                <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 md:p-6">
                    <div class="flex justify-between items-center flex-wrap gap-4 mb-4">
                        <span class="p-input-icon-left w-full md:w-1/3 flex items-center space-x-2">
                            <i class="pi pi-search" />
                            <InputText v-model="search" placeholder="Buscar por Folio, Cliente o Estado..." class="w-full" />
                        </span>
                        <Link :href="route('quotes.create')">
                            <Button label="Crear Cotización" icon="pi pi-plus" />
                        </Link>
                    </div>

                    <!-- Vista de Tabla para Escritorio -->
                    <div class="hidden md:block">
                        <DataTable :value="quotes.data" stripedRows tableStyle="min-width: 50rem;"
                            @row-click="onRowClick" selectionMode="single" dataKey="id" :rowClass="rowClass">
                            <template #empty> No se encontraron cotizaciones. </template>

                            <Column header="Folio" style="width: 10%">
                                <template #body="{ data }">
                                   <div class="flex items-center gap-2">
                                        <i :class="getStatusIcon(data.status)" :title="data.status"></i>
                                        <span class="font-mono">Cot-{{ data.id }}</span>
                                   </div>
                                </template>
                            </Column>
                            <Column field="client" header="Cliente" sortable>
                                <template #body="{ data }">
                                    <div class="font-semibold">{{ data.client?.name || data.client_name }}</div>
                                    <div class="text-sm text-gray-500">{{ data.client?.tax_id || data.origin }}</div>
                                </template>
                            </Column>
                             <Column field="title" header="Título" sortable></Column>
                            <!-- Columna de Monto actualizada -->
                            <Column field="amount" header="Monto" sortable class="text-right">
                                <template #body="{ data }">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Se muestra el monto final con descuento -->
                                        <span class="font-semibold">{{ formatCurrency(data.final_amount) }}</span>
                                        <!-- NUEVO: Ícono con tooltip si hay descuento -->
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
                                    <span class="text-green-600">{{ formatCurrency(data.total_paid) }}</span>
                                </template>
                            </Column>
                            <!-- MODIFICACIÓN: Columna de Saldo actualizada -->
                            <Column header="Saldo" sortable class="text-right">
                                <template #body="{ data }">
                                    <!-- Se usa 'final_amount' para el cálculo y se añade tolerancia para decimales -->
                                    <span class="font-bold" :class="[(data.final_amount - (data.total_paid || 0)) > 0.01 ? 'text-red-600' : 'text-gray-700']">
                                        {{ formatCurrency(data.final_amount - (data.total_paid || 0)) }}
                                    </span>
                                </template>
                            </Column>
                             <Column field="status" header="Estado" sortable>
                                <template #body="{ data }">
                                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" rounded />
                                </template>
                            </Column>
                             <Column field="project_id" header="Proyecto">
                                <template #body="{ data }">
                                    <p>{{ data.project_id ?? '-' }}</p>
                                </template>
                            </Column>
                            <Column header="Acciones" style="width: 10%" bodyClass="text-center">
                                <template #body="{ data }">
                                    <Button icon="pi pi-ellipsis-v" text rounded aria-haspopup="true"
                                        aria-controls="overlay_menu" @click.stop="toggleMenu($event, data)" />
                                </template>
                            </Column>
                        </DataTable>
                    </div>

                    <Menu ref="menu" id="overlay_menu" :model="menuItems" :popup="true" />

                    <Pagination :links="quotes.links" />

                    <!-- Vista de Tarjetas para Móvil -->
                    <div class="md:hidden grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                        <Card v-for="quote in quotes.data" :key="quote.id"
                            @click="onRowClick({data: quote})">
                            <template #title>
                                <div class="flex justify-between items-start">
                                    <span class="text-lg font-bold font-mono">Cot-{{ quote.id }}</span>
                                    <Tag :value="quote.status" :severity="getStatusSeverity(quote.status)" rounded />
                                </div>
                            </template>
                            <template #subtitle>{{ quote.client?.name || quote.client_name }}</template>
                            <template #content>
                                <p class="font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ quote.title }}</p>
                                <ul class="space-y-2 text-gray-700 dark:text-gray-300">
                                    <!-- MODIFICACIÓN: Se usa 'final_amount' para el monto en vista móvil -->
                                    <li class="flex justify-between border-t pt-2 mt-2">
                                        <span class="font-bold">Monto:</span>
                                        <span class="font-bold text-blue-600">{{ formatCurrency(quote.final_amount) }}</span>
                                    </li>
                                     <li class="flex justify-between">
                                        <span class="text-green-600">Pagado:</span>
                                        <span class="text-green-600">{{ formatCurrency(quote.total_paid) }}</span>
                                    </li>
                                    <!-- MODIFICACIÓN: Se usa 'final_amount' para el saldo en vista móvil -->
                                    <li class="flex justify-between">
                                        <span class="font-semibold" :class="[(quote.final_amount - (quote.total_paid || 0)) > 0.01 ? 'text-red-600' : 'text-gray-700']">Saldo:</span>
                                        <span class="font-semibold" :class="[(quote.final_amount - (quote.total_paid || 0)) > 0.01 ? 'text-red-600' : 'text-gray-700']">{{ formatCurrency(quote.final_amount - (quote.total_paid || 0)) }}</span>
                                    </li>
                                </ul>
                            </template>
                            <template #footer>
                                <div class="flex justify-end">
                                    <Button label="Acciones" icon="pi pi-bars" @click.stop="toggleMenu($event, quote)"
                                        aria-haspopup="true" aria-controls="overlay_menu" severity="secondary" />
                                </div>
                            </template>
                        </Card>
                         <div v-if="quotes.data.length === 0" class="text-center text-gray-500 col-span-full mt-8">
                            No se encontraron cotizaciones.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diálogo Modal para Agregar Pago -->
        <Dialog v-model:visible="isPaymentDialogVisible" modal header="Registrar Pago" :style="{ width: '25rem' }">
            <template #header>
                <div class="flex flex-col">
                    <h3 class="text-lg font-semibold">Registrar Pago a Cotización</h3>
                    <p class="text-sm text-gray-500">Para: Cot-{{ selectedQuoteForPayment?.id }}</p>
                </div>
            </template>
            <form @submit.prevent="submitPayment">
                <div class="flex flex-col gap-4 p-4">
                    <div class="flex flex-col gap-2">
                        <label for="amount">Monto del Pago</label>
                        <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN"
                            locale="es-MX" :class="{ 'p-invalid': paymentForm.errors.amount }" />
                        <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="payment_date">Fecha del Pago</label>
                        <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd"
                            :class="{ 'p-invalid': paymentForm.errors.payment_date }" />
                        <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="notes">Notas (Opcional)</label>
                        <Textarea id="notes" v-model="paymentForm.notes" rows="3" />
                    </div>
                </div>
            </form>
            <template #footer>
                <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" />
                <Button label="Guardar Pago" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" />
            </template>
        </Dialog>

    </AppLayout>
</template>

<style>
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
</style>
