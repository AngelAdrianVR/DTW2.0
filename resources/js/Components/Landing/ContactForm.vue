<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import SectionHeading from './SectionHeading.vue';
import { EMPLOYEE_RANGES, INDUSTRIES } from '../../Data/leadOptions';
import { getScenario } from '../../Data/calculatorScenarios';
import { formatMXN } from '../../utils/financialCalculator';
import { useT } from '../../Composables/useT';
import { track, trackWhatsAppClick } from '../../utils/analytics';

const props = defineProps({
    calculator: { type: Object, default: null },
    whatsapp: { type: String, default: '' },
    whatsappDisplay: { type: String, default: '' },
    contactEmail: { type: String, default: '' },
});

const t = useT();
const page = usePage();

const submitted = ref(false);
const dismissedCalculator = ref(false);
const industry = ref('');
const employees = ref('');

const form = useForm({
    client_name: '',
    company: '',
    client_email: '',
    client_phone: '',
    industry: '',
    employees: '',
    description: '',
    title: '',
    locale: page.props.locale || 'es',
    calculator: null,
});

/**
 * Resumen del resultado de la calculadora. Se traduce aquí (y no en la
 * calculadora) para que el idioma coincida siempre con el del envío.
 */
const calculatorSummary = computed(() => {
    const data = props.calculator;

    if (!data || !data.annual_saving || !data.used) {
        return null;
    }

    const scenario = getScenario(data.scenario);

    return {
        title: t(scenario.titleKey),
        monthlyCost: data.monthly_cost,
        recoverable: data.recoverable,
        annualSaving: data.annual_saving,
        improvement: data.improvement,
        payload: {
            scenario: data.scenario,
            scenario_label: t(scenario.titleKey),
            monthly_cost: data.monthly_cost,
            recoverable: data.recoverable,
            annual_saving: data.annual_saving,
            improvement: data.improvement,
            inputs: data.inputs || {},
        },
    };
});

const showCalculator = computed(() => Boolean(calculatorSummary.value) && !dismissedCalculator.value);

// Si Inertia vuelve a montar la página tras el envío, el estado de éxito se
// recupera del flash compartido por HandleInertiaRequests.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.type === 'success' && flash?.message) {
            submitted.value = true;
        }
    },
    { immediate: true, deep: true },
);

watch(
    calculatorSummary,
    (summary) => {
        dismissedCalculator.value = false;
        form.calculator = summary?.payload ?? null;
    },
    { immediate: true },
);

const whatsappUrl = computed(() => {
    if (!props.whatsapp) {
        return '#contacto';
    }

    const message = t('Hello, I want a free diagnosis for my company.');
    return `https://wa.me/${props.whatsapp}?text=${encodeURIComponent(message)}`;
});

const emailUrl = computed(() => (props.contactEmail ? `mailto:${props.contactEmail}` : '#'));

function labelFor(options, value) {
    const option = options.find((item) => item.value === value);
    return option ? t(option.labelKey) : value;
}

function submit() {
    form.industry = industry.value ? labelFor(INDUSTRIES, industry.value) : '';
    form.employees = employees.value ? labelFor(EMPLOYEE_RANGES, employees.value) : '';
    form.title = form.industry ? `${t('Free diagnosis')} · ${form.industry}` : t('Free diagnosis');
    form.calculator = calculatorSummary.value?.payload ?? null;
    form.locale = page.props.locale || 'es';

    form.post('/quote-request', {
        preserveScroll: true,
        onSuccess: () => {
            track('lead_request', {
                scenario: calculatorSummary.value?.payload?.scenario ?? null,
                employees: employees.value || null,
                language: form.locale,
            });
            submitted.value = true;
        },
    });
}

function sendAnother() {
    submitted.value = false;
    form.reset();
    form.clearErrors();
    industry.value = '';
    employees.value = '';
    form.calculator = calculatorSummary.value?.payload ?? null;
}

function onWhatsAppClick() {
    trackWhatsAppClick('contact_section');
}

const inputClass =
    'w-full rounded-xl border border-dtw-ink/15 bg-white px-4 py-2.5 text-sm text-dtw-ink shadow-sm transition placeholder-dtw-ink/35 focus:border-dtw-primary focus:outline-none focus:ring-2 focus:ring-dtw-primary/20';
const labelClass = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-dtw-ink/60';
const errorClass = 'mt-1 text-[0.7rem] font-medium text-dtw-danger';
</script>

<template>
    <section id="contacto" class="relative scroll-mt-24 overflow-hidden bg-dtw-dark py-20 sm:py-24">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-dtw-radial"></div>
            <div class="absolute -bottom-24 right-0 h-72 w-72 rounded-full bg-dtw-primary/25 blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid gap-10 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
                <!-- Texto de apoyo -->
                <div>
                    <SectionHeading
                        theme="dark"
                        align="left"
                        :eyebrow="t('Free diagnosis')"
                        :title="t('Let us review your operation and tell you exactly where the money is leaking')"
                        :description="t('A 30-minute call, no cost and no commitment. We review your processes, quantify the losses and tell you what to automate first.')"
                    />

                    <ol class="mt-8 space-y-4">
                        <li class="flex gap-3">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-xs font-bold text-white"
                            >
                                1
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ t('We talk about your operation') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-white/55">
                                    {{ t('What takes time, what gets lost and what your team does by hand.') }}
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-xs font-bold text-white"
                            >
                                2
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ t('We quantify the opportunity') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-white/55">
                                    {{ t('We translate the problem into hours and pesos so you can decide with numbers.') }}
                                </p>
                            </div>
                        </li>
                        <li class="flex gap-3">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white/10 font-display text-xs font-bold text-white"
                            >
                                3
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-white">{{ t('We send you a clear proposal') }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-white/55">
                                    {{ t('Scope, stages, timeline and estimated return. No fine print.') }}
                                </p>
                            </div>
                        </li>
                    </ol>

                    <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5">
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-white/60">
                            {{ t('Prefer to talk right now?') }}
                        </p>
                        <div class="mt-3 space-y-2">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-3 rounded-xl bg-dtw-success/10 px-4 py-3 text-sm font-semibold text-white transition hover:bg-dtw-success/20"
                                @click="onWhatsAppClick"
                            >
                                <i class="pi pi-whatsapp text-base text-dtw-success" aria-hidden="true"></i>
                                <span>{{ whatsappDisplay || whatsapp }}</span>
                            </a>
                            <a
                                :href="emailUrl"
                                class="flex items-center gap-3 rounded-xl bg-white/5 px-4 py-3 text-sm font-semibold text-white/80 transition hover:bg-white/10"
                            >
                                <i class="pi pi-envelope text-base text-dtw-primary-soft" aria-hidden="true"></i>
                                <span>{{ contactEmail }}</span>
                            </a>
                        </div>
                        <p class="mt-3 text-[0.7rem] text-white/55">{{ t('Zapopan, Jalisco, Mexico') }}</p>
                    </div>
                </div>

                <!-- Formulario -->
                <div class="rounded-3xl border border-white/10 bg-white p-5 shadow-dtw-soft sm:p-7">
                    <!-- Estado de éxito -->
                    <div v-if="submitted" class="py-6 text-center" role="status" aria-live="polite">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-dtw-success/10 text-dtw-success">
                            <i class="pi pi-check text-2xl" aria-hidden="true"></i>
                        </span>
                        <h3 class="mt-5 font-display text-2xl font-extrabold text-dtw-ink">
                            {{ t('Thank you, we received your request') }}
                        </h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-dtw-ink/65">
                            {{ t('We will contact you within one business day to schedule your free diagnosis.') }}
                        </p>
                        <div class="mt-6 flex flex-wrap justify-center gap-3">
                            <a
                                :href="whatsappUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-full bg-dtw-success px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-dtw-success/90"
                                @click="onWhatsAppClick"
                            >
                                <i class="pi pi-whatsapp" aria-hidden="true"></i>
                                {{ t('Continue on WhatsApp') }}
                            </a>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-dtw-ink/15 px-5 py-2.5 text-sm font-semibold text-dtw-ink transition hover:border-dtw-primary/40 hover:text-dtw-primary"
                                @click="sendAnother"
                            >
                                {{ t('Send another request') }}
                            </button>
                        </div>
                    </div>

                    <!-- Formulario -->
                    <form v-else class="space-y-4" novalidate @submit.prevent="submit">
                        <div>
                            <h3 class="font-display text-xl font-bold text-dtw-ink">
                                {{ t('Request your free diagnosis') }}
                            </h3>
                            <p class="mt-1 text-sm text-dtw-ink/60">
                                {{ t('We only ask for what we need to contact you.') }}
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="lead-name" :class="labelClass">
                                    {{ t('Full name') }} <span class="text-dtw-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="lead-name"
                                    v-model="form.client_name"
                                    type="text"
                                    name="name"
                                    autocomplete="name"
                                    :class="inputClass"
                                    :aria-invalid="form.errors.client_name ? 'true' : undefined"
                                    :placeholder="t('Your name')"
                                />
                                <p v-if="form.errors.client_name" :class="errorClass">{{ form.errors.client_name }}</p>
                            </div>

                            <div>
                                <label for="lead-company" :class="labelClass">
                                    {{ t('Company') }} <span class="text-dtw-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="lead-company"
                                    v-model="form.company"
                                    type="text"
                                    name="organization"
                                    autocomplete="organization"
                                    :class="inputClass"
                                    :aria-invalid="form.errors.company ? 'true' : undefined"
                                    :placeholder="t('Company name')"
                                />
                                <p v-if="form.errors.company" :class="errorClass">{{ form.errors.company }}</p>
                            </div>

                            <div>
                                <label for="lead-email" :class="labelClass">
                                    {{ t('Work email') }} <span class="text-dtw-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="lead-email"
                                    v-model="form.client_email"
                                    type="email"
                                    name="email"
                                    autocomplete="email"
                                    inputmode="email"
                                    :class="inputClass"
                                    :aria-invalid="form.errors.client_email ? 'true' : undefined"
                                    :placeholder="t('name@company.com')"
                                />
                                <p v-if="form.errors.client_email" :class="errorClass">{{ form.errors.client_email }}</p>
                            </div>

                            <div>
                                <label for="lead-phone" :class="labelClass">
                                    {{ t('Phone or WhatsApp') }} <span class="text-dtw-danger" aria-hidden="true">*</span>
                                </label>
                                <input
                                    id="lead-phone"
                                    v-model="form.client_phone"
                                    type="tel"
                                    name="phone"
                                    autocomplete="tel"
                                    inputmode="tel"
                                    :class="inputClass"
                                    :aria-invalid="form.errors.client_phone ? 'true' : undefined"
                                    :placeholder="t('With area code')"
                                />
                                <p v-if="form.errors.client_phone" :class="errorClass">{{ form.errors.client_phone }}</p>
                            </div>

                            <div>
                                <label for="lead-industry" :class="labelClass">
                                    {{ t('Industry') }} <span class="text-dtw-danger" aria-hidden="true">*</span>
                                </label>
                                <select
                                    id="lead-industry"
                                    v-model="industry"
                                    name="industry"
                                    :class="inputClass"
                                    :aria-invalid="form.errors.industry ? 'true' : undefined"
                                >
                                    <option value="" disabled>{{ t('Select an option') }}</option>
                                    <option v-for="option in INDUSTRIES" :key="option.value" :value="option.value">
                                        {{ t(option.labelKey) }}
                                    </option>
                                </select>
                                <p v-if="form.errors.industry" :class="errorClass">{{ form.errors.industry }}</p>
                            </div>

                            <div>
                                <label for="lead-employees" :class="labelClass">{{ t('Company size') }}</label>
                                <select id="lead-employees" v-model="employees" name="employees" :class="inputClass">
                                    <option value="">{{ t('Select an option') }}</option>
                                    <option v-for="option in EMPLOYEE_RANGES" :key="option.value" :value="option.value">
                                        {{ t(option.labelKey) }}
                                    </option>
                                </select>
                                <p v-if="form.errors.employees" :class="errorClass">{{ form.errors.employees }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="lead-description" :class="labelClass">
                                {{ t('What is happening in your operation?') }}
                                <span class="text-dtw-danger" aria-hidden="true">*</span>
                            </label>
                            <textarea
                                id="lead-description"
                                v-model="form.description"
                                name="description"
                                rows="4"
                                :class="[inputClass, 'resize-y']"
                                :aria-invalid="form.errors.description ? 'true' : undefined"
                                :placeholder="t('Which process is costing you the most time or money right now?')"
                            ></textarea>
                            <p v-if="form.errors.description" :class="errorClass">{{ form.errors.description }}</p>
                        </div>

                        <!-- Resultado de la calculadora que viaja con el lead -->
                        <div v-if="showCalculator" class="rounded-xl border border-dtw-primary/25 bg-dtw-primary/[0.06] p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-[0.16em] text-dtw-primary">
                                        {{ t('Attached calculator result') }}
                                    </p>
                                    <p class="mt-1 font-display text-sm font-bold text-dtw-ink">
                                        {{ calculatorSummary.title }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="text-[0.7rem] font-semibold text-dtw-ink/60 underline decoration-dotted transition hover:text-dtw-danger"
                                    @click="dismissedCalculator = true"
                                >
                                    {{ t('Remove') }}
                                </button>
                            </div>

                            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-dtw-ink/60 sm:grid-cols-3">
                                <div>
                                    <dt>{{ t('Monthly cost') }}</dt>
                                    <dd class="font-semibold text-dtw-ink">{{ formatMXN(calculatorSummary.monthlyCost) }}</dd>
                                </div>
                                <div>
                                    <dt>{{ t('Recoverable') }}</dt>
                                    <dd class="font-semibold text-dtw-success">{{ formatMXN(calculatorSummary.recoverable) }}</dd>
                                </div>
                                <div>
                                    <dt>{{ t('Annual saving') }}</dt>
                                    <dd class="font-semibold text-dtw-ink">{{ formatMXN(calculatorSummary.annualSaving) }}</dd>
                                </div>
                            </dl>

                            <p class="mt-3 text-[0.7rem] leading-relaxed text-dtw-ink/60">
                                {{ t('The numbers are sent with your request so we can start from them.') }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-dtw-primary px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-dtw-primary-deep disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            <i
                                :class="form.processing ? 'pi pi-spinner pi-spin' : 'pi pi-send'"
                                class="text-xs"
                                aria-hidden="true"
                            ></i>
                            {{ form.processing ? t('Sending...') : t('Send my information') }}
                        </button>

                        <p class="text-center text-[0.7rem] leading-relaxed text-dtw-ink/60">
                            {{ t('By sending this form you accept our') }}
                            <Link
                                :href="route('privacy.show')"
                                class="font-semibold text-dtw-primary underline decoration-dotted"
                            >
                                {{ t('Privacy Policy') }}
                            </Link>.
                            {{ t('We do not share your data with third parties.') }}
                        </p>
                    </form>
                </div>
            </div>
        </div>
    </section>
</template>
