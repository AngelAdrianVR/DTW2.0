<script setup>
/**
 * Tarjeta de evento para la galería de /eventos.
 * Recibe el evento ya normalizado y traducido por useEvents().
 */
import { Link } from '@inertiajs/vue3';
import EventStatusBadge from './EventStatusBadge.vue';
import { useT } from '@/Composables/useT';

defineProps({
    event: { type: Object, required: true },
});

const t = useT();
</script>

<template>
    <Link
        :href="event.href"
        class="group flex h-full flex-col overflow-hidden rounded-[26px] border border-white/10 bg-white/[0.03] transition duration-300 hover:-translate-y-1 hover:border-white/25 hover:bg-white/[0.06]"
    >
        <div class="relative aspect-[16/10] w-full overflow-hidden">
            <img
                v-if="event.image"
                :src="event.image"
                :alt="event.title"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.04]"
                loading="lazy"
            />
            <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-dtw-brand/30 via-dtw-surface-soft to-dtw-void"
            >
                <img src="/images/white_logo.png" alt="" class="h-8 w-auto opacity-40" />
            </div>

            <div class="pointer-events-none absolute left-4 top-4">
                <EventStatusBadge :status="event.status" />
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-5">
            <p v-if="event.eventDateLabel" class="text-xs font-medium uppercase tracking-[0.18em] text-dtw-dim">
                {{ event.eventDateLabel }}
            </p>

            <h3
                class="line-clamp-2 font-display text-lg font-bold leading-snug text-white transition group-hover:text-dtw-primary-soft"
            >
                {{ event.title }}
            </h3>

            <p v-if="event.prizesSummary" class="line-clamp-2 text-sm leading-relaxed text-dtw-muted">
                {{ event.prizesSummary }}
            </p>

            <span
                class="mt-auto inline-flex items-center gap-2 pt-2 text-sm font-semibold text-dtw-primary-soft transition group-hover:text-white"
            >
                {{ event.canRegister ? t('Register now') : t('See event') }}
                <i class="pi pi-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-0.5"></i>
            </span>
        </div>
    </Link>
</template>
