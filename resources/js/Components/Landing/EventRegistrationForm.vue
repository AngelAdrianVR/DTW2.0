<script setup>
/**
 * Formulario público de registro a un evento.
 *
 * - Un teléfono = un boleto: el backend rechaza duplicados con un error en el
 *   campo `phone`, que aquí se pinta debajo del input.
 * - El campo variable lo define cada evento (etiqueta, ayuda y si es obligatorio).
 * - Al guardar, el flash del backend marca el estado de éxito sin recargar.
 */
import { computed, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { useT } from '@/Composables/useT';

const props = defineProps({
    event: { type: Object, required: true },
});

const t = useT();
const page = usePage();

const submitted = ref(false);

const form = useForm({
    name: '',
    business_name: '',
    phone: '',
    custom_answer: '',
    locale: page.props.locale || 'es',
    // Honeypot: una persona nunca lo ve; un bot lo llena y el backend lo rechaza.
    website: '',
});

const customLabel = computed(() => props.event.customField?.label || '');
const customPlaceholder = computed(() => props.event.customField?.placeholder || '');
const customRequired = computed(() => Boolean(props.event.customField?.required));

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.type === 'success' && flash?.message) {
            submitted.value = true;
        }
    },
    { immediate: true, deep: true },
);

const inputClass =
    'mt-1.5 w-full rounded-xl border border-white/15 bg-dtw-void/60 px-4 py-3 text-sm text-white placeholder:text-dtw-dim focus:border-dtw-primary focus:outline-none focus:ring-1 focus:ring-dtw-primary';

function submit() {
    form.post(`/eventos/${props.event.slug}/registro`, {
        preserveScroll: true,
        onSuccess: () => {
            if (!form.hasErrors) {
                form.reset('name', 'business_name', 'phone', 'custom_answer', 'website');
            }
        },
    });
}
</script>

<template>
    <div class="rounded-[26px] border border-white/10 bg-white/[0.04] p-6 md:p-7">
        <!-- Éxito -->
        <div v-if="submitted" class="text-center">
            <span
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-dtw-success/15 text-dtw-success"
            >
                <i class="pi pi-check text-2xl" aria-hidden="true"></i>
            </span>
            <p class="mt-5 font-display text-lg font-bold text-white">{{ t('We have your registration') }}</p>
            <p class="mt-2 text-sm leading-relaxed text-dtw-muted">
                {{ t('We will contact you if you win this event.') }}
            </p>
            <button
                type="button"
                class="mt-6 inline-flex items-center gap-2 rounded-full border border-white/15 px-5 py-2.5 text-sm font-semibold text-white transition hover:border-white/35 hover:bg-white/5"
                @click="submitted = false"
            >
                <i class="pi pi-user-plus text-xs"></i>
                {{ t('Register another participant') }}
            </button>
        </div>

        <!-- Formulario -->
        <form v-else class="space-y-4" @submit.prevent="submit">
            <div>
                <p class="font-display text-lg font-bold text-white">{{ t('Registration form') }}</p>
                <p class="mt-1 text-sm text-dtw-muted">{{ t('It takes less than a minute.') }}</p>
            </div>

            <div>
                <label for="event-name" class="text-xs font-semibold uppercase tracking-[0.14em] text-dtw-dim">
                    {{ t('Full name') }}
                </label>
                <input
                    id="event-name"
                    v-model="form.name"
                    type="text"
                    autocomplete="name"
                    :class="inputClass"
                    :placeholder="t('Your name')"
                />
                <p v-if="form.errors.name" class="mt-1 text-xs text-dtw-danger">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="event-business" class="text-xs font-semibold uppercase tracking-[0.14em] text-dtw-dim">
                    {{ t('Business name (optional)') }}
                </label>
                <input
                    id="event-business"
                    v-model="form.business_name"
                    type="text"
                    autocomplete="organization"
                    :class="inputClass"
                    :placeholder="t('Your business')"
                />
                <p v-if="form.errors.business_name" class="mt-1 text-xs text-dtw-danger">
                    {{ form.errors.business_name }}
                </p>
            </div>

            <div>
                <label for="event-phone" class="text-xs font-semibold uppercase tracking-[0.14em] text-dtw-dim">
                    {{ t('Phone or WhatsApp') }}
                </label>
                <input
                    id="event-phone"
                    v-model="form.phone"
                    type="tel"
                    inputmode="tel"
                    autocomplete="tel"
                    :class="inputClass"
                    placeholder="33 1234 5678"
                />
                <p v-if="form.errors.phone" class="mt-1 text-xs text-dtw-danger">{{ form.errors.phone }}</p>
            </div>

            <div v-if="customLabel">
                <label for="event-custom" class="text-xs font-semibold uppercase tracking-[0.14em] text-dtw-dim">
                    {{ customLabel }}
                    <span v-if="customRequired" class="text-dtw-primary-soft">*</span>
                </label>
                <textarea
                    id="event-custom"
                    v-model="form.custom_answer"
                    rows="3"
                    :class="inputClass"
                    :placeholder="customPlaceholder"
                ></textarea>
                <p v-if="form.errors.custom_answer" class="mt-1 text-xs text-dtw-danger">
                    {{ form.errors.custom_answer }}
                </p>
            </div>

            <!-- Honeypot -->
            <div class="hidden" aria-hidden="true">
                <label for="event-website">Website</label>
                <input id="event-website" v-model="form.website" type="text" tabindex="-1" autocomplete="off" />
            </div>

            <p v-if="form.errors.registration" class="text-xs text-dtw-danger">{{ form.errors.registration }}</p>

            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-dtw-primary px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-dtw-primary-deep disabled:cursor-not-allowed disabled:opacity-70"
            >
                <i :class="form.processing ? 'pi pi-spinner pi-spin' : 'pi pi-send'" class="text-xs"></i>
                {{ form.processing ? t('Sending...') : t('Register') }}
            </button>

            <p class="text-center text-[0.7rem] leading-relaxed text-dtw-dim">
                {{ t('We only use your data to contact you if you win.') }}
            </p>
        </form>
    </div>
</template>

