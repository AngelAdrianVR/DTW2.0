<script setup>
/**
 * Bloque "Eventos" de la landing.
 *
 * Destaca la convocatoria abierta (o la más reciente) con una tarjeta grande y
 * deja las últimas ediciones como historial a un lado. El detalle completo vive
 * en /eventos.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import EventStatusBadge from './EventStatusBadge.vue';
import { useEvents } from '@/Composables/useEvents';
import { useT } from '@/Composables/useT';

const props = defineProps({
    events: { type: Array, default: () => [] },
});

const t = useT();
const allEvents = useEvents(computed(() => props.events));

const featured = computed(
    () => allEvents.value.find((event) => event.status === 'open') || allEvents.value[0] || null,
);

const history = computed(() =>
    allEvents.value.filter((event) => event.id !== featured.value?.id).slice(0, 4),
);
</script>

<template>
    <section v-if="allEvents.length" id="eventos" class="relative py-20 md:py-24">
        <div class="mx-auto w-full max-w-7xl px-5 md:px-8">
            <!-- Encabezado -->
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between" v-reveal>
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft">
                        {{ t('Events') }}
                    </p>
                    <h2
                        class="mt-4 font-display text-[clamp(1.7rem,3.6vw,2.8rem)] font-extrabold leading-[1.08] tracking-tight text-white"
                    >
                        {{ t('Events and challenges open to the community') }}
                    </h2>
                </div>

                <Link
                    href="/eventos"
                    class="group inline-flex w-fit items-center gap-2 rounded-full border border-white/15 bg-white/[0.03] px-6 py-3 text-sm font-semibold text-white transition hover:border-white/30 hover:bg-white/[0.07]"
                >
                    {{ t('See all events') }}
                    <i
                        class="pi pi-arrow-up-right text-xs transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
                    ></i>
                </Link>
            </div>

            <!-- Convocatoria destacada + historial -->
            <div v-if="featured" class="mt-10 grid gap-6 lg:grid-cols-[1.15fr_0.85fr]" v-reveal>
                <Link
                    :href="featured.href"
                    class="group relative flex flex-col overflow-hidden rounded-[30px] border border-white/10 bg-white/[0.03] transition duration-300 hover:border-white/25"
                >
                    <div class="relative aspect-[16/9] w-full overflow-hidden">
                        <img
                            v-if="featured.image"
                            :src="featured.image"
                            :alt="featured.title"
                            class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]"
                            loading="lazy"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center bg-gradient-to-br from-dtw-brand/40 via-dtw-surface-soft to-dtw-void"
                        >
                            <img src="/images/white_logo.png" alt="" class="h-10 w-auto opacity-40" />
                        </div>
                        <div class="pointer-events-none absolute left-5 top-5">
                            <EventStatusBadge :status="featured.status" />
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col gap-4 p-6 md:p-8">
                        <p
                            v-if="featured.eventDateLabel"
                            class="text-xs font-medium uppercase tracking-[0.2em] text-dtw-dim"
                        >
                            {{ featured.eventDateLabel }}
                        </p>

                        <h3
                            class="font-display text-2xl font-extrabold leading-tight tracking-tight text-white transition group-hover:text-dtw-primary-soft md:text-3xl"
                        >
                            {{ featured.title }}
                        </h3>

                        <p v-if="featured.challenge" class="line-clamp-3 text-sm leading-relaxed text-dtw-muted">
                            {{ featured.challenge }}
                        </p>

                        <p
                            v-if="featured.prizesSummary"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] px-4 py-3 text-sm leading-relaxed text-dtw-ink/80"
                        >
                            <span class="mr-2 font-semibold text-dtw-primary-soft">{{ t('Prizes') }}:</span>
                            {{ featured.prizesSummary }}
                        </p>

                        <span
                            class="mt-auto inline-flex w-fit items-center gap-2 rounded-full bg-dtw-primary px-6 py-3 text-sm font-semibold text-white transition group-hover:bg-dtw-primary-deep"
                        >
                            {{ featured.canRegister ? t('Register now') : t('See event') }}
                            <i class="pi pi-arrow-right text-xs"></i>
                        </span>
                    </div>
                </Link>

                <div v-if="history.length" class="flex flex-col gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-dtw-dim">
                        {{ t('Previous editions') }}
                    </p>

                    <Link
                        v-for="event in history"
                        :key="event.id"
                        :href="event.href"
                        class="group flex items-center gap-4 rounded-2xl border border-white/10 bg-white/[0.02] p-3 transition hover:border-white/25 hover:bg-white/[0.05]"
                    >
                        <div class="h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-dtw-surface-soft">
                            <img
                                v-if="event.image"
                                :src="event.image"
                                :alt="event.title"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>

                        <div class="min-w-0">
                            <p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-dtw-dim">
                                {{ event.eventDateLabel }}
                            </p>
                            <p
                                class="mt-1 line-clamp-2 text-sm font-semibold text-white transition group-hover:text-dtw-primary-soft"
                            >
                                {{ event.title }}
                            </p>
                            <p v-if="event.winners.length" class="mt-1 truncate text-xs text-dtw-muted">
                                {{ t('Winners') }}: {{ event.winners.map((winner) => winner.name).join(', ') }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </section>
</template>
