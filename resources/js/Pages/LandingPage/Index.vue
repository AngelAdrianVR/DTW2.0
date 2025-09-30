<script>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Tag from 'primevue/tag';

export default {
    components: {
        LandingLayout,
        Button,
        Card,
        InputText,
        Textarea,
        Tag
    },
    // Usando Options API
    data() {
        return {
            // Datos para la sección de servicios
            services: [
                {
                    icon: 'pi pi-chip',
                    title: 'Soluciones IoT',
                    description: 'Conectamos tus dispositivos y máquinas para transmitir información clave en tiempo real, optimizando procesos y permitiendo un mantenimiento predictivo.'
                },
                {
                    icon: 'pi pi-palette',
                    title: 'Branding y Diseño',
                    description: 'Creamos identidades de marca memorables. Desde el logo hasta la estrategia digital, damos vida a tu visión con un diseño que impacta y comunica.'
                },
                {
                    icon: 'pi pi-sitemap',
                    title: 'Sistemas Empresariales',
                    description: 'Desarrollamos software a la medida (ERP, CRM, PMS) que centraliza operaciones, automatiza tareas y transforma tus datos en decisiones inteligentes.'
                }
            ],
            // Datos para el portafolio
            portfolio: [
                { image: 'https://placehold.co/600x400/1e293b/94a3b8?text=Proyecto+Alfa', title: 'Dashboard Industrial IoT', category: 'IoT', tags: ['Vue.js', 'Laravel', 'MQTT'] },
                { image: 'https://placehold.co/600x400/1e293b/94a3b8?text=Proyecto+Beta', title: 'Rediseño de Marca Tech', category: 'Branding', tags: ['Figma', 'Illustrator', 'SEO'] },
                { image: 'https://placehold.co/600x400/1e293b/94a3b8?text=Proyecto+Gamma', title: 'Sistema de Gestión de Proyectos', category: 'Sistemas', tags: ['React', 'Node.js', 'MongoDB'] },
                { image: 'https://placehold.co/600x400/1e293b/94a3b8?text=Proyecto+Delta', title: 'Plataforma E-commerce', category: 'Sistemas', tags: ['Vue.js', 'Inertia.js', 'MySQL'] },
            ],
            // Logos de clientes
            clients: [
                { name: 'Emblems3D USA', logo: 'https://placehold.co/200x100/e2e8f0/334155?text=Emblems3D' },
                { name: 'Nala Jewelry', logo: 'https://placehold.co/200x100/e2e8f0/334155?text=Nala+Jewelry' },
                { name: 'Ingeniería Zafiro', logo: 'https://placehold.co/200x100/e2e8f0/334155?text=Ingeniería+Zafiro' },
                { name: 'ADTI', logo: 'https://placehold.co/200x100/e2e8f0/334155?text=ADTI' },
            ],
             // Proyectos propios
            ownProjects: [
                { image: 'https://placehold.co/600x400/0f172a/64748b?text=EZY+Ventas', title: 'EZY Ventas', description: 'Un sistema de punto de venta en la nube, intuitivo y potente para pequeños y medianos negocios.', link: '#' },
                { image: 'https://placehold.co/600x400/0f172a/64748b?text=Finanzas+App', title: 'FINANZAS', description: 'Aplicación para el control de finanzas personales y empresariales, simple y segura.', link: '#' },
            ],
            // Modelo para el formulario de contacto
            contactForm: {
                name: '',
                email: '',
                message: ''
            }
        };
    },
    mounted() {
        // Lógica para animaciones al hacer scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                }
            });
        }, { threshold: 0.1 });

        this.$el.querySelectorAll('.fade-target').forEach(el => {
            observer.observe(el);
        });
    },
    methods: {
        handleContactSubmit() {
            // Aquí iría la lógica para enviar el formulario con Inertia
            console.log('Formulario enviado:', this.contactForm);
            // this.$inertia.post('/contacto', this.contactForm);
        }
    }
};
</script>

<template>
    <LandingLayout :welcomeMessage="'Bienvenido a DTW'" >
        <!-- SECCIÓN HERO -->
        <section id="inicio" class="relative text-center h-[80vh] flex flex-col justify-center items-center overflow-hidden">
            <div class="absolute inset-0 bg-gray-900 z-10 opacity-60"></div>
            <video autoplay loop muted playsinline class="absolute z-0 w-auto min-w-full min-h-full max-w-none">
                 <source src="https://cdn.pixabay.com/video/2022/05/23/116290-715392822_large.mp4" type="video/mp4">
                 Your browser does not support the video tag.
            </video>
            <div class="container mx-auto px-4 z-20">
                <h1 class="text-4xl md:text-6xl font-extrabold text-white leading-tight mb-4 animate-fade-in-down">
                    Transformamos <span class="text-cyan-400">Ideas</span> en Soluciones Digitales
                </h1>
                <p class="text-lg md:text-xl text-gray-300 max-w-3xl mx-auto mb-8 animate-fade-in-up">
                    Expertos en IoT, Branding y Sistemas Empresariales para impulsar el crecimiento de tu negocio.
                </p>
                <Button label="Hablemos de tu Proyecto" icon="pi pi-arrow-right" iconPos="right" size="large" severity="info" />
            </div>
        </section>

        <!-- SECCIÓN DE SERVICIOS -->
        <section id="servicios" class="py-20 bg-gray-900/50">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-2 fade-target">Nuestros Servicios</h2>
                <p class="text-gray-400 mb-12 fade-target">Soluciones integrales para la era digital.</p>
                <div class="grid md:grid-cols-3 gap-8">
                    <div v-for="service in services" :key="service.title" class="fade-target">
                        <Card class="bg-gray-800/50 border border-white/10 text-gray-200 h-full">
                            <template #header>
                                <div class="p-6 text-cyan-400">
                                    <i :class="service.icon" style="font-size: 2.5rem"></i>
                                </div>
                            </template>
                            <template #title><h3 class="text-white">{{ service.title }}</h3></template>
                            <template #content><p>{{ service.description }}</p></template>
                        </Card>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN PORTAFOLIO -->
        <section id="proyectos" class="py-20">
            <div class="container mx-auto px-4 text-center">
                 <h2 class="text-3xl font-bold mb-2 fade-target">Portafolio de Proyectos</h2>
                 <p class="text-gray-400 mb-12 fade-target">Casos de éxito que hablan por nosotros.</p>
                 <div class="grid md:grid-cols-2 gap-8">
                     <div v-for="(item, index) in portfolio" :key="index" class="fade-target">
                        <Card class="bg-gray-800/50 border border-white/10 text-left overflow-hidden group">
                            <template #header>
                                <img :alt="item.title" :src="item.image" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500" />
                            </template>
                            <template #title><h3 class="text-white">{{ item.title }}</h3></template>
                            <template #subtitle><p class="text-cyan-400">{{ item.category }}</p></template>
                            <template #content>
                                <div class="flex flex-wrap gap-2">
                                     <Tag v-for="tag in item.tags" :key="tag" :value="tag" severity="info"></Tag>
                                </div>
                            </template>
                        </Card>
                     </div>
                 </div>
            </div>
        </section>

         <!-- SECCIÓN CLIENTES -->
        <section id="clientes" class="py-20 bg-gray-900/50">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-12 fade-target">Confían en Nosotros</h2>
                <div class="flex flex-wrap justify-center items-center gap-8 md:gap-12 opacity-60">
                    <div v-for="client in clients" :key="client.name" class="fade-target">
                        <img :src="client.logo" :alt="client.name" class="h-10 transition-opacity hover:opacity-100" />
                    </div>
                </div>
            </div>
        </section>

        <!-- SECCIÓN PROYECTOS PROPIOS -->
        <section id="proyectos-propios" class="py-20">
             <div class="container mx-auto px-4">
                 <h2 class="text-3xl font-bold mb-12 text-center fade-target">Nuestras Iniciativas</h2>
                 <div class="max-w-4xl mx-auto space-y-12">
                     <div v-for="(project, index) in ownProjects" :key="project.title" 
                          class="fade-target flex flex-col md:flex-row items-center gap-8"
                          :class="{'md:flex-row-reverse': index % 2 !== 0}">
                         <img :src="project.image" :alt="project.title" class="w-full md:w-1/2 rounded-lg shadow-2xl">
                         <div class="md:w-1/2">
                             <h3 class="text-2xl font-bold text-white mb-3">{{ project.title }}</h3>
                             <p class="text-gray-400 mb-6">{{ project.description }}</p>
                             <Button label="Ver más" icon="pi pi-external-link" severity="info" outlined />
                         </div>
                     </div>
                 </div>
             </div>
        </section>

        <!-- SECCIÓN CONTACTO -->
        <section id="contacto" class="py-20 bg-gray-900/50">
            <div class="container mx-auto px-4">
                <div class="max-w-2xl mx-auto text-center">
                    <h2 class="text-3xl font-bold mb-2 fade-target">¿Tienes una idea?</h2>
                    <p class="text-gray-400 mb-8 fade-target">Contáctanos. Hagamos que suceda.</p>
                    <form @submit.prevent="handleContactSubmit" class="space-y-6 fade-target">
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
                        <Button type="submit" label="Enviar Mensaje" icon="pi pi-send" severity="info" size="large" class="w-full" />
                    </form>
                </div>
            </div>
        </section>

    </LandingLayout>
</template>

<style scoped>
/* Estilos para PrimeVue Inputs en tema oscuro */
:deep(.p-inputtext), :deep(.p-textarea) {
    background: rgba(31, 41, 55, 0.5) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #e5e7eb !important;
}

:deep(.p-float-label label) {
    color: #9ca3af !important;
}

/* Animaciones de entrada */
.fade-target {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease-out, transform 0.6s ease-out;
}

.fade-in-up {
    opacity: 1;
    transform: translateY(0);
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-down {
    animation: fadeInDown 0.8s ease-out forwards;
}

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
.animate-fade-in-up {
    animation: fadeInUp 0.8s 0.2s ease-out forwards;
    opacity: 0;
}
</style>
