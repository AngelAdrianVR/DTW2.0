<template>
    <!-- Main container with a dark background -->
    <div class="min-h-screen flex flex-col sm:justify-center items-center p-4 sm:pt-0 bg-gray-900">
        <!-- Card with glowing border -->
        <div class="relative w-full sm:max-w-md">
             <!-- The glowing effect -->
            <div class="absolute -inset-1 bg-gradient-to-r from-fuchsia-600 to-cyan-500 rounded-2xl blur-lg opacity-75"></div>

            <!-- The card itself -->
            <div class="relative w-full px-6 sm:px-8 py-10 bg-black shadow-2xl overflow-hidden sm:rounded-2xl border border-gray-700">
                <div class="flex justify-center mb-6">
                     <slot name="logo" />
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>

