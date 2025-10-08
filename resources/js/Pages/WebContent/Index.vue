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
    title: '',
    content: '',
    link_url: '',
    images: [],
    is_published: true,
    end_date: null,
});

// Formulario para editar contenido
const editForm = useForm({
    id: null,
    title: '',
    content: '',
    link_url: '',
    is_published: true,
    end_date: null,
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
    editForm.title = content.title;
    editForm.content = content.content;
    editForm.link_url = content.link_url;
    editForm.is_published = content.is_published;
    editForm.end_date = content.end_date ? new Date(content.end_date) : null;
    isEditModalVisible.value = true;
};

// Función para enviar el formulario de edición
const updateContent = () => {
    editForm.put(route('admin.webcontents.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalVisible.value = false;
            editForm.reset();
        },
    });
};

// Función para eliminar un item con confirmación
const deleteContent = (id) => {
    confirm.require({
        message: '¿Estás seguro de que quieres eliminar este elemento?',
        header: 'Confirmación de eliminación',
        icon: 'pi pi-info-circle',
        rejectClass: 'p-button-text p-button-text',
        acceptClass: 'p-button-danger p-button-text',
        accept: () => {
             useForm({}).delete(route('admin.webcontents.destroy', id), {
                preserveScroll: true,
            });
        },
    });
};

// Devuelve el nombre legible de la seccion
const getSectionName = (key) => {
    const type = types.value.find(t => t.value === key);
    return type ? type.label : key;
};

</script>

<template>
    <Head title="Gestionar Contenido Web" />
    <Toast />
    <ConfirmDialog></ConfirmDialog>

    <!-- Modal de Edición -->
    <Dialog header="Editar Contenido" v-model:visible="isEditModalVisible" :modal="true" :style="{ width: '50vw' }">
        <form @submit.prevent="updateContent" class="p-fluid">
             <div class="field">
                <label for="edit_title" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Título (Opcional)</label>
                <InputText id="edit_title" type="text" v-model="editForm.title" />
                <small v-if="editForm.errors.title" class="p-error">{{ editForm.errors.title }}</small>
            </div>
             <div class="field mt-4">
                <label for="edit_link_url" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">URL (Opcional)</label>
                <InputText id="edit_link_url" type="url" v-model="editForm.link_url" />
                <small v-if="editForm.errors.link_url" class="p-error">{{ editForm.errors.link_url }}</small>
            </div>
            <div class="field mt-4">
                <label for="edit_content" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Descripción (Opcional)</label>
                <Textarea id="edit_content" v-model="editForm.content" rows="4" />
                <small v-if="editForm.errors.content" class="p-error">{{ editForm.errors.content }}</small>
            </div>
            <div class="field mt-4">
                <label for="edit_end_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Fecha de Finalización (Opcional)</label>
                <Calendar id="edit_end_date" v-model="editForm.end_date" dateFormat="yy-mm-dd" />
                <small class="text-gray-500 dark:text-gray-400">El contenido se ocultará automáticamente después de esta fecha.</small>
            </div>
            <div class="field-checkbox mt-4">
                <Checkbox id="edit_is_published" v-model="editForm.is_published" :binary="true" />
                <label for="edit_is_published" class="ml-2">Publicado</label>
            </div>
        </form>
         <template #footer>
            <Button label="Cancelar" icon="pi pi-times" @click="isEditModalVisible = false" class="p-button-text"/>
            <Button type="submit" @click="updateContent" label="Guardar Cambios" icon="pi pi-check" :loading="editForm.processing" />
        </template>
    </Dialog>


    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Gestionar Contenido Web</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                 <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <!-- FORMULARIO PARA AGREGAR NUEVO CONTENIDO -->
                    <div class="p-6 mb-8 bg-white dark:bg-gray-800 rounded-lg shadow-md border dark:border-gray-700">
                        <h3 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Agregar Nuevo Contenido</h3>
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="p-fluid">
                                    <label for="type" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Sección</label>
                                    <Dropdown id="type" v-model="form.type" :options="types" optionLabel="label" optionValue="value" placeholder="Selecciona una sección" class="w-full" />
                                </div>
                                <div class="p-fluid">
                                     <label for="title" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Título (Opcional)</label>
                                    <InputText id="title" type="text" v-model="form.title" class="w-full" />
                                    <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                                </div>
                                 <div class="p-fluid">
                                    <label for="link_url" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">URL (Opcional)</label>
                                    <InputText id="link_url" type="url" v-model="form.link_url" class="w-full" />
                                     <small v-if="form.errors.link_url" class="p-error">{{ form.errors.link_url }}</small>
                                </div>
                                 <div class="md:col-span-2">
                                     <label for="content" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Descripción (Opcional)</label>
                                    <Textarea id="content" v-model="form.content" rows="3" class="w-full" />
                                     <small v-if="form.errors.content" class="p-error">{{ form.errors.content }}</small>
                                </div>
                                <div class="p-fluid">
                                    <label for="end_date" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Fecha de Finalización (Opcional)</label>
                                    <Calendar id="end_date" v-model="form.end_date" dateFormat="yy-mm-dd" class="w-full" />
                                    <small class="text-gray-500 dark:text-gray-400">El contenido se ocultará automáticamente después de esta fecha.</small>
                                </div>
                                <div class="md:col-span-2">
                                    <label for="images" class="block font-medium text-sm text-gray-700 dark:text-gray-300 mb-2">Imágenes</label>
                                    <FileUpload ref="fileUploadRef" name="images[]" :multiple="true" :showUploadButton="false" :showCancelButton="false" accept="image/*" chooseLabel="Seleccionar Imágenes">
                                        <template #empty>
                                            <p>Arrastra y suelta las imágenes aquí.</p>
                                        </template>
                                    </FileUpload>
                                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full mt-2">
                                        {{ form.progress.percentage }}%
                                    </progress>
                                    <small v-if="form.errors.images" class="p-error">{{ form.errors.images }}</small>
                                </div>

                               <div class="flex items-center">
                                    <Checkbox id="is_published" v-model="form.is_published" :binary="true" />
                                    <label for="is_published" class="ml-2 block text-sm text-gray-900 dark:text-gray-100">Publicado</label>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end">
                                <Button type="submit" label="Guardar Contenido" icon="pi pi-check" :loading="form.processing" />
                            </div>
                        </form>
                    </div>


                    <!-- VISUALIZACIÓN DEL CONTENIDO EXISTENTE -->
                    <TabView>
                        <TabPanel v-for="(group, type) in webcontents" :key="type" :header="getSectionName(type)">
                             <div v-if="group.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                <Card v-for="content in group" :key="content.id" class="overflow-hidden transition-all duration-300 ease-in-out hover:shadow-2xl bg-gray-100 dark:bg-gray-800">
                                     <template #header>
                                        <Carousel :value="content.media" :numVisible="1" :numScroll="1" :show-indicators="content.media.length > 1">
                                            <template #item="slotProps">
                                                <div class="p-2 bg-gray-50 dark:bg-gray-800">
                                                    <img :src="slotProps.data.original_url" :alt="content.title" class="w-full h-48 object-contain rounded-md">
                                                </div>
                                            </template>
                                        </Carousel>
                                    </template>
                                    <template #title>
                                       <div class="truncate"> {{ content.title || 'Sin título' }} </div>
                                    </template>
                                    <template #subtitle>
                                        <a :href="content.link_url" target="_blank" class="text-blue-500 hover:underline truncate block">{{ content.link_url || '' }}</a>
                                    </template>
                                    <template #content>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 h-16 overflow-hidden">
                                            {{ content.content || 'Sin descripción' }}
                                        </p>
                                    </template>
                                    <template #footer>
                                         <div class="flex justify-between items-center">
                                             <Tag :value="content.is_published ? 'Publicado' : 'Borrador'" :severity="content.is_published ? 'success' : 'warning'" />
                                             <div class="flex items-center gap-1">
                                                <Button @click="editContent(content)" icon="pi pi-pencil" class="p-button-rounded p-button-text" />
                                                <Button @click="deleteContent(content.id)" icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-text" />
                                             </div>
                                         </div>
                                    </template>
                                </Card>
                            </div>
                             <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <i class="pi pi-inbox text-4xl mb-2"></i>
                                <p>No hay contenido en esta sección.</p>
                            </div>
                        </TabPanel>
                    </TabView>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

