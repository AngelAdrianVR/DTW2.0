<script setup>
import { ref, onMounted, onBeforeUnmount, computed } from 'vue';
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';

const { props: pageProps } = usePage();
const locale = computed(() => pageProps.locale || 'en');

const props = defineProps({
  project: { type: Object, required: true },
});

const title = computed(() => {
  if (locale.value === 'es' && props.project.spanish_title) return props.project.spanish_title;
  return props.project.english_title || props.project.spanish_title || 'Untitled Project';
});

const content = computed(() => {
  if (locale.value === 'es' && props.project.spanish_content) return props.project.spanish_content;
  return props.project.english_content || props.project.spanish_content || '';
});

const backLinkText = computed(() => {
  return locale.value === 'es' ? 'Volver a Proyectos' : 'Back to Projects';
});

// --- Lightbox ---
const lightboxImage = ref(null);
const isLightboxOpen = ref(false);

const openLightbox = (url) => {
  lightboxImage.value = url;
  isLightboxOpen.value = true;
  document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
  isLightboxOpen.value = false;
  document.body.style.overflow = '';
  setTimeout(() => { lightboxImage.value = null; }, 300);
};

const onLightboxKeydown = (e) => {
  if (e.key === 'Escape') closeLightbox();
};

// --- Entrada de página ---
onMounted(() => {
  requestAnimationFrame(() => {
    const page = document.querySelector('.project-details-page');
    if (page) page.classList.add('page-enter-active');
  });
  document.addEventListener('keydown', onLightboxKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onLightboxKeydown);
  document.body.style.overflow = '';
});
</script>

<template>
  <LandingLayout :title="title">
    <div class="project-details-page">
      <!-- Blobs decorativos de fondo -->
      <div class="fixed pointer-events-none inset-0 overflow-hidden z-0">
        <div class="absolute -top-60 -right-60 w-[600px] h-[600px] rounded-full blur-[120px] opacity-[0.12]" style="background-color: #7c3aed;"></div>
        <div class="absolute top-1/3 -left-40 w-[500px] h-[500px] rounded-full blur-[100px] opacity-[0.08]" style="background-color: #3b82f6;"></div>
        <div class="absolute -bottom-40 right-1/4 w-[400px] h-[400px] rounded-full blur-[100px] opacity-[0.07]" style="background-color: #eab308;"></div>
      </div>

      <main class="relative z-10 container mx-auto px-4 md:px-8 py-24 md:py-32 text-white">
        <!-- Botón para volver -->
        <div class="mb-12">
          <Link href="/" class="back-link group">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 transition-transform duration-300 group-hover:-translate-x-1.5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            {{ backLinkText }}
          </Link>
        </div>

        <!-- Layout principal de dos columnas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
          <!-- Columna Izquierda: Información del Proyecto -->
          <div class="project-info-container">
            <!-- Blob decorativo del info -->
            <div class="absolute -top-32 -left-32 w-80 h-80 rounded-full blur-[80px] opacity-[0.08] pointer-events-none" style="background-color: #17edf4;"></div>
            <h1 class="relative text-5xl md:text-7xl font-bold text-glow mb-4">{{ title }}</h1>
            <h2 class="relative text-2xl md:text-3xl font-light text-cyan-300/80 mb-8" style="letter-spacing: -0.02em;">{{ project.subtitle }}</h2>
            <p class="relative text-lg text-white/65 leading-relaxed whitespace-pre-wrap mb-10 font-light">{{ content }}</p>
            <a v-if="project.link_url" :href="project.link_url" target="_blank" rel="noopener noreferrer">
              <Button label="Visitar el Sitio" icon="pi pi-external-link" class="p-button-info p-button-raised launch-button" />
            </a>
          </div>

          <!-- Columna Derecha: Galería de Imágenes con Scroll -->
          <div class="gallery-container">
            <div class="scroll-driven-gallery space-y-8 md:space-y-12">
              <div
                v-for="(media, index) in project.media"
                :key="index"
                class="image-wrapper group cursor-pointer"
                :style="{ animationDelay: `${index * 0.1}s` }"
                @click="openLightbox(media.original_url)"
              >
                <!-- Brillo sutil al hover -->
                <div class="absolute inset-0 bg-gradient-to-t from-violet-600/10 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10 pointer-events-none rounded-xl"></div>
                <!-- Icono de lupa -->
                <div class="absolute top-3 right-3 z-20 opacity-0 group-hover:opacity-100 transition-all duration-300 bg-black/50 backdrop-blur-sm rounded-full p-2 pointer-events-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="14"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                  </svg>
                </div>
                <img
                  :src="media.original_url"
                  :alt="`Imagen ${index + 1} de ${title}`"
                  class="gallery-image"
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

      <!-- ========== LIGHTBOX ========== -->
      <Teleport to="body">
        <transition name="lightbox">
          <div
            v-if="isLightboxOpen"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-8"
            @click.self="closeLightbox"
          >
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>
            <!-- Botón cerrar -->
            <button
              @click="closeLightbox"
              class="absolute top-4 right-4 sm:top-6 sm:right-6 z-20 p-2 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-sm border border-white/10 hover:border-white/30 transition-all duration-200 group"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white group-hover:rotate-90 transition-transform duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
            <!-- Imagen -->
            <img
              v-if="lightboxImage"
              :src="lightboxImage"
              class="relative z-10 max-w-full max-h-[90vh] rounded-xl shadow-2xl object-contain"
              @click.stop
            />
          </div>
        </transition>
      </Teleport>
    </div>
  </LandingLayout>
</template>

<style scoped>
/* =========== ENTRADA DE PÁGINA =========== */
@keyframes page-fade-in-up {
  from { opacity: 0; transform: translateY(30px); }
  to   { opacity: 1; transform: translateY(0); }
}

.project-details-page {
  opacity: 0;
}
.project-details-page.page-enter-active {
  animation: page-fade-in-up 0.7s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* =========== INFO COLUMN =========== */
.project-info-container {
  position: sticky;
  top: 120px;
  height: fit-content;
}

.text-glow {
  text-shadow: 0 0 12px rgba(23, 237, 244, 0.45), 0 0 35px rgba(98, 21, 192, 0.35);
}

/* =========== BACK LINK =========== */
.back-link {
  display: inline-flex;
  align-items: center;
  font-size: 0.95rem;
  color: #8b8fa5;
  padding: 0.5rem 1.25rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
  backdrop-filter: blur(8px);
  transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.back-link:hover {
  color: #17EDF4;
  border-color: rgba(23, 237, 244, 0.35);
  background: rgba(23, 237, 244, 0.06);
  box-shadow: 0 0 25px rgba(23, 237, 244, 0.12);
}

/* =========== LAUNCH BUTTON =========== */
.launch-button {
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease !important;
}
.launch-button:hover {
  transform: translateY(-3px) !important;
  box-shadow: 0 12px 30px rgba(98, 21, 192, 0.35) !important;
}

/* =========== GALERÍA =========== */
.gallery-container {
  perspective: 1000px;
}

.image-wrapper {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.06);
  transform-style: preserve-3d;
  animation: galleryStaggerIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1),
              border-color 0.4s ease,
              box-shadow 0.4s ease;
}
.image-wrapper:hover {
  transform: translateY(-3px);
  border-color: rgba(139, 92, 246, 0.4);
  box-shadow: 0 16px 45px rgba(139, 92, 246, 0.15), 0 0 25px rgba(59, 130, 246, 0.08);
}

@keyframes galleryStaggerIn {
  from { opacity: 0; transform: translateY(36px); }
  to   { opacity: 1; transform: translateY(0); }
}

.gallery-image {
  width: 100%;
  height: auto;
  object-fit: cover;
  display: block;
  transition: transform 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
.image-wrapper:hover .gallery-image {
  transform: scale(1.03);
}

/* =========== SCROLL-DRIVEN ANIMATIONS (navegadores compatibles) =========== */
@supports (animation-timeline: scroll()) {
  .image-wrapper {
    animation: reveal-3d linear forwards, galleryStaggerIn 0.55s cubic-bezier(0.16, 1, 0.3, 1) both;
    animation-timeline: scroll(), auto;
    animation-range: entry 5% cover 40%, normal;
  }

  .gallery-image {
    animation: parallax-scroll linear forwards;
    animation-timeline: view();
    animation-range: entry 0% cover 100%;
  }

  @keyframes reveal-3d {
    from { opacity: 0; transform: rotateX(-15deg) scale(0.92); }
    to   { opacity: 1; transform: rotateX(0deg) scale(1); }
  }

  @keyframes parallax-scroll {
    from { transform: scale(1.12) translateY(-10%); }
    to   { transform: scale(1.12) translateY(3%); }
  }
}

/* Fallback sin scroll-driven */
@supports not (animation-timeline: scroll()) {
  .image-wrapper {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s cubic-bezier(0.16, 1, 0.3, 1),
                transform 0.6s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .page-enter-active .image-wrapper {
    opacity: 1;
    transform: translateY(0);
  }
}

/* =========== LIGHTBOX =========== */
.lightbox-enter-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.lightbox-leave-active {
  transition: all 0.2s cubic-bezier(0.55, 0, 1, 0.45);
}
.lightbox-enter-from {
  opacity: 0;
}
.lightbox-enter-from img {
  transform: scale(0.9);
  opacity: 0;
}
.lightbox-leave-to {
  opacity: 0;
}
.lightbox-leave-to img {
  transform: scale(0.95);
  opacity: 0;
}
.lightbox-enter-active img {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.lightbox-leave-active img {
  transition: all 0.2s cubic-bezier(0.55, 0, 1, 0.45);
}

/* =========== RESPONSIVE =========== */
@media (max-width: 1023px) {
  .project-info-container {
    position: static;
    top: auto;
    text-align: center;
  }
  .launch-button { margin: 0 auto; }
  .grid { gap: 3rem; }
  h1 { font-size: 2.5rem; }
  h2 { font-size: 1.35rem; }
  .back-link { font-size: 0.85rem; padding: 0.4rem 1rem; }
}
</style>
