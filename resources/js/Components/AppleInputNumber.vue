<template>
    <div :class="[
        'flex items-center bg-white dark:bg-zinc-950 border border-zinc-200 dark:border-zinc-700 overflow-hidden shadow-sm transition-colors focus-within:border-zinc-400 dark:focus-within:border-zinc-500',
        size === 'sm' ? 'h-8 rounded-lg' : 'h-[48px] rounded-xl'
    ]">
        <button 
            type="button" 
            @click="decrement" 
            :class="[
                'flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors active:bg-zinc-200 dark:active:bg-zinc-700 border-r border-zinc-200 dark:border-zinc-700',
                size === 'sm' ? 'w-8 h-full' : 'w-12 h-full'
            ]"
            :disabled="min !== null && modelValue <= min"
        >
            <i class="pi pi-minus" :class="size === 'sm' ? 'text-[0.6rem]' : 'text-sm'"></i>
        </button>
        
        <input 
            type="text" 
            inputmode="decimal"
            :value="displayValue"
            @input="onInput"
            @blur="onBlur"
            :class="[
                'flex-1 w-full h-full text-center bg-transparent border-0 outline-none text-zinc-900 dark:text-zinc-100 font-medium min-w-0 px-1',
                size === 'sm' ? 'text-sm' : 'text-lg'
            ]"
        />
        
        <button 
            type="button" 
            @click="increment" 
            :class="[
                'flex items-center justify-center text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors active:bg-zinc-200 dark:active:bg-zinc-700 border-l border-zinc-200 dark:border-zinc-700',
                size === 'sm' ? 'w-8 h-full' : 'w-12 h-full'
            ]"
            :disabled="max !== null && modelValue >= max"
        >
            <i class="pi pi-plus" :class="size === 'sm' ? 'text-[0.6rem]' : 'text-sm'"></i>
        </button>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: 0 },
    min: { type: Number, default: null },
    max: { type: Number, default: null },
    step: { type: Number, default: 1 },
    allowDecimals: { type: Boolean, default: false },
    size: { type: String, default: 'md' } // Acepta 'md' (default) y 'sm' para tablas/móviles
});

const emit = defineEmits(['update:modelValue']);
const displayValue = ref(props.modelValue);

// Sincroniza el valor si cambia desde el padre
watch(() => props.modelValue, (newVal) => {
    if (parseFloat(displayValue.value) !== newVal) {
        displayValue.value = newVal;
    }
});

const commitValue = (val) => {
    let num = parseFloat(val);
    if (isNaN(num)) num = props.min !== null ? props.min : 0;

    // Respetar límites
    if (props.min !== null && num < props.min) num = props.min;
    if (props.max !== null && num > props.max) num = props.max;

    // Manejar decimales o enteros
    if (!props.allowDecimals) {
        num = Math.round(num);
    } else {
        // Redondeo seguro a 3 decimales máximo para evitar floats largos
        num = Math.round(num * 1000) / 1000; 
    }

    displayValue.value = num;
    emit('update:modelValue', num);
};

const increment = () => commitValue((parseFloat(props.modelValue) || 0) + props.step);
const decrement = () => commitValue((parseFloat(props.modelValue) || 0) - props.step);

const onInput = (e) => {
    // Solo permitimos números, el punto decimal y el signo menos
    displayValue.value = e.target.value.replace(/[^0-9.-]/g, '');
};

const onBlur = () => commitValue(displayValue.value);
</script>