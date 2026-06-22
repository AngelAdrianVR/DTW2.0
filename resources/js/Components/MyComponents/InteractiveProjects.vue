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

// Paleta de colores que cicla por índice para dar variedad visual
const colorPalette = ['#7c3aed', '#3b82f6', '#eab308'];
const colorPaletteSecondary = ['#a855f7', '#60a5fa', '#f59e0b'];

const cardColor = (index) => colorPalette[index % colorPalette.length];
const cardColorSecondary = (index) => colorPaletteSecondary[index % colorPaletteSecondary.length];
const cardBorderClass = (index) => {
  const classes = ['hover:border-violet-500/30', 'hover:border-blue-500/30', 'hover:border-amber-500/30'];
  return classes[index % classes.length];
};
</script>

<template>
  <!-- SECCIÓN DE PROYECTOS -->
  <section id="proyectos" class="relative w-full bg-[#050510] px-4 sm:px-6 md:px-10 py-12 sm:py-20 overflow-hidden">
    <div class="max-w-[1400px] mx-auto">
      <h2
        class="text-white text-3xl sm:text-4xl md:text-5xl font-light text-center mb-12 sm:mb-24"
        style="letter-spacing: -0.04em;"
      >
        {{ sectionTitle }}
      </h2>
      
      <!-- Grid de proyectos -->
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        <Link
          v-for="(project, index) in publishedProjects"
          :key="project.id"
          :href="`/landing-projects/${project.id}`"
          :style="{ animationDelay: `${index * 0.08}s` }"
          class="project-card-link group"
          :class="cardBorderClass(index)"
        >
          <div class="project-card">
            <!-- Blob decorativo -->
            <div
              class="absolute top-1/2 -translate-y-1/2 -left-[300px] h-[350px] w-[350px] rounded-full blur-3xl opacity-0 group-hover:opacity-25 transition-all duration-700"
              :style="{ backgroundColor: cardColor(index) }"
            ></div>
            <!-- Blob pequeño inferior -->
            <div
              class="absolute -bottom-12 right-1/4 h-24 w-24 rounded-full blur-2xl opacity-0 group-hover:opacity-15 transition-all duration-500"
              :style="{ backgroundColor: cardColorSecondary(index) }"
            ></div>

            <!-- Imagen -->
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
              <!-- Overlay gradiente sobre la imagen -->
              <div class="absolute inset-0 bg-gradient-to-t from-[#0a0a14] via-transparent to-transparent opacity-60 group-hover:opacity-40 transition-opacity duration-500"></div>
            </div>

            <!-- Info -->
            <div class="project-info">
              <h3 class="text-white text-xl sm:text-2xl font-light leading-tight">{{ getTitle(project) }}</h3>
              <p class="mt-3 text-[13px] sm:text-[14px] leading-relaxed text-white/55 font-light line-clamp-3">{{ getContent(project) }}</p>
            </div>
          </div>
        </Link>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* =========== TARJETA DE PROYECTO (GPU-optimized) =========== */
.project-card-link {
  display: block;
  text-decoration: none;
  color: inherit;
  will-change: transform;
  animation: cardStaggerIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.project-card {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  background-color: #0a0a14;
  border: 1px solid rgba(255, 255, 255, 0.05);
  height: 100%;
  display: flex;
  flex-direction: column;
  /* Solo transition en propiedades GPU-friendly */
  transition: transform 0.25s cubic-bezier(0.25, 0.46, 0.45, 0.94),
              border-color 0.5s ease,
              box-shadow 0.5s ease;
  transform: translateY(0);
}

.project-card-link:hover .project-card {
  transform: translateY(-6px);
  box-shadow: 0 12px 50px rgba(139, 92, 246, 0.12), 0 0 30px rgba(59, 130, 246, 0.06);
}

/* =========== BORDE GLOW SUTIL POR ÍNDICE (inyectado como clase) =========== */
.hover\:border-violet-500\/30:hover .project-card { border-color: rgba(139, 92, 246, 0.3); }
.hover\:border-blue-500\/30:hover .project-card  { border-color: rgba(59, 130, 246, 0.3); }
.hover\:border-amber-500\/30:hover .project-card { border-color: rgba(234, 179, 8, 0.3); }

/* =========== IMAGEN =========== */
.image-container {
  height: 220px;
  overflow: hidden;
  position: relative;
}
@media (min-width: 640px) {
  .image-container { height: 250px; }
}

.project-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  /* Solo transform para que la GPU lo maneje */
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  transform: scale(1);
}

.project-card-link:hover .project-image {
  transform: scale(1.06);
}

/* =========== INFORMACIÓN =========== */
.project-info {
  position: relative;
  z-index: 2;
  padding: 1.25rem 1.5rem 1.5rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
}

/* =========== LINE CLAMP UTILITY =========== */
.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* =========== ENTRADA ESCALONADA (idéntica a services) =========== */
@keyframes cardStaggerIn {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
