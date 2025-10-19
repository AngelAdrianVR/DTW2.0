<script setup>
import { onMounted, computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';

// Obtenemos el idioma actual desde las props de la página de Inertia
const { props: pageProps } = usePage();
const locale = computed(() => pageProps.locale || 'en'); // Por defecto 'en' si no está definido

// Define las props que el componente espera recibir del controlador
const props = defineProps({
  project: {
    type: Object,
    required: true,
  },
});

// Propiedad computada para el título, que cambia según el idioma
const title = computed(() => {
  if (locale.value === 'es' && props.project.spanish_title) {
    return props.project.spanish_title;
  }
  return props.project.english_title || props.project.spanish_title || 'Untitled Project';
});

// Propiedad computada para el contenido, que cambia según el idioma
const content = computed(() => {
  if (locale.value === 'es' && props.project.spanish_content) {
    return props.project.spanish_content;
  }
  return props.project.english_content || props.project.spanish_content || '';
});

// Propiedad computada para el texto del enlace de "volver"
const backLinkText = computed(() => {
    return locale.value === 'es' ? 'Volver a Proyectos' : 'Back to Projects';
});

// Función para animar la entrada de la página
onMounted(() => {
  // Usamos un pequeño retraso para asegurar que el navegador aplique la animación
  requestAnimationFrame(() => {
    const page = document.querySelector('.project-details-page');
    if (page) {
      page.classList.add('page-enter-active');
    }
  });
});
</script>

<template>
  <LandingLayout :title="title">
    <div class="project-details-page">
      <main class="container mx-auto px-4 md:px-8 py-24 md:py-32 text-white">
        <!-- Botón para volver al inicio -->
        <div class="mb-12">
          <Link href="/" class="back-link group">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 transition-transform group-hover:-translate-x-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            {{ backLinkText }}
          </Link>
        </div>

        <!-- Layout principal de dos columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
          <!-- Columna Izquierda: Información del Proyecto -->
          <div class="project-info-container">
            <h1 class="text-5xl md:text-7xl font-bold text-glow mb-4">{{ title }}</h1>
            <h2 class="text-2xl md:text-3xl font-semibold text-cyan-300 mb-8">{{ project.subtitle }}</h2>
            <p class="text-lg text-gray-300 leading-relaxed whitespace-pre-wrap mb-10">{{ content }}</p>
            <a v-if="project.link_url" :href="project.link_url" target="_blank" rel="noopener noreferrer">
              <Button label="Visitar el Sitio" icon="pi pi-external-link" class="p-button-info p-button-raised launch-button" />
            </a>
          </div>

          <!-- Columna Derecha: Galería de Imágenes con Scroll -->
          <div class="gallery-container">
            <div class="scroll-driven-gallery space-y-8 md:space-y-12">
              <div v-for="(media, index) in project.media" :key="index" class="image-wrapper">
                <img
                  :src="media.original_url"
                  :alt="`Imagen ${index + 1} de ${title}`"
                  class="object-contain"
                  onerror="this.onerror=null;this.src='https://placehold.co/1200x800/111827/7B8A9E?text=Image+Not+Found';"
                />
              </div>
               <div v-if="!project.media || project.media.length === 0" class="image-wrapper">
                <img
                  src="https://placehold.co/1200x800/111827/7B8A9E?text=No+Images+Available"
                  alt="No hay imágenes"
                  class="gallery-image"
                />
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </LandingLayout>
</template>

<style scoped>
/* --- TRANSICIÓN DE PÁGINA MEJORADA --- */
@keyframes page-fade-in-up {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.project-details-page {
  opacity: 0;
}
.project-details-page.page-enter-active {
  /* Aplicamos la animación al entrar */
  animation: page-fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Estilos de la sección de detalles */
.project-info-container {
  position: sticky;
  top: 120px; /* Aumentamos el espacio superior */
  height: fit-content;
}

.text-glow {
  text-shadow: 0 0 8px rgba(23, 237, 244, 0.5), 0 0 20px rgba(98, 21, 192, 0.4);
}

.back-link {
  display: inline-flex;
  align-items: center;
  font-size: 1rem;
  color: #9ca3af;
  transition: color 0.3s ease;
}

.back-link:hover {
  color: #17EDF4;
}

.launch-button {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.launch-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 20px rgba(98, 21, 192, 0.3);
}

/* --- ANIMACIONES SCROLL-DRIVEN MEJORADAS --- */
.gallery-container {
  /* Activamos el contexto de perspectiva para las transformaciones 3D */
  perspective: 1000px;
}

.image-wrapper {
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  border: 1px solid rgba(98, 21, 192, 0.2);
  /* Aseguramos que la transformación 3D se aplique correctamente */
  transform-style: preserve-3d;
}

.gallery-image {
  width: 100%;
  height: auto;
  object-fit: cover;
  display: block;
}

@supports (animation-timeline: scroll()) {
  .image-wrapper {
    /* Animación principal para revelar la tarjeta con efecto 3D */
    animation: reveal-3d linear forwards; /* 'forwards' mantiene el estado final */
    animation-timeline: view();
    animation-range: entry 10% cover 45%;
  }

  .gallery-image {
    /* Animación de parallax para la imagen interior */
    animation: parallax-scroll linear forwards;
    animation-timeline: view();
    animation-range: entry 0% cover 100%; /* La animación dura mientras el item esté visible */
  }

  @keyframes reveal-3d {
    from {
      opacity: 0;
      transform: rotateX(-20deg) scale(0.9);
    }
    to {
      opacity: 1;
      transform: rotateX(0deg) scale(1);
    }
  }

  @keyframes parallax-scroll {
    from {
      transform: scale(1.15) translateY(-15%);
    }
    to {
      transform: scale(1.15) translateY(5%);
    }
  }
}

/* Fallback para navegadores sin soporte */
@supports not (animation-timeline: scroll()) {
  .image-wrapper {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.7s ease-out, transform 0.7s ease-out;
  }
  .page-enter-active .image-wrapper {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Adaptación a pantallas pequeñas */
@media (max-width: 1023px) {
  .project-info-container {
    position: static;
    top: auto;
    text-align: center;
  }
  .launch-button {
      margin: 0 auto;
  }
  .grid {
    gap: 4rem;
  }
  h1 { font-size: 3rem; }
  h2 { font-size: 1.5rem; }
}
</style>
