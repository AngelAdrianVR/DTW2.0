<script setup>
/**
 * Banda de clientes: sustituye al antiguo bloque de contacto del final del
 * inicio. Pinta los logos que el back-office guarda como `client_logos`
 * (lista plana `{ name, url }`) en una marquesina continua.
 *
 * Si todavía no hay logos publicados se muestra un aviso neutro en lugar de
 * dejar el hueco vacío.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useT } from '@/Composables/useT';

const props = defineProps({
    logos: { type: Array, default: () => [] },
});

const t = useT();
const page = usePage();

const items = computed(() => {
    const logos = props.logos?.length ? props.logos : page.props.clientLogos || [];

    return logos.filter((logo) => logo?.url);
});
</script>

<template>
    <section id="clientes" class="relative overflow-hidden py-20 md:py-24">
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/12 to-transparent"
        ></div>

        <div class="relative mx-auto w-full max-w-5xl px-5 text-center md:px-8" v-reveal>
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-dtw-primary-soft">
                {{ t('Clients') }}
            </p>

            <h2
                class="mt-4 font-display text-[clamp(1.6rem,3.2vw,2.5rem)] font-extrabold leading-tight tracking-tight text-white"
            >
                {{ t('Companies that trust us') }}
            </h2>

            <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-dtw-muted md:text-base">
                {{ t('Some of the teams we have built software for.') }}
            </p>
        </div>

        <!-- Marquesina de logos -->
        <div
            v-if="items.length"
            class="dtw-marquee relative mt-12 overflow-hidden motion-reduce:overflow-x-auto md:mt-16"
        >
            <div class="dtw-marquee__track">
                <!-- La lista se pinta dos veces para que el bucle cierre sin salto -->
                <div
                    v-for="group in 2"
                    :key="`group-${group}`"
                    class="flex shrink-0 items-center gap-10 px-5 md:gap-14 md:px-7"
                    :aria-hidden="group === 2 ? 'true' : undefined"
                >
                    <div
                        v-for="(logo, index) in items"
                        :key="`logo-${group}-${index}`"
                        class="flex h-16 w-[8.5rem] shrink-0 items-center justify-center md:h-20 md:w-40"
                    >
                        <img
                            :src="logo.url"
                            :alt="group === 2 ? '' : logo.name || ''"
                            loading="lazy"
                            draggable="false"
                            class="max-h-10 w-auto max-w-full object-contain opacity-55 transition duration-300 hover:scale-105 hover:opacity-100 md:max-h-12"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Sin logos publicados todavía -->
        <div v-else class="relative mx-auto mt-12 w-full max-w-5xl px-5 md:mt-16 md:px-8">
            <div
                class="rounded-[26px] border border-dashed border-white/15 bg-white/[0.02] px-8 py-10 text-sm text-dtw-muted"
            >
                {{ t('Client logos are on their way.') }}
            </div>
        </div>
    </section>
</template>
