<template>
  <div
    :class="commercialDocumentThemeClasses"
    class="space-y-6"
  >
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.vap_proposal_templates.surface.library')"
      :title="$t('gestlab.general.labels.vap_proposal_templates.title')"
      :description="$t('gestlab.general.labels.vap_proposal_templates.description')"
    >
      <template #actions>
        <span class="ds-chip">
          {{ totalTemplates }} {{ $t('gestlab.general.labels.vap_proposal_templates.total') }}
        </span>
        <Link
          :href="route('vap-proposals.templates.create')"
          class="ds-button ds-button-primary"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposal_templates.create_new') }}
        </Link>
      </template>

      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article
          v-for="stat in templateStatCards"
          :key="stat.key"
          class="ds-card flex items-start justify-between gap-4 bg-[var(--ds-panel-raised)] p-4"
        >
          <div>
            <p class="ds-kicker text-[0.64rem]">
              {{ $t(stat.labelKey) }}
            </p>
            <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">
              {{ stat.value }}
            </p>
          </div>
          <span :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border', stat.tone]">
            <component
              :is="stat.icon"
              class="h-5 w-5"
            />
          </span>
        </article>
      </div>
    </ModuleHero>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-2xl">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.search') }}
          </p>
          <h2 class="ds-heading mt-2 text-2xl">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.refine_library') }}
          </h2>
          <p class="ds-copy mt-2 text-sm">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.presets_help') }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="resetFilters"
          >
            <ArrowPathIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.reset_filters') }}
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="importTemplate"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import') }}
          </button>
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="exportTemplates"
          >
            <ArrowUpTrayIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.export') }}
          </button>
        </div>
      </div>

      <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(16rem,1.3fr)_repeat(4,minmax(11rem,1fr))]">
        <BaseInput
          v-model="search"
          type="search"
          :label="$t('gestlab.general.labels.vap_proposal_templates.surface.search')"
          :placeholder="$t('gestlab.general.labels.vap_proposal_templates.search_placeholder')"
        >
          <template #leading>
            <MagnifyingGlassIcon class="h-5 w-5" />
          </template>
        </BaseInput>

        <BaseSelect
          v-model="statusFilter"
          :label="$t('gestlab.general.labels.vap_proposal_templates.show.status')"
        >
          <option value="all">
            {{ $t('gestlab.general.labels.vap_proposal_templates.filters.all') }}
          </option>
          <option value="active">
            {{ $t('gestlab.general.labels.vap_proposal_templates.filters.active') }}
          </option>
          <option value="inactive">
            {{ $t('gestlab.general.labels.vap_proposal_templates.filters.inactive') }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="categoryFilter"
          :label="$t('gestlab.general.labels.vap_proposal_templates.show.category')"
        >
          <option value="all">
            {{ $t('gestlab.general.labels.vap_proposal_templates.filters.all_categories') }}
          </option>
          <option
            v-for="category in categoryOptions"
            :key="category"
            :value="category"
          >
            {{ getCategoryLabel(category) }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="sortBy"
          :label="$t('gestlab.general.labels.vap_proposal_templates.sort_by')"
        >
          <option value="created_at">
            {{ $t('gestlab.general.labels.vap_proposal_templates.sort.newest') }}
          </option>
          <option value="name">
            {{ $t('gestlab.general.labels.vap_proposal_templates.sort.name') }}
          </option>
          <option value="proposals_count">
            {{ $t('gestlab.general.labels.vap_proposal_templates.sort.most_used') }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="exportFormat"
          :label="$t('gestlab.general.labels.vap_proposal_templates.surface.export_format')"
        >
          <option value="xlsx">
            Excel (.xlsx)
          </option>
          <option value="csv">
            CSV (.csv)
          </option>
          <option value="json">
            JSON (.json)
          </option>
        </BaseSelect>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_proposal_templates.list.title') }}
          </p>
          <h2 class="ds-heading mt-1 text-xl">
            {{ resultSummary }}
          </h2>
        </div>
        <Link
          :href="route('vap-proposals.templates.create')"
          class="ds-button ds-button-primary"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposal_templates.create_new') }}
        </Link>
      </div>

      <div
        v-if="!templateRows.length"
        class="p-5 sm:p-8"
      >
        <div class="ds-empty-state p-8 text-center">
          <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-4 text-base">
            {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.title') }}
          </h3>
          <p class="ds-copy mx-auto mt-2 max-w-md text-sm">
            {{ $t('gestlab.general.labels.vap_proposal_templates.empty_state.description') }}
          </p>
          <Link
            :href="route('vap-proposals.templates.create')"
            class="ds-button ds-button-primary mt-6"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposal_templates.create_first') }}
          </Link>
        </div>
      </div>

      <div v-else>
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
          <article
            v-for="template in templateRows"
            :key="template.id"
            class="group flex min-h-full flex-col overflow-hidden rounded-[1.55rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] shadow-[var(--ds-shadow-control)] transition duration-200 hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb)/0.72)]"
          >
            <div class="border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
              <div class="flex items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                  <span :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border', categoryIconClass(template.category)]">
                    <component
                      :is="getCategoryIcon(template.category)"
                      class="h-5 w-5"
                    />
                  </span>
                  <div class="min-w-0">
                    <h3 class="truncate text-base font-black text-[var(--ds-text)]">
                      {{ template.name }}
                    </h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                      <span class="ds-chip">
                        {{ getCategoryLabel(template.category) }}
                      </span>
                      <span :class="statusBadgeClass(template.is_active)">
                        {{ template.is_active ? $t('gestlab.general.labels.vap_proposal_templates.active') : $t('gestlab.general.labels.vap_proposal_templates.inactive') }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex shrink-0 items-center gap-1">
                  <Link
                    :href="route('vap-proposals.templates.show', template.id)"
                    class="ds-table-action"
                    :title="$t('gestlab.general.labels.vap_proposal_templates.view_details')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="route('vap-proposals.templates.edit', template.id)"
                    class="ds-table-action"
                    :title="$t('gestlab.general.labels.vap_proposal_templates.edit_label')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </Link>
                  <button
                    type="button"
                    class="ds-table-action ds-table-action-danger disabled:cursor-not-allowed disabled:opacity-40"
                    :title="$t('gestlab.general.labels.vap_proposal_templates.delete')"
                    :disabled="Number(template.proposals_count || 0) > 0"
                    @click="confirmDelete(template)"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>

            <div class="flex flex-1 flex-col p-4">
              <p class="line-clamp-3 min-h-[4.5rem] text-sm font-semibold leading-6 text-[var(--ds-text-muted)]">
                {{ stripHtml(template.content) || template.description || '—' }}
              </p>

              <div class="mt-5 grid grid-cols-3 gap-2">
                <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-center">
                  <p class="text-lg font-black tabular-nums text-[var(--ds-text)]">
                    {{ Number(template.proposals_count || 0) }}
                  </p>
                  <p class="mt-1 text-[0.66rem] font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.used') }}
                  </p>
                </div>
                <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-center">
                  <p class="text-lg font-black tabular-nums text-[var(--ds-text)]">
                    {{ calculateAcceptanceRate(template) }}%
                  </p>
                  <p class="mt-1 text-[0.66rem] font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.acceptance_rate') }}
                  </p>
                </div>
                <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3 text-center">
                  <p class="text-sm font-black tabular-nums text-[var(--ds-text)]">
                    {{ formatDate(template.updated_at) }}
                  </p>
                  <p class="mt-1 text-[0.66rem] font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">
                    {{ $t('gestlab.general.labels.vap_proposal_templates.last_updated') }}
                  </p>
                </div>
              </div>

              <div class="mt-auto flex flex-col gap-3 border-t border-[var(--ds-border)] pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
                  {{ $t('gestlab.general.labels.vap_proposal_templates.created_on') }} {{ formatDate(template.created_at) }}
                </p>
                <Link
                  :href="route('vap-proposals.create', { template_id: template.id })"
                  class="ds-button ds-button-secondary w-full sm:w-auto"
                >
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_proposal_templates.use_template') }}
                </Link>
              </div>
            </div>
          </article>
        </div>

        <div class="border-t border-[var(--ds-border)] px-2 py-3">
          <Pagination
            :links="paginationLinks"
            :from="paginationMeta.from"
            :to="paginationMeta.to"
            :total="paginationMeta.total"
            :current_page="paginationMeta.currentPage"
            :last_page="paginationMeta.lastPage"
          />
        </div>
      </div>
    </section>

    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.presets') }}
          </p>
          <h2 class="ds-heading mt-2 text-2xl">
            {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.title') }}
          </h2>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            {{ $t('gestlab.general.labels.vap_proposal_templates.surface.presets_help') }}
          </p>
        </div>

        <button
          type="button"
          class="ds-button ds-button-secondary"
          @click="showUsageReport"
        >
          <ChartBarIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.report') }}
        </button>
      </div>

      <div
        v-if="presetRows.length"
        class="mt-5 grid gap-4 md:grid-cols-3"
      >
        <Link
          v-for="preset in presetRows"
          :key="preset.slug"
          :href="route('vap-proposals.templates.create', { preset: preset.slug })"
          class="rounded-[1.35rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4 text-left shadow-[var(--ds-shadow-control)] transition hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb)/0.72)]"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-sm font-black text-[var(--ds-text)]">
                {{ preset.name }}
              </p>
              <p class="mt-1 text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">
                {{ getCategoryLabel(preset.category) }}
              </p>
            </div>
            <DocumentDuplicateIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
          </div>
          <p class="ds-copy mt-3 line-clamp-3 text-sm">
            {{ preset.description }}
          </p>
        </Link>
      </div>
    </section>

    <confirm-dialog
      v-if="selectedTemplate && showDeleteModal"
      :title="$t('gestlab.general.labels.vap_proposal_templates.delete_modal.title')"
      :description="$t('gestlab.general.labels.vap_proposal_templates.delete_modal.message', { name: selectedTemplate?.name })"
      :cancel="$t('gestlab.general.buttons.cancel')"
      :confirm="$t('gestlab.general.labels.vap_proposal_templates.delete')"
      variant="danger"
      @confirmed="deleteTemplate"
      @canceled="cancelDelete"
    />

    <Modal
      :show="showImportModal"
      max-width="lg"
      @close="closeImportModal"
    >
      <div class="p-5 sm:p-6">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="ds-kicker">
              {{ $t('gestlab.general.labels.vap_proposal_templates.quick_actions.import') }}
            </p>
            <h2 class="ds-heading mt-2 text-xl">
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.title') }}
            </h2>
            <p class="ds-copy mt-2 text-sm">
              {{ $t('gestlab.general.labels.vap_proposal_templates.import.file_types') }}
            </p>
          </div>
          <button
            type="button"
            class="ds-icon-button"
            @click="closeImportModal"
          >
            <XMarkIcon class="h-5 w-5" />
          </button>
        </div>

        <div
          class="mt-5 cursor-pointer rounded-[1.5rem] border-2 border-dashed p-8 text-center transition"
          :class="dragOver ? 'border-[rgb(var(--primary-500-rgb))] bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)]' : 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] hover:border-[rgb(var(--primary-300-rgb))]'"
          @click="openFilePicker"
          @dragover.prevent="dragOver = true"
          @dragleave="dragOver = false"
          @drop="handleFileDrop"
        >
          <input
            ref="fileInput"
            type="file"
            accept=".json,.txt,.xlsx,.csv"
            class="hidden"
            @change="handleFileSelect"
          />
          <CloudArrowUpIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
          <p class="mt-4 text-sm font-bold text-[var(--ds-text)]">
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.drag_drop') }}
          </p>
          <span class="ds-button ds-button-secondary mt-4">
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.browse_files') }}
          </span>
          <p class="mt-3 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.file_types') }}
          </p>
        </div>

        <div
          v-if="importFile"
          class="mt-4 rounded-[1.25rem] border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4"
        >
          <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
              <p class="truncate text-sm font-black text-[var(--ds-text)]">
                {{ importFile.name }}
              </p>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ formatFileSize(importFile.size) }}
              </p>
            </div>
            <button
              type="button"
              class="ds-table-action ds-table-action-danger"
              @click="importFile = null"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-4 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="closeImportModal"
          >
            {{ $t('gestlab.general.buttons.cancel') }}
          </button>
          <button
            type="button"
            class="ds-button ds-button-primary disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="!importFile"
            @click="processImport"
          >
            {{ $t('gestlab.general.labels.vap_proposal_templates.import.process') }}
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  ArrowUpTrayIcon,
  BeakerIcon,
  BugAntIcon,
  CakeIcon,
  ChartBarIcon,
  CheckCircleIcon,
  CloudArrowUpIcon,
  CpuChipIcon,
  DocumentChartBarIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  EyeIcon,
  GlobeAltIcon,
  MagnifyingGlassIcon,
  NoSymbolIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n'
import { useToast } from 'vue-toastification'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import Modal from '@/Components/modal.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import Pagination from '@/Components/Pagination.vue'
import { commercialDocumentThemeClasses } from '@/Composables/useCommercialDocumentTheme'

const props = defineProps({
  templates: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  presets: {
    type: Array,
    default: () => [],
  },
})

const toast = useToast()
const search = ref(props.filters?.search ?? '')
const statusFilter = ref(props.filters?.status ?? 'all')
const categoryFilter = ref(props.filters?.category ?? 'all')
const sortBy = ref(props.filters?.sort ?? 'created_at')
const showDeleteModal = ref(false)
const showImportModal = ref(false)
const selectedTemplate = ref(null)
const importFile = ref(null)
const dragOver = ref(false)
const exportFormat = ref('xlsx')
const fileInput = ref(null)

const categoryOptions = ['general', 'compliance', 'field-services', 'chemical', 'microbiology', 'physical', 'environmental', 'food']
const categoryIcons = {
  compliance: CheckCircleIcon,
  'field-services': GlobeAltIcon,
  chemical: BeakerIcon,
  microbiology: BugAntIcon,
  physical: CpuChipIcon,
  environmental: GlobeAltIcon,
  food: CakeIcon,
  general: DocumentChartBarIcon,
}

const templateRows = computed(() => Array.isArray(props.templates?.data) ? props.templates.data : [])
const presetRows = computed(() => Array.isArray(props.presets) ? props.presets : [])
const paginationLinks = computed(() => Array.isArray(props.templates?.links) ? props.templates.links : [])
const totalTemplates = computed(() => Number(props.templates?.total ?? templateRows.value.length))
const activeTemplatesCount = computed(() => templateRows.value.filter((template) => template.is_active).length)
const inactiveTemplatesCount = computed(() => templateRows.value.filter((template) => !template.is_active).length)
const totalProposalsCount = computed(() => templateRows.value.reduce((sum, template) => sum + Number(template.proposals_count || 0), 0))

const paginationMeta = computed(() => ({
  from: Number(props.templates?.from ?? (templateRows.value.length ? 1 : 0)),
  to: Number(props.templates?.to ?? templateRows.value.length),
  total: totalTemplates.value,
  currentPage: Number(props.templates?.current_page ?? 1),
  lastPage: Number(props.templates?.last_page ?? 1),
}))

const resultSummary = computed(() => trans('gestlab.general.labels.vap_proposal_templates.list.summary', {
  from: paginationMeta.value.from,
  to: paginationMeta.value.to,
  total: paginationMeta.value.total,
}))

const templateStatCards = computed(() => [
  {
    key: 'total',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.total',
    value: totalTemplates.value,
    icon: DocumentTextIcon,
    tone: 'border-[rgb(var(--primary-200-rgb))] bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--primary-300-rgb)/0.22)] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))]',
  },
  {
    key: 'active',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.active',
    value: activeTemplatesCount.value,
    icon: CheckCircleIcon,
    tone: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-300/20 dark:bg-emerald-400/10 dark:text-emerald-100',
  },
  {
    key: 'inactive',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.inactive',
    value: inactiveTemplatesCount.value,
    icon: NoSymbolIcon,
    tone: 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
  },
  {
    key: 'used',
    labelKey: 'gestlab.general.labels.vap_proposal_templates.stats.used',
    value: totalProposalsCount.value,
    icon: ChartBarIcon,
    tone: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-300/20 dark:bg-amber-400/10 dark:text-amber-100',
  },
])

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

const downloadFilename = (response, fallback) => {
  const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || ''
  const match = disposition.match(/filename="?([^"]+)"?/i)

  return match?.[1] || fallback
}

const getCategoryIcon = (category) => categoryIcons[category] || DocumentChartBarIcon

const categoryIconClass = (category) => {
  const classes = {
    compliance: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-300/20 dark:bg-emerald-400/10 dark:text-emerald-100',
    'field-services': 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-300/20 dark:bg-amber-400/10 dark:text-amber-100',
    chemical: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-300/20 dark:bg-sky-400/10 dark:text-sky-100',
    microbiology: 'border-green-200 bg-green-50 text-green-800 dark:border-green-300/20 dark:bg-green-400/10 dark:text-green-100',
    physical: 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-300/20 dark:bg-yellow-400/10 dark:text-yellow-100',
    environmental: 'border-teal-200 bg-teal-50 text-teal-800 dark:border-teal-300/20 dark:bg-teal-400/10 dark:text-teal-100',
    food: 'border-red-200 bg-red-50 text-red-800 dark:border-red-300/20 dark:bg-red-400/10 dark:text-red-100',
    general: 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
  }

  return classes[category] || classes.general
}

const statusBadgeClass = (isActive) => {
  return isActive
    ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-black text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2.5 py-0.5 text-xs font-black text-[var(--ds-text-muted)]'
}

const getCategoryLabel = (category) => {
  const key = category === 'field-services' ? 'field_services' : category

  return trans(`gestlab.general.labels.vap_proposal_templates.categories.${key}`) || trans('gestlab.general.labels.vap_proposal_templates.categories.general')
}

const applyFilters = debounce(() => {
  router.get(route('vap-proposals.templates.index'), {
    search: search.value,
    status: statusFilter.value,
    category: categoryFilter.value,
    sort: sortBy.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 350)

const resetFilters = () => {
  search.value = ''
  statusFilter.value = 'all'
  categoryFilter.value = 'all'
  sortBy.value = 'created_at'
  router.get(route('vap-proposals.templates.index'), {}, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const formatDate = (date) => {
  if (!date) {
    return '—'
  }

  return new Date(date).toLocaleDateString('pt-AO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const stripHtml = (html) => {
  if (!html) {
    return ''
  }

  const stripped = String(html).replace(/<[^>]*>/g, '').replace(/\s+/g, ' ').trim()

  return stripped.length > 150 ? `${stripped.substring(0, 150)}...` : stripped
}

const calculateAcceptanceRate = (template) => {
  const proposalsCount = Number(template.proposals_count || 0)

  if (!proposalsCount) {
    return 0
  }

  return Math.round((Number(template.accepted_proposals_count || 0) / proposalsCount) * 100)
}

const confirmDelete = (template) => {
  if (Number(template.proposals_count || 0) > 0) {
    toast.warning(trans('gestlab.general.labels.vap_proposal_templates.notifications.cannot_delete_in_use'))
    return
  }

  selectedTemplate.value = template
  showDeleteModal.value = true
}

const cancelDelete = () => {
  showDeleteModal.value = false
  selectedTemplate.value = null
}

const deleteTemplate = () => {
  if (!selectedTemplate.value?.id) {
    cancelDelete()
    return
  }

  router.delete(route('vap-proposals.templates.destroy', selectedTemplate.value.id), {
    onSuccess: cancelDelete,
  })
}

const importTemplate = () => {
  showImportModal.value = true
}

const closeImportModal = () => {
  showImportModal.value = false
  dragOver.value = false
}

const openFilePicker = () => {
  fileInput.value?.click()
}

const handleFileDrop = (event) => {
  event.preventDefault()
  dragOver.value = false

  const files = event.dataTransfer?.files
  if (files?.length) {
    importFile.value = files[0]
  }
}

const handleFileSelect = (event) => {
  const files = event.target.files
  if (files?.length) {
    importFile.value = files[0]
  }
}

const formatFileSize = (bytes) => {
  if (!bytes) {
    return '0 Bytes'
  }

  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))

  return `${Number.parseFloat((bytes / Math.pow(k, i)).toFixed(2))} ${sizes[i]}`
}

const processImport = async () => {
  if (!importFile.value) {
    return
  }

  const formData = new FormData()
  formData.append('template_file', importFile.value)

  try {
    const response = await fetch(route('vap-proposals.templates.import'), {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: formData,
    })
    const payload = await response.json()

    if (!response.ok || !payload.success) {
      throw new Error(payload.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.import_request_error'))
    }

    showImportModal.value = false
    importFile.value = null
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.notifications.import_success'))
    router.reload()
  } catch (error) {
    toast.error(error.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.import_error'))
  }
}

const exportTemplates = async () => {
  try {
    const response = await fetch(route('vap-proposals.templates.export', {
      format: exportFormat.value,
    }))

    if (!response.ok) {
      throw new Error(trans('gestlab.general.labels.vap_proposal_templates.notifications.export_request_error'))
    }

    const blob = await response.blob()
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', downloadFilename(response, `modelos-proposta-${new Date().toISOString().split('T')[0]}.${exportFormat.value}`))
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    toast.success(trans('gestlab.general.labels.vap_proposal_templates.notifications.export_success'))
  } catch (error) {
    toast.error(error.message || trans('gestlab.general.labels.vap_proposal_templates.notifications.export_error'))
  }
}

const showUsageReport = () => {
  toast.info(trans('gestlab.general.labels.vap_proposal_templates.notifications.usage_report'))
}

watch([search, statusFilter, categoryFilter, sortBy], applyFilters)
</script>
