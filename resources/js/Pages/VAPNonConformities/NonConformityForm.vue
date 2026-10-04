<template>
  <form class="min-w-0 space-y-6 overflow-x-clip" :aria-busy="form.processing" @submit.prevent="submit">
    <p v-if="form.hasErrors" role="alert" class="ds-alert ds-alert-danger">{{ Object.values(form.errors).join(' ') }}</p>
    <nav class="ds-panel flex overflow-x-auto px-3 sm:px-5" aria-label="Etapas do dossier CAPA">
      <button
        v-for="section in workflowSections"
        :key="section.value"
        type="button"
        class="-mb-px min-h-12 shrink-0 border-b-2 px-4 text-sm font-bold transition"
        :class="activeWorkflowSection === section.value ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
        @click="activeWorkflowSection = section.value"
      >
        {{ section.label }}
      </button>
    </nav>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <div class="space-y-6">
        <article v-show="activeWorkflowSection === 'event'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.basic_info') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  Identifique o desvio, classifique o impacto e descreva a evidência observada.
                </p>
              </div>
            </div>
          </div>

          <div class="grid gap-4 p-5 md:grid-cols-2">
            <BaseInput
              v-model="form.nc_number"
              :label="$t('gestlab.general.labels.vap_non_conformities.nc_number')"
              :error="form.errors.nc_number"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.nc_number_placeholder')"
              required
            >
              <template #leading>
                <HashtagIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.title"
              :label="$t('gestlab.general.labels.vap_non_conformities.title')"
              :error="form.errors.title"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.title_placeholder')"
              required
            >
              <template #leading>
                <TagIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseSelect
              v-model="form.severity"
              :label="$t('gestlab.general.labels.vap_non_conformities.severity.title')"
              :error="form.errors.severity"
              required
            >
              <option v-for="option in severityOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </BaseSelect>

            <BaseSelect
              v-model="form.category"
              :label="$t('gestlab.general.labels.vap_non_conformities.category')"
              :error="form.errors.category"
              required
            >
              <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </BaseSelect>

            <div class="ds-field-group">
              <span class="ds-field-label">Laboratório responsável</span>
              <p class="ds-field flex min-h-11 items-center">{{ labs[0]?.name || 'Laboratório activo' }}</p>
            </div>

            <BaseSelect v-model="form.department_id" label="Departamento" :error="form.errors.department_id">
              <option value="">Sem departamento associado</option>
              <option v-for="department in departments" :key="department.id" :value="department.id">{{ department.name }}</option>
            </BaseSelect>

            <BaseTextarea
              v-model="form.description"
              class="md:col-span-2"
              :label="$t('gestlab.general.labels.vap_non_conformities.description')"
              :error="form.errors.description"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.description_placeholder')"
              rows="4"
              required
            />
          </div>
        </article>

        <article v-show="activeWorkflowSection === 'scope'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <LinkIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.related_entities') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  Ligue a ocorrência a amostras, métodos, equipamentos, lotes e área de ocorrência.
                </p>
              </div>
            </div>
          </div>

          <div class="grid gap-4 p-5 md:grid-cols-2">
            <BaseInput
              v-model="form.sample_id"
              :label="$t('gestlab.general.labels.vap_non_conformities.sample_id')"
              :error="form.errors.sample_id"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.sample_id_placeholder')"
            >
              <template #leading>
                <BeakerIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.test_method"
              :label="$t('gestlab.general.labels.vap_non_conformities.test_method')"
              :error="form.errors.test_method"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.test_method_placeholder')"
            >
              <template #leading>
                <ClipboardDocumentCheckIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.equipment_id"
              :label="$t('gestlab.general.labels.vap_non_conformities.equipment_id')"
              :error="form.errors.equipment_id"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.equipment_id_placeholder')"
            >
              <template #leading>
                <CpuChipIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.batch_number"
              :label="$t('gestlab.general.labels.vap_non_conformities.batch_number')"
              :error="form.errors.batch_number"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.batch_number_placeholder')"
            >
              <template #leading>
                <QueueListIcon class="h-4 w-4" />
              </template>
            </BaseInput>

            <BaseInput
              v-model="form.occurrence_area"
              class="md:col-span-2"
              :label="$t('gestlab.general.labels.vap_non_conformities.occurrence_area')"
              :error="form.errors.occurrence_area"
              placeholder="Área, bancada, etapa do método ou processo afectado"
            />
          </div>
        </article>

        <article v-show="activeWorkflowSection === 'capa'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <WrenchScrewdriverIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.corrective_actions') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                  Defina correcções imediatas, acções correctivas e prazos de conclusão.
                  Acções retiradas do plano ficam arquivadas no histórico após guardar.
                </p>
              </div>
            </div>

            <button type="button" class="ds-button ds-button-secondary" @click="addAction">
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.add_action') }}
            </button>
          </div>

          <div v-if="actions.length" class="grid gap-4 p-5">
            <article v-for="(action, index) in actions" :key="action.id || index" class="ds-card overflow-hidden">
              <div class="flex items-center justify-between gap-3 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
                <div class="flex min-w-0 items-center gap-3">
                  <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-sm font-black text-[var(--ds-text)]">
                    {{ index + 1 }}
                  </span>
                  <div class="min-w-0">
                    <h3 class="truncate text-sm font-black text-[var(--ds-text)]">
                      {{ $t('gestlab.general.labels.vap_non_conformities.action') }} #{{ index + 1 }}
                    </h3>
                    <p class="text-xs font-semibold text-[var(--ds-text-soft)]">
                      {{ action.due_at ? $t('gestlab.general.labels.vap_non_conformities.due') + ': ' + formatDate(action.due_at) : $t('gestlab.general.labels.vap_non_conformities.no_due_date') }}
                    </p>
                  </div>
                </div>

                <button type="button" class="ds-table-action ds-table-action-danger" :title="$t('gestlab.general.labels.vap_non_conformities.buttons.remove_action')" @click="removeAction(index)">
                  <TrashIcon class="h-4 w-4" />
                  <span class="sr-only">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.remove_action') }}</span>
                </button>
              </div>

              <div class="grid gap-4 p-4 md:grid-cols-2">
                <BaseTextarea
                  v-model="action.correction"
                  :label="$t('gestlab.general.labels.vap_non_conformities.correction')"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.correction_placeholder')"
                  rows="3"
                />

                <BaseTextarea
                  v-model="action.corrective_action"
                  :label="$t('gestlab.general.labels.vap_non_conformities.corrective_action')"
                  :placeholder="$t('gestlab.general.labels.vap_non_conformities.corrective_action_placeholder')"
                  rows="3"
                />

                <BaseInput
                  v-model="action.due_at"
                  type="datetime-local"
                  :label="$t('gestlab.general.labels.vap_non_conformities.due_date')"
                />
              </div>
            </article>
          </div>

          <div v-else class="ds-empty-state p-10 text-center">
            <WrenchScrewdriverIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
            <h3 class="mt-4 text-base font-black text-[var(--ds-text)]">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_title') }}
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm font-medium text-[var(--ds-text-muted)]">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_description') }}
            </p>
            <button type="button" class="ds-button ds-button-primary mt-5" @click="addAction">
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.add_first_action') }}
            </button>
          </div>
        </article>

        <article v-show="activeWorkflowSection === 'evidence'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <PaperClipIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Evidência, causa raiz, prevenção e conclusão da investigação.</p>
              </div>
            </div>
          </div>

          <div class="grid gap-5 p-5 lg:grid-cols-2">
            <div class="space-y-4">
              <label class="ds-field-group">
                <span class="ds-field-label">Anexar evidências</span>
                <FileInput type="file" multiple :disabled="form.processing" class="ds-field" @change="selectAttachmentFiles" />
                <span class="ds-field-hint">PDF, imagens ou documentos até 10MB.</span>
                <span v-if="form.errors.attachment_files" class="ds-field-error">{{ form.errors.attachment_files }}</span>
              </label>

              <div v-if="selectedAttachmentFiles.length" class="space-y-2">
                <div v-for="(file, index) in selectedAttachmentFiles" :key="`${file.name}-${file.size}-${index}`" class="flex items-center justify-between gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm">
                  <span class="truncate font-semibold text-[var(--ds-text)]">{{ file.name }}</span>
                  <button type="button" class="ds-table-action ds-table-action-danger" @click="removeAttachmentFile(index)"><TrashIcon class="h-4 w-4" /><span class="sr-only">Remover {{ file.name }}</span></button>
                </div>
              </div>

              <div v-if="existingAttachments.length" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                <p class="text-sm font-black text-[var(--ds-text)]">Anexos já registados</p>
                <div class="mt-3 space-y-2">
                  <a v-for="attachment in existingAttachments" :key="attachment.id" :href="attachment.url" target="_blank" rel="noreferrer" class="flex items-center justify-between gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2 text-sm transition hover:border-[rgb(var(--primary-300-rgb))]">
                    <span class="truncate font-semibold text-[var(--ds-text)]">{{ attachment.name || attachment.file_name }}</span>
                    <span class="shrink-0 text-xs font-bold text-[var(--ds-text-soft)]">{{ attachment.human_readable_size }}</span>
                  </a>
                </div>
              </div>

              <BaseTextarea v-model="form.root_cause" :label="$t('gestlab.general.labels.vap_non_conformities.root_cause')" :placeholder="$t('gestlab.general.labels.vap_non_conformities.root_cause_placeholder')" rows="5" />
            </div>

            <div class="space-y-4">
              <BaseTextarea v-model="form.corrective_actions" :label="$t('gestlab.general.labels.vap_non_conformities.corrective_actions')" placeholder="Plano correctivo global, quando não for dividido em acções individuais" rows="4" />
              <BaseTextarea v-model="form.preventive_actions" :label="$t('gestlab.general.labels.vap_non_conformities.preventive_actions')" :placeholder="$t('gestlab.general.labels.vap_non_conformities.preventive_actions_placeholder')" rows="4" />
              <BaseTextarea v-model="form.comments" :label="$t('gestlab.general.labels.vap_non_conformities.comments')" :placeholder="$t('gestlab.general.labels.vap_non_conformities.comments_placeholder')" rows="4" />
            </div>
          </div>
        </article>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
        <article class="ds-command-surface p-5">
          <div class="flex items-start gap-3">
            <ClockIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
            <div>
              <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.timeline_assignment') }}</h2>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                Responsáveis, prazos e estado actual do fluxo.
              </p>
            </div>
          </div>

          <div class="mt-5 space-y-4">
            <BaseInput
              v-model="form.reported_by"
              :label="$t('gestlab.general.labels.vap_non_conformities.reported_by')"
              :error="form.errors.reported_by"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.reported_by_placeholder')"
              required
            />

            <BaseInput
              v-model="form.assigned_to"
              :label="$t('gestlab.general.labels.vap_non_conformities.assigned_to')"
              :error="form.errors.assigned_to"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.assigned_to_placeholder')"
            />

            <BaseInput
              v-model="form.reported_at"
              type="datetime-local"
              :label="$t('gestlab.general.labels.vap_non_conformities.reported_at')"
              :error="form.errors.reported_at"
              required
            />

            <BaseInput
              v-model="form.due_date"
              type="datetime-local"
              :label="$t('gestlab.general.labels.vap_non_conformities.due_date')"
              :error="form.errors.due_date"
            />

            <BaseSelect
              v-model="form.status"
              :label="$t('gestlab.general.labels.vap_non_conformities.status.title')"
              :error="form.errors.status"
              required
            >
              <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </BaseSelect>
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.stats.title') }}</h2>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Resumo operacional antes da submissão.</p>
          </div>

          <div class="grid gap-3 p-5">
            <div v-for="metric in workflowMetrics" :key="metric.label" class="ds-card p-4">
              <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</p>
              <p class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</p>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.caption }}</p>
            </div>
          </div>
        </article>

      </aside>
    </section>

    <section class="ds-command-surface sticky bottom-2 z-20 p-2 shadow-lg shadow-black/5 sm:bottom-3 sm:p-4">
      <div class="flex items-center justify-end gap-3 sm:justify-between">
        <p class="hidden text-sm font-semibold text-[var(--ds-text-soft)] sm:block">
          {{ $t('gestlab.general.labels.vap_non_conformities.last_updated') }}:
          <span class="font-bold text-[var(--ds-text)]">
            {{ nonConformity?.updated_at ? formatDate(nonConformity.updated_at) : $t('gestlab.general.labels.vap_non_conformities.never') }}
          </span>
        </p>

        <div class="grid w-full grid-cols-[2.75rem_2.75rem_minmax(0,1fr)] gap-2 sm:flex sm:w-auto">
          <button
            type="button"
            class="ds-button ds-button-secondary px-0 sm:px-4"
            :aria-label="$t('gestlab.general.labels.vap_non_conformities.buttons.cancel')"
            :title="$t('gestlab.general.labels.vap_non_conformities.buttons.cancel')"
            @click="cancel"
          >
            <XMarkIcon class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.cancel') }}</span>
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary px-0 sm:px-4"
            :aria-label="$t('gestlab.general.labels.vap_non_conformities.buttons.reset')"
            :title="$t('gestlab.general.labels.vap_non_conformities.buttons.reset')"
            @click="reset"
          >
            <ArrowPathIcon class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.reset') }}</span>
          </button>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
            <CheckCircleIcon v-if="!form.processing" class="h-4 w-4" />
            <ArrowPathIcon v-else class="h-4 w-4 animate-spin" />
            {{ form.processing ? $t('gestlab.general.labels.vap_non_conformities.buttons.processing') : $t('gestlab.general.labels.vap_non_conformities.buttons.save_changes') }}
          </button>
        </div>
      </div>
    </section>
  </form>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  RefreshCw as ArrowPathIcon,
  FlaskConical as BeakerIcon,
  CircleCheck as CheckCircleIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Clock as ClockIcon,
  Cpu as CpuChipIcon,
  Hash as HashtagIcon,
  Info as InformationCircleIcon,
  Link as LinkIcon,
  Paperclip as PaperClipIcon,
  CirclePlus as PlusCircleIcon,
  Rows3 as QueueListIcon,
  Tag as TagIcon,
  Trash2 as TrashIcon,
  Wrench as WrenchScrewdriverIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import { computed, ref } from 'vue'

const props = defineProps({
  nonConformity: {
    type: Object,
    default: null,
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
})

const form = useForm({
  department_id: props.nonConformity?.department_id || '',
  nc_number: props.nonConformity?.nc_number || '',
  title: props.nonConformity?.title || '',
  description: props.nonConformity?.description || '',
  status: props.nonConformity?.status || 'opened',
  severity: props.nonConformity?.severity || 'medium',
  category: props.nonConformity?.category || 'quality',
  sample_id: props.nonConformity?.sample_id || '',
  test_method: props.nonConformity?.test_method || '',
  equipment_id: props.nonConformity?.equipment_id || '',
  batch_number: props.nonConformity?.batch_number || '',
  reported_by: props.nonConformity?.reported_by || '',
  assigned_to: props.nonConformity?.assigned_to || '',
  assigned_to_id: props.nonConformity?.assigned_to_id || '',
  reported_at: formatDateForInput(props.nonConformity?.reported_at) || formatDateForInput(new Date()),
  due_date: formatDateForInput(props.nonConformity?.due_date) || '',
  occurrence_area: props.nonConformity?.occurrence_area || '',
  root_cause: props.nonConformity?.root_cause || '',
  corrective_actions: props.nonConformity?.corrective_actions || '',
  preventive_actions: props.nonConformity?.preventive_actions || '',
  comments: props.nonConformity?.comments || '',
  attachment_files: [],
  actions_present: true,
  actions: props.nonConformity?.actions || [],
})

const selectedAttachmentFiles = ref([])
const actions = ref(cloneInitialActions())
const activeWorkflowSection = ref('event')

const workflowSections = [
  { value: 'event', label: 'Evento e classificação' },
  { value: 'scope', label: 'Âmbito e rastreabilidade' },
  { value: 'capa', label: 'Plano CAPA' },
  { value: 'evidence', label: 'Evidência e conclusão' },
]

const severityOptions = [
  { value: 'low', label: 'Baixa' },
  { value: 'medium', label: 'Média' },
  { value: 'high', label: 'Alta' },
  { value: 'critical', label: 'Crítica' },
]

const categoryOptions = [
  { value: 'quality', label: 'Qualidade' },
  { value: 'safety', label: 'Segurança' },
  { value: 'environmental', label: 'Ambiental' },
  { value: 'regulatory', label: 'Regulatório' },
  { value: 'other', label: 'Outro' },
]

const statusOptions = props.nonConformity?.status === 'resolved' ? [{ value: 'resolved', label: 'Resolvida — alterações exigem nova verificação' }] : [
  { value: 'opened', label: 'Aberta' },
  { value: 'in_progress', label: 'Em progresso' },
]

const severityValueClasses = {
  low: 'text-emerald-700 dark:text-emerald-300',
  medium: 'text-amber-700 dark:text-amber-300',
  high: 'text-orange-700 dark:text-orange-300',
  critical: 'text-rose-700 dark:text-rose-300',
}

const existingAttachments = computed(() => props.nonConformity?.media_attachments || [])
const evidenceCount = computed(() => selectedAttachmentFiles.value.length + existingAttachments.value.length)

const workflowMetrics = computed(() => [
  {
    label: 'Severidade',
    value: labelFor(severityOptions, form.severity),
    caption: 'Nível de impacto declarado',
    valueClass: severityValueClasses[form.severity] || severityValueClasses.medium,
  },
  {
    label: 'Acções CAPA',
    value: actions.value.length,
    caption: 'Itens de correcção e prevenção',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Dias em aberto',
    value: calculateDaysOpen(),
    caption: 'Desde a data de reporte',
    valueClass: isPastDue.value ? 'text-rose-700 dark:text-rose-300' : 'text-[var(--ds-text)]',
  },
  {
    label: 'Evidências',
    value: evidenceCount.value,
    caption: 'Ficheiros anexados ao registo',
    valueClass: 'text-[var(--ds-text)]',
  },
])

const isPastDue = computed(() => {
  if (!form.due_date || form.status === 'closed') {
    return false
  }

  return new Date(form.due_date) < new Date()
})

function cloneInitialActions() {
  return (props.nonConformity?.actions || []).filter(action => !action.deleted_at).map(action => ({
    id: action.id,
    correction: action.correction || '',
    corrective_action: action.corrective_action || '',
    due_at: formatDateForInput(action.due_at),
  }))
}

function labelFor(options, value) {
  return options.find(option => option.value === value)?.label || value || '--'
}

function addAction() {
  if (form.processing) return
  actions.value.push({
    correction: '',
    corrective_action: '',
    due_at: '',
  })
}

function removeAction(index) {
  if (form.processing) return
  actions.value.splice(index, 1)
}

function selectAttachmentFiles(event) {
  if (form.processing) return
  selectedAttachmentFiles.value = Array.from(event.target.files || [])
  form.attachment_files = selectedAttachmentFiles.value
}

function removeAttachmentFile(index) {
  if (form.processing) return
  selectedAttachmentFiles.value.splice(index, 1)
  form.attachment_files = selectedAttachmentFiles.value
}

function submit() {
  if (form.processing) return
  form.clearErrors()
  form.actions = actions.value
  form.attachment_files = selectedAttachmentFiles.value
  const options = {
    forceFormData: true,
    onError: focusFirstErrorSection,
    onHttpException: response => {
      form.setError('request', response.status === 409
        ? 'Não foi possível guardar. Os dados e ficheiros seleccionados foram mantidos; tente novamente.'
        : 'O registo ou a autorização já não está disponível. Actualize a página antes de tentar novamente.')
      return false
    },
    onNetworkError: () => {
      form.setError('request', 'Falha de ligação. Confirme o estado do dossier antes de repetir; os dados foram mantidos.')
      return false
    },
    onCancel: () => form.setError('request', 'Operação interrompida. Confirme o estado do dossier antes de repetir.'),
  }

  if (props.isEditing) {
    form
      .transform(data => ({
        ...data,
        _method: 'put',
      }))
      .post(route('vap_non_conformities.update', props.nonConformity.id), {
        ...options,
        onFinish: () => form.transform(data => data),
      })

    return
  }

  form.post(route('vap_non_conformities.store'), options)
}

function focusFirstErrorSection(errors) {
  const firstError = Object.keys(errors || {})[0] || ''

  if (/^(sample_id|test_method|equipment_id|batch_number|occurrence_area)/.test(firstError)) {
    activeWorkflowSection.value = 'scope'
    return
  }

  if (/^actions/.test(firstError)) {
    activeWorkflowSection.value = 'capa'
    return
  }

  if (/^(attachment_files|root_cause|corrective_actions|preventive_actions|comments)/.test(firstError)) {
    activeWorkflowSection.value = 'evidence'
    return
  }

  activeWorkflowSection.value = 'event'
}

function reset() {
  if (form.processing) return
  form.reset()
  actions.value = cloneInitialActions()
  selectedAttachmentFiles.value = []
  form.attachment_files = []
}

function cancel() {
  if (form.processing) return
  if (props.isEditing && props.nonConformity?.id) {
    router.visit(route('vap_non_conformities.show', props.nonConformity.id))

    return
  }

  router.visit(route('vap_non_conformities.index'))
}

function formatDate(dateString) {
  if (!dateString) {
    return '--'
  }

  return new Date(dateString).toLocaleDateString('pt-PT')
}

function formatDateForInput(dateString) {
  if (!dateString) {
    return ''
  }

  if (typeof dateString === 'string' && dateString.includes('T')) {
    return dateString.slice(0, 16)
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return ''
  }

  return date.toISOString().slice(0, 16)
}

function calculateDaysOpen() {
  if (!form.reported_at) {
    return 0
  }

  const reported = new Date(form.reported_at)
  const now = new Date()
  const diff = now - reported

  return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
}
</script>
