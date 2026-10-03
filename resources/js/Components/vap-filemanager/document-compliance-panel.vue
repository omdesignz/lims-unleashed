<template>
  <section class="grid gap-6" aria-label="Controlo documental">
    <template v-if="selectedFile">
      <section class="pl-panel" aria-labelledby="compliance-document">
        <div class="pl-panel-head">
          <h3 id="compliance-document" class="pl-k truncate">{{ selectedFile.name }}</h3>
          <StatusChip :tone="statusTone">{{ statusLabel }}</StatusChip>
        </div>
        <dl class="pl-facts pl-facts-2">
          <div class="pl-fact"><dt>Número</dt><dd class="pl-num">{{ selectedFile.document_number || $t('gestlab.general.labels.vap_filemanager.missing_document_number') }}</dd></div>
          <div class="pl-fact"><dt>Revisão</dt><dd class="pl-num">{{ selectedFile.revision_code || $t('gestlab.general.labels.vap_filemanager.missing_revision') }}</dd></div>
          <div class="pl-fact"><dt>Confidencialidade</dt><dd>{{ confidentialityLabels[selectedFile.confidentiality_level || 'internal'] }}</dd></div>
          <div class="pl-fact"><dt>Acesso actual</dt><dd>{{ accessLabels[selectedFile.current_access_level || 'read'] ?? selectedFile.current_access_level }}</dd></div>
          <div class="pl-fact"><dt>Entrada em vigor</dt><dd class="pl-num">{{ formatDate(selectedFile.effective_at) }}</dd></div>
          <div class="pl-fact"><dt>Próxima revisão</dt><dd class="pl-num">{{ formatDate(selectedFile.review_due_at) }}</dd></div>
        </dl>
      </section>

      <div v-if="complianceAlerts.length" class="grid gap-3">
        <div
          v-for="alert in complianceAlerts"
          :key="alert.title"
          class="pl-banner items-start text-sm"
          :class="alert.tone === 'warning' ? 'pl-banner-warn' : 'pl-banner-bad'"
          role="alert"
        >
          <span class="grid gap-1">
            <span class="pl-k">{{ alert.title }}</span>
            <span class="leading-6">{{ alert.description }}</span>
          </span>
        </div>
      </div>

      <section class="pl-panel" aria-labelledby="compliance-checklist">
        <div class="pl-panel-head">
          <h3 id="compliance-checklist" class="pl-k">Lista de conformidade</h3>
          <span class="pl-k pl-faint">{{ readinessScore }}% cumprido</span>
        </div>
        <ul>
          <li v-for="item in checklist" :key="item.label" class="pl-row items-start">
            <span class="min-w-0">
              <span class="block font-medium">{{ item.label }}</span>
              <span class="block text-[12.5px] leading-5 text-[var(--pl-muted)]">{{ item.help }}</span>
            </span>
            <StatusChip :tone="item.ok ? 'ok' : 'wait'">{{ item.ok ? 'Cumprido' : 'Em falta' }}</StatusChip>
          </li>
        </ul>
      </section>

      <section class="grid gap-4" aria-labelledby="compliance-metadata">
        <h3 id="compliance-metadata" class="pl-k">Metadados documentais</h3>
        <div class="grid gap-4 md:grid-cols-2">
          <BaseInput v-model="form.document_number" class="ds-field" label="Número do documento" />
          <BaseInput v-model="form.document_type" class="ds-field" label="Tipo documental" :placeholder="$t('gestlab.general.labels.vap_filemanager.document_type_placeholder')" />
          <BaseInput v-model="form.category" class="ds-field" label="Categoria" />
          <BaseSelect v-model="form.confidentiality_level" label="Confidencialidade">
            <option value="public">Público</option>
            <option value="internal">Interno</option>
            <option value="confidential">Confidencial</option>
            <option value="restricted">Restrito</option>
          </BaseSelect>
          <BaseInput v-model="form.retention_period_days" type="number" min="1" class="ds-field" label="Retenção (dias)" />
          <DateTimePicker v-model="form.review_due_at" type="date" label="Próxima revisão" />
          <div class="md:col-span-2">
            <DateTimePicker v-model="form.effective_at" type="date" label="Data de entrada em vigor" />
          </div>
          <label class="flex items-center gap-3 text-sm">
            <CheckboxInput v-model="form.is_controlled" type="checkbox" />
            Documento controlado
          </label>
          <label class="flex items-center gap-3 text-sm">
            <CheckboxInput v-model="form.requires_periodic_review" type="checkbox" />
            Exige revisão periódica
          </label>
          <div class="md:col-span-2">
            <BaseTextarea v-model="form.change_reason" :rows="3" class="ds-field" label="Motivo da alteração" />
          </div>
        </div>
      </section>

      <div class="flex flex-wrap gap-2 border-t border-[var(--pl-line)] pt-4">
        <button type="button" class="ds-button ds-button-secondary" :disabled="busy || !canWrite" @click="saveMetadata">Guardar metadados</button>
        <button type="button" class="ds-button ds-button-secondary" :disabled="busy || !canWrite" @click="submitReview">Submeter para revisão</button>
        <button type="button" class="ds-button ds-button-primary" :disabled="busy || !canApprove" @click="approveDocument">Aprovar e efectivar</button>
        <button type="button" class="ds-button ds-button-danger" :disabled="busy || !canApprove" @click="markObsolete">Marcar obsoleto</button>
      </div>
    </template>

    <div v-else class="ds-empty-state grid justify-items-start gap-2 p-6">
      <span class="pl-k">Nenhum documento seleccionado</span>
      <p class="text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_filemanager.select_single_document_hint') }}</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, watch, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useFileStore } from '@/Stores/fileStore'
import { trans } from 'laravel-vue-i18n'
import StatusChip from '@/Components/plano/StatusChip.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'

const fileStore = useFileStore()
const toast = useToast()
const busy = ref(false)

const selectedFile = computed(() => {
  const selectedIds = Array.from(fileStore.selectedItems)
  return selectedIds.length === 1 ? fileStore.files.find(file => file.id === selectedIds[0] && file.type === 'file') : null
})

const canWrite = computed(() => ['write', 'admin'].includes(selectedFile.value?.current_access_level || ''))
const canApprove = computed(() => (selectedFile.value?.current_access_level || '') === 'admin')
const isReviewOverdue = computed(() => {
  if (!selectedFile.value?.review_due_at) {
    return false
  }

  return new Date(selectedFile.value.review_due_at).getTime() < Date.now()
})

const checklist = computed(() => {
  if (!selectedFile.value) {
    return []
  }

  return [
    {
      label: 'Identificação documental definida',
      help: 'Número documental e tipo facilitam pesquisa, referência e circulação controlada.',
      ok: Boolean(form.document_number && form.document_type),
    },
    {
      label: 'Retenção configurada',
      help: 'O documento precisa de uma política de retenção coerente com o processo.',
      ok: Boolean(form.retention_period_days),
    },
    {
      label: 'Revisão periódica tratada',
      help: 'Defina uma data de revisão quando o documento exige monitorização periódica.',
      ok: !form.requires_periodic_review || Boolean(form.review_due_at),
    },
    {
      label: 'Entrada em vigor pronta',
      help: 'A efectividade deve estar clara antes de colocar o documento em uso.',
      ok: selectedFile.value.status !== 'effective' || Boolean(form.effective_at),
    },
  ]
})

const readinessScore = computed(() => {
  if (!checklist.value.length) {
    return 0
  }

  const completed = checklist.value.filter((item) => item.ok).length

  return Math.round((completed / checklist.value.length) * 100)
})

const complianceAlerts = computed(() => {
  if (!selectedFile.value) {
    return []
  }

  const alerts = []

  if (isReviewOverdue.value) {
    alerts.push({
      title: 'Revisão periódica em atraso',
      description: `A data de revisão expirou em ${formatDate(selectedFile.value.review_due_at)}. Revalide ou marque o documento como obsoleto.`,
      tone: 'warning',
    })
  }

  if (selectedFile.value.status === 'effective' && !selectedFile.value.is_controlled) {
    alerts.push({
      title: 'Documento efectivo sem marcação controlada',
      description: 'Se este ficheiro faz parte do sistema documental ISO, active o controlo para manter rastreabilidade formal.',
      tone: 'danger',
    })
  }

  if (selectedFile.value.confidentiality_level === 'restricted' && selectedFile.value.current_access_level !== 'admin') {
    alerts.push({
      title: 'Conteúdo restrito com sessão sem privilégio total',
      description: 'A revisão deste documento deve ser feita por um utilizador com controlo administrativo do ficheiro.',
      tone: 'warning',
    })
  }

  return alerts
})

const statusLabels: Record<string, string> = {
  draft: 'Rascunho',
  in_review: 'Em revisão',
  approved: 'Aprovado',
  effective: 'Efectivo',
  obsolete: 'Obsoleto',
  archived: 'Arquivado',
}

const confidentialityLabels: Record<string, string> = {
  public: 'Público',
  internal: 'Interno',
  confidential: 'Confidencial',
  restricted: 'Restrito',
}

const accessLabels: Record<string, string> = {
  read: 'Leitura',
  write: 'Escrita',
  admin: 'Administração',
}

const statusLabel = computed(() => {
  const status = selectedFile.value?.status || 'draft'

  return statusLabels[status] ?? status
})

/** Status chip tone: in review waits, approved is in progress, effective conforms, obsolete is closed. */
const statusTone = computed(() => ({
  in_review: 'wait',
  approved: 'run',
  effective: 'ok',
  obsolete: 'done',
  archived: 'done',
} as Record<string, string>)[selectedFile.value?.status || 'draft'] ?? 'neutral')

const form = reactive({
  document_number: '',
  document_type: '',
  category: '',
  confidentiality_level: 'internal',
  retention_period_days: '',
  review_due_at: '',
  effective_at: '',
  is_controlled: true,
  requires_periodic_review: true,
  change_reason: '',
})

watch(selectedFile, (file) => {
  form.document_number = file?.document_number || ''
  form.document_type = file?.document_type || ''
  form.category = file?.category || ''
  form.confidentiality_level = file?.confidentiality_level || 'internal'
  form.retention_period_days = file?.retention_period_days ? String(file.retention_period_days) : ''
  form.review_due_at = file?.review_due_at ? String(file.review_due_at).slice(0, 10) : ''
  form.effective_at = file?.effective_at ? String(file.effective_at).slice(0, 10) : ''
  form.is_controlled = file?.is_controlled ?? true
  form.requires_periodic_review = file?.requires_periodic_review ?? true
  form.change_reason = ''
}, { immediate: true })

async function refreshFiles() {
  await fileStore.fetchFiles()
  await fileStore.loadFiles()
}

async function saveMetadata() {
  if (!selectedFile.value) return

  busy.value = true

  try {
    await axios.put(`/api/files/${selectedFile.value.id}/metadata`, payload())
    await refreshFiles()
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.metadata_updated'))
  } catch {
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_updating_metadata'))
  } finally {
    busy.value = false
  }
}

async function submitReview() {
  if (!selectedFile.value) return

  busy.value = true

  try {
    await axios.post(`/api/files/${selectedFile.value.id}/submit-review`, {
      change_reason: form.change_reason || 'Submissão para revisão controlada',
    })
    await refreshFiles()
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.document_submitted_for_review'))
  } catch {
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_submitting_review'))
  } finally {
    busy.value = false
  }
}

async function approveDocument() {
  if (!selectedFile.value) return

  busy.value = true

  try {
    await axios.post(`/api/files/${selectedFile.value.id}/approve`, {
      change_reason: form.change_reason || 'Documento aprovado para uso controlado',
      effective_at: form.effective_at || null,
      review_due_at: form.review_due_at || null,
    })
    await refreshFiles()
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.document_approved'))
  } catch {
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_approving_document'))
  } finally {
    busy.value = false
  }
}

async function markObsolete() {
  if (!selectedFile.value) return

  busy.value = true

  try {
    await axios.post(`/api/files/${selectedFile.value.id}/obsolete`, {
      change_reason: form.change_reason || 'Documento substituído ou obsoleto',
    })
    await refreshFiles()
    toast.success(trans('gestlab.general.labels.vap_filemanager.notifications.document_obsolete'))
  } catch {
    toast.error(trans('gestlab.general.labels.vap_filemanager.notifications.error_marking_obsolete'))
  } finally {
    busy.value = false
  }
}

function payload() {
  return {
    document_number: form.document_number || null,
    document_type: form.document_type || null,
    category: form.category || null,
    confidentiality_level: form.confidentiality_level,
    retention_period_days: form.retention_period_days ? Number(form.retention_period_days) : null,
    review_due_at: form.review_due_at || null,
    effective_at: form.effective_at || null,
    is_controlled: form.is_controlled,
    requires_periodic_review: form.requires_periodic_review,
    change_reason: form.change_reason || null,
  }
}

function formatDate(value: string | null | undefined) {
  if (!value) {
    return 'Não definido'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    dateStyle: 'medium',
  }).format(new Date(value))
}
</script>
