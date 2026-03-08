<script setup>
import SectionTitle from './SectionTitle.vue';
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <!-- 
                 Cambios realizados:
                 1. dark:bg-zinc-900: Para igualar el tono del contenedor padre.
                 2. shadow-none: Para eliminar el efecto de "cuadro" elevado.
                 3. sm:rounded-xl: Para modernizar el borde.
            -->
            <div class="px-4 py-5 sm:p-6 bg-white dark:bg-zinc-900 shadow-none sm:rounded-xl">
                <slot name="content" />
            </div>
        </div>
    </div>
</template>