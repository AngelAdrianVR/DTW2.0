<template>
  <section id="clients" class="py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <h2 class="section-title text-3xl sm:text-4xl font-bold text-center mb-16">
        <span>{{ t('Our Clients & Experience') }}</span>
      </h2>

      <div class="flex flex-col items-center justify-center gap-16">
        <!-- Columna de Animación del Planeta -->
        <div class="relative w-full max-w-lg h-96 flex-shrink-0">
          <canvas ref="globeCanvas" class="w-full h-full"></canvas>
          <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <p class="text-2xl sm:text-3xl font-bold text-center text-white text-glow max-w-xs">
              {{ t('Are You Ready to Accelerate Your Business?') }}
            </p>
          </div>
        </div>

        <!-- Columna de Contadores -->
        <div ref="counters" class="grid grid-cols-1 md:grid-cols-3 gap-8 text-white w-full max-w-4xl">
           <div v-for="counter in counters" :key="counter.label" class="counter-card p-6 text-center rounded-2xl">
            <p class="text-5xl sm:text-6xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-purple-500 mb-2">
              <span :id="'counter-' + counter.id">{{ Math.round(counter.displayValue) }}</span>+
            </p>
            <p class="text-lg text-gray-300">{{ t(counter.label) }}</p>
          </div>
        </div>
      </div>

      <!-- Sección de Logos de Clientes -->
      <div class="mt-24">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-8 items-center">
          <div v-for="(client, index) in clients" :key="index" class="flex justify-center">
            <img :src="client.logo" :alt="client.name" class="h-10 w-auto object-contain transition-all duration-300 filter grayscale hover:grayscale-0 opacity-60 hover:opacity-100" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import * as THREE from 'three';

export default {
  name: 'InteractiveClients',
  data() {
    return {
      counters: [
        { id: 'clients', label: 'Happy Clients', target: 40, displayValue: 0 },
        { id: 'projects', label: 'Projects Completed', target: 28, displayValue: 0 },
        { id: 'experience', label: 'Years of Experience', target: 4, displayValue: 0 },
      ],
      clients: [
        { name: 'TechCorp', logo: 'https://placehold.co/150x50/ffffff/111827?text=TechCorp&font=raleway' },
        { name: 'InnovateX', logo: 'https://placehold.co/150x50/ffffff/111827?text=InnovateX&font=raleway' },
        { name: 'DataStream', logo: 'https://placehold.co/150x50/ffffff/111827?text=DataStream&font=raleway' },
        { name: 'CloudNine', logo: 'https://placehold.co/150x50/ffffff/111827?text=CloudNine&font=raleway' },
        { name: 'QuantumLeap', logo: 'https://placehold.co/150x50/ffffff/111827?text=QuantumLeap&font=raleway' },
        { name: 'NextGen', logo: 'https://placehold.co/150x50/ffffff/111827?text=NextGen&font=raleway' },
      ],
      observer: null,
    };
  },
  mounted() {
    this.animationFrameId = null;
    this.scene = null;
    this.camera = null;
    this.renderer = null;
    this.particles = null;

    this.initThreeJS();
    this.initCounterObserver();
    window.addEventListener('resize', this.onWindowResize);
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.onWindowResize);
    if (this.observer) {
      this.observer.disconnect();
    }
    cancelAnimationFrame(this.animationFrameId);
    if (this.renderer) {
      this.renderer.dispose();
    }
  },
  methods: {
    initThreeJS() {
      const canvas = this.$refs.globeCanvas;
      if (!canvas) return;

      // Escena, cámara y renderizador
      this.scene = new THREE.Scene();
      this.camera = new THREE.PerspectiveCamera(75, canvas.clientWidth / canvas.clientHeight, 0.1, 1000);
      this.camera.position.z = 3;

      this.renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
      this.renderer.setSize(canvas.clientWidth, canvas.clientHeight);
      this.renderer.setPixelRatio(window.devicePixelRatio);

      // Partículas
      const particleCount = 500; // Reducido para mejor rendimiento
      const positions = new Float32Array(particleCount * 3);
      const radius = 1.8;

      for (let i = 0; i < particleCount; i++) {
        const i3 = i * 3;
        
        // Generar puntos en una esfera
        const theta = Math.random() * 2 * Math.PI;
        const phi = Math.acos(2 * Math.random() - 1);
        
        positions[i3] = radius * Math.sin(phi) * Math.cos(theta);
        positions[i3 + 1] = radius * Math.sin(phi) * Math.sin(theta);
        positions[i3 + 2] = radius * Math.cos(phi);
      }
      
      const particlesGeometry = new THREE.BufferGeometry();
      particlesGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
      
      const createParticleSystem = (color, size) => {
        const material = new THREE.PointsMaterial({
          color: color,
          size: size,
          blending: THREE.AdditiveBlending,
          transparent: true,
          depthWrite: false,
          sizeAttenuation: true,
        });
        return new THREE.Points(particlesGeometry, material);
      };

      this.particles = new THREE.Group();
      this.particles.add(createParticleSystem(0x6215C0, 0.018)); // Púrpura
      this.particles.add(createParticleSystem(0x17EDF4, 0.018)); // Cyan

      this.scene.add(this.particles);
      this.animate();
    },

    animate() {
      this.animationFrameId = requestAnimationFrame(this.animate);
      if (this.particles) {
        this.particles.rotation.x += 0.0005;
        this.particles.rotation.y += 0.0008;
      }
      this.renderer.render(this.scene, this.camera);
    },

    onWindowResize() {
        if (!this.$refs.globeCanvas) return;
        const canvas = this.$refs.globeCanvas;
        this.camera.aspect = canvas.clientWidth / canvas.clientHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(canvas.clientWidth, canvas.clientHeight);
    },

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
/* Estilos adicionales para este componente si son necesarios */
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
.text-glow {
  text-shadow: 0 0 8px rgba(23, 237, 244, 0.6), 0 0 20px rgba(98, 21, 192, 0.5);
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
</style>

