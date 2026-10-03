<script setup>
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  RefreshCw as ArrowPathIcon,
  CircleCheck as CheckCircleIcon,
  Monitor as ComputerDesktopIcon,
  Smartphone as DevicePhoneMobileIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import DialogModal from '@/Components/dialog-modal.vue'

defineProps({
  sessions: { type: Array, default: () => [] },
})

const confirmingLogout = ref(false)
const passwordInput = ref(null)
const form = useForm({ password: '' })

async function confirmLogout() {
  confirmingLogout.value = true
  await nextTick()
  passwordInput.value?.focus()
}

function logoutOtherBrowserSessions() {
  form.delete(route('other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: closeModal,
    onError: () => passwordInput.value?.focus(),
    onFinish: () => form.reset(),
  })
}

function closeModal() {
  confirmingLogout.value = false
  form.reset()
  form.clearErrors()
}
</script>

<template>
  <div class="space-y-5">
    <div v-if="sessions.length" class="divide-y divide-[var(--ds-border)] rounded-lg border border-[var(--ds-border)]">
      <div v-for="(session, index) in sessions" :key="session.id ?? index" class="flex items-start gap-3 px-4 py-4">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-1 ring-inset ring-[var(--ds-border)]">
          <ComputerDesktopIcon v-if="session.agent.is_desktop" class="h-4 w-4" />
          <DevicePhoneMobileIcon v-else class="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2">
            <p class="text-sm font-bold text-[var(--ds-text)]">
              {{ session.agent.platform || 'Plataforma desconhecida' }} · {{ session.agent.browser || 'Navegador desconhecido' }}
            </p>
            <span v-if="session.is_current_device" class="ds-chip ds-chip-success">Este dispositivo</span>
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ session.ip_address }} · {{ session.is_current_device ? 'Sessão actual' : `Última actividade ${session.last_active}` }}
          </p>
        </div>
      </div>
    </div>

    <div v-else class="ds-empty-state py-8 text-center">
      <ComputerDesktopIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
      <p class="mt-3 text-sm font-bold text-[var(--ds-text)]">Nenhuma sessão adicional detectada</p>
      <p class="ds-copy mt-1 text-sm">A sessão actual continuará activa.</p>
    </div>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p v-if="form.recentlySuccessful" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
        <CheckCircleIcon class="h-4 w-4" />
        As restantes sessões foram terminadas.
      </p>
      <span v-else />
      <button type="button" class="ds-button ds-button-secondary sm:ml-auto" :disabled="sessions.length <= 1" @click="confirmLogout">
        <ArrowPathIcon class="h-4 w-4" />
        Terminar outras sessões
      </button>
    </div>

    <DialogModal :show="confirmingLogout" max-width="lg" @close="closeModal">
      <template #title>
        <div class="flex items-center justify-between gap-4">
          <span>Confirmar encerramento de sessões</span>
          <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
            <XMarkIcon class="h-5 w-5" />
            <span class="sr-only">Fechar</span>
          </button>
        </div>
      </template>

      <template #content>
        <p>Introduza a sua palavra-passe para terminar todas as outras sessões do navegador.</p>
        <label class="ds-field-group mt-5">
          <span class="ds-field-label">Palavra-passe</span>
          <BaseInput
            ref="passwordInput"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            class="ds-field"
            :aria-invalid="Boolean(form.errors.password)"
            @keyup.enter="logoutOtherBrowserSessions" />
          <span v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</span>
        </label>
      </template>

      <template #footer>
        <button type="button" class="ds-button ds-button-secondary" @click="closeModal">Cancelar</button>
        <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.password" @click="logoutOtherBrowserSessions">
          <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
          {{ form.processing ? 'A terminar...' : 'Terminar sessões' }}
        </button>
      </template>
    </DialogModal>
  </div>
</template>
