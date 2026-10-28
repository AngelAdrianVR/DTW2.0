<template>
  <section id="own-projects" class="relative w-full bg-[#050510] px-4 sm:px-6 md:px-10 py-12 sm:py-20 overflow-hidden">
    <!-- Blob ambiental -->
    <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] rounded-full blur-[120px] opacity-[0.06] pointer-events-none" style="background-color: #7c3aed;"></div>
    <div class="absolute top-1/3 -right-32 w-[400px] h-[400px] rounded-full blur-[100px] opacity-[0.05] pointer-events-none" style="background-color: #3b82f6;"></div>

    <div class="max-w-[1400px] mx-auto">
      <h2
        class="text-white text-3xl sm:text-4xl md:text-5xl font-light text-center mb-12 sm:mb-24"
        style="letter-spacing: -0.04em;"
      >
        {{ t('Our Own Projects') }}
      </h2>

      <div
        v-for="(project, index) in processedProjects"
        :key="project.id"
        class="project-showcase group"
        :style="{ animationDelay: `${index * 0.12}s` }"
      >
        <!-- Blob decorativo del showcase -->
        <div class="absolute top-1/2 -translate-y-1/2 -left-[350px] h-[380px] w-[380px] rounded-full blur-3xl opacity-0 group-hover:opacity-[0.12] transition-opacity duration-700 pointer-events-none" style="background-color: #7c3aed;"></div>

        <div class="grid grid-cols-1 lg:grid-cols-10 gap-8 items-stretch relative z-10">
          <!-- Columna de la Galería -->
          <div class="gallery-col lg:col-span-6 flex flex-col" :class="{ 'lg:order-last': index % 2 !== 0 }">
            <div class="main-image-wrapper group/img">
              <template v-if="project.galleryImages.length > 0">
                <transition name="crossfade" mode="out-in">
                  <img :key="currentImageKey(project)"
                       :src="currentImageUrl(project)"
                       :alt="currentImageAlt(project)"
                       class="main-image">
                </transition>
              </template>
              <template v-else>
                <div class="no-image-placeholder">
                  <span>{{ project.title }}</span>
                </div>
              </template>
            </div>
          </div>

          <!-- Columna de la Tarjeta de Detalles -->
          <div class="details-col lg:col-span-4 flex flex-col">
            <div v-if="project.logoImage" class="logo-wrapper">
              <img :src="project.logoImage.original_url" :alt="project.title + ' Logo'" class="project-logo">
            </div>
            
            <h3 class="text-white text-2xl sm:text-3xl font-light leading-tight mb-4" style="letter-spacing: -0.02em;">{{ project.title }}</h3>
            <p class="text-white/55 font-light leading-relaxed flex-grow mb-8 text-[14px] sm:text-[15px]">{{ project.content }}</p>
            
            <div class="mt-auto">
              <a :href="project.link_url" target="_blank" rel="noopener noreferrer" class="visit-btn">
                {{ t('Visit Site') }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-2 arrow-icon" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </a>
            </div>
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
  data() {
    return {
      currentImageIndexes: {},
      intervals: {},
    };
  },
  computed: {
    processedProjects() {
      return this.projects.map(project => {
        const media = project.media || [];
        const logoImage = media.length > 0 ? media[media.length - 1] : null;
        const galleryImages = media.length > 1 ? media.slice(0, media.length - 1) : [];
        
        return {
          ...project,
          logoImage,
          galleryImages,
        };
      });
    }
  },
  methods: {
    currentImageUrl(project) {
      if (!project.galleryImages.length) return '';
      const index = this.currentImageIndexes[project.id] || 0;
      return project.galleryImages[index].original_url;
    },
    currentImageAlt(project) {
      if (!project.galleryImages.length) return project.title;
      const index = this.currentImageIndexes[project.id] || 0;
      return project.galleryImages[index].name;
    },
    currentImageKey(project) {
        if (!project.galleryImages.length) return project.id;
        const index = this.currentImageIndexes[project.id] || 0;
        return `${project.id}-${index}`;
    },
    startCarousel() {
      this.processedProjects.forEach(project => {
        if (project.galleryImages.length > 1) {
          this.currentImageIndexes[project.id] = 0;
          this.intervals[project.id] = setInterval(() => {
            const newIndex = (this.currentImageIndexes[project.id] + 1) % project.galleryImages.length;
            this.currentImageIndexes[project.id] = newIndex;
          }, 5000); 
        }
      });
    }
  },
  mounted() {
    this.startCarousel();
  },
  beforeDestroy() {
    Object.values(this.intervals).forEach(clearInterval);
  },
};
</script>

<style scoped>
/* =========== SHOWCASE CONTENEDOR =========== */
.project-showcase {
  position: relative;
  background: #0a0a14;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 1.5rem;
  padding: 1.5rem;
  margin-bottom: 4rem;
  overflow: hidden;
  /* Solo propiedades GPU-friendly */
  transition: border-color 0.5s ease, box-shadow 0.5s ease;
  transform: translateY(0);
  animation: showcaseStaggerIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.project-showcase:hover {
  border-color: rgba(139, 92, 246, 0.25);
  box-shadow: 0 8px 50px rgba(139, 92, 246, 0.06), 0 0 25px rgba(59, 130, 246, 0.04);
}

@keyframes showcaseStaggerIn {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* =========== GALERÍA =========== */
.gallery-col {
  /* nada extra, hereda del grid */
}

.main-image-wrapper {
  border-radius: 0.75rem;
  overflow: hidden;
  height: 320px;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.04);
}
@media (min-width: 640px) {
  .main-image-wrapper { height: 400px; }
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
  /* Solo transform en hover, sin filter para evitar lag */
  transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
  transform: scale(1);
}
.group\/img:hover .main-image {
  transform: scale(1.04);
}

.no-image-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  background: rgba(15, 15, 30, 0.5);
  border-radius: 0.75rem;
  color: #64748b;
  font-size: 1.5rem;
  font-weight: 300;
}

/* =========== DETALLES =========== */
.details-col {
  background: #0d0d1a;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 1rem;
  padding: 2rem;
  text-align: center;
  /* Sin backdrop-filter (pesado), solo fondo sólido con borde sutil */
  transition: border-color 0.5s ease, box-shadow 0.5s ease;
}
.project-showcase:hover .details-col {
  border-color: rgba(139, 92, 246, 0.35);
  box-shadow: 0 0 35px rgba(139, 92, 246, 0.08);
}

.logo-wrapper {
  margin-bottom: 1.5rem;
}

.project-logo {
  margin: 0 auto;
  height: 70px;
  width: auto;
  object-fit: contain;
}

/* =========== BOTÓN VISITAR =========== */
.visit-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.7rem 1.5rem;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, #7c3aed, #3b82f6);
  color: #fff;
  font-weight: 600;
  font-size: 0.9rem;
  text-decoration: none;
  /* Solo transform y box-shadow */
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1),
              box-shadow 0.25s ease;
  box-shadow: 0 0 18px rgba(124, 58, 237, 0.2);
}
.visit-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 35px rgba(124, 58, 237, 0.4);
}
.visit-btn .arrow-icon {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.visit-btn:hover .arrow-icon {
  transform: translateX(4px);
}

/* =========== TRANSICIÓN CROSSFADE (optimizada) =========== */
.crossfade-enter-active {
  transition: opacity 0.4s ease;
}
.crossfade-leave-active {
  transition: opacity 0.25s ease;
}
.crossfade-enter-from,
.crossfade-leave-to {
  opacity: 0;
}
</style>
