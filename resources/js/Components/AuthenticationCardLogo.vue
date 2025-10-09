<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <Link :href="'/'">
        <img class="dark:block hidden"
            :class="`h-${height} w-auto`" 
            src="@/../../public/images/white_logo.png" 
        >
        <img class="dark:hidden block"
            :class="`h-${height} w-auto`" 
            src="@/../../public/images/black_logo.png" 
        >
    </Link>
</template>
