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
    // Lógica para manejar el efecto de inclinación 3D en las tarjetas
    handleMouseMove(event) {
      // Deshabilitamos el efecto si un servicio ya está seleccionado
      if (this.selectedService) return;

      const card = event.currentTarget;
      const { left, top, width, height } = card.getBoundingClientRect();
      const x = event.clientX - left;
      const y = event.clientY - top;

      const rotateX = (y - height / 2) / (height / 2) * -8;
      const rotateY = (x - width / 2) / (width / 2) * 8;

      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.05, 1.05, 1.05)`;
    },
    // Restablecer la transformación cuando el mouse sale de la tarjeta
    handleMouseLeave(event) {
      event.currentTarget.style.transform = 'perspective(1000px) rotateX(0) rotateY(0) scale3d(1, 1, 1)';
    },
    // Método para establecer el servicio seleccionado al hacer clic
    selectService(service) {
      this.selectedService = service;
    },
    // Método para volver a la vista de cuadrícula
    goBack() {
      this.selectedService = null;
    }
  }
}
</script>

<template>
  <!-- SECCIÓN DE SERVICIOS -->
  <section id="servicios" class="py-20 px-4 relative z-10 bg-[#111827] min-h-[70vh] overflow-hidden">
    <div class="container mx-auto">
      <h2 class="text-4xl font-bold text-center mb-16 section-title"><span>{{ t('Our Services') }}</span></h2>

      <!-- Componente de transición de Vue para animar el cambio entre vistas -->
      <transition name="fade-transform" mode="out-in">
        
        <!-- VISTA DE CUADRÍCULA (GRID) - Se muestra si no hay servicio seleccionado -->
        <div v-if="!selectedService" key="grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          <div 
            v-for="service in services" 
            :key="service.id" 
            class="service-card-3d cursor-pointer"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
            @click="selectService(service)"
          >
            <div class="card-content text-center">
              <i :class="service.icon" style="font-size: 3.5rem;" class="icon-glow"></i>
              <h3 class="text-2xl font-semibold mt-4">{{ service.title }}</h3>
              <p class="text-gray-400 mt-4 px-4 text-sm">{{ service.description }}</p>
            </div>
          </div>
        </div>

        <!-- VISTA DE DETALLE - Se muestra cuando se selecciona un servicio -->
        <div v-else key="detail" class="service-detail-container">
          <!-- Botón para regresar a la vista de cuadrícula -->
          <button @click="goBack" class="back-button">
             <i class="pi pi-arrow-left"></i> {{ t('Back') }}
          </button>
          
          <div class="grid md:grid-cols-2 gap-12 items-center">
            <!-- Contenido de texto del servicio -->
            <div class="text-content">
              <i :class="selectedService.icon" style="font-size: 3.5rem;" class="icon-glow mb-4 inline-block"></i>
              <h3 class="text-4xl font-bold text-glow mb-6">{{ selectedService.title }}</h3>
              <p class="text-gray-300 leading-relaxed">{{ selectedService.detailedDescription }}</p>
            </div>
            
            <!-- Galería de imágenes del servicio -->
            <div class="image-gallery">
                <img v-for="(image, index) in selectedService.images" :key="index" :src="image" :alt="selectedService.title" class="gallery-image">
            </div>
          </div>
        </div>
      </transition>
    </div>
  </section>
</template>

<style scoped>
/* Estilos existentes para el título de la sección */
.section-title {
  position: relative;
  display: inline-block;
  left: 50%;
  transform: translateX(-50%);
  color: #fff;
  padding-bottom: 0.5rem;
}
.section-title span { z-index: 1; }
.section-title::after {
  content: ''; position: absolute; bottom: 0; left: 50%;
  transform: translateX(-50%); width: 60%; height: 3px;
  background: linear-gradient(90deg, #6215C0, #17EDF4); border-radius: 2px;
}

/* Estilos para las tarjetas de servicio con efecto 3D */
.service-card-3d {
  position: relative; background: rgba(23, 25, 48, 0.5);
  border-radius: 1rem; padding: 2rem 1.5rem;
  transition: transform 0.1s ease-out; transform-style: preserve-3d; will-change: transform;
}
.service-card-3d::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  border-radius: 1rem; border: 1px solid transparent;
  background: linear-gradient(135deg, rgba(23, 237, 244, 0.2), rgba(98, 21, 192, 0.2)) border-box;
  -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite: destination-out; mask-composite: exclude;
  transition: all 0.3s ease;
}
.service-card-3d:hover::before {
  background: linear-gradient(135deg, rgba(23, 237, 244, 0.7), rgba(98, 21, 192, 0.7)) border-box;
  box-shadow: 0 0 25px rgba(23, 237, 244, 0.3);
}
.card-content { pointer-events: none; }
.icon-glow { color: #17EDF4; text-shadow: 0 0 10px #17EDF4, 0 0 20px #17EDF4; }

/* NUEVOS ESTILOS PARA LA VISTA DE DETALLE */
.service-detail-container {
  background: rgba(23, 25, 48, 0.6);
  border: 1px solid rgba(23, 237, 244, 0.2);
  backdrop-filter: blur(15px);
  border-radius: 1.5rem;
  padding: 2rem 3rem;
}
.back-button {
  display: inline-flex; align-items: center; gap: 0.5rem;
  background: transparent; border: 1px solid rgba(23, 237, 244, 0.5);
  color: rgba(23, 237, 244, 0.8); padding: 0.5rem 1.5rem; border-radius: 999px;
  margin-bottom: 2rem; cursor: pointer; transition: all 0.3s ease;
}
.back-button:hover {
  background: rgba(23, 237, 244, 0.2); color: #17EDF4;
  box-shadow: 0 0 15px rgba(23, 237, 244, 0.5);
}
.text-glow { text-shadow: 0 0 8px rgba(23, 237, 244, 0.6), 0 0 20px rgba(98, 21, 192, 0.5); }
.image-gallery {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}
.gallery-image {
  width: 100%; height: auto; border-radius: 0.75rem;
  border: 1px solid rgba(98, 21, 192, 0.3);
  transition: all 0.3s ease; opacity: 0.8;
}
.gallery-image:hover {
  transform: scale(1.05); opacity: 1;
  box-shadow: 0 0 20px rgba(98, 21, 192, 0.5);
  border-color: rgba(98, 21, 192, 0.7);
}

/* ESTILOS DE TRANSICIÓN */
.fade-transform-enter-active, .fade-transform-leave-active {
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
}
.fade-transform-enter-from, .fade-transform-leave-to {
  opacity: 0;
  transform: scale(0.9) translateY(20px);
}
</style>
