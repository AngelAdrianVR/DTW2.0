<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';

// Props
const props = defineProps(['hostingClient']);

const toast = useToast();
const isPaymentDialogVisible = ref(false);
const showPasswords = ref({}); // Objeto dinamico para cada credencial

const paymentForm = useForm({
    amount: props.hostingClient.payment_amount || null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    receipt: null,
});

// Helpers
const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset());
    return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const formatCurrency = (value) => {
    if (value === null || isNaN(value)) value = 0;
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(value);
};

const getStatusSeverity = (status) => {
    const statusMap = { 'Activo': 'success', 'Suspendido': 'warning', 'Cancelado': 'danger' };
    return statusMap[status] || 'info';
};

// Methods
const togglePassword = (index) => {
    showPasswords.value[index] = !showPasswords.value[index];
};

const openPaymentDialog = () => {
    paymentForm.reset();
    paymentForm.amount = props.hostingClient.payment_amount;
    isPaymentDialogVisible.value = true;
};

const closePaymentDialog = () => {
    isPaymentDialogVisible.value = false;
    paymentForm.reset();
};

const handleFileChange = (event) => {
    paymentForm.receipt = event.target.files[0];
};

const submitPayment = () => {
    paymentForm.post(route('hosting-clients.payments.store', props.hostingClient.id), {
        preserveScroll: true,
        forceFormData: true, 
        onSuccess: () => {
            closePaymentDialog();
            toast.add({
                severity: 'success', summary: 'Éxito',
                detail: 'Pago y comprobante registrados correctamente', life: 3000
            });
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).join(' ');
            toast.add({
                severity: 'error', summary: 'Error',
                detail: errorMessages || 'No se pudo registrar el pago.', life: 3000
            });
        }
    });
};
</script>

<template>
    <AppLayout title="Detalles del Servicio">
        <!-- Fondo estilo Apple UI: muy claro o completamente oscuro -->
        <div class="py-12 bg-[#F5F5F7] dark:bg-black min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Encabezado con Nav -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                    <div class="flex items-center gap-4">
                        <Link :href="route('hosting-clients.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-[#1C1C1E] border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition-all duration-300">
                            <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                        </Link>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight leading-tight">Detalles del Servicio</h1>
                            <p class="text-gray-500 dark:text-gray-400">{{ hostingClient.service_provider }}</p>
                        </div>
                    </div>
                    <Link :href="route('hosting-clients.edit', hostingClient.id)">
                        <Button label="Editar Configuración" icon="pi pi-pencil" rounded class="!rounded-xl p-button-md !text-[var(--primary-text-color)]" />
                    </Link>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Columna Izquierda: Información de Cliente y Metadatos -->
                    <div class="lg:col-span-1 space-y-6">
                        
                        <!-- Tarjeta de Cliente Estilo Apple -->
                        <div class="bg-white dark:bg-[#1C1C1E] rounded-[2rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-zinc-800 transition-all">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 tracking-tight">Información del Cliente</h3>
                                <Link :href="route('clients.show', hostingClient.client.id)">
                                    <div class="w-8 h-8 rounded-full bg-gray-100 dark:bg-zinc-800 flex items-center justify-center hover:bg-gray-200 transition-colors" v-tooltip.bottom="'Ver perfil completo'">
                                        <i class="pi pi-user text-gray-500 text-sm"></i>
                                    </div>
                                </Link>
                            </div>
                            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                                <div>
                                    <span class="block text-xs uppercase tracking-wider text-gray-400 mb-1">Nombre Comercial</span>
                                    <span class="font-medium text-gray-900 dark:text-white text-base">{{ hostingClient.client.name }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 border-t border-gray-100 dark:border-zinc-800 pt-4">
                                    <div>
                                        <span class="block text-xs uppercase tracking-wider text-gray-400 mb-1">RFC</span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ hostingClient.client.tax_id || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta de Contactos Reducida -->
                        <div v-if="hostingClient.client.contacts.length" class="bg-white dark:bg-[#1C1C1E] rounded-[2rem] p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-zinc-800">
                            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 tracking-tight mb-4">Contactos Directos</h3>
                            <div class="space-y-4">
                                <div v-for="contact in hostingClient.client.contacts" :key="contact.id" class="flex flex-col border-b border-gray-50 dark:border-zinc-800/50 last:border-0 pb-3 last:pb-0">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ contact.name }} <span class="text-xs text-gray-400 font-normal ml-1">({{ contact.position || 'Sin puesto' }})</span></span>
                                    <a :href="`mailto:${contact.email}`" class="text-blue-500 hover:text-blue-600 text-sm mt-1 flex items-center gap-2"><i class="pi pi-envelope text-xs"></i> {{ contact.email }}</a>
                                    <a v-if="contact.phone" :href="`tel:${contact.phone}`" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-sm mt-1 flex items-center gap-2"><i class="pi pi-phone text-xs"></i> {{ contact.phone }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna Derecha: Detalles Core y Pagos -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Tarjeta Principal: Estado del Sistema -->
                        <div class="bg-white dark:bg-[#1C1C1E] rounded-[2rem] p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-zinc-800">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-100 dark:border-zinc-800 pb-6 mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Estado del Sistema</h3>
                                    <div class="mt-2 flex items-center gap-3">
                                        <Tag :value="hostingClient.hosting_type || 'Interno'" :severity="hostingClient.hosting_type === 'Externo' ? 'secondary' : 'primary'" class="!rounded-full !px-3" />
                                        <Tag :value="hostingClient.status" :severity="getStatusSeverity(hostingClient.status)" class="!rounded-full !px-3" />
                                    </div>
                                </div>
                                <div class="mt-4 sm:mt-0 text-left sm:text-right">
                                    <span class="block text-sm text-gray-500 dark:text-zinc-400">Fecha de Registro</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(hostingClient.start_date) }}</span>
                                </div>
                            </div>

                            <!-- Grid de Datos de Facturación (Si aplica) -->
                            <div v-if="hostingClient.hosting_type === 'Interno'" class="grid grid-cols-1 sm:grid-cols-3 gap-6 bg-[#F5F5F7] dark:bg-[#2C2C2E]/40 rounded-2xl p-5 mb-8">
                                <div class="flex flex-col">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">Próximo Pago</span>
                                    <span class="text-lg font-bold" :class="new Date(hostingClient.next_payment_date) < new Date() ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400'">
                                        {{ formatDate(hostingClient.next_payment_date) }}
                                    </span>
                                </div>
                                <div class="flex flex-col sm:border-l border-gray-200 dark:border-zinc-700/50 sm:pl-6">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">Monto ({{ hostingClient.billing_cycle }})</span>
                                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(hostingClient.payment_amount) }}</span>
                                </div>
                            </div>

                            <!-- Nueva Sección Múltiples Credenciales de Soporte -->
                            <div v-if="hostingClient.support_credentials && hostingClient.support_credentials.length > 0" class="mb-8">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <i class="pi pi-key text-orange-500"></i> Accesos y Credenciales
                                </h4>
                                <div class="space-y-3">
                                    <div v-for="(cred, index) in hostingClient.support_credentials" :key="index" class="bg-orange-50/50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <span class="text-xs font-bold text-orange-800/60 dark:text-orange-400/80 uppercase tracking-wider block mb-1">Usuario / ID</span>
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ cred.user || 'N/A' }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <span class="text-xs font-bold text-orange-800/60 dark:text-orange-400/80 uppercase tracking-wider block mb-1">Contraseña</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-gray-900 dark:text-gray-100 tracking-wider bg-white/50 dark:bg-black/30 px-2 py-0.5 rounded-md">
                                                    {{ showPasswords[index] ? (cred.password || 'N/A') : '••••••••' }}
                                                </span>
                                                <Button v-if="cred.password" :icon="showPasswords[index] ? 'pi pi-eye-slash' : 'pi pi-eye'" text rounded size="small" @click="togglePassword(index)" class="!text-orange-600 dark:!text-orange-400 !w-6 !h-6" />
                                            </div>
                                        </div>
                                         <div class="flex-1" v-if="cred.notes">
                                            <span class="text-xs font-bold text-orange-800/60 dark:text-orange-400/80 uppercase tracking-wider block mb-1">Nota</span>
                                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ cred.notes }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- URLs -->
                            <div v-if="hostingClient.hosted_urls && hostingClient.hosted_urls.length" class="mb-8">
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <i class="pi pi-globe text-blue-500"></i> Sitios Alojados
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    <a v-for="url in hostingClient.hosted_urls" :key="url" :href="url" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 dark:bg-zinc-800/50 hover:bg-gray-100 dark:hover:bg-zinc-700/50 rounded-xl text-sm font-medium text-blue-600 dark:text-blue-400 transition-colors border border-gray-100 dark:border-zinc-800">
                                        {{ url ? url.replace(/^https?:\/\//, '') : 'URL no válida' }} <i class="pi pi-external-link text-[10px]"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Notas Generales -->
                            <div v-if="hostingClient.notes" class="mt-6 pt-6 border-t border-gray-100 dark:border-zinc-800">
                                <h4 class="text-sm font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Notas del Servicio</h4>
                                <p class="text-gray-700 dark:text-gray-300 text-sm whitespace-pre-wrap leading-relaxed">{{ hostingClient.notes }}</p>
                            </div>
                        </div>

                        <!-- Tarjeta de Historial de Pagos -->
                        <div v-if="hostingClient.hosting_type === 'Interno' || hostingClient.payments.length > 0" class="bg-white dark:bg-[#1C1C1E] rounded-[2rem] p-6 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-gray-100 dark:border-zinc-800">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Pagos</h3>
                                <Button v-if="hostingClient.hosting_type === 'Interno'" label="Añadir Pago" icon="pi pi-plus" size="small" rounded @click="openPaymentDialog" class="!bg-emerald-600 hover:!bg-emerald-700 !border-0 !text-[var(--primary-text-color)] font-medium" />
                            </div>
                            
                            <div class="overflow-hidden border border-gray-100 dark:border-zinc-800 rounded-2xl">
                                <DataTable :value="hostingClient.payments" responsiveLayout="scroll" :pt="{
                                    headerRow: { class: 'bg-gray-50 dark:bg-[#2C2C2E]' },
                                    row: { class: 'dark:bg-[#1C1C1E] dark:hover:bg-[#2C2C2E]/50' }
                                }">
                                    <template #empty><div class="p-6 text-center text-gray-500">No se han registrado pagos para este servicio.</div></template>
                                    <Column field="payment_date" header="Fecha" sortable>
                                        <template #body="{ data }"><span class="font-medium text-gray-800 dark:text-gray-200">{{ formatDate(data.payment_date) }}</span></template>
                                    </Column>
                                    <Column field="amount" header="Monto" sortable bodyClass="text-right">
                                        <template #body="{ data }">
                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.amount) }}</span>
                                        </template>
                                    </Column>
                                    <Column field="receipt_path" header="Comprobante" bodyClass="text-center">
                                        <template #body="{ data }">
                                            <a v-if="data.receipt_path" :href="`/storage/${data.receipt_path}`" target="_blank" title="Ver Comprobante">
                                                <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors mx-auto">
                                                    <i class="pi pi-file-pdf text-red-500 text-sm"></i>
                                                </div>
                                            </a>
                                            <span v-else class="text-gray-400 text-sm">-</span>
                                        </template>
                                    </Column>
                                    <Column field="notes" header="Notas" bodyClass="text-sm text-gray-600 dark:text-gray-400"></Column>
                                </DataTable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Agregar Pago con Comprobante (Apple Style) -->
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
                        <span class="text-xs text-gray-500 dark:text-zinc-400">Para: {{ hostingClient.client.name }}</span>
                    </div>
                </div>
            </template>
            <form @submit.prevent="submitPayment">
                <div class="flex flex-col gap-5 mt-2">
                    <div class="flex flex-col gap-2">
                        <label for="amount" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Monto del Pago <span class="text-red-500">*</span></label>
                        <InputNumber id="amount" v-model="paymentForm.amount" mode="currency" currency="MXN" locale="es-MX" class="!rounded-xl w-full" :class="{ 'p-invalid': paymentForm.errors.amount }" required />
                        <small v-if="paymentForm.errors.amount" class="p-error">{{ paymentForm.errors.amount }}</small>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label for="payment_date" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Fecha del Pago <span class="text-red-500">*</span></label>
                        <Calendar id="payment_date" v-model="paymentForm.payment_date" dateFormat="yy-mm-dd" class="!rounded-xl w-full" :class="{ 'p-invalid': paymentForm.errors.payment_date }" required />
                        <small v-if="paymentForm.errors.payment_date" class="p-error">{{ paymentForm.errors.payment_date }}</small>
                    </div>
                    
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Subir Comprobante (Opcional)</label>
                        <input type="file" @change="handleFileChange" accept=".pdf,.jpg,.jpeg,.png"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-zinc-800 dark:file:text-emerald-400 transition-colors cursor-pointer" />
                        <small v-if="paymentForm.errors.receipt" class="p-error">{{ paymentForm.errors.receipt }}</small>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label for="notes" class="text-sm font-semibold text-gray-700 dark:text-zinc-300">Notas Adicionales</label>
                        <Textarea id="notes" v-model="paymentForm.notes" rows="2" class="!rounded-xl w-full" />
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
.p-inputtext, .p-inputnumber-input {
    width: 100% !important;
}
</style>