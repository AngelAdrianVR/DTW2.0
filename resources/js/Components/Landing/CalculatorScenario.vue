<script setup>
import { reactive } from 'vue';
import { clampNumber, clampPercent } from '../../utils/financialCalculator';

/**
 * Renderiza los campos de un escenario de la calculadora.
 * No conoce etiquetas: recibe claves de traducción y las resuelve con t().
 */
const props = defineProps({
    scenario: { type: Object, required: true },
    modelValue: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue']);

// Los errores se muestran solo después de que el usuario tocó el campo.
const touched = reactive({});

const MAX_SAFE = 1000000000;

const inputBase =
    'w-full rounded-xl border bg-white py-2.5 text-sm text-dtw-ink shadow-sm transition placeholder-dtw-ink/30 focus:outline-none focus:ring-2';

function inputClass(field) {
    const invalid = touched[field.key] && fieldError(field);

    return [
        inputBase,
        invalid
            ? 'border-dtw-danger/60 focus:border-dtw-danger focus:ring-dtw-danger/20'
            : 'border-dtw-ink/15 focus:border-dtw-primary focus:ring-dtw-primary/20',
        field.type === 'currency' ? 'pl-7' : 'pl-3',
        field.type === 'percent' ? 'pr-9' : 'pr-3',
    ];
}

function fieldId(field) {
    return `calc-${props.scenario.id}-${field.key}`;
}

function rawValue(field) {
    const value = props.modelValue[field.key];
    return value === null || value === undefined ? '' : String(value);
}

function updateField(field, value) {
    emit('update:modelValue', { ...props.modelValue, [field.key]: value });
}

function onInput(field, event) {
    updateField(field, event.target.value);
}

function onBlur(field, event) {
    touched[field.key] = true;

    const raw = event.target.value;

    // Si el campo queda vacío se conserva vacío para mostrar el aviso,
    // pero el cálculo lo interpreta como 0 (nunca produce NaN).
    if (raw === '' || raw === null) {
        return;
    }

    const clamped =
        field.type === 'percent'
            ? clampPercent(raw)
            : clampNumber(raw, field.min ?? 0, field.max ?? MAX_SAFE);

    updateField(field, clamped);
}

function fieldError(field) {
    const raw = props.modelValue[field.key];

    if (raw === '' || raw === null || raw === undefined) {
        return 'Enter a value';
    }

    const number = Number(raw);

    if (!Number.isFinite(number)) {
        return 'Enter a valid number';
    }

    if (number < 0) {
        return 'It cannot be negative';
    }

    if (field.type === 'percent' && number > 100) {
        return 'The percentage cannot exceed 100%';
    }

    return '';
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="field in scenario.fields" :key="field.key">
            <label :for="fieldId(field)" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-dtw-ink/60">
                {{ t(field.labelKey) }}
            </label>

            <div class="relative">
                <span
                    v-if="field.type === 'currency'"
                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-dtw-ink/55"
                    aria-hidden="true"
                >
                    $
                </span>

                <input
                    :id="fieldId(field)"
                    type="number"
                    inputmode="decimal"
                    :value="rawValue(field)"
                    :min="field.min"
                    :max="field.max"
                    :step="field.step"
                    :class="inputClass(field)"
                    :aria-invalid="touched[field.key] && fieldError(field) ? 'true' : undefined"
                    :aria-describedby="[field.hintKey ? `${fieldId(field)}-hint` : null, `${fieldId(field)}-error`].filter(Boolean).join(' ')"
                    @input="onInput(field, $event)"
                    @blur="onBlur(field, $event)"
                />

                <span
                    v-if="field.type === 'percent'"
                    class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-medium text-dtw-ink/55"
                    aria-hidden="true"
                >
                    %
                </span>
            </div>

            <p v-if="field.hintKey" :id="`${fieldId(field)}-hint`" class="mt-1.5 text-[0.7rem] leading-snug text-dtw-ink/60">
                {{ t(field.hintKey) }}
            </p>

            <p
                v-if="touched[field.key] && fieldError(field)"
                :id="`${fieldId(field)}-error`"
                class="mt-1 text-[0.7rem] font-medium text-dtw-danger"
            >
                {{ t(fieldError(field)) }}
            </p>
        </div>
    </div>
</template>
