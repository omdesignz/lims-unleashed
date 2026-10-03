<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <span class="ds-chip">
            <DocumentTextIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposals.surface.commercial_management') }}
          </span>
          <span v-if="selectedTemplate" class="ds-badge ds-badge-info max-w-full truncate">
            {{ $t('gestlab.general.labels.vap_proposals.surface.template_badge', { name: selectedTemplate.name }) }}
          </span>
        </div>
        <h1 class="ds-heading mt-3 text-2xl sm:text-3xl">{{ $t('gestlab.general.labels.vap_proposals.title') }}</h1>
        <p class="ds-copy mt-1 max-w-3xl text-sm">
          {{ $t('gestlab.general.labels.vap_proposals.description') }}
          <span class="font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.vap_proposals.surface.proposals_count', { count: formatNumber(stats.total) }) }}</span>
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <Link :href="route('vap-proposals.templates.index')" class="ds-button ds-button-secondary">
          <DocumentDuplicateIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.surface.templates') }}
        </Link>
        <Link :href="route('vap-proposals.create')" class="ds-button ds-button-primary">
          <PlusCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_proposals.create_new') }}
        </Link>
      </div>
    </header>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6" :aria-label="$t('gestlab.general.labels.vap_proposals.stats.title')">
      <article v-for="stat in statCards" :key="stat.key" class="ds-card p-4">
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs font-bold text-[var(--ds-text-soft)]">{{ $t(stat.labelKey) }}</p>
            <p class="mt-2 truncate text-xl font-bold text-[var(--ds-text)]" :title="String(stat.value)">
              {{ stat.currency ? formatCurrency(stat.value) : formatNumber(stat.value) }}
            </p>
          </div>
          <span :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-lg', stat.tone]">
            <component :is="stat.icon" class="h-4 w-4" />
          </span>
        </div>
      </article>
    </section>

    <section class="ds-command-surface p-4 sm:p-5">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
        <div class="grid min-w-0 flex-1 gap-4 md:grid-cols-[minmax(15rem,1fr)_13rem_13rem]">
          <label class="ds-field-group">
            <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_proposals.filters.search') }}</span>
            <span class="relative block">
              <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
              <BaseInput
                v-model="search"
                type="search"
                :placeholder="$t('gestlab.general.labels.vap_proposals.filters.search_placeholder')"
                class="ds-field pl-10"
                @input="debouncedApplyFilters"
              />
            </span>
          </label>

          <label class="ds-field-group">
            <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_proposals.filters.status') }}</span>
            <BaseSelect v-model="statusFilter" class="ds-field">
              <option value="all">{{ $t('gestlab.general.labels.vap_proposals.filters.all_statuses') }}</option>
              <option value="PENDING">{{ $t('gestlab.general.labels.vap_proposals.status.pending') }}</option>
              <option value="SENT">{{ $t('gestlab.general.labels.vap_proposals.status.sent') }}</option>
              <option value="VIEWED">{{ $t('gestlab.general.labels.vap_proposals.status.viewed') }}</option>
              <option value="ACCEPTED">{{ $t('gestlab.general.labels.vap_proposals.status.accepted') }}</option>
              <option value="REJECTED">{{ $t('gestlab.general.labels.vap_proposals.status.rejected') }}</option>
              <option value="REVISED">{{ $t('gestlab.general.labels.vap_proposals.status.revised') }}</option>
              <option value="EXPIRED">{{ $t('gestlab.general.labels.vap_proposals.status.expired') }}</option>
            </BaseSelect>
          </label>

          <label class="ds-field-group">
            <span class="ds-field-label">{{ $t('gestlab.general.labels.vap_proposals.filters.indicators') }}</span>
            <BaseSelect v-model="period" class="ds-field">
              <option value="7">{{ $t('gestlab.general.labels.vap_proposals.chart.last_7_days') }}</option>
              <option value="30">{{ $t('gestlab.general.labels.vap_proposals.chart.last_30_days') }}</option>
              <option value="90">{{ $t('gestlab.general.labels.vap_proposals.chart.last_90_days') }}</option>
            </BaseSelect>
          </label>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <div v-if="selectedTemplate" class="flex min-h-10 items-center gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 text-xs font-semibold text-[var(--ds-text-muted)]">
            <span class="max-w-52 truncate">{{ selectedTemplate.name }}</span>
            <Link :href="route('vap-proposals.index')" class="font-bold text-primary-700 hover:text-primary-900 dark:text-primary-200">
              {{ $t('gestlab.general.labels.vap_proposals.filters.clear_template') }}
            </Link>
          </div>
          <button type="button" class="ds-button ds-button-secondary" @click="resetFilters">
            <ArrowPathIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposals.filters.clear_search') }}
          </button>
        </div>
      </div>
    </section>

    <div class="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_22rem]">
      <section class="ds-table-shell min-w-0">
        <div class="ds-table-summary px-4 py-3 sm:px-5">
          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <ListBulletIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
              <h2 class="ds-heading text-sm">{{ $t('gestlab.general.labels.vap_proposals.list.title') }}</h2>
            </div>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">
              {{ $t('gestlab.general.labels.vap_proposals.filters.proposals_count', { from: proposals.from || 0, to: proposals.to || 0, total: proposals.total || 0 }) }}
            </p>
          </div>
          <Link :href="route('vap-proposals.create')" class="ds-button ds-button-primary shrink-0">
            <PlusCircleIcon class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $t('gestlab.general.labels.vap_proposals.create_new') }}</span>
          </Link>
        </div>

        <div v-if="!proposals.data.length" class="ds-empty-state m-5 px-5 py-12 text-center">
          <DocumentTextIcon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-4 text-base">{{ $t('gestlab.general.labels.vap_proposals.empty_state.title') }}</h3>
          <p class="ds-copy mx-auto mt-1 max-w-md text-sm">{{ $t('gestlab.general.labels.vap_proposals.empty_state.description') }}</p>
          <Link :href="route('vap-proposals.create')" class="ds-button ds-button-primary mt-5">
            <PlusCircleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_proposals.create_first') }}
          </Link>
        </div>

        <div v-else class="overflow-x-auto">
          <DataTable class="ds-data-table min-w-[980px]">
            <thead>
              <tr>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.proposal_no') }}</th>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.customer') }}</th>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.department') }}</th>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.total') }}</th>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.status') }}</th>
                <th>{{ $t('gestlab.general.labels.vap_proposals.table.expiry') }}</th>
                <th class="text-right">{{ $t('gestlab.general.labels.vap_proposals.table.actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="proposal in proposals.data" :key="proposal.id">
                <td>
                  <Link :href="route('vap-proposals.show', proposal.id)" class="font-mono text-sm font-bold text-[var(--ds-text)] hover:text-primary-700 dark:hover:text-primary-200">
                    {{ proposal.proposal_number }}
                  </Link>
                  <p class="mt-1 text-xs text-[var(--ds-text-soft)]">{{ formatDate(proposal.created_at) }}</p>
                </td>
                <td>
                  <p class="font-bold text-[var(--ds-text)]">{{ proposal.customer?.name || '—' }}</p>
                  <p class="mt-1 text-xs text-[var(--ds-text-soft)]">{{ proposal.customer?.code || $t('gestlab.general.labels.vap_proposals.row.no_customer_code') }}</p>
                </td>
                <td>
                  <p>{{ proposal.department?.name || '—' }}</p>
                  <p v-if="proposal.template" class="mt-1 max-w-48 truncate text-xs text-[var(--ds-text-soft)]" :title="proposal.template.name">{{ proposal.template.name }}</p>
                </td>
                <td>
                  <p class="font-bold text-[var(--ds-text)]">{{ formatCurrency(proposal.total) }}</p>
                  <p class="mt-1 text-xs text-[var(--ds-text-soft)]">{{ proposal.items_count }} {{ proposal.items_count === 1 ? $t('gestlab.general.labels.vap_proposals.row.single_item') : $t('gestlab.general.labels.vap_proposals.row.multiple_items') }}</p>
                </td>
                <td><span :class="statusBadgeClass(proposal.status)">{{ proposal.status_badge?.text || proposal.status }}</span></td>
                <td>
                  <p class="text-[var(--ds-text)]">{{ formatDate(proposal.expiry_date) }}</p>
                  <p :class="['mt-1 text-xs font-bold', proposal.days_until_expiry <= 3 ? 'text-red-600 dark:text-red-300' : 'text-[var(--ds-text-soft)]']">
                    {{ expiryLabel(proposal.days_until_expiry) }}
                  </p>
                </td>
                <td>
                  <div class="flex justify-end gap-1">
                    <Link :href="route('vap-proposals.show', proposal.id)" class="ds-table-action px-2" :title="$t('gestlab.general.labels.vap_proposals.row.view')">
                      <EyeIcon class="h-4 w-4" />
                    </Link>
                    <Link v-if="canRevise(proposal)" :href="route('vap-proposals.edit', proposal.id)" class="ds-table-action px-2" :title="$t('gestlab.general.labels.vap_proposals.row.revise')">
                      <PencilSquareIcon class="h-4 w-4" />
                    </Link>
                    <a v-if="proposal.has_document" :href="route('vap-proposals.download.pdf', proposal.id)" class="ds-table-action px-2" :title="$t('gestlab.general.labels.vap_proposals.row.download_pdf')">
                      <ArrowDownTrayIcon class="h-4 w-4" />
                    </a>
                    <button v-if="canDelete(proposal)" type="button" class="ds-table-action ds-table-action-danger px-2" :disabled="archive.processing.value" :aria-label="$t('gestlab.general.labels.vap_proposals.row.delete')" :title="$t('gestlab.general.labels.vap_proposals.row.delete')" @click="confirmDelete(proposal)">
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-if="proposals.data.length > 0" class="border-t border-[var(--ds-border)] px-4 py-4 sm:px-5">
          <Pagination
            :links="proposals.links"
            :from="proposals.from"
            :to="proposals.to"
            :total="proposals.total"
            :current_page="proposals.current_page"
            :last_page="proposals.last_page"
          />
        </div>
      </section>

      <aside class="ds-panel h-fit overflow-hidden">
        <div class="flex items-center justify-between border-b border-[var(--ds-border)] px-4 py-3">
          <div class="flex items-center gap-2">
            <ChartBarIcon class="h-4 w-4 text-primary-700 dark:text-primary-200" />
            <h2 class="ds-heading text-sm">{{ $t('gestlab.general.labels.vap_proposals.chart.title') }}</h2>
          </div>
          <span class="ds-badge ds-badge-neutral">{{ period }}d</span>
        </div>
        <div class="h-72 p-3">
          <apexchart v-if="chartData.length" type="area" height="100%" :options="chartOptions" :series="chartData" />
          <div v-else class="flex h-full items-center justify-center p-5 text-center">
            <p class="ds-copy text-sm">{{ $t('gestlab.general.labels.vap_proposals.chart.empty') }}</p>
          </div>
        </div>
      </aside>
    </div>

    <ArchiveMutationFeedback :processing="archive.processing.value" :message="archive.message.value" :failed="archive.failed.value" @refresh="router.reload()" />
    <ConfirmationModal :show="showDeleteModal" :closeable="!archive.processing.value" @close="showDeleteModal = false">
      <template #title>{{ $t('gestlab.general.labels.vap_proposals.delete.title') }}</template>
      <template #content>
        <div class="space-y-3 text-sm font-medium text-[var(--ds-text-muted)]">
          <p>{{ $t('gestlab.general.labels.vap_proposals.delete.message', { number: selectedProposal?.proposal_number }) }}</p>
          <p class="font-bold text-red-600 dark:text-red-300">{{ $t('gestlab.general.labels.vap_proposals.delete.warning') }}</p>
        </div>
      </template>
      <template #footer>
        <button type="button" class="ds-button ds-button-secondary" :disabled="archive.processing.value" @click="showDeleteModal = false">Cancelar</button>
        <button type="button" class="ds-button ds-button-danger" :disabled="archive.processing.value || !selectedProposal" @click="deleteProposal">
          {{ archive.processing.value ? 'A arquivar…' : 'Arquivar proposta' }}
        </button>
      </template>
    </ConfirmationModal>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ArrowPathIcon,
  BanknotesIcon,
  ChartBarIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentDuplicateIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  ListBulletIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  PlusCircleIcon,
  TrashIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import ArchiveMutationFeedback from '@/Components/archive-mutation-feedback.vue'
import { useRecordArchive } from '@/Composables/useRecordArchive'

const props = defineProps({
  proposals: {
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
  chartSeries: {
    type: Array,
    default: () => [],
  },
  selectedTemplate: {
    type: Object,
    default: null,
  },
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const period = ref(String(props.filters.period || 30))
const showDeleteModal = ref(false)
const selectedProposal = ref(null)
const archive = useRecordArchive({
  destroyUrl: ids => route('vap-proposals.destroy', ids[0]),
  onSuccess: () => {
    showDeleteModal.value = false
    selectedProposal.value = null
  },
})
const isDark = ref(false)

let darkModeObserver = null

const statCards = computed(() => [
  { key: 'total', labelKey: 'gestlab.general.labels.vap_proposals.stats.total', value: props.stats.total, icon: DocumentTextIcon, tone: 'bg-primary-50 text-primary-800 dark:bg-primary-500/10 dark:text-primary-100' },
  { key: 'pending', labelKey: 'gestlab.general.labels.vap_proposals.stats.pending', value: props.stats.pending, icon: ClockIcon, tone: 'bg-amber-50 text-amber-800 dark:bg-amber-400/10 dark:text-amber-200' },
  { key: 'accepted', labelKey: 'gestlab.general.labels.vap_proposals.stats.accepted', value: props.stats.accepted, icon: CheckCircleIcon, tone: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-400/10 dark:text-emerald-200' },
  { key: 'rejected', labelKey: 'gestlab.general.labels.vap_proposals.stats.rejected', value: props.stats.rejected, icon: XCircleIcon, tone: 'bg-red-50 text-red-800 dark:bg-red-400/10 dark:text-red-200' },
  { key: 'expired', labelKey: 'gestlab.general.labels.vap_proposals.stats.expired', value: props.stats.expired, icon: ExclamationTriangleIcon, tone: 'bg-orange-50 text-orange-800 dark:bg-orange-400/10 dark:text-orange-200' },
  { key: 'total_value', labelKey: 'gestlab.general.labels.vap_proposals.stats.accepted_value', value: props.stats.total_value, icon: BanknotesIcon, currency: true, tone: 'bg-indigo-50 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-100' },
])

const chartData = computed(() => props.chartSeries || [])

const chartOptions = computed(() => ({
  chart: {
    type: 'area',
    height: 350,
    toolbar: { show: false },
    zoom: { enabled: false },
    foreColor: isDark.value ? '#cbd5e1' : '#64748b',
  },
  colors: ['#0f766e', '#4f46e5'],
  dataLabels: { enabled: false },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 0.35,
      opacityFrom: isDark.value ? 0.28 : 0.34,
      opacityTo: 0.02,
      stops: [0, 90, 100],
    },
  },
  grid: {
    borderColor: isDark.value ? 'rgba(255,255,255,0.1)' : '#e2e8f0',
    strokeDashArray: 5,
  },
  markers: {
    size: 4,
    strokeWidth: 2,
    strokeColors: isDark.value ? '#0f172a' : '#ffffff',
  },
  stroke: {
    curve: 'smooth',
    width: 3,
  },
  tooltip: {
    theme: isDark.value ? 'dark' : 'light',
    x: { format: 'dd MMM yyyy' },
  },
  xaxis: {
    type: 'datetime',
    labels: {
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontWeight: 700,
      },
    },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: {
        colors: isDark.value ? '#94a3b8' : '#64748b',
        fontWeight: 700,
      },
    },
  },
}))

const applyFilters = () => {
  router.get(route('vap-proposals.index'), {
    search: search.value || undefined,
    status: statusFilter.value,
    template_id: props.selectedTemplate?.id || undefined,
    period: period.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

const debouncedApplyFilters = debounce(applyFilters, 400)

watch([statusFilter, period], () => {
  applyFilters()
})

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
  darkModeObserver = new MutationObserver(() => {
    isDark.value = document.documentElement.classList.contains('dark')
  })
  darkModeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

onBeforeUnmount(() => {
  darkModeObserver?.disconnect()
})

const resetFilters = () => {
  search.value = ''
  statusFilter.value = 'all'
  period.value = '30'
  applyFilters()
}

const formatNumber = (value) => new Intl.NumberFormat('pt-AO').format(Number(value || 0))

const formatDate = (date) => {
  if (!date) {
    return '—'
  }

  return new Intl.DateTimeFormat('pt-AO', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date))
}

const expiryLabel = (days) => {
  if (days === null || days === undefined) {
    return trans('gestlab.general.labels.vap_proposals.expiry.undefined')
  }

  if (days < 0) {
    const overdueDays = Math.abs(days)
    return trans('gestlab.general.labels.vap_proposals.expiry.overdue', {
      days: overdueDays,
      unit: overdueDays === 1
        ? trans('gestlab.general.labels.vap_proposals.expiry.one_day')
        : trans('gestlab.general.labels.vap_proposals.expiry.days'),
    })
  }

  return `${days} ${days === 1 ? trans('gestlab.general.labels.vap_proposals.expiry.one_day_left') : trans('gestlab.general.labels.vap_proposals.expiry.days_left')}`
}

const formatCurrency = (amount) => new Intl.NumberFormat('pt-AO', {
  style: 'currency',
  currency: 'AOA',
}).format(Number(amount || 0))

const statusBadgeClass = (status) => {
  const classes = {
    PENDING: 'ds-badge ds-badge-warning',
    SENT: 'ds-badge ds-badge-info',
    VIEWED: 'ds-badge ds-badge-info',
    ACCEPTED: 'ds-badge ds-badge-success',
    REJECTED: 'ds-badge ds-badge-danger',
    REVISED: 'ds-badge ds-badge-warning',
    EXPIRED: 'ds-badge ds-badge-neutral',
  }

  return classes[status] || classes.PENDING
}

const canRevise = (proposal) => proposal.can_revise === true

const canDelete = (proposal) => proposal.can_archive === true

const confirmDelete = (proposal) => {
  if (archive.processing.value) return
  selectedProposal.value = proposal
  showDeleteModal.value = true
}

const deleteProposal = () => {
  if (!selectedProposal.value || archive.processing.value) return
  showDeleteModal.value = false
  archive.submit('delete', [selectedProposal.value.id])
}
</script>
