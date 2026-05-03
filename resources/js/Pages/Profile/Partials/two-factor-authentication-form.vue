<template>
  <ActionSection>
    <template #title>
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
        Autenticação de Dois Factores
      </div>
    </template>

    <template #description>
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="text-gray-600">
            Adicione segurança adicional à sua conta usando autenticação de dois factores.
          </p>
          <p class="text-sm text-gray-500 mt-1">
            Proteja o seu acesso com um segundo factor de autenticação.
          </p>
        </div>
      </div>
    </template>

    <template #content>
      <!-- Status Header -->
      <div class="mb-6">
        <div v-if="twoFactorEnabled && ! confirming" class="flex items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
            <svg class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-green-900">
              Autenticação de dois factores activa
            </h3>
            <p class="text-sm text-green-700">
              A sua conta está protegida com um segundo factor de segurança.
            </p>
          </div>
        </div>

        <div v-else-if="twoFactorEnabled && confirming" class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
            <svg class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-blue-900">
              Concluir activação
            </h3>
            <p class="text-sm text-blue-700">
              Por favor, conclua a configuração da autenticação de dois factores.
            </p>
          </div>
        </div>

        <div v-else class="flex items-center gap-3 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100">
            <svg class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-yellow-900">
              Autenticação de dois factores inactiva
            </h3>
            <p class="text-sm text-yellow-700">
              A sua conta não está protegida com um segundo factor de segurança.
            </p>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="mb-6">
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <p class="text-sm text-gray-700">
            Quando a autenticação de dois factores estiver activa, será solicitado um token seguro durante o login.
            Pode obter este token através de aplicações como Google Authenticator, Authy ou Microsoft Authenticator.
          </p>
        </div>
      </div>

      <!-- QR Code Section -->
      <div v-if="twoFactorEnabled && qrCode" class="mb-6">
        <div class="space-y-4">
          <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
              {{ confirming ? 'Configuração do Autenticador' : 'Código QR Activo' }}
            </h4>
            
            <div class="text-sm text-gray-600 mb-4">
              <p v-if="confirming">
                Para concluir a activação, digitalize o código QR abaixo usando o aplicativo autenticador do seu telemóvel ou introduza a chave de configuração manualmente.
              </p>
              <p v-else>
                Use o código QR abaixo para configurar o seu aplicativo autenticador. Este código é necessário para gerar os tokens de acesso.
              </p>
            </div>

            <!-- QR Code Display -->
            <div class="flex flex-col items-center space-y-4">
              <div class="bg-white p-4 rounded-lg border border-gray-300 shadow-sm" v-html="qrCode" />
              
              <!-- Setup Key -->
              <div v-if="setupKey" class="w-full">
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Chave de Configuração</span>
                    <button 
                      @click="copySetupKey"
                      class="text-xs text-blue-900 hover:text-blue-700"
                    >
                      Copiar
                    </button>
                  </div>
                  <div class="font-mono text-sm bg-white px-3 py-2 rounded border border-gray-300 text-center">
                    {{ setupKey }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Verification Code Input -->
          <div v-if="confirming" class="bg-white rounded-lg border border-gray-200 p-5">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">
              Verificar Configuração
            </h4>
            <p class="text-sm text-gray-600 mb-4">
              Introduza o código de 6 dígitos gerado pelo seu aplicativo autenticador para verificar a configuração.
            </p>
            
            <div class="space-y-3">
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Código de Verificação
                </label>
                <div class="relative">
                  <TextInput
                    id="code"
                    v-model="confirmationForm.code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    autofocus
                    autocomplete="one-time-code"
                    placeholder="000000"
                    class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-center text-lg font-mono tracking-widest focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20"
                    :class="confirmationForm.errors.code ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
                    @keyup.enter="confirmTwoFactorAuthentication"
                  />
                </div>
                <InputError :message="confirmationForm.errors.code" class="text-xs" />
              </div>
              
              <button
                @click="confirmTwoFactorAuthentication"
                :disabled="confirmationForm.processing || !confirmationForm.code"
                :class="[
                  'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                  confirmationForm.processing || !confirmationForm.code
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
                ]"
              >
                <svg v-if="confirmationForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ confirmationForm.processing ? 'A verificar...' : 'Verificar e Activar' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recovery Codes -->
      <div v-if="recoveryCodes.length > 0 && ! confirming" class="mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-5">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-gray-900">
              Códigos de Recuperação
            </h4>
            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
              {{ recoveryCodes.length }} códigos
            </span>
          </div>
          
          <div class="text-sm text-gray-600 mb-4">
            <p>
              Guarde estes códigos de recuperação num local seguro. Eles podem ser usados para recuperar o acesso à sua conta se perder o seu dispositivo de autenticação.
            </p>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div 
              v-for="(code, index) in recoveryCodes" 
              :key="code"
              class="bg-gray-50 rounded-lg p-3 border border-gray-200 text-center"
            >
              <span class="font-mono text-sm text-gray-900">{{ code }}</span>
              <div class="mt-1 text-xs text-gray-500">
                Código {{ index + 1 }}
              </div>
            </div>
          </div>
          
          <div class="mt-4 flex items-center justify-between">
            <button
              @click="downloadRecoveryCodes"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Descarregar
            </button>
            
            <button
              @click="regenerateRecoveryCodes"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Gerar Novos
            </button>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-3">
        <div v-if="! twoFactorEnabled">
          <ConfirmsPassword @confirmed="enableTwoFactorAuthentication">
            <button
              :disabled="enabling"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                enabling
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              <svg v-if="enabling" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              {{ enabling ? 'A activar...' : 'Activar 2FA' }}
            </button>
          </ConfirmsPassword>
        </div>

        <div v-else class="flex flex-wrap gap-3">
          <ConfirmsPassword v-if="confirming" @confirmed="confirmTwoFactorAuthentication">
            <button
              :disabled="enabling"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                enabling
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
              ]"
            >
              Concluir Activação
            </button>
          </ConfirmsPassword>

          <ConfirmsPassword v-if="recoveryCodes.length > 0 && ! confirming" @confirmed="regenerateRecoveryCodes">
            <button
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Regenerar Códigos
            </button>
          </ConfirmsPassword>

          <ConfirmsPassword v-if="! confirming" @confirmed="disableTwoFactorAuthentication">
            <button
              :disabled="disabling"
              :class="[
                'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2',
                disabling
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-red-600 to-red-500 text-white hover:from-red-500 hover:to-red-400'
              ]"
            >
              <svg v-if="disabling" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              {{ disabling ? 'A desactivar...' : 'Desactivar 2FA' }}
            </button>
          </ConfirmsPassword>

          <ConfirmsPassword v-if="confirming" @confirmed="disableTwoFactorAuthentication">
            <button
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              Cancelar
            </button>
          </ConfirmsPassword>
        </div>
      </div>
    </template>
  </ActionSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import ActionSection from "../../../Components/action-section.vue";
import ConfirmsPassword from "../../../Components/confirms-password.vue";
import InputError from "../../../Components/input-error.vue";
import TextInput from "../../../Components/text-input.vue";

const props = defineProps({
    requiresConfirmation: Boolean,
});

const enabling = ref(false);
const confirming = ref(false);
const disabling = ref(false);
const qrCode = ref(null);
const setupKey = ref(null);
const recoveryCodes = ref([]);

const confirmationForm = useForm({
    code: '',
});

const twoFactorEnabled = computed(
    () => ! enabling.value && usePage().props?.auth?.user?.two_factor_secret, 
);

watch(twoFactorEnabled, () => {
    if (! twoFactorEnabled.value) {
        confirmationForm.reset();
        confirmationForm.clearErrors();
    }
});

const enableTwoFactorAuthentication = () => {
    enabling.value = true;
    router.post('/user/two-factor-authentication', {}, {
        preserveScroll: true,
        onSuccess: () => Promise.all([
            showQrCode(),
            showSetupKey(),
            showRecoveryCodes(),
        ]),
        onFinish: () => {
            enabling.value = false;
            confirming.value = props.requiresConfirmation;
        },
    });
};

const showQrCode = () => {
    return axios.get('/user/two-factor-qr-code').then(response => {
        qrCode.value = response.data.svg;
    });
};

const showSetupKey = () => {
    return axios.get('/user/two-factor-secret-key').then(response => {
        setupKey.value = response.data.secretKey;
    });
};

const showRecoveryCodes = () => {
    return axios.get('/user/two-factor-recovery-codes').then(response => {
        recoveryCodes.value = response.data;
    });
};

const confirmTwoFactorAuthentication = () => {
    confirmationForm.post('/user/confirmed-two-factor-authentication', {
        errorBag: "confirmTwoFactorAuthentication",
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            confirming.value = false;
            qrCode.value = null;
            setupKey.value = null;
        },
    });
};

const regenerateRecoveryCodes = () => {
    axios
        .post('/user/two-factor-recovery-codes')
        .then(() => showRecoveryCodes());
};

const disableTwoFactorAuthentication = () => {
    disabling.value = true;
    router.delete('/user/two-factor-authentication', {
        preserveScroll: true,
        onSuccess: () => {
            disabling.value = false;
            confirming.value = false;
        },
    });
};

const copySetupKey = () => {
    navigator.clipboard.writeText(setupKey.value).then(() => {
        // You could add a toast notification here
        console.log('Setup key copied to clipboard');
    });
};

const downloadRecoveryCodes = () => {
    const element = document.createElement('a');
    const file = new Blob([recoveryCodes.value.join('\n')], {type: 'text/plain'});
    element.href = URL.createObjectURL(file);
    element.download = 'recovery-codes.txt';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
};
</script>

<style scoped>
/* Smooth transitions */
button {
  transition: all 0.2s ease-in-out;
}

/* QR Code styling */
:deep(svg) {
  max-width: 100%;
  height: auto;
}
</style>
