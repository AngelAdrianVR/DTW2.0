<script setup>
/**
 * Fondo global del sitio público: cuadrícula, líneas orgánicas y halos.
 *
 * Es una capa decorativa, fija y no interactiva. Debe montarse dentro de un
 * contenedor con `relative isolate` para que el `-z-10` la deje por debajo del
 * contenido pero por encima del color base.
 */
</script>

<template>
    <div class="dtw-site-backdrop pointer-events-none fixed inset-0 -z-10" aria-hidden="true"></div>
</template>
