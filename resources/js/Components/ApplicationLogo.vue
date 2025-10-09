<template>
  <img 
    :class="`h-${height} w-auto`" 
    src="@/../../public/images/white_logo.png" 
  >
</template>

<script>
export default {
  props: {
    height: {
      type: String,
      default: '10' // Altura por defecto (Tailwind: h-10)
    },
  }
}
</script>

