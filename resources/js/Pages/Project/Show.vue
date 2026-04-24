<script setup>
import { ref, computed } from 'vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ProgressBar from 'primevue/progressbar';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Toast from 'primevue/toast';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from "primevue/useconfirm";

// --- PROPS ---
const props = defineProps({
    quote: { type: Object, required: true },
});

// --- STATE MANAGEMENT ---
const toast = useToast();
const confirm = useConfirm();
const isUpdatingStatus = ref(false);
const isPaymentDialogVisible = ref(false);
const isDateDialogVisible = ref(false);
const selectedStageLabel = ref('');
const isEditingPayment = ref(false);
const selectedPaymentId = ref(null);
const paymentMenu = ref();
const selectedPaymentForMenu = ref(null);

// --- FORMS ---
const invoiceForm = useForm({ invoice_file: null });

const paymentForm = useForm({
    quote_id: props.quote.id,
    client_id: props.quote.client_id,
    amount: null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    receipt: null,
    _method: 'post', // Necesario para Laravel cuando enviamos archivos en PUT/PATCH
});

const dateForm = useForm({
    field: '',
    date: null,
});

// --- HELPER DE ARCHIVOS/FACTURAS ---
const getFileExtension = (filename) => {
    if (!filename) return '';
    return filename.split('.').pop().toUpperCase();
};

const getFileIcon = (filename) => {
    const ext = getFileExtension(filename).toLowerCase();
    if (ext === 'pdf') return 'pi pi-file-pdf text-red-500';
    if (ext === 'xml') return 'pi pi-code text-orange-500';
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(ext)) return 'pi pi-image text-blue-500';
    if (['doc', 'docx'].includes(ext)) return 'pi pi-file-word text-blue-700';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return 'pi pi-file-excel text-emerald-600';
    return 'pi pi-file text-gray-500';
};

const getFileColorClass = (filename) => {
    const ext = getFileExtension(filename).toLowerCase();
    if (ext === 'pdf') return 'bg-red-50 dark:bg-red-900/20 text-red-500 border-red-100 dark:border-red-900/50';
    if (ext === 'xml') return 'bg-orange-50 dark:bg-orange-900/20 text-orange-500 border-orange-100 dark:border-orange-900/50';
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(ext)) return 'bg-blue-50 dark:bg-blue-900/20 text-blue-500 border-blue-100 dark:border-blue-900/50';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-500 border-emerald-100 dark:border-emerald-900/50';
    return 'bg-gray-50 dark:bg-gray-900/20 text-gray-500 border-gray-100 dark:border-gray-800';
};

const getTagSeverity = (filename) => {
    const ext = getFileExtension(filename).toLowerCase();
    if (ext === 'pdf') return 'danger';
    if (ext === 'xml') return 'warning';
    if (['jpg', 'jpeg', 'png', 'gif', 'svg'].includes(ext)) return 'info';
    if (['xls', 'xlsx', 'csv'].includes(ext)) return 'success';
    return 'secondary';
};

// --- TIMELINE LOGIC (APPLE STYLE CON FECHAS) ---
const stages = computed(() => {
    const isRejected = props.quote.status === 'Rechazado';
    
    return [
        { id: 'Pendiente', label: 'Pendiente', icon: 'pi pi-history', color: 'bg-green-500', date: props.quote.created_at, dateField: 'created_at' },
        { id: 'Enviado', label: 'Enviado', icon: 'pi pi-send', color: 'bg-green-500', date: props.quote.sent_at, dateField: 'sent_at' },
        { id: isRejected ? 'Rechazado' : 'Aceptado', label: isRejected ? 'Rechazado' : 'Aceptado', icon: isRejected ? 'pi pi-times' : 'pi pi-check', color: isRejected ? 'bg-[#EF4444]' : 'bg-green-500', date: isRejected ? props.quote.rejected_at : props.quote.accepted_at, dateField: isRejected ? 'rejected_at' : 'accepted_at' },
        { id: 'Pagado', label: 'Pagado', icon: 'pi pi-verified', color: 'bg-green-500', date: props.quote.paid_at, dateField: 'paid_at' }
    ];
});

const currentStageIndex = computed(() => {
    return stages.value.findIndex(s => s.id === props.quote.status);
});

const progressWidth = computed(() => {
    if (currentStageIndex.value === -1 || currentStageIndex.value === 0) return '0%';
    return `${(currentStageIndex.value / (stages.value.length - 1)) * 100}%`;
});

const canUpdateTo = (targetStatus) => {
    if (isUpdatingStatus.value) return false;
    const current = props.quote.status;
    
    if (current === 'Pendiente' && (targetStatus === 'Enviado' || targetStatus === 'Pagado')) return true;
    if (current === 'Enviado' && (targetStatus === 'Aceptado' || targetStatus === 'Rechazado' || targetStatus === 'Pagado')) return true;
    if (current === 'Rechazado' && (targetStatus === 'Aceptado' || targetStatus === 'Enviado')) return true;
    if (current === 'Aceptado' && (targetStatus === 'Enviado' || targetStatus === 'Rechazado' || targetStatus === 'Pagado')) return true;
    if (current === 'Pagado' && targetStatus === 'Aceptado') return true;
    
    return false;
};

// Se agregó soporte para enviar una fecha personalizada cuando cambia a 'Pagado'
const changeQuoteStatus = (newStatus, customDate = null) => {
    if (!canUpdateTo(newStatus)) return;
    
    isUpdatingStatus.value = true;
    
    const payload = { status: newStatus };
    if (customDate) {
        payload.date = customDate;
    }

    router.put(route('quotes.updateStatus', { quote: props.quote.id }), payload, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Estado actualizado correctamente.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo actualizar el estado.', life: 3000 });
        },
        onFinish: () => {
            isUpdatingStatus.value = false;
        }
    });
};

// --- DATE EDITOR LOGIC ---
const openDateEditor = (stage) => {
    selectedStageLabel.value = stage.label;
    dateForm.field = stage.dateField;
    dateForm.date = stage.date ? new Date(stage.date) : new Date();
    isDateDialogVisible.value = true;
};

const submitDateChange = () => {
    const formattedDate = dateForm.date.toISOString().slice(0, 19).replace('T', ' ');
    
    dateForm.transform((data) => ({
        ...data,
        date: formattedDate
    })).put(route('quotes.updateDates', props.quote.id), {
        preserveScroll: true,
        onSuccess: () => {
            isDateDialogVisible.value = false;
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Fecha actualizada correctamente.', life: 3000 });
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Hubo un problema al guardar la fecha.', life: 3000 });
        }
    });
};

// --- SUBMIT INVOICE ---
const submitInvoice = () => {
    invoiceForm.post(route('quotes.invoices.store', props.quote.id), {
        preserveScroll: true,
        onSuccess: () => {
            invoiceForm.reset('invoice_file');
            const fileInput = document.getElementById('invoice-input');
            if (fileInput) fileInput.value = '';
            toast.add({ severity: 'success', summary: 'Éxito', detail: 'Archivo adjuntado correctamente', life: 3000 });
        },
    });
};

const invoices = computed(() => props.quote.media?.filter(file => file.collection_name === 'invoices') || []);

// --- PAYMENT LOGIC ---
const totalPaid = computed(() => {
    if (!props.quote.payments) return 0;
    return props.quote.payments.reduce((sum, p) => sum + parseFloat(p.amount), 0);
});

const totalWithDiscount = computed(() => {    
    let amount = parseFloat(props.quote.amount) || 0;
    const discount = parseFloat(props.quote.percentage_discount) || 0;
    
    if (discount > 0 && discount <= 100) {
        amount = amount - ((amount * discount) / 100);
    }
    
    if (props.quote.needs_invoice) {
        amount = amount * 1.16;
    }
    
    return amount;
});

const remainingBalance = computed(() => {
    return Math.max(0, totalWithDiscount.value - totalPaid.value);
});

const openPaymentDialog = () => {
    isEditingPayment.value = false;
    paymentForm.reset();
    paymentForm.clearErrors();
    paymentForm.quote_id = props.quote.id;
    paymentForm.client_id = props.quote.client_id;
    paymentForm.amount = remainingBalance.value > 0 ? remainingBalance.value : null;
    paymentForm._method = 'post';
    isPaymentDialogVisible.value = true;
};

const openEditPaymentDialog = (payment) => {
    isEditingPayment.value = true;
    selectedPaymentId.value = payment.id;
    paymentForm.clearErrors();
    
    paymentForm.amount = parseFloat(payment.amount);
    paymentForm.payment_date = new Date(payment.payment_date);
    paymentForm.notes = payment.notes || '';
    paymentForm.receipt = null;
    paymentForm._method = 'put';

    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
    setTimeout(() => {
        paymentForm.reset();
        isEditingPayment.value = false;
        selectedPaymentId.value = null;
    }, 200);
};

const submitPayment = () => {
    // Calcular si con este pago se salda la cotización, tolerando pequeños márgenes de error por decimales
    let trueRemaining = remainingBalance.value;
    if (isEditingPayment.value) {
        const originalPayment = props.quote.payments.find(p => p.id === selectedPaymentId.value);
        const originalAmount = originalPayment ? parseFloat(originalPayment.amount) : 0;
        trueRemaining = remainingBalance.value + originalAmount; // Sumar el pago viejo para tener el saldo real a cubrir
    }
    
    // Tolerancia de 5 centavos para errores de redondeo de punto flotante en JS
    const isFullPayment = (parseFloat(paymentForm.amount) >= (trueRemaining - 0.05));
    
    // Extraer la fecha garantizando el formato local YYYY-MM-DD
    let paymentDateStr = null;
    if (paymentForm.payment_date) {
        const d = new Date(paymentForm.payment_date);
        paymentDateStr = d.getFullYear() + '-' + 
                         String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                         String(d.getDate()).padStart(2, '0') + ' 12:00:00';
    }

    const routeName = isEditingPayment.value ? 'client-payments.update' : 'client-payments.store';
    const routeParam = isEditingPayment.value ? selectedPaymentId.value : null;

    paymentForm.post(route(routeName, routeParam), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentDialog();
            toast.add({ severity: 'success', summary: 'Éxito', detail: isEditingPayment.value ? 'Pago actualizado correctamente' : 'Pago registrado correctamente', life: 3000 });
            
            // Cambio automático a pagado si se cubre todo
            if (isFullPayment && props.quote.status !== 'Pagado') {
                changeQuoteStatus('Pagado', paymentDateStr);
            }
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({ severity: 'error', summary: 'Error', detail: errorMessages || 'Hubo un problema con el pago.', life: 3000 });
        }
    });
};

const confirmDeletePayment = (payment) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este pago? Esta acción actualizará el saldo y el estado de la cotización si es necesario.',
        header: 'Confirmar Eliminación',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        acceptLabel: 'Sí, eliminar',
        rejectLabel: 'Cancelar',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        accept: () => {
            router.delete(route('client-payments.destroy', payment.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Pago eliminado correctamente', life: 3000 });
                },
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Hubo un problema al eliminar el pago.', life: 3000 });
                }
            });
        }
    });
};

const togglePaymentMenu = (event, payment) => {
    selectedPaymentForMenu.value = payment;
    paymentMenu.value.toggle(event);
};

const paymentMenuItems = computed(() => {
    if (!selectedPaymentForMenu.value) return [];
    
    return [
        {
            label: 'Editar',
            icon: 'pi pi-pencil',
            command: () => openEditPaymentDialog(selectedPaymentForMenu.value)
        },
        {
            label: 'Eliminar',
            icon: 'pi pi-trash',
            command: () => confirmDeletePayment(selectedPaymentForMenu.value)
        }
    ];
});

// --- HELPERS ---
const getStatusClasses = (status) => {
    const base = "px-3 py-1 rounded-full text-xs font-semibold tracking-wide border";
    const styles = {
        'Pendiente': 'bg-blue-50 text-blue-600 border-blue-100 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800',
        'Enviado': 'bg-amber-50 text-amber-600 border-amber-100 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800',
        'Aceptado': 'bg-emerald-50 text-emerald-600 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800',
        'Rechazado': 'bg-rose-50 text-rose-600 border-rose-100 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-800',
        'Pagado': 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-900/40 dark:text-emerald-400 dark:border-emerald-700',
    };
    return `${base} ${styles[status] || 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-gray-800 dark:text-gray-400'}`;
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
};

const formatDateShort = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).replace('.', '');
};

const formatCurrency = (value) => {
    const numericValue = parseFloat(value);
    if (isNaN(numericValue)) return '$0.00';
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(numericValue);
};

const deleteFile = (fileId) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este archivo adjunto?',
        header: 'Confirmar Eliminación',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Eliminar',
        rejectLabel: 'Cancelar',
        accept: () => {
            router.delete(route('quotes.invoices.destroy', { quote: props.quote.id, media: fileId }), { preserveScroll: true });
        }
    });
};

const getReceiptUrl = (payment) => {
    if (payment.media && payment.media.length > 0) return payment.media[0].original_url;
    if (payment.receipt_path || payment.receipt) return '/storage/' + (payment.receipt_path || payment.receipt);
    if (payment.receipt_url) return payment.receipt_url;
    return null;
};
</script>

<template>
    <Head :title="`Cotización #${quote.id}`" />
    
    <AppLayout title="Detalle de Cotización">
        <Toast />
        <ConfirmDialog />

        <div class="py-6 sm:py-12">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-4 sm:mb-6">
                 <Link :href="route('quotes.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
            </div>
            
            <!-- Header Content -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-6 sm:mb-8">
                <header class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3 mb-2">
                            <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-[#212121] dark:text-zinc-100 tracking-tight">
                                Cotización #{{ quote.id }}
                            </h1>
                            <span :class="getStatusClasses(quote.status)" class="mt-1 sm:mt-0">{{ quote.status }}</span>
                        </div>
                        <p class="text-gray-500 dark:text-zinc-400 text-sm sm:text-lg">{{ quote.title }}</p>
                    </div>
                    
                    <div class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto">
                         <a :href="route('quotes.print', quote.id)" target="_blank" rel="noopener noreferrer" class="flex-1 sm:flex-none">
                            <Button label="Imprimir" icon="pi pi-print" severity="secondary" outlined rounded class="w-full sm:w-auto !bg-white dark:!bg-transparent !border-gray-300 dark:!border-zinc-600 !text-gray-700 dark:!text-zinc-300 shadow-sm" />
                        </a>
                        <Link v-if="quote.status !== 'Pagado'" :href="route('quotes.edit', quote.id)" class="flex-1 sm:flex-none">
                            <Button label="Editar" icon="pi pi-pencil" rounded class="w-full sm:w-auto !text-[var(--primary-text-color)]" />
                        </Link>
                    </div>
                </header>
            </div>

            <!-- Apple Style Interactive Timeline -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 sm:mb-12">
                <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-6 sm:p-8 pt-8 sm:pt-10 relative overflow-hidden">
                    <h3 class="text-xs sm:text-sm font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-widest mb-6 sm:mb-10 text-center">Progreso de la Cotización</h3>
                    
                    <!-- Contenedor ajustado para caber perfectamente sin scroll -->
                    <div class="pb-6 w-full px-2 sm:px-0">
                        <div class="relative flex items-center justify-between z-10 mx-auto w-full mb-8 sm:mb-10">
                            <!-- Barra de fondo -->
                            <div class="absolute left-0 right-0 top-1/2 h-1.5 bg-gray-100 dark:bg-zinc-800 -translate-y-1/2 rounded-full z-0"></div>
                            <!-- Barra de progreso con animación -->
                            <div class="absolute left-0 top-1/2 h-1.5 bg-green-300 dark:bg-green-200 -translate-y-1/2 rounded-full z-0 transition-all duration-700 ease-in-out" :style="{ width: progressWidth }"></div>

                            <!-- Nodos -->
                            <div v-for="(stage, index) in stages" :key="stage.id" class="relative z-10 flex flex-col items-center group">
                                
                                <!-- Botón del Nodo -->
                                <button 
                                    @click="changeQuoteStatus(stage.id)"
                                    :disabled="!canUpdateTo(stage.id)"
                                    class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center border-[3px] transition-all duration-300 shadow-sm"
                                    :class="[
                                        index <= currentStageIndex ? `${stage.color} border-white dark:border-zinc-900 text-white` : 'bg-white dark:bg-zinc-900 border-gray-200 dark:border-zinc-700 text-gray-400 dark:text-zinc-600',
                                        canUpdateTo(stage.id) ? 'cursor-pointer hover:scale-110 hover:shadow-md ring-4 ring-blue-50 dark:ring-purple-900/30' : 'cursor-default',
                                        isUpdatingStatus && canUpdateTo(stage.id) ? 'opacity-50' : ''
                                    ]"
                                >
                                    <i v-if="isUpdatingStatus && canUpdateTo(stage.id)" class="pi pi-spinner pi-spin text-lg sm:text-xl"></i>
                                    <i v-else :class="stage.icon" class="text-lg sm:text-xl"></i>
                                </button>

                                <!-- Etiqueta y Fecha Interactiva -->
                                <div class="absolute top-12 sm:top-14 left-1/2 -translate-x-1/2 flex flex-col items-center w-24 sm:w-32 transition-colors duration-300">
                                    <span class="whitespace-nowrap text-[10px] sm:text-xs font-bold"
                                        :class="index <= currentStageIndex ? 'text-gray-800 dark:text-zinc-200' : 'text-gray-400 dark:text-zinc-600'">
                                        {{ stage.label }}
                                    </span>
                                    
                                    <!-- Editor de Fecha -->
                                    <div v-if="index <= currentStageIndex" class="mt-1">
                                        <div v-if="stage.date" 
                                            @click="openDateEditor(stage)"
                                            class="flex items-center gap-1 sm:gap-1.5 px-2 py-1 rounded-lg text-[9px] sm:text-[11px] font-medium text-gray-500 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800 hover:text-blue-600 dark:hover:text-blue-400 cursor-pointer transition-all border border-transparent hover:border-gray-200 dark:hover:border-zinc-700 group/date">
                                            <i class="pi pi-calendar text-[9px] sm:text-[10px] opacity-70 group-hover/date:opacity-100"></i>
                                            <span class="capitalize">{{ formatDateShort(stage.date) }}</span>
                                            <i class="pi pi-pencil text-[8px] sm:text-[9px] opacity-0 group-hover/date:opacity-100 transition-opacity"></i>
                                        </div>
                                        <div v-else
                                            @click="openDateEditor(stage)"
                                            class="flex items-center gap-1 mt-1 text-[9px] sm:text-[10px] font-medium text-blue-500 hover:text-blue-600 cursor-pointer transition-colors px-2 py-1 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                            <i class="pi pi-calendar-plus text-[9px] sm:text-[10px]"></i>
                                            <span>Definir fecha</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tooltip guía -->
                                <div v-if="canUpdateTo(stage.id)" class="absolute -top-10 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-[10px] py-1 px-3 rounded-lg font-bold opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none shadow-lg">
                                    Clic para cambiar a {{ stage.label }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones alternativos de estado rápido -->
                    <div v-if="quote.status === 'Enviado'" class="text-center mt-2 sm:mt-6 animate-fade-in-up">
                        <button @click="changeQuoteStatus('Rechazado')" :disabled="isUpdatingStatus" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors underline decoration-dotted underline-offset-4">
                            O marcar cotización como Rechazada
                        </button>
                    </div>
                    <div v-if="quote.status === 'Rechazado'" class="text-center mt-2 sm:mt-6 animate-fade-in-up">
                        <button @click="changeQuoteStatus('Aceptado')" :disabled="isUpdatingStatus" class="text-xs font-bold text-gray-400 hover:text-emerald-500 transition-colors underline decoration-dotted underline-offset-4">
                            O marcar cotización como Aceptada
                        </button>
                    </div>
                    <div v-if="quote.status === 'Aceptado'" class="text-center mt-2 sm:mt-6 animate-fade-in-up">
                        <button @click="changeQuoteStatus('Rechazado')" :disabled="isUpdatingStatus" class="text-xs font-bold text-gray-400 hover:text-red-500 transition-colors underline decoration-dotted underline-offset-4 mr-4">
                            O marcar cotización como Rechazada
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
                    
                    <!-- Columna Izquierda: Información Principal -->
                    <div class="lg:col-span-8 space-y-6 sm:space-y-8">
                        <section class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden">
                            <div class="p-6 sm:p-8">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-zinc-100 mb-4 sm:mb-6 flex items-center">
                                    <i class="pi pi-info-circle mr-2 text-blue-500"></i> Información General
                                </h3>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-6 sm:gap-y-8 gap-x-6 sm:gap-x-12">
                                    <div class="group">
                                        <h4 class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-1 sm:mb-2">Cliente</h4>
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900 dark:to-indigo-900 flex items-center justify-center text-blue-600 dark:text-blue-300 font-bold text-base sm:text-lg mr-2 sm:mr-3">
                                                {{ quote.client?.name ? quote.client.name.charAt(0) : '?' }}
                                            </div>
                                            <p class="text-base sm:text-lg font-medium text-gray-900 dark:text-zinc-100">{{ quote.client?.name || 'Sin Cliente' }}</p>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-1 sm:mb-2">Vigencia</h4>
                                        <p class="text-base sm:text-lg font-medium text-gray-900 dark:text-zinc-100 flex items-center">
                                            <i class="pi pi-calendar mr-2 text-gray-400"></i> {{ formatDate(quote.valid_until) }}
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-1 sm:mb-2">Duración Estimada</h4>
                                        <p class="text-base sm:text-lg font-medium text-gray-900 dark:text-zinc-100 flex items-center">
                                            <i class="pi pi-clock mr-2 text-gray-400"></i> {{ quote.work_days }} días hábiles
                                        </p>
                                    </div>

                                     <div>
                                        <h4 class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-1 sm:mb-2">Método de Pago</h4>
                                        <p class="text-base sm:text-lg font-medium text-gray-900 dark:text-zinc-100 flex items-center">
                                            <i class="pi pi-wallet mr-2 text-gray-400"></i> {{ quote.payment_type }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-8 sm:mt-10 pt-6 sm:pt-8 border-t border-gray-100 dark:border-zinc-800">
                                     <h4 class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider mb-3 sm:mb-4">Descripción del Proyecto</h4>
                                     <div class="prose prose-blue prose-sm sm:prose-lg max-w-none text-gray-600 dark:text-zinc-300 leading-relaxed break-words whitespace-pre-wrap" v-html="quote.description"></div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Columna Derecha: Finanzas y Acciones -->
                    <div class="lg:col-span-4 space-y-6">
                        
                        <!-- Tarjeta de Finanzas -->
                        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 overflow-hidden relative">
                            <div class="absolute top-0 right-0 -mr-16 -mt-16 w-32 h-32 rounded-full bg-blue-50 dark:bg-blue-900/20 blur-2xl pointer-events-none"></div>

                            <div class="p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-zinc-100 mb-6">Resumen Financiero</h3>
                                
                                <div class="space-y-4">
                                    <div class="flex justify-between items-center text-gray-600 dark:text-zinc-400 text-sm sm:text-base">
                                        <span>Subtotal</span>
                                        <span class="font-medium text-gray-900 dark:text-zinc-100">{{ formatCurrency(quote.amount) }}</span>
                                    </div>
                                    
                                    <div v-if="quote.percentage_discount > 0" class="flex justify-between items-center text-rose-500 bg-rose-50 dark:bg-rose-900/10 px-3 py-2 rounded-lg -mx-3">
                                        <span class="flex items-center text-xs sm:text-sm"><i class="pi pi-tag mr-2"></i> Descuento ({{ quote.percentage_discount }}%)</span>
                                        <span class="font-bold text-sm sm:text-base">- {{ formatCurrency((quote.amount * quote.percentage_discount) / 100) }}</span>
                                    </div>

                                    <div v-if="quote.needs_invoice" class="flex justify-between items-center text-blue-600 bg-blue-50 dark:bg-blue-900/10 px-3 py-2 rounded-lg -mx-3">
                                        <span class="flex items-center text-xs sm:text-sm"><i class="pi pi-receipt mr-2"></i> IVA (16%)</span>
                                        <span class="font-bold text-sm sm:text-base">+ {{ formatCurrency((quote.amount - ((quote.amount * (quote.percentage_discount || 0)) / 100)) * 0.16) }}</span>
                                    </div>

                                    <div class="flex justify-between items-center text-emerald-600 dark:text-emerald-400 text-sm sm:text-base" v-if="totalPaid > 0">
                                        <span class="flex items-center"><i class="pi pi-check-circle mr-2"></i> Pagado</span>
                                        <span class="font-medium">{{ formatCurrency(totalPaid) }}</span>
                                    </div>

                                    <div class="pt-6 mt-2 border-t border-dashed border-gray-200 dark:border-zinc-700">
                                        <div class="flex justify-between items-end">
                                            <span class="text-gray-500 dark:text-zinc-400 text-xs sm:text-sm font-medium uppercase tracking-wide">Total a Pagar</span>
                                            <span class="text-2xl sm:text-3xl font-extrabold text-blue-600 dark:text-blue-400 tracking-tight">{{ formatCurrency(totalWithDiscount) }}</span>
                                        </div>
                                        <div class="flex justify-between items-end mt-2" v-if="totalPaid > 0">
                                            <span class="text-gray-500 dark:text-zinc-400 text-xs sm:text-sm font-medium uppercase tracking-wide">Saldo Restante</span>
                                            <span class="text-lg sm:text-xl font-bold" :class="remainingBalance > 0.01 ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400 dark:text-zinc-500'">{{ formatCurrency(remainingBalance) }}</span>
                                        </div>
                                        <p class="text-right text-[10px] sm:text-xs text-gray-400 dark:text-zinc-500 mt-1">Total con IVA interno reflejado.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta de Historial de Pagos con botón de Agregar -->
                        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-zinc-100 flex items-center">
                                    <i class="pi pi-history mr-2 text-emerald-500"></i> Historial de Pagos
                                </h3>
                                <Button v-if="['Aceptado', 'Pagado'].includes(quote.status)" 
                                    @click="openPaymentDialog" 
                                    icon="pi pi-plus" 
                                    class="!w-8 !h-8 !p-0 !bg-emerald-50 dark:!bg-emerald-900/30 !text-emerald-600 dark:!text-emerald-400 !border-none hover:!bg-emerald-100 dark:hover:!bg-emerald-900/50" 
                                    rounded 
                                    v-tooltip.top="'Registrar Nuevo Pago'" />
                            </div>

                            <div v-if="quote.payments && quote.payments.length > 0" class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div v-for="payment in quote.payments" :key="payment.id" class="p-4 rounded-2xl bg-gray-50 dark:bg-zinc-800/50 border border-gray-100 dark:border-zinc-700 relative group">
                                    
                                    <!-- Botón de Opciones del Pago -->
                                    <div class="absolute top-2 right-2">
                                        <Button icon="pi pi-ellipsis-v" @click="(e) => togglePaymentMenu(e, payment)" class="!w-6 !h-6 !p-0 !text-gray-400 hover:!text-gray-600 dark:hover:!text-gray-300 !bg-transparent !border-none" rounded text />
                                    </div>

                                    <div class="flex justify-between items-start mb-1 pr-6">
                                        <span class="font-bold text-base sm:text-lg text-gray-800 dark:text-zinc-200">{{ formatCurrency(payment.amount) }}</span>
                                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 bg-white dark:bg-zinc-800 px-2 py-1 rounded-full shadow-sm">{{ formatDate(payment.payment_date) }}</span>
                                    </div>
                                    <p v-if="payment.notes" class="text-xs sm:text-sm text-gray-600 dark:text-zinc-400 mt-2 italic border-l-2 border-emerald-200 dark:border-emerald-800 pl-2">
                                        {{ payment.notes }}
                                    </p>
                                    <div v-if="getReceiptUrl(payment)" class="mt-4 pt-3 border-t border-gray-200 dark:border-zinc-700">
                                        <a :href="getReceiptUrl(payment)" target="_blank" class="inline-flex items-center text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-900/30 px-3 py-1.5 rounded-full hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                                            <i class="pi pi-file mr-1.5"></i> Ver Comprobante
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-6">
                                <i class="pi pi-wallet text-3xl text-gray-300 dark:text-zinc-600 mb-2"></i>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">Aún no hay pagos registrados.</p>
                                <Button v-if="['Aceptado', 'Pagado'].includes(quote.status)" @click="openPaymentDialog" label="Registrar Primer Pago" text size="small" class="mt-2 !text-emerald-600" />
                            </div>
                            
                            <!-- Menú contextual para las opciones del pago -->
                            <Menu ref="paymentMenu" id="payment_menu" :model="paymentMenuItems" :popup="true" />
                        </div>

                        <!-- Tarjeta de Archivos / Facturas -->
                        <div class="bg-white dark:bg-zinc-900 rounded-[2rem] shadow-sm border border-gray-100 dark:border-zinc-800 p-6">
                            <h3 class="text-base sm:text-lg font-bold text-gray-900 dark:text-zinc-100 mb-4 flex items-center">
                                <i class="pi pi-paperclip mr-2 text-gray-400"></i> Adjuntos y Facturas
                            </h3>

                            <form @submit.prevent="submitInvoice" class="mb-6 relative group">
                                <div class="relative flex items-center justify-center w-full">
                                    <label for="invoice-input" class="flex flex-col items-center justify-center w-full h-24 sm:h-28 border-2 border-gray-200 border-dashed rounded-2xl cursor-pointer bg-gray-50 dark:bg-zinc-800/50 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors duration-200 group-hover:border-blue-300">
                                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                            <i v-if="!invoiceForm.invoice_file" class="pi pi-cloud-upload text-2xl sm:text-3xl text-gray-400 mb-2 group-hover:text-blue-500 transition-colors"></i>
                                            <i v-else class="pi pi-check-circle text-2xl sm:text-3xl text-emerald-500 mb-2"></i>
                                            <p v-if="!invoiceForm.invoice_file" class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400"><span class="font-semibold">Click para subir</span> archivo</p>
                                            <p v-else class="text-xs sm:text-sm text-emerald-600 font-medium truncate max-w-[80%]">{{ invoiceForm.invoice_file.name }}</p>
                                        </div>
                                        <input id="invoice-input" type="file" @input="invoiceForm.invoice_file = $event.target.files[0]" class="hidden" accept=".pdf,.xml,.jpg,.png,.jpeg" />
                                    </label>
                                </div>
                                <div v-if="invoiceForm.invoice_file" class="mt-3 animate-fade-in-up">
                                    <Button type="submit" label="Subir Archivo" icon="pi pi-upload" class="w-full !text-[var(--primary-text-color)]" :loading="invoiceForm.processing" rounded />
                                </div>
                                <p v-if="invoiceForm.errors.invoice_file" class="text-[10px] sm:text-xs text-red-500 mt-2 text-center">{{ invoiceForm.errors.invoice_file }}</p>
                                <ProgressBar v-if="invoiceForm.progress" :value="invoiceForm.progress.percentage" class="mt-3 h-1" :showValue="false" />
                            </form>

                            <div class="space-y-3">
                                <div v-if="invoices.length > 0">
                                    <ul class="space-y-3">
                                        <!-- Actualización Visual para Íconos y Formatos -->
                                        <li v-for="file in invoices" :key="file.id" class="group flex items-center justify-between p-3 rounded-xl bg-white dark:bg-zinc-800/50 border border-gray-100 dark:border-zinc-700 hover:shadow-md transition-all duration-200">
                                            <a :href="file.original_url" target="_blank" rel="noopener noreferrer" class="flex items-center flex-1 min-w-0 mr-3">
                                                
                                                <div :class="['w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm border', getFileColorClass(file.file_name || file.name)]">
                                                    <i :class="getFileIcon(file.file_name || file.name)" class="text-xl"></i>
                                                </div>
                                                
                                                <div class="ml-3 flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <p class="text-sm font-bold text-gray-900 dark:text-zinc-100 truncate group-hover:text-blue-600 transition-colors">
                                                            {{ file.file_name || file.name }}
                                                        </p>
                                                        <Tag :value="getFileExtension(file.file_name || file.name)" class="!text-[10px] !font-black !px-2 !py-0.5 shrink-0 uppercase tracking-widest" :severity="getTagSeverity(file.file_name || file.name)" />
                                                    </div>
                                                    <p class="text-xs text-gray-500 font-medium truncate">{{ (file.size / 1024).toFixed(2) }} KB</p>
                                                </div>
                                            </a>
                                            <Button @click="deleteFile(file.id)" icon="pi pi-trash" class="!w-8 !h-8 !p-0 !text-gray-400 hover:!text-red-500 hover:!bg-red-50 dark:hover:!bg-red-900/30 transition-colors" text rounded />
                                        </li>
                                    </ul>
                                </div>
                                <div v-else class="text-center py-6 sm:py-8 px-4 bg-gray-50 dark:bg-zinc-800/50 rounded-2xl border border-dashed border-gray-200 dark:border-zinc-700">
                                    <i class="pi pi-folder-open text-gray-300 dark:text-zinc-600 text-3xl sm:text-4xl mb-3 block"></i>
                                    <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-500">No hay archivos adjuntos aún.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Diálogo Modal para Registrar/Editar Pago ajustado al móvil -->
        <Dialog v-model:visible="isPaymentDialogVisible" modal :header="isEditingPayment ? 'Editar Pago' : 'Registrar Pago'" :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" :style="{ width: '28rem', maxWidth: '100%' }"
            :pt="{ 
                root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0 m-4 sm:m-0' }, 
                header: { class: 'pt-6 sm:pt-8 px-6 sm:px-8 pb-0 bg-transparent rounded-t-[2rem] dark:text-zinc-100' }, 
                content: { class: 'px-6 sm:px-8 pb-6 sm:pb-8 pt-4 bg-transparent rounded-b-[2rem]' } 
            }">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center shrink-0">
                        <i :class="isEditingPayment ? 'pi pi-pencil' : 'pi pi-dollar'" class="text-emerald-600 dark:text-emerald-400 text-lg font-bold"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-lg sm:text-xl font-bold text-gray-800 dark:text-white tracking-tight truncate">{{ isEditingPayment ? 'Editar Pago' : 'Registrar Pago' }}</span>
                        <span class="text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400">Cot-{{ quote.id }}</span>
                    </div>
                </div>
            </template>
            <form @submit.prevent="submitPayment">
                <div class="flex flex-col gap-4 sm:gap-5 mt-2">
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <label for="amount" class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300">Monto del Pago <span class="text-red-500">*</span></label>
                        <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN"
                            locale="es-MX" class="!rounded-xl w-full" :class="{ 'p-invalid': paymentForm.errors.amount }" required />
                        <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                    </div>
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <label for="payment_date" class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300">Fecha del Pago <span class="text-red-500">*</span></label>
                        <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" class="!rounded-xl w-full"
                            :class="{ 'p-invalid': paymentForm.errors.payment_date }" required />
                        <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                    </div>
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <label for="notes" class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300">Notas (Opcional)</label>
                        <Textarea id="notes" v-model="paymentForm.notes" rows="2" class="!rounded-xl w-full" />
                    </div>
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <label for="receipt" class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Comprobante (Opcional)
                            <span v-if="isEditingPayment" class="text-gray-400 font-normal ml-1">- Selecciona un archivo sólo si deseas reemplazar el actual.</span>
                        </label>
                        <input type="file" id="receipt" @input="paymentForm.receipt = $event.target.files[0]" 
                            class="block w-full text-xs sm:text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs sm:file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-zinc-800 dark:file:text-emerald-400 transition-colors cursor-pointer" 
                            accept=".pdf,.jpg,.jpeg,.png" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2 sm:gap-3 mt-4 w-full">
                    <Button label="Cancelar" text severity="secondary" @click="closePaymentDialog" class="!rounded-xl font-medium w-full sm:w-auto" />
                    <Button :label="isEditingPayment ? 'Actualizar' : 'Guardar'" icon="pi pi-check" @click="submitPayment" :loading="paymentForm.processing" class="!rounded-xl font-medium bg-emerald-600 border-emerald-600 hover:bg-emerald-700 !text-[var(--primary-text-color)] w-full sm:w-auto" />
                </div>
            </template>
        </Dialog>

        <!-- Diálogo Modal para Editar Fecha ajustado al móvil -->
        <Dialog v-model:visible="isDateDialogVisible" modal :breakpoints="{ '1199px': '75vw', '575px': '90vw' }" :style="{ width: '22rem', maxWidth: '100%' }"
            :pt="{ 
                root: { class: 'dark:bg-zinc-900 rounded-[2rem] shadow-2xl border-0 m-4 sm:m-0' }, 
                header: { class: 'pt-6 px-6 pb-0 bg-transparent rounded-t-[2rem] dark:text-zinc-100' }, 
                content: { class: 'px-6 pb-6 pt-4 bg-transparent rounded-b-[2rem]' } 
            }">
            <template #header>
                <div class="flex items-center gap-3 w-full">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/50 flex items-center justify-center shrink-0">
                        <i class="pi pi-calendar-plus text-blue-500 dark:text-blue-400 text-lg font-bold"></i>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-base sm:text-lg font-bold text-gray-800 dark:text-white tracking-tight truncate">Editar Fecha</span>
                        <span class="text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400">Estado: {{ selectedStageLabel }}</span>
                    </div>
                </div>
            </template>
            <form @submit.prevent="submitDateChange">
                <div class="flex flex-col gap-4 mt-2">
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <label class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300">Selecciona la nueva fecha</label>
                        <Calendar v-model="dateForm.date" dateFormat="dd/mm/yy" class="!rounded-xl w-full" :showIcon="true" />
                    </div>
                </div>
            </form>
            <template #footer>
                <div class="flex justify-end gap-2 sm:gap-3 mt-4 w-full">
                    <Button label="Cancelar" text severity="secondary" @click="isDateDialogVisible = false" class="!rounded-xl font-medium w-full sm:w-auto" />
                    <Button label="Guardar" icon="pi pi-check" @click="submitDateChange" :loading="dateForm.processing" class="!rounded-xl font-medium !text-[var(--primary-text-color)] w-full sm:w-auto" />
                </div>
            </template>
        </Dialog>

    </AppLayout>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.prose { line-height: 1.8; }
.prose h1, .prose h2, .prose h3 { color: inherit; font-weight: 700; margin-top: 1.5em; margin-bottom: 0.5em; }
.prose ul { list-style-type: disc; padding-left: 1.5em; margin-top: 1em; margin-bottom: 1em; }
.prose p { margin-bottom: 1em; }

/* Scrollbar super fino tipo Apple para Historial de Pagos y Línea de Tiempo */
.custom-scrollbar::-webkit-scrollbar { width: 3px; height: 3px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #52525b; }
.custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }

/* Input overrides para asegurar consistencia */
:deep(.p-inputtext), :deep(.p-dropdown), :deep(.p-textarea) {
    width: 100%;
}
</style>