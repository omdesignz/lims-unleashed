<script setup>
import { nextTick, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  ArrowPathIcon,
  ExclamationTriangleIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import DialogModal from '@/Components/dialog-modal.vue'

const confirmingUserDeletion = ref(false)
const passwordInput = ref(null)
const form = useForm({ password: '' })

async function confirmUserDeletion() {
  confirmingUserDeletion.value = true
  await nextTick()
  passwordInput.value?.focus()
}

function deleteUser() {
  form.delete(route('current-user.destroy'), {
    preserveScroll: true,
    onError: () => passwordInput.value?.focus(),
    onFinish: () => form.reset(),
  })
}

function closeModal() {
  confirmingUserDeletion.value = false
  form.reset()
  form.clearErrors()
}
</script>

<template>
  <div class="rounded-lg border border-rose-200 bg-rose-50/70 px-4 py-4 dark:border-rose-400/20 dark:bg-rose-500/10">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-rose-600 dark:text-rose-300" />
        <div>
          <p class="text-sm font-bold text-rose-900 dark:text-rose-100">Eliminação permanente</p>
          <p class="mt-1 text-sm leading-6 text-rose-800 dark:text-rose-200">
            Os dados associados à conta deixam de estar disponíveis e terá de ser criado um novo acesso para regressar ao LIMS.
          </p>
        </div>
      </div>
      <button type="button" class="ds-button ds-button-danger shrink-0" @click="confirmUserDeletion">
        <TrashIcon class="h-4 w-4" />
        Eliminar conta
      </button>
    </div>

    <DialogModal :show="confirmingUserDeletion" max-width="lg" @close="closeModal">
      <template #title>
        <div class="flex items-center justify-between gap-4">
          <span>Eliminar conta</span>
          <button type="button" class="ds-icon-button" title="Fechar" @click="closeModal">
            <XMarkIcon class="h-5 w-5" />
            <span class="sr-only">Fechar</span>
          </button>
        </div>
      </template>

      <template #content>
        <div class="flex items-start gap-3 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-100">
          <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0" />
          <p class="text-sm font-semibold">Esta operação é permanente. Confirme a sua palavra-passe para continuar.</p>
        </div>
        <label class="ds-field-group mt-5">
          <span class="ds-field-label">Palavra-passe</span>
          <BaseInput
            ref="passwordInput"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            class="ds-field"
            :aria-invalid="Boolean(form.errors.password)"
            @keyup.enter="deleteUser" />
          <span v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</span>
        </label>
      </template>

      <template #footer>
        <button type="button" class="ds-button ds-button-secondary" @click="closeModal">Cancelar</button>
        <button type="button" class="ds-button ds-button-danger" :disabled="form.processing || !form.password" @click="deleteUser">
          <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
          <TrashIcon v-else class="h-4 w-4" />
          {{ form.processing ? 'A eliminar...' : 'Eliminar permanentemente' }}
        </button>
      </template>
    </DialogModal>
  </div>
</template>
