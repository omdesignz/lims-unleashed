<template>
  <section class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_34%),linear-gradient(135deg,_#f8fafc,_#eef6ff)] px-5 py-5 dark:border-slate-700 dark:bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_34%),linear-gradient(135deg,_#0f172a,_#111827)]">
      <div class="flex items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-sky-700 dark:text-sky-300">ISO 17025</p>
          <h2 class="mt-2 text-xl font-semibold text-slate-900 dark:text-slate-100">Controlo documental</h2>
          <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
            Estado controlado, revisão, retenção, confidencialidade e efetividade no mesmo painel operacional.
          </p>
        </div>
        <div
          v-if="selectedFile"
          class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-right shadow-sm dark:border-slate-600 dark:bg-slate-900/80"
        >
          <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Prontidão</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ readinessScore }}%</p>
        </div>
      </div>
    </div>

    <div v-if="selectedFile" class="space-y-5 p-5">
      <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <h3 class="truncate text-base font-semibold text-slate-900 dark:text-slate-100">{{ selectedFile.name }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              {{ selectedFile.document_number || "Sem número documental" }} •
              {{ selectedFile.revision_code || "Sem revisão" }}
            </p>
          </div>
          <span
            class="inline-flex shrink-0 items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em]"
            :class="statusClass"
          >
            {{ selectedFile.status || "draft" }}
          </span>
        </div>

        <dl class="mt-4 grid gap-3 sm:grid-cols-2">
          <div class="rounded-2xl border border-white bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Confidencialidade</dt>
            <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ selectedFile.confidentiality_level || "internal" }}</dd>
          </div>
          <div class="rounded-2xl border border-white bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Acesso actual</dt>
            <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ selectedFile.current_access_level || "read" }}</dd>
          </div>
          <div class="rounded-2xl border border-white bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Entrada em vigor</dt>
            <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(selectedFile.effective_at) }}</dd>
          </div>
          <div class="rounded-2xl border border-white bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
            <dt class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Próxima revisão</dt>
            <dd class="mt-2 text-sm font-medium text-slate-900 dark:text-slate-100">{{ formatDate(selectedFile.review_due_at) }}</dd>
          </div>
        </dl>
      </div>

      <div
        v-if="complianceAlerts.length"
        class="space-y-3"
      >
        <article
          v-for="alert in complianceAlerts"
          :key="alert.title"
          class="rounded-2xl border px-4 py-3"
          :class="alert.tone === 'warning' ? 'border-amber-200 bg-amber-50 text-amber-900' : 'border-rose-200 bg-rose-50 text-rose-900'"
        >
          <p class="text-sm font-semibold">{{ alert.title }}</p>
          <p class="mt-1 text-sm leading-6">{{ alert.description }}</p>
        </article>
      </div>

      <div class="rounded-[1.5rem] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-4 py-3 dark:border-slate-700">
          <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-700 dark:text-slate-200">Checklist de conformidade</h3>
        </div>
        <div class="space-y-3 p-4">
          <div
            v-for="item in checklist"
            :key="item.label"
            class="flex items-start gap-3 rounded-2xl border px-4 py-3"
            :class="item.ok ? 'border-emerald-200 bg-emerald-50/80' : 'border-slate-200 bg-slate-50'"
          >
            <div
              class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
              :class="item.ok ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
            >
              {{ item.ok ? "✓" : "!" }}
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ item.label }}</p>
              <p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ item.help }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Número do documento</span>
          <input v-model="form.document_number" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </label>

        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Tipo documental</span>
          <input v-model="form.document_type" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400" placeholder="SOP, IT, Procedimento..." />
        </label>

        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Categoria</span>
          <input v-model="form.category" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </label>

        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Confidencialidade</span>
          <select v-model="form.confidentiality_level" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
            <option value="public">Público</option>
            <option value="internal">Interno</option>
            <option value="confidential">Confidencial</option>
            <option value="restricted">Restrito</option>
          </select>
        </label>

        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Retenção (dias)</span>
          <input v-model="form.retention_period_days" type="number" min="1" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </label>

        <label class="block text-sm">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Próxima revisão</span>
          <input v-model="form.review_due_at" type="date" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </label>

        <label class="block text-sm md:col-span-2">
          <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Data de entrada em vigor</span>
          <input v-model="form.effective_at" type="date" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
        </label>
      </div>

      <div class="grid gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
        <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-200">
          <input v-model="form.is_controlled" type="checkbox" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900" />
          Documento controlado
        </label>

        <label class="flex items-center gap-3 text-sm text-slate-700 dark:text-slate-200">
          <input v-model="form.requires_periodic_review" type="checkbox" class="rounded border-slate-300 dark:border-slate-600 dark:bg-slate-900" />
          Exige revisão periódica
        </label>
      </div>

      <label class="block text-sm">
        <span class="mb-1.5 block font-medium text-slate-700 dark:text-slate-200">Motivo da alteração</span>
        <textarea v-model="form.change_reason" rows="3" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100" />
      </label>

      <div class="grid gap-3 sm:grid-cols-2">
        <button class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy || !canWrite" @click="saveMetadata">
          Guardar metadados
        </button>
        <button class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-800 transition hover:bg-sky-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy || !canWrite" @click="submitReview">
          Submeter para revisão
        </button>
        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy || !canApprove" @click="approveDocument">
          Aprovar e efetivar
        </button>
        <button class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50" :disabled="busy || !canApprove" @click="markObsolete">
          Marcar obsoleto
        </button>
      </div>
    </div>

    <div v-else class="p-5">
      <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm leading-6 text-slate-500 dark:border-slate-600 dark:bg-slate-800/70 dark:text-slate-300">
        Seleccione um ficheiro para gerir número documental, retenção, revisão periódica, aprovação e obsolescência.
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, watch, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useFileStore } from '@/Stores/fileStore'

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
      help: 'A efetividade deve estar clara antes de colocar o documento em uso.',
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
      title: 'Documento eficaz sem marcação controlada',
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

const statusClass = computed(() => {
  const status = selectedFile.value?.status || 'draft'

  if (status === 'effective') {
    return 'bg-emerald-100 text-emerald-700'
  }

  if (status === 'obsolete') {
    return 'bg-rose-100 text-rose-700'
  }

  if (status === 'in_review') {
    return 'bg-amber-100 text-amber-700'
  }

  if (status === 'approved') {
    return 'bg-sky-100 text-sky-700'
  }

  return 'bg-slate-100 text-slate-700'
})

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
    toast.success('Metadados documentais atualizados.')
  } catch (error) {
    console.error(error)
    toast.error('Não foi possível atualizar os metadados documentais.')
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
    toast.success('Documento submetido para revisão.')
  } catch (error) {
    console.error(error)
    toast.error('Não foi possível submeter o documento para revisão.')
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
    toast.success('Documento aprovado e efetivado.')
  } catch (error) {
    console.error(error)
    toast.error('Não foi possível aprovar o documento.')
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
    toast.success('Documento marcado como obsoleto.')
  } catch (error) {
    console.error(error)
    toast.error('Não foi possível marcar o documento como obsoleto.')
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
