<script setup>
import { usePage } from '@inertiajs/vue3'
import { ArrowUpRight as ArrowUpRightIcon } from '@lucide/vue'
import { computed } from 'vue'
import RevealGroup from '@/Components/motion/RevealGroup.vue'
import { MotionConfig, motion } from 'motion-v'
import { easeOut } from '@/Support/motion'

/**
 * Plano sign-in: a white page with an ink panel set on it. The panel always
 * carries the dark tokens; the sample's journey runs along the foot.
 */
const props = defineProps({
  title: { type: String, required: true },
  accent: { type: String, default: '' },
  eyebrow: { type: String, default: 'Acesso seguro' },
  description: { type: String, default: 'Acesso controlado a operações laboratoriais e registos auditáveis.' },
  contextTitle: { type: String, default: 'Conformidade operacional' },
  contextDescription: { type: String, default: 'A autenticação protege dados técnicos, aprovações e documentos emitidos.' },
  mode: { type: String, default: 'staff' },
})

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
// White-label settings keep the client's name and logo; colour stays VAP (Plano: colour is information).
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
const modeLabel = computed(() => props.mode === 'portal' ? 'Portal do cliente' : 'Acesso do laboratório')
const portalHref = computed(() => {
  if (props.mode === 'portal' || brandSettings.value.portal_enabled === false) {
    return null
  }

  try {
    return route('portal.login')
  } catch {
    return null
  }
})
const currentYear = new Date().getFullYear()

// The journey every sample takes through the system, traced once on arrival.
const lifecycle = [
  { label: 'Recepção', claim: 'Registo único', note: 'Uma amostra, um dossier. Sem folhas paralelas.' },
  { label: 'Análise', claim: 'Três etapas', note: 'Inserção, verificação e aprovação, cada uma com autor e data.' },
  { label: 'Verificação', claim: 'Prazos visíveis', note: 'Cada fila mostra o que vence primeiro.' },
  { label: 'Aprovação', claim: 'Auditável', note: 'Toda a alteração guarda autor, hora e motivo.' },
  { label: 'Certificado', claim: 'Certificado assinado', note: 'Emitido só depois da aprovação técnica.' },
]
</script>

<template>
  <div
    class="auth-canvas min-h-dvh"
    :data-theme-preset="themePreset"
  >
    <header class="auth-bar">
      <img v-if="brandLogoUrl" :src="brandLogoUrl" :alt="brandAppName" :data-initials="brandInitials" />
      <template v-else>
        <img class="auth-logo-light" src="/brand/svg/VAP_Small.svg" alt="VAP Sistemas" :data-initials="brandInitials" />
        <img class="auth-logo-dark" src="/brand/svg/VAP_Small_White.svg" alt="VAP Sistemas" :data-initials="brandInitials" />
      </template>
      <p class="pl-k auth-bar-label">LIMS · {{ brandLabName }}</p>
      <div class="auth-bar-end">
        <a v-if="portalHref" :href="portalHref" class="auth-portal">Portal do cliente<ArrowUpRightIcon aria-hidden="true" /></a>
        <span v-else class="pl-k">{{ modeLabel }}</span>
      </div>
    </header>

    <main class="auth-main">
      <div class="auth-sheet">
        <section class="auth-form">
          <RevealGroup :step="0.06">
            <div class="mb-9 flex justify-between gap-4"><span class="pl-k pl-muted">{{ eyebrow }}</span><span class="pl-k pl-muted">{{ modeLabel }}</span></div>
            <div class="mb-8">
              <h1 class="auth-title"><span v-if="accent" class="auth-title-accent">{{ accent }}</span>{{ title }}</h1>
              <p class="mt-4 max-w-[46ch] text-[15px] leading-6 text-[var(--pl-muted)]">{{ description }}</p>
            </div>

            <slot />

            <p class="pl-k pl-muted pl-prompt mt-8">{{ contextTitle }} · Sessão registada na pista de auditoria</p>
            <p class="sr-only">{{ contextDescription }}</p>
          </RevealGroup>
        </section>
      </div>

      <div class="auth-claims" aria-hidden="true">
        <span class="pl-k">{{ brandAppName }}</span>
        <span class="pl-k">{{ brandSlogan }}</span>
        <span class="pl-k">&copy; {{ currentYear }}</span>
      </div>
    </main>

    <MotionConfig reduced-motion="user">
      <ol class="auth-trace" :aria-label="`Percurso de uma amostra em ${brandLabName}`">
        <motion.li
          v-for="(step, index) in lifecycle"
          :key="step.label"
          class="auth-trace-step"
          :initial="{ opacity: 0, transform: 'translateY(6px)' }"
          :animate="{ opacity: 1, transform: 'translateY(0px)' }"
          :transition="{ duration: 0.4, ease: easeOut, delay: 0.2 + index * 0.12 }"
        >
          <span class="pl-k">{{ step.label }}</span>
          <p class="auth-trace-note"><b>{{ step.claim }}</b>{{ step.note }}</p>
        </motion.li>
      </ol>
    </MotionConfig>
  </div>
</template>
