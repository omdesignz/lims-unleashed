<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- Header Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <svg class="h-7 w-7 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
            </svg>
            Segurança
          </h1>
          <p class="mt-2 text-gray-600">
            A segurança em sistemas informáticos é uma questão crítica e importante para empresas e indivíduos.
            <span class="font-semibold text-blue-900">{{ $page.props.auth.name }}</span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <div v-if="confirmsTwoFactorAuthentication" class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-700/10">
            <div class="h-2 w-2 rounded-full bg-green-500 mr-2"></div>
            2FA Activo
          </div>
          <div v-else class="inline-flex items-center rounded-full bg-yellow-50 px-3 py-1 text-sm font-medium text-yellow-700 ring-1 ring-inset ring-yellow-700/10">
            <div class="h-2 w-2 rounded-full bg-yellow-500 mr-2"></div>
            2FA Inactivo
          </div>
        </div>
      </div>
    </div>

    <!-- Security Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Column (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Profile Information Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Informação do Perfil
            </h2>
          </div>
          <div class="p-6">
            <UpdateProfileInformationForm :user="$page.props.auth" />
          </div>
        </div>

        <!-- Password Update Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
              </svg>
              Alterar Palavra-passe
            </h2>
          </div>
          <div class="p-6">
            <UpdatePasswordForm />
          </div>
        </div>

        <!-- Two-Factor Authentication Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              Autenticação de Dois Factores
            </h2>
          </div>
          <div class="p-6">
            <TwoFactorAuthenticationForm :requires-confirmation="confirmsTwoFactorAuthentication" />
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11a5 5 0 1110 0v2a2 2 0 002 2v1H5v-1a2 2 0 002-2v-2z" />
              </svg>
              Passkeys
            </h2>
          </div>
          <div class="p-6">
            <PasskeyManagementForm :passkeys="passkeys" />
          </div>
        </div>

        <!-- Browser Sessions Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
              </svg>
              Sessões do Browser
            </h2>
          </div>
          <div class="p-6">
            <LogoutOtherBrowserSessionsForm :sessions="sessions" />
          </div>
        </div>

        <!-- Delete Account Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
              Eliminar Conta
            </h2>
          </div>
          <div class="p-6">
            <DeleteUserForm />
          </div>
        </div>
      </div>

      <!-- Right Column (1/3 width) -->
      <div class="space-y-6">
        <!-- Security Status Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            Estado da Segurança
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-green-500"></div>
                <span class="text-sm text-gray-700">Autenticação de email</span>
              </div>
              <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div :class="[
                  'h-3 w-3 rounded-full',
                  confirmsTwoFactorAuthentication ? 'bg-green-500' : 'bg-yellow-500'
                ]"></div>
                <span class="text-sm text-gray-700">2FA</span>
              </div>
              <span :class="[
                'text-xs font-medium px-2.5 py-0.5 rounded-full',
                confirmsTwoFactorAuthentication 
                  ? 'bg-green-100 text-green-800' 
                  : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ confirmsTwoFactorAuthentication ? 'Activo' : 'Inactivo' }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-blue-500"></div>
                <span class="text-sm text-gray-700">Sessões activas</span>
              </div>
              <span class="text-sm font-semibold text-blue-900">
                {{ sessions?.length }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-full bg-gray-400"></div>
                <span class="text-sm text-gray-700">Última actualização</span>
              </div>
              <span class="text-xs text-gray-500">
                Hoje
              </span>
            </div>
          </div>

          <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="text-xs text-gray-600">
              <p class="font-medium mb-1">Recomendações:</p>
              <ul class="space-y-1 list-disc pl-4">
                <li v-if="!confirmsTwoFactorAuthentication">Active a autenticação de dois factores</li>
                <li>Actualize a palavra-passe regularmente</li>
                <li>Revise as sessões activas mensalmente</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            Acções Rápidas
          </h3>
          <div class="space-y-3">
            <button 
              @click="scrollToSection('password')"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Alterar palavra-passe
              </span>
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <button 
              @click="scrollToSection('2fa')"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Gerir 2FA
              </span>
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>

            <button 
              @click="scrollToSection('sessions')"
              class="w-full flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 transition-colors duration-200"
            >
              <span class="flex items-center gap-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Ver sessões
              </span>
              <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Security Tips Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Dicas de Segurança
          </h3>
          <div class="space-y-3">
            <div class="bg-blue-50 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-blue-900 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <p class="text-xs text-blue-900">
                  Use palavras-passe fortes e únicas para cada serviço
                </p>
              </div>
            </div>
            <div class="bg-yellow-50 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-yellow-900 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <p class="text-xs text-yellow-900">
                  Nunca partilhe os seus códigos 2FA
                </p>
              </div>
            </div>
            <div class="bg-green-50 rounded-lg p-3">
              <div class="flex items-start gap-2">
                <svg class="h-4 w-4 text-green-900 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="text-xs text-green-900">
                  Faça logout de dispositivos não confiáveis
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import DeleteUserForm from "../../Shared/delete-user-form.vue";
import LogoutOtherBrowserSessionsForm from "./Partials/logout-other-browser-sessions-form.vue";
import PasskeyManagementForm from "./Partials/passkey-management-form.vue";
import TwoFactorAuthenticationForm from "./Partials/two-factor-authentication-form.vue";
import UpdatePasswordForm from "./Partials/update-password-form.vue";
import UpdateProfileInformationForm from "./Partials/update-profile-information-form.vue";
import Layout from "@/Shared/Layouts/Layout.vue"
import { ref } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
    passkeys: Array,
});

defineOptions({
    layout: Layout
});

// Scroll to section function
const scrollToSection = (sectionId) => {
  const element = document.getElementById(sectionId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' });
  }
};
</script>

<style scoped>
/* Smooth transitions */
button, a {
  transition: all 0.2s ease-in-out;
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
