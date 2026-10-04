<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import Pagination from '@/Components/pagination.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import InventoryReportExportButton from '@/Components/vap-inventory/InventoryReportExportButton.vue'
import { useConsumptionReversal } from '@/Composables/useConsumptionReversal'
import { usePermission } from '@/Composables/usePermissions'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Eye as EyeIcon, Undo2 as ArrowUturnLeftIcon, X as XMarkIcon } from '@lucide/vue'
import { computed, ref } from 'vue'

/**
 * Reagent consumption register (Plano queue). A consumption is never deleted: a
 * reversal keeps the original record and adds a compensating stock movement, so
 * reversed rows stay listed with their evidence.
 */
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

const lede = computed(() => {
  const records = Number(props.consumptions.total || 0)
  const scope = filterPeriod.value ? ` (${filterPeriod.value})` : ''

  return records
    ? `${formatQuantity(records)} ${records === 1 ? 'registo' : 'registos'}${scope}: ${formatQuantity(props.stats.total_consumption)} consumidos em ${formatQuantity(props.stats.total_uses)} usos. Reversões preservam o consumo original.`
    : `Nenhum consumo registado${scope}. Reversões preservam o consumo original.`
})

const highlights = computed(() => [
  ['Média diária', formatQuantity(props.stats.avg_daily_consumption)],
  ['Pico diário', props.stats.peak_consumption_day?.date
    ? `${formatQuantity(props.stats.peak_consumption_day.total_consumption)} · ${formatDate(props.stats.peak_consumption_day.date)}`
    : 'Sem pico definido'],
  ['Mais consumido', props.stats.most_consumed_item?.reagent_name
    ? `${props.stats.most_consumed_item.reagent_name} · ${formatQuantity(props.stats.most_consumed_item.total_consumption)}`
    : 'Sem consumo registado'],
  ['Mais activo', props.stats.most_active_user?.used_by
    ? `${props.stats.most_active_user.used_by} · ${formatQuantity(props.stats.most_active_user.total_consumption)}`
    : 'Sem utilizador destacado'],
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

</script>

<template>
  <div class="pl-page" data-template="queue">
    <Head title="Consumo de reagentes" />
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Consumo de reagentes' }]"
      title="Consumo de reagentes"
      :lede="lede"
    >
      <template #actions>
        <InventoryReportExportButton report-type="consumption" :filters="filters.data()" />
        <Link v-if="hasPermission('add_reagent_consumption')" :href="route('vap-inventory.reagents.consumption.create')" class="ds-button ds-button-primary">Registar consumo</Link>
      </template>
    </PageHeader>

    <dl class="pl-panel pl-facts pl-facts-2 mb-10" aria-label="Indicadores do período">
      <div v-for="[label, value] in highlights" :key="label" class="pl-fact"><dt>{{ label }}</dt><dd class="pl-num">{{ value }}</dd></div>
    </dl>

    <form class="pl-filter" role="search" @submit.prevent="applyFilters">
      <label for="consumption-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="consumption-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" placeholder="reagente, utilizador ou observações" />
      <div class="w-40"><BaseInput v-model="filters.date_from" type="date" aria-label="Data inicial" placeholder="Desde" /></div>
      <div class="w-40"><BaseInput v-model="filters.date_to" type="date" aria-label="Data final" placeholder="Até" /></div>
      <div class="w-48">
        <BaseSelect v-model="filters.item_id" aria-label="Reagente">
          <option value="">Todos os reagentes</option>
          <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }} ({{ item.code }})</option>
        </BaseSelect>
      </div>
      <div class="w-44">
        <BaseSelect v-model="filters.warehouse_id" aria-label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-44">
        <BaseSelect v-model="filters.user_id" aria-label="Utilizador">
          <option value="">Todos os utilizadores</option>
          <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
        </BaseSelect>
      </div>
      <div class="w-36">
        <BaseSelect v-model="filters.sort_by" aria-label="Ordenar por" @change="applyFilters">
          <option value="date">Data</option>
          <option value="quantity_used">Quantidade</option>
        </BaseSelect>
      </div>
      <div class="w-36">
        <BaseSelect v-model="filters.sort_direction" aria-label="Direcção" @change="applyFilters">
          <option value="desc">Descendente</option>
          <option value="asc">Ascendente</option>
        </BaseSelect>
      </div>
      <button type="submit" class="ds-button ds-button-quiet" :disabled="filters.processing">{{ filters.processing ? 'A procurar…' : 'Aplicar' }}</button>
      <button v-if="activeFilterCount" type="button" class="ds-chip" :disabled="filters.processing" @click="resetFilters">Limpar filtros <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
    </form>

    <section class="pl-panel" aria-label="Registos de consumo" :aria-busy="loading">
      <DataTable v-if="consumptions.data?.length">
        <thead>
          <tr>
            <th scope="col">Data</th>
            <th scope="col">Reagente</th>
            <th scope="col">Categoria</th>
            <th scope="col">Armazém</th>
            <th scope="col" class="text-right">Quantidade</th>
            <th scope="col">Utilizador</th>
            <th scope="col">Observações</th>
            <th scope="col"><span class="sr-only">Acções</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="consumption in consumptions.data" :key="consumption.id">
            <td class="pl-num">{{ formatDate(consumption.date) }}</td>
            <td>
              <Link :href="route('vap-inventory.reagents.consumption.show', consumption.id)" class="font-medium hover:text-[var(--pl-accent-text)]">{{ consumption.reagent_name }}</Link>
              <span class="block pl-num text-[12.5px] text-[var(--pl-muted)]">{{ consumption.item?.code || 'N/A' }}</span>
              <StatusChip v-if="consumption.reversal" tone="done" class="mt-1">Revertido</StatusChip>
            </td>
            <td class="text-[var(--pl-muted)]">{{ consumption.item?.category?.name || 'N/A' }}</td>
            <td>{{ consumption.warehouse?.name || 'N/A' }}</td>
            <td class="pl-num text-right">{{ formatQuantity(consumption.quantity_used) }}</td>
            <td>{{ consumption.used_by }}</td>
            <td class="max-w-xs text-[var(--pl-muted)]"><span class="line-clamp-2">{{ consumption.remarks || '—' }}</span></td>
            <td class="text-right">
              <div class="flex justify-end gap-1">
                <Link :href="route('vap-inventory.reagents.consumption.show', consumption.id)" class="ds-table-action" :aria-label="`Ver consumo de ${consumption.reagent_name}`">
                  <EyeIcon class="h-4 w-4" aria-hidden="true" />
                </Link>
                <button v-if="hasPermission('delete_reagent_consumption') && !consumption.reversal" type="button" class="ds-table-action ds-table-action-danger" :disabled="reversal.processing.value" :aria-label="`Reverter consumo de ${consumption.reagent_name}`" @click="reversal.open(consumption)">
                  <ArrowUturnLeftIcon class="h-4 w-4" aria-hidden="true" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>
      <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
        <span class="pl-k">{{ activeFilterCount ? 'Nenhum consumo neste filtro' : 'Ainda não há consumos' }}</span>
        <p class="text-sm text-[var(--pl-muted)]">{{ activeFilterCount ? 'Ajuste o período, o reagente ou o armazém.' : 'Registe uma saída de reagente para começar o histórico.' }}</p>
      </div>
      <Pagination
        v-if="consumptions.data?.length"
        :links="consumptions.links"
        :total="consumptions.total"
        :from="consumptions.from"
        :to="consumptions.to"
        :last_page="consumptions.last_page"
        :current_page="consumptions.current_page"
      />
    </section>

    <div v-if="summaryByItem.length || summaryByUser.length" class="mt-10 grid gap-7 xl:grid-cols-2">
      <section v-if="summaryByItem.length" class="pl-panel" aria-labelledby="consumption-by-item-title">
        <div class="pl-panel-head"><h2 id="consumption-by-item-title" class="pl-k">Consumo por reagente</h2><span class="pl-k pl-faint">Sem reversões</span></div>
        <DataTable>
          <thead>
            <tr>
              <th scope="col">Reagente</th>
              <th scope="col" class="text-right">Total</th>
              <th scope="col" class="text-right">Usos</th>
              <th scope="col" class="text-right">Média</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in summaryByItem" :key="item.reagent_id || item.reagent_name">
              <td>{{ item.reagent_name }}</td>
              <td class="pl-num text-right">{{ formatQuantity(item.total_consumption) }}</td>
              <td class="pl-num text-right">{{ formatQuantity(item.usage_count) }}</td>
              <td class="pl-num text-right">{{ formatQuantity(item.avg_per_use) }}</td>
            </tr>
          </tbody>
        </DataTable>
      </section>

      <section v-if="summaryByUser.length" class="pl-panel" aria-labelledby="consumption-by-user-title">
        <div class="pl-panel-head"><h2 id="consumption-by-user-title" class="pl-k">Consumo por utilizador</h2><span class="pl-k pl-faint">Sem reversões</span></div>
        <DataTable>
          <thead>
            <tr>
              <th scope="col">Utilizador</th>
              <th scope="col" class="text-right">Total</th>
              <th scope="col" class="text-right">Usos</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in summaryByUser" :key="user.used_by">
              <td>{{ user.used_by }}</td>
              <td class="pl-num text-right">{{ formatQuantity(user.total_consumption) }}</td>
              <td class="pl-num text-right">{{ formatQuantity(user.usage_count) }}</td>
            </tr>
          </tbody>
        </DataTable>
      </section>
    </div>

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
      <dl class="pl-panel pl-facts mt-4 text-left">
        <div class="pl-fact"><dt>Reagente</dt><dd>{{ reversal.pending.value.reagent_name }}</dd></div>
        <div class="pl-fact"><dt>Quantidade</dt><dd class="pl-num">{{ formatQuantity(reversal.pending.value.quantity_used) }}</dd></div>
        <div class="pl-fact"><dt>Armazém</dt><dd>{{ reversal.pending.value.warehouse?.name || 'armazém não definido' }}</dd></div>
      </dl>
      <p v-if="reversal.error.value" role="alert" class="ds-field-error mt-3">{{ reversal.error.value }}</p>
    </confirm-dialog>
  </div>
</template>
