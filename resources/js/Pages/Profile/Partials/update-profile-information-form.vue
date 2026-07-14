<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ArrowPathIcon,
  CheckCircleIcon,
  EnvelopeIcon,
  PhotoIcon,
} from '@heroicons/vue/24/outline'
import SignaturePad from '@/Components/signature-pad.vue'

const props = defineProps({
  user: { type: Object, required: true },
  mustVerifyEmail: { type: Boolean, default: false },
})

const form = useForm({
  _method: 'PUT',
  name: props.user.name ?? '',
  email: props.user.email ?? '',
  photo: null,
})

const signatureForm = useForm({ signature: null })
const photoInput = ref(null)
const photoPreview = ref(null)
const verificationLinkSent = ref(false)

function selectNewPhoto() {
  photoInput.value?.click()
}

function updatePhotoPreview() {
  const photo = photoInput.value?.files?.[0]

  if (!photo) {
    photoPreview.value = null
    form.photo = null
    return
  }

  form.photo = photo
  const reader = new FileReader()
  reader.onload = (event) => {
    photoPreview.value = event.target?.result ?? null
  }
  reader.readAsDataURL(photo)
}

function clearPhotoFileInput() {
  if (photoInput.value) {
    photoInput.value.value = ''
  }

  form.photo = null
  photoPreview.value = null
}

function updateProfileInformation() {
  form.post(route('user-profile-information.update'), {
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    forceFormData: true,
    onSuccess: clearPhotoFileInput,
  })
}

function sendEmailVerification() {
  router.post(route('verification.send'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      verificationLinkSent.value = true
    },
  })
}

function saveSignature(signature) {
  signatureForm.signature = signature
  signatureForm.post(route('users.setsignature'), {
    preserveScroll: true,
    onSuccess: () => signatureForm.reset(),
  })
}

function deleteSignature() {
  router.get(route('users.unsetsignature'), {}, { preserveScroll: true })
}
</script>

<template>
  <form class="space-y-7" @submit.prevent="updateProfileInformation">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
      <img
        v-if="!photoPreview && user.profile_photo_url"
        :src="user.profile_photo_url"
        :alt="user.name"
        class="h-20 w-20 shrink-0 rounded-lg object-cover ring-1 ring-[var(--ds-border)]"
      >
      <div
        v-else-if="photoPreview"
        class="h-20 w-20 shrink-0 rounded-lg bg-cover bg-center ring-1 ring-[var(--ds-border)]"
        :style="{ backgroundImage: `url('${photoPreview}')` }"
        role="img"
        aria-label="Pré-visualização da nova fotografia"
      />
      <span v-else class="grid h-20 w-20 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] ring-1 ring-[var(--ds-border)]">
        <PhotoIcon class="h-7 w-7 text-[var(--ds-text-soft)]" />
      </span>

      <div class="min-w-0 space-y-2">
        <FileInput ref="photoInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="updatePhotoPreview" />
        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="selectNewPhoto">
            <PhotoIcon class="h-4 w-4" />
            Alterar fotografia
          </button>
          <button v-if="photoPreview" type="button" class="ds-button ds-button-secondary" @click="clearPhotoFileInput">
            Cancelar selecção
          </button>
        </div>
        <p class="ds-field-hint">PNG, JPG ou WebP. Utilize uma imagem quadrada para melhor identificação nos registos.</p>
        <p v-if="form.errors.photo" class="ds-field-error">{{ form.errors.photo }}</p>
      </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
      <label class="ds-field-group">
        <span class="ds-field-label">Nome completo <span class="ds-field-required">*</span></span>
        <BaseInput v-model="form.name" type="text" autocomplete="name" class="ds-field" :aria-invalid="Boolean(form.errors.name)" required />
        <span v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</span>
      </label>

      <label class="ds-field-group">
        <span class="flex items-center justify-between gap-3">
          <span class="ds-field-label">Correio electrónico <span class="ds-field-required">*</span></span>
          <span class="inline-flex items-center gap-1 text-xs font-semibold" :class="user.email_verified_at ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">
            <CheckCircleIcon v-if="user.email_verified_at" class="h-3.5 w-3.5" />
            {{ user.email_verified_at ? 'Verificado' : 'Não verificado' }}
          </span>
        </span>
        <BaseInput v-model="form.email" type="email" autocomplete="email" class="ds-field" :aria-invalid="Boolean(form.errors.email)" required />
        <span v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</span>
      </label>
    </div>

    <div v-if="mustVerifyEmail" class="flex flex-col gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-100">
      <div class="flex items-start gap-2.5">
        <EnvelopeIcon class="mt-0.5 h-4 w-4 shrink-0" />
        <p>{{ verificationLinkSent ? 'Foi enviado um novo link de verificação.' : 'Confirme este endereço para manter notificações e recuperação de conta disponíveis.' }}</p>
      </div>
      <button type="button" class="shrink-0 font-bold underline underline-offset-4" @click="sendEmailVerification">Reenviar correio electrónico</button>
    </div>

    <div class="border-t border-[var(--ds-border)] pt-6">
      <div class="mb-4">
        <h3 class="text-sm font-bold text-[var(--ds-text)]">Assinatura digital</h3>
        <p class="ds-copy mt-1 text-sm">Aplicada em documentos e aprovações emitidos em seu nome.</p>
      </div>
      <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
        <SignaturePad :current-signature="user.signature_url" @save="saveSignature" @delete="deleteSignature" />
      </div>
      <p v-if="signatureForm.hasErrors" class="ds-field-error mt-2">Não foi possível actualizar a assinatura.</p>
    </div>

    <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-between">
      <p v-if="form.recentlySuccessful" class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
        <CheckCircleIcon class="h-4 w-4" />
        Perfil actualizado.
      </p>
      <span v-else />
      <button type="submit" class="ds-button ds-button-primary sm:ml-auto" :disabled="form.processing">
        <ArrowPathIcon v-if="form.processing" class="h-4 w-4 animate-spin" />
        {{ form.processing ? 'A guardar...' : 'Guardar perfil' }}
      </button>
    </div>
  </form>
</template>
