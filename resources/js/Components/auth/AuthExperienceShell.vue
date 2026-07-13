<script setup>
import { usePage } from '@inertiajs/vue3'
import {
  BeakerIcon,
  ClipboardDocumentCheckIcon,
  LockClosedIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  eyebrow: { type: String, default: 'Acesso seguro' },
  description: { type: String, default: 'Acesso controlado a operacoes laboratoriais e registos auditaveis.' },
  contextTitle: { type: String, default: 'Conformidade operacional' },
  contextDescription: { type: String, default: 'A autenticacao protege dados tecnicos, aprovacoes e documentos emitidos.' },
  mode: { type: String, default: 'staff' },
})

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
const brandLogoUrl = computed(() => brandSettings.value.logo_url ?? null)
const brandAppName = computed(() => brandSettings.value.app_name ?? 'GestLab')
const modeLabel = computed(() => props.mode === 'portal' ? 'Portal do cliente' : 'Area interna')

const assurances = [
  { title: 'Sessao protegida', description: 'Controlo de acesso e confirmacao de identidade.', icon: LockClosedIcon },
  { title: 'Rastreabilidade', description: 'Operacoes associadas ao utilizador autenticado.', icon: ClipboardDocumentCheckIcon },
  { title: 'Contexto laboratorial', description: 'Acesso aos fluxos autorizados do LIMS.', icon: BeakerIcon },
]
</script>

<template>
  <div class="ds-app-canvas min-h-screen">
    <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel)]">
      <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
          <img v-if="brandLogoUrl" class="h-9 max-w-36 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
          <span v-else class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[rgb(var(--primary-700-rgb))] text-xs font-black text-white">GL</span>
          <div class="min-w-0">
            <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ brandAppName }}</p>
            <p class="text-xs font-semibold text-[var(--ds-text-muted)]">Laboratory Information Management System</p>
          </div>
        </div>
        <span class="ds-badge bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-1 ring-inset ring-[var(--ds-border)]">{{ modeLabel }}</span>
      </div>
    </header>

    <main class="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-7xl items-center gap-10 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,28rem)] lg:px-8 lg:py-12">
      <section class="hidden lg:block">
        <p class="ds-kicker">{{ eyebrow }}</p>
        <h1 class="ds-heading mt-3 max-w-2xl text-4xl">{{ title }}</h1>
        <p class="ds-copy mt-4 max-w-2xl text-base leading-7">{{ description }}</p>

        <div class="mt-8 max-w-2xl divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
          <article v-for="item in assurances" :key="item.title" class="flex items-start gap-4 py-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><component :is="item.icon" class="h-5 w-5" /></span>
            <div><h2 class="text-sm font-bold text-[var(--ds-text)]">{{ item.title }}</h2><p class="ds-copy mt-1 text-sm">{{ item.description }}</p></div>
          </article>
        </div>

        <div class="mt-6 flex max-w-2xl items-start gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
          <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" />
          <div><h2 class="text-sm font-bold text-[var(--ds-text)]">{{ contextTitle }}</h2><p class="ds-copy mt-1 text-sm leading-6">{{ contextDescription }}</p></div>
        </div>
      </section>

      <section class="mx-auto w-full max-w-md">
        <div class="mb-5 lg:hidden">
          <p class="ds-kicker">{{ eyebrow }}</p>
          <h1 class="ds-heading mt-2 text-2xl">{{ title }}</h1>
          <p class="ds-copy mt-2 text-sm">{{ description }}</p>
        </div>

        <div class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3">
              <span class="grid h-9 w-9 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><LockClosedIcon class="h-4 w-4" /></span>
              <div><p class="ds-kicker">{{ modeLabel }}</p><p class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ brandAppName }}</p></div>
            </div>
          </div>
          <div class="p-5 sm:p-6"><slot /></div>
        </div>

        <p class="mt-4 text-center text-xs font-semibold leading-5 text-[var(--ds-text-soft)]">Sessao segura, rastreabilidade e controlo de acesso.</p>
      </section>
    </main>
  </div>
</template>
