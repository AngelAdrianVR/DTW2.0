<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Back from '@/Components/MyComponents/Back.vue';

// --- PROPS ---
const props = defineProps({
    hostingClient: {
        type: Object,
        required: true,
    }
});

// --- HELPERS ---
const formatDate = (value) => {
    if (!value) return '';
    const date = new Date(value);
    date.setMinutes(date.getMinutes() + date.getTimezoneOffset()); // Adjust for timezone
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
</script>

<template>
    <AppLayout title="Detalles del Servicio de Hosting">
        <div class="py-12">
            <Back :href="route('hosting-clients.index')" />
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
                                <h3 class="text-xl font-bold">Detalles del Servicio de Hosting</h3>
                                <Link :href="route('hosting-clients.edit', hostingClient.id)">
                                    <Button label="Editar" icon="pi pi-pencil" severity="secondary"/>
                                </Link>
                            </div>
                        </template>
                         <template #content>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4">
                                <div class="flex justify-between"><span class="font-semibold">Proveedor:</span><span>{{ hostingClient.service_provider }}</span></div>
                                <div class="flex justify-between"><span class="font-semibold">Estado:</span><Tag :value="hostingClient.status" :severity="getStatusSeverity(hostingClient.status)" /></div>
                                <div class="flex justify-between"><span class="font-semibold">Fecha de Inicio:</span><span>{{ formatDate(hostingClient.start_date) }}</span></div>
                                <div class="flex justify-between"><span class="font-semibold">Próximo Pago:</span><span>{{ formatDate(hostingClient.next_payment_date) }}</span></div>
                                <div class="flex justify-between"><span class="font-semibold">Monto:</span><span>{{ formatCurrency(hostingClient.payment_amount) }}</span></div>
                                <div class="flex justify-between"><span class="font-semibold">Ciclo:</span><span>{{ hostingClient.billing_cycle }}</span></div>
                                <div class="flex flex-col md:col-span-2" v-if="hostingClient.hosted_urls && hostingClient.hosted_urls.length">
                                    <span class="font-semibold mb-2">URLs Alojadas:</span>
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

                     <Card>
                        <template #title>
                            <h3 class="text-xl font-bold">Historial de Pagos</h3>
                        </template>
                        <template #content>
                            <DataTable :value="hostingClient.payments" responsiveLayout="scroll">
                                <template #empty>No se han registrado pagos.</template>
                                <Column field="payment_date" header="Fecha de Pago" sortable>
                                    <template #body="{ data }">{{ formatDate(data.payment_date) }}</template>
                                </Column>
                                <Column field="amount" header="Monto" sortable bodyClass="text-right">
                                     <template #body="{ data }">{{ formatCurrency(data.amount) }}</template>
                                </Column>
                                 <Column field="notes" header="Notas"></Column>
                            </DataTable>
                        </template>
                     </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
