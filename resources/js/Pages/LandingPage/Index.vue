<script>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import InteractiveHero from '@/Components/MyComponents/InteractiveHero.vue';
import InteractiveServices from '@/Components/MyComponents/InteractiveServices.vue';
import InteractiveProjects from '@/Components/MyComponents/InteractiveProjects.vue';
import InteractiveOwnProjects from '@/Components/MyComponents/InteractiveOwnProjects.vue';
import InteractiveContact from '@/Components/MyComponents/InteractiveContact.vue';
import InteractiveClients from '@/Components/MyComponents/InteractiveClients.vue';
// import BenefitsSection from '@/Components/MyComponents/BenefitsSection.vue';
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
    // BenefitsSection,
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
      :title="t('Home') + ' DTW'" >
        <div class="page-container selection:bg-cyan-300/50">
            <!-- Blobs de luz ambiental (solo CSS, sin JS, sin impacto en rendimiento) -->
            <div class="ambient-lights" aria-hidden="true">
                <div class="ambient-blob a-blob-1"></div>
                <div class="ambient-blob a-blob-2"></div>
                <div class="ambient-blob a-blob-3"></div>
            </div>

            <!-- SECCIÓN INICIO (HERO) -->
            <InteractiveHero />

            <!-- Divisor gradiente sutil -->
            <div class="section-divider"></div>

            <!-- SECCIÓN SERVICIOS -->
            <InteractiveServices :services="translatedServices" />

            <!-- Divisor gradiente sutil -->
            <div class="section-divider"></div>

            <!-- SECCIÓN PROYECTOS -->
            <InteractiveProjects :projects="portfolio" />

            <div class="section-divider"></div>

            <!-- SECCIÓN PROYECTOS PROPIOS -->
            <InteractiveOwnProjects :projects="ownProjects" />

            <div class="section-divider"></div>

            <!-- SECCIÓN CLIENTES -->
            <InteractiveClients :clientLogos="clientLogos" />

            <div class="section-divider"></div>

            <!-- SECCIÓN CONTACTO -->
            <InteractiveContact />
        </div>
    </LandingLayout>
</template>

<style scoped>
/* =========== CONTENEDOR PRINCIPAL =========== */
.page-container {
  position: relative;
  overflow-x: hidden;
  background: #050510;
}

/* =========== LUCES AMBIENTALES FIJAS (CSS-only, GPU-accelerated) =========== */
.ambient-lights {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.ambient-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(120px);
  will-change: transform;
  animation: ambientFloat 20s ease-in-out infinite;
}

/* Violeta - arriba derecha */
.a-blob-1 {
  width: 550px;
  height: 550px;
  top: -15%;
  right: -10%;
  background: rgba(124, 58, 237, 0.09);
  animation-delay: 0s;
  animation-duration: 22s;
}

/* Azul - centro izquierda */
.a-blob-2 {
  width: 450px;
  height: 450px;
  top: 50%;
  left: -8%;
  background: rgba(59, 130, 246, 0.07);
  animation-delay: -7s;
  animation-duration: 18s;
}

/* Cyan/ámbar - abajo derecha */
.a-blob-3 {
  width: 400px;
  height: 400px;
  bottom: -10%;
  right: 15%;
  background: rgba(6, 182, 212, 0.06);
  animation-delay: -14s;
  animation-duration: 24s;
}

@keyframes ambientFloat {
  0%, 100% { transform: translate(0, 0) scale(1); }
  25%      { transform: translate(3%, -2%) scale(1.08); }
  50%      { transform: translate(-2%, 3%) scale(0.94); }
  75%      { transform: translate(-3%, -1%) scale(1.05); }
}

/* =========== DIVISORES ENTRE SECCIONES (línea gradiente sutil) =========== */
.section-divider {
  position: relative;
  z-index: 1;
  height: 1px;
  width: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(139, 92, 246, 0.15) 20%,
    rgba(59, 130, 246, 0.2) 50%,
    rgba(6, 182, 212, 0.12) 80%,
    transparent 100%
  );
  opacity: 0.6;
}

/* =========== ESTILOS DE INPUTS PRIMEVUE (global scope con :deep) =========== */
:deep(.p-inputtext),
:deep(.p-textarea) {
  background: #0d0d1a !important;
  border: 1px solid rgba(255, 255, 255, 0.08) !important;
  color: #e2e8f0 !important;
}

:deep(.p-inputtext:enabled:focus) {
  box-shadow: 0 0 0 1px rgba(168, 85, 247, 0.6) !important;
  border-color: rgba(168, 85, 247, 0.5) !important;
}

:deep(.p-float-label > label) {
  color: #64748b !important;
}
</style>
