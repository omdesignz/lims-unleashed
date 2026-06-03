<template>
  <section class="space-y-4" :class="commercialDocumentThemeClasses">
    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60">
      <p class="text-sm text-slate-700 dark:text-slate-300">
        Registe passkeys para iniciar sessão com Touch ID, Face ID, chaves de segurança ou gestores de credenciais compatíveis.
      </p>
    </div>

    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
      <div>
        <label class="mb-2 block text-sm font-medium text-slate-900 dark:text-slate-100">
          Nome da passkey
        </label>
        <input
          v-model="passkeyName"
          type="text"
          maxlength="255"
          placeholder="Ex.: MacBook do laboratório"
          class="block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
        />
        <p v-if="successMessage" class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
          {{ successMessage }}
        </p>
        <p v-if="errorMessage" class="mt-2 text-xs font-semibold text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </p>
      </div>

      <div class="flex items-end">
        <button
          type="button"
          @click="registerPasskey"
          :disabled="isRegistering"
          class="inline-flex min-w-44 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-primary-900 to-primary-700 px-4 py-3 text-sm font-semibold text-white shadow-lg transition hover:from-primary-800 hover:to-primary-600 disabled:cursor-not-allowed disabled:opacity-60 dark:from-primary-700 dark:to-primary-500"
        >
          <svg v-if="isRegistering" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
          </svg>
          <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11c1.657 0 3-1.567 3-3.5S13.657 4 12 4s-3 1.567-3 3.5S10.343 11 12 11Zm0 0c-3.314 0-6 2.239-6 5v1h12v-1c0-2.761-2.686-5-6-5Z" />
          </svg>
          <span>{{ isRegistering ? 'A registar…' : 'Registar passkey' }}</span>
        </button>
      </div>
    </div>

    <div class="space-y-3">
      <div
        v-for="passkey in passkeys"
        :key="passkey.id"
        class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/80 md:flex-row md:items-center md:justify-between"
      >
        <div>
          <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ passkey.name }}
          </h4>
          <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
            Registada em {{ formatDate(passkey.created_at) }}
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">
            Última utilização: {{ passkey.last_used_at ? formatDate(passkey.last_used_at) : 'Ainda não utilizada' }}
          </p>
        </div>

        <button
          type="button"
          @click="removePasskey(passkey)"
          :disabled="removingPasskeyId === passkey.id"
          class="inline-flex items-center justify-center rounded-xl border border-red-200 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-50 dark:border-red-900/60 dark:text-red-300 dark:hover:bg-red-950/40"
          :class="removingPasskeyId === passkey.id ? 'cursor-not-allowed opacity-60' : ''"
        >
          {{ removingPasskeyId === passkey.id ? 'A remover...' : 'Remover' }}
        </button>
      </div>

      <div
        v-if="!passkeys.length"
        class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-5 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-400"
      >
        Ainda não existem passkeys registadas para esta conta.
      </div>
    </div>
  </section>
</template>

<script setup>
import { router } from '@inertiajs/vue3'
import { startRegistration } from '@simplewebauthn/browser'
import { ref } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

const props = defineProps({
  passkeys: {
    type: Array,
    default: () => [],
  },
  routes: {
    type: Object,
    default: () => ({
      registrationOptions: 'security.passkeys.registration-options',
      store: 'security.passkeys.store',
      destroy: 'security.passkeys.destroy',
    }),
  },
})

const passkeyName = ref('')
const isRegistering = ref(false)
const removingPasskeyId = ref(null)
const errorMessage = ref('')
const successMessage = ref('')

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

function formatDate(value) {
  if (!value) {
    return 'N/D'
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime())
    ? 'N/D'
    : new Intl.DateTimeFormat('pt-PT', {
        dateStyle: 'medium',
        timeStyle: 'short',
      }).format(date)
}

async function registerPasskey() {
  errorMessage.value = ''
  successMessage.value = ''
  isRegistering.value = true

  try {
    const optionsResponse = await fetch(route(props.routes.registrationOptions), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
    })

    if (!optionsResponse.ok) {
      throw new Error('Não foi possível iniciar o registo da passkey.')
    }

    const options = await optionsResponse.json()
    const registration = await startRegistration({ optionsJSON: options })

    const storeResponse = await fetch(route(props.routes.store), {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        name: passkeyName.value || 'Passkey principal',
        passkey: JSON.stringify(registration),
      }),
    })

    const payload = await storeResponse.json().catch(() => ({}))

    if (!storeResponse.ok) {
      throw new Error(payload.message || 'Não foi possível guardar a passkey.')
    }

    passkeyName.value = ''
    successMessage.value = payload.message || 'Passkey registada com sucesso.'
    router.reload({ only: ['passkeys'], preserveScroll: true })
  } catch (error) {
    errorMessage.value = error?.message || 'Não foi possível registar a passkey.'
  } finally {
    isRegistering.value = false
  }
}

function removePasskey(passkey) {
  errorMessage.value = ''
  successMessage.value = ''
  removingPasskeyId.value = passkey.id

  fetch(route(props.routes.destroy, passkey.id), {
    method: 'DELETE',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
    },
  })
    .then(async response => {
      if (!response.ok) {
        const payload = await response.json().catch(() => ({}))
        throw new Error(payload.message || 'Não foi possível remover a passkey.')
      }

      const payload = await response.json().catch(() => ({}))
      successMessage.value = payload.message || 'Passkey removida com sucesso.'
      router.reload({ only: ['passkeys'], preserveScroll: true })
    })
    .catch(error => {
      errorMessage.value = error?.message || 'Não foi possível remover a passkey.'
    })
    .finally(() => {
      removingPasskeyId.value = null
    })
}
</script>
