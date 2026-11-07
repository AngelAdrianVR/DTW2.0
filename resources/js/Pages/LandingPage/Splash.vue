<script setup>
/**
 * Portada temática de la raíz `/`.
 *
 * Muestra el loader de la temporada y, al terminar, entra a la landing real
 * (`/inicio`). Los loaders viven en `Components/MyComponents` y comparten el
 * mismo contrato (`duration` + evento `finished`), así que para cambiar de
 * temática basta con enviar otra prop `theme` desde la ruta o cambiar el
 * valor por defecto de abajo.
 */
import { defineAsyncComponent, onBeforeUnmount } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DayOfTheDeadLoader from '@/Components/MyComponents/DayOfTheDeadLoader.vue';
import { playCempasuchilTransition } from '@/Components/Landing/cempasuchilTransition';

const THEMES = {
    'day-of-the-dead': {
        component: DayOfTheDeadLoader,
        title: 'Día de Muertos',
        status: 'Preparando la ofrenda',
        flowerTransition: true,
    },
    // Se carga sólo si se usa: la portada de Día de Muertos no arrastra el
    // componente de Halloween en su bundle inicial.
    halloween: {
        component: defineAsyncComponent(() => import('@/Components/MyComponents/HalloweenLoader.vue')),
        title: 'Halloween',
        status: 'Invocando la magia',
        flowerTransition: false,
    },
};

const props = defineProps({
    /** `day-of-the-dead` (por defecto) o `halloween`. */
    theme: { type: String, default: 'day-of-the-dead' },
});

const activeTheme = THEMES[props.theme] ?? THEMES['day-of-the-dead'];

const goHome = () => {
    const enter = () => router.visit('/inicio');

    if (activeTheme.flowerTransition) {
        playCempasuchilTransition(enter);
        return;
    }

    enter();
};

onBeforeUnmount(() => {
    // El fondo oscuro lo pone Blade sólo en esta vista; se retira al salir.
    document.body.style.backgroundColor = '';
});
</script>

<template>
    <Head :title="activeTheme.title">
        <meta name="robots" content="noindex, follow" />
    </Head>

    <component
        :is="activeTheme.component"
        :duration="4000"
        :status="activeTheme.status"
        @finished="goHome"
    />
</template>
