<script setup>
/**
 * Formulario compartido por el alta y la edición de eventos.
 *
 * Envía todo como FormData (hay portada) y usa el `_method` de Laravel para el
 * PUT de la edición. Las fechas viajan como texto local (`YYYY-MM-DD` y
 * `YYYY-MM-DDTHH:mm`), que es exactamente lo que el backend guarda, sin
 * conversiones de zona horaria de por medio.
 */
import { computed, onBeforeUnmount, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';
import Button from 'primevue/button';

const props = defineProps({
    event: { type: Object, default: null },
});

const isEdit = computed(() => Boolean(props.event?.id));

const toDate = (value) => (value ? String(value).slice(0, 10) : '');
const toDateTime = (value) => (value ? String(value).slice(0, 16) : '');

const form = useForm({
    spanish_title: props.event?.spanish_title || '',
    english_title: props.event?.english_title || '',
    spanish_description: props.event?.spanish_description || '',
    english_description: props.event?.english_description || '',
    spanish_challenge: props.event?.spanish_challenge || '',
    english_challenge: props.event?.english_challenge || '',
    spanish_prizes_summary: props.event?.spanish_prizes_summary || '',
    english_prizes_summary: props.event?.english_prizes_summary || '',
    event_date: toDate(props.event?.event_date),
    registration_starts_at: toDateTime(props.event?.registration_starts_at),
    registration_ends_at: toDateTime(props.event?.registration_ends_at),
    custom_field_label_es: props.event?.custom_field_label_es || '',
    custom_field_label_en: props.event?.custom_field_label_en || '',
    custom_field_placeholder_es: props.event?.custom_field_placeholder_es || '',
    custom_field_placeholder_en: props.event?.custom_field_placeholder_en || '',
    custom_field_required: Boolean(props.event?.custom_field_required),
    is_published: props.event ? Boolean(props.event.is_published) : true,
    sort_order: props.event?.sort_order ?? 0,
    slug: props.event?.slug || '',
    image: null,
    remove_image: false,
});

const imagePreview = ref(null);
const currentImage = ref(props.event?.image_url || null);

const fieldClass = 'w-full';

function onFileChange(event) {
    const file = event.target.files?.[0] || null;

    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }

    form.image = file;
    imagePreview.value = file ? URL.createObjectURL(file) : null;
}

onBeforeUnmount(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});

function submit() {
    const options = { forceFormData: true, preserveScroll: true };

    if (isEdit.value) {
        form.put(route('admin.events.update', props.event.id), options);
        return;
    }

    form.post(route('admin.events.store'), options);
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Español -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">
                    Contenido en español
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Título *</label>
                        <InputText
                            v-model="form.spanish_title"
                            :class="fieldClass"
                            placeholder="Ej. Reto de automatización 2026"
                        />
                        <p v-if="form.errors.spanish_title" class="mt-1 text-xs text-red-600">
                            {{ form.errors.spanish_title }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Descripción corta
                        </label>
                        <Textarea v-model="form.spanish_description" :class="fieldClass" rows="3" auto-resize />
                        <p v-if="form.errors.spanish_description" class="mt-1 text-xs text-red-600">
                            {{ form.errors.spanish_description }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Reto</label>
                        <Textarea v-model="form.spanish_challenge" :class="fieldClass" rows="5" auto-resize />
                        <p v-if="form.errors.spanish_challenge" class="mt-1 text-xs text-red-600">
                            {{ form.errors.spanish_challenge }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Resumen de premios
                        </label>
                        <Textarea v-model="form.spanish_prizes_summary" :class="fieldClass" rows="3" auto-resize />
                        <p v-if="form.errors.spanish_prizes_summary" class="mt-1 text-xs text-red-600">
                            {{ form.errors.spanish_prizes_summary }}
                        </p>
                    </div>
                </div>
            </section>

            <!-- English -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">
                    Content in English
                </h2>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Title</label>
                        <InputText
                            v-model="form.english_title"
                            :class="fieldClass"
                            placeholder="E.g. Automation challenge 2026"
                        />
                        <p v-if="form.errors.english_title" class="mt-1 text-xs text-red-600">
                            {{ form.errors.english_title }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Short description
                        </label>
                        <Textarea v-model="form.english_description" :class="fieldClass" rows="3" auto-resize />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Challenge</label>
                        <Textarea v-model="form.english_challenge" :class="fieldClass" rows="5" auto-resize />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Prizes summary
                        </label>
                        <Textarea v-model="form.english_prizes_summary" :class="fieldClass" rows="3" auto-resize />
                    </div>
                </div>
            </section>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Fechas -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">
                    Fechas y registro
                </h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Fecha del evento
                        </label>
                        <input
                            v-model="form.event_date"
                            type="date"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        />
                        <p v-if="form.errors.event_date" class="mt-1 text-xs text-red-600">
                            {{ form.errors.event_date }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">Orden</label>
                        <InputText v-model="form.sort_order" type="number" min="0" :class="fieldClass" />
                        <p v-if="form.errors.sort_order" class="mt-1 text-xs text-red-600">
                            {{ form.errors.sort_order }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Abre el registro
                        </label>
                        <input
                            v-model="form.registration_starts_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        />
                        <p v-if="form.errors.registration_starts_at" class="mt-1 text-xs text-red-600">
                            {{ form.errors.registration_starts_at }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Cierra el registro
                        </label>
                        <input
                            v-model="form.registration_ends_at"
                            type="datetime-local"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"
                        />
                        <p v-if="form.errors.registration_ends_at" class="mt-1 text-xs text-red-600">
                            {{ form.errors.registration_ends_at }}
                        </p>
                    </div>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-gray-500 dark:text-zinc-400">
                    Si dejas el cierre vacío, el registro sigue abierto hasta que lo cierres o despublices el evento.
                </p>
            </section>

            <!-- Campo personalizado -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">
                    Campo del formulario
                </h2>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Etiqueta (ES)
                        </label>
                        <InputText
                            v-model="form.custom_field_label_es"
                            :class="fieldClass"
                            placeholder="¿Qué problema resuelve tu negocio?"
                        />
                        <p v-if="form.errors.custom_field_label_es" class="mt-1 text-xs text-red-600">
                            {{ form.errors.custom_field_label_es }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Label (EN)
                        </label>
                        <InputText
                            v-model="form.custom_field_label_en"
                            :class="fieldClass"
                            placeholder="What problem does your business solve?"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Ayuda (ES)
                        </label>
                        <InputText v-model="form.custom_field_placeholder_es" :class="fieldClass" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            Placeholder (EN)
                        </label>
                        <InputText v-model="form.custom_field_placeholder_en" :class="fieldClass" />
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2">
                    <Checkbox v-model="form.custom_field_required" input-id="custom_required" :binary="true" />
                    <label for="custom_required" class="text-sm text-gray-700 dark:text-zinc-200">
                        Respuesta obligatoria
                    </label>
                </div>

                <p class="mt-3 text-xs leading-relaxed text-gray-500 dark:text-zinc-400">
                    Cada evento puede pedir un dato distinto: la problemática, el giro, el número de sucursales…
                </p>
            </section>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <!-- Portada -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">Portada</h2>

                <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div
                        class="h-28 w-44 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-zinc-700 dark:bg-zinc-800"
                    >
                        <img
                            v-if="imagePreview || currentImage"
                            :src="imagePreview || currentImage"
                            alt="Portada del evento"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full items-center justify-center text-xs text-gray-400">
                            Sin imagen
                        </div>
                    </div>

                    <div class="flex-1 space-y-3">
                        <input
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-full file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 dark:text-zinc-300 dark:file:bg-indigo-500/10 dark:file:text-indigo-300"
                            @change="onFileChange"
                        />
                        <p v-if="form.errors.image" class="text-xs text-red-600">{{ form.errors.image }}</p>

                        <div v-if="currentImage" class="flex items-center gap-2">
                            <Checkbox v-model="form.remove_image" input-id="remove_image" :binary="true" />
                            <label for="remove_image" class="text-sm text-gray-700 dark:text-zinc-200">
                                Quitar la portada actual
                            </label>
                        </div>

                        <p class="text-xs leading-relaxed text-gray-500 dark:text-zinc-400">
                            JPG o PNG de hasta 4 MB. Se usa en la tarjeta y en la ficha del evento.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Publicación -->
            <section
                class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900"
            >
                <h2 class="text-xs font-bold uppercase tracking-[0.18em] text-gray-500 dark:text-zinc-400">
                    Publicación
                </h2>

                <div class="mt-4 space-y-4">
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="form.is_published" input-id="is_published" :binary="true" />
                        <label for="is_published" class="text-sm text-gray-700 dark:text-zinc-200">
                            Visible en el sitio
                        </label>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-zinc-200">
                            URL (slug)
                        </label>
                        <InputText v-model="form.slug" :class="fieldClass" placeholder="reto-automatizacion-2026" />
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-red-600">{{ form.errors.slug }}</p>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-zinc-400">
                            Déjalo vacío para generarlo desde el título en español.
                        </p>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 pb-2">
            <Link
                :href="route('admin.events.index')"
                class="inline-flex items-center gap-2 rounded-full border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
            >
                Cancelar
            </Link>

            <Button
                type="submit"
                :loading="form.processing"
                :label="isEdit ? 'Guardar cambios' : 'Crear evento'"
                icon="pi pi-save"
            />
        </div>
    </form>
</template>
