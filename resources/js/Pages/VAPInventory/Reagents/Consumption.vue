<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Controlo de reagentes</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument" />
              Consumo rastreável
            </span>
            <span v-if="filterPeriod" class="ds-chip">{{ filterPeriod }}</span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">Relatório de consumo de reagentes</h1>
          <p class="ds-copy mt-2 text-sm">
            Acompanhe saída de reagentes por data, armazém, utilizador e lote operacional, mantendo evidência para existências, auditoria e investigação de desvios.
          </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
          <button type="button" class="ds-button ds-button-secondary" @click="exportReport">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar
          </button>
          <Link v-if="hasPermission('add_reagent_consumption')" :href="route('vap-inventory.reagents.consumption.create')" class="ds-button ds-button-primary">
            <PlusIcon class="h-4 w-4" />
            Registrar consumo
          </Link>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-3 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in summaryCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-2xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p v-if="metric.caption" class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_1fr_0.9fr]">
      <article class="ds-card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="ds-kicker">Reagente crítico</p>
            <h2 class="ds-heading mt-2 text-base">{{ stats.most_consumed_item?.reagent_name || 'Sem consumo registado' }}</h2>
          </div>
          <TrophyIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
        </div>
        <p class="mt-4 text-3xl font-bold text-rose-700 dark:text-rose-300">
          {{ formatQuantity(stats.most_consumed_item?.total_consumption) }}
        </p>
        <p class="mt-2 text-sm text-[color:var(--ds-text-soft)]">Total consumido no período filtrado.</p>
      </article>

      <article class="ds-card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="ds-kicker">Utilizador activo</p>
            <h2 class="ds-heading mt-2 text-base">{{ stats.most_active_user?.used_by || 'Sem utilizador destacado' }}</h2>
          </div>
          <UsersIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
        </div>
        <p class="mt-4 text-3xl font-bold text-[color:var(--ds-text)]">
          {{ formatQuantity(stats.most_active_user?.total_consumption) }}
        </p>
        <p class="mt-2 text-sm text-[color:var(--ds-text-soft)]">Consumo associado ao utilizador no período.</p>
      </article>

      <article class="ds-card p-5">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="ds-kicker">Pico operacional</p>
            <h2 class="ds-heading mt-2 text-base">{{ peakConsumptionDate }}</h2>
          </div>
          <ChartBarIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
        </div>
        <p class="mt-4 text-3xl font-bold text-amber-700 dark:text-amber-300">
          {{ formatQuantity(stats.peak_consumption_day?.total_consumption) }}
        </p>
        <p class="mt-2 text-sm text-[color:var(--ds-text-soft)]">Dia com maior pressão de consumo.</p>
      </article>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[color:var(--ds-border)] px-5 py-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <FunnelIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
          <div>
            <h2 class="ds-heading text-base">Filtros de consumo</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Refine por período, reagente, armazém, utilizador e texto livre.</p>
          </div>
        </div>
        <span class="ds-chip">
          <span class="lims-status-dot" :class="activeFilterCount ? 'lims-status-dot-hold' : 'lims-status-dot-release'" />
          {{ activeFilterCount }} filtros activos
        </span>
      </div>

      <div class="grid gap-4 p-5 lg:grid-cols-5">
        <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
        <BaseInput v-model="filters.date_to" type="date" label="Data final" />
        <BaseSelect v-model="filters.item_id" label="Reagente">
          <option value="">Todos os reagentes</option>
          <option v-for="item in items" :key="item.id" :value="item.id">
            {{ item.name }} ({{ item.code }})
          </option>
        </BaseSelect>
        <BaseSelect v-model="filters.warehouse_id" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
            {{ warehouse.name }}
          </option>
        </BaseSelect>
        <BaseSelect v-model="filters.user_id" label="Utilizador">
          <option value="">Todos os utilizadores</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.name }}
          </option>
        </BaseSelect>
      </div>

      <div class="grid gap-4 border-t border-[color:var(--ds-border)] px-5 py-4 lg:grid-cols-[1fr_auto]">
        <BaseInput
          v-model="filters.search"
          type="search"
          label="Pesquisa"
          placeholder="Pesquisar por reagente, utilizador ou observações"
          @keyup.enter="applyFilters"
        >
          <template #leading>
            <MagnifyingGlassIcon class="h-4 w-4" />
          </template>
        </BaseInput>

        <div class="flex items-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="filters.processing" @click="resetFilters">
            Redefinir
          </button>
          <button type="button" class="ds-button ds-button-primary" :disabled="filters.processing" @click="applyFilters">
            <FunnelIcon class="h-4 w-4" />
            Aplicar filtros
          </button>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div class="flex items-start gap-3">
            <QueueListIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Registos de consumo</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                {{ consumptions.total || 0 }} registos de consumo e reposições preservados.
              </p>
            </div>
          </div>

          <div class="flex flex-wrap items-end gap-3">
            <BaseSelect v-model="filters.sort_by" label="Ordenar por" @change="applyFilters">
              <option value="date">Data</option>
              <option value="quantity_used">Quantidade</option>
            </BaseSelect>
            <BaseSelect v-model="filters.sort_direction" label="Direcção" @change="applyFilters">
              <option value="desc">Descendente</option>
              <option value="asc">Ascendente</option>
            </BaseSelect>
          </div>
        </div>
      </div>

      <div v-if="loading" class="p-8">
        <div class="ds-empty-state p-6 text-center">
          <ArrowPathIcon class="mx-auto h-8 w-8 animate-spin text-[color:var(--ds-text-soft)]" />
          <p class="mt-3 text-sm font-semibold text-[color:var(--ds-text-soft)]">A carregar registos...</p>
        </div>
      </div>

      <div v-else-if="consumptions.data?.length" class="ds-table-shell overflow-x-auto">
        <DataTable class="min-w-[74rem]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-cell text-left">Data</th>
              <th class="ds-table-cell text-left">Reagente</th>
              <th class="ds-table-cell text-left">Categoria</th>
              <th class="ds-table-cell text-left">Armazém</th>
              <th class="ds-table-cell text-left">Quantidade</th>
              <th class="ds-table-cell text-left">Utilizador</th>
              <th class="ds-table-cell text-left">Observações</th>
              <th class="ds-table-cell text-left">Acções</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="consumption in consumptions.data" :key="consumption.id" class="ds-table-row">
              <td class="ds-table-cell align-top text-sm font-semibold text-[color:var(--ds-text)]">
                {{ formatDate(consumption.date) }}
              </td>
              <td class="ds-table-cell align-top">
                <div class="flex items-start gap-3">
                  <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)] text-primary-700 dark:text-primary-300">
                    <BeakerIcon class="h-4 w-4" />
                  </span>
                  <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-[color:var(--ds-text)]">{{ consumption.reagent_name }}</p>
                    <p class="mt-1 font-mono text-xs text-[color:var(--ds-text-soft)]">{{ consumption.item?.code || 'N/A' }}</p>
                    <span v-if="consumption.reversal" class="ds-chip mt-1">Revertido</span>
                  </div>
                </div>
              </td>
              <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text-muted)]">
                {{ consumption.item?.category?.name || 'N/A' }}
              </td>
              <td class="ds-table-cell align-top text-sm font-semibold text-[color:var(--ds-text)]">
                {{ consumption.warehouse?.name || 'N/A' }}
              </td>
              <td class="ds-table-cell align-top">
                <span class="ds-chip">
                  <span class="lims-status-dot lims-status-dot-critical" />
                  {{ formatQuantity(consumption.quantity_used) }}
                </span>
              </td>
              <td class="ds-table-cell align-top">
                <div class="flex items-center gap-2 text-sm font-semibold text-[color:var(--ds-text)]">
                  <UserIcon class="h-4 w-4 text-[color:var(--ds-text-soft)]" />
                  {{ consumption.used_by }}
                </div>
              </td>
              <td class="ds-table-cell max-w-xs align-top text-sm text-[color:var(--ds-text-muted)]">
                <span class="line-clamp-2">{{ consumption.remarks || '-' }}</span>
              </td>
              <td class="ds-table-cell align-top">
                <div class="flex flex-wrap gap-3">
                  <Link :href="route('vap-inventory.reagents.consumption.show', consumption.id)" class="ds-table-action">
                    Visualizar
                  </Link>
                  <button v-if="hasPermission('delete_reagent_consumption') && !consumption.reversal" type="button" class="ds-table-action ds-table-action-danger" :disabled="reversal.processing.value" @click="reversal.open(consumption)">
                    Reverter consumo
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="p-5">
        <div class="ds-empty-state p-6 text-center">
          <BeakerIcon class="mx-auto h-8 w-8 text-[color:var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-bold text-[color:var(--ds-text)]">Nenhum consumo encontrado</h3>
          <p class="mt-1 text-sm text-[color:var(--ds-text-soft)]">Ajuste filtros ou registe uma nova saída de reagente.</p>
        </div>
      </div>

      <div v-if="consumptions.data?.length" class="border-t border-[color:var(--ds-border)] px-5 py-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm font-semibold text-[color:var(--ds-text-soft)]">
            Mostrando {{ consumptions.from }} a {{ consumptions.to }} de {{ consumptions.total }} registos
          </p>
          <div class="flex gap-2">
            <button type="button" class="ds-button ds-button-secondary" :disabled="!consumptions.prev_page_url" @click="previousPage">
              Anterior
            </button>
            <button type="button" class="ds-button ds-button-secondary" :disabled="!consumptions.next_page_url" @click="nextPage">
              Próxima
            </button>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
      <article v-if="summaryByItem.length" class="ds-panel overflow-hidden">
        <div class="ds-table-summary px-5 py-4">
          <div class="flex items-start gap-3">
            <ChartBarIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Consumo por reagente</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Ranking de pressão por material controlado.</p>
            </div>
          </div>
        </div>

        <div class="ds-table-shell overflow-x-auto">
          <DataTable class="min-w-[38rem]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-cell text-left">Reagente</th>
                <th class="ds-table-cell text-left">Total</th>
                <th class="ds-table-cell text-left">Usos</th>
                <th class="ds-table-cell text-left">Média</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in summaryByItem" :key="item.reagent_id || item.reagent_name" class="ds-table-row">
                <td class="ds-table-cell text-sm font-bold text-[color:var(--ds-text)]">{{ item.reagent_name }}</td>
                <td class="ds-table-cell text-sm font-bold text-rose-700 dark:text-rose-300">{{ formatQuantity(item.total_consumption) }}</td>
                <td class="ds-table-cell text-sm text-[color:var(--ds-text)]">{{ formatQuantity(item.usage_count) }}</td>
                <td class="ds-table-cell text-sm text-[color:var(--ds-text-muted)]">{{ formatQuantity(item.avg_per_use) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </article>

      <article v-if="summaryByUser.length" class="ds-panel overflow-hidden">
        <div class="ds-table-summary px-5 py-4">
          <div class="flex items-start gap-3">
            <UsersIcon class="mt-0.5 h-5 w-5 text-primary-700 dark:text-primary-300" />
            <div>
              <h2 class="ds-heading text-base">Consumo por utilizador</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Responsáveis operacionais e volume associado.</p>
            </div>
          </div>
        </div>

        <div class="ds-table-shell overflow-x-auto">
          <DataTable class="min-w-[32rem]">
            <thead class="ds-table-head">
              <tr>
                <th class="ds-table-cell text-left">Utilizador</th>
                <th class="ds-table-cell text-left">Total</th>
                <th class="ds-table-cell text-left">Usos</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in summaryByUser" :key="user.used_by" class="ds-table-row">
                <td class="ds-table-cell text-sm font-bold text-[color:var(--ds-text)]">{{ user.used_by }}</td>
                <td class="ds-table-cell text-sm font-bold text-rose-700 dark:text-rose-300">{{ formatQuantity(user.total_consumption) }}</td>
                <td class="ds-table-cell text-sm text-[color:var(--ds-text)]">{{ formatQuantity(user.usage_count) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </article>
    </section>

    <confirm-dialog
      v-if="reversal.pending.value"
      title="Reverter consumo"
      description="Esta acção repõe as existências uma única vez. O consumo original e o movimento de reposição ficam preservados."
      :confirm="reversal.processing.value ? 'A reverter…' : 'Reverter consumo'"
      cancel="Manter registo"
      variant="danger"
      keep-open-on-confirm
      :disabled="reversal.processing.value"
      @confirmed="reversal.confirm"
      @canceled="reversal.close"
    >
      <div class="mt-4 rounded-lg border border-[color:var(--ds-border)] bg-[color:var(--ds-panel-subtle)] p-4 text-left">
        <p class="text-sm font-bold text-[color:var(--ds-text)]">
          {{ reversal.pending.value.reagent_name }}
        </p>
        <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
          {{ formatQuantity(reversal.pending.value.quantity_used) }} em {{ reversal.pending.value.warehouse?.name || 'armazém não definido' }}
        </p>
      </div>
      <p v-if="reversal.error.value" role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ reversal.error.value }}</p>
    </confirm-dialog>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import { useConsumptionReversal } from '@/Composables/useConsumptionReversal'
import { usePermission } from '@/Composables/usePermissions'
import { Link, router, useForm } from '@inertiajs/vue3'
import {
  Download as ArrowDownTrayIcon,
  RefreshCw as ArrowPathIcon,
  FlaskConical as BeakerIcon,
  ChartColumn as ChartBarIcon,
  Funnel as FunnelIcon,
  Search as MagnifyingGlassIcon,
  Plus as PlusIcon,
  Rows3 as QueueListIcon,
  Trophy as TrophyIcon,
  User as UserIcon,
  Users as UsersIcon,
} from '@lucide/vue'
import { computed, ref } from 'vue'

const props = defineProps({
  consumptions: {
    type: Object,
    default: () => ({ data: [], links: {} }),
  },
  summaryByItem: {
    type: Array,
    default: () => [],
  },
  summaryByUser: {
    type: Array,
    default: () => [],
  },
  items: {
    type: Array,
    default: () => [],
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  users: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
})

const loading = ref(false)
const { hasPermission } = usePermission()
const reversal = useConsumptionReversal({
  canReverse: () => hasPermission('delete_reagent_consumption'),
  reverseUrl: id => route('vap-inventory.reagents.consumption.reverse', id),
})
const quantityFormatter = new Intl.NumberFormat('pt-PT', {
  maximumFractionDigits: 4,
})

const filters = useForm({
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
  item_id: props.filters.item_id ?? '',
  warehouse_id: props.filters.warehouse_id ?? '',
  user_id: props.filters.user_id ?? '',
  search: props.filters.search ?? '',
  sort_by: props.filters.sort_by ?? 'date',
  sort_direction: props.filters.sort_direction ?? 'desc',
})

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) {
    return `${formatDate(filters.date_from)} a ${formatDate(filters.date_to)}`
  }

  if (filters.date_from) {
    return `Desde ${formatDate(filters.date_from)}`
  }

  if (filters.date_to) {
    return `Até ${formatDate(filters.date_to)}`
  }

  return ''
})

const activeFilterCount = computed(() => [
  filters.date_from,
  filters.date_to,
  filters.item_id,
  filters.warehouse_id,
  filters.user_id,
  filters.search,
].filter(Boolean).length)

const peakConsumptionDate = computed(() => {
  if (!props.stats.peak_consumption_day?.date) {
    return 'Sem pico definido'
  }

  return formatDate(props.stats.peak_consumption_day.date)
})

const summaryCards = computed(() => [
  {
    label: 'Consumo total',
    value: formatQuantity(props.stats.total_consumption),
    caption: `${formatQuantity(props.stats.total_uses)} usos`,
    dotClass: 'lims-status-dot-critical',
    valueClass: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Média diária',
    value: formatQuantity(props.stats.avg_daily_consumption),
    caption: 'Consumo por dia',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: 'Registos',
    value: formatQuantity(props.consumptions.total),
    caption: 'Entradas filtradas',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Reagentes',
    value: formatQuantity(props.summaryByItem.length),
    caption: 'Com consumo',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Utilizadores',
    value: formatQuantity(props.summaryByUser.length),
    caption: 'Com actividade',
    dotClass: 'lims-status-dot-release',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Pico diário',
    value: formatQuantity(props.stats.peak_consumption_day?.total_consumption),
    caption: peakConsumptionDate.value,
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return quantityFormatter.format(numericValue)
}

function formatDate(dateString) {
  if (!dateString) {
    return '-'
  }

  const date = new Date(dateString)

  if (Number.isNaN(date.getTime())) {
    return '-'
  }

  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(date)
}

function applyFilters() {
  filters.get(route('vap-inventory.reagents.consumption.index'), {
    preserveScroll: true,
    preserveState: true,
    onStart: () => {
      loading.value = true
    },
    onFinish: () => {
      loading.value = false
    },
  })
}

function resetFilters() {
  filters.date_from = ''
  filters.date_to = ''
  filters.item_id = ''
  filters.warehouse_id = ''
  filters.user_id = ''
  filters.search = ''
  filters.sort_by = 'date'
  filters.sort_direction = 'desc'

  applyFilters()
}

function exportReport() {
  router.post(route('vap-inventory.reports.export'), {
    report_type: 'consumption',
    format: 'pdf',
    filters: filters.data(),
  })
}

function previousPage() {
  if (props.consumptions.prev_page_url) {
    router.visit(props.consumptions.prev_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => {
        loading.value = true
      },
      onFinish: () => {
        loading.value = false
      },
    })
  }
}

function nextPage() {
  if (props.consumptions.next_page_url) {
    router.visit(props.consumptions.next_page_url, {
      preserveScroll: true,
      preserveState: true,
      onStart: () => {
        loading.value = true
      },
      onFinish: () => {
        loading.value = false
      },
    })
  }
}
</script>
