<script setup>
import { computed } from 'vue';
import { Link, Head } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: Number,
        required: true,
    },
});

const title = computed(() => {
    return {
        503: '503: Servicio No Disponible',
        500: '500: Error del Servidor',
        404: '404: Página No Encontrada',
        403: '403: Acceso Denegado',
    }[props.status] || 'Error';
});

const description = computed(() => {
    return {
        503: 'Disculpa, estamos realizando mantenimiento. Por favor, intenta de nuevo más tarde.',
        500: '¡Ups! Algo salió mal en nuestros servidores.',
        404: 'Parece que esta página se ha perdido en la nube.',
        403: 'Lo sentimos, no tienes permiso para acceder a esta página.',
    }[props.status] || 'Ha ocurrido un error inesperado.';
});
</script>

<template>
    <Head :title="title" />
    <div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-4">
        <div class="text-center">
            <!-- Animación de la Nube -->
            <div class="relative w-64 h-48 mx-auto mb-8">
                <svg viewBox="0 0 200 120" class="w-full h-full">
                    <!-- Nube -->
                    <path d="M60 100 C20 100 20 60 60 60 C60 40 100 40 100 60 C140 60 140 100 100 100 Z" fill="#E0E7FF" class="dark:fill-indigo-900/50" />
                    <path d="M100 110 C70 110 70 80 100 80 C100 60 140 60 140 80 C170 80 170 110 140 110 Z" fill="#E0E7FF" class="dark:fill-indigo-900/50" />
                    <!-- Ojos de la nube -->
                    <circle cx="85" cy="75" r="4" fill="#4F46E5" class="animate-bounce" style="animation-delay: 0.1s;"/>
                    <circle cx="115" cy="75" r="4" fill="#4F46E5" class="animate-bounce" />
                     <!-- Cable desconectado -->
                    <g class="animate-plug-swing">
                        <path d="M160 90 Q180 100 190 80" stroke="#9CA3AF" stroke-width="4" fill="none" stroke-linecap="round" />
                        <rect x="185" y="70" width="10" height="10" fill="#6B7281" rx="2"/>
                    </g>
                </svg>
                 <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-5xl font-extrabold text-indigo-600 dark:text-indigo-400">
                    {{ status }}
                </div>
            </div>

            <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ title }}</h1>
            <p class="text-lg md:text-xl text-gray-500 dark:text-gray-400 mb-8">{{ description }}</p>
            <Link :href="route('dashboard')">
                 <Button label="Regresar al Inicio" icon="pi pi-home" />
            </Link>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce {
  0%, 100% {
    transform: translateY(-25%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}
.animate-bounce {
    animation: bounce 1s infinite;
}

@keyframes plug-swing {
  0%, 100% {
    transform: rotate(-5deg);
  }
  50% {
    transform: rotate(5deg);
  }
}
.animate-plug-swing {
    transform-origin: 160px 90px;
    animation: plug-swing 2s ease-in-out infinite;
}
</style>
