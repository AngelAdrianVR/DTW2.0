<script setup>
import { Head, Link } from '@inertiajs/vue3';

</script>

<template>
    <Head title="Sesión Expirada - 419" />

    <div class="min-h-screen flex items-center justify-center relative overflow-hidden px-4">
        
        <!-- Main Card -->
        <div class="relative z-10 w-full max-w-md">
            <!-- Decorative top element -->
            <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-24 h-24 bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-2xl shadow-xl flex items-center justify-center rotate-45 z-20 overflow-hidden group mt-5">
                <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/10 to-blue-500/10 dark:from-cyan-400/10 dark:to-blue-600/10"></div>
                <i class="pi pi-lock -rotate-45 text-3xl text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform duration-300"></i>
            </div>

            <!-- Content Card -->
            <div class="p-8 sm:p-12 pt-16 rounded-2xl shadow-xl dark:shadow-black/20 text-center relative overflow-hidden">
                
                <!-- Subtle inner glow for depth -->
                <div class="absolute inset-0 bg-gradient-to-b from-white/40 to-transparent dark:from-white/5 dark:to-transparent pointer-events-none"></div>

                <div class="relative z-10">
                    <h1 class="text-7xl sm:text-8xl font-black tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-cyan-600 to-blue-600 dark:from-cyan-400 dark:to-blue-500 mb-2 drop-shadow-sm">
                        419
                    </h1>
                    
                    <h2 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-zinc-100 mb-4 mt-10 tracking-tight">
                        Conexión Expirada
                    </h2>
                    
                    <div class="h-px w-16 bg-gradient-to-r from-transparent via-gray-300 dark:via-zinc-600 to-transparent mx-auto mb-6"></div>
                    
                    <p class="text-gray-600 dark:text-zinc-400 mb-8 leading-relaxed text-sm sm:text-base">
                        Por motivos de seguridad, tu sesión ha sido desconectada debido a un periodo prolongado de inactividad.
                    </p>

                    <!-- Acciones -->
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">                        
                        <Link 
                            href="/login" 
                            class="w-full sm:w-auto px-6 py-3 bg-white dark:bg-zinc-800/50 text-gray-700 dark:text-zinc-300 font-semibold rounded-xl border border-gray-200 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 flex items-center justify-center gap-2"
                        >
                            <i class="pi pi-home text-sm"></i>
                            Ir al Inicio
                        </Link>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-2 opacity-50">
                        <span class="text-[12px] font-mono font-bold text-gray-800 dark:text-zinc-500 tracking-widest uppercase">Estatus del sistema: Página expirada</span>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</template>
