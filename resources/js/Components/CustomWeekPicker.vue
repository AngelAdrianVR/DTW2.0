<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    modelValue: String // Formato esperado: "YYYY-WXX", ej. "2024-W41"
});
const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const toggle = () => isOpen.value = !isOpen.value;
const close = () => isOpen.value = false;

// Estado para el mes que estamos visualizando en el calendario
const currentDate = ref(new Date());

// Inicializa el calendario en el mes de la semana seleccionada (o mes actual)
const initDate = () => {
    if (props.modelValue) {
        const [year, week] = props.modelValue.split('-W').map(Number);
        // Aproximación: Día 1 del año + (semanas * 7 días)
        const d = new Date(year, 0, 1 + (week - 1) * 7);
        currentDate.value = d;
    } else {
        currentDate.value = new Date();
    }
};

watch(() => props.modelValue, initDate, { immediate: true });

const monthName = computed(() => {
    return currentDate.value.toLocaleString('es-ES', { month: 'long' });
});

const year = computed(() => currentDate.value.getFullYear());

const prevMonth = () => {
    currentDate.value = new Date(year.value, currentDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentDate.value = new Date(year.value, currentDate.value.getMonth() + 1, 1);
};

// Función matemática para calcular el formato ISO de la semana ("2024-W41")
const getISOWeekString = (date) => {
    const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
    const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    return `${d.getUTCFullYear()}-W${String(weekNo).padStart(2, '0')}`;
};

// Genera la cuadrícula de días y semanas para el calendario
const calendarWeeks = computed(() => {
    const weeks = [];
    const firstDayOfMonth = new Date(year.value, currentDate.value.getMonth(), 1);
    
    // Encontrar el Lunes anterior o igual al inicio del mes
    const dayOfWeek = firstDayOfMonth.getDay() || 7; // 1=Lu, 7=Do
    const startDate = new Date(firstDayOfMonth);
    startDate.setDate(startDate.getDate() - (dayOfWeek - 1));

    let currentDay = new Date(startDate);

    // Renderizamos siempre 6 semanas para un tamaño uniforme
    for (let w = 0; w < 6; w++) {
        const weekDays = [];
        for (let d = 0; d < 7; d++) {
            weekDays.push({
                date: new Date(currentDay),
                dayOfMonth: currentDay.getDate(),
                isCurrentMonth: currentDay.getMonth() === currentDate.value.getMonth(),
            });
            currentDay.setDate(currentDay.getDate() + 1);
        }
        
        // La norma ISO dicta que el Jueves determina a qué año/semana pertenece
        const thursday = weekDays[3].date;
        const isoString = getISOWeekString(thursday);

        weeks.push({
            id: isoString,
            isoString,
            days: weekDays
        });
    }
    return weeks;
});

const selectWeek = (weekObj) => {
    emit('update:modelValue', weekObj.isoString);
    close();
};
</script>

<template>
  <div class="relative inline-block">
    <!-- Slot para inyectar el botón que dispara el menú -->
    <slot name="trigger" :toggle="toggle"></slot>
    
    <!-- Fondo invisible para cerrar al hacer clic afuera -->
    <div v-if="isOpen" class="fixed inset-0 z-[100]" @click="close"></div>

    <!-- Popover con diseño Apple (Cristal esmerilado, esquinas muy redondeadas) -->
    <transition 
        enter-active-class="transition ease-out duration-300" 
        enter-from-class="opacity-0 scale-95 translate-y-2 blur-sm" 
        enter-to-class="opacity-100 scale-100 translate-y-0 blur-0" 
        leave-active-class="transition ease-in duration-200" 
        leave-from-class="opacity-100 scale-100 translate-y-0 blur-0" 
        leave-to-class="opacity-0 scale-95 translate-y-2 blur-sm">
        
        <div v-if="isOpen" class="absolute z-[101] right-0 mt-3 w-72 p-4 bg-white/100 dark:bg-zinc-900/100 backdrop-blur-2xl border border-white/40 dark:border-zinc-700/50 rounded-[1.5rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.1)] dark:shadow-[0_20px_40px_-15px_rgba(0,0,0,0.5)]">
            
            <!-- Cabecera Mes/Año -->
            <div class="flex items-center justify-between mb-4 px-2">
                <button @click="prevMonth" class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-black/5 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition-colors focus:outline-none">
                    <i class="pi pi-chevron-left text-xs font-bold"></i>
                </button>
                <span class="text-[15px] font-semibold tracking-tight capitalize text-gray-900 dark:text-gray-100">{{ monthName }} {{ year }}</span>
                <button @click="nextMonth" class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-black/5 dark:hover:bg-white/10 text-gray-500 dark:text-gray-400 transition-colors focus:outline-none">
                    <i class="pi pi-chevron-right text-xs font-bold"></i>
                </button>
            </div>

            <!-- Días de la semana -->
            <div class="grid grid-cols-7 text-center mb-3">
                <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest" v-for="d in ['Lu','Ma','Mi','Ju','Vi','Sa','Do']" :key="d">{{ d }}</span>
            </div>

            <!-- Filas de Semanas -->
            <div class="space-y-1">
                <div v-for="weekObj in calendarWeeks" :key="weekObj.id"
                     @click="selectWeek(weekObj)"
                     class="grid grid-cols-7 text-center rounded-xl cursor-pointer transition-all duration-200 ease-in-out p-1 relative"
                     :class="[
                        modelValue === weekObj.isoString 
                            ? 'bg-indigo-500 text-white shadow-md shadow-indigo-500/20' 
                            : 'hover:bg-black/5 dark:hover:bg-white/5 text-gray-800 dark:text-gray-300'
                     ]">
                     
                     <span v-for="day in weekObj.days" :key="day.date.toISOString()"
                           class="py-1.5 text-[13px] font-medium z-10 transition-colors"
                           :class="{
                              'opacity-20': !day.isCurrentMonth && modelValue !== weekObj.isoString,
                              'opacity-100': day.isCurrentMonth || modelValue === weekObj.isoString
                           }">
                         {{ day.dayOfMonth }}
                     </span>
                </div>
            </div>
        </div>
    </transition>
  </div>
</template>