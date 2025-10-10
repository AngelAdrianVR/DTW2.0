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
      let drops = [];

      // Ajustar el tamaño del canvas a su contenedor
      const resizeCanvas = () => {
        if (!canvas) return;
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        
        // Inicializar las gotas para la lluvia digital
        const fontSize = 16;
        const columns = Math.floor(canvas.width / fontSize);
        drops = [];
        for (let i = 0; i < columns; i++) {
          drops[i] = 1;
        }
      };
      
      window.addEventListener('resize', resizeCanvas);
      resizeCanvas();

      // Caracteres a usar en la animación
      const binary = "01";

      // Bucle de animación para la lluvia digital
      function draw() {
        // Fondo semi-transparente para crear el efecto de estela
        ctx.fillStyle = 'rgba(10, 25, 47, 0.1)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#64FFDA'; // Un color verde menta tecnológico
        const fontSize = 16;
        ctx.font = `${fontSize}px monospace`;

        for (let i = 0; i < drops.length; i++) {
          const text = binary.charAt(Math.floor(Math.random() * binary.length));
          ctx.fillText(text, i * fontSize, drops[i] * fontSize);

          // Reiniciar la gota si llega al final de la pantalla
          if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
            drops[i] = 0;
          }
          drops[i]++;
        }
      }
      
      // --- Variables para controlar la velocidad de la animación ---
      let fps = 40; // Reducimos los fotogramas por segundo para que sea más lento
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
    <!-- Canvas para la animación de fondo -->
    <canvas ref="heroCanvas" class="absolute top-0 left-0 w-full h-full z-0"></canvas>
    
    <!-- Contenido superpuesto -->
    <div class="relative z-10">
      <!-- Ahora usamos la función `t()` global que viene del mixin -->
      <h1 class="text-5xl md:text-7xl font-bold mb-4 text-glow fade-in-up-h1">{{ t('Digital Innovation Without Limits') }}</h1>
      <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-12 fade-in-up-p">
        {{ t('We transform ideas into cutting-edge technological solutions. We drive your vision towards the digital future.') }}
      </p>
      <!-- Botón de llamada a la acción con estilo simplificado -->
      <a href="#servicios" class="cta-button">
        {{ t('Explore Services') }}
      </a>
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
  /* Sutil efecto de sombra para mejorar la legibilidad sobre el fondo */
  text-shadow: 0 0 10px rgba(10, 25, 47, 0.8);
}

/* Estilos para el nuevo botón de llamada a la acción */
.cta-button {
  display: inline-block;
  font-size: 1rem;
  font-weight: 600;
  color: #64FFDA; /* Mismo color que la lluvia digital */
  background-color: transparent;
  border: 2px solid #64FFDA;
  border-radius: 8px;
  padding: 0.75rem 1.75rem;
  text-decoration: none;
  transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease;
}

.cta-button:hover {
  background-color: rgba(100, 255, 218, 0.1);
  transform: translateY(-3px); /* Ligero efecto de elevación */
}

.cta-button:active {
  transform: translateY(-1px);
}
</style>

