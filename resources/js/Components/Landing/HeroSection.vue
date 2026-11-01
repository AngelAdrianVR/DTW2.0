<script setup>
import { trackCtaClick } from '../../utils/analytics';

function onPrimaryClick() {
    trackCtaClick('hero_calculator');
}

function onSecondaryClick() {
    trackCtaClick('hero_diagnosis');
}
</script>

<template>
    <section id="top" class="relative overflow-hidden bg-dtw-dark pb-16 pt-28 sm:pt-32 lg:pb-24 lg:pt-36">
        <!-- Fondo: rejilla + halos de marca (decorativo) -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-dtw-grid bg-dtw-grid opacity-70"></div>
            <div class="absolute -top-40 left-1/2 h-[34rem] w-[34rem] -translate-x-1/2 rounded-full bg-dtw-primary/25 blur-3xl"></div>
            <div class="absolute -bottom-24 right-0 h-72 w-72 rounded-full bg-dtw-primary-soft/20 blur-3xl"></div>
            <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-dtw-dark to-transparent"></div>
        </div>

        <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-4 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:px-8">
            <!-- Columna de mensaje -->
            <div>
                <p
                    class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-white/70"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full rounded-full bg-dtw-primary-soft opacity-75 motion-safe:animate-pulse-ring"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-dtw-primary-soft"></span>
                    </span>
                    {{ t('Automation and software for growing companies') }}
                </p>

                <h1
                    class="mt-6 font-display text-4xl font-extrabold leading-[1.08] tracking-tight text-white sm:text-5xl lg:text-[3.35rem]"
                >
                    {{ t('Stop losing money to manual processes') }}
                    <span
                        class="mt-2 block bg-gradient-to-r from-dtw-primary-soft via-white to-dtw-primary-soft bg-clip-text text-transparent"
                    >
                        {{ t('We automate what slows your operation down') }}
                    </span>
                </h1>

                <p class="mt-6 max-w-xl text-base leading-relaxed text-white/70 sm:text-lg">
                    {{ t('We design custom software, integrations and automations that remove repetitive work, recover sales and give you real visibility over your operation. No empty promises: first we measure how much you are losing.') }}
                </p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <a
                        href="#calculadora"
                        class="inline-flex items-center justify-center gap-2 rounded-full bg-dtw-primary px-6 py-3.5 text-sm font-semibold text-white shadow-dtw-glow transition hover:bg-dtw-primary-deep"
                        @click="onPrimaryClick"
                    >
                        <i class="pi pi-calculator text-sm" aria-hidden="true"></i>
                        {{ t('Calculate how much you are losing') }}
                    </a>
                    <a
                        href="#contacto"
                        class="inline-flex items-center justify-center gap-2 rounded-full border border-white/20 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10"
                        @click="onSecondaryClick"
                    >
                        <i class="pi pi-comments text-sm" aria-hidden="true"></i>
                        {{ t('Request a free diagnosis') }}
                    </a>
                </div>

                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-3 text-sm text-white/60">
                    <li class="flex items-center gap-2">
                        <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                        {{ t('30-minute diagnosis, no cost') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                        {{ t('Proposal with an estimated ROI') }}
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                        {{ t('Delivery in stages, without stopping your operation') }}
                    </li>
                </ul>
            </div>

            <!-- Columna visual: caos vs control -->
            <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                <div class="rounded-3xl border border-white/10 bg-white/[0.03] p-4 shadow-dtw-soft backdrop-blur-sm sm:p-5">
                    <!-- Antes -->
                    <div class="rounded-2xl border border-dtw-danger/25 bg-dtw-danger/[0.07] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-dtw-danger">
                                {{ t('Without automation') }}
                            </p>
                            <span class="rounded-full bg-dtw-danger/15 px-2.5 py-1 text-[0.65rem] font-semibold text-dtw-danger">
                                {{ t('Today') }}
                            </span>
                        </div>
                        <ul class="mt-3 space-y-2 text-sm text-white/75">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-times-circle text-dtw-danger" aria-hidden="true"></i>
                                {{ t('Quotes and inventory in spreadsheets') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-times-circle text-dtw-danger" aria-hidden="true"></i>
                                {{ t('Follow-up depending on memory or WhatsApp') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-times-circle text-dtw-danger" aria-hidden="true"></i>
                                {{ t('The same data typed into several systems') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-times-circle text-dtw-danger" aria-hidden="true"></i>
                                {{ t('Reports nobody has time to build') }}
                            </li>
                        </ul>
                        <p class="mt-3 border-t border-white/10 pt-3 text-xs text-white/50">
                            {{ t('Result: you decide with outdated information and lose sales you never see.') }}
                        </p>
                    </div>

                    <div class="flex justify-center py-3" aria-hidden="true">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-dtw-primary-soft"
                        >
                            <i class="pi pi-arrow-down"></i>
                        </span>
                    </div>

                    <!-- Después -->
                    <div class="rounded-2xl border border-dtw-success/25 bg-dtw-success/[0.07] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-dtw-success">
                                {{ t('With DTW') }}
                            </p>
                            <span class="rounded-full bg-dtw-success/15 px-2.5 py-1 text-[0.65rem] font-semibold text-dtw-success">
                                {{ t('Measurable') }}
                            </span>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-dtw-dark/60 p-3">
                                <dt class="text-[0.65rem] uppercase tracking-wide text-white/50">{{ t('Recovered sales') }}</dt>
                                <dd class="mt-1 font-display text-xl font-bold text-white">+32%</dd>
                            </div>
                            <div class="rounded-xl bg-dtw-dark/60 p-3">
                                <dt class="text-[0.65rem] uppercase tracking-wide text-white/50">{{ t('Hours saved') }}</dt>
                                <dd class="mt-1 font-display text-xl font-bold text-white">64 h</dd>
                            </div>
                        </dl>

                        <ul class="mt-4 space-y-2 text-sm text-white/75">
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                                {{ t('Connected processes and a single source of truth') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                                {{ t('Automatic alerts before problems cost you money') }}
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-dtw-success" aria-hidden="true"></i>
                                {{ t('Real-time dashboards for every decision') }}
                            </li>
                        </ul>
                        <p class="mt-3 border-t border-white/10 pt-3 text-[0.7rem] text-white/60">
                            {{ t('Illustrative figures from automation projects; they are not a guarantee of results.') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
