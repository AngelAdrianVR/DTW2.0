<template>
  <section id="own-projects" class="py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
    <div class="max-w-7xl mx-auto">
      <h2 class="section-title text-3xl sm:text-4xl font-bold text-center mb-16">
        <span>{{ t('Our Own Projects') }}</span>
      </h2>

      <div v-for="project in projects" :key="project.id" class="project-container grid grid-cols-1 md:grid-cols-12 gap-8 items-center p-8 rounded-2xl">
        
        <!-- Columna de la Composición de Imágenes -->
        <div class="md:col-span-7 h-96 relative flex items-center justify-center group">
          <img 
            v-for="(image, index) in project.images" 
            :key="index"
            :src="image.url" 
            :alt="image.alt"
            :class="image.class"
            class="absolute object-cover rounded-lg shadow-2xl transition-all duration-500 ease-in-out group-hover:shadow-[0_0_35px_rgba(23,237,244,0.4)]"
          />
        </div>

        <!-- Columna de la Tarjeta de Detalles -->
        <div class="md:col-span-5 flex flex-col items-center md:items-start text-center md:text-left">
          <img :src="project.logo" :alt="t(project.titleKey) + ' Logo'" class="h-16 w-auto mb-6"/>
          <h3 class="text-4xl font-bold text-white mb-4" v-html="t(project.titleKey)"></h3>
          <p class="text-gray-300 mb-8">{{ t(project.descriptionKey) }}</p>
          <a :href="project.url" target="_blank" rel="noopener noreferrer" class="visit-site-button">
            {{ t('Visit Site') }}
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2 transition-transform duration-300" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </a>
        </div>

      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'InteractiveOwnProjects',
  props: {
    projects: {
      type: Array,
      required: true,
    },
  },
};
</script>

<style scoped>
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

.project-container {
  background: rgba(17, 24, 39, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
}

.image-style-1 {
  width: 60%;
  height: 60%;
  z-index: 10;
  transform: rotate(-5deg) translateX(-20%) translateY(10%);
}
.image-style-2 {
  width: 35%;
  height: 45%;
  z-index: 20;
  transform: rotate(10deg) translateX(80%) translateY(-30%);
}
.image-style-3 {
  width: 30%;
  height: 30%;
  z-index: 1;
  transform: rotate(5deg) translateX(30%) translateY(90%);
}

.group:hover .image-style-1 { transform: rotate(-8deg) translateX(-25%) translateY(5%) scale(1.05); }
.group:hover .image-style-2 { transform: rotate(12deg) translateX(85%) translateY(-35%) scale(1.05); }
.group:hover .image-style-3 { transform: rotate(2deg) translateX(35%) translateY(85%) scale(1.05); }

.visit-site-button {
  display: inline-flex;
  align-items: center;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  background-color: transparent;
  color: #17EDF4;
  border: 2px solid #17EDF4;
  font-weight: bold;
  text-decoration: none;
  transition: all 0.3s ease;
}

.visit-site-button:hover {
  background-color: #17EDF4;
  color: #111827;
  box-shadow: 0 0 20px rgba(23, 237, 244, 0.5);
}
.visit-site-button:hover svg {
  transform: translateX(5px);
}
</style>
