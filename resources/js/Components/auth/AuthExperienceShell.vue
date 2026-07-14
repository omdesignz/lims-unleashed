<script setup>
import { usePage } from '@inertiajs/vue3'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'

const props = defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: 'Acesso seguro' },
  description: { type: String, default: 'Acesso controlado a operações laboratoriais e registos auditáveis.' },
  contextTitle: { type: String, default: 'Conformidade operacional' },
  contextDescription: { type: String, default: 'A autenticação protege dados técnicos, aprovações e documentos emitidos.' },
  mode: { type: String, default: 'staff' },
})

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
const brandingCssVariables = computed(() => buildBrandingCssVariables(brandSettings.value))
const themePreset = computed(() => brandSettings.value.theme_preset || brandSettings.value.app_theme_preset || 'corporate')
const brandLogoUrl = computed(() => brandSettings.value.logo_url || brandSettings.value.app_logo_url || null)
const brandAppName = computed(() => brandSettings.value.app_name || 'Espaço laboratorial')
const brandLabName = computed(() => brandSettings.value.lab_name || brandAppName.value)
const brandSlogan = computed(() => brandSettings.value.app_slogan || 'Rastreabilidade, qualidade e conformidade laboratorial.')
const brandInitials = computed(() => brandAppName.value
  .split(/\s+/)
  .filter(Boolean)
  .slice(0, 2)
  .map((word) => word.charAt(0).toUpperCase())
  .join('') || 'LW')
const modeLabel = computed(() => props.mode === 'portal' ? 'Portal do cliente' : 'Área interna')
const currentYear = new Date().getFullYear()
</script>

<template>
  <div
    class="min-h-dvh bg-[var(--ds-panel)] text-[var(--ds-text)]"
    :style="brandingCssVariables"
    :data-theme-preset="themePreset"
  >
    <main class="grid min-h-dvh lg:grid-cols-[minmax(0,0.9fr)_minmax(28rem,0.62fr)]">
      <section class="relative hidden overflow-hidden bg-[var(--brand-secondary)] p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
        <div class="absolute inset-y-0 right-0 w-px bg-white/10" aria-hidden="true" />
        <div class="absolute inset-x-10 top-32 border-t border-white/10 xl:inset-x-14" aria-hidden="true" />
        <div class="absolute inset-x-10 bottom-32 border-t border-white/10 xl:inset-x-14" aria-hidden="true" />

        <div class="relative flex min-w-0 items-center gap-4">
          <span v-if="!brandLogoUrl" class="grid h-12 w-12 shrink-0 place-items-center rounded-lg bg-white text-sm font-bold text-[var(--brand-secondary)]">
            {{ brandInitials }}
          </span>
          <span v-else class="flex h-12 max-w-52 items-center rounded-lg bg-white px-3 py-2">
            <img class="max-h-8 max-w-44 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
          </span>
          <div class="min-w-0">
            <p class="truncate text-base font-semibold">{{ brandAppName }}</p>
            <p class="mt-0.5 truncate text-sm text-white/60">{{ brandLabName }}</p>
          </div>
        </div>

        <div class="relative max-w-2xl py-20">
          <p class="font-mono text-xs font-semibold uppercase text-[var(--brand-accent)]">{{ eyebrow }}</p>
          <h1 class="mt-5 max-w-xl text-4xl font-semibold leading-tight text-white xl:text-5xl">{{ title }}</h1>
          <p class="mt-5 max-w-xl text-base leading-7 text-white/70">{{ description }}</p>

          <div class="mt-10 grid max-w-xl grid-cols-4 gap-2" aria-hidden="true">
            <span class="h-1 bg-[var(--brand-accent)]" />
            <span class="h-1 bg-white/60" />
            <span class="h-1 bg-white/30" />
            <span class="h-1 bg-white/15" />
          </div>
        </div>

        <div class="relative flex items-start gap-3 border-t border-white/10 pt-6">
          <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--brand-accent)]" aria-hidden="true" />
          <div>
            <p class="text-sm font-semibold text-white">{{ contextTitle }}</p>
            <p class="mt-1 max-w-xl text-sm leading-6 text-white/60">{{ contextDescription }}</p>
          </div>
        </div>
      </section>

      <section class="flex min-h-dvh flex-col bg-[var(--ds-panel)]">
        <header class="flex min-h-16 items-center justify-between gap-4 border-b border-[var(--ds-border)] px-5 sm:px-8 lg:border-b-0 lg:px-10">
          <div class="flex min-w-0 flex-1 items-center gap-3 lg:hidden">
            <span v-if="!brandLogoUrl" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--brand-secondary)] text-xs font-bold text-white">
              {{ brandInitials }}
            </span>
            <img v-else class="max-h-9 max-w-36 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
            <p class="min-w-0 flex-1 truncate text-sm font-semibold">{{ brandAppName }}</p>
          </div>
          <span class="ml-auto inline-flex shrink-0 items-center gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true" />
            {{ modeLabel }}
          </span>
        </header>

        <div class="flex flex-1 items-center px-5 py-10 sm:px-8 lg:px-10 lg:py-14 xl:px-16">
          <div class="mx-auto w-full max-w-sm">
            <div class="mb-8 lg:hidden">
              <p class="ds-kicker">{{ eyebrow }}</p>
              <h1 class="ds-heading mt-2 text-2xl">{{ title }}</h1>
              <p class="ds-copy mt-2 text-sm leading-6">{{ description }}</p>
            </div>

            <slot />
          </div>
        </div>

        <footer class="flex items-center justify-between gap-4 border-t border-[var(--ds-border)] px-5 py-4 text-xs font-medium text-[var(--ds-text-soft)] sm:px-8 lg:px-10">
          <span>{{ brandAppName }} &copy; {{ currentYear }}</span>
          <span class="hidden sm:inline">{{ brandSlogan }}</span>
        </footer>
      </section>
    </main>
  </div>
</template>
