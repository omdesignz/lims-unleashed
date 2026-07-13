<script setup>
import EmptyLayout from '@/Shared/EmptyLayout.vue'
import {
  ArrowRightIcon,
  BuildingOffice2Icon,
  CheckCircleIcon,
  EnvelopeIcon,
  EyeIcon,
  EyeSlashIcon,
  FingerPrintIcon,
  KeyIcon,
  LockClosedIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { startAuthentication } from '@simplewebauthn/browser'
import { computed, ref } from 'vue'

defineOptions({ layout: EmptyLayout })

const page = usePage()
const brandSettings = computed(() => page.props.settings ?? {})
const brandLogoUrl = computed(() => brandSettings.value.logo_url ?? null)
const brandAppName = computed(() => brandSettings.value.app_name ?? 'LIMS Unleashed')
const csrfToken = typeof document === 'undefined' ? '' : document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
const passkeyProcessing = ref(false)
const passkeyResponse = ref('')
const passkeyLoginForm = ref(null)
const showPassword = ref(false)

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.transform((data) => ({
    ...data,
    remember: form.remember ? 'on' : '',
  })).post(route('portal.login.store'), {
    onFinish: () => form.reset('password'),
  })
}

async function loginWithPasskey() {
  passkeyProcessing.value = true
  form.clearErrors()

  try {
    const response = await fetch(route('portal.passkeys.authentication_options'), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) {
      throw new Error('Nao foi possivel iniciar a autenticacao com passkey.')
    }

    const options = await response.json()
    const assertion = await startAuthentication({ optionsJSON: options })
    passkeyResponse.value = JSON.stringify(assertion)
    passkeyLoginForm.value?.submit()
  } catch (error) {
    passkeyProcessing.value = false
    form.setError('email', error?.message || 'Nao foi possivel autenticar com passkey.')
  }
}
</script>

<template>
  <Head title="Portal do cliente" />

  <div class="ds-app-canvas min-h-screen text-[var(--ds-text)]">
    <header class="border-b border-[var(--ds-border)] bg-[var(--ds-panel)]">
      <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3">
          <img v-if="brandLogoUrl" class="h-10 max-w-40 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
          <span v-else class="lims-brand-mark h-10 w-10"><BuildingOffice2Icon class="h-5 w-5" /></span>
          <div class="min-w-0"><p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ brandAppName }}</p><p class="ds-kicker mt-0.5">Portal do cliente</p></div>
        </div>
        <Link :href="route('login')" class="ds-button ds-button-secondary"><span class="hidden sm:inline">Area interna</span><ArrowRightIcon class="h-4 w-4" /></Link>
      </div>
    </header>

    <main class="mx-auto grid min-h-[calc(100vh-73px)] w-full max-w-6xl items-center gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:px-8 lg:py-12">
      <section class="mx-auto w-full max-w-lg lg:mx-0">
        <div class="mb-6">
          <p class="ds-kicker">Acesso seguro</p>
          <h1 class="ds-heading mt-2 text-3xl sm:text-4xl">Entre no portal da sua conta</h1>
          <p class="ds-copy mt-3 max-w-xl text-sm">Consulte pedidos, documentos e certificados associados ao seu local operacional.</p>
        </div>

        <div class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <div class="flex items-center gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]"><LockClosedIcon class="h-4 w-4" /></span><div><h2 class="text-base font-bold text-[var(--ds-text)]">Credenciais do cliente</h2><p class="ds-copy mt-0.5 text-xs">Use o email ou NIF atribuido pelo laboratorio.</p></div></div>
          </div>

          <form class="space-y-5 p-5 sm:p-6" @submit.prevent="submit">
            <div v-if="form.hasErrors" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200" role="alert">
              Verifique as credenciais e tente novamente.
            </div>

            <div class="ds-field-group">
              <label for="portal-email" class="ds-field-label">Email ou NIF</label>
              <div class="relative"><EnvelopeIcon class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-[var(--ds-text-soft)]" /><input id="portal-email" v-model="form.email" name="email" type="text" autocomplete="username" class="ds-field pl-10" placeholder="cliente@empresa.co.ao" :aria-invalid="Boolean(form.errors.email)" required /></div>
              <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
            </div>

            <div class="ds-field-group">
              <div class="flex items-center justify-between gap-3"><label for="portal-password" class="ds-field-label">Palavra-passe</label><Link :href="route('portal.password.request')" class="text-xs font-bold text-[rgb(var(--primary-700-rgb))] hover:text-[rgb(var(--primary-600-rgb))]">Recuperar acesso</Link></div>
              <div class="relative"><KeyIcon class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-[var(--ds-text-soft)]" /><input id="portal-password" v-model="form.password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" class="ds-field pl-10 pr-11" :aria-invalid="Boolean(form.errors.password)" required /><button type="button" class="ds-icon-button absolute right-1.5 top-1.5" :title="showPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'" @click="showPassword = !showPassword"><EyeSlashIcon v-if="showPassword" class="h-4 w-4" /><EyeIcon v-else class="h-4 w-4" /></button></div>
              <p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-start gap-3"><input v-model="form.remember" name="remember" type="checkbox" class="ds-checkbox mt-0.5" /><span><span class="block text-sm font-bold text-[var(--ds-text)]">Manter sessao iniciada</span><span class="ds-copy mt-0.5 block text-xs">Use apenas num dispositivo de confianca.</span></span></label>

            <button type="submit" class="ds-button ds-button-primary w-full" :disabled="form.processing"><ArrowRightIcon class="h-4 w-4" />{{ form.processing ? 'A iniciar sessao...' : 'Entrar no portal' }}</button>

            <div class="flex items-center gap-3"><span class="h-px flex-1 bg-[var(--ds-border)]" /><span class="ds-field-label">ou</span><span class="h-px flex-1 bg-[var(--ds-border)]" /></div>

            <button type="button" class="ds-button ds-button-secondary w-full" :disabled="passkeyProcessing" @click="loginWithPasskey"><FingerPrintIcon class="h-4 w-4" />{{ passkeyProcessing ? 'A preparar passkey...' : 'Entrar com passkey' }}</button>
          </form>

          <footer class="border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 text-center sm:px-6"><p class="ds-copy text-xs">Precisa de ajuda? <Link href="/help" class="font-bold text-[rgb(var(--primary-700-rgb))]">Contacte o suporte</Link>.</p></footer>
        </div>
      </section>

      <aside class="order-last self-center lg:order-none">
        <div class="ds-card overflow-hidden">
          <header class="border-b border-[var(--ds-border)] px-5 py-4"><h2 class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]"><ShieldCheckIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />Antes de entrar</h2></header>
          <ul class="divide-y divide-[var(--ds-border)]">
            <li class="flex items-start gap-3 px-5 py-4"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" /><div><p class="text-sm font-bold text-[var(--ds-text)]">Confirme o endereco</p><p class="ds-copy mt-1 text-xs">Use apenas o dominio oficial do laboratorio.</p></div></li>
            <li class="flex items-start gap-3 px-5 py-4"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" /><div><p class="text-sm font-bold text-[var(--ds-text)]">Proteja as credenciais</p><p class="ds-copy mt-1 text-xs">Nao partilhe palavras-passe nem codigos temporarios.</p></div></li>
            <li class="flex items-start gap-3 px-5 py-4"><CheckCircleIcon class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" /><div><p class="text-sm font-bold text-[var(--ds-text)]">Termine sessoes publicas</p><p class="ds-copy mt-1 text-xs">Saia da conta em equipamentos partilhados.</p></div></li>
          </ul>
        </div>
      </aside>
    </main>

    <form ref="passkeyLoginForm" :action="route('portal.passkeys.login')" method="post" class="hidden">
      <input type="hidden" name="_token" :value="csrfToken" />
      <input type="hidden" name="remember" :value="form.remember ? '1' : ''" />
      <input type="hidden" name="start_authentication_response" :value="passkeyResponse" />
    </form>
  </div>
</template>
