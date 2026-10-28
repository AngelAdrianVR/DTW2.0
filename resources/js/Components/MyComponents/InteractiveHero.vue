<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
  setup() {
    // Referencia al elemento canvas de la plantilla
    const heroCanvas = ref(null);
    // ID para el bucle de animación, para poder detenerlo
    let animationFrameId;

    // Se ejecuta después de que el componente se monta en el DOM
    onMounted(() => {
      const canvas = heroCanvas.value;
      if (!canvas) return;

      const ctx = canvas.getContext('2d');
      let stars = [];
      let time = 0;

      // Ajustar el tamaño del canvas a su contenedor
      const resizeCanvas = () => {
        if (!canvas) return;
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        
        // CALIDAD: Cambia el 100 para ajustar cantidad de estrellas (más = más pesado)
        stars = Array.from({ length: 90 }, () => ({
          x: Math.random() * canvas.width,
          y: Math.random() * canvas.height,
          r: Math.random() * 1.5,
          a: Math.random() * Math.PI * 2
        }));
      };
      
      window.addEventListener('resize', resizeCanvas);
      resizeCanvas();

      function draw() {
        // Fondo transparente para que el video se vea a través
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Estrellas
        ctx.fillStyle = '#ffffff';
        stars.forEach(star => {
          star.a += 0.02;
          ctx.globalAlpha = 0.3 + Math.abs(Math.sin(star.a)) * 0.7;
          ctx.beginPath();
          ctx.arc(star.x, star.y, star.r, 0, Math.PI * 2);
          ctx.fill();
        });
        
        // Burbuja fluida con gradientes
        time += 0.015;
        const cx = canvas.width / 1.5;
        const cy = canvas.height / 1.5;
        // CALIDAD: Cambia el 0.5 para ajustar el tamaño de los orbes (más grande = más lag)
        // En desktop reducimos 25% el radio para que la burbuja no tape contenido
        const radius = Math.min(cx, cy) * (canvas.width >= 768 ? 0.3 : 0.4);

        ctx.globalAlpha = 1;
        ctx.globalCompositeOperation = 'screen';
        // CALIDAD: Cambia el blur para ajustar suavizado (más px = más difuso pero más pesado)
        ctx.filter = 'blur(35px)';

        const drawOrb = (color, ox, oy) => {
          const grad = ctx.createRadialGradient(ox, oy, 0, ox, oy, radius);
          grad.addColorStop(0, color);
          grad.addColorStop(1, 'transparent');
          ctx.fillStyle = grad;
          ctx.beginPath();
          ctx.arc(ox, oy, radius, 0, Math.PI * 2);
          ctx.fill();
        };

        drawOrb('rgba(168, 85, 247, 0.7)', cx + Math.sin(time) * radius * 0.4, cy + Math.cos(time) * radius * 0.4);
        drawOrb('rgba(234, 179, 8, 0.5)', cx + Math.cos(time * 1.2) * radius * 0.4, cy + Math.sin(time * 0.8) * radius * 0.4);
        drawOrb('rgba(59, 130, 246, 0.7)', cx + Math.sin(time * 0.9) * radius * 0.4, cy + Math.cos(time * 1.1) * radius * 0.4);

        ctx.filter = 'none';
        ctx.globalCompositeOperation = 'source-over';
      }
      
      // --- Variables para controlar la velocidad de la animación ---
      // CALIDAD: Cambia fps para ajustar fluidez (60 = máxima, 30 = aceptable, 24 = mínima)
      let fps = 30;
      let now;
      let then = Date.now();
      let interval = 1000 / fps;
      let delta;

      function animate() {
        animationFrameId = requestAnimationFrame(animate);
        
        now = Date.now();
        delta = now - then;

        // Solo dibuja un nuevo fotograma si ha pasado el tiempo suficiente
        if (delta > interval) {
            then = now - (delta % interval);
            draw();
        }
      }

      animate();

      // Limpieza al desmontar el componente
      onUnmounted(() => {
        cancelAnimationFrame(animationFrameId);
        window.removeEventListener('resize', resizeCanvas);
      });
    });

    return {
      heroCanvas
    };
  }
}
</script>

<template>
  <!-- Sección Hero principal -->
  <section id="inicio" class="hero-section min-h-screen flex items-center justify-center text-center relative overflow-hidden px-4">
    <!-- Video de fondo (burbuja fluida) -->
    <video
      autoplay
      loop
      muted
      playsinline
      class="absolute inset-0 w-full h-full object-cover z-0"
      src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260421_072701_f6a01abb-eb30-4559-9d6e-774362defbc3.mp4"
    ></video>

    <!-- Overlay oscuro para legibilidad del texto -->
    <div class="absolute inset-0 bg-gradient-to-b from-[#050510]/70 via-[#050510]/60 to-[#050510]/80 z-[1]"></div>

    <!-- Canvas para estrellas y orbes (transparente sobre el video) -->
    <canvas ref="heroCanvas" class="absolute top-0 left-0 w-full h-full z-[2]"></canvas>
    
    <!-- Contenido superpuesto -->
    <div class="relative z-10">
      <h1 class="text-5xl md:text-7xl font-bold mb-4 text-glow fade-in-up-h1">{{ t('Digital Innovation Without Limits') }}</h1>
      <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-12 fade-in-up-p">
        {{ t('We transform ideas into cutting-edge technological solutions. We drive your vision towards the digital future.') }}
      </p>
      <!-- <a href="#servicios" class="cta-button">
        {{ t('Explore Services') }}
      </a> -->
    </div>
  </section>
</template>

<style scoped>
/* Animaciones de entrada para el texto (con mayor duración) */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in-up-h1 {
  animation: fadeInUp 1.5s ease-out forwards;
}

.fade-in-up-p {
  animation: fadeInUp 1.5s ease-out 0.5s forwards;
  opacity: 0; /* Inicia invisible hasta que la animación comience */
}

/* Estilos para el texto con efecto de brillo */
.text-glow {
  text-shadow: 0 0 20px rgba(168, 85, 247, 0.5), 0 0 40px rgba(168, 85, 247, 0.2), 0 0 15px rgba(0, 0, 0, 0.9);
}

/* Estilos para el nuevo botón de llamada a la acción */
.cta-button {
  display: inline-block;
  font-size: 1rem;
  font-weight: 600;
  color: #c4b5fd;
  background-color: transparent;
  border: 2px solid #8b5cf6;
  border-radius: 8px;
  padding: 0.75rem 1.75rem;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 0 20px rgba(139, 92, 246, 0.2);
}

.cta-button:hover {
  background-color: rgba(139, 92, 246, 0.15);
  border-color: #a855f7;
  box-shadow: 0 0 35px rgba(168, 85, 247, 0.4);
  transform: translateY(-3px);
  color: #e9d5ff;
}

.cta-button:active {
  transform: translateY(-1px);
}
</style>