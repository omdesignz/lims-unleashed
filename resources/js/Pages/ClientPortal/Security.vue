<script setup>
import { Dialog, DialogPanel } from '@headlessui/vue'
import {
  CheckCircleIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  EnvelopeIcon,
  ExclamationTriangleIcon,
  FingerPrintIcon,
  KeyIcon,
  LockClosedIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import { Link, router, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import PasskeyManagementForm from '@/Pages/Profile/Partials/passkey-management-form.vue'
import Layout from '@/Shared/Layouts/PortalLayout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  warehouse: Object,
  security: Object,
  sessions: { type: Array, default: () => [] },
  passkeys: { type: Array, default: () => [] },
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const confirmationForm = useForm({ code: '' })
const passwordConfirmationForm = useForm({ password: '' })
const sessionLogoutForm = useForm({ password: '' })

const twoFactorWorking = ref(false)
const twoFactorEnabled = ref(Boolean(props.security?.two_factor_enabled))
const twoFactorConfirmed = ref(Boolean(props.security?.two_factor_confirmed))
const qrCode = ref(null)
const recoveryCodes = ref([])
const passwordConfirmationOpen = ref(false)
const pendingSensitiveAction = ref(null)
const sessionLogoutOpen = ref(false)
const securityError = ref('')

const warehouse = computed(() => props.warehouse?.data ?? props.warehouse ?? {})
const sessions = computed(() => props.sessions ?? [])
const passkeys = computed(() => props.passkeys ?? [])
const portalPasskeyRoutes = {
  registrationOptions: 'portal.security.passkeys.registration-options',
  store: 'portal.security.passkeys.store',
  destroy: 'portal.security.passkeys.destroy',
}

const securityCards = computed(() => [
  {
    label: 'Palavra-passe',
    value: props.security?.has_password ? 'Configurada' : 'Por configurar',
    icon: LockClosedIcon,
    tone: props.security?.has_password ? 'good' : 'warn',
  },
  {
    label: 'Email',
    value: props.security?.email_verified ? 'Verificado' : 'Pendente',
    icon: EnvelopeIcon,
    tone: props.security?.email_verified ? 'good' : 'warn',
  },
  {
    label: 'Duplo factor',
    value: twoFactorEnabled.value ? (twoFactorConfirmed.value ? 'Activo' : 'A confirmar') : 'Inactivo',
    icon: KeyIcon,
    tone: twoFactorEnabled.value && twoFactorConfirmed.value ? 'good' : 'warn',
  },
  {
    label: 'Passkeys',
    value: props.security?.passkey_count ? `${props.security.passkey_count} registada${props.security.passkey_count === 1 ? '' : 's'}` : 'Nenhuma',
    icon: FingerPrintIcon,
    tone: props.security?.passkey_count ? 'good' : 'warn',
  },
])

function statusToneClass(tone) {
  return tone === 'good'
    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200'
}

async function getJson(routeName) {
  const response = await fetch(route(routeName), {
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
  })

  if (!response.ok) {
    throw new Error('Nao foi possivel obter os dados de seguranca. Tente novamente.')
  }

  return response.json()
}

function updatePassword() {
  passwordForm.put(route('portal.user-password.update'), {
    errorBag: 'updatePassword',
    preserveScroll: true,
    onSuccess: () => passwordForm.reset('current_password', 'password', 'password_confirmation'),
  })
}

function resendVerification() {
  router.post(route('portal.verification.send'), {}, { preserveScroll: true })
}

async function requestPasswordConfirmation(action) {
  securityError.value = ''

  try {
    const confirmation = await getJson('portal.password.confirmation')

    if (confirmation.confirmed) {
      await action()
      return
    }

    pendingSensitiveAction.value = action
    passwordConfirmationOpen.value = true
  } catch (error) {
    securityError.value = error?.message || 'Nao foi possivel confirmar a identidade.'
  }
}

function closePasswordConfirmation() {
  passwordConfirmationOpen.value = false
  passwordConfirmationForm.reset('password')
  passwordConfirmationForm.clearErrors()
  pendingSensitiveAction.value = null
}

function confirmPasswordForSensitiveAction() {
  passwordConfirmationForm.post(route('portal.password.confirm.store'), {
    preserveScroll: true,
    onSuccess: async () => {
      const action = pendingSensitiveAction.value

      passwordConfirmationOpen.value = false
      passwordConfirmationForm.reset('password')
      passwordConfirmationForm.clearErrors()
      pendingSensitiveAction.value = null

      if (action) {
        await action()
      }
    },
  })
}

async function loadTwoFactorDetails() {
  const [qr, codes] = await Promise.all([
    getJson('portal.two-factor.qr-code'),
    getJson('portal.two-factor.recovery-codes'),
  ])

  qrCode.value = qr.svg
  recoveryCodes.value = codes
}

function enableTwoFactor() {
  twoFactorWorking.value = true
  securityError.value = ''

  router.post(route('portal.two-factor.enable'), {}, {
    preserveScroll: true,
    onSuccess: async () => {
      twoFactorEnabled.value = true

      try {
        await loadTwoFactorDetails()
      } catch (error) {
        securityError.value = error?.message || 'O duplo factor foi activado, mas os dados de configuracao nao puderam ser carregados.'
      }
    },
    onFinish: () => {
      twoFactorWorking.value = false
    },
  })
}

async function showTwoFactorDetails() {
  twoFactorWorking.value = true
  securityError.value = ''

  try {
    await loadTwoFactorDetails()
  } catch (error) {
    securityError.value = error?.message || 'Nao foi possivel carregar os dados do duplo factor.'
  } finally {
    twoFactorWorking.value = false
  }
}

function confirmTwoFactor() {
  confirmationForm.post(route('portal.two-factor.confirm'), {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorConfirmed.value = true
      confirmationForm.reset('code')
    },
  })
}

function regenerateRecoveryCodes() {
  twoFactorWorking.value = true
  securityError.value = ''

  router.post(route('portal.two-factor.regenerate-recovery-codes'), {}, {
    preserveScroll: true,
    onSuccess: async () => {
      try {
        recoveryCodes.value = await getJson('portal.two-factor.recovery-codes')
      } catch (error) {
        securityError.value = error?.message || 'Os codigos foram regenerados, mas nao puderam ser apresentados.'
      }
    },
    onFinish: () => {
      twoFactorWorking.value = false
    },
  })
}

function disableTwoFactor() {
  twoFactorWorking.value = true
  securityError.value = ''

  router.delete(route('portal.two-factor.disable'), {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorEnabled.value = false
      twoFactorConfirmed.value = false
      qrCode.value = null
      recoveryCodes.value = []
    },
    onFinish: () => {
      twoFactorWorking.value = false
    },
  })
}

function openSessionLogoutModal() {
  sessionLogoutOpen.value = true
}

function closeSessionLogoutModal() {
  sessionLogoutOpen.value = false
  sessionLogoutForm.reset('password')
  sessionLogoutForm.clearErrors()
}

function logoutOtherSessions() {
  sessionLogoutForm.delete(route('portal.other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeSessionLogoutModal(),
    onFinish: () => sessionLogoutForm.reset('password'),
  })
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-5 sm:px-6">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex min-w-0 items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ShieldCheckIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="ds-kicker">Seguranca da conta</p>
              <h1 class="ds-heading mt-1 text-2xl">Acesso ao portal</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Proteja credenciais, confirme dispositivos e reveja os acessos associados a {{ warehouse?.name || 'este local' }}.</p>
            </div>
          </div>
          <Link :href="route('portal.profile')" class="ds-button ds-button-secondary">Voltar ao perfil</Link>
        </div>
      </div>

      <dl class="grid gap-px bg-[var(--ds-border)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="item in securityCards" :key="item.label" class="bg-[var(--ds-panel)] p-5">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="ds-field-label">{{ item.label }}</dt>
              <dd class="mt-2 break-words text-lg font-bold text-[var(--ds-text)]">{{ item.value }}</dd>
            </div>
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg border" :class="statusToneClass(item.tone)">
              <component :is="item.icon" class="h-4 w-4" />
            </span>
          </div>
        </div>
      </dl>
    </section>

    <p v-if="securityError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200" role="alert">
      {{ securityError }}
    </p>

    <div class="space-y-8">
      <section class="grid gap-4 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-6">
        <div>
          <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><LockClosedIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Palavra-passe</h2>
          <p class="ds-copy mt-2 text-sm">Use uma credencial longa e exclusiva para o acesso deste cliente.</p>
        </div>
        <form class="ds-card overflow-hidden" @submit.prevent="updatePassword">
          <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
            <div class="ds-field-group lg:col-span-2">
              <label for="current-password" class="ds-field-label">Palavra-passe actual</label>
              <input id="current-password" v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="ds-field" :aria-invalid="Boolean(passwordForm.errors.current_password)" />
              <p v-if="passwordForm.errors.current_password" class="ds-field-error">{{ passwordForm.errors.current_password }}</p>
            </div>
            <div class="ds-field-group">
              <label for="new-password" class="ds-field-label">Nova palavra-passe</label>
              <input id="new-password" v-model="passwordForm.password" type="password" autocomplete="new-password" class="ds-field" :aria-invalid="Boolean(passwordForm.errors.password)" />
              <p v-if="passwordForm.errors.password" class="ds-field-error">{{ passwordForm.errors.password }}</p>
            </div>
            <div class="ds-field-group">
              <label for="password-confirmation" class="ds-field-label">Confirmar palavra-passe</label>
              <input id="password-confirmation" v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="ds-field" :aria-invalid="Boolean(passwordForm.errors.password_confirmation)" />
              <p v-if="passwordForm.errors.password_confirmation" class="ds-field-error">{{ passwordForm.errors.password_confirmation }}</p>
            </div>
          </div>
          <footer class="flex flex-col gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <p class="ds-copy text-xs">A alteracao termina sessoes que deixem de ser confiaveis.</p>
            <button type="submit" class="ds-button ds-button-primary" :disabled="passwordForm.processing">{{ passwordForm.processing ? 'A guardar...' : 'Actualizar palavra-passe' }}</button>
          </footer>
        </form>
      </section>

      <section class="grid gap-4 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-6">
        <div>
          <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><EnvelopeIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Email verificado</h2>
          <p class="ds-copy mt-2 text-sm">Necessario para alertas, recuperacao da conta e comunicacoes criticas.</p>
        </div>
        <div class="ds-card overflow-hidden">
          <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <CheckCircleIcon v-if="security?.email_verified" class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                <ExclamationTriangleIcon v-else class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ security?.email_verified ? 'Email verificado' : 'Verificacao pendente' }}</p>
              </div>
              <p class="ds-copy mt-1 break-words text-sm">{{ warehouse?.email || 'Sem email registado' }}</p>
            </div>
            <button v-if="!security?.email_verified" type="button" class="ds-button ds-button-secondary" @click="resendVerification">Reenviar verificacao</button>
          </div>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-6">
        <div>
          <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><KeyIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Duplo factor</h2>
          <p class="ds-copy mt-2 text-sm">Adiciona um codigo temporario ao inicio de sessao com palavra-passe.</p>
        </div>
        <div class="ds-card overflow-hidden">
          <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
            <div>
              <p class="text-sm font-bold text-[var(--ds-text)]">{{ twoFactorEnabled ? (twoFactorConfirmed ? 'Proteccao activa' : 'Configuracao por confirmar') : 'Proteccao inactiva' }}</p>
              <p class="ds-copy mt-1 text-sm">{{ twoFactorEnabled ? 'A conta exige uma prova adicional de identidade.' : 'Active esta proteccao para reduzir o risco de credenciais comprometidas.' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
              <button v-if="!twoFactorEnabled" type="button" class="ds-button ds-button-primary" :disabled="twoFactorWorking" @click="requestPasswordConfirmation(enableTwoFactor)">Activar 2FA</button>
              <button v-else type="button" class="ds-button ds-button-secondary" :disabled="twoFactorWorking" @click="requestPasswordConfirmation(showTwoFactorDetails)">Mostrar codigos</button>
              <button v-if="twoFactorEnabled" type="button" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200" :disabled="twoFactorWorking" @click="requestPasswordConfirmation(disableTwoFactor)">Desactivar</button>
            </div>
          </div>

          <div v-if="qrCode || recoveryCodes.length" class="grid gap-px border-t border-[var(--ds-border)] bg-[var(--ds-border)] lg:grid-cols-2">
            <div v-if="qrCode" class="bg-[var(--ds-panel)] p-5 sm:p-6">
              <h3 class="text-sm font-bold text-[var(--ds-text)]">Aplicacao autenticadora</h3>
              <p class="ds-copy mt-1 text-xs">Leia o codigo e introduza o token de seis digitos.</p>
              <div class="mt-4 inline-flex max-w-full overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[rgb(255,255,255)] p-3" v-html="qrCode" />
              <form v-if="!twoFactorConfirmed" class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="confirmTwoFactor">
                <div class="min-w-0 flex-1">
                  <label for="two-factor-code" class="sr-only">Codigo de seis digitos</label>
                  <input id="two-factor-code" v-model="confirmationForm.code" type="text" inputmode="numeric" autocomplete="one-time-code" class="ds-field" placeholder="Codigo de 6 digitos" />
                  <p v-if="confirmationForm.errors.code" class="ds-field-error mt-1">{{ confirmationForm.errors.code }}</p>
                </div>
                <button type="submit" class="ds-button ds-button-primary self-start">Confirmar</button>
              </form>
            </div>

            <div v-if="recoveryCodes.length" class="bg-[var(--ds-panel)] p-5 sm:p-6">
              <div class="flex items-start justify-between gap-3">
                <div><h3 class="text-sm font-bold text-[var(--ds-text)]">Codigos de recuperacao</h3><p class="ds-copy mt-1 text-xs">Guarde-os fora deste dispositivo.</p></div>
                <button type="button" class="ds-button ds-button-secondary" :disabled="twoFactorWorking" @click="requestPasswordConfirmation(regenerateRecoveryCodes)">Regenerar</button>
              </div>
              <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <code v-for="code in recoveryCodes" :key="code" class="rounded-md border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text)]">{{ code }}</code>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-6">
        <div>
          <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><ComputerDesktopIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Sessoes activas</h2>
          <p class="ds-copy mt-2 text-sm">Reveja navegadores recentes e termine acessos que ja nao reconhece.</p>
        </div>
        <div class="ds-card overflow-hidden">
          <ul v-if="sessions.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="session in sessions" :key="session.id" class="flex items-start gap-3 px-5 py-4 sm:px-6">
              <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]">
                <ComputerDesktopIcon v-if="session.agent?.is_desktop" class="h-4 w-4" />
                <DevicePhoneMobileIcon v-else class="h-4 w-4" />
              </span>
              <div class="min-w-0 flex-1">
                <p class="break-words text-sm font-bold text-[var(--ds-text)]">{{ session.agent?.platform || 'Dispositivo desconhecido' }} · {{ session.agent?.browser || 'Navegador desconhecido' }}</p>
                <p class="ds-copy mt-1 text-xs">{{ session.ip_address || 'IP nao registado' }} · {{ session.is_current_device ? 'Este dispositivo' : `Ultima actividade ${session.last_active}` }}</p>
              </div>
              <span v-if="session.is_current_device" class="ds-chip shrink-0">Actual</span>
            </li>
          </ul>
          <div v-else class="p-5 sm:p-6"><div class="ds-empty-state px-4 py-6 text-center"><p class="text-sm font-bold">Sem lista de sessoes</p><p class="ds-copy mt-1 text-xs">Disponivel quando o armazenamento de sessoes usa a base de dados.</p></div></div>
          <footer class="flex justify-end border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
            <button type="button" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200" @click="openSessionLogoutModal">Terminar outras sessoes</button>
          </footer>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-[15rem_minmax(0,1fr)] md:gap-6">
        <div>
          <h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><FingerPrintIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />Passkeys</h2>
          <p class="ds-copy mt-2 text-sm">Associe biometria, um gestor de credenciais ou uma chave fisica.</p>
        </div>
        <div class="ds-card p-5 sm:p-6">
          <PasskeyManagementForm :passkeys="passkeys" :routes="portalPasskeyRoutes" />
        </div>
      </section>
    </div>

    <Dialog :open="passwordConfirmationOpen" class="relative z-50" @close="closePasswordConfirmation">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 overflow-y-auto p-4 sm:grid sm:place-items-center">
        <DialogPanel class="ds-modal-panel mx-auto w-full max-w-md overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]"><LockClosedIcon class="h-4 w-4" /></span><div><h2 class="text-base font-bold">Confirmar identidade</h2><p class="ds-copy mt-1 text-sm">Introduza a palavra-passe antes desta operacao sensivel.</p></div></div>
          </div>
          <form @submit.prevent="confirmPasswordForSensitiveAction">
            <div class="p-5 sm:p-6"><div class="ds-field-group"><label for="sensitive-password" class="ds-field-label">Palavra-passe</label><input id="sensitive-password" v-model="passwordConfirmationForm.password" type="password" autocomplete="current-password" class="ds-field" /><p v-if="passwordConfirmationForm.errors.password" class="ds-field-error">{{ passwordConfirmationForm.errors.password }}</p></div></div>
            <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><button type="button" class="ds-button ds-button-secondary" @click="closePasswordConfirmation">Cancelar</button><button type="submit" class="ds-button ds-button-primary" :disabled="passwordConfirmationForm.processing">{{ passwordConfirmationForm.processing ? 'A confirmar...' : 'Confirmar' }}</button></footer>
          </form>
        </DialogPanel>
      </div>
    </Dialog>

    <Dialog :open="sessionLogoutOpen" class="relative z-50" @close="closeSessionLogoutModal">
      <div class="ds-modal-backdrop fixed inset-0" aria-hidden="true" />
      <div class="fixed inset-0 overflow-y-auto p-4 sm:grid sm:place-items-center">
        <DialogPanel class="ds-modal-panel mx-auto w-full max-w-md overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200"><ComputerDesktopIcon class="h-4 w-4" /></span><div><h2 class="text-base font-bold">Terminar outras sessoes</h2><p class="ds-copy mt-1 text-sm">A sessao deste dispositivo permanece activa.</p></div></div>
          </div>
          <form @submit.prevent="logoutOtherSessions">
            <div class="p-5 sm:p-6"><div class="ds-field-group"><label for="session-password" class="ds-field-label">Palavra-passe</label><input id="session-password" v-model="sessionLogoutForm.password" type="password" autocomplete="current-password" class="ds-field" /><p v-if="sessionLogoutForm.errors.password" class="ds-field-error">{{ sessionLogoutForm.errors.password }}</p></div></div>
            <footer class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:justify-end sm:px-6"><button type="button" class="ds-button ds-button-secondary" @click="closeSessionLogoutModal">Cancelar</button><button type="submit" class="ds-button ds-button-danger" :disabled="sessionLogoutForm.processing">{{ sessionLogoutForm.processing ? 'A terminar...' : 'Terminar sessoes' }}</button></footer>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  </div>
</template>
