<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <div class="relative isolate px-6 py-8 sm:px-8">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(14,165,233,0.18),transparent_35%),linear-gradient(135deg,rgba(15,23,42,0.04),transparent)] dark:bg-[radial-gradient(circle_at_top_left,rgba(56,189,248,0.14),transparent_38%),linear-gradient(135deg,rgba(15,23,42,0.65),rgba(15,23,42,0.15))]" />
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-primary-800 dark:border-primary-800/60 dark:bg-primary-950/50 dark:text-primary-200">
              <ShieldCheckIcon class="h-4 w-4" />
              Segurança
            </span>
            <h1 class="mt-4 max-w-3xl text-3xl font-black tracking-tight text-slate-950 dark:text-white sm:text-4xl">
              Centro de autenticação do portal
            </h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
              Controle a palavra-passe, verificação de email e autenticação de dois factores da conta cliente sem sair do portal.
            </p>
          </div>
          <Link
            :href="route('portal.profile')"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-primary-300 hover:text-primary-800 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-primary-600 dark:hover:text-primary-200"
          >
            Voltar ao perfil
          </Link>
        </div>
      </div>
    </section>

    <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
      <article
        v-for="item in securityCards"
        :key="item.label"
        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ item.label }}</p>
            <p class="mt-3 text-lg font-black text-slate-950 dark:text-white">{{ item.value }}</p>
          </div>
          <span class="rounded-2xl p-2" :class="item.tone === 'good' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300'">
            <component :is="item.icon" class="h-5 w-5" />
          </span>
        </div>
        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ item.description }}</p>
      </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
      <form
        class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8"
        @submit.prevent="updatePassword"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">Alterar palavra-passe</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
              Use uma palavra-passe longa e única. Esta alteração afecta apenas o acesso ao portal do cliente.
            </p>
          </div>
          <LockClosedIcon class="h-8 w-8 text-primary-700 dark:text-primary-300" />
        </div>

        <div class="mt-7 grid gap-5">
          <label class="block">
            <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Palavra-passe actual</span>
            <input
              v-model="passwordForm.current_password"
              type="password"
              autocomplete="current-password"
              class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
              :class="passwordForm.errors.current_password ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''"
              placeholder="Introduza a palavra-passe actual"
            >
            <span v-if="passwordForm.errors.current_password" class="mt-2 block text-xs font-semibold text-rose-600 dark:text-rose-400">{{ passwordForm.errors.current_password }}</span>
          </label>

          <div class="grid gap-5 md:grid-cols-2">
            <label class="block">
              <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Nova palavra-passe</span>
              <input
                v-model="passwordForm.password"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                :class="passwordForm.errors.password ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''"
                placeholder="Mínimo recomendado: 8 caracteres"
              >
              <span v-if="passwordForm.errors.password" class="mt-2 block text-xs font-semibold text-rose-600 dark:text-rose-400">{{ passwordForm.errors.password }}</span>
            </label>

            <label class="block">
              <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Confirmar palavra-passe</span>
              <input
                v-model="passwordForm.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition placeholder:text-slate-400 focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                :class="passwordForm.errors.password_confirmation ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''"
                placeholder="Repita a nova palavra-passe"
              >
              <span v-if="passwordForm.errors.password_confirmation" class="mt-2 block text-xs font-semibold text-rose-600 dark:text-rose-400">{{ passwordForm.errors.password_confirmation }}</span>
            </label>
          </div>
        </div>

        <div class="mt-7 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-xs leading-5 text-slate-500 dark:text-slate-400">
            Recomendado: combine letras, números e símbolos. Evite palavras-passe usadas noutros serviços.
          </p>
          <button
            type="submit"
            :disabled="passwordForm.processing"
            class="inline-flex items-center justify-center rounded-2xl bg-primary-800 px-5 py-3 text-sm font-black text-white shadow-lg shadow-primary-900/20 transition hover:bg-primary-700 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ passwordForm.processing ? 'A guardar...' : 'Actualizar palavra-passe' }}
          </button>
        </div>
      </form>

      <div class="space-y-6">
        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">Verificação de email</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                O email verificado protege notificações, reposição de palavra-passe e comunicações críticas.
              </p>
            </div>
            <EnvelopeIcon class="h-8 w-8 text-primary-700 dark:text-primary-300" />
          </div>

          <div class="mt-6 rounded-2xl border p-4" :class="security?.email_verified ? 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-200' : 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200'">
            <p class="text-sm font-bold">
              {{ security?.email_verified ? 'Email verificado' : 'Email ainda não verificado' }}
            </p>
            <p class="mt-1 text-sm opacity-80">{{ warehouse?.email || 'Sem email registado' }}</p>
          </div>

          <button
            v-if="!security?.email_verified"
            type="button"
            class="mt-5 inline-flex w-full items-center justify-center rounded-2xl border border-primary-300 bg-primary-50 px-4 py-3 text-sm font-black text-primary-800 transition hover:bg-primary-100 dark:border-primary-800 dark:bg-primary-950/40 dark:text-primary-200 dark:hover:bg-primary-900/50"
            @click="resendVerification"
          >
            Reenviar email de verificação
          </button>
        </article>

        <article class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">Autenticação de dois factores</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Adicione uma camada extra de protecção com uma app autenticadora.
              </p>
            </div>
            <KeyIcon class="h-8 w-8 text-primary-700 dark:text-primary-300" />
          </div>

          <div class="mt-6 flex flex-wrap gap-3">
            <button
              v-if="!twoFactorEnabled"
              type="button"
              :disabled="twoFactorWorking"
              class="inline-flex items-center justify-center rounded-2xl bg-primary-800 px-4 py-2.5 text-sm font-black text-white transition hover:bg-primary-700 disabled:opacity-60"
              @click="requestPasswordConfirmation(enableTwoFactor)"
            >
              Activar 2FA
            </button>
            <button
              v-else
              type="button"
              :disabled="twoFactorWorking"
              class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
              @click="requestPasswordConfirmation(showTwoFactorDetails)"
            >
              Mostrar QR e códigos
            </button>
            <button
              v-if="twoFactorEnabled"
              type="button"
              :disabled="twoFactorWorking"
              class="inline-flex items-center justify-center rounded-2xl border border-rose-300 px-4 py-2.5 text-sm font-black text-rose-700 transition hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
              @click="requestPasswordConfirmation(disableTwoFactor)"
            >
              Desactivar
            </button>
          </div>

          <div v-if="qrCode || recoveryCodes.length" class="mt-6 space-y-5">
            <div v-if="qrCode" class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
              <p class="text-sm font-bold text-slate-900 dark:text-slate-100">Código QR</p>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Leia este código na sua aplicação autenticadora.</p>
              <div class="mt-4 inline-flex rounded-2xl bg-white p-4 shadow-sm dark:bg-white" v-html="qrCode" />
              <form v-if="!twoFactorConfirmed" class="mt-4 flex flex-col gap-3 sm:flex-row" @submit.prevent="confirmTwoFactor">
                <input
                  v-model="confirmationForm.code"
                  type="text"
                  inputmode="numeric"
                  autocomplete="one-time-code"
                  class="min-w-0 flex-1 rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 outline-none focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                  placeholder="Código de 6 dígitos"
                >
                <button type="submit" class="rounded-2xl bg-primary-800 px-4 py-3 text-sm font-black text-white transition hover:bg-primary-700">
                  Confirmar
                </button>
              </form>
              <p v-if="confirmationForm.errors.code" class="mt-2 text-xs font-semibold text-rose-600 dark:text-rose-400">{{ confirmationForm.errors.code }}</p>
            </div>

            <div v-if="recoveryCodes.length" class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                  <p class="text-sm font-bold text-slate-900 dark:text-slate-100">Códigos de recuperação</p>
                  <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Guarde estes códigos num local seguro.</p>
                </div>
                <button type="button" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200" @click="requestPasswordConfirmation(regenerateRecoveryCodes)">
                  Regenerar
                </button>
              </div>
              <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <code
                  v-for="code in recoveryCodes"
                  :key="code"
                  class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200"
                >
                  {{ code }}
                </code>
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">Sessões activas</h2>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            Reveja os dispositivos recentes associados ao portal. Se suspeitar de acesso indevido, altere a palavra-passe e termine as outras sessões.
          </p>
        </div>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-2xl border border-rose-300 px-4 py-2.5 text-sm font-black text-rose-700 transition hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/40"
          @click="openSessionLogoutModal"
        >
          Terminar outras sessões
        </button>
      </div>

      <div v-if="sessions.length" class="mt-6 grid gap-3 lg:grid-cols-2">
        <article
          v-for="session in sessions"
          :key="session.id"
          class="flex items-start gap-4 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950"
        >
          <div class="rounded-2xl bg-white p-3 text-slate-700 shadow-sm dark:bg-slate-900 dark:text-slate-200">
            <ComputerDesktopIcon v-if="session.agent?.is_desktop" class="h-5 w-5" />
            <DevicePhoneMobileIcon v-else class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-950 dark:text-white">
              {{ session.agent?.platform || 'Dispositivo desconhecido' }} · {{ session.agent?.browser || 'Navegador desconhecido' }}
            </p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              {{ session.ip_address || 'IP não registado' }}
            </p>
            <p class="mt-2 text-xs font-bold" :class="session.is_current_device ? 'text-emerald-600 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400'">
              {{ session.is_current_device ? 'Este dispositivo' : `Activo pela última vez ${session.last_active}` }}
            </p>
          </div>
        </article>
      </div>

      <div v-else class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
        A lista de sessões está disponível quando o driver de sessão usa a base de dados.
      </div>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
      <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
          <h2 class="text-xl font-black tracking-tight text-slate-950 dark:text-white">Passkeys</h2>
          <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            Permita que os clientes entrem com Touch ID, Face ID, chaves de segurança ou gestores de credenciais compatíveis.
          </p>
        </div>
        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
          Disponível
        </span>
      </div>
      <PasskeyManagementForm
        class="mt-6"
        :passkeys="passkeys"
        :routes="portalPasskeyRoutes"
      />
    </section>

    <Dialog :open="passwordConfirmationOpen" class="relative z-50" @close="closePasswordConfirmation">
      <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-lg rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 sm:p-8">
          <div class="flex items-start gap-4">
            <div class="rounded-2xl bg-primary-50 p-3 text-primary-800 dark:bg-primary-950/50 dark:text-primary-200">
              <LockClosedIcon class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-lg font-black text-slate-950 dark:text-white">Confirmar identidade</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Para proteger alterações sensíveis, confirme a palavra-passe antes de continuar.
              </p>
            </div>
          </div>

          <form class="mt-6 space-y-4" @submit.prevent="confirmPasswordForSensitiveAction">
            <label class="block">
              <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Palavra-passe</span>
              <input
                v-model="passwordConfirmationForm.password"
                type="password"
                autocomplete="current-password"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                :class="passwordConfirmationForm.errors.password ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''"
              >
              <span v-if="passwordConfirmationForm.errors.password" class="mt-2 block text-xs font-semibold text-rose-600 dark:text-rose-400">
                {{ passwordConfirmationForm.errors.password }}
              </span>
            </label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="closePasswordConfirmation"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="passwordConfirmationForm.processing"
                class="rounded-2xl bg-primary-800 px-4 py-2.5 text-sm font-black text-white transition hover:bg-primary-700 disabled:opacity-60"
              >
                {{ passwordConfirmationForm.processing ? 'A confirmar...' : 'Confirmar' }}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>

    <Dialog :open="sessionLogoutOpen" class="relative z-50" @close="closeSessionLogoutModal">
      <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" aria-hidden="true" />
      <div class="fixed inset-0 flex items-center justify-center p-4">
        <DialogPanel class="w-full max-w-lg rounded-[2rem] border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900 sm:p-8">
          <div class="flex items-start gap-4">
            <div class="rounded-2xl bg-rose-50 p-3 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300">
              <ShieldCheckIcon class="h-6 w-6" />
            </div>
            <div>
              <h2 class="text-lg font-black text-slate-950 dark:text-white">Terminar outras sessões</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Introduza a palavra-passe para sair de outros navegadores e dispositivos associados a esta conta.
              </p>
            </div>
          </div>

          <form class="mt-6 space-y-4" @submit.prevent="logoutOtherSessions">
            <label class="block">
              <span class="text-sm font-bold text-slate-800 dark:text-slate-100">Palavra-passe</span>
              <input
                v-model="sessionLogoutForm.password"
                type="password"
                autocomplete="current-password"
                class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-950 shadow-sm outline-none transition focus:border-primary-600 focus:ring-4 focus:ring-primary-600/15 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                :class="sessionLogoutForm.errors.password ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/15' : ''"
              >
              <span v-if="sessionLogoutForm.errors.password" class="mt-2 block text-xs font-semibold text-rose-600 dark:text-rose-400">
                {{ sessionLogoutForm.errors.password }}
              </span>
            </label>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
              <button
                type="button"
                class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                @click="closeSessionLogoutModal"
              >
                Cancelar
              </button>
              <button
                type="submit"
                :disabled="sessionLogoutForm.processing"
                class="rounded-2xl bg-rose-700 px-4 py-2.5 text-sm font-black text-white transition hover:bg-rose-600 disabled:opacity-60"
              >
                {{ sessionLogoutForm.processing ? 'A terminar...' : 'Terminar sessões' }}
              </button>
            </div>
          </form>
        </DialogPanel>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, ref } from 'vue'
import { Dialog, DialogPanel } from '@headlessui/vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  CheckCircleIcon,
  ComputerDesktopIcon,
  DevicePhoneMobileIcon,
  EnvelopeIcon,
  ExclamationTriangleIcon,
  KeyIcon,
  LockClosedIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import Layout from '@/Shared/Layouts/PortalLayout.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'
import PasskeyManagementForm from '@/Pages/Profile/Partials/passkey-management-form.vue'

defineOptions({
  layout: Layout,
})

const props = defineProps({
  warehouse: Object,
  security: Object,
  sessions: {
    type: Array,
    default: () => [],
  },
  passkeys: {
    type: Array,
    default: () => [],
  },
})

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const confirmationForm = useForm({
  code: '',
})

const passwordConfirmationForm = useForm({
  password: '',
})

const sessionLogoutForm = useForm({
  password: '',
})

const twoFactorWorking = ref(false)
const twoFactorEnabled = ref(Boolean(props.security?.two_factor_enabled))
const twoFactorConfirmed = ref(Boolean(props.security?.two_factor_confirmed))
const qrCode = ref(null)
const recoveryCodes = ref([])
const passwordConfirmationOpen = ref(false)
const pendingSensitiveAction = ref(null)
const sessionLogoutOpen = ref(false)

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
    description: props.security?.has_password ? 'A conta já tem uma palavra-passe definida.' : 'Defina uma palavra-passe antes de usar o portal em produção.',
    icon: props.security?.has_password ? CheckCircleIcon : ExclamationTriangleIcon,
    tone: props.security?.has_password ? 'good' : 'warn',
  },
  {
    label: 'Email',
    value: props.security?.email_verified ? 'Verificado' : 'Pendente',
    description: props.security?.email_verified ? 'Pode receber notificações críticas com segurança.' : 'Reenvie a verificação para concluir a activação.',
    icon: props.security?.email_verified ? CheckCircleIcon : ExclamationTriangleIcon,
    tone: props.security?.email_verified ? 'good' : 'warn',
  },
  {
    label: '2FA',
    value: twoFactorEnabled.value ? (twoFactorConfirmed.value ? 'Activo' : 'A confirmar') : 'Inactivo',
    description: twoFactorEnabled.value ? 'A autenticação forte está disponível para este acesso.' : 'Active 2FA para reduzir risco de acesso indevido.',
    icon: twoFactorEnabled.value ? CheckCircleIcon : ExclamationTriangleIcon,
    tone: twoFactorEnabled.value ? 'good' : 'warn',
  },
  {
    label: 'Passkeys',
    value: props.security?.passkey_count ? `${props.security.passkey_count} registada${props.security.passkey_count === 1 ? '' : 's'}` : 'Nenhuma',
    description: props.security?.passkey_count ? 'Acesso sem palavra-passe disponível para dispositivos autorizados.' : 'Registe pelo menos uma passkey para reduzir atrito e risco.',
    icon: props.security?.passkey_count ? CheckCircleIcon : ExclamationTriangleIcon,
    tone: props.security?.passkey_count ? 'good' : 'warn',
  },
])

const updatePassword = () => {
  passwordForm.put(route('portal.user-password.update'), {
    errorBag: 'updatePassword',
    preserveScroll: true,
    onSuccess: () => passwordForm.reset('current_password', 'password', 'password_confirmation'),
  })
}

const resendVerification = () => {
  router.post(route('portal.verification.send'), {}, {
    preserveScroll: true,
  })
}

const requestPasswordConfirmation = async (action) => {
  const response = await axios.get(route('portal.password.confirmation'))

  if (response.data.confirmed) {
    action()

    return
  }

  pendingSensitiveAction.value = action
  passwordConfirmationOpen.value = true
}

const closePasswordConfirmation = () => {
  passwordConfirmationOpen.value = false
  passwordConfirmationForm.reset('password')
  passwordConfirmationForm.clearErrors()
  pendingSensitiveAction.value = null
}

const confirmPasswordForSensitiveAction = () => {
  passwordConfirmationForm.post(route('portal.password.confirm.store'), {
    preserveScroll: true,
    onSuccess: () => {
      const action = pendingSensitiveAction.value

      passwordConfirmationOpen.value = false
      passwordConfirmationForm.reset('password')
      passwordConfirmationForm.clearErrors()
      pendingSensitiveAction.value = null

      if (action) {
        action()
      }
    },
  })
}

const loadTwoFactorDetails = async () => {
  const [qrResponse, recoveryResponse] = await Promise.all([
    axios.get(route('portal.two-factor.qr-code')),
    axios.get(route('portal.two-factor.recovery-codes')),
  ])

  qrCode.value = qrResponse.data.svg
  recoveryCodes.value = recoveryResponse.data
}

const enableTwoFactor = () => {
  twoFactorWorking.value = true

  router.post(route('portal.two-factor.enable'), {}, {
    preserveScroll: true,
    onSuccess: async () => {
      twoFactorEnabled.value = true
      await loadTwoFactorDetails()
    },
    onFinish: () => {
      twoFactorWorking.value = false
    },
  })
}

const showTwoFactorDetails = async () => {
  twoFactorWorking.value = true

  try {
    await loadTwoFactorDetails()
  } finally {
    twoFactorWorking.value = false
  }
}

const confirmTwoFactor = () => {
  confirmationForm.post(route('portal.two-factor.confirm'), {
    preserveScroll: true,
    onSuccess: () => {
      twoFactorConfirmed.value = true
      confirmationForm.reset('code')
    },
  })
}

const regenerateRecoveryCodes = async () => {
  twoFactorWorking.value = true

  try {
    await axios.post(route('portal.two-factor.regenerate-recovery-codes'))
    const recoveryResponse = await axios.get(route('portal.two-factor.recovery-codes'))
    recoveryCodes.value = recoveryResponse.data
  } finally {
    twoFactorWorking.value = false
  }
}

const disableTwoFactor = () => {
  twoFactorWorking.value = true

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

const openSessionLogoutModal = () => {
  sessionLogoutOpen.value = true
}

const closeSessionLogoutModal = () => {
  sessionLogoutOpen.value = false
  sessionLogoutForm.reset('password')
  sessionLogoutForm.clearErrors()
}

const logoutOtherSessions = () => {
  sessionLogoutForm.delete(route('portal.other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: () => closeSessionLogoutModal(),
    onFinish: () => sessionLogoutForm.reset('password'),
  })
}
</script>
