<script>
import { ref, onMounted, onUnmounted } from 'vue';

export default {
  setup() {
    const scrollProgress = ref(0);

    const handleScroll = () => {
      const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
      const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
      const progress = (scrollTop / scrollHeight) * 100;
      scrollProgress.value = progress;
    };

    onMounted(() => {
      window.addEventListener('scroll', handleScroll);
    });

    onUnmounted(() => {
      window.removeEventListener('scroll', handleScroll);
    });

    return {
      scrollProgress,
    };
  },
};
</script>

<template>
  <div class="scroll-indicator-container">
    <div class="progress-bar-track">
      <div class="progress-bar-fill" :style="{ height: scrollProgress + '%' }"></div>
    </div>
    <div class="rocket-icon" :style="{ top: `calc(${scrollProgress}% - 16px)` }">
      <!-- SVG de un cohete -->
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#64FFDA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send">
        <line x1="22" y1="2" x2="11" y2="13"></line>
        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
      </svg>
    </div>
  </div>
</template>

<style scoped>
.scroll-indicator-container {
  position: fixed;
  top: 50%;
  left: 20px; /* Posición a la izquierda */
  transform: translateY(-50%);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.progress-bar-track {
  width: 4px;
  height: 200px; /* Altura de la barra */
  background-color: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  position: relative;
}

.progress-bar-fill {
  width: 100%;
  background: linear-gradient(to top, #64FFDA, #17EDF4);
  border-radius: 2px;
  position: absolute;
  bottom: 0;
  left: 0;
}

.rocket-icon {
  position: absolute;
  left: 50%;
  transform: translateX(-50%) rotate(-45deg); /* Cohete apuntando hacia arriba y a la derecha */
  transition: top 0.1s linear;
}

/* Ocultar en pantallas pequeñas para no estorbar */
@media (max-width: 768px) {
  .scroll-indicator-container {
    display: none;
  }
}
</style>
