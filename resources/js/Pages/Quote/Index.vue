<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from "primevue/useconfirm";
import AppLayout from '@/Layouts/AppLayout.vue';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import Pagination from '@/Components/MyComponents/Pagination.vue';

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
/**
 * Debounce function to limit the rate at which a function gets called.
 * @param {Function} func The function to debounce.
 * @param {number} delay The debounce delay in milliseconds.
 */
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

    // Lógica para cambiar de estado
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
            label: 'Ver Detalles',
            icon: 'pi pi-eye',
            command: () => router.get(`/quotes/${quote.id}`) // Asumiendo que tendrás una ruta de show
        },
        {
            label: 'Editar Cotización',
            icon: 'pi pi-pencil',
            command: () => router.get(`/quotes/${quote.id}/edit`), // Asumiendo que tendrás una ruta de edit
            visible: !['Aceptado', 'Pagado'].includes(quote.status)
        },
        {
            label: 'Cambiar Estado',
            icon: 'pi pi-sort-alt',
            items: statusActions,
            visible: statusActions.length > 0
        },
        {
            separator: true
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
    // router.get(`/quotes/${event.data.id}`); // Opcional: navegar al detalle al hacer clic
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
                            <Column field="amount" header="Monto" sortable class="text-right">
                                <template #body="{ data }">
                                    <span class="font-semibold">{{ formatCurrency(data.amount) }}</span>
                                </template>
                            </Column>
                             <Column field="status" header="Estado" sortable>
                                <template #body="{ data }">
                                    <Tag :value="data.status" :severity="getStatusSeverity(data.status)" rounded />
                                </template>
                            </Column>
                             <Column field="created_at" header="Fecha" sortable>
                                <template #body="{ data }">
                                    {{ formatDate(data.created_at) }}
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
                    
                    <!-- Paginador para la tabla -->
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
                                    <li class="flex justify-between border-t pt-2 mt-2">
                                        <span class="font-bold">Monto:</span>
                                        <span class="font-bold text-blue-600">
                                            {{ formatCurrency(quote.amount) }}
                                        </span>
                                    </li>
                                     <li class="flex justify-between">
                                        <span>Fecha:</span>
                                        <span>{{ formatDate(quote.created_at) }}</span>
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
    </AppLayout>
</template>

<style>
.p-tag {
    text-transform: capitalize;
}
.p-datatable .p-column-header-content {
    justify-content: space-between;
}
</style>

