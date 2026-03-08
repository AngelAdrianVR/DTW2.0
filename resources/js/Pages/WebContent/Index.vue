<script setup>
import { ref, watch } from 'vue';
import { useForm, Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue'; // O el layout de tu ERP

// Importa los componentes de PrimeVue que vamos a utilizar
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Dropdown from 'primevue/dropdown';
import FileUpload from 'primevue/fileupload';
import Card from 'primevue/card';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import Dialog from 'primevue/dialog';
import Checkbox from 'primevue/checkbox';
import Carousel from 'primevue/carousel';
import Calendar from 'primevue/calendar';
import Toast from 'primevue/toast';
import { useConfirm } from "primevue/useconfirm";
import { useToast } from 'primevue/usetoast';

// Props
const props = defineProps({
    webcontents: Object,
});

const page = usePage();
const confirm = useConfirm();
const toast = useToast();
const fileUploadRef = ref(null);
const editFileUploadRef = ref(null); // <-- Referencia para el FileUpload de edición
const isEditModalVisible = ref(false);

// Watcher para notificaciones de éxito y error
watch(() => page.props.flash.success, (newValue) => {
    if (newValue) {
        toast.add({ severity: 'success', summary: 'Éxito', detail: newValue, life: 3000 });
    }
});
watch(() => page.props.flash.error, (newValue) => {
    if (newValue) {
        toast.add({ severity: 'error', summary: 'Error', detail: newValue, life: 3000 });
    }
});


// Estados
const types = ref([
    { label: 'Portafolio de Proyectos', value: 'portfolio' },
    { label: 'Proyectos Propios', value: 'own_projects' },
    { label: 'Logos de Clientes', value: 'client_logos' },
    { label: 'Publicidad', value: 'advertising' },
]);

// Formulario para crear nuevo contenido
const form = useForm({
    type: 'portfolio',
    spanish_title: '',
    english_title: '',
    spanish_content: '',
    english_content: '',
    link_url: '',
    images: [],
    is_published: true,
    end_date: null,
});

// Formulario para editar contenido
const editForm = useForm({
    id: null,
    spanish_title: '',
    english_title: '',
    spanish_content: '',
    english_content: '',
    link_url: '',
    is_published: true,
    end_date: null,
    media: [], // <-- Para almacenar las imágenes existentes
    new_images: [], // <-- Para las nuevas imágenes a subir
    _method: 'put', // <-- Para "engañar" al método POST
});


// Función para enviar el formulario de creación
const submit = () => {
    if (fileUploadRef.value) {
        form.images = fileUploadRef.value.files;
    }
    form.post(route('admin.webcontents.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileUploadRef.value) {
                fileUploadRef.value.clear();
            }
        },
    });
};

// Función para abrir el modal de edición
const editContent = (content) => {
    editForm.id = content.id;
    editForm.spanish_title = content.spanish_title;
    editForm.english_title = content.english_title;
    editForm.spanish_content = content.spanish_content;
    editForm.english_content = content.english_content;
    editForm.link_url = content.link_url;
    editForm.is_published = content.is_published;
    editForm.end_date = content.end_date ? new Date(content.end_date) : null;
    editForm.media = content.media; // <-- Cargar las imágenes existentes
    isEditModalVisible.value = true;
};

// Función para enviar el formulario de edición
const updateContent = () => {
    // Asignar los nuevos archivos antes de enviar
    if (editFileUploadRef.value) {
        editForm.new_images = editFileUploadRef.value.files;
    }

    // Usamos POST porque PUT no soporta multipart/form-data de forma nativa
    editForm.post(route('admin.webcontents.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalVisible.value = false;
            editForm.reset();
             if (editFileUploadRef.value) {
                editFileUploadRef.value.clear();
            }
        },
    });
};

// Función para eliminar un item con confirmación
const deleteContent = (id) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este elemento?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        acceptLabel:'Sí, Eliminar',
        rejectLabel:'Cancelar',
        accept: () => {
             useForm({}).delete(route('admin.webcontents.destroy', id), {
                preserveScroll: true,
            });
        },
    });
};

// --- INICIO: Nueva función para confirmar y eliminar una imagen ---
const confirmDeleteImage = (mediaId) => {
     confirm.require({
        message: '¿Estás seguro de que quieres eliminar esta imagen?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text !text-zinc-600 dark:!text-zinc-600 !rounded-xl !px-4 !py-2 hover:!bg-zinc-100',
        acceptClass: '!bg-red-600 hover:!bg-red-700 !border-0 !rounded-xl !px-4 !py-2 !text-[var(--primary-text-color)]',
        rejectLabel:'Cancelar',
        acceptLabel:'Sí, Eliminar',
        accept: () => {
             useForm({}).delete(route('admin.media.destroy', mediaId), {
                preserveScroll: true,
                onSuccess: () => {
                    // Remover la imagen de la lista en el frontend
                    editForm.media = editForm.media.filter(img => img.id !== mediaId);
                }
            });
        },
    });
};

// --- FIN: Nueva función para confirmar y eliminar una imagen ---

// Devuelve el nombre legible de la seccion
const getSectionName = (key) => {
    const type = types.value.find(t => t.value === key);
    return type ? type.label : key;
};

// Formatea la fecha
const formatDate = (value) => {
    if (value) {
        return new Date(value).toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    return '';
};

</script>

<template>
    <AppLayout title="Gestionar Contenido Web">
        <div class="p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto">
                <Toast />
                <ConfirmDialog />

                <!-- Modal de Edición (Estilo Zinc) -->
                <Dialog header="Editar Contenido" v-model:visible="isEditModalVisible" :modal="true" :style="{ width: '70vw' }" 
                    :breakpoints="{'960px': '80vw', '641px': '95vw'}"
                    :pt="{ root: { class: 'dark:bg-zinc-900 dark:border-zinc-700' }, header: { class: 'dark:bg-zinc-900 dark:text-zinc-200' }, content: { class: 'dark:bg-zinc-900' }, footer: { class: 'dark:bg-zinc-900' } }"
                >
                    <form @submit.prevent="updateContent" class="p-fluid w-full">
                        <div class="field">
                            <label for="edit_spanish_title" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Título en Español (Opcional)</label>
                            <InputText id="edit_spanish_title" type="text" v-model="editForm.spanish_title" class="w-full" />
                            <small v-if="editForm.errors.spanish_title" class="p-error">{{ editForm.errors.spanish_title }}</small>
                        </div>
                        <div class="field mt-4">
                            <label for="edit_english_title" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Título en Inglés (Opcional)</label>
                            <InputText id="edit_english_title" type="text" v-model="editForm.english_title" class="w-full" />
                            <small v-if="editForm.errors.english_title" class="p-error">{{ editForm.errors.english_title }}</small>
                        </div>
                        <div class="field mt-4">
                            <label for="edit_link_url" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">URL (Opcional)</label>
                            <InputText id="edit_link_url" type="url" v-model="editForm.link_url" class="w-full" />
                            <small v-if="editForm.errors.link_url" class="p-error">{{ editForm.errors.link_url }}</small>
                        </div>
                        <div class="field mt-4">
                            <label for="edit_spanish_content" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Descripción en Español (Opcional)</label>
                            <Textarea id="edit_spanish_content" v-model="editForm.spanish_content" class="w-full" rows="4" />
                            <small v-if="editForm.errors.spanish_content" class="p-error">{{ editForm.errors.spanish_content }}</small>
                        </div>
                        <div class="field mt-4">
                            <label for="edit_english_content" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Descripción en Inglés (Opcional)</label>
                            <Textarea id="edit_english_content" v-model="editForm.english_content" rows="4" class="w-full" />
                            <small v-if="editForm.errors.english_content" class="p-error">{{ editForm.errors.english_content }}</small>
                        </div>
                        <div class="field mt-4">
                            <label for="edit_end_date" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Fecha de Finalización (Opcional)</label>
                            <Calendar id="edit_end_date" v-model="editForm.end_date" dateFormat="yy-mm-dd" /><br>
                            <small class="text-gray-500 dark:text-zinc-500">El contenido se ocultará automáticamente después de esta fecha.</small>
                        </div>
                        <div class="field-checkbox mt-4 flex items-center">
                            <Checkbox id="edit_is_published" v-model="editForm.is_published" :binary="true" />
                            <label for="edit_is_published" class="ml-2 dark:text-zinc-300">Publicado</label>
                        </div>

                        <!-- INICIO: Sección para gestionar imágenes -->
                        <div class="field mt-6 border-t pt-4 border-gray-100 dark:border-zinc-800">
                            <h4 class="font-semibold mb-3 text-gray-800 dark:text-zinc-200">Imágenes Actuales</h4>
                            <div v-if="editForm.media.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="image in editForm.media" :key="image.id" class="relative group">
                                    <img :src="image.original_url" :alt="image.name" class="w-full h-24 object-cover rounded-lg shadow-md border dark:border-zinc-700">
                                    <Button
                                        icon="pi pi-times"
                                        class="p-button-danger p-button-rounded absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                        @click="confirmDeleteImage(image.id)"
                                    />
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 dark:text-zinc-500">No hay imágenes para este contenido.</div>
                        </div>

                        <div class="field mt-4">
                            <h4 class="font-semibold mb-3 text-gray-800 dark:text-zinc-200">Agregar Nuevas Imágenes</h4>
                            <FileUpload
                                ref="editFileUploadRef"
                                name="new_images[]"
                                :multiple="true"
                                :showUploadButton="false"
                                :showCancelButton="false"
                                accept="image/*"
                                chooseLabel="Seleccionar Imágenes"
                                class="!text-[var(--primary-text-color)]"
                            >
                                <template #empty>
                                    <p class="text-gray-500 dark:text-zinc-400 ">Arrastra y suelta las imágenes aquí.</p>
                                </template>
                            </FileUpload>
                            <small v-if="editForm.errors.new_images" class="p-error">{{ editForm.errors.new_images }}</small>
                        </div>
                        <!-- FIN: Sección para gestionar imágenes -->

                    </form>
                    <template #footer>
                        <Button label="Cancelar" icon="pi pi-times" @click="isEditModalVisible = false" class="p-button-text !text-gray-600 dark:!text-zinc-400"/>
                        <Button type="submit" @click="updateContent" label="Guardar Cambios" icon="pi pi-check" :loading="editForm.processing" class="!text-[var(--primary-text-color)]"/>
                    </template>
                </Dialog>

                <!-- Header Principal -->
                <header class="mb-8">
                    <h1 class="text-3xl font-bold dark:text-zinc-100 text-[#212121]">Gestor de Contenido</h1>
                    <p class="text-gray-400 dark:text-zinc-400 mt-1">Administra tu portafolio, logos y banners publicitarios.</p>
                </header>

                <!-- FORMULARIO PARA AGREGAR NUEVO CONTENIDO -->
                <div class="p-6 mb-8 bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-800 transition-colors duration-300">
                    <h3 class="text-xl font-bold mb-6 text-gray-900 dark:text-zinc-100">Agregar Nuevo Contenido</h3>
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-fluid md:col-span-2">
                                <label for="type" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Sección</label>
                                <Dropdown id="type" v-model="form.type" :options="types" optionLabel="label" optionValue="value" placeholder="Selecciona una sección" class="w-full" />
                            </div>
                            <div class="p-fluid">
                                    <label for="spanish_title" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Título en Español (Opcional)</label>
                                <InputText id="spanish_title" type="text" v-model="form.spanish_title" class="w-full" />
                                <small v-if="form.errors.spanish_title" class="p-error">{{ form.errors.spanish_title }}</small>
                            </div>
                            <div class="p-fluid">
                                    <label for="english_title" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Título en Inglés (Opcional)</label>
                                <InputText id="english_title" type="text" v-model="form.english_title" class="w-full" />
                                <small v-if="form.errors.english_title" class="p-error">{{ form.errors.english_title }}</small>
                            </div>

                                <div class="md:col-span-2">
                                    <label for="spanish_content" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Descripción en Español (Opcional)</label>
                                <Textarea id="spanish_content" v-model="form.spanish_content" rows="3" class="w-full" />
                                    <small v-if="form.errors.spanish_content" class="p-error">{{ form.errors.spanish_content }}</small>
                            </div>
                                <div class="md:col-span-2">
                                    <label for="english_content" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Descripción en Inglés (Opcional)</label>
                                <Textarea id="english_content" v-model="form.english_content" rows="3" class="w-full" />
                                    <small v-if="form.errors.english_content" class="p-error">{{ form.errors.english_content }}</small>
                            </div>
                            
                            <div class="p-fluid">
                                <label for="link_url" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">URL (Opcional)</label>
                                <InputText id="link_url" type="url" v-model="form.link_url" class="w-full" />
                                    <small v-if="form.errors.link_url" class="p-error">{{ form.errors.link_url }}</small>
                            </div>

                            <div class="p-fluid">
                                <label for="end_date" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Fecha de Finalización (Opcional)</label>
                                <Calendar id="end_date" v-model="form.end_date" dateFormat="yy-mm-dd" class="w-full" />
                                <small class="text-gray-500 dark:text-zinc-500">El contenido se ocultará automáticamente después de esta fecha.</small>
                            </div>

                            <div class="md:col-span-2">
                                <label for="images" class="block font-medium text-sm text-gray-700 dark:text-zinc-300 mb-2">Imágenes</label>
                                <FileUpload ref="fileUploadRef" name="images[]" :multiple="true" :showUploadButton="false" :showCancelButton="false" accept="image/*" chooseLabel="Seleccionar Imágenes" class="!text-[var(--primary-text-color)]">
                                    <template #empty>
                                        <p class="text-gray-500 dark:text-zinc-400">Arrastra y suelta las imágenes aquí.</p>
                                    </template>
                                </FileUpload>
                                <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full mt-2 h-2 rounded overflow-hidden">
                                    {{ form.progress.percentage }}%
                                </progress>
                                <small v-if="form.errors.images" class="p-error">{{ form.errors.images }}</small>
                            </div>

                            <div class="flex items-center">
                                <Checkbox id="is_published" v-model="form.is_published" :binary="true" />
                                <label for="is_published" class="ml-2 block text-sm text-gray-900 dark:text-zinc-200">Publicado</label>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <Button type="submit" label="Guardar Contenido" icon="pi pi-check" :loading="form.processing" class="!text-[var(--primary-text-color)]"/>
                        </div>
                    </form>
                </div>

                <!-- VISUALIZACIÓN DEL CONTENIDO EXISTENTE -->
                <div class="zinc-tabs">
                    <TabView>
                        <TabPanel v-for="(group, type) in webcontents" :key="type" :header="getSectionName(type)">
                                <div v-if="group.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
                                <Card v-for="content in group" :key="content.id" class="overflow-hidden transition-all duration-300 ease-in-out hover:shadow-lg dark:bg-zinc-900 dark:border-zinc-800 border border-gray-100 shadow-sm !rounded-xl flex flex-col">
                                        <template #header>
                                        <Carousel :value="content.media" :numVisible="1" :numScroll="1" :show-indicators="content.media.length > 1" 
                                            :pt="{ root: { class: 'dark:border-zinc-800' } }">
                                            <template #item="slotProps">
                                                <div class="p-2 bg-gray-50 dark:bg-zinc-950/50 flex items-center justify-center h-48">
                                                    <img :src="slotProps.data.original_url" :alt="content.spanish_title || content.english_title" class="w-full h-full object-contain rounded-md">
                                                </div>
                                            </template>
                                        </Carousel>
                                    </template>
                                    <template #title>
                                        <div class="truncate font-bold text-gray-800 dark:text-zinc-100"> {{ content.spanish_title || 'Sin título' }} </div>
                                        <div class="truncate text-sm text-gray-500 dark:text-zinc-400 font-normal"> {{ content.english_title || '' }} </div>
                                    </template>
                                    <template #subtitle>
                                        <a :href="content.link_url" target="_blank" class="text-blue-500 dark:text-blue-400 hover:underline truncate block text-sm">{{ content.link_url || '' }}</a>
                                    </template>
                                    <template #content>
                                        <div class="text-sm text-gray-600 dark:text-zinc-400 h-20 overflow-y-auto flex-grow custom-scrollbar">
                                            <p v-if="content.spanish_content"><strong class="font-semibold dark:text-zinc-300">ES:</strong> {{ content.spanish_content }}</p>
                                            <p v-if="content.english_content" class="mt-1"><strong class="font-semibold dark:text-zinc-300">EN:</strong> {{ content.english_content }}</p>
                                            <p v-if="!content.spanish_content && !content.english_content" class="italic text-zinc-500">Sin descripción</p>
                                        </div>
                                        <!-- INICIO: Mostrar fecha de expiración -->
                                        <div v-if="content.end_date" class="mt-3 text-xs text-red-500 dark:text-red-400 flex items-center bg-red-50 dark:bg-red-900/10 p-2 rounded-lg w-fit">
                                            <i class="pi pi-calendar-times mr-2"></i>
                                            <span>Expira: {{ formatDate(content.end_date) }}</span>
                                        </div>
                                        <!-- FIN: Mostrar fecha de expiración -->
                                    </template>
                                    <template #footer>
                                            <div class="flex justify-between items-center mt-auto border-t border-gray-100 dark:border-zinc-800 pt-3">
                                                <Tag :value="content.is_published ? 'Publicado' : 'Borrador'" :severity="content.is_published ? 'success' : 'warning'" />
                                                <div class="flex items-center gap-1">
                                                <Button @click="editContent(content)" icon="pi pi-pencil" text rounded aria-label="Editar" class="!text-gray-500 dark:!text-zinc-400 hover:!bg-gray-100 dark:hover:!bg-zinc-800" />
                                                <Button @click="deleteContent(content.id)" icon="pi pi-trash" text rounded severity="danger" aria-label="Eliminar" />
                                                </div>
                                            </div>
                                    </template>
                                </Card>
                            </div>
                                <div v-else class="text-center py-12 text-gray-500 dark:text-zinc-500 bg-white dark:bg-zinc-900 rounded-2xl border border-dashed border-gray-300 dark:border-zinc-700">
                                <i class="pi pi-inbox text-4xl mb-3 opacity-50"></i>
                                <p>No hay contenido en esta sección.</p>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Estilos para asegurar que el autocompletado del navegador no altere el diseño */
.p-inputtext, .p-inputnumber-input, .p-dropdown {
    width: 100% !important;
}

/* Zinc Theme Overrides for PrimeVue TabView */
/* Esto hace que las pestañas se vean bien en modo oscuro con la paleta Zinc */
:deep(.zinc-tabs .p-tabview-nav) {
    background-color: transparent !important;
    border-color: #e4e4e7; /* zinc-200 */
}
.dark :deep(.zinc-tabs .p-tabview-nav) {
    border-color: #27272a; /* zinc-800 */
}

:deep(.zinc-tabs .p-tabview-nav li .p-tabview-nav-link) {
    background-color: transparent !important;
    color: #52525b; /* zinc-600 */
    border-color: transparent;
    font-weight: 500;
}
.dark :deep(.zinc-tabs .p-tabview-nav li .p-tabview-nav-link) {
    color: #a1a1aa; /* zinc-400 */
}

:deep(.zinc-tabs .p-tabview-nav li:not(.p-highlight):hover .p-tabview-nav-link) {
    color: #18181b; /* zinc-900 */
    border-color: #d4d4d8;
}
.dark :deep(.zinc-tabs .p-tabview-nav li:not(.p-highlight):hover .p-tabview-nav-link) {
    color: #e4e4e7; /* zinc-200 */
    border-color: #3f3f46;
}

:deep(.zinc-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #18181b; /* zinc-900 */
    border-color: #18181b;
    border-bottom-width: 2px;
}
.dark :deep(.zinc-tabs .p-tabview-nav li.p-highlight .p-tabview-nav-link) {
    color: #f4f4f5; /* zinc-100 */
    border-color: #f4f4f5;
}

:deep(.zinc-tabs .p-tabview-panels) {
    background-color: transparent !important;
    padding: 1.5rem 0;
}
</style>