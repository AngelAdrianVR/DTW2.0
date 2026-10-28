<script setup>
// BenefitsSection.vue — Vue 3 + Inertia adaptation
// Dark background, white text. Three-card grid with moving blob/video.

defineProps({
  videoUrl: {
    type: String,
    default: 'https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260421_072701_f6a01abb-eb30-4559-9d6e-774362defbc3.mp4',
  },
});
</script>

<template>
  <section class="relative w-full bg-[#050510] px-4 sm:px-6 md:px-10 py-12 sm:py-20">
    <!-- Section Heading -->
    <h2
      class="text-white text-3xl sm:text-4xl md:text-5xl font-light text-center mb-12 sm:mb-24"
      style="letter-spacing: -0.04em;"
    >
      {{ t('Key Benefits') }}
    </h2>

    <!-- Three-Column Card Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4 max-w-[1400px] mx-auto">

      <!-- ═══ Card 1: Text Card (Left) ═══ -->
      <div class="relative h-[380px] sm:h-[460px] rounded-2xl bg-[#0a0a14] overflow-hidden p-6 sm:p-8 border border-white/5 hover:border-violet-500/30 transition-all duration-500 group">
        <!-- Purple Blob -->
        <div
          class="absolute top-1/2 -translate-y-1/2 -left-[420px] h-[460px] w-[460px] rounded-full blur-3xl opacity-30 group-hover:opacity-40 transition-opacity duration-700"
          style="background-color: #7c3aed;"
        ></div>
        <!-- Amber Blob -->
        <div
          class="absolute -bottom-20 left-1/3 h-32 w-32 rounded-full blur-2xl opacity-15"
          style="background-color: #eab308;"
        ></div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col h-full">
          <h3 class="text-white text-xl sm:text-2xl font-light leading-tight">
            {{ t('Preemptive Risks') }}<br />{{ t('Scouting and Reactions') }}
          </h3>
          <p class="mt-12 sm:mt-20 text-[13px] sm:text-[14px] leading-relaxed text-white/70 font-light max-w-[280px]">
            {{ t('Defense platforms constantly observe bandwidth streams, record files, and machine behaviors to uncover unusual patterns or outliers that could signal a defensive failure.') }}
          </p>
        </div>
      </div>

      <!-- ═══ Card 2: Video Card (Center) ═══ -->
      <div class="relative h-[380px] sm:h-[460px] rounded-2xl bg-[#0a0a14] overflow-hidden flex flex-col border border-white/5 hover:border-blue-500/30 transition-all duration-500 group">
        <!-- Top video region (75% height) -->
        <div class="relative w-full overflow-hidden" style="height: 75%;">
          <video
            :src="videoUrl"
            autoplay
            loop
            muted
            playsinline
            class="w-full h-full object-cover block"
          ></video>
          <!-- Bottom fade overlay -->
          <div
            class="pointer-events-none absolute bottom-0 left-0 right-0 h-32"
            style="background: linear-gradient(to bottom, transparent, #0a0a14);"
          ></div>
        </div>

        <!-- Bottom text region -->
        <div class="flex-1 flex items-center justify-start p-6 sm:p-8">
          <h3 class="text-white text-xl sm:text-2xl font-light leading-tight text-left">
            {{ t('Know-how and Sectoral') }}<br />{{ t('Awareness') }}
          </h3>
        </div>
      </div>

      <!-- ═══ Card 3: Text Card (Right) ═══ -->
      <div class="relative h-[380px] sm:h-[460px] rounded-2xl bg-[#0a0a14] overflow-hidden p-6 sm:p-8 border border-white/5 hover:border-amber-500/30 transition-all duration-500 group">
        <!-- Amber Blob top-right -->
        <div
          class="absolute -top-28 -right-28 h-56 w-56 rounded-full blur-3xl opacity-30 group-hover:opacity-40 transition-opacity duration-700"
          style="background-color: #eab308;"
        ></div>
        <!-- Purple Blob accent -->
        <div
          class="absolute bottom-0 left-0 h-40 w-40 rounded-full blur-2xl opacity-15"
          style="background-color: #7c3aed;"
        ></div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col h-full">
          <h3 class="text-white text-xl sm:text-2xl font-light leading-tight">
            {{ t('Preemptive Risks') }}<br />{{ t('Scouting and Reactions') }}
          </h3>
          <p class="mt-auto text-[13px] sm:text-[14px] leading-relaxed text-white/70 font-light max-w-[320px]">
            {{ t('Defense platforms constantly observe bandwidth streams, record files, and machine behaviors to uncover unusual patterns or outliers that could signal a defensive failure.') }}
          </p>
        </div>
      </div>

    </div>
  </section>
</template>
