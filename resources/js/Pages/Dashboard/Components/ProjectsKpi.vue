<script setup>
import Card from 'primevue/card';

defineProps({
    data: Object
});
</script>

<template>
    <Card class="shadow-sm rounded-2xl overflow-hidden h-full flex flex-col justify-between bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800">
        <template #content>
            <div class="flex flex-col h-full">
                <!-- Cabecera y Total -->
                <div class="text-center pb-4 border-b border-gray-100 dark:border-zinc-800/50">
                    <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-2xl bg-purple-50 dark:bg-purple-900/20 mb-3 border border-purple-100 dark:border-purple-800/30">
                        <i class="pi pi-briefcase text-2xl text-purple-600 dark:text-purple-400"></i>
                    </div>
                    <p class="text-4xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ data.total }}</p>
                    <p class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-wide">Proyectos Registrados</p>
                </div>

                <!-- Desglose de Estado -->
                <div class="grid grid-cols-3 gap-2 mt-4 pt-1">
                    <!-- En Proceso -->
                    <div class="flex flex-col items-center justify-center p-2 rounded-xl bg-blue-50 dark:bg-blue-900/10 border border-blue-100/50 dark:border-blue-800/20" title="Proyectos En Proceso">
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ data.en_proceso || 0 }}</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></div>
                            <span class="text-[9px] font-semibold text-blue-600/80 dark:text-blue-400/80 uppercase">Curso</span>
                        </div>
                    </div>
                    
                    <!-- Pendientes -->
                    <div class="flex flex-col items-center justify-center p-2 rounded-xl bg-amber-50 dark:bg-amber-900/10 border border-amber-100/50 dark:border-amber-800/20" title="Proyectos Pendientes">
                        <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ data.pendientes || 0 }}</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-amber-500"></div>
                            <span class="text-[9px] font-semibold text-amber-600/80 dark:text-amber-400/80 uppercase">Pend.</span>
                        </div>
                    </div>

                    <!-- Completados -->
                    <div class="flex flex-col items-center justify-center p-2 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100/50 dark:border-emerald-800/20" title="Proyectos Completados">
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ data.completados || 0 }}</span>
                        <div class="flex items-center gap-1 mt-0.5">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                            <span class="text-[9px] font-semibold text-emerald-600/80 dark:text-emerald-400/80 uppercase">Compl.</span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>