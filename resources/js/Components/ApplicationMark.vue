<template>
  <!-- 
    Envolvemos las imágenes en un solo div.
    v-bind="$attrs" aplica cualquier atributo no-prop (como la clase "block h-9 w-auto"
    del componente padre) a este div.
  -->
  <div v-bind="$attrs">
    <img 
      class="dark:block hidden w-auto"
      :class="`h-${height}`" 
      src="@/../../public/images/white_logo.png" 
      alt="Logo Dark"
    >
    <img 
      class="dark:hidden block w-auto"
      :class="`h-${height}`" 
      src="@/../../public/images/black_logo.png" 
      alt="Logo Light"
    >
  </div>
</template>

<script>
export default {
  // Para evitar que los atributos se apliquen automáticamente al elemento raíz incorrecto
  inheritAttrs: false,
  props: {
    height: {
      type: String,
      default: '10' // Altura por defecto (Tailwind: h-10)
    },
  }
}
</script>

