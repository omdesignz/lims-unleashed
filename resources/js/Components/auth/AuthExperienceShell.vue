<script setup>
import { usePage } from '@inertiajs/vue3'
import { ShieldCheck as ShieldCheckIcon } from '@lucide/vue'
import { computed } from 'vue'
import { buildBrandingCssVariables } from '@/Utils/brandingPalette'
import BrandMark from '@/Components/brand/BrandMark.vue'
import RevealGroup from '@/Components/motion/RevealGroup.vue'
import { MotionConfig, motion } from 'motion-v'
import { easeOut } from '@/Support/motion'

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

// The journey every sample takes through the system, traced once on arrival.
const lifecycle = [
  { label: 'Recepção', note: 'Registo e numeração por laboratório.' },
  { label: 'Análise', note: 'Resultados inseridos por parâmetro.' },
  { label: 'Verificação', note: 'Segunda leitura antes de avançar.' },
  { label: 'Aprovação', note: 'Libertação pelo responsável técnico.' },
  { label: 'Certificado', note: 'Documento emitido e assinado.' },
]
const stepNumber = (index) => String(index + 1).padStart(2, '0')
</script>

<template>
  <div
    class="auth-canvas min-h-dvh bg-[var(--ds-canvas)] text-[var(--ds-text)]"
    :style="brandingCssVariables"
    :data-theme-preset="themePreset"
  >
    <header class="auth-bar">
      <div class="flex min-w-0 items-center gap-3">
        <BrandMark v-if="!brandLogoUrl" :width="54" />
        <img v-else class="max-h-9 max-w-40 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
        <span class="h-5 w-px shrink-0 bg-[var(--ds-border-strong)]" aria-hidden="true" />
        <p class="min-w-0 truncate text-[0.9375rem] font-semibold tracking-tight" :data-initials="brandInitials">{{ brandAppName }}</p>
      </div>
      <p class="flex shrink-0 items-center gap-2 text-[0.8125rem] text-[var(--ds-text-muted)]">
        <span class="h-1.5 w-1.5 rounded-full bg-[var(--lims-release)]" aria-hidden="true" />
        {{ modeLabel }}
      </p>
    </header>

    <main class="auth-main">
      <div class="auth-sheet">
        <section class="auth-form">
          <RevealGroup :step="0.06">
            <div class="mb-7">
              <h1 class="auth-title">{{ title }}</h1>
              <p class="mt-2 text-[0.9375rem] leading-6 text-[var(--ds-text-muted)]">{{ description }}</p>
            </div>

            <slot />
          </RevealGroup>
        </section>

        <MotionConfig reduced-motion="user">
          <aside class="auth-trace" :aria-label="`Percurso de uma amostra em ${brandLabName}`">
            <div>
              <p class="text-[0.8125rem] text-[#70b5ff]">{{ brandLabName }}</p>
              <p class="mt-2 max-w-[18rem] text-[1.375rem] font-medium leading-[1.2] tracking-[-0.02em] text-white">Da recepção ao certificado, cada amostra deixa rasto.</p>
            </div>

            <ol class="auth-trace-steps">
              <motion.span
                class="auth-trace-line"
                aria-hidden="true"
                :initial="{ scaleY: 0 }"
                :animate="{ scaleY: 1 }"
                :transition="{ duration: 1.2, ease: [0.4, 0, 0.2, 1], delay: 0.3 }"
              />
              <motion.li
                v-for="(step, index) in lifecycle"
                :key="step.label"
                class="auth-trace-step"
                :data-final="index === lifecycle.length - 1"
                :initial="{ opacity: 0, transform: 'translateY(6px)' }"
                :animate="{ opacity: 1, transform: 'translateY(0px)' }"
                :transition="{ duration: 0.4, ease: easeOut, delay: 0.35 + index * 0.2 }"
              >
                <span class="auth-trace-node" aria-hidden="true" />
                <span class="auth-trace-index">{{ stepNumber(index) }}</span>
                <span>
                  <span class="block text-sm font-medium text-white">{{ step.label }}</span>
                  <span class="block text-[0.8125rem] leading-5 text-white/60">{{ step.note }}</span>
                </span>
              </motion.li>
            </ol>

            <div class="flex items-start gap-2.5 border-t border-white/10 pt-4">
              <ShieldCheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-[#70b5ff]" aria-hidden="true" />
              <p class="text-[0.8125rem] leading-5 text-white/60"><span class="font-medium text-white">{{ contextTitle }}.</span> {{ contextDescription }}</p>
            </div>
          </aside>
        </MotionConfig>
      </div>
    </main>

    <footer class="auth-bar text-xs text-[var(--ds-text-soft)]">
      <span>{{ brandAppName }} &copy; {{ currentYear }}</span>
      <span class="hidden sm:inline">{{ brandSlogan }}</span>
    </footer>
  </div>
</template>
