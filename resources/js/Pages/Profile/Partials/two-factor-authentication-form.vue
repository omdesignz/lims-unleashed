<script setup>
import { computed, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  CheckBadgeIcon,
  ClipboardDocumentIcon,
  KeyIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  ShieldExclamationIcon,
} from '@heroicons/vue/24/outline'
import ConfirmsPassword from '@/Components/confirms-password.vue'

const props = defineProps({
  enabledAndConfirmed: { type: Boolean, default: false },
})

const page = usePage()
const enabling = ref(false)
const disabling = ref(false)
const confirming = ref(Boolean(page.props.auth?.user?.two_factor_secret) && !props.enabledAndConfirmed)
const loadingSetup = ref(false)
const loadingRecoveryCodes = ref(false)
const qrCode = ref(null)
const setupKey = ref(null)
const recoveryCodes = ref([])
const copiedSetupKey = ref(false)
const errorMessage = ref('')

const confirmationForm = useForm({ code: '' })
const twoFactorEnabled = computed(() => Boolean(page.props.auth?.user?.two_factor_secret))

watch(twoFactorEnabled, (enabled) => {
  if (!enabled) {
    confirming.value = false
    qrCode.value = null
    setupKey.value = null
    recoveryCodes.value = []
    confirmationForm.reset()
    confirmationForm.clearErrors()
  }
})

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function requestJson(url, options = {}) {
  const response = await fetch(url, {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      ...options.headers,
    },
    ...options,
  })

  const payload = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(payload.message || 'Não foi possível concluir a operação.')
  }

  return payload
}

async function loadAuthenticatorSetup() {
  loadingSetup.value = true
  errorMessage.value = ''

  try {
    const [qrPayload, keyPayload] = await Promise.all([
      requestJson(route('two-factor.qr-code')),
      requestJson(route('two-factor.secret-key')),
    ])
    qrCode.value = qrPayload.svg
    setupKey.value = keyPayload.secretKey
  } catch (error) {
    errorMessage.value = error.message
  } finally {
    loadingSetup.value = false
  }
}

function enableTwoFactorAuthentication() {
  enabling.value = true
  errorMessage.value = ''

  router.post(route('two-factor.enable'), {}, {
    preserveScroll: true,
    onSuccess: async () => {
      confirming.value = true
      await loadAuthenticatorSetup()
    },
    onError: () => {
      errorMessage.value = 'Não foi possível activar a autenticação de dois factores.'
    },
    onFinish: () => {
      enabling.value = false
    },
  })
}

function confirmTwoFactorAuthentication() {
  confirmationForm.post(route('two-factor.confirm'), {
    errorBag: 'confirmTwoFactorAuthentication',
    preserveScroll: true,
    onSuccess: async () => {
      confirming.value = false
      qrCode.value = null
      setupKey.value = null
      confirmationForm.reset()
      await showRecoveryCodes()
    },
  })
}

async function showRecoveryCodes() {
  loadingRecoveryCodes.value = true
  errorMessage.value = ''

  try {
    recoveryCodes.value = await requestJson(route('two-factor.recovery-codes'))
  } catch (error) {
    errorMessage.value = error.message
  } finally {
    loadingRecoveryCodes.value = false
  }
}

async function regenerateRecoveryCodes() {
  loadingRecoveryCodes.value = true
  errorMessage.value = ''

  try {
    await requestJson(route('two-factor.regenerate-recovery-codes'), { method: 'POST' })
    await showRecoveryCodes()
  } catch (error) {
    errorMessage.value = error.message
    loadingRecoveryCodes.value = false
  }
}

function disableTwoFactorAuthentication() {
  disabling.value = true
  errorMessage.value = ''

  router.delete(route('two-factor.disable'), {
    preserveScroll: true,
    onError: () => {
      errorMessage.value = 'Não foi possível desactivar a autenticação de dois factores.'
    },
    onFinish: () => {
      disabling.value = false
    },
  })
}

async function copySetupKey() {
  if (!setupKey.value) return

  await navigator.clipboard.writeText(setupKey.value)
  copiedSetupKey.value = true
  window.setTimeout(() => {
    copiedSetupKey.value = false
  }, 2000)
}

function downloadRecoveryCodes() {
  const file = new Blob([recoveryCodes.value.join('\n')], { type: 'text/plain' })
  const downloadUrl = URL.createObjectURL(file)
  const link = document.createElement('a')
  link.href = downloadUrl
  link.download = 'lims-recovery-codes.txt'
  link.click()
  URL.revokeObjectURL(downloadUrl)
}
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-col gap-4 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 ring-inset" :class="twoFactorEnabled ? 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-400/20' : 'bg-white text-[var(--ds-text-muted)] ring-[var(--ds-border)] dark:bg-white/5'">
          <ShieldCheckIcon v-if="twoFactorEnabled" class="h-5 w-5" />
          <ShieldExclamationIcon v-else class="h-5 w-5" />
        </span>
        <div>
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ twoFactorEnabled ? 'Protecção adicional activa' : 'Protecção adicional inactiva' }}</p>
          <p class="ds-copy mt-1 text-sm">{{ twoFactorEnabled ? 'O login exige um código temporário gerado no seu autenticador.' : 'Active um autenticador antes de utilizar a conta fora de dispositivos controlados.' }}</p>
        </div>
      </div>

      <ConfirmsPassword v-if="!twoFactorEnabled" @confirmed="enableTwoFactorAuthentication">
        <button type="button" class="ds-button ds-button-primary shrink-0" :disabled="enabling">
          <ArrowPathIcon v-if="enabling" class="h-4 w-4 animate-spin" />
          <LockClosedIcon v-else class="h-4 w-4" />
          {{ enabling ? 'A activar...' : 'Activar 2FA' }}
        </button>
      </ConfirmsPassword>
    </div>

    <p v-if="errorMessage" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200">{{ errorMessage }}</p>

    <div v-if="confirming" class="space-y-5 rounded-lg border border-[var(--ds-border)] px-4 py-5 sm:px-5">
      <div>
        <p class="text-sm font-bold text-[var(--ds-text)]">Configurar autenticador</p>
        <p class="ds-copy mt-1 text-sm">Digitalize o código QR e introduza o código de seis dígitos para concluir a activação.</p>
      </div>

      <div v-if="loadingSetup" class="flex items-center gap-2 py-8 text-sm font-semibold text-[var(--ds-text-muted)]">
        <ArrowPathIcon class="h-4 w-4 animate-spin" />
        A preparar o código de configuração...
      </div>

      <div v-else-if="qrCode" class="grid gap-5 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-start">
        <div class="w-fit rounded-lg border border-[var(--ds-border)] bg-white p-3 [&_svg]:h-40 [&_svg]:w-40" v-html="qrCode" />
        <div class="min-w-0 space-y-4">
          <div v-if="setupKey" class="ds-field-group">
            <span class="ds-field-label">Chave de configuração manual</span>
            <div class="flex gap-2">
              <code class="min-w-0 flex-1 break-all rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2.5 text-xs font-bold text-[var(--ds-text)]">{{ setupKey }}</code>
              <button type="button" class="ds-icon-button shrink-0" :title="copiedSetupKey ? 'Copiada' : 'Copiar chave'" @click="copySetupKey">
                <CheckBadgeIcon v-if="copiedSetupKey" class="h-4 w-4 text-emerald-600" />
                <ClipboardDocumentIcon v-else class="h-4 w-4" />
                <span class="sr-only">{{ copiedSetupKey ? 'Chave copiada' : 'Copiar chave' }}</span>
              </button>
            </div>
          </div>

          <form class="space-y-3" @submit.prevent="confirmTwoFactorAuthentication">
            <label class="ds-field-group">
              <span class="ds-field-label">Código de verificação</span>
              <BaseInput
                v-model="confirmationForm.code"
                type="text"
                inputmode="numeric"
                autocomplete="one-time-code"
                maxlength="6"
                class="ds-field max-w-48 text-center font-mono text-lg tracking-[0.3em]"
                :aria-invalid="Boolean(confirmationForm.errors.code)"
                placeholder="000000" />
              <span v-if="confirmationForm.errors.code" class="ds-field-error">{{ confirmationForm.errors.code }}</span>
            </label>
            <button type="submit" class="ds-button ds-button-primary" :disabled="confirmationForm.processing || confirmationForm.code.length !== 6">
              <ArrowPathIcon v-if="confirmationForm.processing" class="h-4 w-4 animate-spin" />
              <CheckBadgeIcon v-else class="h-4 w-4" />
              {{ confirmationForm.processing ? 'A verificar...' : 'Verificar e concluir' }}
            </button>
          </form>
        </div>
      </div>
    </div>

    <div v-if="recoveryCodes.length" class="space-y-4 rounded-lg border border-[var(--ds-border)] px-4 py-5 sm:px-5">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p class="text-sm font-bold text-[var(--ds-text)]">Códigos de recuperação</p>
          <p class="ds-copy mt-1 text-sm">Guarde estes códigos fora do LIMS. Cada código só pode ser usado uma vez.</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary shrink-0" @click="downloadRecoveryCodes">
          <ArrowDownTrayIcon class="h-4 w-4" />
          Descarregar
        </button>
      </div>

      <ul class="grid gap-2 sm:grid-cols-2">
        <li v-for="code in recoveryCodes" :key="code" class="rounded-lg bg-[var(--ds-panel-subtle)] px-3 py-2 font-mono text-sm font-semibold text-[var(--ds-text)] ring-1 ring-inset ring-[var(--ds-border)]">{{ code }}</li>
      </ul>

      <ConfirmsPassword @confirmed="regenerateRecoveryCodes">
        <button type="button" class="ds-button ds-button-secondary" :disabled="loadingRecoveryCodes">
          <ArrowPathIcon class="h-4 w-4" :class="{ 'animate-spin': loadingRecoveryCodes }" />
          Gerar novos códigos
        </button>
      </ConfirmsPassword>
    </div>

    <div v-if="twoFactorEnabled && !confirming" class="flex flex-wrap gap-2 border-t border-[var(--ds-border)] pt-5">
      <ConfirmsPassword v-if="!recoveryCodes.length" @confirmed="showRecoveryCodes">
        <button type="button" class="ds-button ds-button-secondary" :disabled="loadingRecoveryCodes">
          <ArrowPathIcon v-if="loadingRecoveryCodes" class="h-4 w-4 animate-spin" />
          <KeyIcon v-else class="h-4 w-4" />
          {{ loadingRecoveryCodes ? 'A carregar...' : 'Mostrar códigos de recuperação' }}
        </button>
      </ConfirmsPassword>

      <ConfirmsPassword @confirmed="disableTwoFactorAuthentication">
        <button type="button" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200" :disabled="disabling">
          <ArrowPathIcon v-if="disabling" class="h-4 w-4 animate-spin" />
          <ShieldExclamationIcon v-else class="h-4 w-4" />
          {{ disabling ? 'A desactivar...' : 'Desactivar 2FA' }}
        </button>
      </ConfirmsPassword>
    </div>
  </div>
</template>
