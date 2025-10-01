<script>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

export default {

  // Registra los componentes de PrimeVue que se usarán
  components: {
    Card,
    Button,
    InputText,
    Textarea,
    LandingLayout,
  },

  // Datos del componente
  data() {
    return {
      // Lista de servicios para mostrar en tarjetas
      services: [
        { icon: 'pi pi-code', title: 'Desarrollo Web a Medida', description: 'Soluciones web robustas y escalables, desde landing pages hasta aplicaciones complejas.' },
        { icon: 'pi pi-mobile', title: 'Aplicaciones Móviles', description: 'Apps nativas e híbridas para iOS y Android, centradas en la experiencia de usuario.' },
        { icon: 'pi pi-cloud', title: 'Soluciones en la Nube', description: 'Infraestructura cloud, microservicios y despliegue continuo para máxima eficiencia.' },
      ],
      // Lista de proyectos de ejemplo
      projects: [
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Proyecto+Alfa', title: 'Plataforma de E-learning', description: 'Sistema de gestión de aprendizaje con gamificación.' },
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Proyecto+Beta', title: 'Dashboard de Analítica', description: 'Visualización de datos en tiempo real para inteligencia de negocios.' },
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Proyecto+Gamma', title: 'App de Fitness Social', description: 'Red social para deportistas con seguimiento de actividades.' },
      ],
      // Modelo de datos para el formulario de contacto
      contactForm: {
        name: '',
        email: '',
        message: '',
      }
    };
  }
}
</script>

<template>
    <LandingLayout title="Inicio DTW" welcomeMessage="Bienvenido a DTW - Innovación Digital Sin Límites">
        <div class="page-container selection:bg-cyan-300/50">
            <!-- SECCIÓN INICIO (HERO) -->
            <section id="inicio" class="hero-section min-h-screen flex items-center justify-center text-center px-4">
            <div class="hero-content">
                <h1 class="text-5xl md:text-7xl font-bold mb-4 text-glow">Innovación Digital Sin Límites</h1>
                <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-8">
                Transformamos ideas en soluciones tecnológicas de vanguardia. Impulsamos tu visión hacia el futuro digital.
                </p>
                <a href="#servicios">
                    <Button label="Explorar Servicios" icon="pi pi-arrow-down" severity="info" raised />
                </a>
            </div>
            <div class="grid-background"></div>
            </section>

            <!-- SECCIÓN SERVICIOS -->
            <section id="servicios" class="py-20 px-4">
            <div class="container mx-auto">
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>Nuestros Servicios</span></h2>
                <div class="grid md:grid-cols-3 gap-8">
                <Card v-for="service in services" :key="service.title" class="service-card">
                    <template #header>
                    <div class="p-4 text-center">
                        <i :class="service.icon" style="font-size: 3rem; color: #17EDF4;"></i>
                    </div>
                    </template>
                    <template #title>
                    <h3 class="text-xl font-semibold text-center">{{ service.title }}</h3>
                    </template>
                    <template #content>
                    <p class="text-gray-400 text-center">{{ service.description }}</p>
                    </template>
                </Card>
                </div>
            </div>
            </section>

            <!-- SECCIÓN PROYECTOS -->
            <section id="proyectos" class="py-20 px-4 bg-gray-900/50">
            <div class="container mx-auto">
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>Proyectos Destacados</span></h2>
                <div class="grid md:grid-cols-3 gap-8">
                <Card v-for="project in projects" :key="project.title" class="project-card">
                    <template #header>
                    <img :src="project.image" :alt="project.title" class="rounded-t-lg">
                    </template>
                    <template #title>
                    <h3 class="text-xl font-semibold">{{ project.title }}</h3>
                    </template>
                    <template #content>
                    <p class="text-gray-400">{{ project.description }}</p>
                    </template>
                </Card>
                </div>
            </div>
            </section>

            <!-- SECCIÓN CONTACTO -->
            <section id="contacto" class="py-20 px-4">
            <div class="container mx-auto max-w-2xl">
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>Hablemos</span></h2>
                <div class="contact-form p-8">
                <form @submit.prevent>
                    <div class="flex flex-col gap-6">
                    <span class="p-float-label">
                        <InputText id="name" v-model="contactForm.name" class="w-full" />
                        <label for="name">Nombre</label>
                    </span>
                    <span class="p-float-label">
                        <InputText id="email" v-model="contactForm.email" type="email" class="w-full" />
                        <label for="email">Email</label>
                    </span>
                    <span class="p-float-label">
                        <Textarea id="message" v-model="contactForm.message" rows="5" class="w-full" />
                        <label for="message">Mensaje</label>
                    </span>
                    <Button type="submit" label="Enviar Mensaje" severity="info" raised class="w-full" />
                    </div>
                </form>
                </div>
            </div>
            </section>
        </div>
    </LandingLayout>
</template>

<style scoped>
/* Estilos generales y de fondo */
.page-container {
  position: relative;
  overflow-x: hidden;
}

.grid-background {
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  width: 100%;
  height: 100%;
  background-image:
    linear-gradient(rgba(17, 24, 39, 0) 0%, #111827 90%),
    radial-gradient(ellipse at center, rgba(30, 30, 50, 0.6) 0%, transparent 70%),
    linear-gradient(to right, rgba(29, 78, 216, 0.1) 1px, transparent 1px),
    linear-gradient(to bottom, rgba(29, 78, 216, 0.1) 1px, transparent 1px);
  background-size: 100% 100%, 100% 100%, 40px 40px, 40px 40px;
  z-index: 0;
}

.hero-section {
  position: relative;
}

.hero-content {
  position: relative;
  z-index: 1;
}

/* Efecto de brillo en texto */
.text-glow {
  text-shadow: 0 0 8px rgba(23, 237, 244, 0.6), 0 0 20px rgba(98, 21, 192, 0.5);
}

/* Títulos de sección */
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


/* Tarjetas de Servicio */
.service-card {
  background-color: rgba(23, 25, 48, 0.5) !important;
  border: 1px solid rgba(23, 237, 244, 0.2) !important;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease-in-out;
  border-radius: 1rem !important;
}

.service-card:hover {
  transform: translateY(-10px);
  border-color: rgba(23, 237, 244, 0.7) !important;
  box-shadow: 0 0 25px rgba(23, 237, 244, 0.3);
}

/* Tarjetas de Proyecto */
.project-card {
  background-color: rgba(23, 25, 48, 0.8) !important;
  border: 1px solid rgba(98, 21, 192, 0.2) !important;
  transition: all 0.3s ease-in-out;
  border-radius: 1rem !important;
  overflow: hidden;
}

.project-card:hover {
    border-color: rgba(98, 21, 192, 0.7) !important;
    box-shadow: 0 0 25px rgba(98, 21, 192, 0.3);
}
.project-card img {
    transition: transform 0.3s ease-in-out;
}
.project-card:hover img {
    transform: scale(1.05);
}

/* Formulario de Contacto */
.contact-form {
  background-color: rgba(23, 25, 48, 0.5) !important;
  border: 1px solid rgba(23, 237, 244, 0.2) !important;
  backdrop-filter: blur(10px);
  border-radius: 1rem !important;
}

/* Estilos para los inputs de PrimeVue */
:deep(.p-inputtext), :deep(.p-textarea) {
  background: rgba(0,0,0,0.3) !important;
  border: 1px solid rgba(255,255,255,0.2) !important;
  color: #fff !important;
}

:deep(.p-inputtext:enabled:focus) {
  box-shadow: 0 0 0 1px rgba(23, 237, 244, 0.8) !important;
  border-color: rgba(23, 237, 244, 0.8) !important;
}

:deep(.p-float-label > label) {
    color: #9ca3af !important;
}

</style>
