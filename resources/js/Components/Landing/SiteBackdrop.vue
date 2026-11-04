<script setup>
/**
 * Fondo global del sitio público: cuadrícula fina y halos suaves.
 *
 * No dibuja líneas ni patrones diagonales a propósito: cada vista monta sus
 * propios iconos decorativos (por ejemplo la copa de /eventos) y el fondo debe
 * quedarse quieto detrás de ellos.
 *
 * Es una capa decorativa, fija y no interactiva. Debe montarse dentro de un
 * contenedor con `relative isolate` para que el `-z-10` la deje por debajo del
 * contenido pero por encima del color base.
 */
</script>

<template>
    <div class="dtw-site-backdrop pointer-events-none fixed inset-0 -z-10" aria-hidden="true"></div>
</template>
