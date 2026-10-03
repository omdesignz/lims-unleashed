<script setup>
import { computed, ref, watch } from 'vue'
import { VPerfectSignature } from 'v-perfect-signature'
import {
  Download as ArrowDownTrayIcon,
  Check as CheckIcon,
  SquarePen as PencilSquareIcon,
  Trash2 as TrashIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'

const props = defineProps({
  currentSignature: { type: String, default: '' },
})

const emit = defineEmits(['save', 'delete'])

const signaturePad = ref(null)
const hasDraft = ref(false)
const showPad = ref(!props.currentSignature)
const showDeleteConfirmation = ref(false)
const errorMessage = ref('')

const strokeOptions = {
  size: 4,
  thinning: 0.72,
  smoothing: 0.55,
  streamline: 0.55,
  color: '#143d37',
}

const hasExistingSignature = computed(() => Boolean(props.currentSignature))

watch(() => props.currentSignature, (signature) => {
  showPad.value = !signature
  hasDraft.value = false
  errorMessage.value = ''
})

function markDraft() {
  hasDraft.value = true
  errorMessage.value = ''
}

function clearSignature() {
  signaturePad.value?.clear()
  hasDraft.value = false
  errorMessage.value = ''
}

function cancelReplacement() {
  clearSignature()
  showPad.value = false
}

function signatureData() {
  if (!signaturePad.value || signaturePad.value.isEmpty()) {
    errorMessage.value = 'Desenhe a assinatura antes de continuar.'
    return null
  }

  errorMessage.value = ''
  return signaturePad.value.toDataURL()
}

function downloadSignature() {
  const data = signatureData()

  if (!data) return

  const link = document.createElement('a')
  link.download = 'assinatura.png'
  link.href = data
  link.click()
}

function saveSignature() {
  const data = signatureData()

  if (data) {
    emit('save', data)
  }
}

function deleteSignature() {
  showDeleteConfirmation.value = false
  emit('delete')
}
</script>

<template>
  <div class="space-y-4">
    <div v-if="hasExistingSignature && !showPad" class="space-y-4">
      <div class="flex min-h-40 items-center justify-center rounded-lg border border-[var(--ds-border)] bg-white p-5 dark:bg-white">
        <img :src="currentSignature" alt="Assinatura actual" class="max-h-32 max-w-full object-contain">
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
          <CheckIcon class="h-4 w-4" />
          Assinatura registada
        </p>
        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="showPad = true">
            <PencilSquareIcon class="h-4 w-4" />
            Substituir
          </button>
          <button type="button" class="ds-button ds-button-secondary text-rose-700 dark:text-rose-200" @click="showDeleteConfirmation = true">
            <TrashIcon class="h-4 w-4" />
            Remover
          </button>
        </div>
      </div>
    </div>

    <div v-else class="space-y-4">
      <div class="relative overflow-hidden rounded-lg border border-dashed border-[var(--ds-border-strong)] bg-white dark:bg-white" @pointerdown="markDraft">
        <VPerfectSignature ref="signaturePad" :stroke-options="strokeOptions" class="h-44 w-full cursor-crosshair sm:h-52" />
        <div v-if="!hasDraft" class="pointer-events-none absolute inset-0 grid place-items-center p-6">
          <div class="text-center">
            <PencilSquareIcon class="mx-auto h-7 w-7 text-slate-400" />
            <p class="mt-2 text-sm font-semibold text-slate-500">Desenhe a assinatura nesta área</p>
          </div>
        </div>
      </div>

      <p v-if="errorMessage" class="ds-field-error" role="alert">{{ errorMessage }}</p>

      <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="!hasDraft" @click="clearSignature">
            <XMarkIcon class="h-4 w-4" />
            Limpar
          </button>
          <button type="button" class="ds-button ds-button-secondary" :disabled="!hasDraft" @click="downloadSignature">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Descarregar
          </button>
          <button v-if="hasExistingSignature" type="button" class="ds-button ds-button-secondary" @click="cancelReplacement">Cancelar</button>
        </div>

        <button type="button" class="ds-button ds-button-primary" :disabled="!hasDraft" @click="saveSignature">
          <CheckIcon class="h-4 w-4" />
          Guardar assinatura
        </button>
      </div>
    </div>

    <p class="border-t border-[var(--ds-border)] pt-3 text-xs font-semibold leading-5 text-[var(--ds-text-soft)]">
      A assinatura fica associada à sua identidade e pode ser aplicada em documentos sujeitos a aprovação.
    </p>

    <ConfirmDialog
      v-if="showDeleteConfirmation"
      title="Remover assinatura"
      description="A assinatura deixará de estar disponível para novos documentos e aprovações."
      confirm="Remover"
      @confirmed="deleteSignature"
      @canceled="showDeleteConfirmation = false"
    />
  </div>
</template>
