<template>
  <section id="clients" class="relative w-full bg-[#050510] px-4 sm:px-6 md:px-10 py-12 sm:py-20 overflow-hidden">
    <!-- Blobs ambientales -->
    <div class="absolute top-0 -right-40 w-[500px] h-[500px] rounded-full blur-[120px] opacity-[0.06] pointer-events-none" style="background-color: #06b6d4;"></div>
    <div class="absolute -bottom-32 -left-32 w-[450px] h-[450px] rounded-full blur-[100px] opacity-[0.05] pointer-events-none" style="background-color: #7c3aed;"></div>

    <div class="max-w-[1400px] mx-auto">
      <h2
        class="text-white text-3xl sm:text-4xl md:text-5xl font-light text-center mb-12 sm:mb-20"
        style="letter-spacing: -0.04em;"
      >
        {{ t('Our Clients & Experience') }}
      </h2>

      <!-- Contadores -->
      <div ref="counters" class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 w-full max-w-4xl mx-auto mb-16 sm:mb-24">
        <div
          v-for="(counter, idx) in counters"
          :key="counter.label"
          :style="{ animationDelay: `${idx * 0.1}s` }"
          class="counter-card"
        >
          <p class="counter-number">
            <span :id="'counter-' + counter.id">{{ Math.round(counter.displayValue) }}</span>+
          </p>
          <p class="counter-label">{{ t(counter.label) }}</p>
        </div>
      </div>

      <!-- Logos de Clientes -->
      <div class="mt-12">
        <p class="text-center text-white/40 font-light text-sm tracking-wider uppercase mb-8">{{ t('Trusted by leading companies') }}</p>
        
        <div class="logo-scroller" v-if="clientLogos && clientLogos.length > 0">
          <div class="logo-scroller-inner">
            <div v-for="(client, index) in clientLogos" :key="`logo-a-${index}`" class="logo-item">
              <img :src="client.url" :alt="client.name" class="logo-img" />
            </div>
            <div v-for="(client, index) in clientLogos" :key="`logo-b-${index}`" aria-hidden="true" class="logo-item">
              <img :src="client.url" :alt="client.name" class="logo-img" />
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
/* =========== TARJETAS DE CONTADORES =========== */
.counter-card {
  padding: 1.5rem;
  text-align: center;
  border-radius: 1rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  background: #0a0a14;
  /* Sin backdrop-filter, sin transition:all */
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1),
              border-color 0.4s ease,
              box-shadow 0.4s ease;
  transform: translateY(0);
  animation: counterStaggerIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}
.counter-card:hover {
  transform: translateY(-6px);
  border-color: rgba(139, 92, 246, 0.3);
  box-shadow: 0 8px 35px rgba(139, 92, 246, 0.1);
}

@keyframes counterStaggerIn {
  from { opacity: 0; transform: translateY(24px); }
  to   { opacity: 1; transform: translateY(0); }
}

.counter-number {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  background: linear-gradient(135deg, #a855f7, #eab308, #3b82f6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
@media (min-width: 640px) {
  .counter-number { font-size: 3.75rem; }
}

.counter-label {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.45);
  font-weight: 300;
}

/* =========== CARRUSEL DE LOGOS =========== */
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
  /* GPU-accelerated: solo usa transform */
  will-change: transform;
  animation: logoScroll 45s linear infinite;
}

.logo-scroller:hover .logo-scroller-inner {
  animation-play-state: paused;
}

.logo-item {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2rem;
}

.logo-img {
  height: 40px;
  width: auto;
  max-width: none;
  object-fit: contain;
  /* Solo opacity y transform en hover, sin filter para evitar lag */
  opacity: 0.55;
  transition: opacity 0.3s ease, transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
@media (min-width: 640px) {
  .logo-img { height: 56px; }
}

.logo-img:hover {
  opacity: 1;
  transform: scale(1.08);
}

@keyframes logoScroll {
  to { transform: translateX(-50%); }
}
</style>

