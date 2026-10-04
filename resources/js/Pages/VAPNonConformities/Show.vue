<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <Head title="Não conformidades" />
    <p v-if="downloads.error.value" role="alert" class="ds-alert ds-alert-danger">{{ downloads.error.value }}</p>
    <p v-if="downloads.processing.value" role="status">A preparar exportação…</p>
    <p v-if="archive.failed.value" role="alert" class="ds-alert ds-alert-danger">{{ archive.message.value }}</p>
    <p v-if="archive.processing.value" role="status">A guardar…</p>
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">CAPA dossier</span>
            <span :class="['inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', statusClasses]">
              <span :class="['h-2 w-2 rounded-full', statusDotClass]"></span>
              {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nonConformity.status}`) }}
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_non_conformities.details_title') }}
              </h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                {{ $t('gestlab.general.labels.vap_non_conformities.details_description') }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" :disabled="downloads.processing.value" @click="downloads.download(route('vap_non_conformities.export.details.pdf', nonConformity.id))" class="ds-button ds-button-secondary">
            <ArrowDownTrayIcon class="h-4 w-4" />
            PDF
          </button>
          <button type="button" :disabled="downloads.processing.value" @click="downloads.download(route('vap_non_conformities.export.details.excel', nonConformity.id))" class="ds-button ds-button-secondary">
            <DocumentArrowDownIcon class="h-4 w-4" />
            Excel
          </button>
          <Link v-if="can.edit && !nonConformity.deleted_at" :href="route('vap_non_conformities.edit', nonConformity.id)" class="ds-button ds-button-primary">
            <PencilSquareIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit') }}
          </Link>
        </div>
      </div>

      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 text-xl font-black" :class="card.valueClass">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5', card.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>

      <nav class="mt-5 flex overflow-x-auto border-t border-[var(--ds-border)] pt-1" aria-label="Secções do dossier CAPA">
        <button
          v-for="section in dossierSections"
          :key="section.value"
          type="button"
          class="-mb-px min-h-12 shrink-0 border-b-2 px-4 text-sm font-bold transition"
          :class="activeDossierSection === section.value ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
          @click="activeDossierSection = section.value"
        >
          {{ section.label }}
        </button>
      </nav>
    </section>

    <NonConformityLifecycle :record="nonConformity" :can="can" />
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <div class="space-y-6">
        <section v-show="activeDossierSection === 'overview'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <InformationCircleIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.basic_info') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Classificação, rastreabilidade e descrição do evento.</p>
              </div>
            </div>
          </div>

          <dl class="grid gap-4 p-5 md:grid-cols-2">
            <div v-for="field in basicFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="mt-2 text-sm font-bold" :class="field.valueClass">{{ field.value }}</dd>
            </div>
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 md:col-span-2">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.description') }}</dt>
              <dd class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-[var(--ds-text)]">{{ nonConformity.description }}</dd>
            </div>
          </dl>
        </section>

        <section v-show="activeDossierSection === 'overview'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <LinkIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.related_info') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Amostras, métodos, equipamento, lote e local de ocorrência.</p>
              </div>
            </div>
          </div>

          <dl class="grid gap-4 p-5 md:grid-cols-2">
            <div v-for="field in relatedFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ field.value }}</dd>
            </div>
          </dl>
        </section>

        <section v-show="activeDossierSection === 'capa'" class="ds-table-shell">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <WrenchScrewdriverIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.corrective_actions') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Correcções e acções correctivas registadas no dossier.</p>
              </div>
            </div>
            <span class="ds-chip">{{ actionRows.length }} acções</span>
          </div>

          <div v-if="actionRows.length" class="divide-y divide-[var(--ds-border)]">
            <article v-for="(action, index) in actionRows" :key="action.id || index" class="p-5">
              <p v-if="action.deleted_at" class="ds-chip mb-3">Arquivada · Histórico preservado</p>
              <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                  <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-sm font-black text-[var(--ds-text)]">
                    {{ index + 1 }}
                  </span>
                  <div class="min-w-0">
                    <h3 class="text-sm font-black text-[var(--ds-text)]">
                      {{ $t('gestlab.general.labels.vap_non_conformities.action') }} #{{ index + 1 }}
                    </h3>
                    <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
                      {{ action.due_at ? $t('gestlab.general.labels.vap_non_conformities.due') + ': ' + formatDateTime(action.due_at) : $t('gestlab.general.labels.vap_non_conformities.no_due_date') }}
                    </p>
                  </div>
                </div>
                <span v-if="action.approved_at" class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-800 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20">
                  {{ $t('gestlab.general.labels.vap_non_conformities.approved') }}
                </span>
              </div>

              <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div v-if="action.correction" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                  <h4 class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.correction') }}</h4>
                  <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-[var(--ds-text)]">{{ action.correction }}</p>
                </div>
                <div v-if="action.corrective_action" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
                  <h4 class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.corrective_action') }}</h4>
                  <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-[var(--ds-text)]">{{ action.corrective_action }}</p>
                </div>
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
          </div>
        </section>

        <section v-show="activeDossierSection === 'evidence'" class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <ClipboardDocumentCheckIcon class="mt-0.5 h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              <div>
                <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.additional_info') }}</h2>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Causa raiz, prevenção, acções globais e comentários.</p>
              </div>
            </div>
          </div>

          <div v-if="additionalNarratives.length" class="grid gap-4 p-5">
            <article v-for="item in additionalNarratives" :key="item.label" class="ds-card p-4">
              <h3 class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ item.label }}</h3>
              <p class="mt-2 whitespace-pre-line text-sm font-semibold leading-6 text-[var(--ds-text)]">{{ item.value }}</p>
            </article>
          </div>
          <div v-else class="ds-empty-state m-5 p-8 text-center text-sm text-[var(--ds-text-muted)]">Ainda não existem conclusões narrativas registadas.</div>
        </section>
      </div>

      <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
        <section class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.timeline') }}</h2>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Responsabilidade, reporte e marcos do fluxo.</p>

          <dl class="mt-5 space-y-3">
            <div v-for="field in timelineFields" :key="field.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="mt-2 text-sm font-bold" :class="field.valueClass">{{ field.value }}</dd>
            </div>
          </dl>
        </section>

        <section v-show="activeDossierSection === 'evidence'" class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading flex items-center gap-2 text-base">
              <PaperClipIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
              {{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}
            </h2>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Evidência documental anexada ao registo.</p>
          </div>

          <div class="space-y-2 p-5">
            <a
              v-for="attachment in mediaAttachments"
              :key="attachment.id"
              :href="attachment.url"
              target="_blank"
              rel="noreferrer"
              class="flex items-center justify-between gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-sm transition hover:border-[rgb(var(--primary-300-rgb))]"
            >
              <span class="truncate font-semibold text-[var(--ds-text)]">{{ attachment.name || attachment.file_name }}</span>
              <span class="shrink-0 text-xs font-bold text-[var(--ds-text-soft)]">{{ attachment.human_readable_size }}</span>
            </a>

            <p v-if="!mediaAttachments.length" class="rounded-lg border border-dashed border-[var(--ds-border)] px-4 py-6 text-center text-sm font-semibold text-[var(--ds-text-muted)]">
              Nenhum anexo registado para esta não conformidade.
            </p>
          </div>
        </section>

        <section class="ds-command-surface p-5">
          <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.vap_non_conformities.actions.title') }}</h2>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">Acções administrativas para este dossier.</p>
          <div class="mt-5 space-y-2">
            <button v-if="can.restore && nonConformity.deleted_at" type="button" :disabled="archive.processing.value" class="ds-button ds-button-primary w-full" @click="archive.submit('restore', [nonConformity.id])">Restaurar não conformidade</button>
            <Link v-if="can.edit && !nonConformity.deleted_at" :href="route('vap_non_conformities.edit', nonConformity.id)" class="ds-button ds-button-primary w-full">
              <PencilSquareIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit_nc') }}
            </Link>
            <button v-if="can.archive && !nonConformity.deleted_at" type="button" :disabled="archive.processing.value" class="ds-button ds-button-danger w-full" @click="openDeleteModal">
              <TrashIcon class="h-4 w-4" />
              Arquivar não conformidade
            </button>
          </div>
        </section>
      </aside>
    </div>

    <section class="ds-command-surface p-5">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm font-semibold text-[var(--ds-text-soft)]">
          {{ $t('gestlab.general.labels.vap_non_conformities.created_at') }}:
          <span class="font-bold text-[var(--ds-text)]">{{ formatDateTime(nonConformity.created_at) }}</span>
          <span v-if="nonConformity.updated_at" class="ml-0 mt-1 block sm:ml-4 sm:mt-0 sm:inline">
            {{ $t('gestlab.general.labels.vap_non_conformities.updated_at') }}:
            <span class="font-bold text-[var(--ds-text)]">{{ formatDateTime(nonConformity.updated_at) }}</span>
          </span>
        </p>

        <Link :href="route('vap_non_conformities.index')" class="ds-button ds-button-secondary">
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.back_to_list') }}
        </Link>
      </div>
    </section>

    <confirm-dialog
      v-if="showDeleteModal"
      title="Arquivar não conformidade?"
      description="O dossier, as acções e os anexos serão preservados. Pode restaurar o registo com autorização."
      :cancel="$t('gestlab.general.labels.vap_non_conformities.buttons.cancel')"
      :disabled="archive.processing.value"
      :keep-open-on-confirm="true"
      confirm="Arquivar"
      variant="danger"
      @confirmed="deleteNc"
      @canceled="closeDeleteModal"
    >
      <p v-if="archive.failed.value" role="alert" class="ds-alert ds-alert-danger">{{ archive.message.value }}</p>
      <div class="mt-4 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 text-left">
        <p class="font-mono text-xs font-black text-[var(--ds-text-soft)]">{{ nonConformity.nc_number }}</p>
        <p class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ nonConformity.title }}</p>
      </div>
    </confirm-dialog>
  </div>
</template>

<script setup>
import NonConformityLifecycle from '@/Pages/VAPNonConformities/NonConformityLifecycle.vue'
import { useFileDownload } from '@/Composables/useFileDownload'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  Download as ArrowDownTrayIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Clock as ClockIcon,
  FileDown as DocumentArrowDownIcon,
  CircleAlert as ExclamationCircleIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Info as InformationCircleIcon,
  Link as LinkIcon,
  Paperclip as PaperClipIcon,
  SquarePen as PencilSquareIcon,
  Trash2 as TrashIcon,
  Wrench as WrenchScrewdriverIcon,
} from '@lucide/vue'
import { computed, ref } from 'vue'

const downloads = useFileDownload()
const props = defineProps({
  can: { type: Object, default: () => ({}) },
  nonConformity: {
    type: Object,
    required: true,
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
})

const archive = useRecordArchive({
  destroyUrl: ids => route('vap_non_conformities.destroy', ids[0]),
  restoreUrl: ids => route('vap_non_conformities.restore', ids[0]),
  onSuccess: () => { showDeleteModal.value = false },
})
const showDeleteModal = ref(false)
const activeDossierSection = ref('overview')

const dossierSections = [
  { value: 'overview', label: 'Evento e rastreabilidade' },
  { value: 'capa', label: 'Plano CAPA' },
  { value: 'evidence', label: 'Evidência e conclusão' },
]

const statusClassMap = {
  opened: 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] ring-[rgb(var(--primary-200-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.22)]',
  in_progress: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  resolved: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  closed: 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)] ring-[var(--ds-border-strong)]',
}

const statusDotMap = {
  opened: 'bg-[rgb(var(--primary-700-rgb))]',
  in_progress: 'bg-amber-500',
  resolved: 'bg-emerald-500',
  closed: 'bg-[var(--ds-text-soft)]',
}

const severityClassMap = {
  low: 'text-emerald-700 dark:text-emerald-300',
  medium: 'text-amber-700 dark:text-amber-300',
  high: 'text-orange-700 dark:text-orange-300',
  critical: 'text-rose-700 dark:text-rose-300',
}

const categoryLabels = {
  quality: 'Qualidade',
  safety: 'Segurança',
  environmental: 'Ambiental',
  regulatory: 'Regulatório',
  other: 'Outro',
}

const mediaAttachments = computed(() => props.nonConformity.media_attachments || [])
const actionRows = computed(() => props.nonConformity.actions || [])
const isOverdue = computed(() => {
  if (!props.nonConformity.due_date || props.nonConformity.status === 'closed') {
    return false
  }

  return new Date(props.nonConformity.due_date) < new Date()
})

const statusClasses = computed(() => statusClassMap[props.nonConformity.status] || statusClassMap.opened)
const statusDotClass = computed(() => statusDotMap[props.nonConformity.status] || statusDotMap.opened)
const severityClasses = computed(() => severityClassMap[props.nonConformity.severity] || severityClassMap.medium)

const summaryCards = computed(() => [
  {
    label: 'Número',
    value: props.nonConformity.nc_number,
    detail: 'Identificador rastreável do evento',
    icon: ClipboardDocumentCheckIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Severidade',
    value: severityLabel(props.nonConformity.severity),
    detail: 'Nível de impacto declarado',
    icon: ExclamationCircleIcon,
    tone: severityClasses.value,
    valueClass: severityClasses.value,
  },
  {
    label: 'Acções',
    value: actionRows.value.length,
    detail: 'Correcções e CAPA associadas',
    icon: WrenchScrewdriverIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Prazo',
    value: props.nonConformity.due_date ? formatDateTime(props.nonConformity.due_date) : '--',
    detail: isOverdue.value ? 'Prazo ultrapassado' : 'Data alvo de resolução',
    icon: ClockIcon,
    tone: isOverdue.value ? 'text-rose-700 dark:text-rose-300' : 'text-amber-700 dark:text-amber-300',
    valueClass: isOverdue.value ? 'text-rose-700 dark:text-rose-300' : 'text-[var(--ds-text)]',
  },
])

const basicFields = computed(() => [
  {
    label: 'Número',
    value: props.nonConformity.nc_number,
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Título',
    value: props.nonConformity.title,
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Severidade',
    value: severityLabel(props.nonConformity.severity),
    valueClass: severityClasses.value,
  },
  {
    label: 'Categoria',
    value: categoryLabel(props.nonConformity.category),
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Laboratório',
    value: props.nonConformity.lab?.name || '--',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Departamento',
    value: props.nonConformity.department?.name || '--',
    valueClass: 'text-[var(--ds-text)]',
  },
])

const relatedFields = computed(() => [
  {
    label: 'Amostra',
    value: props.nonConformity.sample_id || '--',
  },
  {
    label: 'Método',
    value: props.nonConformity.test_method || '--',
  },
  {
    label: 'Equipamento',
    value: props.nonConformity.equipment_id || '--',
  },
  {
    label: 'Lote',
    value: props.nonConformity.batch_number || '--',
  },
  {
    label: 'Área de ocorrência',
    value: props.nonConformity.occurrence_area || '--',
  },
])

const timelineFields = computed(() => [
  {
    label: 'Reportado por',
    value: props.nonConformity.reported_by || '--',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Reportado em',
    value: formatDateTime(props.nonConformity.reported_at),
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Responsável',
    value: props.nonConformity.assigned_to || '--',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Prazo',
    value: props.nonConformity.due_date ? formatDateTime(props.nonConformity.due_date) : '--',
    valueClass: isOverdue.value ? 'text-rose-700 dark:text-rose-300' : 'text-[var(--ds-text)]',
  },
  {
    label: 'Resolvido em',
    value: props.nonConformity.resolved_at ? formatDateTime(props.nonConformity.resolved_at) : '--',
    valueClass: 'text-[var(--ds-text)]',
  },
  {
    label: 'Fechado em',
    value: props.nonConformity.closed_at ? formatDateTime(props.nonConformity.closed_at) : '--',
    valueClass: 'text-[var(--ds-text)]',
  },
])

const additionalNarratives = computed(() => [
  {
    label: 'Causa raiz',
    value: props.nonConformity.root_cause,
  },
  {
    label: 'Acções correctivas gerais',
    value: props.nonConformity.corrective_actions,
  },
  {
    label: 'Acções preventivas',
    value: props.nonConformity.preventive_actions,
  },
  {
    label: 'Comentários',
    value: props.nonConformity.comments,
  },
].filter(item => Boolean(item.value)))

function formatDateTime(dateString) {
  if (!dateString) {
    return '--'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '--'
  }

  return `${date.toLocaleDateString('pt-PT')} ${date.toLocaleTimeString('pt-PT', { hour: '2-digit', minute: '2-digit' })}`
}

function categoryLabel(category) {
  return categoryLabels[category] || category || '--'
}

function severityLabel(severity) {
  const labels = {
    low: 'Baixa',
    medium: 'Média',
    high: 'Alta',
    critical: 'Crítica',
  }

  return labels[severity] || severity || '--'
}

function openDeleteModal() {
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (archive.processing.value) return
  showDeleteModal.value = false
}

function deleteNc() {
  archive.submit('delete', [props.nonConformity.id])
}
</script>
