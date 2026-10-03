<script setup>
import AuthExperienceShell from '@/Components/auth/AuthExperienceShell.vue'
import EmptyLayout from '../../Shared/EmptyLayout.vue'
import { startAuthentication } from '@simplewebauthn/browser'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import {
  ChevronRight as ChevronRightIcon,
  Eye as EyeIcon,
  EyeOff as EyeSlashIcon,
  Fingerprint as FingerPrintIcon,
} from '@lucide/vue'
import { computed, ref } from 'vue'

defineOptions({ layout: EmptyLayout })
defineProps({ status: String })

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
const brandLoginHeadline = computed(() => brandSettings.value.login_headline || 'Bem-vindo de volta')
const brandLoginSubheadline = computed(() => brandSettings.value.login_subheadline || 'Aceda a operação e mantenha a rastreabilidade do laboratório sob controlo.')
const socialProviders = computed(() => page.props.socialAuth?.providers ?? [])

const showPassword = ref(false)
const passkeyProcessing = ref(false)
const passkeyResponse = ref('')
const passkeyLoginForm = ref(null)
const csrfToken = typeof document === 'undefined'
  ? ''
  : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

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
    .post('/login', {
      onFinish: () => form.reset('password'),
    })
}

const loginWithPasskey = async () => {
  passkeyProcessing.value = true
  form.clearErrors()

  try {
    const response = await fetch(route('passkeys.authentication_options'), {
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      throw new Error('Não foi possível iniciar a autenticação com passkey.')
    }

    const options = await response.json()
    const assertion = await startAuthentication({ optionsJSON: options })

    passkeyResponse.value = JSON.stringify(assertion)
    passkeyLoginForm.value?.submit()
  } catch (error) {
    passkeyProcessing.value = false
    form.setError('email', error?.message || 'Não foi possível autenticar com passkey.')
  }
}
</script>

<template>
  <Head title="Início de sessão" />
  <AuthExperienceShell
    accent="Entrar."
    :title="brandLoginHeadline"
    eyebrow="Área interna"
    :description="brandLoginSubheadline"
    context-title="Sessão protegida"
    context-description="A identificação do utilizador mantém operações, revisoes e documentos associados ao responsável correcto."
  >
    <div>

      <div
        v-if="status"
        class="pl-banner pl-banner-ok mb-5 text-sm"
        role="status"
      >
        {{ status }}
      </div>

      <form class="space-y-5" @submit.prevent="submit">
        <div class="ds-field-group">
          <label for="email" class="ds-field-label">{{ $t('gestlab.pages.login.email_input_title') }}</label>
          <BaseInput
            id="email"
            v-model="form.email"
            name="email"
            type="text"
            autocomplete="username"
            autofocus
            required
            class="ds-field"
            :placeholder="$t('gestlab.pages.login.email_placeholder')"
            :aria-invalid="Boolean(form.errors.email)"
          />
          <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
        </div>

        <div class="ds-field-group">
          <div class="flex items-center justify-between gap-4">
            <label for="password" class="ds-field-label">{{ $t('gestlab.pages.login.password_input_title') }}</label>
            <Link :href="route('password.request')" class="pl-k pl-acc hover:underline">
              {{ $t('gestlab.pages.login.forgot_password') }}
            </Link>
          </div>
          <div class="relative">
            <BaseInput
              id="password"
              v-model="form.password"
              name="password"
              :type="showPassword ? 'text' : 'password'"
              autocomplete="current-password"
              required
              class="ds-field pr-11"
              :placeholder="$t('gestlab.pages.login.password_placeholder')"
              :aria-invalid="Boolean(form.errors.password)"
            />
            <button
              type="button"
              class="ds-icon-button absolute right-1 top-1/2 h-8 w-8 -translate-y-1/2"
              :title="showPassword ? $t('gestlab.pages.login.hide_password') : $t('gestlab.pages.login.show_password')"
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
          <span>{{ $t('gestlab.pages.login.remember_input_title') }}</span>
        </label>

        <p v-if="$page.props.errors?.social" class="ds-field-error">{{ $page.props.errors.social }}</p>

        <div class="grid gap-3 pt-2 sm:grid-cols-2">
          <button type="submit" class="ds-button ds-button-primary min-h-12 w-full justify-between" :disabled="form.processing || passkeyProcessing">
            {{ form.processing ? $t('gestlab.pages.login.processing') : $t('gestlab.pages.login.login_button_title') }}
            <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
          </button>

          <button type="button" class="ds-button ds-button-secondary min-h-12 w-full justify-between" :disabled="passkeyProcessing || form.processing" @click="loginWithPasskey">
            {{ passkeyProcessing ? $t('gestlab.pages.login.passkey_processing') : $t('gestlab.pages.login.passkey_button') }}
            <FingerPrintIcon class="h-4 w-4" aria-hidden="true" />
          </button>
        </div>
      </form>

      <form ref="passkeyLoginForm" :action="route('passkeys.login')" method="post" class="hidden">
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="remember" :value="form.remember ? 'on' : ''" />
        <input type="hidden" name="start_authentication_response" :value="passkeyResponse" />
      </form>

      <div v-if="socialProviders.length" class="mt-6 border-t border-[var(--pl-line)] pt-5">
        <p class="pl-k pl-muted mb-3">Ou continue com</p>
        <div class="grid gap-2">
          <a
            v-for="provider in socialProviders"
            :key="provider.service"
            :href="route('auth.redirect', provider.service)"
            class="ds-button ds-button-secondary w-full"
          >
            Entrar com {{ provider.label }}
          </a>
        </div>
      </div>

    </div>
  </AuthExperienceShell>
</template>
