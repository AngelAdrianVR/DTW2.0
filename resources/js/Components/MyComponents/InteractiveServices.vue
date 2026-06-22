<script>
export default {
  props: {
    // Definimos una propiedad 'services' para recibir los datos desde el componente padre
    services: {
      type: Array,
      required: true,
    },
  },
  data() {
    return {
      // Estado para rastrear el servicio seleccionado. Nulo significa que no hay ninguno seleccionado.
      selectedService: null,
    };
  },
  methods: {
    // Devuelve la clase de color del borde hover según el servicio
    cardColorClass(serviceId) {
      const map = {
        web: 'hover:border-violet-500/30',
        mobile: 'hover:border-blue-500/30',
        cloud: 'hover:border-amber-500/30',
        iot: 'hover:border-cyan-500/30',
      };
      return map[serviceId] || 'hover:border-violet-500/30';
    },
    // Color principal del blob decorativo
    blobColor(serviceId) {
      const map = {
        web: '#7c3aed',
        mobile: '#3b82f6',
        cloud: '#eab308',
        iot: '#06b6d4',
      };
      return map[serviceId] || '#7c3aed';
    },
    // Color secundario del blob
    blobColorSecondary(serviceId) {
      const map = {
        web: '#a855f7',
        mobile: '#60a5fa',
        cloud: '#f59e0b',
        iot: '#22d3ee',
      };
      return map[serviceId] || '#a855f7';
    },
    // Efecto de inclinación 3D sutil en las tarjetas
    handleMouseMove(event) {
      if (this.selectedService) return;

      const card = event.currentTarget;
      const { left, top, width, height } = card.getBoundingClientRect();
      const x = event.clientX - left;
      const y = event.clientY - top;

      const rotateX = (y - height / 2) / (height / 2) * -5;
      const rotateY = (x - width / 2) / (width / 2) * 5;

      card.style.transform = `perspective(1200px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.03, 1.03, 1.03)`;
    },
    // Restablecer la transformación cuando el mouse sale de la tarjeta
    handleMouseLeave(event) {
      event.currentTarget.style.transform = 'perspective(1200px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
    },
    // Establecer el servicio seleccionado al hacer clic
    selectService(service) {
      this.selectedService = service;
    },
    // Volver a la vista de cuadrícula
    goBack() {
      this.selectedService = null;
    }
  }
}
</script>

<template>
  <!-- SECCIÓN DE SERVICIOS -->
  <section id="servicios" class="relative w-full bg-[#050510] px-4 sm:px-6 md:px-10 py-12 sm:py-20 overflow-hidden">
    <div class="max-w-[1400px] mx-auto">
      <h2
        class="text-white text-3xl sm:text-4xl md:text-5xl font-light text-center mb-12 sm:mb-24"
        style="letter-spacing: -0.04em;"
      >
        {{ t('Our Services') }}
      </h2>

      <!-- Componente de transición de Vue para animar el cambio entre vistas -->
      <transition name="service-view" mode="out-in">
        
        <!-- VISTA DE CUADRÍCULA (GRID) -->
        <div v-if="!selectedService" key="grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
          <div
            v-for="(service, index) in services"
            :key="service.id"
            :style="{ animationDelay: `${index * 0.1}s` }"
            class="service-card group cursor-pointer"
            :class="cardColorClass(service.id)"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
            @click="selectService(service)"
          >
            <!-- Blob decorativo grande -->
            <div
              class="absolute top-1/2 -translate-y-1/2 -left-[380px] h-[400px] w-[400px] rounded-full blur-3xl opacity-20 group-hover:opacity-35 transition-opacity duration-700"
              :style="{ backgroundColor: blobColor(service.id) }"
            ></div>
            <!-- Blob decorativo pequeño -->
            <div
              class="absolute -bottom-16 left-1/3 h-28 w-28 rounded-full blur-2xl opacity-10 group-hover:opacity-20 transition-opacity duration-500"
              :style="{ backgroundColor: blobColorSecondary(service.id) }"
            ></div>

            <!-- Contenido -->
            <div class="card-content relative z-10 flex flex-col h-full">
              <i :class="service.icon" class="text-4xl sm:text-5xl mb-6 transition-transform duration-500 group-hover:scale-110" :style="{ color: blobColor(service.id), filter: `drop-shadow(0 0 18px ${blobColor(service.id)}44)` }"></i>
              <h3 class="text-white text-xl sm:text-2xl font-light leading-tight mb-4">{{ service.title }}</h3>
              <p class="mt-auto text-[13px] sm:text-[14px] leading-relaxed text-white/60 font-light">{{ service.description }}</p>
            </div>
          </div>
        </div>

        <!-- VISTA DE DETALLE -->
        <div v-else key="detail" class="service-detail-container">
          <!-- Blobs decorativos del detalle -->
          <div
            class="absolute -top-40 -right-40 h-80 w-80 rounded-full blur-3xl opacity-20"
            :style="{ backgroundColor: blobColor(selectedService.id) }"
          ></div>
          <div
            class="absolute -bottom-20 -left-20 h-60 w-60 rounded-full blur-3xl opacity-10"
            :style="{ backgroundColor: blobColorSecondary(selectedService.id) }"
          ></div>

          <button @click="goBack" class="back-button">
            <i class="pi pi-arrow-left"></i> {{ t('Back') }}
          </button>
          
          <div class="relative z-10 grid md:grid-cols-2 gap-12 items-center">
            <div>
              <i :class="selectedService.icon" class="text-5xl mb-6 inline-block" :style="{ color: blobColor(selectedService.id), filter: `drop-shadow(0 0 20px ${blobColor(selectedService.id)}55)` }"></i>
              <h3 class="text-white text-3xl sm:text-4xl font-light leading-tight mb-6" style="letter-spacing: -0.03em;">{{ selectedService.title }}</h3>
              <p class="text-white/65 leading-relaxed font-light text-[15px]">{{ selectedService.detailedDescription }}</p>
            </div>
            
            <div class="image-gallery">
              <img v-for="(image, idx) in selectedService.images" :key="idx" :src="image" :alt="selectedService.title" class="gallery-image">
            </div>
          </div>
        </div>
      </transition>
    </div>
  </section>
</template>

<style scoped>
/* =========== TARJETAS DE SERVICIO (estilo BenefitsSection) =========== */
.service-card {
  position: relative;
  height: 380px;
  border-radius: 1rem;
  background: #0a0a14;
  overflow: hidden;
  padding: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  transition: transform 0.15s ease-out, border-color 0.5s ease, box-shadow 0.5s ease;
  transform-style: preserve-3d;
  will-change: transform;
}
@media (min-width: 640px) {
  .service-card {
    height: 440px;
    padding: 2rem;
  }
}
.service-card:hover {
  box-shadow: 0 0 50px rgba(168, 85, 247, 0.1), 0 0 25px rgba(59, 130, 246, 0.06);
}

/* Contenido interno no responde al pointer (para el 3D) */
.card-content {
  pointer-events: none;
}

/* =========== VISTA DE DETALLE =========== */
.service-detail-container {
  position: relative;
  background: #0a0a14;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 1.5rem;
  padding: 2rem 2.5rem;
  overflow: hidden;
  backdrop-filter: blur(15px);
}
.back-button {
  position: relative;
  z-index: 10;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #c4b5fd;
  padding: 0.5rem 1.5rem;
  border-radius: 999px;
  margin-bottom: 2rem;
  cursor: pointer;
  transition: all 0.35s ease;
  font-size: 0.875rem;
}
.back-button:hover {
  background: rgba(168, 85, 247, 0.1);
  color: #e9d5ff;
  box-shadow: 0 0 25px rgba(168, 85, 247, 0.25);
  border-color: rgba(168, 85, 247, 0.5);
}

/* =========== GALERÍA DE IMÁGENES =========== */
.image-gallery {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}
.gallery-image {
  width: 100%;
  height: auto;
  border-radius: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.06);
  transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  opacity: 0.75;
}
.gallery-image:hover {
  transform: scale(1.04);
  opacity: 1;
  box-shadow: 0 0 30px rgba(168, 85, 247, 0.2);
  border-color: rgba(168, 85, 247, 0.35);
}

/* =========== TRANSICIONES ENTRE VISTAS =========== */
.service-view-enter-active {
  transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
}
.service-view-leave-active {
  transition: all 0.3s cubic-bezier(0.55, 0, 1, 0.45);
}
.service-view-enter-from {
  opacity: 0;
  transform: translateY(24px) scale(0.97);
}
.service-view-leave-to {
  opacity: 0;
  transform: translateY(-16px) scale(0.98);
}

/* =========== ENTRADA ESCALONADA DE TARJETAS =========== */
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
.service-card {
  animation: cardStaggerIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
