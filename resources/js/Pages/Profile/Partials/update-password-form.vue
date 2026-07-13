<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  ArrowPathIcon,
  CheckCircleIcon,
  EyeIcon,
  EyeSlashIcon,
} from '@heroicons/vue/24/outline'

const currentPasswordInput = ref(null)
const passwordInput = ref(null)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmation = ref(false)

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const requirements = computed(() => [
  { label: '8 ou mais caracteres', met: form.password.length >= 8 },
  { label: 'Letra maiúscula', met: /[A-Z]/.test(form.password) },
  { label: 'Letra minúscula', met: /[a-z]/.test(form.password) },
  { label: 'Número ou símbolo', met: /[0-9!@#$%^&*(),.?":{}|<>]/.test(form.password) },
])

const strength = computed(() => requirements.value.filter((item) => item.met).length)
const passwordsMatch = computed(() => Boolean(form.password) && form.password === form.password_confirmation)
const canSubmit = computed(() => Boolean(form.current_password) && form.password.length >= 8 && passwordsMatch.value)

function updatePassword() {
  form.put(route('user-password.update'), {
    errorBag: 'updatePassword',
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      showCurrentPassword.value = false
      showNewPassword.value = false
      showConfirmation.value = false
    },
    onError: () => {
      if (form.errors.password) {
        form.reset('password', 'password_confirmation')
        passwordInput.value?.focus()
      }

      if (form.errors.current_password) {
        form.reset('current_password')
        currentPasswordInput.value?.focus()
      }
    },
  })
}
</script>

<template>
  <form class="space-y-5" @submit.prevent="updatePassword">
    <label class="ds-field-group">
      <span class="ds-field-label">Palavra-passe actual <span class="ds-field-required">*</span></span>
      <span class="relative block">
        <input
          ref="currentPasswordInput"
          v-model="form.current_password"
          :type="showCurrentPassword ? 'text' : 'password'"
          autocomplete="current-password"
          class="ds-field pr-11"
          :aria-invalid="Boolean(form.errors.current_password)"
          required
        >
        <button
          type="button"
          class="ds-icon-button absolute right-1 top-1/2 -translate-y-1/2"
          :title="showCurrentPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'"
          @click="showCurrentPassword = !showCurrentPassword"
        >
          <EyeSlashIcon v-if="showCurrentPassword" class="h-4 w-4" />
          <EyeIcon v-else class="h-4 w-4" />
          <span class="sr-only">{{ showCurrentPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe' }}</span>
        </button>
      </span>
      <span v-if="form.errors.current_password" class="ds-field-error">{{ form.errors.current_password }}</span>
    </label>

    <div class="grid gap-5 sm:grid-cols-2">
      <label class="ds-field-group">
        <span class="ds-field-label">Nova palavra-passe <span class="ds-field-required">*</span></span>
        <span class="relative block">
          <input
            ref="passwordInput"
            v-model="form.password"
            :type="showNewPassword ? 'text' : 'password'"
            autocomplete="new-password"
            class="ds-field pr-11"
            :aria-invalid="Boolean(form.errors.password)"
            required
          >
          <button
            type="button"
            class="ds-icon-button absolute right-1 top-1/2 -translate-y-1/2"
            :title="showNewPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe'"
            @click="showNewPassword = !showNewPassword"
          >
            <EyeSlashIcon v-if="showNewPassword" class="h-4 w-4" />
            <EyeIcon v-else class="h-4 w-4" />
            <span class="sr-only">{{ showNewPassword ? 'Ocultar palavra-passe' : 'Mostrar palavra-passe' }}</span>
          </button>
        </span>
        <span v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</span>
      </label>

      <label class="ds-field-group">
        <span class="flex items-center justify-between gap-3">
          <span class="ds-field-label">Confirmar palavra-passe <span class="ds-field-required">*</span></span>
          <span v-if="form.password_confirmation" class="text-xs font-semibold" :class="passwordsMatch ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">
            {{ passwordsMatch ? 'Coincide' : 'Não coincide' }}
          </span>
        </span>
        <span class="relative block">
          <input
            v-model="form.password_confirmation"
            :type="showConfirmation ? 'text' : 'password'"
            autocomplete="new-password"
            class="ds-field pr-11"
            :aria-invalid="Boolean(form.errors.password_confirmation) || (Boolean(form.password_confirmation) && !passwordsMatch)"
            required
          >
          <button
            type="button"
            class="ds-icon-button absolute right-1 top-1/2 -translate-y-1/2"
            :title="showConfirmation ? 'Ocultar confirmação' : 'Mostrar confirmação'"
            @click="showConfirmation = !showConfirmation"
          >
            <EyeSlashIcon v-if="showConfirmation" class="h-4 w-4" />
            <EyeIcon v-else class="h-4 w-4" />
            <span class="sr-only">{{ showConfirmation ? 'Ocultar confirmação' : 'Mostrar confirmação' }}</span>
          </button>
        </span>
        <span v-if="form.errors.password_confirmation" class="ds-field-error">{{ form.errors.password_confirmation }}</span>
      </label>
    </div>

    <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
      <div class="flex items-center justify-between gap-3">
        <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Robustez</p>
        <div class="grid w-28 grid-cols-4 gap-1" aria-hidden="true">
          <span v-for="index in 4" :key="index" class="h-1.5 rounded-full" :class="index <= strength ? 'bg-emerald-500' : 'bg-[var(--ds-border)]'" />
        </div>
      </div>
      <ul class="mt-3 grid gap-2 sm:grid-cols-2">
        <li v-for="item in requirements" :key="item.label" class="flex items-center gap-2 text-xs font-semibold" :class="item.met ? 'text-emerald-700 dark:text-emerald-300' : 'text-[var(--ds-text-muted)]'">
          <CheckCircleIcon class="h-4 w-4 shrink-0" />
          {{ item.label }}
        </li>
      </ul>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
      <p v-if="form.recentlySuccessful" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
        <CheckCircleIcon class="h-4 w-4" />
        Palavra-passe actualizada.
      </p>
      <span v-else />
      <button type="submit" class="ds-button ds-button-primary sm:ml-auto" :disabled="form.processing || !canSubmit">
        <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
        {{ form.processing ? 'A actualizar...' : 'Actualizar palavra-passe' }}
      </button>
    </div>
  </form>
</template>
