<script setup>
import { usePomodoro } from '@/Composables/usePomodoro';

const { state, displayTime, startTimer, pauseTimer, resetTimer, toggleModal, saveSettings } = usePomodoro();

const handleSave = () => {
    saveSettings();
    // No reseteamos inmediatamente para permitir al usuario guardar sin interrumpir.
    // El reset se aplicará al iniciar la siguiente sesión.
};
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="state.isModalOpen" class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm" @click="toggleModal(false)"></div>
    </transition>

    <transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
        enter-to-class="opacity-100 transform translate-y-0 sm:scale-100"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 transform translate-y-0 sm:scale-100"
        leave-to-class="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
    >
        <div v-if="state.isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-md bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-center">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Pomodoro Timer</h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ state.isWorkSession ? 'Concéntrate en tus tareas.' : 'Toma un merecido descanso.' }}
                    </p>

                    <div class="my-8">
                        <p class="font-mono text-7xl md:text-8xl font-bold tracking-tighter" :class="state.isWorkSession ? 'text-gray-900 dark:text-white' : 'text-blue-500 dark:text-blue-400'">
                            {{ displayTime }}
                        </p>
                    </div>
                    
                    <!-- Formulario de configuración visible solo si el timer no está corriendo -->
                    <div v-if="!state.isRunning || state.isPaused" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                             <div>
                                <label for="work-minutes" class="block text-sm font-medium text-left text-gray-700 dark:text-gray-300">Enfoque (min)</label>
                                <input v-model.number="state.settings.work_minutes" @change="handleSave" type="number" id="work-minutes" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label for="break-minutes" class="block text-sm font-medium text-left text-gray-700 dark:text-gray-300">Descanso (min)</label>
                                <input v-model.number="state.settings.break_minutes" @change="handleSave" type="number" id="break-minutes" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                            </div>
                            <!-- Nuevos campos de configuración -->
                             <div>
                                <label for="long-break-minutes" class="block text-sm font-medium text-left text-gray-700 dark:text-gray-300">Descanso largo (min)</label>
                                <input v-model.number="state.settings.long_break_minutes" @change="handleSave" type="number" id="long-break-minutes" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label for="sessions-before-long-break" class="block text-sm font-medium text-left text-gray-700 dark:text-gray-300">Sesiones p/ d. largo</label>
                                <input v-model.number="state.settings.sessions_before_long_break" @change="handleSave" type="number" id="sessions-before-long-break" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm text-gray-900 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-center gap-x-4">
                        <button @click="resetTimer(true)" title="Reiniciar" class="p-3 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9a9 9 0 0114.53-4.53l-2.08 2.08M20 15a9 9 0 01-14.53 4.53l2.08-2.08"></path></svg>
                        </button>

                        <button v-if="!state.isRunning || state.isPaused" @click="startTimer" class="px-8 py-4 bg-blue-600 text-white font-semibold rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-transform transform hover:scale-105">
                            {{ state.isPaused ? 'Reanudar' : 'Iniciar' }}
                        </button>
                        <button v-else @click="pauseTimer" class="px-8 py-4 bg-yellow-500 text-white font-semibold rounded-full shadow-lg hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-transform transform hover:scale-105">
                            Pausar
                        </button>
                        
                        <button @click="toggleModal(false)" title="Cerrar" class="p-3 rounded-full text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                             <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
