<template>
  <div class="grid gap-4 sm:grid-cols-[12rem_minmax(0,1fr)] sm:items-start">
    <div class="flex h-24 items-center justify-center border border-[color:var(--ds-border)] bg-white p-3">
      <img v-if="previewUrl" :src="previewUrl" alt="Logótipo dos documentos" class="max-h-full max-w-full object-contain" />
      <PhotoIcon v-else class="h-6 w-6 text-gray-400" aria-hidden="true" />
    </div>

    <div class="grid content-start gap-2">
      <p class="text-sm font-semibold text-[color:var(--ds-text)]">
        {{ logoUrl ? 'Impresso no cabeçalho de todos os documentos PDF.' : 'Sem logótipo: os documentos mostram apenas o nome do laboratório.' }}
      </p>
      <p class="ds-field-hint">PNG ou JPEG, até 1 MB. Ajustado a 38 × 18 mm; um fundo transparente ou branco imprime melhor.</p>

      <div v-if="canEdit" class="mt-1 flex flex-wrap gap-2">
        <label class="ds-button ds-button-secondary cursor-pointer" :class="{ 'pointer-events-none opacity-60': form.processing }">
          <ArrowUpTrayIcon class="h-4 w-4" aria-hidden="true" />
          {{ logoUrl ? 'Substituir logótipo' : 'Carregar logótipo' }}
          <FileInput accept="image/png,image/jpeg" class="sr-only" :disabled="form.processing" @change="upload" />
        </label>
        <button
          v-if="logoUrl"
          type="button"
          class="ds-button ds-button-secondary"
          :disabled="form.processing"
          @click="remove"
        >
          <TrashIcon class="h-4 w-4" aria-hidden="true" />
          Remover
        </button>
      </div>

      <p v-if="form.errors.logo" class="ds-field-error" role="alert">{{ form.errors.logo }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Upload as ArrowUpTrayIcon, Image as PhotoIcon, Trash2 as TrashIcon } from '@lucide/vue'

const props = defineProps({
  logoUrl: { type: String, default: null },
  canEdit: { type: Boolean, default: false },
})

const emit = defineEmits(['saved'])

const form = useForm({ logo: null })
const pendingUrl = ref(null)
const previewUrl = computed(() => pendingUrl.value || props.logoUrl)

function upload(event) {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  form.clearErrors()
  form.logo = file
  pendingUrl.value = URL.createObjectURL(file)
  form.post(route('generalsettings.document-logo.update'), {
    preserveScroll: true,
    onSuccess: (response) => emit('saved', response.props.settingsRevision),
    onFinish: () => {
      URL.revokeObjectURL(pendingUrl.value)
      pendingUrl.value = null
      form.logo = null
    },
  })
}

function remove() {
  form.clearErrors()
  form.delete(route('generalsettings.document-logo.destroy'), {
    preserveScroll: true,
    onSuccess: (response) => emit('saved', response.props.settingsRevision),
  })
}
</script>
