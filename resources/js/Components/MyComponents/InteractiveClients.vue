<template>
  <section id="clients" class="py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <h2 class="section-title text-3xl sm:text-4xl font-bold text-center mb-12">
        <span>{{ t('Our Clients & Experience') }}</span>
      </h2>

      <!-- Los contadores ahora son el elemento principal -->
      <div ref="counters" class="grid grid-cols-1 md:grid-cols-3 gap-8 text-white w-full max-w-4xl mx-auto mb-20">
         <div v-for="counter in counters" :key="counter.label" class="counter-card p-6 text-center rounded-2xl">
          <p class="text-5xl sm:text-6xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-purple-500 mb-2">
            <span :id="'counter-' + counter.id">{{ Math.round(counter.displayValue) }}</span>+
          </p>
          <p class="text-lg text-gray-300">{{ t(counter.label) }}</p>
        </div>
      </div>

      <!-- Sección de Logos de Clientes con carrusel dinámico -->
      <div class="mt-16">
        <p class="text-center text-gray-400 font-semibold mb-8">{{ t('Trusted by leading companies') }}</p>
        
        <!-- Contenedor del carrusel -->
        <div class="logo-scroller" v-if="clientLogos && clientLogos.length > 0">
          <div class="logo-scroller-inner">
            <!-- Logos originales -->
            <div v-for="(client, index) in clientLogos" :key="`logo-a-${index}`" class="logo-item">
              <!-- MODIFICACIÓN: Aumentada la altura de h-10 a h-16 -->
              <img :src="client.url" :alt="client.name" class="h-16 w-auto object-contain" />
            </div>
            <!-- Logos duplicados para el efecto de bucle infinito -->
            <div v-for="(client, index) in clientLogos" :key="`logo-b-${index}`" aria-hidden="true" class="logo-item">
              <!-- MODIFICACIÓN: Aumentada la altura de h-10 a h-14 -->
              <img :src="client.url" :alt="client.name" class="h-10 w-auto object-contain" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'InteractiveClients',
  props: {
    clientLogos: {
      type: Array,
      required: true,
    }
  },
  data() {
    return {
      counters: [
        { id: 'clients', label: 'Happy Clients', target: 20, displayValue: 0 },
        { id: 'projects', label: 'Projects Completed', target: 30, displayValue: 0 },
        { id: 'experience', label: 'Years of Experience', target: 4, displayValue: 0 },
      ],
      observer: null,
    };
  },
  mounted() {
    this.initCounterObserver();
  },
  beforeDestroy() {
    if (this.observer) {
      this.observer.disconnect();
    }
  },
  methods: {
    initCounterObserver() {
      this.observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              this.animateCounters();
              this.observer.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.5 }
      );

      if (this.$refs.counters) {
        this.observer.observe(this.$refs.counters);
      }
    },
    
    animateCounters() {
      const duration = 2000; // 2 segundos
      this.counters.forEach(counter => {
        let startTime = null;
        const step = (timestamp) => {
          if (!startTime) startTime = timestamp;
          const progress = Math.min((timestamp - startTime) / duration, 1);
          counter.displayValue = progress * counter.target;
          if (progress < 1) {
            requestAnimationFrame(step);
          } else {
            counter.displayValue = counter.target;
          }
        };
        requestAnimationFrame(step);
      });
    },
  },
};
</script>

<style scoped>
/* Estilos del título y las tarjetas de contador (sin cambios) */
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
.counter-card {
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(23, 25, 48, 0.5);
  backdrop-filter: blur(5px);
  transition: all 0.3s ease-in-out;
}
.counter-card:hover {
  transform: translateY(-8px);
  border-color: rgba(23, 237, 244, 0.5);
  box-shadow: 0 0 20px rgba(23, 237, 244, 0.2);
}

/* Estilos para el carrusel de logos */
.logo-scroller {
  max-width: 100%;
  overflow: hidden;
  -webkit-mask-image: linear-gradient(to right, transparent, white 20%, white 80%, transparent);
  mask-image: linear-gradient(to right, transparent, white 20%, white 80%, transparent);
}

.logo-scroller-inner {
  display: flex;
  flex-wrap: nowrap;
  width: fit-content;
  animation: scroll 40s linear infinite;
}

.logo-scroller:hover .logo-scroller-inner {
  animation-play-state: paused;
}

.logo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2rem; /* 32px de espacio entre logos */
}

.logo-item img {
  max-width: none; /* Evita que las imágenes se encojan */
  transition: all 0.3s ease;
  filter: grayscale(1);
  opacity: 0.7;
}

.logo-item img:hover {
  filter: grayscale(0);
  opacity: 1;
  transform: scale(1.1);
}

@keyframes scroll {
  to {
    transform: translateX(-50%);
  }
}
</style>

