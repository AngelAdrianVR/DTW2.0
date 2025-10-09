<script setup>
import LandingLayout from '@/Layouts/LandingLayout.vue';
import { Head } from '@inertiajs/vue3';

// Los props 'title' y 'content' son pasados desde LegalController
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    content: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head :title="title" />
    <LandingLayout>
        <div class="py-12 sm:py-16">
            <div class="mx-auto max-w-4xl px-6 lg:px-8 ">
                <div class="shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl md:col-span-2">
                    <div class="px-4 py-6 sm:p-8">
                        <!-- Título de la página -->
                        <h1 class="text-3xl font-bold tracking-tight text-gray-200 mb-6 pb-4 border-b border-gray-200">
                            {{ title }}
                        </h1>
                        
                        <!-- Contenido del Markdown convertido a HTML -->
                        <!-- Usamos v-html para renderizar el HTML que viene del controlador -->
                        <!-- La clase 'prose' de TailwindCSS le da un estilo legible por defecto -->
                        <div 
                            class="prose prose-lg max-w-none prose-headings:font-semibold prose-a:text-indigo-600 hover:prose-a:text-indigo-500 text-gray-300"
                            v-html="content"
                        >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LandingLayout>
</template>

<style>
/* Estilos adicionales para la clase 'prose' si es necesario */
.prose {
    line-height: 1.75;
}
.prose h2 {
    font-size: 1.5em;
    margin-top: 2em;
    margin-bottom: 1em;
}
.prose p {
    margin-bottom: 1.25em;
}
</style>
