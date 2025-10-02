<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

// Usamos el helper de formularios de Inertia para manejar el estado y envío
const form = useForm({
  client_name: '',
  client_email: '',
  client_phone: '',
  title: '',
  description: '',
});

const formSubmitted = ref(false);

// Función para manejar el envío del formulario
const handleSubmit = () => {
  form.post('/quote-request', { // La ruta que definiremos en Laravel
    preserveScroll: true,
    onSuccess: () => {
      form.reset(); // Limpia el formulario
      formSubmitted.value = true; // Muestra el mensaje de éxito
      // Oculta el mensaje después de 5 segundos
      setTimeout(() => formSubmitted.value = false, 5000);
    },
    // Opcional: Manejo de errores
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
        
        <!-- Mensaje de éxito -->
        <transition name="fade-in-up">
          <div v-if="formSubmitted" class="success-message">
            <i class="pi pi-check-circle" style="font-size: 2rem"></i>
            <p class="font-semibold">{{ t('Quote request sent successfully!') }}</p>
          </div>
        </transition>

        <form @submit.prevent="handleSubmit">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Columna Izquierda -->
            <div class="flex flex-col gap-8">
              <span class="p-float-label">
                <InputText id="name" v-model="form.client_name" class="w-full" />
                <label for="name">{{ t('Name') }}</label>
              </span>
              <span class="p-float-label">
                <InputText id="email" v-model="form.client_email" type="email" class="w-full" />
                <label for="email">{{ t('Email') }}</label>
              </span>
              <span class="p-float-label">
                <InputText id="phone" v-model="form.client_phone" class="w-full" />
                <label for="phone">{{ t('Phone') }}</label>
              </span>
            </div>
            <!-- Columna Derecha -->
            <div class="flex flex-col gap-8">
              <span class="p-float-label">
                <InputText id="title" v-model="form.title" class="w-full" />
                <label for="title">{{ t('Service of Interest') }}</label>
              </span>
              <span class="p-float-label h-full">
                <Textarea id="description" v-model="form.description" class="w-full h-full" />
                <label for="description">{{ t('Tell us about your project') }}</label>
              </span>
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

/* Mensaje de éxito */
.success-message {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(23, 25, 48, 0.95); display: flex; flex-direction: column;
  align-items: center; justify-content: center; z-index: 10;
  color: #17EDF4; text-align: center; gap: 1rem;
}

/* Transición para el mensaje de éxito */
.fade-in-up-enter-active, .fade-in-up-leave-active {
  transition: all 0.5s ease;
}
.fade-in-up-enter-from, .fade-in-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}
</style>
