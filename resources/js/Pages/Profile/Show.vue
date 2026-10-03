<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  LogOut as ArrowRightStartOnRectangleIcon,
  BellRing as BellAlertIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Fingerprint as FingerPrintIcon,
  IdCard as IdentificationIcon,
  KeyRound as KeyIcon,
  ShieldCheck as ShieldCheckIcon,
} from '@lucide/vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import SettingsSection from '@/Components/settings/SettingsSection.vue'
import DeleteUserForm from '@/Shared/delete-user-form.vue'
import LogoutOtherBrowserSessionsForm from './Partials/logout-other-browser-sessions-form.vue'
import PasskeyManagementForm from './Partials/passkey-management-form.vue'
import TwoFactorAuthenticationForm from './Partials/two-factor-authentication-form.vue'
import UpdatePasswordForm from './Partials/update-password-form.vue'
import UpdateProfileInformationForm from './Partials/update-profile-information-form.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  confirmsTwoFactorAuthentication: { type: Boolean, default: false },
  mustVerifyEmail: { type: Boolean, default: false },
  sessions: { type: Array, default: () => [] },
  passkeys: { type: Array, default: () => [] },
})

const page = usePage()
const user = computed(() => page.props.auth?.user ?? {})
const fortify = computed(() => page.props.fortify ?? {})

const navigation = computed(() => [
  { id: 'profile', label: 'Perfil', icon: IdentificationIcon, show: fortify.value.canUpdateProfileInformation !== false },
  { id: 'password', label: 'Palavra-passe', icon: KeyIcon, show: fortify.value.canUpdatePassword !== false },
  { id: 'two-factor', label: 'Duplo factor', icon: ShieldCheckIcon, show: fortify.value.canManageTwoFactorAuthentication !== false },
  { id: 'passkeys', label: 'Passkeys', icon: FingerPrintIcon, show: true },
  { id: 'sessions', label: 'Sessões', icon: ArrowRightStartOnRectangleIcon, show: true },
  { id: 'danger-zone', label: 'Zona crítica', icon: ExclamationTriangleIcon, show: true },
].filter((item) => item.show))
</script>

<template>
  <Head title="Conta e segurança" />

  <div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <header class="border-b border-[var(--ds-border)] pb-6">
      <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
        <div class="max-w-3xl">
          <p class="ds-kicker">Preferências pessoais</p>
          <h1 class="ds-heading mt-2 text-2xl sm:text-3xl">Conta e segurança</h1>
          <p class="ds-copy mt-2 text-sm sm:text-base">
            Actualize a sua identidade no laboratório e controle os métodos usados para aceder ao LIMS.
          </p>
        </div>

        <div class="flex flex-wrap gap-2" aria-label="Estado da conta">
          <Link :href="route('notification-preferences.edit')" class="ds-button ds-button-secondary">
            <BellAlertIcon class="h-4 w-4" /> Notificações
          </Link>
          <span class="ds-chip" :class="user.email_verified_at ? 'ds-chip-success' : 'ds-chip-warning'">
            <span class="h-1.5 w-1.5 rounded-full bg-current" />
            Correio electrónico {{ user.email_verified_at ? 'verificado' : 'por verificar' }}
          </span>
          <span class="ds-chip" :class="confirmsTwoFactorAuthentication ? 'ds-chip-success' : 'ds-chip-neutral'">
            <span class="h-1.5 w-1.5 rounded-full bg-current" />
            2FA {{ confirmsTwoFactorAuthentication ? 'activo' : 'inactivo' }}
          </span>
          <span class="ds-chip ds-chip-neutral">{{ sessions.length }} {{ sessions.length === 1 ? 'sessão' : 'sessões' }}</span>
        </div>
      </div>
    </header>

    <div class="grid gap-6 lg:grid-cols-[13rem_minmax(0,1fr)] lg:items-start">
      <aside class="lg:sticky lg:top-20">
        <nav class="overflow-x-auto border-b border-[var(--ds-border)] lg:border-b-0" aria-label="Secções da conta">
          <ul class="flex min-w-max gap-1 pb-3 lg:min-w-0 lg:flex-col lg:pb-0">
            <li v-for="item in navigation" :key="item.id">
              <a
                :href="`#${item.id}`"
                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm font-semibold text-[var(--ds-text-muted)] transition hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--primary-500-rgb))]"
              >
                <component :is="item.icon" class="h-4 w-4 shrink-0" />
                {{ item.label }}
              </a>
            </li>
          </ul>
        </nav>

        <div class="mt-6 hidden border-t border-[var(--ds-border)] pt-5 lg:block">
          <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Sessão actual</p>
          <p class="mt-2 break-words text-sm font-semibold text-[var(--ds-text)]">{{ user.name }}</p>
          <p class="mt-1 break-all text-xs text-[var(--ds-text-muted)]">{{ user.email }}</p>
        </div>
      </aside>

      <div class="ds-panel divide-y divide-[var(--ds-border)] overflow-hidden">
        <SettingsSection
          v-if="fortify.canUpdateProfileInformation !== false"
          id="profile"
          title="Perfil"
          description="Informação usada na identificação de utilizadores, aprovações e documentos laboratoriais."
        >
          <template #icon><IdentificationIcon class="h-4 w-4" /></template>
          <UpdateProfileInformationForm :user="user" :must-verify-email="mustVerifyEmail" />
        </SettingsSection>

        <SettingsSection
          v-if="fortify.canUpdatePassword !== false"
          id="password"
          title="Palavra-passe"
          description="Utilize uma palavra-passe exclusiva e suficientemente longa para proteger os registos do laboratório."
        >
          <template #icon><KeyIcon class="h-4 w-4" /></template>
          <UpdatePasswordForm />
        </SettingsSection>

        <SettingsSection
          v-if="fortify.canManageTwoFactorAuthentication !== false"
          id="two-factor"
          title="Autenticação de dois factores"
          description="Adicione uma prova de identidade adicional ao processo de início de sessão."
        >
          <template #icon><ShieldCheckIcon class="h-4 w-4" /></template>
          <TwoFactorAuthenticationForm :enabled-and-confirmed="confirmsTwoFactorAuthentication" />
        </SettingsSection>

        <SettingsSection
          id="passkeys"
          title="Passkeys"
          description="Associe dispositivos de confiança para autenticação forte sem depender apenas da palavra-passe."
        >
          <template #icon><FingerPrintIcon class="h-4 w-4" /></template>
          <PasskeyManagementForm :passkeys="passkeys" />
        </SettingsSection>

        <SettingsSection
          id="sessions"
          title="Sessões do navegador"
          description="Revise os dispositivos recentes e encerre acessos que já não reconhece."
        >
          <template #icon><ArrowRightStartOnRectangleIcon class="h-4 w-4" /></template>
          <LogoutOtherBrowserSessionsForm :sessions="sessions" />
        </SettingsSection>

        <SettingsSection
          id="danger-zone"
          title="Eliminar conta"
          description="Esta operação remove permanentemente a conta e termina o acesso ao LIMS."
        >
          <template #icon><ExclamationTriangleIcon class="h-4 w-4 text-rose-600 dark:text-rose-300" /></template>
          <DeleteUserForm />
        </SettingsSection>
      </div>
    </div>
  </div>
</template>
