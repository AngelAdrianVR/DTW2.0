<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import SectionHeading from './SectionHeading.vue';
import CalculatorScenario from './CalculatorScenario.vue';
import AnimatedNumber from './AnimatedNumber.vue';
import {
    CALCULATOR_SCENARIOS,
    DEFAULT_SCENARIO_ID,
    defaultValuesFor,
    getScenario,
} from '../../Data/calculatorScenarios';
import { computeScenario } from '../../utils/financialCalculator';
import { track, trackCalculatorResult, trackCalculatorStart } from '../../utils/analytics';

/**
 * Calculadora de pérdidas financieras.
 * Emite el resultado (sin etiquetas traducidas) para que el formulario de
 * contacto lo adjunte al lead. Las etiquetas se traducen en cada componente.
 */
const emit = defineEmits(['update:result']);

const STORAGE_KEY = 'dtw:calculator';

const activeId = ref(DEFAULT_SCENARIO_ID);
const values = ref(defaultValuesFor(DEFAULT_SCENARIO_ID));
const hasInteracted = ref(false);

const scenario = computed(() => getScenario(activeId.value));
const result = computed(() => computeScenario(activeId.value, values.value));

const inputs = computed(() =>
    scenario.value.fields.reduce((carry, field) => {
        carry[field.key] = Number(values.value[field.key]) || 0;
        return carry;
    }, {}),
);

const hasValues = computed(() => Object.values(inputs.value).some((value) => value > 0));

function selectScenario(id) {
    if (id === activeId.value) {
        return;
    }

    activeId.value = id;
    values.value = defaultValuesFor(id);
    track('calculator_scenario_select', { scenario: id });
}

function resetValues() {
    values.value = defaultValuesFor(activeId.value);
}

function onDiagnosisClick() {
    // Pedir el diagnóstico con estos números ya es un uso explícito del
    // resultado: se adjunta al lead aunque no se hayan movido los campos.
    hasInteracted.value = true;
    trackCalculatorResult({ ...result.value, scenarioId: activeId.value });
}

// El resultado se emite en cada cambio para que el formulario lo tenga listo.
watch(
    [result, hasInteracted],
    ([value, used]) => {
        emit('update:result', {
            scenario: activeId.value,
            monthly_cost: value.monthlyCost,
            recoverable: value.recoverable,
            annual_saving: value.annualSaving,
            improvement: value.improvement,
            inputs: inputs.value,
            // Solo se adjunta al lead cuando el prospecto realmente usó la
            // calculadora (o recuperó sus datos de una visita anterior).
            used,
        });
    },
    { immediate: true },
);

// Primer cambio del usuario = inicio de la calculadora (una sola vez).
watch(
    values,
    () => {
        if (!hasInteracted.value) {
            hasInteracted.value = true;
            trackCalculatorStart(activeId.value);
        }
    },
    { deep: true },
);

// Persistencia ligera: si el prospecto recarga la página no pierde sus datos.
watch(
    [activeId, values],
    () => {
        try {
            sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({ scenario: activeId.value, values: values.value }),
            );
        } catch (error) {
            // sessionStorage puede estar bloqueado (modo privado): no es crítico.
        }
    },
    { deep: true },
);

onMounted(() => {
    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (!stored) {
            return;
        }

        const saved = JSON.parse(stored);
        const savedScenario = saved?.scenario ? getScenario(saved.scenario) : null;

        if (savedScenario && savedScenario.id === saved.scenario) {
            activeId.value = saved.scenario;
            values.value = { ...defaultValuesFor(saved.scenario), ...(saved.values || {}) };
            // El prospecto ya había usado la calculadora antes de recargar.
            hasInteracted.value = true;
        }
    } catch (error) {
        // Datos corruptos o storage bloqueado: se ignoran.
    }
});
</script>

<template>
    <section id="calculadora" class="scroll-mt-24 bg-white py-20 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <SectionHeading
                :eyebrow="t('Free financial diagnosis')"
                :title="t('How much is your operation losing every month?')"
                :description="t('Choose the scenario that looks most like your company, move the numbers and see the money you could recover. It takes less than a minute and you do not need to leave any data.')"
            />

            <!-- Selector de escenario -->
            <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" role="group" :aria-label="t('Choose a scenario')">
                <button
                    v-for="item in CALCULATOR_SCENARIOS"
                    :key="item.id"
                    type="button"
                    :aria-pressed="activeId === item.id"
                    :class="[
                        'group flex h-full flex-col items-start gap-3 rounded-2xl border p-4 text-left transition',
                        activeId === item.id
                            ? 'border-dtw-primary bg-dtw-primary/[0.07] shadow-dtw-card'
                            : 'border-dtw-ink/10 bg-white hover:border-dtw-primary/40 hover:shadow-dtw-card',
                    ]"
                    @click="selectScenario(item.id)"
                >
                    <span
                        :class="[
                            'flex h-10 w-10 items-center justify-center rounded-xl transition',
                            activeId === item.id
                                ? 'bg-dtw-primary text-white'
                                : 'bg-dtw-primary/10 text-dtw-primary group-hover:bg-dtw-primary/15',
                        ]"
                    >
                        <i :class="item.icon" aria-hidden="true"></i>
                    </span>
                    <span class="font-display text-sm font-bold leading-snug text-dtw-ink">
                        {{ t(item.titleKey) }}
                    </span>
                </button>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <!-- Datos del escenario -->
                <div class="rounded-2xl border border-dtw-ink/10 bg-dtw-light/70 p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-dtw-primary">
                                {{ t('Selected scenario') }}
                            </p>
                            <h3 class="mt-2 font-display text-xl font-bold text-dtw-ink">{{ t(scenario.titleKey) }}</h3>
                            <p class="mt-1 max-w-md text-sm leading-relaxed text-dtw-ink/60">
                                {{ t(scenario.descriptionKey) }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full border border-dtw-ink/15 bg-white px-3 py-1.5 text-xs font-semibold text-dtw-ink/70 transition hover:border-dtw-primary/40 hover:text-dtw-primary"
                            @click="resetValues"
                        >
                            <i class="pi pi-refresh text-[0.7rem]" aria-hidden="true"></i>
                            {{ t('Reset') }}
                        </button>
                    </div>

                    <CalculatorScenario v-model="values" :scenario="scenario" class="mt-6" />

                    <details class="group mt-6 rounded-xl border border-dtw-ink/10 bg-white p-4">
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-3 text-sm font-semibold text-dtw-ink"
                        >
                            {{ t('How do we calculate it?') }}
                            <i
                                class="pi pi-chevron-down text-xs text-dtw-ink/55 transition group-open:rotate-180"
                                aria-hidden="true"
                            ></i>
                        </summary>

                        <div class="mt-4 space-y-4 text-sm text-dtw-ink/65">
                            <div>
                                <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-dtw-ink/60">
                                    {{ t('Formulas') }}
                                </p>
                                <ul class="mt-2 space-y-1.5">
                                    <li v-for="key in scenario.formulaKeys" :key="key" class="flex gap-2">
                                        <i class="pi pi-angle-right mt-0.5 text-[0.65rem] text-dtw-primary" aria-hidden="true"></i>
                                        <span>{{ t(key) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <p class="text-[0.7rem] font-semibold uppercase tracking-wide text-dtw-ink/60">
                                    {{ t('Assumptions') }}
                                </p>
                                <ul class="mt-2 space-y-1.5">
                                    <li v-for="key in scenario.assumptionKeys" :key="key" class="flex gap-2">
                                        <i class="pi pi-angle-right mt-0.5 text-[0.65rem] text-dtw-primary" aria-hidden="true"></i>
                                        <span>{{ t(key) }}</span>
                                    </li>
                                </ul>
                            </div>
                            <p class="text-[0.7rem] leading-relaxed text-dtw-ink/60">
                                {{ t('These are estimates to start a conversation, not a financial audit.') }}
                            </p>
                        </div>
                    </details>
                </div>

                <!-- Resultado -->
                <div class="relative overflow-hidden rounded-2xl bg-dtw-ink p-5 text-white shadow-dtw-soft sm:p-6">
                    <div
                        aria-hidden="true"
                        class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-dtw-primary/30 blur-3xl"
                    ></div>

                    <div class="relative">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-dtw-primary-soft">
                                {{ t('Your estimated result') }}
                            </p>
                            <span class="rounded-full bg-white/10 px-2.5 py-1 text-[0.65rem] font-semibold text-white/70">
                                {{ t(scenario.shortKey) }}
                            </span>
                        </div>

                        <div class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs text-white/55">{{ t('Monthly cost of the problem') }}</p>
                            <p class="mt-1 font-display text-2xl font-extrabold text-dtw-danger sm:text-3xl">
                                <AnimatedNumber :value="result.monthlyCost" prefix="$" suffix=" MXN" />
                            </p>
                        </div>

                        <div class="mt-3 rounded-xl border border-dtw-success/25 bg-dtw-success/10 p-4">
                            <p class="text-xs text-white/55">{{ t('Recoverable every month') }}</p>
                            <p class="mt-1 font-display text-2xl font-extrabold text-dtw-success sm:text-3xl">
                                <AnimatedNumber :value="result.recoverable" prefix="$" suffix=" MXN" />
                            </p>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                                <dt class="text-[0.65rem] uppercase tracking-wide text-white/60">
                                    {{ t('Potential annual saving') }}
                                </dt>
                                <dd class="mt-1 font-display text-base font-bold sm:text-lg">
                                    <AnimatedNumber :value="result.annualSaving" prefix="$" suffix=" MXN" />
                                </dd>
                            </div>
                            <div class="rounded-xl border border-white/10 bg-white/5 p-3">
                                <dt class="text-[0.65rem] uppercase tracking-wide text-white/60">
                                    {{ t('Estimated improvement') }}
                                </dt>
                                <dd class="mt-1 font-display text-base font-bold text-dtw-success sm:text-lg">
                                    <AnimatedNumber :value="result.improvement" suffix="%" />
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-5">
                            <div class="flex items-center justify-between text-[0.65rem] uppercase tracking-wide text-white/60">
                                <span>{{ t('Current situation') }}</span>
                                <span>{{ t('With automation') }}</span>
                            </div>
                            <div class="mt-2 space-y-2">
                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full bg-dtw-danger" style="width: 100%"></div>
                                </div>
                                <div class="h-2.5 w-full overflow-hidden rounded-full bg-white/10">
                                    <div
                                        class="h-full rounded-full bg-dtw-success transition-all duration-700"
                                        :style="{ width: `${Math.max(2, result.improvement)}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <p v-if="!hasValues" class="mt-4 rounded-lg bg-white/5 px-3 py-2 text-[0.7rem] text-white/60">
                            {{ t('Complete the fields to see your result.') }}
                        </p>

                        <a
                            href="#contacto"
                            class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-full bg-dtw-primary px-5 py-3 text-sm font-semibold text-white transition hover:bg-dtw-primary-deep"
                            @click="onDiagnosisClick"
                        >
                            <i class="pi pi-send text-xs" aria-hidden="true"></i>
                            {{ t('Request a diagnosis with these numbers') }}
                        </a>

                        <p class="mt-3 text-center text-[0.65rem] leading-relaxed text-white/55">
                            {{ t('Estimate based on the data you entered. It is not a guaranteed result.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
