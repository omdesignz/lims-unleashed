<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '@/Shared/EmptyLayout.vue'
import { startAuthentication } from '@simplewebauthn/browser'
import { Head, Link, useForm } from '@inertiajs/vue3'
import {
  ArrowRight as ArrowRightIcon,
  LogOut as ArrowRightStartOnRectangleIcon,
  Eye as EyeIcon,
  EyeOff as EyeSlashIcon,
  Fingerprint as FingerPrintIcon,
} from '@lucide/vue'
import { ref } from 'vue'

defineOptions({ layout: EmptyLayout })
defineProps({ status: String })

const csrfToken = typeof document === 'undefined'
  ? ''
  : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
const passkeyProcessing = ref(false)
const passkeyResponse = ref('')
const passkeyLoginForm = ref(null)
const showPassword = ref(false)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      remember: data.remember ? 'on' : '',
    }))
    .post(route('portal.login.store'), {
      onFinish: () => form.reset('password'),
    })
}

const loginWithPasskey = async () => {
  passkeyProcessing.value = true
  form.clearErrors()

  try {
    const response = await fetch(route('portal.passkeys.authentication_options'), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) {
      throw new Error('Não foi possível iniciar a autenticação com a chave de acesso.')
    }

    const options = await response.json()
    const assertion = await startAuthentication({ optionsJSON: options })

    passkeyResponse.value = JSON.stringify(assertion)
    passkeyLoginForm.value?.submit()
  } catch (error) {
    passkeyProcessing.value = false
    form.setError('email', error?.message || 'Não foi possível autenticar com a chave de acesso.')
  }
}
</script>

<template>
  <Head title="Portal do cliente" />

  <AuthExperienceShell
    title="Acompanhe o trabalho do seu laboratório"
    eyebrow="Portal do cliente"
    description="Consulte pedidos, colheitas, resultados, certificados e documentos comerciais num único local."
    context-title="Acesso associado a sua organização"
    context-description="Os registos apresentados respeitam o cliente e o local operacional vinculados a sua conta."
    mode="portal"
  >
    <div>
      <p class="ds-copy text-sm leading-6">Use o correio electrónico ou NIF atribuido pelo laboratório.</p>

      <div
        v-if="status"
        class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
      >
        {{ status }}
      </div>

      <form class="mt-7 space-y-5" @submit.prevent="submit">
        <div class="ds-field-group">
          <label for="portal-email" class="ds-field-label">Correio electrónico ou NIF</label>
          <BaseInput
            id="portal-email"
            v-model="form.email"
            name="email"
            type="text"
            autocomplete="username"
            autofocus
            required
            class="ds-field"
            placeholder="cliente@empresa.co.ao"
            :aria-invalid="Boolean(form.errors.email)"
          />
          <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
        </div>

        <div class="ds-field-group">
          <div class="flex items-center justify-between gap-4">
            <label for="portal-password" class="ds-field-label">Palavra-passe</label>
            <Link :href="route('portal.password.request')" class="text-xs font-semibold text-[rgb(var(--primary-700-rgb))] hover:underline dark:text-[rgb(var(--primary-200-rgb))]">
              Recuperar acesso
            </Link>
          </div>
          <div class="relative">
            <BaseInput
              id="portal-password"
              v-model="form.password"
              name="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              required
              class="ds-field pr-11"
              :aria-invalid="Boolean(form.errors.password)"
            />
            <button
              type="button"
              class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-[var(--ds-text-soft)] transition hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)]"
              :title="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'"
              @click="showPassword = !showPassword"
            >
              <EyeSlashIcon v-if="showPassword" class="h-4 w-4" aria-hidden="true" />
              <EyeIcon v-else class="h-4 w-4" aria-hidden="true" />
            </button>
          </div>
          <p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p>
        </div>

        <label class="flex items-center gap-3 text-sm font-medium text-[var(--ds-text-muted)]">
          <CheckboxInput v-model="form.remember" name="remember" type="checkbox" class="ds-checkbox" />
          <span>Manter sessão iniciada</span>
        </label>

        <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing || passkeyProcessing">
          <ArrowRightStartOnRectangleIcon class="h-4 w-4" aria-hidden="true" />
          {{ form.processing ? 'A iniciar sessão...' : 'Entrar no portal' }}
        </button>

        <button type="button" class="ds-button ds-button-secondary w-full" :disabled="passkeyProcessing || form.processing" @click="loginWithPasskey">
          <FingerPrintIcon class="h-4 w-4" aria-hidden="true" />
          {{ passkeyProcessing ? 'A preparar a chave de acesso...' : 'Entrar com a chave de acesso' }}
        </button>
      </form>

      <form ref="passkeyLoginForm" :action="route('portal.passkeys.login')" method="post" class="hidden">
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="remember" :value="form.remember ? '1' : ''" />
        <input type="hidden" name="start_authentication_response" :value="passkeyResponse" />
      </form>

      <div class="mt-6 border-t border-[var(--ds-border)] pt-5">
        <Link :href="route('login')" class="ds-button ds-button-ghost w-full">
          Área interna
          <ArrowRightIcon class="h-4 w-4" aria-hidden="true" />
        </Link>
      </div>
    </div>
  </AuthExperienceShell>
</template>
