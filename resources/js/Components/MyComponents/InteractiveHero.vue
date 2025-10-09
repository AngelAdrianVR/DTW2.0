<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
  // Usamos el API de Opciones para ser consistentes con tu Index.vue
  // y para tener acceso a `this.t()` del mixin global.
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
      let particlesArray = [];

      // Clase para crear partículas individuales
      class Particle {
        constructor(x, y, directionX, directionY, size, color) {
          this.x = x;
          this.y = y;
          this.directionX = directionX;
          this.directionY = directionY;
          this.size = size;
          this.color = color;
        }

        // Método para dibujar la partícula
        draw() {
          ctx.beginPath();
          ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
          ctx.fillStyle = '#8C92AC';
          ctx.fill();
        }

        // Método para actualizar la posición de la partícula
        update() {
          if (this.x > canvas.width || this.x < 0) {
            this.directionX = -this.directionX;
          }
          if (this.y > canvas.height || this.y < 0) {
            this.directionY = -this.directionY;
          }
          this.x += this.directionX;
          this.y += this.directionY;
          this.draw();
        }
      }
      
      // Ajustar el tamaño del canvas a su contenedor
      const resizeCanvas = () => {
        if (!canvas) return;
        canvas.width = canvas.offsetWidth;
        canvas.height = canvas.offsetHeight;
        init(); // Reinicializar partículas al cambiar tamaño para evitar espacios vacíos
      };
      
      window.addEventListener('resize', resizeCanvas);
      resizeCanvas();

      // Objeto para rastrear la posición del mouse
      const mouse = {
        x: null,
        y: null,
        radius: 150
      };

      canvas.addEventListener('mousemove', (event) => {
        const rect = canvas.getBoundingClientRect();
        mouse.x = event.clientX - rect.left;
        mouse.y = event.clientY - rect.top;
      });

      canvas.addEventListener('mouseleave', () => {
        mouse.x = null;
        mouse.y = null;
      });

      // Inicializar el arreglo de partículas
      function init() {
        particlesArray = [];
        let density = (canvas.width * canvas.height) / 10000; // Reducir densidad
        const maxParticles = 100; // Límite reducido para mejor rendimiento
        for (let i = 0; i < density && i < maxParticles; i++) {
            let size = Math.random() * 1.5 + 1; // Partículas ligeramente más pequeñas
            let x = Math.random() * (canvas.width - size * 2) + size;
            let y = Math.random() * (canvas.height - size * 2) + size;
            let directionX = (Math.random() * 0.4) - 0.2; // Movimiento más lento
            let directionY = (Math.random() * 0.4) - 0.2; // Movimiento más lento
            let color = '#8C92AC';
            particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
        }
      }

      // Conectar partículas cercanas con una línea
      function connect() {
        let opacityValue = 1;
        for (let a = 0; a < particlesArray.length; a++) {
          for (let b = a; b < particlesArray.length; b++) {
            let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x)) +
              ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
            
            if (distance < (canvas.width / 8) * (canvas.height / 8)) { // Aumentar un poco el radio de conexión
              opacityValue = 1 - (distance / 25000); // Ajustar el desvanecimiento
              ctx.strokeStyle = `rgba(140, 146, 172, ${opacityValue})`;
              ctx.lineWidth = 1;
              ctx.beginPath();
              ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
              ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
              ctx.stroke();
            }
          }
        }
      }
      
      // Bucle de animación
      function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particlesArray.length; i++) {
          particlesArray[i].update();
        }
        connect();
        animationFrameId = requestAnimationFrame(animate);
      }

      init();
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
      <!-- Botón de llamada a la acción con estilo futurista -->
      <a href="#servicios" class="cta-button">
        {{ t('Explore Services') }}
        <span class="cta-button__glitch"></span>
        <span class="cta-button__label"></span>
      </a>
    </div>
  </section>
</template>

<style scoped>
/* Animaciones de entrada para el texto */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in-up-h1 {
  animation: fadeInUp 0.8s ease-out forwards;
}

.fade-in-up-p {
  animation: fadeInUp 0.8s ease-out 0.3s forwards;
  opacity: 0; /* Inicia invisible hasta que la animación comience */
}


/* Estilos para el texto con efecto de brillo */
.text-glow {
  text-shadow: 0 0 8px rgba(23, 237, 244, 0.6), 0 0 20px rgba(98, 21, 192, 0.5);
}

/* Estilos para el botón futurista de llamada a la acción */
.cta-button {
  --glow-color: rgb(23, 237, 244);
  --glow-spread-color: rgba(98, 21, 192, 0.781);
  --enhanced-glow-color: rgb(21, 131, 192);
  --btn-color: rgb(100, 100, 100);
  border: .25em solid var(--glow-color);
  padding: 1em 3em;
  color: var(--glow-color);
  font-size: 15px;
  font-weight: bold;
  background-color: transparent;
  border-radius: 1em;
  outline: none;
  box-shadow: 0 0 1em .25em var(--glow-color),
             0 0 4em 1em var(--glow-spread-color),
             inset 0 0 .75em .25em var(--glow-color);
  text-shadow: 0 0 .5em var(--glow-color);
  position: relative;
  transition: all 0.3s;
  text-decoration: none;
}

.cta-button::after {
  pointer-events: none;
  content: "";
  position: absolute;
  top: 120%;
  left: 0;
  height: 100%;
  width: 100%;
  background-color: var(--glow-spread-color);
  filter: blur(2em);
  opacity: .7;
  transform: perspective(1.5em) rotateX(35deg) scale(1, .6);
}

.cta-button:hover {
  color: var(--btn-color);
  background-color: var(--glow-color);
  box-shadow: 0 0 1em .25em var(--glow-color),
             0 0 4em 2em var(--glow-spread-color),
             inset 0 0 .75em .25em var(--glow-color);
}

.cta-button:active {
  box-shadow: 0 0 0.6em .25em var(--glow-color),
             0 0 2.5em 2em var(--glow-spread-color),
             inset 0 0 .5em .25em var(--glow-color);
}
</style>
