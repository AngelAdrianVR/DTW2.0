<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Obtenemos el idioma actual desde las props de la página de Inertia
const { props: pageProps } = usePage();
const locale = computed(() => pageProps.locale || 'en'); // Por defecto 'en' si no está definido

// Definimos las props que el componente espera recibir
const props = defineProps({
  projects: {
    type: Array,
    required: true,
  },
});

// Función auxiliar para obtener el título traducido con fallbacks
const getTitle = (project) => {
  if (locale.value === 'es' && project.spanish_title) {
    return project.spanish_title;
  }
  // Si el idioma es inglés o el título en español no existe, usa el inglés.
  // Si el inglés tampoco existe, usa el español como último recurso.
  return project.english_title || project.spanish_title || 'Proyecto sin título';
};

// Función auxiliar para obtener el contenido traducido con fallbacks
const getContent = (project) => {
  if (locale.value === 'es' && project.spanish_content) {
    return project.spanish_content;
  }
  return project.english_content || project.spanish_content || '';
};

// Filtrar solo los proyectos que están publicados
const publishedProjects = computed(() => {
  return props.projects.filter(project => project.is_published);
});

// Título de la sección traducido
const sectionTitle = computed(() => {
    return locale.value === 'es' ? 'Proyectos Destacados' : 'Featured Projects';
});
</script>

<template>
  <!-- SECCIÓN DE PROYECTOS -->
  <section id="proyectos" class="py-20 px-4 bg-gray-900/50">
    <div class="container mx-auto">
      <h2 class="text-4xl font-bold text-center mb-16 section-title"><span>{{ sectionTitle }}</span></h2>
      
      <!-- Grid de proyectos -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Iteramos sobre los proyectos publicados -->
        <Link 
          v-for="project in publishedProjects" 
          :key="project.id" 
          :href="`/landing-projects/${project.id}`"
          class="project-card-link"
        >
          <div class="project-card group">
            <!-- Contenedor de la imagen para controlar el zoom -->
            <div class="image-container">
                <img 
                  v-if="project.media && project.media.length > 0"
                  :src="project.media[0].original_url" 
                  :alt="getTitle(project)" 
                  class="project-image"
                  onerror="this.onerror=null;this.src='https://placehold.co/600x400/1A202C/7B8A9E?text=Image+Not+Found';"
                >
                 <img 
                  v-else
                  src="https://placehold.co/600x400/1A202C/7B8A9E?text=No+Image" 
                  :alt="getTitle(project)" 
                  class="project-image"
                >
            </div>
            <div class="project-info">
              <div>
                <h3 class="text-2xl font-bold text-white">{{ getTitle(project) }}</h3>
                <p class="text-gray-400 mt-2 line-clamp-3">{{ getContent(project) }}</p>
              </div>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Estilos para el título de la sección */
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

/* Link que envuelve la tarjeta */
.project-card-link {
  display: block;
  text-decoration: none;
  color: inherit;
  transition: transform 0.3s ease;
}

/* Contenedor principal de la tarjeta del proyecto */
.project-card {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  background-color: #1A202C;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: box-shadow 0.3s ease;
  height: 100%;
  /* Estructura flexible para separar imagen y texto */
  display: flex;
  flex-direction: column;
}

/* Efecto de borde con gradiente al pasar el ratón */
.project-card::before {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 1rem;
  padding: 1px;
  background: linear-gradient(45deg, #6215C0, #17EDF4);
  -webkit-mask: 
    linear-gradient(#fff 0 0) content-box, 
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  opacity: 0;
  transition: opacity 0.3s ease-in-out;
}

.project-card-link:hover .project-card::before {
  opacity: 1;
}

.project-card-link:hover .project-card {
  box-shadow: 0 20px 40px rgba(0,0,0,0.7);
}


/* Contenedor de la imagen para el efecto de zoom */
.image-container {
  height: 250px;
  overflow: hidden;
  position: relative;
}

/* Imagen del proyecto */
.project-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.project-card-link:hover .project-image {
  transform: scale(1.1);
}

/* Capa de información (ya no es un overlay) */
.project-info {
  padding: 1.5rem;
  flex-grow: 1; /* Ocupa el espacio restante */
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.project-info h3 {
    color: #f0f0f0;
}

/* Limita el texto del contenido para evitar desbordamiento */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}

/* === MEDIA QUERIES PARA RESPONSIVIDAD === */
@media (min-width: 768px) {
    .project-card-link:hover {
        transform: translateY(-8px);
    }
}
</style>
