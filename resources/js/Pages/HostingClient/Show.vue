<script setup>
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputNumber from 'primevue/inputnumber';
import Calendar from 'primevue/calendar';
import Textarea from 'primevue/textarea';
import Back from '@/Components/MyComponents/Back.vue';

// --- PROPS ---
const props = defineProps({
    hostingClient: Object
});

const toast = useToast();
const isPaymentDialogVisible = ref(false);
const showPassword = ref(false);

const paymentForm = useForm({
    amount: props.hostingClient.payment_amount || null,
    payment_date: new Date().toISOString().slice(0, 10),
    notes: '',
    receipt: null,
});

// --- HELPERS ---
const formatDate = (value) => {
    if (!value) return '';
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

// --- METHODS ---
const togglePassword = () => {
    showPassword.value = !showPassword.value;
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
        // forceFormData es necesario cuando enviamos archivos por Inertia
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
    <AppLayout title="Detalles del Servicio de Hosting">
        <div class="py-12">
            <div class="max-w-4xl mx-20 mb-6">
                 <Link :href="route('hosting-clients.index')" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-300">
                    <i class="pi pi-arrow-left text-gray-500 dark:text-gray-300"></i>
                </Link>
            </div>
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Columna Izquierda: Cliente y Contactos -->
                <div class="lg:col-span-1 space-y-4">
                    <Card>
                        <template #title>
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold">Información del Cliente</h3>
                                <Link :href="route('clients.show', hostingClient.client.id)">
                                    <Button icon="pi pi-user" text rounded aria-label="Ver Cliente" v-tooltip.bottom="'Ver perfil completo del cliente'"/>
                                </Link>
                            </div>
                        </template>
                        <template #content>
                            <ul class="space-y-4 text-gray-700 dark:text-gray-300">
                                <li class="flex justify-between">
                                    <span class="font-semibold">Nombre:</span>
                                    <span>{{ hostingClient.client.name }}</span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="font-semibold">RFC:</span>
                                    <span>{{ hostingClient.client.tax_id || 'N/A' }}</span>
                                </li>
                                <li class="flex flex-col text-sm">
                                    <span class="font-semibold mb-1">Dirección:</span>
                                    <p class="whitespace-pre-wrap">{{ hostingClient.client.address || 'No especificada' }}</p>
                                </li>
                            </ul>
                        </template>
                    </Card>

                    <Card v-if="hostingClient.client.contacts.length">
                         <template #title>
                            <h3 class="text-xl font-bold">Contactos</h3>
                        </template>
                        <template #content>
                            <div v-for="contact in hostingClient.client.contacts" :key="contact.id" class="not-first:border-t not-first:mt-4 not-first:pt-4">
                                <p class="font-bold">{{ contact.name }} <span class="text-sm font-normal text-gray-500">- {{ contact.position || 'Sin puesto' }}</span></p>
                                <p class="text-sm text-blue-600">{{ contact.email }}</p>
                                <p class="text-sm text-gray-600">{{ contact.phone }}</p>
                            </div>
                        </template>
                    </Card>
                </div>

                <!-- Columna Derecha: Hosting y Pagos -->
                <div class="lg:col-span-2 space-y-4">
                    <Card>
                        <template #title>
                             <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold">Detalles del Servicio</h3>
                                <Link :href="route('hosting-clients.edit', hostingClient.id)">
                                    <Button label="Editar" icon="pi pi-pencil" severity="secondary"/>
                                </Link>
                            </div>
                        </template>
                         <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div class="flex justify-between md:col-span-2 pb-2 border-b border-gray-100 dark:border-zinc-800">
                                    <span class="font-semibold">Tipo:</span>
                                    <Tag :value="hostingClient.hosting_type || 'Interno'" :severity="hostingClient.hosting_type === 'Externo' ? 'info' : 'primary'" />
                                </div>

                                <div class="flex justify-between"><span class="font-semibold">Proveedor:</span><span>{{ hostingClient.service_provider }}</span></div>
                                <div class="flex justify-between"><span class="font-semibold">Estado:</span><Tag :value="hostingClient.status" :severity="getStatusSeverity(hostingClient.status)" /></div>
                                <div class="flex justify-between"><span class="font-semibold">Fecha Registro:</span><span>{{ formatDate(hostingClient.start_date) }}</span></div>
                                
                                <template v-if="hostingClient.hosting_type === 'Interno'">
                                    <div class="flex justify-between"><span class="font-semibold text-emerald-600">Próximo Pago:</span><span class="font-bold">{{ formatDate(hostingClient.next_payment_date) }}</span></div>
                                    <div class="flex justify-between"><span class="font-semibold">Monto:</span><span>{{ formatCurrency(hostingClient.payment_amount) }}</span></div>
                                    <div class="flex justify-between"><span class="font-semibold">Ciclo:</span><span>{{ hostingClient.billing_cycle }}</span></div>
                                </template>

                                <!-- SECCIÓN DE CREDENCIALES -->
                                <div class="flex flex-col md:col-span-2 mt-4 p-4 bg-orange-50/50 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/30 rounded-xl" v-if="hostingClient.support_user || hostingClient.support_password">
                                    <span class="font-bold text-orange-800 dark:text-orange-400 mb-3 flex items-center gap-2">
                                        <i class="pi pi-lock"></i> Credenciales de Soporte
                                    </span>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <span class="text-sm text-gray-500 block">Usuario / Correo:</span>
                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ hostingClient.support_user || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-sm text-gray-500 block">Contraseña:</span>
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium text-gray-800 dark:text-gray-200 tracking-wider">
                                                    {{ showPassword ? (hostingClient.support_password || 'N/A') : '••••••••' }}
                                                </span>
                                                <Button v-if="hostingClient.support_password" :icon="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'" text rounded size="small" @click="togglePassword" class="!text-gray-500" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col md:col-span-2 mt-2" v-if="hostingClient.hosted_urls && hostingClient.hosted_urls.length">
                                    <span class="font-semibold mb-2">URLs de los Sistemas/Sitios:</span>
                                    <ul class="list-disc pl-5">
                                        <li v-for="url in hostingClient.hosted_urls" :key="url"><a :href="url" target="_blank" class="text-blue-500 hover:underline">{{ url }}</a></li>
                                    </ul>
                                </div>
                                 <div class="flex flex-col md:col-span-2" v-if="hostingClient.notes">
                                    <span class="font-semibold mb-2">Notas:</span>
                                    <p class="text-sm p-3 bg-gray-100 dark:bg-gray-700 rounded-md whitespace-pre-wrap">{{ hostingClient.notes }}</p>
                                </div>
                            </div>
                         </template>
                    </Card>

                    <!-- Tarjeta de Pagos (Solo visible si es Interno o si ya tiene historial) -->
                     <Card v-if="hostingClient.hosting_type === 'Interno' || hostingClient.payments.length > 0">
                        <template #title>
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold">Historial de Pagos</h3>
                                <Button v-if="hostingClient.hosting_type === 'Interno'" label="Añadir Pago" icon="pi pi-plus" size="small" @click="openPaymentDialog" class="!text-[var(--primary-text-color)]" />
                            </div>
                        </template>
                        <template #content>
                            <DataTable :value="hostingClient.payments" responsiveLayout="scroll">
                                <template #empty>No se han registrado pagos.</template>
                                <Column field="payment_date" header="Fecha" sortable>
                                    <template #body="{ data }">{{ formatDate(data.payment_date) }}</template>
                                </Column>
                                <Column field="amount" header="Monto" sortable bodyClass="text-right">
                                     <template #body="{ data }">
                                        <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(data.amount) }}</span>
                                     </template>
                                </Column>
                                <Column field="receipt_path" header="Comprobante" bodyClass="text-center">
                                    <template #body="{ data }">
                                        <a v-if="data.receipt_path" :href="`/storage/${data.receipt_path}`" target="_blank" title="Ver Comprobante">
                                            <Button icon="pi pi-file-pdf" text rounded severity="danger" />
                                        </a>
                                        <span v-else class="text-gray-400 text-sm">-</span>
                                    </template>
                                </Column>
                                <Column field="notes" header="Notas"></Column>
                            </DataTable>
                        </template>
                     </Card>
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
                    
                    <!-- Campo de Archivo (Comprobante) -->
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
/* Estilos para asegurar que el autocompletado del navegador no altere el diseño */
.p-inputtext, .p-inputnumber-input {
    width: 100% !important;
}
</style>