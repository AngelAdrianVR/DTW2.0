<template>
  <div class="w-full h-full">
    <canvas ref="miniGlobeCanvas"></canvas>
  </div>
</template>

<script>
import * as THREE from 'three';

export default {
  name: 'MiniGlobe',
  mounted() {
    this.animationFrameId = null;
    this.scene = null;
    this.camera = null;
    this.renderer = null;
    this.particles = null;

    // Esperar un tick para asegurar que el canvas esté en el DOM
    this.$nextTick(() => {
        this.initThreeJS();
        window.addEventListener('resize', this.onWindowResize);
    });
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.onWindowResize);
    cancelAnimationFrame(this.animationFrameId);
    if (this.renderer) {
      this.renderer.dispose();
    }
  },
  methods: {
    initThreeJS() {
      const canvas = this.$refs.miniGlobeCanvas;
      if (!canvas || !canvas.parentElement) return;

      const container = canvas.parentElement;

      // Escena, cámara y renderizador
      this.scene = new THREE.Scene();
      this.camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
      this.camera.position.z = 2.5; // Un poco más cerca

      this.renderer = new THREE.WebGLRenderer({ canvas, alpha: true, antialias: true });
      this.renderer.setSize(container.clientWidth, container.clientHeight);
      this.renderer.setPixelRatio(window.devicePixelRatio);

      // Partículas
      const particleCount = 220; // Reducido para mejor rendimiento
      const positions = new Float32Array(particleCount * 3);
      const radius = 1.2; // Radio más pequeño

      for (let i = 0; i < particleCount; i++) {
        const i3 = i * 3;
        
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
      this.particles.add(createParticleSystem(0x6215C0, 0.02)); // Púrpura
      this.particles.add(createParticleSystem(0x17EDF4, 0.02)); // Cyan

      this.scene.add(this.particles);
      this.animate();
    },

    animate() {
      this.animationFrameId = requestAnimationFrame(this.animate);
      if (this.particles) {
        this.particles.rotation.x += 0.001;
        this.particles.rotation.y += 0.0015;
      }
      if(this.renderer && this.scene && this.camera) {
        this.renderer.render(this.scene, this.camera);
      }
    },

    onWindowResize() {
        const canvas = this.$refs.miniGlobeCanvas;
        if (!canvas || !canvas.parentElement) return;

        const container = canvas.parentElement;
        this.camera.aspect = container.clientWidth / container.clientHeight;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize(container.clientWidth, container.clientHeight);
    },
  },
};
</script>

<style scoped>
canvas {
  display: block;
  width: 100%;
  height: 100%;
}
</style>
