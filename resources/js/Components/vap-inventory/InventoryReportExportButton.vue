<template>
  <form :action="route('vap-inventory.reports.export')" method="post">
    <input type="hidden" name="_token" :value="csrfToken" />
    <input type="hidden" name="report_type" :value="reportType" />
    <input type="hidden" name="format" value="pdf" />
    <input v-for="field in filterFields" :key="field.name" type="hidden" :name="field.name" :value="field.value" />
    <button type="submit" class="ds-button ds-button-secondary" :disabled="!csrfToken">
      <ArrowDownTrayIcon class="h-4 w-4" />
      Exportar PDF
    </button>
  </form>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { Download as ArrowDownTrayIcon } from '@lucide/vue'

const props = defineProps({
  reportType: { type: String, required: true },
  filters: { type: Object, default: () => ({}) },
})

const csrfToken = ref('')
const filterFields = computed(() => Object.entries(props.filters)
  .filter(([, value]) => value !== null && value !== undefined && value !== '')
  .map(([key, value]) => ({ name: `filters[${key}]`, value: String(value) })))

onMounted(() => {
  csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
})
</script>
