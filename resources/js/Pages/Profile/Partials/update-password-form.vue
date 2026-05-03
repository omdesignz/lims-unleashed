<template>
  <FormSection @submitted="updatePassword">
    <template #title>
      <div class="flex items-center gap-2">
        <svg class="h-5 w-5 text-blue-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
        </svg>
        Actualizar Palavra-passe
      </div>
    </template>

    <template #description>
      <div class="flex items-start gap-2">
        <svg class="h-5 w-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
          <p class="text-gray-600">
            Certifique-se de que sua conta está usando uma senha longa e aleatória para permanecer segura.
          </p>
          <p class="text-sm text-gray-500 mt-1">
            Use pelo menos 8 caracteres incluindo letras maiúsculas, minúsculas, números e símbolos.
          </p>
        </div>
      </div>
    </template>

    <template #form>
      <div class="space-y-6">
        <!-- Current Password -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <InputLabel for="current_password" value="Palavra-passe Actual" class="text-sm font-medium text-gray-700" />
            <span class="text-xs text-gray-500">Requerida para confirmar</span>
          </div>
          <div class="relative">
            <TextInput
              id="current_password"
              ref="currentPasswordInput"
              v-model="form.current_password"
              type="password"
              class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
              :class="form.errors.current_password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
              autocomplete="current-password"
              placeholder="Introduza a sua palavra-passe actual"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <button 
                @click="togglePasswordVisibility('current_password')"
                type="button"
                class="text-gray-400 hover:text-blue-900"
              >
                <svg v-if="showCurrentPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>
          <InputError :message="form.errors.current_password" class="text-xs" />
        </div>

        <!-- New Password -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <InputLabel for="password" value="Nova Palavra-passe" class="text-sm font-medium text-gray-700" />
            <div class="flex items-center gap-2">
              <div v-if="passwordStrength > 0" class="flex items-center gap-1">
                <div class="text-xs font-medium" :class="passwordStrengthClass">
                  {{ passwordStrengthText }}
                </div>
                <div class="h-2 w-12 rounded-full bg-gray-200">
                  <div 
                    class="h-full rounded-full transition-all duration-300"
                    :class="passwordStrengthClass"
                    :style="{ width: `${passwordStrength * 25}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
          <div class="relative">
            <TextInput
              id="password"
              ref="passwordInput"
              v-model="form.password"
              :type="showNewPassword ? 'text' : 'password'"
              class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
              :class="form.errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : ''"
              autocomplete="new-password"
              placeholder="Introduza a nova palavra-passe"
              @input="checkPasswordStrength"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <button 
                @click="togglePasswordVisibility('new')"
                type="button"
                class="text-gray-400 hover:text-blue-900"
              >
                <svg v-if="showNewPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Password Requirements -->
          <div class="grid grid-cols-1 md:grid-cols-1 gap-2 mt-3">
            <div class="flex items-center gap-2">
              <div class="h-2 w-2 rounded-full" :class="hasLength ? 'bg-green-500' : 'bg-gray-300'"></div>
              <span class="text-xs" :class="hasLength ? 'text-green-700' : 'text-gray-500'">
                Pelo menos 8 caracteres
              </span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-2 w-2 rounded-full" :class="hasUppercase ? 'bg-green-500' : 'bg-gray-300'"></div>
              <span class="text-xs" :class="hasUppercase ? 'text-green-700' : 'text-gray-500'">
                Letra maiúscula
              </span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-2 w-2 rounded-full" :class="hasLowercase ? 'bg-green-500' : 'bg-gray-300'"></div>
              <span class="text-xs" :class="hasLowercase ? 'text-green-700' : 'text-gray-500'">
                Letra minúscula
              </span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-2 w-2 rounded-full" :class="hasNumber ? 'bg-green-500' : 'bg-gray-300'"></div>
              <span class="text-xs" :class="hasNumber ? 'text-green-700' : 'text-gray-500'">
                Pelo menos um número
              </span>
            </div>
            <div class="flex items-center gap-2">
              <div class="h-2 w-2 rounded-full" :class="hasSpecial ? 'bg-green-500' : 'bg-gray-300'"></div>
              <span class="text-xs" :class="hasSpecial ? 'text-green-700' : 'text-gray-500'">
                Carácter especial
              </span>
            </div>
          </div>
          
          <InputError :message="form.errors.password" class="text-xs" />
        </div>

        <!-- Confirm Password -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <InputLabel for="password_confirmation" value="Confirmar Palavra-passe" class="text-sm font-medium text-gray-700" />
            <span v-if="form.password && form.password_confirmation" class="text-xs" :class="passwordsMatch ? 'text-green-600' : 'text-red-600'">
              {{ passwordsMatch ? '✓ Palavras-passe coincidem' : '✗ Palavras-passe não coincidem' }}
            </span>
          </div>
          <div class="relative">
            <TextInput
              id="password_confirmation"
              v-model="form.password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              class="block w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 transition-colors duration-200"
              :class="[
                form.errors.password_confirmation ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : '',
                form.password_confirmation && !passwordsMatch ? 'border-red-300' : ''
              ]"
              autocomplete="new-password"
              placeholder="Confirme a nova palavra-passe"
            />
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
              <button 
                @click="togglePasswordVisibility('confirm')"
                type="button"
                class="text-gray-400 hover:text-blue-900"
              >
                <svg v-if="showConfirmPassword" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.59 6.59m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
          </div>
          <InputError :message="form.errors.password_confirmation" class="text-xs" />
        </div>
      </div>
    </template>

    <template #actions>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-3">
          <ActionMessage :on="form.recentlySuccessful" class="flex items-center gap-2 text-sm font-medium text-green-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Palavra-passe actualizada com sucesso
          </ActionMessage>
          
          <div v-if="form.hasErrors" class="flex items-center gap-2 text-sm font-medium text-red-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.998-.833-2.732 0L4.346 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            Por favor, corrija os erros
          </div>
        </div>
        
        <PrimaryButton 
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
            form.processing || !isFormValid 
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700'
          ]" 
          :disabled="form.processing || !isFormValid"
        >
          <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          {{ form.processing ? 'A actualizar...' : 'Actualizar Palavra-passe' }}
        </PrimaryButton>
      </div>
    </template>
  </FormSection>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '../../../Components/action-message.vue'
import InputError from '../../../Components/input-error.vue'
import InputLabel from '../../../Components/input-label.vue'
import TextInput from '../../../Components/text-input.vue'
import PrimaryButton from '../../../Components/primary-button.vue'
import FormSection from '../../../Components/form-section.vue'

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Password strength checker
const passwordStrength = ref(0);
const hasLength = computed(() => form.password.length >= 8);
const hasUppercase = computed(() => /[A-Z]/.test(form.password));
const hasLowercase = computed(() => /[a-z]/.test(form.password));
const hasNumber = computed(() => /[0-9]/.test(form.password));
const hasSpecial = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.password));

const passwordsMatch = computed(() => form.password === form.password_confirmation && form.password !== '');

const checkPasswordStrength = () => {
  let strength = 0;
  if (hasLength.value) strength++;
  if (hasUppercase.value) strength++;
  if (hasLowercase.value) strength++;
  if (hasNumber.value) strength++;
  if (hasSpecial.value) strength++;
  passwordStrength.value = strength;
};

const passwordStrengthClass = computed(() => {
  switch (passwordStrength.value) {
    case 1: return 'bg-red-500 text-red-700';
    case 2: return 'bg-yellow-500 text-yellow-700';
    case 3: return 'bg-yellow-500 text-yellow-700';
    case 4: return 'bg-green-500 text-green-700';
    case 5: return 'bg-green-500 text-green-700';
    default: return 'bg-gray-500 text-gray-700';
  }
});

const passwordStrengthText = computed(() => {
  switch (passwordStrength.value) {
    case 1: return 'Fraca';
    case 2: return 'Razoável';
    case 3: return 'Boa';
    case 4: return 'Forte';
    case 5: return 'Muito Forte';
    default: return 'Insira uma palavra-passe';
  }
});

const isFormValid = computed(() => {
  return form.current_password && 
         form.password && 
         form.password_confirmation && 
         passwordsMatch.value &&
         passwordStrength.value >= 3; // At least "Good" strength
});

const togglePasswordVisibility = (type) => {
  switch (type) {
    case 'current':
      showCurrentPassword.value = !showCurrentPassword.value;
      break;
    case 'new':
      showNewPassword.value = !showNewPassword.value;
      break;
    case 'confirm':
      showConfirmPassword.value = !showConfirmPassword.value;
      break;
  }
};

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => {
          form.reset();
          showCurrentPassword.value = false;
          showNewPassword.value = false;
          showConfirmPassword.value = false;
          passwordStrength.value = 0;
        },
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<style scoped>
/* Smooth transitions */
input, button {
  transition: all 0.2s ease-in-out;
}

/* Password strength bar animation */
@keyframes fillBar {
  from {
    width: 0%;
  }
  to {
    width: var(--strength-width);
  }
}

div[style*="width"] {
  animation: fillBar 0.5s ease-out;
}
</style>