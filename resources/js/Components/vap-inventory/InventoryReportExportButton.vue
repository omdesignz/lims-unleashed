<template>
  <div class="min-w-0">
    <button type="button" class="ds-button ds-button-secondary" :disabled="processing || !csrfToken" :aria-busy="processing" @click="exportReport">
      <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
      {{ processing ? 'A preparar…' : 'Exportar PDF' }}
    </button>
    <p v-if="error" class="ds-field-error mt-2 max-w-sm" role="alert">{{ error }}</p>
    <p v-if="processing" class="ds-copy mt-2 text-sm" role="status">A preparar o ficheiro. Os filtros serão mantidos.</p>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { Download as ArrowDownTrayIcon } from '@lucide/vue'
import { useFileDownload } from '@/Composables/useFileDownload'

const props = defineProps({
  reportType: { type: String, required: true },
  filters: { type: Object, default: () => ({}) },
})

const csrfToken = ref('')
const { download, processing, error } = useFileDownload()

function exportReport() {
  if (processing.value || !csrfToken.value) return
  const filters = Object.fromEntries(Object.entries(props.filters)
    .filter(([, value]) => value !== null && value !== undefined && value !== ''))

  return download(route('vap-inventory.reports.export'), {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken.value },
    body: JSON.stringify({ report_type: props.reportType, format: 'pdf', filters }),
  })
}

onMounted(() => {
  csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
})
</script>
