<script setup>
import DialogModal from '@/Components/dialog-modal.vue'
import axios from 'axios'
import { computed, reactive, ref, watch } from 'vue'

/**
 * Issues a free-form document from its template: its number, revision and
 * date, then one input for each field the template declares.
 */
const props = defineProps({
  show: { type: Boolean, default: false },
  template: { type: Object, default: null },
})

const emit = defineEmits(['close'])

const fields = computed(() => (props.template?.layout_schema?.custom_fields || []).filter((field) => field?.key))
const form = reactive({ document_code: '', document_revision: '0', issue_date: '', fields: {} })
const errors = ref({})
const failure = ref('')
const busy = ref(false)

const today = () => {
  const now = new Date()

  return [now.getFullYear(), String(now.getMonth() + 1).padStart(2, '0'), String(now.getDate()).padStart(2, '0')].join('-')
}

watch(() => [props.show, props.template?.id], () => {
  if (!props.show) {
    return
  }

  form.document_code = ''
  form.document_revision = '0'
  form.issue_date = today()
  form.fields = Object.fromEntries(fields.value.map((field) => [field.key, '']))
  errors.value = {}
  failure.value = ''
}, { immediate: true })

const errorFor = (key) => errors.value[key]?.[0] || ''

async function readFailure(error) {
  const data = error?.response?.data
  const body = data instanceof Blob ? JSON.parse(await data.text().catch(() => '{}') || '{}') : (data || {})

  errors.value = body.errors || {}

  return Object.keys(errors.value).length ? '' : (body.message || 'Não foi possível emitir o documento.')
}

async function issue() {
  if (busy.value || !props.template?.issue_path) {
    return
  }

  busy.value = true
  errors.value = {}
  failure.value = ''

  try {
    const response = await axios.post(props.template.issue_path, form, { responseType: 'blob' })
    const filename = /filename="?([^";]+)"?/.exec(response.headers?.['content-disposition'] || '')?.[1] || 'documento.pdf'
    const url = URL.createObjectURL(response.data)
    const link = document.createElement('a')

    link.href = url
    link.download = filename
    link.click()
    URL.revokeObjectURL(url)
    emit('close')
  } catch (error) {
    failure.value = await readFailure(error)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <DialogModal :show="show" max-width="2xl" @close="emit('close')">
    <template #title>Emitir documento</template>

    <template #content>
      <p class="text-sm text-[var(--ds-text-muted)]">{{ template?.name }} · os valores são impressos como texto, tal como escritos.</p>

      <form id="issue-document-form" class="mt-5 grid gap-4 sm:grid-cols-3" @submit.prevent="issue">
        <div class="ds-field-group">
          <label for="issue-document-code" class="ds-field-label">Número do documento</label>
          <BaseInput id="issue-document-code" v-model="form.document_code" type="text" class="ds-field" required maxlength="80" placeholder="DOC-2026-001" />
          <p v-if="errorFor('document_code')" class="ds-field-error">{{ errorFor('document_code') }}</p>
        </div>
        <div class="ds-field-group">
          <label for="issue-document-revision" class="ds-field-label">Revisão</label>
          <BaseInput id="issue-document-revision" v-model="form.document_revision" type="text" class="ds-field" maxlength="20" />
          <p v-if="errorFor('document_revision')" class="ds-field-error">{{ errorFor('document_revision') }}</p>
        </div>
        <div class="ds-field-group">
          <label for="issue-document-date" class="ds-field-label">Data de emissão</label>
          <BaseInput id="issue-document-date" v-model="form.issue_date" type="date" class="ds-field" />
          <p v-if="errorFor('issue_date')" class="ds-field-error">{{ errorFor('issue_date') }}</p>
        </div>

        <div v-for="field in fields" :key="field.key" class="ds-field-group" :class="field.type === 'long_text' || field.type === 'text' ? 'sm:col-span-3' : ''">
          <label :for="`issue-field-${field.key}`" class="ds-field-label">{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></label>
          <textarea
            v-if="field.type === 'long_text'"
            :id="`issue-field-${field.key}`"
            v-model="form.fields[field.key]"
            rows="6"
            class="ds-field"
            :required="Boolean(field.required)"
            :placeholder="field.sample"
          />
          <BaseInput
            v-else
            :id="`issue-field-${field.key}`"
            v-model="form.fields[field.key]"
            :type="field.type === 'date' ? 'date' : field.type === 'number' ? 'number' : 'text'"
            :step="field.type === 'number' ? 'any' : undefined"
            class="ds-field"
            :required="Boolean(field.required)"
            :placeholder="field.type === 'text' ? field.sample : undefined"
          />
          <p v-if="errorFor(`fields.${field.key}`)" class="ds-field-error">{{ errorFor(`fields.${field.key}`) }}</p>
        </div>

        <p v-if="!fields.length" class="text-sm text-[var(--ds-text-muted)] sm:col-span-3">Este modelo não declara campos: o documento é emitido com o texto do próprio modelo.</p>
      </form>

      <p v-if="failure" class="pl-banner pl-banner-bad mt-4 text-sm" role="alert">{{ failure }}</p>
    </template>

    <template #footer>
      <button type="button" class="ds-button ds-button-secondary" :disabled="busy" @click="emit('close')">Cancelar</button>
      <button type="submit" form="issue-document-form" class="ds-button ds-button-primary" :disabled="busy">{{ busy ? 'A emitir…' : 'Emitir PDF' }}</button>
    </template>
  </DialogModal>
</template>
