<template>
  <div
    class="auth-experience-shell relative min-h-screen overflow-hidden bg-[#07110f] text-[#f7f1e7]"
    :class="commercialDocumentThemeClasses"
  >
    <div class="absolute inset-0">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(31,122,104,0.38),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(217,176,95,0.18),transparent_32%)]"></div>
      <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-[#fffaf0]/10 to-transparent"></div>
      <div class="absolute left-1/2 top-1/2 h-[42rem] w-[42rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#ded3bf]/10"></div>
      <div class="absolute left-1/2 top-1/2 h-[28rem] w-[28rem] -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#ded3bf]/10"></div>
    </div>

    <main class="relative mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-8 sm:px-6 lg:px-8">
      <div class="grid w-full items-center gap-8 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,0.8fr)]">
        <section class="hidden rounded-[2rem] border border-[#ded3bf]/15 bg-[#fffaf0]/[0.07] p-8 shadow-2xl shadow-black/30 backdrop-blur-xl lg:block">
          <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#fffaf0] text-[#143d37] shadow-xl shadow-black/20">
              <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75m6-3.75v5.25c0 5.385-3.72 10.035-9 11.25-5.28-1.215-9-5.865-9-11.25V6l9-3 9 3Z" />
              </svg>
            </div>
            <div>
              <p class="text-sm font-black uppercase tracking-[0.28em] text-accent-100">
                {{ eyebrow }}
              </p>
              <h1 class="mt-2 text-3xl font-black tracking-tight text-white xl:text-5xl">
                {{ title }}
              </h1>
            </div>
          </div>

          <p class="mt-8 max-w-2xl text-lg leading-8 text-[#d9e4dc]">
            {{ description }}
          </p>

          <div class="mt-10 grid gap-4 sm:grid-cols-3">
            <div
              v-for="item in metrics"
              :key="item.label"
              class="rounded-3xl border border-[#ded3bf]/15 bg-[#fffaf0]/10 p-5"
            >
              <p class="text-2xl font-black text-white">
                {{ item.value }}
              </p>
              <p class="mt-2 text-xs font-bold uppercase tracking-[0.2em] text-[#cbd8cf]">
                {{ item.label }}
              </p>
            </div>
          </div>

          <div class="mt-10 rounded-3xl border border-[#ded3bf]/15 bg-[#07110f]/45 p-6">
            <div class="flex items-start gap-4">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[#fffaf0]/10 text-accent-100">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </div>
              <div>
                <h2 class="text-base font-bold text-white">
                  {{ contextTitle }}
                </h2>
                <p class="mt-2 text-sm leading-6 text-[#cbd8cf]">
                  {{ contextDescription }}
                </p>
              </div>
            </div>
          </div>
        </section>

        <section class="mx-auto w-full max-w-md">
          <div class="mb-6 flex items-center justify-between gap-4 lg:hidden">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.24em] text-accent-100">
                {{ eyebrow }}
              </p>
              <h1 class="mt-2 text-2xl font-black tracking-tight text-white">
                {{ title }}
              </h1>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fffaf0] text-[#143d37]">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9 12.75 11.25 15 15 9.75m6-3.75v5.25c0 5.385-3.72 10.035-9 11.25-5.28-1.215-9-5.865-9-11.25V6l9-3 9 3Z" />
              </svg>
            </div>
          </div>

          <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0]/96 shadow-2xl shadow-black/30 backdrop-blur-xl dark:border-[#25443c] dark:bg-[#0c1714]/95">
            <div class="border-b border-[#ded3bf] bg-gradient-to-r from-[#fffdf7] to-[#eef7f3] p-6 dark:border-[#25443c] dark:from-[#0c1714] dark:to-[#10231f]">
              <div class="flex items-center gap-4">
                <img
                  v-if="brandLogoUrl"
                  class="h-12 max-w-44 object-contain"
                  :src="brandLogoUrl"
                  :alt="brandAppName"
                />
                <div v-else class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#143d37] text-white">
                  <span class="text-sm font-black">GL</span>
                </div>
                <div>
                  <p class="text-xs font-black uppercase tracking-[0.22em] text-[#143d37] dark:text-accent-200">
                    {{ modeLabel }}
                  </p>
                  <p class="mt-1 text-sm font-semibold text-[#475a53] dark:text-[#d9e4dc]">
                    {{ brandAppName }}
                  </p>
                </div>
              </div>
            </div>

            <div class="p-6 sm:p-8">
              <slot />
            </div>
          </div>

          <p class="mt-5 text-center text-xs leading-5 text-[#cbd8cf]">
            Acesso protegido com sessão segura, rastreabilidade e políticas de conformidade.
          </p>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  eyebrow: {
    type: String,
    default: 'Acesso seguro',
  },
  description: {
    type: String,
    default: 'Entre num ambiente desenhado para rastreabilidade, confidencialidade, validação técnica e operação laboratorial sem fricção.',
  },
  contextTitle: {
    type: String,
    default: 'Conformidade desde o primeiro clique',
  },
  contextDescription: {
    type: String,
    default: 'Os fluxos de autenticação preservam a experiência premium do sistema e reforçam segurança, auditoria e responsabilidade operacional.',
  },
  mode: {
    type: String,
    default: 'staff',
  },
})

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
const brandLogoUrl = computed(() => brandSettings.value.logo_url ?? null)
const brandAppName = computed(() => brandSettings.value.app_name ?? 'GestLab')
const modeLabel = computed(() => props.mode === 'portal' ? 'Portal do cliente' : 'Área interna')

const metrics = [
  { value: 'ISO', label: '17025 ready' },
  { value: '2FA', label: 'Passkeys e OTP' },
  { value: 'QMS', label: 'Evidência auditável' },
]
</script>
