<script setup>
import { useForm } from '@inertiajs/vue3';

// Usamos el helper de formularios de Inertia para manejar el estado y envío
const form = useForm({
  client_name: '',
  client_email: '',
  client_phone: '',
  title: '',
  description: '',
});

// Función para manejar el envío del formulario
const handleSubmit = () => {
  form.post('/quote-request', { // La ruta que definiremos en Laravel
    preserveScroll: true,
    onSuccess: () => {
      form.reset(); // Limpia el formulario en caso de éxito
    },
    // Opcional: Manejo de errores en el lado del cliente si es necesario
    onError: (errors) => {
      console.error('Error submitting form:', errors);
    },
  });
};
</script>

<template>
  <!-- SECCIÓN DE CONTACTO / COTIZACIÓN -->
  <section id="contacto" class="py-20 px-4">
    <div class="container mx-auto max-w-3xl">
      <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>{{ t("Request a Quote") }}</span></h2>
      <div class="contact-form p-8 md:p-12 relative overflow-hidden">
        
        <form @submit.prevent="handleSubmit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Columna Izquierda -->
            <div class="flex flex-col gap-6">
              <div class="field">
                <span class="p-float-label">
                  <InputText id="name" v-model="form.client_name" class="w-full" :class="{'p-invalid': form.errors.client_name}" />
                  <label for="name">{{ t('Name') }}</label>
                </span>
                <!-- <small v-if="form.errors.client_name" class="p-error">{{ form.errors.client_name }}</small> -->
              </div>
              <div class="field">
                <span class="p-float-label">
                  <InputText id="email" v-model="form.client_email" type="email" class="w-full" :class="{'p-invalid': form.errors.client_email}" />
                  <label for="email">{{ t('Email') }}</label>
                </span>
                <!-- <small v-if="form.errors.client_email" class="p-error">{{ form.errors.client_email }}</small> -->
              </div>
              <div class="field">
                <span class="p-float-label">
                  <InputText id="phone" v-model="form.client_phone" class="w-full" :class="{'p-invalid': form.errors.client_phone}" />
                  <label for="phone">{{ t('Phone') }}</label>
                </span>
                <!-- <small v-if="form.errors.client_phone" class="p-error">{{ form.errors.client_phone }}</small> -->
              </div>
            </div>
            <!-- Columna Derecha -->
            <div class="flex flex-col gap-6">
              <div class="field">
                <span class="p-float-label">
                  <InputText id="title" v-model="form.title" class="w-full" :class="{'p-invalid': form.errors.title}" />
                  <label for="title">{{ t('Service of Interest') }}</label>
                </span>
                 <!-- <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small> -->
              </div>
              <div class="field h-full">
                <span class="p-float-label h-full">
                  <Textarea id="description" v-model="form.description" class="w-full h-full" :class="{'p-invalid': form.errors.description}" />
                  <label for="description">{{ t('Tell us about your project') }}</label>
                </span>
                 <!-- <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small> -->
              </div>
            </div>
          </div>

          <!-- Botón de envío -->
          <div class="mt-12 text-center">
            <button type="submit" class="submit-button" :disabled="form.processing">
              <span v-if="!form.processing" class="flex items-center justify-center gap-2">
                <i class="pi pi-send"></i> {{ t('Send Request') }}
              </span>
              <span v-else class="flex items-center justify-center gap-3">
                <div class="spinner"></div>
                {{ t('Sending...') }}
              </span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Estilos existentes */
.section-title {
  position: relative; display: inline-block; left: 50%; transform: translateX(-50%);
  color: #fff; padding-bottom: 0.5rem;
}
.section-title span { position: relative; z-index: 1; }
.section-title::after {
  content: ''; position: absolute; bottom: 0; left: 50%; transform: translateX(-50%);
  width: 60%; height: 3px; background: linear-gradient(90deg, #6215C0, #17EDF4);
  border-radius: 2px;
}
.contact-form {
  background-color: rgba(23, 25, 48, 0.5); border: 1px solid rgba(23, 237, 244, 0.2);
  backdrop-filter: blur(10px); border-radius: 1rem; transition: all 0.3s ease;
}
:deep(.p-inputtext), :deep(.p-textarea) {
  background: rgba(0,0,0,0.3) !important; border: 1px solid rgba(255,255,255,0.2) !important;
  color: #fff !important; transition: all 0.3s ease;
}
:deep(.p-inputtext:enabled:focus), :deep(.p-textarea:enabled:focus) {
  box-shadow: 0 0 15px rgba(23, 237, 244, 0.5) !important;
  border-color: rgba(23, 237, 244, 0.8) !important;
}
:deep(.p-float-label > label) { color: #9ca3af !important; }

/* Estilos para los errores de validación */
.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem; /* Espacio entre el input y el mensaje de error */
}
:deep(.p-error) {
  color: #fca5a5; /* Un rojo claro para el texto del error */
  font-size: 0.875rem;
}
:deep(.p-inputtext.p-invalid), :deep(.p-textarea.p-invalid) {
    border-color: #f87171 !important; /* Borde rojo para el input inválido */
}

/* Nuevo Botón Minimalista */
.submit-button {
  padding: 0.75rem 2.5rem; font-size: 1rem; font-weight: 500;
  color: #e5e7eb; background-color: transparent;
  border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 999px;
  cursor: pointer; transition: all 0.3s ease; position: relative;
  overflow: hidden;
}
.submit-button:hover:not(:disabled) {
  color: #fff; border-color: #17EDF4;
  box-shadow: 0 0 15px rgba(23, 237, 244, 0.4);
}
.submit-button:disabled {
  opacity: 0.7; cursor: not-allowed;
}

/* Animación del Spinner */
.spinner {
  width: 1.25em; height: 1.25em; border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #17EDF4; border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
