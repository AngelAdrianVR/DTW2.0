<template>
  <section id="own-projects" class="py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <h2 class="section-title text-3xl sm:text-4xl font-bold text-center mb-20">
        <!-- Asumo que tienes una función t() para traducciones, la he mantenido -->
        <span>{{ t('Our Own Projects') }}</span>
      </h2>

      <!-- Iteramos sobre los proyectos procesados -->
      <div v-for="(project, index) in processedProjects" :key="project.id" 
           class="project-showcase grid grid-cols-1 lg:grid-cols-10 gap-8 items-stretch p-4 sm:p-6 rounded-2xl mb-16">
        
        <!-- Columna de la Galería (ocupa más espacio) -->
        <div class="gallery-container lg:col-span-6 flex flex-col gap-4" :class="{ 'lg:order-last': index % 2 !== 0 }">
          <!-- MODIFICACIÓN: Altura reducida de h-80 sm:h-96 a h-72 sm:h-80 -->
          <div class="main-image-wrapper rounded-lg overflow-hidden h-72 sm:h-80 group">
            <!-- MODIFICACIÓN: Implementación del carrusel con transición -->
            <template v-if="project.galleryImages.length > 0">
              <transition name="fade" mode="out-in">
                <img :key="currentImageKey(project)"
                     :src="currentImageUrl(project)"
                     :alt="currentImageAlt(project)"
                     class="main-image object-cover w-full h-full">
              </transition>
            </template>
            <!-- Mantenemos el placeholder si no hay imágenes -->
            <template v-else>
              <div class="no-image-placeholder flex items-center justify-center h-full bg-slate-900/50 rounded-lg">
                <span class="text-gray-500 text-2xl font-semibold">{{ project.title }}</span>
              </div>
            </template>
          </div>
          <div class="thumbnail-grid grid grid-cols-2 sm:grid-cols-4 gap-4" v-if="project.galleryImages.length > 1">
            <div v-for="image in project.galleryImages.slice(1, 5)" :key="image.id" class="thumbnail-wrapper rounded-md overflow-hidden h-24 group">
               <img :src="image.original_url" :alt="image.name" class="thumbnail-image object-cover w-full h-full">
            </div>
          </div>
        </div>

        <!-- Columna de la Tarjeta de Detalles -->
        <div class="details-card lg:col-span-4 flex flex-col p-8 rounded-xl text-center">
          <div v-if="project.logoImage" class="logo-wrapper mb-6">
            <img :src="project.logoImage.original_url" :alt="project.title + ' Logo'" class="project-logo mx-auto h-20 w-auto object-contain">
          </div>
          
          <h3 class="text-3xl font-bold text-white mb-4">{{ project.title }}</h3>
          <p class="text-gray-300 flex-grow mb-8">{{ project.content }}</p>
          
          <div class="mt-auto">
             <a :href="project.link_url" target="_blank" rel="noopener noreferrer" class="visit-site-button">
              {{ t('Visit Site') }}
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 icon-arrow" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'InteractiveOwnProjects',
  props: {
    projects: {
      type: Array,
      required: true,
    },
  },
  // MODIFICACIÓN: Añadido el estado local del componente
  data() {
    return {
      // Objeto para almacenar el índice de la imagen actual para cada proyecto
      currentImageIndexes: {},
      // Objeto para almacenar los IDs de los intervalos y poder limpiarlos después
      intervals: {},
    };
  },
  computed: {
    /**
     * Procesa los proyectos para separar la última imagen como logotipo
     * y el resto como imágenes de galería.
     */
    processedProjects() {
      return this.projects.map(project => {
        const media = project.media || [];
        // La última imagen es el logo
        const logoImage = media.length > 0 ? media[media.length - 1] : null;
        // Todas las imágenes excepto la última son para la galería
        const galleryImages = media.length > 1 ? media.slice(0, media.length - 1) : [];
        
        return {
          ...project,
          logoImage,
          galleryImages,
        };
      });
    }
  },
  // MODIFICACIÓN: Añadidos métodos para controlar el carrusel
  methods: {
    // Devuelve la URL de la imagen activa para un proyecto
    currentImageUrl(project) {
      if (!project.galleryImages.length) return '';
      const index = this.currentImageIndexes[project.id] || 0;
      return project.galleryImages[index].original_url;
    },
    // Devuelve el texto alternativo de la imagen activa
    currentImageAlt(project) {
      if (!project.galleryImages.length) return project.title;
      const index = this.currentImageIndexes[project.id] || 0;
      return project.galleryImages[index].name;
    },
    // Genera una clave única para forzar la re-renderización en la transición
    currentImageKey(project) {
        if (!project.galleryImages.length) return project.id;
        const index = this.currentImageIndexes[project.id] || 0;
        return `${project.id}-${index}`;
    },
    // Inicia el carrusel para todos los proyectos que califiquen
    startCarousel() {
      this.processedProjects.forEach(project => {
        // Solo iniciar si hay más de una imagen en la galería
        if (project.galleryImages.length > 1) {
          // CORRECCIÓN: Usar asignación directa para Vue 3
          this.currentImageIndexes[project.id] = 0;

          this.intervals[project.id] = setInterval(() => {
            const newIndex = (this.currentImageIndexes[project.id] + 1) % project.galleryImages.length;
            // CORRECCIÓN: Usar asignación directa para Vue 3
            this.currentImageIndexes[project.id] = newIndex;
          }, 3000); // Cambiar de imagen cada 3 segundos
        }
      });
    }
  },
  // MODIFICACIÓN: Añadidos hooks del ciclo de vida
  mounted() {
    this.startCarousel();
  },
  beforeDestroy() {
    // Limpiamos todos los intervalos cuando el componente se destruye para evitar fugas de memoria
    Object.values(this.intervals).forEach(clearInterval);
  },
};
</script>

<style scoped>
/* Título de la sección */
.section-title {
  position: relative;
  display: inline-block;
  left: 50%;
  transform: translateX(-50%);
  color: #fff;
  padding-bottom: 0.5rem;
}
.section-title span {
  position: relative;
  z-index: 1;
}
.section-title::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 60%;
  height: 3px;
  background: linear-gradient(90deg, #6215C0, #17EDF4);
  border-radius: 2px;
}

/* Contenedor principal de cada proyecto */
.project-showcase {
  background: #111827;
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: all 0.3s ease-in-out;
}
.project-showcase:hover {
  border-color: rgba(23, 237, 244, 0.5);
}

/* Tarjeta de detalles del proyecto */
.details-card {
  background: rgba(29, 36, 56, 0.5);
  border: 1px solid rgba(98, 21, 192, 0.4);
  backdrop-filter: blur(12px);
  box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
  transition: all 0.3s ease-in-out;
}
.project-showcase:hover .details-card {
  border-color: rgba(98, 21, 192, 0.8);
  box-shadow: 0 0 25px rgba(98, 21, 192, 0.4);
}

/* Estilos para la galería de imágenes */
.main-image, .thumbnail-image {
  transition: transform 0.4s cubic-bezier(0.25, 0.8, 0.25, 1), filter 0.4s ease;
}
.group:hover .main-image, .group:hover .thumbnail-image {
  transform: scale(1.05);
  filter: brightness(1.1);
}

/* Botón de visita */
.visit-site-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  background-image: linear-gradient(90deg, #6215C0, #17EDF4);
  color: #ffffff;
  font-weight: bold;
  text-decoration: none;
  transition: all 0.3s ease;
  background-size: 200% 100%;
  background-position: right bottom;
}

.visit-site-button:hover {
  background-position: left bottom;
  box-shadow: 0 0 20px rgba(23, 237, 244, 0.5);
  transform: translateY(-2px);
}
.visit-site-button .icon-arrow {
  transition: transform 0.3s ease;
}
.visit-site-button:hover .icon-arrow {
  transform: translateX(5px);
}

/* MODIFICACIÓN: Estilos para la transición de la imagen */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s ease;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>

