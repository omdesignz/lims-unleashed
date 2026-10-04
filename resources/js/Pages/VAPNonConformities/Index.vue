<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <Head title="Não conformidades" />
    <p v-if="downloads.error.value || filterError" role="alert" class="ds-alert ds-alert-danger">{{ downloads.error.value || filterError }}</p>
    <p v-if="downloads.processing.value" role="status">A preparar exportação…</p>
    <p v-if="archive.failed.value" role="alert" class="ds-alert ds-alert-danger">{{ archive.message.value }}</p>
    <p v-if="archive.processing.value" role="status">A guardar…</p>
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Quality assurance</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-hold"></span>
              CAPA fluxo de trabalho
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">
                {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
              </h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                {{ $t('gestlab.general.labels.vap_non_conformities.index_description') }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <Link :href="route('vap_non_conformities.index', { archived: filters.archived ? undefined : 1 })" class="ds-button ds-button-secondary">{{ filters.archived ? 'Registos activos' : 'Arquivo' }}</Link>
          <button type="button" class="ds-button ds-button-secondary" :disabled="downloads.processing.value || filtering" @click="exportReport('pdf')">
            <ArrowDownTrayIcon class="h-4 w-4" />
            PDF
          </button>
          <button type="button" class="ds-button ds-button-secondary" :disabled="downloads.processing.value || filtering" @click="exportReport('excel')">
            <DocumentArrowDownIcon class="h-4 w-4" />
            Excel
          </button>
          <Link v-if="can.create && !filters.archived" :href="route('vap_non_conformities.create')" class="ds-button ds-button-primary">
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.new_non_conformity') }}
          </Link>
        </div>
      </div>

      <p class="mt-4 text-sm text-[var(--ds-text-muted)]">Indicadores e exportações: {{ filters.archived ? 'arquivo' : 'registos activos' }} do laboratório, com os filtros aplicados.</p>
      <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4">
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 text-2xl font-black text-[var(--ds-text)]">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5', card.tone]" />
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>

      <nav class="mt-5 flex overflow-x-auto border-t border-[var(--ds-border)] pt-1" aria-label="Vistas de não conformidades">
        <button
          v-for="view in workspaceViews"
          :key="view.value"
          type="button"
          class="-mb-px inline-flex min-h-12 shrink-0 items-center gap-2 border-b-2 px-4 text-sm font-bold transition"
          :class="workspaceView === view.value ? 'border-[rgb(var(--primary-700-rgb))] text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--accent-200-rgb))] dark:text-[rgb(var(--accent-100-rgb))]' : 'border-transparent text-[var(--ds-text-muted)] hover:border-[var(--ds-border-strong)] hover:text-[var(--ds-text)]'"
          @click="workspaceView = view.value"
        >
          <component :is="view.icon" class="h-4 w-4" />
          {{ view.label }}
        </button>
      </nav>
    </section>

    <section v-show="workspaceView === 'register'" class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[minmax(16rem,1.4fr)_repeat(3,minmax(11rem,1fr))]">
        <label class="ds-field-group">
          <span class="ds-field-label">Pesquisar</span>
          <span class="relative block">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              v-model="search"
              type="search"
              class="ds-field pl-10"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.search_placeholder')"
              @keyup.enter="applyFilters"
            />
          </span>
        </label>

        <BaseSelect v-model="statusFilter" :label="$t('gestlab.general.labels.vap_non_conformities.status.title')">
          <option value="">{{ $t('gestlab.general.labels.vap_non_conformities.all_statuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">
            {{ $t(`gestlab.general.labels.vap_non_conformities.status.${status}`) }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="severityFilter" :label="$t('gestlab.general.labels.vap_non_conformities.severity.title')">
          <option value="">{{ $t('gestlab.general.labels.vap_non_conformities.all_severities') }}</option>
          <option v-for="severity in severities" :key="severity" :value="severity">
            {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${severity}`) }}
          </option>
        </BaseSelect>

        <BaseSelect v-model="categoryFilter" :label="$t('gestlab.general.labels.vap_non_conformities.category')">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category" :value="category">
            {{ categoryLabel(category) }}
          </option>
        </BaseSelect>
      </div>

      <div class="mt-4 flex flex-wrap gap-4">
        <BaseInput v-model="startDate" type="date" label="Relatadas desde" />
        <BaseInput v-model="endDate" type="date" label="Relatadas até" />
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">
            Mostrando {{ nonConformities.from || 0 }} a {{ nonConformities.to || 0 }} de {{ nonConformities.total || 0 }} desvios
          </p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            Registos do laboratório na vista seleccionada.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <button type="button" class="ds-button ds-button-secondary" @click="applyFilters">
            <FunnelIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.apply_filters') }}
          </button>
          <button type="button" class="ds-button ds-button-secondary" :disabled="!hasFilters" @click="clearFilters">
            <XMarkIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.clear_filters') }}
          </button>
        </div>
      </div>
    </section>

    <section v-show="workspaceView === 'analytics'" class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Análise CAPA</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Tendência, severidade e estado do fluxo</h2>
        </div>
        <span class="ds-chip">{{ riskLoad }} registos em atenção</span>
      </div>

      <div class="grid gap-4 p-4 xl:grid-cols-3">
        <article class="ds-card p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Tendência de não conformidades</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Datas de relato dos registos filtrados, nos últimos seis meses.</p>
            </div>
            <ChartBarSquareIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]" />
          </div>
          <ChartWrapper class="mt-4 min-h-72" type="area" height="288" :series="ncTrendSeries" :options="ncTrendOptions" />
        </article>

        <article class="ds-card p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Distribuição por severidade</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Prioridade para contenção, investigação e CAPA.</p>
            </div>
            <ExclamationCircleIcon class="h-5 w-5 text-rose-700 dark:text-rose-300" />
          </div>
          <ChartWrapper class="mt-4 min-h-72" type="donut" height="288" :series="severityChartSeries" :options="severityChartOptions" />
        </article>

        <article class="ds-card p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Estado do fluxo</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Abertas, em progresso, resolvidas e encerradas.</p>
            </div>
            <ClipboardDocumentCheckIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
          </div>
          <ChartWrapper class="mt-4 min-h-72" type="bar" height="288" :series="statusChartSeries" :options="statusChartOptions" />
        </article>
      </div>
    </section>

    <section v-show="workspaceView === 'register'" class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Fila de qualidade</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">
            {{ $t('gestlab.general.labels.vap_non_conformities.list_title') }}
          </h2>
        </div>
        <span class="ds-chip">{{ nonConformityRows.length }} nesta página</span>
      </div>

      <div v-if="nonConformityRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
        <article v-for="nc in nonConformityRows" :key="`mobile-${nc.id}`" class="space-y-4 p-5">
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ nc.nc_number }}</p>
              <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ nc.title }}</h3>
              <p class="mt-1 line-clamp-2 text-sm font-semibold text-[var(--ds-text-muted)]">{{ truncateText(nc.description, 110) }}</p>
            </div>
            <span :class="['inline-flex shrink-0 items-center gap-2 rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', severityClasses[nc.severity]]">
              {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${nc.severity}`) }}
            </span>
          </div>

          <dl class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Estado</dt>
              <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ $t(`gestlab.general.labels.vap_non_conformities.status.${nc.status}`) }}</dd>
            </div>
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Reportado</dt>
              <dd class="mt-2 text-sm font-bold text-[var(--ds-text)]">{{ formatDate(nc.reported_at) }}</dd>
            </div>
            <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
              <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Prazo</dt>
              <dd :class="['mt-2 text-sm font-bold', isOverdue(nc) ? 'text-rose-700 dark:text-rose-300' : 'text-[var(--ds-text)]']">
                {{ nc.due_date ? formatDate(nc.due_date) : '--' }}
              </dd>
            </div>
          </dl>

          <div class="flex flex-wrap gap-2">
            <Link :href="route('vap_non_conformities.show', nc.id)" class="ds-table-action">
              <EyeIcon class="h-4 w-4" />
              Abrir
            </Link>
            <Link v-if="can.edit && !nc.deleted_at" :href="route('vap_non_conformities.edit', nc.id)" class="ds-table-action">
              <PencilSquareIcon class="h-4 w-4" />
              Editar
            </Link>
            <button type="button" class="ds-table-action ds-table-action-danger" @click="openDeleteModal(nc)">
              <TrashIcon class="h-4 w-4" />
              Eliminar
            </button>
          </div>
        </article>
      </div>

      <div v-if="nonConformityRows.length" class="hidden overflow-x-auto lg:block">
        <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
          <thead class="bg-[var(--ds-panel-subtle)]">
            <tr>
              <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.nc_number') }}</th>
              <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.title') }}</th>
              <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.status.title') }}</th>
              <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}</th>
              <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Prazos</th>
              <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
            <tr v-for="nc in nonConformityRows" :key="nc.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
              <td class="px-5 py-4 align-top">
                <p class="font-mono text-xs font-black text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">{{ nc.nc_number }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ categoryLabel(nc.category) }}</p>
              </td>
              <td class="px-5 py-4 align-top">
                <p class="font-black text-[var(--ds-text)]">{{ nc.title }}</p>
                <p class="mt-1 max-w-md truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ truncateText(nc.description, 100) }}</p>
              </td>
              <td class="px-5 py-4 align-top">
                <span :class="['inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', statusClasses[nc.status]]">
                  <span :class="['h-2 w-2 rounded-full', statusDotClasses[nc.status] || 'bg-[var(--ds-border-strong)]']"></span>
                  {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nc.status}`) }}
                </span>
              </td>
              <td class="px-5 py-4 align-top">
                <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', severityClasses[nc.severity]]">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${nc.severity}`) }}
                </span>
              </td>
              <td class="px-5 py-4 align-top">
                <p class="font-bold text-[var(--ds-text)]">{{ formatDate(nc.reported_at) }}</p>
                <p :class="['mt-1 text-xs font-black', isOverdue(nc) ? 'text-rose-700 dark:text-rose-300' : 'text-[var(--ds-text-muted)]']">
                  {{ nc.due_date ? formatDate(nc.due_date) : 'Sem prazo' }}
                </p>
              </td>
              <td class="px-5 py-4 align-top">
                <div class="flex justify-end gap-2">
                  <Link :href="route('vap_non_conformities.show', nc.id)" class="ds-table-action" :title="$t('gestlab.general.labels.vap_non_conformities.buttons.view')">
                    <EyeIcon class="h-4 w-4" />
                    <span class="sr-only">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.view') }} {{ nc.nc_number }}</span>
                  </Link>
                  <Link v-if="can.edit && !nc.deleted_at" :href="route('vap_non_conformities.edit', nc.id)" class="ds-table-action" :title="$t('gestlab.general.labels.vap_non_conformities.buttons.edit')">
                    <PencilSquareIcon class="h-4 w-4" />
                    <span class="sr-only">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit') }} {{ nc.nc_number }}</span>
                  </Link>
                  <button v-if="can.archive && !nc.deleted_at" type="button" :disabled="archive.processing.value" class="ds-table-action ds-table-action-danger" title="Arquivar" @click="openDeleteModal(nc)">
                    <TrashIcon class="h-4 w-4" />
                    <span class="sr-only">Arquivar {{ nc.nc_number }}</span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-if="!nonConformityRows.length" class="ds-empty-state p-10 text-center">
        <ExclamationTriangleIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
        <h3 class="mt-4 text-base font-black text-[var(--ds-text)]">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_title') }}
        </h3>
        <p class="mx-auto mt-2 max-w-md text-sm font-medium text-[var(--ds-text-muted)]">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_description') }}
        </p>
        <Link v-if="can.create && !filters.archived" :href="route('vap_non_conformities.create')" class="ds-button ds-button-primary mt-5">
          <PlusCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.create_first_nc') }}
        </Link>
      </div>

      <div v-if="nonConformityRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
        <Pagination :links="nonConformities.links" :from="nonConformities.from" :to="nonConformities.to" :total="nonConformities.total" :current_page="nonConformities.current_page" :last_page="nonConformities.last_page" />
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
        <p class="font-mono text-xs font-black text-[var(--ds-text-soft)]">{{ ncToDelete?.nc_number }}</p>
        <p class="mt-1 text-sm font-bold text-[var(--ds-text)]">{{ ncToDelete?.title }}</p>
      </div>
    </confirm-dialog>
  </div>
</template>

<script setup>
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ChartWrapper from '@/Components/apex-chart/ChartWrapper.vue'
import { useFileDownload } from '@/Composables/useFileDownload'
import { useRecordArchive } from '@/Composables/useRecordArchive'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import Pagination from '@/Components/Pagination.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
  Download as ArrowDownTrayIcon,
  ChartColumnBig as ChartBarSquareIcon,
  ClipboardCheck as ClipboardDocumentCheckIcon,
  Clock as ClockIcon,
  FileDown as DocumentArrowDownIcon,
  FileText as DocumentTextIcon,
  CircleAlert as ExclamationCircleIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Eye as EyeIcon,
  Funnel as FunnelIcon,
  Search as MagnifyingGlassIcon,
  SquarePen as PencilSquareIcon,
  CirclePlus as PlusCircleIcon,
  Trash2 as TrashIcon,
  X as XMarkIcon,
} from '@lucide/vue'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps({
  can: { type: Object, default: () => ({}) },
  nonConformities: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    required: true,
  },
  charts: {
    type: Object,
    default: () => ({}),
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

const downloads = useFileDownload()
const filtering = ref(false)
const filterError = ref('')
const startDate = ref(props.filters.start_date || '')
const endDate = ref(props.filters.end_date || '')
const workspaceView = ref('register')
const workspaceViews = [
  { value: 'register', label: 'Fila CAPA', icon: ClipboardDocumentCheckIcon },
  { value: 'analytics', label: 'Tendências e risco', icon: ChartBarSquareIcon },
]

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const severityFilter = ref(props.filters.severity || '')
const categoryFilter = ref(props.filters.category || '')
const archive = useRecordArchive({
  destroyUrl: ids => route('vap_non_conformities.destroy', ids[0]),
  restoreUrl: ids => route('vap_non_conformities.restore', ids[0]),
  onSuccess: () => { showDeleteModal.value = false },
})
const showDeleteModal = ref(false)
const ncToDelete = ref(null)

const statuses = ['opened', 'in_progress', 'resolved', 'closed']
const severities = ['low', 'medium', 'high', 'critical']
const categories = ['quality', 'safety', 'environmental', 'regulatory', 'other']

const statusClasses = {
  opened: 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] ring-[rgb(var(--primary-200-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.22)]',
  in_progress: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  resolved: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  closed: 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)] ring-[var(--ds-border-strong)]',
}

const statusDotClasses = {
  opened: 'bg-[rgb(var(--primary-700-rgb))]',
  in_progress: 'bg-amber-500',
  resolved: 'bg-emerald-500',
  closed: 'bg-[var(--ds-text-soft)]',
}

const severityClasses = {
  low: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  medium: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  high: 'bg-orange-50 text-orange-800 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-400/20',
  critical: 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-400/20',
}

const categoryLabels = {
  quality: 'Qualidade',
  safety: 'Segurança',
  environmental: 'Ambiental',
  regulatory: 'Regulatório',
  other: 'Outro',
}

const chartTextColor = '#6b7482'
const chartGridColor = '#dbe3ea'

const nonConformityRows = computed(() => props.nonConformities?.data || [])
const riskLoad = computed(() => Number(props.stats?.attention || 0))

const summaryCards = computed(() => [
  {
    label: 'Total',
    value: props.stats.total || 0,
    detail: 'Registos que correspondem aos filtros',
    icon: DocumentTextIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
  },
  {
    label: 'Abertas',
    value: props.stats.open || 0,
    detail: 'Aguardam investigação ou CAPA',
    icon: ExclamationTriangleIcon,
    tone: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: 'Críticas',
    value: props.stats.critical || 0,
    detail: 'Exigem contenção e prioridade',
    icon: ExclamationCircleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Vencidas',
    value: props.stats.overdue || 0,
    detail: 'Prazos de resposta ultrapassados',
    icon: ClockIcon,
    tone: 'text-orange-700 dark:text-orange-300',
  },
])

const ncTrendSeries = computed(() => props.charts?.trend?.series || [])
const ncTrendOptions = computed(() => ({
  chart: { foreColor: chartTextColor, toolbar: { show: false } },
  xaxis: { categories: props.charts?.trend?.categories || [] },
  grid: { borderColor: chartGridColor },
  stroke: { curve: 'smooth', width: 3 },
  fill: { type: 'solid', opacity: 0.12 },
  tooltip: { theme: 'light' },
}))

const severityChartSeries = computed(() => props.charts?.severity?.series || [])
const severityChartOptions = computed(() => ({
  labels: props.charts?.severity?.labels || [],
  chart: { foreColor: chartTextColor },
  legend: { position: 'bottom' },
  tooltip: { theme: 'light' },
}))

const statusChartSeries = computed(() => [{ name: 'Não conformidades', data: props.charts?.status?.series || [] }])
const statusChartOptions = computed(() => ({
  chart: { foreColor: chartTextColor, toolbar: { show: false } },
  xaxis: { categories: props.charts?.status?.labels || [] },
  plotOptions: { bar: { borderRadius: 6, columnWidth: '44%' } },
  grid: { borderColor: chartGridColor },
  tooltip: { theme: 'light' },
}))

const activeFilterPills = computed(() => {
  const pills = []

  if (search.value) {
    pills.push(`Pesquisa: ${search.value}`)
  }

  if (statusFilter.value) {
    pills.push(`Estado: ${statusFilter.value}`)
  }

  if (severityFilter.value) {
    pills.push(`Severidade: ${severityFilter.value}`)
  }

  if (categoryFilter.value) {
    pills.push(`Categoria: ${categoryLabel(categoryFilter.value)}`)
  }

  if (startDate.value) pills.push(`Desde: ${startDate.value}`)
  if (endDate.value) pills.push(`Até: ${endDate.value}`)
  return pills
})

const hasFilters = computed(() => Boolean(search.value || statusFilter.value || severityFilter.value || categoryFilter.value || startDate.value || endDate.value))

function currentFilters() {
  return {
    archived: props.filters.archived || undefined,
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    severity: severityFilter.value || undefined,
    category: categoryFilter.value || undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
  }
}

function applyFilters() {
  window.clearTimeout(filterTimeout)
  filterError.value = ''
  router.get(route('vap_non_conformities.index'), currentFilters(), {
    preserveState: true,
    preserveScroll: true,
    onStart: () => { filtering.value = true },
    onFinish: () => { filtering.value = false },
    onError: errors => { filterError.value = Object.values(errors).flat().join(' ') },
    onHttpException: () => { filterError.value = 'Não foi possível filtrar. Actualize a página e verifique a autorização.'; return false },
    onNetworkError: () => { filterError.value = 'Falha de ligação. Os filtros foram mantidos; tente novamente.'; return false },
  })
}

function clearFilters() {
  search.value = ''
  statusFilter.value = ''
  severityFilter.value = ''
  categoryFilter.value = ''
  startDate.value = ''
  endDate.value = ''
  applyFilters()
}

function truncateText(text, length) {
  if (!text) {
    return ''
  }

  return text.length > length ? `${text.substring(0, length)}...` : text
}

function formatDate(dateString) {
  if (!dateString) {
    return '--'
  }

  return new Date(dateString).toLocaleDateString('pt-PT')
}

function isOverdue(nc) {
  if (!nc.due_date || !['opened', 'in_progress'].includes(nc.status)) {
    return false
  }

  return new Date(nc.due_date) < new Date()
}

function openDeleteModal(nc) {
  ncToDelete.value = nc
  showDeleteModal.value = true
}

function closeDeleteModal() {
  if (archive.processing.value) return
  showDeleteModal.value = false
  ncToDelete.value = null
}

function deleteNc() {
  if (!ncToDelete.value) {
    return
  }

  archive.submit('delete', [ncToDelete.value.id])
}

function exportReport(type) {
  if (filtering.value) return
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(props.filters)) {
    if (value !== null && value !== undefined && value !== '') params.set(key, String(value))
  }
  const baseRoute = type === 'excel'
    ? route('vap_non_conformities.export.excel')
    : route('vap_non_conformities.export.pdf')
  return downloads.download(params.toString() ? `${baseRoute}?${params.toString()}` : baseRoute)
}

function categoryLabel(category) {
  return categoryLabels[category] || category || '--'
}

let filterTimeout
watch([search, statusFilter, severityFilter, categoryFilter, startDate, endDate], () => {
  window.clearTimeout(filterTimeout)
  filterTimeout = window.setTimeout(() => {
    applyFilters()
  }, 350)
})
onBeforeUnmount(() => window.clearTimeout(filterTimeout))
</script>
