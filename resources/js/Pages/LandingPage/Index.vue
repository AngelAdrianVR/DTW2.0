<script>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import InteractiveHero from '@/Components/MyComponents/InteractiveHero.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

export default {
  // Registra los componentes
  components: {
    Card,
    Button,
    InputText,
    Textarea,
    LandingLayout,
    InteractiveHero,
  },

  // ELIMINAMOS EL MÉTODO `setup()`
  // La función `t()` ahora se obtiene de las propiedades globales de la app,
  // configurada en `resources/js/app.js`.

  // Los datos contienen las CLAVES de traducción
  data() {
    return {
      services: [
        { icon: 'pi pi-code', title: 'Custom Web Development', description: 'Robust and scalable web solutions, from landing pages to complex applications.' },
        { icon: 'pi pi-mobile', title: 'Mobile Applications', description: 'Native and hybrid apps for iOS and Android, focused on user experience.' },
        { icon: 'pi pi-cloud', title: 'Cloud Solutions', description: 'Cloud infrastructure, microservices, and continuous deployment for maximum efficiency.' },
      ],
      projects: [
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Project+Alpha', title: 'E-learning Platform', description: 'Learning management system with gamification.' },
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Project+Beta', title: 'Analytics Dashboard', description: 'Real-time data visualization for business intelligence.' },
        { image: 'https://placehold.co/600x400/1A202C/7B8A9E?text=Project+Gamma', title: 'Social Fitness App', description: 'Social network for athletes with activity tracking.' },
      ],
      contactForm: {
        name: '',
        email: '',
        message: '',
      }
    };
  },

  // Las propiedades computadas ahora usan `this.t()`, que es el helper global.
  computed: {
    translatedServices() {
      return this.services.map(service => ({
        ...service,
        title: this.t(service.title),
        description: this.t(service.description)
      }));
    },
    translatedProjects() {
        return this.projects.map(project => ({
        ...project,
        title: this.t(project.title),
        description: this.t(project.description)
      }));
    }
  }
}
</script>

<template>
    <!-- El template usa `t()` directamente, que ahora es una función global reconocida. -->
    <LandingLayout 
      :title="t('Home') + ' DTW'" 
      :welcomeMessage="t('Welcome to DTW - Digital Innovation')"
    >
        <div class="page-container selection:bg-cyan-300/50">
            <!-- SECCIÓN INICIO (HERO) -->
            <InteractiveHero />

            <!-- SECCIÓN SERVICIOS -->
            <section id="servicios" class="py-20 px-4">
            <div class="container mx-auto">
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>{{ t('Our Services') }}</span></h2>
                <div class="grid md:grid-cols-3 gap-8">
                <Card v-for="service in translatedServices" :key="service.title" class="service-card">
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
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>{{ t('Featured Projects') }}</span></h2>
                <div class="grid md:grid-cols-3 gap-8">
                <Card v-for="project in translatedProjects" :key="project.title" class="project-card">
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
                <h2 class="text-4xl font-bold text-center mb-12 section-title"><span>{{ t("Let's Talk") }}</span></h2>
                <div class="contact-form p-8">
                <form @submit.prevent>
                    <div class="flex flex-col gap-6">
                    <span class="p-float-label">
                        <InputText id="name" v-model="contactForm.name" class="w-full" />
                        <label for="name">{{ t('Name') }}</label>
                    </span>
                    <span class="p-float-label">
                        <InputText id="email" v-model="contactForm.email" type="email" class="w-full" />
                        <label for="email">{{ t('Email') }}</label>
                    </span>
                    <span class="p-float-label">
                        <Textarea id="message" v-model="contactForm.message" rows="5" class="w-full" />
                        <label for="message">{{ t('Message') }}</label>
                    </span>
                    <Button type="submit" :label="t('Send Message')" severity="info" raised class="w-full" />
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

