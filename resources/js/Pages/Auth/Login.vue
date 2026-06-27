<template>
  <Head title="Login" />
  <div class="relative min-h-screen overflow-hidden bg-[#07110f] text-[#f7f1e7]" :class="commercialDocumentThemeClasses">
    <!-- Decorative Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
      <div class="absolute -left-24 top-0 h-[32rem] w-[32rem] rounded-full bg-[#1f7a68]/25 blur-3xl"></div>
      <div class="absolute bottom-0 right-0 h-[36rem] w-[36rem] rounded-full bg-accent-300/18 blur-3xl"></div>
      <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-[#fffaf0]/10 to-transparent"></div>
    </div>

    <div class="relative min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <!-- Login Container -->
      <div class="max-w-6xl w-full mx-auto">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(360px,0.8fr)] lg:gap-14">
          
          <!-- Left Column - Brand & Welcome -->
          <div class="hidden flex-col justify-center lg:flex">
            <div class="mx-auto max-w-xl rounded-[2rem] border border-[#ded3bf]/15 bg-[#fffaf0]/[0.07] p-6 shadow-2xl shadow-black/30 backdrop-blur-xl lg:mx-0 lg:p-8">
              <!-- Logo -->
              <div class="flex items-center gap-4 mb-8">
                <!-- <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-[#143d37] to-[#1f7a68] shadow-lg">
                  <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                  </svg>
                </div> -->
                <img
                  v-if="brandLogoUrl"
                  class="relative inset-0 max-h-20 object-contain"
                  :src="brandLogoUrl"
                  :alt="brandAppName"
                  width="50%"
                />
                <img
                  v-else
                  class="relative inset-0 max-h-20"
                  src="../../../images/sncqa_logo.svg"
                  alt=""
                  width="50%"
                />

                <!-- <div>
                  <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">LIMS</h1>
                  <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Laboratory Information Management System</p>
                </div> -->
              </div>

              <!-- Welcome Message -->
              <div class="mb-10">
                <p class="mb-4 inline-flex rounded-full border border-[#ded3bf]/15 bg-[#fffaf0]/10 px-4 py-1.5 text-xs font-black uppercase tracking-[0.24em] text-accent-100">
                  ISO 17025 workspace
                </p>
                <h2 class="text-4xl font-black tracking-tight text-white mb-4 lg:text-5xl">
                  {{ brandLoginHeadline }}
                </h2>
                <p class="text-lg leading-8 text-slate-300">
                  {{ brandLoginSubheadline }}
                </p>
              </div>

              <!-- Features -->
              <div class="space-y-6">
                <div class="flex items-start gap-4">
                  <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#fffaf0]/10 text-accent-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-white mb-1">
                      {{ $t('gestlab.pages.login.feature1_title') }}
                    </h3>
                    <p class="text-sm leading-6 text-slate-300">
                      {{ $t('gestlab.pages.login.feature1_description') }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#fffaf0]/10 text-accent-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-white mb-1">
                      {{ $t('gestlab.pages.login.feature2_title') }}
                    </h3>
                    <p class="text-sm leading-6 text-slate-300">
                      {{ $t('gestlab.pages.login.feature2_description') }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-4">
                  <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#fffaf0]/10 text-accent-100">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-lg font-semibold text-white mb-1">
                      {{ $t('gestlab.pages.login.feature3_title') }}
                    </h3>
                    <p class="text-sm leading-6 text-slate-300">
                      {{ $t('gestlab.pages.login.feature3_description') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Login Form -->
          <div class="flex items-center justify-center">
            <div class="w-full max-w-md">
              <div class="mb-5 flex items-center justify-between gap-4 px-1 lg:hidden">
                <img
                  v-if="brandLogoUrl"
                  class="h-10 max-w-44 object-contain"
                  :src="brandLogoUrl"
                  :alt="brandAppName"
                />
                <img
                  v-else
                  class="h-10 w-auto"
                  src="../../../images/sncqa_logo.svg"
                  :alt="brandAppName"
                />
                <span class="rounded-full border border-[#ded3bf]/20 bg-[#fffaf0]/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.18em] text-accent-100">
                  {{ $t('gestlab.pages.login.internal_area') }}
                </span>
              </div>

              <!-- Login Card -->
              <div class="rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0]/96 p-5 shadow-2xl shadow-black/30 backdrop-blur-xl dark:border-[#25443c] dark:bg-[#0c1714]/95 sm:p-8">
                <div class="text-center mb-8">
                  <h2 class="mb-2 text-2xl font-extrabold text-[#15231f] dark:text-[#f7f1e7]">
                    {{ $t('gestlab.pages.login.sign_in') }}
                  </h2>
                  <p class="text-sm font-medium text-slate-600 dark:text-slate-300">
                    {{ $t('gestlab.pages.login.sign_in_description') }}
                  </p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                  <div
                    class="hidden"
                    :data-passkey-authentication-options-url="route('passkeys.authentication_options')"
                    :data-passkey-login-url="route('passkeys.login')"
                  ></div>

                  <!-- Email Input -->
                  <div class="space-y-2">
                    <label for="email" class="ds-field-label block">
                      {{ $t('gestlab.pages.login.email_input_title') }}
                    </label>
                    <div class="relative">
                      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                      </div>
                      <input
                        v-model="form.email"
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        :class="[
                        'ds-field pl-10',
                          form.errors.email ? 'border-red-300 focus:border-red-300 focus:ring-2 focus:ring-red-500/20' : ''
                        ]"
                        :placeholder="$t('gestlab.pages.login.email_placeholder')"
                      />
                    </div>
                    <p v-if="form.errors.email" class="text-xs text-red-600">
                      {{ form.errors.email }}
                    </p>
                  </div>

                  <!-- Password Input -->
                  <div class="space-y-2">
                    <div class="flex items-center justify-between">
                      <label for="password" class="ds-field-label block">
                        {{ $t('gestlab.pages.login.password_input_title') }}
                      </label>
                      <Link 
                        :href="route('password.request')" 
                        class="text-xs font-bold text-[#143d37] transition-colors duration-200 hover:text-[#1f7a68] dark:text-accent-200"
                      >
                        {{ $t('gestlab.pages.login.forgot_password') }}
                      </Link>
                    </div>
                    <div class="relative">
                      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                      </div>
                      <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        :class="[
                        'ds-field pl-10 pr-12',
                          form.errors.password ? 'border-red-300 focus:border-red-300 focus:ring-2 focus:ring-red-500/20' : ''
                        ]"
                        :placeholder="$t('gestlab.pages.login.password_placeholder')"
                      />
                      <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <button
                          type="button"
                          @click="togglePasswordVisibility"
                          class="rounded-lg p-1 text-slate-400 transition-colors duration-200 hover:bg-[#ede5d6] hover:text-[#143d37] dark:text-accent-200 dark:hover:bg-[#10231f]"
                          :title="showPassword ? $t('gestlab.pages.login.hide_password') : $t('gestlab.pages.login.show_password')"
                        >
                          <svg v-if="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                          </svg>
                          <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                          </svg>
                        </button>
                      </div>
                    </div>
                    <p v-if="form.errors.password" class="text-xs text-red-600">
                      {{ form.errors.password }}
                    </p>
                  </div>

                  <!-- Remember Me -->
                  <div class="flex items-center">
                    <input
                      v-model="form.remember"
                      id="remember-me"
                      name="remember-me"
                      type="checkbox"
                      class="ds-checkbox"
                    />
                    <label for="remember-me" class="ml-3 text-sm font-medium text-slate-700 dark:text-slate-300">
                      {{ $t('gestlab.pages.login.remember_input_title') }}
                    </label>
                  </div>

                  <!-- Error Messages -->
                  <div v-if="form.hasErrors" class="bg-red-50 rounded-xl p-4 border border-red-200 dark:border-red-900/60 dark:bg-red-950/30">
                    <div class="flex items-center gap-3">
                      <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-red-800">
                          {{ $t('gestlab.pages.login.invalid_credentials_title') }}
                        </p>
                        <p class="text-xs text-red-700 mt-1">
                          {{ $t('gestlab.pages.login.invalid_credentials_description') }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- Login Button -->
                  <button
                    type="submit"
                    :disabled="form.processing"
                    :class="[
                      'inline-flex w-full items-center justify-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold shadow-lg transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1f7a68] focus:ring-offset-2',
                      form.processing
                        ? 'bg-gray-100 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                        : 'bg-gradient-to-r from-[#143d37] to-[#1f7a68] text-white hover:from-[#0d2a25] hover:to-[#176452] hover:shadow-xl active:scale-[0.98]'
                    ]"
                  >
                    <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    {{ form.processing ? $t('gestlab.pages.login.processing') : $t('gestlab.pages.login.login_button_title') }}
                  </button>

                  <button
                    type="button"
                    @click="loginWithPasskey"
                    :disabled="passkeyProcessing"
                    class="inline-flex w-full items-center justify-center gap-3 rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-3.5 text-sm font-bold text-[#15231f] shadow-sm transition hover:bg-[#ede5d6] disabled:cursor-not-allowed disabled:opacity-60 dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]"
                  >
                    <svg v-if="passkeyProcessing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75 11.25 15 15 9.75M4.5 9.75V7.5A2.25 2.25 0 0 1 6.75 5.25h10.5A2.25 2.25 0 0 1 19.5 7.5v9A2.25 2.25 0 0 1 17.25 18.75H6.75A2.25 2.25 0 0 1 4.5 16.5v-6.75Z" />
                    </svg>
                    <span>{{ passkeyProcessing ? $t('gestlab.pages.login.passkey_processing') : $t('gestlab.pages.login.passkey_button') }}</span>
                  </button>

                  <p v-if="$page.props.errors?.social" class="text-sm text-red-600">
                    {{ $page.props.errors.social }}
                  </p>
                </form>

                <form ref="passkeyLoginForm" :action="route('passkeys.login')" method="post" class="hidden">
                  <input type="hidden" name="_token" :value="csrfToken" />
                  <input type="hidden" name="remember" :value="form.remember ? 'on' : ''" />
                  <input type="hidden" name="start_authentication_response" :value="passkeyResponse" />
                </form>

                <div v-if="socialProviders.length" class="mt-6 space-y-3">
                  <div class="flex items-center gap-3">
                    <div class="h-px flex-1 bg-[#ded3bf] dark:bg-[#25443c]"></div>
                    <span class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">SSO</span>
                    <div class="h-px flex-1 bg-[#ded3bf] dark:bg-[#25443c]"></div>
                  </div>
                  <div class="grid gap-3">
                    <a
                      v-for="provider in socialProviders"
                      :key="provider.service"
                      :href="route('auth.redirect', provider.service)"
                      class="inline-flex items-center justify-center gap-3 rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-3 text-sm font-bold text-[#15231f] shadow-sm transition hover:bg-[#ede5d6] dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]"
                    >
                      <span>Entrar com {{ provider.label }}</span>
                    </a>
                  </div>
                </div>

                <!-- Divider -->
                <div class="relative my-8">
                  <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-[#ded3bf] dark:border-[#25443c]" />
                  </div>
                  <div class="relative flex justify-center text-sm">
                    <span class="bg-[#fffaf0] px-4 font-semibold text-slate-500 dark:bg-[#0c1714] dark:text-slate-400">
                      {{ $t('gestlab.pages.login.or') }}
                    </span>
                  </div>
                </div>

                <!-- Client Portal Button -->
                <Link 
                  :href="route('portal.login')"
                  :class="[
                    'inline-flex w-full items-center justify-center gap-3 rounded-2xl border px-4 py-3.5 text-sm font-bold shadow-sm transition-all duration-300 hover:shadow-md active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-[#1f7a68] focus:ring-offset-2',
                    'border-[#d8cfbe] bg-[#fffdf7] text-[#15231f] hover:border-[#b8ad97] hover:bg-[#ede5d6] hover:text-[#143d37] dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]'
                  ]"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  {{ $t('gestlab.pages.login.access_client_portal') }}
                </Link>

                <!-- Footer -->
                <div class="mt-8 border-t border-[#ded3bf] pt-6 dark:border-[#25443c]">
                  <div class="text-center">
                    <p class="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                      {{ $t('gestlab.pages.login.need_help') }}
                      <Link href="/help" class="font-bold text-[#143d37] transition-colors duration-200 hover:text-[#1f7a68] dark:text-accent-200">
                        {{ $t('gestlab.pages.login.contact_support') }}
                      </Link>
                    </p>
                    <p class="text-xs font-medium text-slate-400">
                      {{ $t('gestlab.pages.login.copyright', { year: currentYear }) }} •
                      {{ $t('gestlab.pages.login.version') }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Security Badge -->
              <div class="mt-6 flex items-center justify-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4 text-accent-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span>
                  {{ $t('gestlab.pages.login.secure_connection') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import EmptyLayout from "../../Shared/EmptyLayout.vue";

export default {
    layout: EmptyLayout
}
</script>

<script setup>
import { startAuthentication } from '@simplewebauthn/browser';
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

const showPassword = ref(false);
const passkeyProcessing = ref(false);
const passkeyResponse = ref('');
const passkeyLoginForm = ref(null);
const currentYear = new Date().getFullYear();
const page = usePage();
const socialProviders = page.props.socialAuth?.providers ?? [];
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const brandSettings = computed(() => page.props.settings ?? {});
const brandLogoUrl = computed(() => brandSettings.value.logo_url ?? null);
const brandAppName = computed(() => brandSettings.value.app_name ?? 'LIMS Unleashed');
const brandLoginHeadline = computed(() => brandSettings.value.login_headline ?? 'Bem-vindo de volta');
const brandLoginSubheadline = computed(() => brandSettings.value.login_subheadline ?? 'Aceda à operação, acompanhe a rastreabilidade e mantenha o laboratório sob controlo.');

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : ''
    }))
    .post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const loginWithPasskey = async () => {
    passkeyProcessing.value = true;

    try {
        const response = await fetch(route('passkeys.authentication_options'), {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error('Não foi possível iniciar a autenticação com passkey.');
        }

        const options = await response.json();
        const assertion = await startAuthentication({ optionsJSON: options });

        passkeyResponse.value = JSON.stringify(assertion);
        passkeyLoginForm.value?.submit();
    } catch (error) {
        passkeyProcessing.value = false;
        form.setError('email', error?.message || 'Não foi possível autenticar com passkey.');
    }
};
</script>

<style scoped>
/* Smooth transitions */
button, input, a, label {
  transition: all 0.2s ease-in-out;
}

/* Background animation */
.bg-gradient-to-br {
  background-size: 200% 200%;
  animation: gradientShift 20s ease infinite;
}

@keyframes gradientShift {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}

/* Subtle pulse animation for security badge */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.8;
  }
}

.text-green-500 {
  animation: pulse 2s ease-in-out infinite;
}
</style>
