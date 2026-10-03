<script setup>
import { nextTick, reactive, ref } from 'vue'
import { RefreshCw as ArrowPathIcon, X as XMarkIcon } from '@lucide/vue'
import DialogModal from './dialog-modal.vue'

const emit = defineEmits(['confirmed'])

defineProps({
  title: { type: String, default: 'Confirmar palavra-passe' },
  content: { type: String, default: 'Para sua segurança, confirme a palavra-passe antes de continuar.' },
  button: { type: String, default: 'Confirmar' },
})

const confirmingPassword = ref(false)
const passwordInput = ref(null)
const form = reactive({
  password: '',
  error: '',
  processing: false,
})

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

async function startConfirmingPassword() {
  form.error = ''

  try {
    const response = await fetch(route('password.confirmation'), {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    })
    const payload = await response.json()

    if (payload.confirmed) {
      emit('confirmed')
      return
    }

    confirmingPassword.value = true
    await nextTick()
    passwordInput.value?.focus()
  } catch {
    form.error = 'Não foi possível verificar a confirmação da palavra-passe.'
    confirmingPassword.value = true
  }
}

async function confirmPassword() {
  form.processing = true
  form.error = ''

  try {
    const response = await fetch(route('password.confirm.store'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({ password: form.password }),
    })
    const payload = await response.json().catch(() => ({}))

    if (!response.ok) {
      throw new Error(payload.errors?.password?.[0] || payload.message || 'Palavra-passe inválida.')
    }

    closeModal()
    await nextTick()
    emit('confirmed')
  } catch (error) {
    form.error = error.message
    await nextTick()
    passwordInput.value?.focus()
  } finally {
    form.processing = false
  }
}

function closeModal() {
  confirmingPassword.value = false
  form.password = ''
  form.error = ''
}
</script>

<template>
  <span class="contents">
    <span class="contents" @click="startConfirmingPassword">
      <slot />
    </span>

    <DialogModal :show="confirmingPassword" max-width="lg" @close="closeModal">
    <template #title>
      <div class="flex items-center justify-between gap-4">
        <span>{{ title }}</span>
        <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
          <XMarkIcon class="h-5 w-5" />
          <span class="sr-only">Fechar</span>
        </button>
      </div>
    </template>

    <template #content>
      <p>{{ content }}</p>
      <label class="ds-field-group mt-5">
        <span class="ds-field-label">Palavra-passe</span>
        <BaseInput
          ref="passwordInput"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          class="ds-field"
          :aria-invalid="Boolean(form.error)"
          @keyup.enter="confirmPassword" />
        <span v-if="form.error" class="ds-field-error">{{ form.error }}</span>
      </label>
    </template>

    <template #footer>
      <button type="button" class="ds-button ds-button-secondary" @click="closeModal">Cancelar</button>
      <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.password" @click="confirmPassword">
        <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
        {{ form.processing ? 'A confirmar...' : button }}
      </button>
    </template>
    </DialogModal>
  </span>
</template>
