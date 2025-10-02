<script>
export default {
  props: {
    // Definimos una propiedad 'projects' para recibir los datos desde el componente padre
    projects: {
      type: Array,
      required: true,
    },
  },
}
</script>

<template>
  <!-- SECCIÓN DE PROYECTOS -->
  <section id="proyectos" class="py-20 px-4 bg-gray-900/50">
    <div class="container mx-auto">
      <h2 class="text-4xl font-bold text-center mb-16 section-title"><span>{{ t('Featured Projects') }}</span></h2>
      <div class="grid md:grid-cols-3 gap-8">
        <!-- Iteramos sobre el array 'projects' recibido como prop -->
        <div 
          v-for="project in projects" 
          :key="project.title" 
          class="project-card group"
        >
          <img :src="project.image" :alt="project.title" class="project-image">
          <div class="project-info">
            <div>
              <h3 class="text-2xl font-bold text-white">{{ project.title }}</h3>
              <p class="text-gray-300 mt-2">{{ project.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
/* Estilos para el título de la sección */
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

/* Contenedor principal de la tarjeta del proyecto */
.project-card {
  position: relative;
  border-radius: 1rem;
  overflow: hidden;
  background-color: #1A202C;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  border: 1px solid rgba(98, 21, 192, 0.2);
  transition: border-color 0.3s ease;
}

.project-card:hover {
  border-color: rgba(98, 21, 192, 0.7);
}

/* Imagen del proyecto */
.project-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.group:hover .project-image {
  transform: scale(1.1);
}

/* Capa de información superpuesta */
.project-info {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.5rem;
  background: linear-gradient(to top, rgba(10, 10, 25, 0.95) 0%, rgba(10, 10, 25, 0.8) 50%, transparent 100%);
  transform: translateY(100%);
  transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  display: flex;
  align-items: flex-end;
  height: 100%;
  opacity: 0;
}

.group:hover .project-info {
  transform: translateY(0);
  opacity: 1;
}
</style>
