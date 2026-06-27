<script setup>
import { computed, ref } from 'vue'
import { VPerfectSignature } from 'v-perfect-signature'
import {
  ArrowDownTrayIcon,
  CheckIcon,
  PencilSquareIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  currentSignature: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['save', 'delete'])

const signaturePad = ref()
const hasDraft = ref(false)
const errorMessage = ref('')

const strokeOptions = {
  size: 4,
  thinning: 0.72,
  smoothing: 0.55,
  streamline: 0.55,
  color: '#143d37',
}

const hasExistingSignature = computed(() => props.currentSignature.length > 0)

const markDraft = () => {
  hasDraft.value = true
  errorMessage.value = ''
}

const clear = () => {
  signaturePad.value?.clear()
  hasDraft.value = false
  errorMessage.value = ''
}

const getSignatureData = () => {
  if (!signaturePad.value || signaturePad.value.isEmpty()) {
    errorMessage.value = 'gestlab.general.labels.signature.empty_error'

    return null
  }

  errorMessage.value = ''

  return signaturePad.value.toDataURL()
}

const download = () => {
  const signatureData = getSignatureData()

  if (!signatureData) {
    return
  }

  const link = document.createElement('a')
  link.download = 'assinatura.png'
  link.href = signatureData
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

const save = () => {
  const signatureData = getSignatureData()

  if (signatureData) {
    emit('save', signatureData)
  }
}
</script>

<template>
  <section class="ds-panel overflow-hidden">
    <header class="flex flex-col gap-4 border-b border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[rgb(var(--primary-700-rgb)/0.1)] text-[rgb(var(--primary-800-rgb)/1)] dark:text-[rgb(var(--accent-100-rgb)/1)]">
          <PencilSquareIcon class="h-5 w-5" aria-hidden="true" />
        </span>
        <div class="min-w-0">
          <h3 class="text-base font-extrabold text-[color:var(--ds-text)]">
            {{ props.title || $t('gestlab.general.labels.signature.title') }}
          </h3>
          <p class="mt-1 text-sm text-[color:var(--ds-text-muted)]">
            {{ $t('gestlab.general.labels.signature.description') }}
          </p>
        </div>
      </div>

      <span
        v-if="hasExistingSignature"
        class="inline-flex w-fit items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-extrabold text-emerald-800 dark:bg-emerald-500/12 dark:text-emerald-200"
      >
        <CheckIcon class="h-4 w-4" aria-hidden="true" />
        {{ $t('gestlab.general.labels.signature.signature_verified') }}
      </span>
    </header>

    <div class="space-y-4 p-5 sm:p-6">
      <div
        v-if="hasExistingSignature"
        class="ds-empty-state flex min-h-44 items-center justify-center p-5"
      >
        <img
          :src="props.currentSignature"
          :alt="$t('gestlab.general.labels.signature.current_signature')"
          class="max-h-36 max-w-full object-contain"
        />
      </div>

      <template v-else>
        <div
          class="relative overflow-hidden rounded-[1.4rem] border border-dashed border-[color:var(--ds-border-strong)] bg-[#fffdf7] shadow-inner"
          @pointerdown="markDraft"
        >
          <VPerfectSignature
            ref="signaturePad"
            :stroke-options="strokeOptions"
            class="h-44 w-full cursor-crosshair sm:h-52"
          />

          <div
            v-if="!hasDraft"
            class="pointer-events-none absolute inset-0 flex items-center justify-center p-6"
          >
            <div class="text-center">
              <PencilSquareIcon class="mx-auto h-8 w-8 text-[rgb(var(--primary-700-rgb)/0.4)]" aria-hidden="true" />
              <p class="mt-2 text-sm font-bold text-[rgb(var(--primary-900-rgb)/0.64)]">
                {{ $t('gestlab.general.labels.signature.draw_signature_here') }}
              </p>
            </div>
          </div>
        </div>

        <p
          v-if="errorMessage"
          role="alert"
          class="rounded-2xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-200"
        >
          {{ $t(errorMessage) }}
        </p>

        <div class="flex flex-col-reverse gap-2 border-t border-[color:var(--ds-border)] pt-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="ds-button ds-button-secondary"
              :disabled="!hasDraft"
              @click="clear"
            >
              <XMarkIcon class="h-4 w-4" aria-hidden="true" />
              {{ $t('gestlab.general.buttons.clear') }}
            </button>
            <button
              type="button"
              class="ds-button ds-button-secondary"
              :disabled="!hasDraft"
              @click="download"
            >
              <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
              {{ $t('gestlab.general.buttons.download') }}
            </button>
          </div>

          <button
            type="button"
            class="ds-button ds-button-primary"
            :disabled="!hasDraft"
            @click="save"
          >
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ $t('gestlab.general.labels.signature.confirm') }}
          </button>
        </div>
      </template>
    </div>
  </section>
</template>
