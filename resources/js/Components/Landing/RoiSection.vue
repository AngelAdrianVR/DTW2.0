<script setup>
import { computed } from 'vue';
import AnimatedNumber from './AnimatedNumber.vue';
import SectionHeading from './SectionHeading.vue';
import { computeRoi, formatMXN } from '../../utils/financialCalculator';
import { trackCtaClick } from '../../utils/analytics';

/**
 * Sección de retorno de inversión con un ejemplo hipotético explícito.
 * Los importes son ilustrativos y no representan una promesa de resultado.
 */
const props = defineProps({
    investment: { type: Number, default: 20000 },
    monthlySaving: { type: Number, default: 6000 },
});

const roi = computed(() =>
    computeRoi({ investment: props.investment, monthlySaving: props.monthlySaving }),
);

const months = computed(() =>
    Array.from({ length: 12 }, (_, index) => ({
        month: index + 1,
        cumulative: roi.value.monthlySaving * (index + 1),
    })),
);

function barHeight(value) {
    if (!roi.value.annualSaving) {
        return '3%';
    }
    return `${Math.max(3, Math.round((value / roi.value.annualSaving) * 100))}%`;
}

const investmentLine = computed(() => {
    if (!roi.value.annualSaving) {
        return '0%';
    }
    return `${Math.min(100, Math.round((roi.value.investment / roi.value.annualSaving) * 100))}%`;
});

function onCtaClick() {
    trackCtaClick('roi_calculator');
}
</script>

<template>
    <section id="roi" class="relative scroll-mt-24 overflow-hidden bg-dtw-dark py-20 sm:py-24">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-dtw-radial"></div>
            <div class="absolute -left-24 bottom-0 h-64 w-64 rounded-full bg-dtw-primary/20 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
                <div>
                    <SectionHeading
                        theme="dark"
                        align="left"
                        :eyebrow="t('Return on investment')"
                        :title="t('Automation is not an expense: it is an investment that pays for itself')"
                        :description="t('When a process stops depending on manual work, the saving is measurable every month. This is how a small project looks in real numbers.')"
                    />

                    <ul class="mt-6 space-y-3 text-sm text-white/65">
                        <li class="flex gap-2">
                            <i class="pi pi-check-circle mt-0.5 text-dtw-success" aria-hidden="true"></i>
                            {{ t('We start with a small, high-impact scope: visible results in weeks.') }}
                        </li>
                        <li class="flex gap-2">
                            <i class="pi pi-check-circle mt-0.5 text-dtw-success" aria-hidden="true"></i>
                            {{ t('We measure before and after with the indicators you already track.') }}
                        </li>
                        <li class="flex gap-2">
                            <i class="pi pi-check-circle mt-0.5 text-dtw-success" aria-hidden="true"></i>
                            {{ t('No forced contracts: we grow the system as the results appear.') }}
                        </li>
                    </ul>

                    <a
                        href="#calculadora"
                        class="mt-8 inline-flex items-center justify-center gap-2 rounded-full bg-dtw-primary px-6 py-3.5 text-sm font-semibold text-white shadow-dtw-glow transition hover:bg-dtw-primary-deep"
                        @click="onCtaClick"
                    >
                        <i class="pi pi-calculator text-sm" aria-hidden="true"></i>
                        {{ t('Calculate my own numbers') }}
                    </a>
                </div>

                <!-- Ejemplo de ROI -->
                <div class="rounded-3xl border border-white/10 bg-dtw-surface/80 p-5 shadow-dtw-soft sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-white/60">
                            {{ t('Illustrative example') }}
                        </p>
                        <span class="rounded-full bg-dtw-success/15 px-2.5 py-1 text-[0.7rem] font-semibold text-dtw-success">
                            ROI +<AnimatedNumber :value="roi.roi" suffix="%" />
                        </span>
                    </div>

                    <dl class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-[0.6rem] uppercase tracking-wide text-white/55">{{ t('Investment') }}</dt>
                            <dd class="mt-1 font-display text-base font-bold text-white">
                                <AnimatedNumber :value="roi.investment" prefix="$" suffix=" MXN" />
                            </dd>
                        </div>
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-[0.6rem] uppercase tracking-wide text-white/55">{{ t('Monthly saving') }}</dt>
                            <dd class="mt-1 font-display text-base font-bold text-white">
                                <AnimatedNumber :value="roi.monthlySaving" prefix="$" suffix=" MXN" />
                            </dd>
                        </div>
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-[0.6rem] uppercase tracking-wide text-white/55">{{ t('Break-even') }}</dt>
                            <dd class="mt-1 font-display text-base font-bold text-white">
                                <AnimatedNumber :value="roi.monthsToBreakEven" :decimals="1" suffix=" " />{{ t('months') }}
                            </dd>
                        </div>
                        <div class="rounded-xl bg-white/5 p-3">
                            <dt class="text-[0.6rem] uppercase tracking-wide text-white/55">
                                {{ t('First-year net benefit') }}
                            </dt>
                            <dd class="mt-1 font-display text-base font-bold text-dtw-success">
                                <AnimatedNumber :value="roi.firstYearNet" prefix="$" suffix=" MXN" />
                            </dd>
                        </div>
                    </dl>

                    <!-- Ahorro acumulado durante 12 meses -->
                    <div class="mt-6">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/60">
                                {{ t('Cumulative saving over 12 months') }}
                            </p>
                            <p class="text-[0.7rem] font-semibold text-white/70">{{ formatMXN(roi.annualSaving) }}</p>
                        </div>

                        <div class="relative mt-4">
                            <div
                                class="pointer-events-none absolute inset-x-0 z-10 border-t border-dashed border-white/30"
                                :style="{ bottom: investmentLine }"
                            >
                                <span class="absolute -top-2 right-0 rounded bg-dtw-surface px-1.5 text-[0.6rem] text-white/50">
                                    {{ t('Investment') }} {{ formatMXN(roi.investment) }}
                                </span>
                            </div>

                            <div
                                class="flex h-36 items-end gap-1.5"
                                role="img"
                                :aria-label="t('Chart of cumulative savings over twelve months compared with the investment')"
                            >
                                <div
                                    v-for="month in months"
                                    :key="month.month"
                                    class="flex-1 rounded-t transition-all duration-700"
                                    :class="
                                        month.cumulative >= roi.investment
                                            ? 'bg-gradient-to-t from-dtw-primary to-dtw-primary-soft'
                                            : 'bg-white/15'
                                    "
                                    :style="{ height: barHeight(month.cumulative) }"
                                    :title="`${t('Month')} ${month.month}: ${formatMXN(month.cumulative)}`"
                                ></div>
                            </div>
                        </div>

                        <div class="mt-2 flex justify-between text-[0.6rem] text-white/50">
                            <span>{{ t('Month') }} 1</span>
                            <span>{{ t('Month') }} 6</span>
                            <span>{{ t('Month') }} 12</span>
                        </div>
                    </div>

                    <p class="mt-4 text-[0.68rem] leading-relaxed text-white/55">
                        {{ t('Hypothetical example with an investment of $20,000 MXN and a monthly saving of $6,000 MXN. Your numbers depend on your operation and are validated in the diagnosis.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
