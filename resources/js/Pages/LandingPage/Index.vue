<script>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import InteractiveHero from '@/Components/MyComponents/InteractiveHero.vue';
import InteractiveServices from '@/Components/MyComponents/InteractiveServices.vue';
import InteractiveProjects from '@/Components/MyComponents/InteractiveProjects.vue';
import InteractiveOwnProjects from '@/Components/MyComponents/InteractiveOwnProjects.vue';
import InteractiveContact from '@/Components/MyComponents/InteractiveContact.vue';
import InteractiveClients from '@/Components/MyComponents/InteractiveClients.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

//images
import ioT from '@/../../public/images/IoT.webp';
import ioT2 from '@/../../public/images/IOT2.png';
import mov1 from '@/../../public/images/sistemas.webp';
import mov2 from '@/../../public/images/home_IoT.webp';
import web1 from '@/../../public/images/web1.png';
import web2 from '@/../../public/images/web2.png';
import cloud1 from '@/../../public/images/cloud1.png';
import cloud2 from '@/../../public/images/cloud2.png';

export default {
  // Registra los componentes
  components: {
    Card,
    Button,
    InputText,
    Textarea,
    LandingLayout,
    InteractiveHero,
    InteractiveClients,
    InteractiveContact,
    InteractiveProjects,
    InteractiveServices,
    InteractiveOwnProjects,
  },

  props: {
      portfolio: Array,
      ownProjects: Array,
      clientLogos: Array, // MODIFICACIÓN: Recibe los logos de los clientes
      advertising: Array,
    },
  // Los datos contienen las CLAVES de traducción para contenido estático
  data() {
    return {
      services: [
        {
          id: 'web',
          icon: 'pi pi-code',
          title: 'Custom Web Development',
          description: 'Robust and scalable web solutions, from landing pages to complex applications.',
          detailedDescription: 'From corporate websites to complex e-commerce platforms and custom web applications, we create digital experiences that captivate and convert. We use modern technologies like Vue.js, React, and Node.js to build fast, secure, and scalable solutions tailored to your needs.',
          images: [
            web1,
            web2
          ]
        },
        {
          id: 'mobile',
          icon: 'pi pi-mobile',
          title: 'Mobile Applications',
          description: 'Native and hybrid apps for iOS and Android, focused on user experience.',
          detailedDescription: 'We design and develop mobile applications for iOS and Android that stand out for their intuitive interface and flawless performance. Whether native or hybrid, we focus on creating apps that users love and that drive business growth.',
          images: [
            mov1,
            mov2
          ]
        },
        {
          id: 'cloud',
          icon: 'pi pi-cloud',
          title: 'Cloud Solutions',
          description: 'Cloud infrastructure, microservices, and continuous deployment for maximum efficiency.',
          detailedDescription: 'We help you migrate, manage, and optimize your infrastructure in the cloud. We design scalable architectures with microservices, implement CI/CD pipelines for agile deployments, and ensure the security and availability of your applications on platforms like AWS, Google Cloud, and Azure.',
          images: [
            cloud1,
            cloud2
          ]
        },
        {
            id: 'iot',
            icon: 'pi pi-globe',
            title: 'IoT & Automations',
            description: 'Connecting the physical world to the digital, creating intelligent systems.',
            detailedDescription: 'We specialize in developing IoT solutions that connect devices, collect data, and enable intelligent automation. From smart homes to industrial applications, we transform processes and create new opportunities through connectivity and real-time data analysis.',
            images: [
                ioT,
                ioT2
            ]
        }
      ],
      contactForm: {
        name: '',
        email: '',
        message: '',
      }
    };
  },

  computed: {
    translatedServices() {
      return this.services.map(service => ({
        ...service,
        title: this.t(service.title),
        description: this.t(service.description),
        detailedDescription: this.t(service.detailedDescription)
      }));
    },
  }
}
</script>

<template>
    <LandingLayout 
      :title="t('Home') + ' DTW'" 
      :welcomeMessage="t('Welcome to DTW - Digital Innovation')"
    >
        <div class="page-container selection:bg-cyan-300/50">
            <!-- SECCIÓN INICIO (HERO) -->
            <InteractiveHero />

            <!-- SECCIÓN SERVICIOS -->
            <InteractiveServices :services="translatedServices" />

            <!-- SECCIÓN PROYECTOS -->
            <InteractiveProjects :projects="portfolio" />

            <!-- SECCIÓN PROYECTOS PROPIOS -->
            <!-- Pasamos la prop 'ownProjects' directamente desde el controlador -->
            <InteractiveOwnProjects :projects="ownProjects" />

            <!-- SECCIÓN CLIENTES -->
            <!-- MODIFICACIÓN: Pasamos los logos al componente -->
            <InteractiveClients :clientLogos="clientLogos" />

            <!-- SECCIÓN CONTACTO -->
            <InteractiveContact />

            
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
