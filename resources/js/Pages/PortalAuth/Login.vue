<template>
  <div class="relative min-h-screen overflow-hidden bg-[#07110f] py-8 text-[#f7f1e7] sm:py-12 lg:py-24" :class="commercialDocumentThemeClasses">
    <Head title="Login" />
    
    <div class="pointer-events-none absolute -left-24 top-0 h-[32rem] w-[32rem] rounded-full bg-[#1f7a68]/25 blur-3xl"></div>
    <div class="pointer-events-none absolute bottom-0 right-0 h-[36rem] w-[36rem] rounded-full bg-accent-300/18 blur-3xl"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-gradient-to-b from-[#fffaf0]/10 to-transparent"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-center">
        
        <!-- LEFT COLUMN - Features & Introduction -->
        <div class="space-y-10">
          <!-- Brand Section -->
          <div class="flex items-center gap-4">
            <img v-if="brandLogoUrl" class="h-12 max-w-48 object-contain" :src="brandLogoUrl" :alt="brandAppName" />
            <img v-else class="h-12 w-auto" src="../../../images/sncqa_logo.svg" :alt="brandAppName" />
            <div class="h-1 w-8 rounded-full bg-[#d9b05f]"></div>
            <h1 class="text-xl font-extrabold tracking-tight text-[#f7f1e7]">
              {{ brandAppName }}
            </h1>
          </div>

          <!-- Portal Info Card -->
          <div class="rounded-[2rem] border border-[#ded3bf]/15 bg-[#fffaf0]/[0.07] p-8 shadow-2xl shadow-black/30 backdrop-blur-xl">
            <div class="space-y-6">
              <!-- Badge -->
              <span class="inline-flex items-center rounded-full border border-[#ded3bf]/15 bg-[#fffaf0]/10 px-4 py-1.5 text-xs font-black uppercase tracking-[0.22em] text-accent-100">
                {{ $t('gestlab.pages.portal_login.portal_title') }}
              </span>

              <!-- Title & Description -->
              <div class="space-y-4">
                <h2 class="text-3xl font-black tracking-tight text-white">
                  {{ $t('gestlab.pages.portal_login.portal_subtitle') }}
                </h2>
                <p class="text-lg leading-relaxed text-[#d9e4dc]">
                  {{ $t('gestlab.pages.portal_login.portal_slogan') }}
                </p>
              </div>

              <!-- Features List -->
              <div class="space-y-6 pt-4">
                <div 
                  v-for="feature in features" 
                  :key="feature.name"
                  class="relative pl-10"
                >
                  <div class="absolute left-0 top-0 flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-r from-[#143d37] to-[#1f7a68] shadow-sm">
                    <component 
                      :is="feature.icon" 
                      class="h-4 w-4 text-white" 
                      aria-hidden="true" 
                    />
                  </div>
                  <div>
                    <h3 class="text-base font-semibold text-white">
                      {{ $t(feature.name) }}
                    </h3>
                    <p class="mt-1 text-sm leading-relaxed text-[#cbd8cf]">
                      {{ $t(feature.description) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - Login Form -->
        <div class="relative">
          <!-- Login Card -->
          <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0]/96 shadow-2xl shadow-black/30 backdrop-blur-xl dark:border-[#25443c] dark:bg-[#0c1714]/95">
            <!-- Card Header with Gradient -->
            <div class="bg-gradient-to-r from-[#143d37] via-[#165649] to-[#1f7a68] px-8 py-6">
              <div class="flex items-center justify-between">
                <div class="space-y-1">
                  <h2 class="text-xl font-bold text-white">
                    {{ $t('gestlab.pages.portal_login.title') }}
                  </h2>
                  <p class="text-sm text-[#eef7f3] opacity-90">
                    {{ $t('gestlab.pages.portal_login.subtitle') }}
                  </p>
                </div>
                <div class="hidden sm:block">
                  <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/10 backdrop-blur-sm">
                    <LockClosedIcon class="h-5 w-5 text-white" />
                  </div>
                </div>
              </div>
            </div>

            <!-- Card Content -->
            <div class="p-8">
              <form @submit.prevent="submit" class="space-y-6">
                <!-- Email Field -->
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <label for="email" class="flex items-center gap-2 text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">
                      <svg class="h-4 w-4 text-[#143d37] dark:text-accent-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                      </svg>
                      {{ $t('gestlab.pages.portal_login.email_input_title') }}
                    </label>
                    <span v-if="form.errors.email" class="inline-flex items-center gap-1 text-xs text-red-600">
                      <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                      </svg>
                      {{ $t('gestlab.pages.portal_login.required') }}
                    </span>
                  </div>
                  <div class="relative">
                    <input 
                      v-model="form.email" 
                      id="email" 
                      name="email" 
                      type="email" 
                      autocomplete="email"
                      required
                      :class="[
                        'block w-full rounded-2xl border px-4 py-3 pl-11 text-sm font-medium transition-all duration-200 focus:outline-none',
                        form.errors.email 
                          ? 'border-red-300 bg-red-50 text-red-900 placeholder-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' 
                          : 'border-[#d8cfbe] bg-[#fffdf7] text-[#15231f] placeholder:text-slate-400 focus:border-[#1f7a68] focus:ring-2 focus:ring-[#1f7a68]/20 dark:border-[#25443c] dark:bg-[#07110f]/70 dark:text-[#f7f1e7]'
                      ]"
                      :placeholder="$t('gestlab.pages.portal_login.email_placeholder')"
                    />
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                      <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                      </svg>
                    </div>
                  </div>
                  <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 102 0V5zm-1 7a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                    </svg>
                    {{ form.errors.email }}
                  </p>
                </div>

                <!-- Password Field -->
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <label for="password" class="flex items-center gap-2 text-sm font-bold text-[#15231f] dark:text-[#f7f1e7]">
                      <svg class="h-4 w-4 text-[#143d37] dark:text-accent-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                      </svg>
                      {{ $t('gestlab.pages.portal_login.password_input_title') }}
                    </label>
                    <Link
                      :href="route('portal.password.request')"
                      class="text-xs font-bold text-[#143d37] transition hover:text-[#1f7a68] dark:text-accent-200"
                    >
                      {{ $t('gestlab.pages.portal_login.forgot_password') }}
                    </Link>
                  </div>
                  <div class="relative">
                    <input 
                      v-model="form.password" 
                      id="password" 
                      name="password" 
                      type="password" 
                      autocomplete="current-password"
                      required
                      :class="[
                        'block w-full rounded-2xl border px-4 py-3 pl-11 text-sm font-medium transition-all duration-200 focus:outline-none',
                        form.errors.password 
                          ? 'border-red-300 bg-red-50 text-red-900 placeholder-red-400 focus:border-red-500 focus:ring-2 focus:ring-red-500/20' 
                          : 'border-[#d8cfbe] bg-[#fffdf7] text-[#15231f] placeholder:text-slate-400 focus:border-[#1f7a68] focus:ring-2 focus:ring-[#1f7a68]/20 dark:border-[#25443c] dark:bg-[#07110f]/70 dark:text-[#f7f1e7]'
                      ]"
                      :placeholder="$t('gestlab.pages.portal_login.password_placeholder')"
                    />
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                      <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                      </svg>
                    </div>
                  </div>
                  <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 flex items-center gap-1">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 102 0V5zm-1 7a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                    </svg>
                    {{ form.errors.password }}
                  </p>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                  <div class="flex items-center h-5">
                    <input 
                      v-model="form.remember" 
                      id="remember" 
                      name="remember" 
                      type="checkbox" 
                      class="h-4 w-4 rounded border-[#d8cfbe] text-[#143d37] focus:ring-[#1f7a68] focus:ring-offset-0 dark:border-[#25443c]"
                    />
                  </div>
                  <div class="ml-3 text-sm">
                    <label for="remember" class="text-[#15231f] dark:text-[#f7f1e7]">
                      {{ $t('gestlab.pages.portal_login.remember_input_title') }}
                    </label>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                      {{ $t('gestlab.pages.portal_login.remember_hint') }}
                    </p>
                  </div>
                </div>

                <!-- Login Button -->
                <div>
                  <button 
                    type="submit"
                    :disabled="form.processing"
                    :class="[
                      'w-full inline-flex items-center justify-center gap-3 rounded-2xl px-4 py-3.5 text-sm font-bold text-white shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2',
                      form.processing
                        ? 'bg-slate-400 cursor-not-allowed focus:ring-slate-400'
                        : 'bg-gradient-to-r from-[#143d37] to-[#1f7a68] hover:from-[#0d2a25] hover:to-[#176452] focus:ring-[#1f7a68]'
                    ]"
                  >
                    <svg 
                      v-if="form.processing" 
                      class="animate-spin h-4 w-4 text-white" 
                      xmlns="http://www.w3.org/2000/svg" 
                      fill="none" 
                      viewBox="0 0 24 24"
                    >
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg 
                      v-else 
                      class="h-4 w-4" 
                      fill="none" 
                      viewBox="0 0 24 24" 
                      stroke-width="2" 
                      stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    {{ form.processing 
                      ? $t('gestlab.pages.portal_login.login_processing') 
                      : $t('gestlab.pages.portal_login.login_button_title') 
                    }}
                  </button>
                </div>

                <!-- Divider -->
                <div class="relative">
                  <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-[#ded3bf] dark:border-[#25443c]"></div>
                  </div>
                  <div class="relative flex justify-center text-sm">
                    <span class="bg-[#fffaf0] px-4 text-slate-500 dark:bg-[#0c1714] dark:text-slate-400">
                      {{ $t('gestlab.pages.portal_login.or_continue_with') }}
                    </span>
                  </div>
                </div>

                <button
                  type="button"
                  class="inline-flex w-full items-center justify-center gap-3 rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-3.5 text-sm font-bold text-[#15231f] shadow-sm transition-all duration-200 hover:bg-[#ede5d6] focus:outline-none focus:ring-2 focus:ring-[#1f7a68]/30 focus:ring-offset-2 dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]"
                  :disabled="passkeyProcessing"
                  @click="loginWithPasskey"
                >
                  <svg v-if="passkeyProcessing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                  </svg>
                  <LockClosedIcon v-else class="h-4 w-4" />
                  <span>{{ passkeyProcessing ? $t('gestlab.pages.portal_login.passkey_processing') : $t('gestlab.pages.portal_login.passkey_button') }}</span>
                </button>

                <form ref="passkeyLoginForm" :action="route('portal.passkeys.login')" method="post" class="hidden">
                  <input type="hidden" name="_token" :value="csrfToken">
                  <input type="hidden" name="remember" :value="form.remember ? '1' : ''">
                  <input type="hidden" name="start_authentication_response" :value="passkeyResponse">
                </form>

                <!-- Employee Portal Button -->
                <div>
                  <Link
                    :href="route('login')"
                    class="group inline-flex w-full items-center justify-center gap-3 rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-3.5 text-sm font-bold text-[#15231f] shadow-sm transition-all duration-200 hover:bg-[#ede5d6] focus:outline-none focus:ring-2 focus:ring-[#1f7a68]/30 focus:ring-offset-2 dark:border-[#25443c] dark:bg-[#07110f]/80 dark:text-[#f7f1e7] dark:hover:bg-[#10231f]"
                  >
                    <svg class="h-4 w-4 text-[#143d37] group-hover:text-[#1f7a68] dark:text-accent-200" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    {{ $t('gestlab.pages.portal_login.employee_portal_button_title') }}
                  </Link>
                </div>

                <!-- Help Text -->
                <div class="text-center pt-2">
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.pages.portal_login.need_help') }}
                    <Link 
                      href="/help" 
                      class="font-bold text-[#143d37] transition-colors duration-200 hover:text-[#1f7a68] hover:underline dark:text-accent-200"
                    >
                      {{ $t('gestlab.pages.portal_login.contact_support') }}
                    </Link>
                  </p>
                </div>
              </form>
            </div>

            <!-- Card Footer -->
            <div class="border-t border-[#ded3bf] bg-[#f4efe4] px-8 py-4 dark:border-[#25443c] dark:bg-[#07110f]/80">
              <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>{{ brandAppName }}</span>
                <span class="flex items-center gap-1">
                  <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.316 3.051a1 1 0 01.633 1.265l-4 12a1 1 0 11-1.898-.632l4-12a1 1 0 011.265-.633zM5.707 6.293a1 1 0 010 1.414L3.414 10l2.293 2.293a1 1 0 11-1.414 1.414l-3-3a1 1 0 010-1.414l3-3a1 1 0 011.414 0zm8.586 0a1 1 0 011.414 0l3 3a1 1 0 010 1.414l-3 3a1 1 0 11-1.414-1.414L16.586 10l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                  v{{ $t('gestlab.app.version') }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { startAuthentication } from '@simplewebauthn/browser';
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { CloudArrowUpIcon, LockClosedIcon, ServerIcon } from '@heroicons/vue/20/solid';
import EmptyLayout from "../../Shared/EmptyLayout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

defineOptions({
  layout: EmptyLayout
});

const features = [
  {
    name: 'gestlab.pages.portal_login.points.first.title',
    description: 'gestlab.pages.portal_login.points.first.description',
    icon: CloudArrowUpIcon,
  },
  {
    name: 'gestlab.pages.portal_login.points.second.title',
    description: 'gestlab.pages.portal_login.points.second.description',
    icon: LockClosedIcon,
  },
  {
    name: 'gestlab.pages.portal_login.points.third.title',
    description: 'gestlab.pages.portal_login.points.third.description',
    icon: ServerIcon,
  },
];

const page = usePage();
const brandSettings = computed(() => page.props.settings ?? {});
const brandLogoUrl = computed(() => brandSettings.value.logo_url ?? null);
const brandAppName = computed(() => brandSettings.value.app_name ?? 'LIMS Unleashed');
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
const passkeyProcessing = ref(false);
const passkeyResponse = ref('');
const passkeyLoginForm = ref(null);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.transform(data => ({
    ...data,
    remember: form.remember ? 'on' : ''
  }))
  .post(route('portal.login.store'), {
    onFinish: () => form.reset('password'),
  });
};

const loginWithPasskey = async () => {
  passkeyProcessing.value = true;

  try {
    const response = await fetch(route('portal.passkeys.authentication_options'), {
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
