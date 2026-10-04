<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Relatórios' }, { title: 'Consumo' }]"
      title="Consumo de reagentes"
      :lede="lede"
    >
      <template #actions>
        <InventoryReportExportButton report-type="consumption" :filters="filters" />
        <Link :href="route('vap-inventory.reagents.consumption.index')" class="ds-button ds-button-quiet">Registo operacional</Link>
      </template>
    </PageHeader>

    <form class="pl-filter" role="search" aria-label="Filtrar consumo" @submit.prevent>
      <label for="consumption-search" class="pl-filter-prompt">Filtro://</label>
      <BaseInput id="consumption-search" v-model="filters.search" type="search" data-bare class="pl-filter-input" maxlength="100" placeholder="reagente, utilizador ou observação" />
      <span v-if="loading" class="pl-k pl-faint" role="status">A actualizar…</span>
      <button v-if="hasActiveFilters" type="button" class="ds-chip" @click="clearFilters">
        Limpar filtros
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>
    </form>
    <div class="grid gap-4 border border-b-0 border-[var(--pl-line)] p-4 md:grid-cols-2 xl:grid-cols-4">
      <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
      <BaseInput v-model="filters.date_to" type="date" label="Data final" />
      <div class="ds-field-group xl:col-span-2">
        <span class="ds-field-label">Reagente</span>
        <ComboboxEnhanced
          :model-value="selectedItem"
          :options="itemOptions"
          placeholder="Pesquisar reagente por nome ou código"
          @update:model-value="selectItem"
        />
      </div>
      <BaseSelect v-model="filters.warehouse_id" label="Armazém">
        <option value="">Todos os armazéns</option>
        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
      </BaseSelect>
      <BaseSelect v-model="filters.user_id" label="Utilizador">
        <option value="">Todos os utilizadores</option>
        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }}</option>
      </BaseSelect>
      <BaseSelect v-model="filters.sort_by" label="Ordenar por">
        <option value="date">Data</option>
        <option value="quantity_used">Quantidade</option>
      </BaseSelect>
      <BaseSelect v-model="filters.sort_direction" label="Direcção">
        <option value="desc">Descendente</option>
        <option value="asc">Ascendente</option>
      </BaseSelect>
    </div>

    <dl class="pl-panel pl-facts pl-facts-2 mb-8" aria-label="Resumo do consumo">
      <div v-for="card in summaryCards" :key="card.label" class="pl-fact">
        <dt>{{ card.label }}</dt>
        <dd>
          <span class="pl-num font-medium">{{ card.value }}</span>
          <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ card.detail }}</span>
        </dd>
      </div>
    </dl>

    <div class="mb-8 grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0 xl:row-span-2" aria-labelledby="consumption-items-chart">
        <div class="pl-panel-head">
          <h2 id="consumption-items-chart" class="pl-k">Reagentes mais consumidos</h2>
          <span class="pl-k pl-faint">{{ itemConsumptionTotal }} reagentes</span>
        </div>
        <div class="min-h-72 p-4">
          <PlanoChart kind="bar" :label="singleItem ? 'Quantidade consumida' : 'Registos de consumo por reagente'" :categories="(singleItem ? charts?.item_consumption?.labels : charts?.item_records?.labels) || []" :series="(singleItem ? charts?.item_consumption?.series : charts?.item_records?.series) || []" :format="singleItem ? 'decimal' : 'count'" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="consumption-users-chart">
        <div class="pl-panel-head">
          <h2 id="consumption-users-chart" class="pl-k">Consumo por utilizador</h2>
          <span class="pl-k pl-faint">{{ userConsumptionTotal }} utilizadores</span>
        </div>
        <div class="min-h-64 p-4">
          <PlanoChart kind="donut" label="Registos de consumo por utilizador" :categories="charts?.user_records?.labels || []" :series="[{ name: 'Registos', data: charts?.user_records?.series || [] }]" />
        </div>
      </section>
      <section class="pl-panel min-w-0" aria-labelledby="consumption-daily-chart">
        <div class="pl-panel-head">
          <h2 id="consumption-daily-chart" class="pl-k">Ritmo diário</h2>
          <span class="pl-k pl-faint">{{ filterPeriod || 'Período completo' }}</span>
        </div>
        <div class="min-h-56 p-4">
          <PlanoChart :kind="singleItem ? 'area' : 'column'" :label="singleItem ? 'Quantidade consumida por dia' : 'Registos de consumo por dia'" :categories="((singleItem ? charts?.daily_consumption?.labels : charts?.daily_records?.labels) || []).map(dayLabel)" :series="(singleItem ? charts?.daily_consumption?.series : charts?.daily_records?.series) || []" :format="singleItem ? 'decimal' : 'count'" :height="224" />
        </div>
      </section>
    </div>

    <div class="mb-8 grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="pl-panel min-w-0" aria-labelledby="consumption-events" :aria-busy="loading">
        <div class="pl-panel-head">
          <h2 id="consumption-events" class="pl-k">Eventos de consumo</h2>
          <span class="pl-k pl-faint">{{ consumptions.total || consumptionRows.length }} registos</span>
        </div>
        <div v-if="loading" class="ds-empty-state m-4 grid justify-items-start gap-2 p-6" role="status">
          <span class="pl-k">A actualizar o relatório…</span>
        </div>
        <DataTable v-else-if="consumptionRows.length">
          <thead>
            <tr>
              <th scope="col">Data</th>
              <th scope="col">Reagente</th>
              <th scope="col">Armazém</th>
              <th scope="col" class="text-right">Quantidade</th>
              <th scope="col">Utilizador</th>
              <th scope="col">Observação</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="event in consumptionRows" :key="event.id">
              <td class="pl-num">{{ formatDate(event.date) }}</td>
              <td>
                <span class="font-medium">{{ event.reagent_name || event.item?.name || 'Reagente não identificado' }}</span>
                <span class="pl-num block text-[12px] text-[var(--pl-muted)]">{{ event.item?.code || 'Sem código' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ event.item?.category?.name || 'Sem categoria' }}</span>
              </td>
              <td>{{ event.warehouse?.name || 'N/D' }}</td>
              <td class="pl-num text-right">{{ formatQuantity(event.quantity_used) }}</td>
              <td>{{ event.used_by || event.user?.name || 'N/D' }}</td>
              <td class="max-w-xs text-[12.5px] text-[var(--pl-muted)]">{{ event.remarks || 'Sem observações.' }}</td>
            </tr>
          </tbody>
        </DataTable>
        <div v-else class="ds-empty-state m-4 grid justify-items-start gap-2 p-6">
          <span class="pl-k">Sem consumo no período</span>
          <p class="text-sm text-[var(--pl-muted)]">Ajuste os filtros para consultar outro conjunto de registos.</p>
        </div>
        <Pagination
          v-if="consumptionRows.length"
          :links="consumptions.links"
          :total="consumptions.total"
          :from="consumptions.from"
          :to="consumptions.to"
          :last_page="consumptions.last_page"
          :current_page="consumptions.current_page"
        />
      </section>

      <aside class="grid content-start gap-6">
        <section class="pl-panel" aria-labelledby="consumption-top-items">
          <div class="pl-panel-head">
            <h2 id="consumption-top-items" class="pl-k">Maior consumo</h2>
            <span class="pl-k pl-faint">Reagentes</span>
          </div>
          <ol v-if="summaryByItem.length">
            <li v-for="(item, index) in summaryByItem.slice(0, 7)" :key="item.reagent_id || index" class="pl-row">
              <span class="flex min-w-0 items-center gap-3">
                <span class="pl-num pl-faint w-5 shrink-0 text-[12px]">{{ String(index + 1).padStart(2, '0') }}</span>
                <span class="min-w-0">
                  <span class="block truncate font-medium">{{ item.reagent_name || 'Sem reagente' }}</span>
                  <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ item.usage_count }} eventos · média {{ formatQuantity(item.avg_per_use) }}</span>
                </span>
              </span>
              <span class="pl-num text-[12.5px]">{{ formatQuantity(item.total_consumption) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por reagente.</p>
        </section>

        <section class="pl-panel" aria-labelledby="consumption-top-users">
          <div class="pl-panel-head">
            <h2 id="consumption-top-users" class="pl-k">Utilizadores mais activos</h2>
          </div>
          <ol v-if="summaryByUser.length">
            <li v-for="user in summaryByUser.slice(0, 7)" :key="user.used_by" class="pl-row">
              <span class="min-w-0">
                <span class="block truncate font-medium">{{ user.used_by || 'Sem utilizador' }}</span>
                <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ user.usage_count }} eventos</span>
              </span>
              <span class="pl-num text-[12.5px]">{{ formatQuantity(user.total_consumption) }}</span>
            </li>
          </ol>
          <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por utilizador.</p>
        </section>
      </aside>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <section class="pl-panel min-w-0" aria-labelledby="consumption-by-item">
        <div class="pl-panel-head">
          <h2 id="consumption-by-item" class="pl-k">Consumo por reagente</h2>
          <span class="pl-k pl-faint">{{ summaryByItem.length }} reagentes</span>
        </div>
        <DataTable v-if="summaryByItem.length">
          <thead>
            <tr>
              <th scope="col">Reagente</th>
              <th scope="col" class="text-right">Eventos</th>
              <th scope="col" class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in summaryByItem" :key="item.reagent_id">
              <td class="font-medium">{{ item.reagent_name || 'Sem reagente' }}</td>
              <td class="pl-num text-right">{{ item.usage_count }}</td>
              <td class="pl-num text-right">{{ formatQuantity(item.total_consumption) }}</td>
            </tr>
          </tbody>
        </DataTable>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por reagente.</p>
      </section>

      <section class="pl-panel min-w-0" aria-labelledby="consumption-by-user">
        <div class="pl-panel-head">
          <h2 id="consumption-by-user" class="pl-k">Consumo por utilizador</h2>
          <span class="pl-k pl-faint">{{ summaryByUser.length }} utilizadores</span>
        </div>
        <DataTable v-if="summaryByUser.length">
          <thead>
            <tr>
              <th scope="col">Utilizador</th>
              <th scope="col" class="text-right">Eventos</th>
              <th scope="col" class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in summaryByUser" :key="user.used_by">
              <td class="font-medium">{{ user.used_by || 'Sem utilizador' }}</td>
              <td class="pl-num text-right">{{ user.usage_count }}</td>
              <td class="pl-num text-right">{{ formatQuantity(user.total_consumption) }}</td>
            </tr>
          </tbody>
        </DataTable>
        <p v-else class="px-4 py-3 text-sm text-[var(--pl-muted)]">Sem dados por utilizador.</p>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Pagination from '@/Components/pagination.vue'
import InventoryReportExportButton from '@/Components/vap-inventory/InventoryReportExportButton.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import PlanoChart from '@/Components/plano/PlanoChart.vue'
import { X as XMarkIcon } from '@lucide/vue'

const props = defineProps({
  consumptions: { type: Object, default: () => ({ data: [] }) },
  summaryByItem: { type: Array, default: () => [] },
  summaryByDate: { type: Array, default: () => [] },
  summaryByUser: { type: Array, default: () => [] },
  items: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  users: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
})

const loading = ref(false)

const filters = reactive({
  date_from: props.filters?.date_from ?? '',
  date_to: props.filters?.date_to ?? '',
  item_id: props.filters?.item_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  user_id: props.filters?.user_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: ['date', 'quantity_used'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'date',
  sort_direction: props.filters?.sort_direction === 'asc' ? 'asc' : 'desc',
})

const itemOptions = computed(() => props.items.map((item) => ({
  value: item.id,
  label: `${item.name}${item.code ? ` · ${item.code}` : ''}`,
})))
const selectedItem = ref(itemOptions.value.find((option) => String(option.value) === String(filters.item_id)) || null)
const consumptionRows = computed(() => props.consumptions?.data || [])

const summaryCards = computed(() => [
  {
    label: 'Consumo total',
    value: formatQuantity(props.stats?.total_consumption),
    detail: 'Volume acumulado',
  },
  {
    label: 'Eventos',
    value: formatQuantity(props.stats?.total_uses),
    detail: 'Registos de utilização',
  },
  {
    label: 'Média diária',
    value: formatQuantity(props.stats?.avg_daily_consumption),
    detail: 'Ritmo médio do período',
  },
  {
    label: 'Reagente principal',
    value: props.stats?.most_consumed_item?.reagent_name || 'Sem dados',
    detail: formatQuantity(props.stats?.most_consumed_item?.total_consumption),
  },
  {
    label: 'Pico de utilização',
    value: peakDayLabel.value,
    detail: `${formatQuantity(props.stats?.peak_consumption_day?.total_consumption)} em ${props.stats?.peak_consumption_day?.usage_count || 0} eventos`,
  },
])

const lede = computed(() => {
  const events = Number(props.consumptions?.total ?? consumptionRows.value.length)

  return events
    ? `${formatQuantity(events)} ${events === 1 ? 'evento' : 'eventos'} de consumo neste âmbito. Volume, responsáveis e ritmo diário por reagente e armazém.`
    : 'Nenhum evento de consumo neste âmbito. Ajuste o período, o reagente ou o armazém.'
})

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) return `${formatDate(filters.date_from)} - ${formatDate(filters.date_to)}`
  if (filters.date_from) return `Desde ${formatDate(filters.date_from)}`
  if (filters.date_to) return `Até ${formatDate(filters.date_to)}`
  return ''
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filterPeriod.value) pills.push(filterPeriod.value)
  if (filters.item_id) pills.push(`Reagente: ${selectedItem.value?.label || 'Seleccionado'}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.user_id) pills.push(`Utilizador: ${userName(filters.user_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
// Quantities only add up within one reagent; across reagents the charts count records.
const singleItem = computed(() => Boolean(props.filters?.item_id))
const dayLabel = (date) => (/^\d{4}-\d{2}-\d{2}/.test(String(date)) ? `${String(date).slice(8, 10)}/${String(date).slice(5, 7)}` : date)
const itemConsumptionTotal = computed(() => props.charts?.item_consumption?.labels?.length || 0)
const userConsumptionTotal = computed(() => props.charts?.user_consumption?.labels?.length || 0)
const peakDayLabel = computed(() => props.stats?.peak_consumption_day?.date ? formatDate(props.stats.peak_consumption_day.date) : 'Sem dados')

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatQuantity(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function userName(id) {
  return props.users.find((user) => String(user.id) === String(id))?.name || 'N/D'
}

function selectItem(option) {
  selectedItem.value = option
  filters.item_id = option?.value ?? ''
}

function clearFilters() {
  selectedItem.value = null
  Object.assign(filters, {
    date_from: '',
    date_to: '',
    item_id: '',
    warehouse_id: '',
    user_id: '',
    search: '',
    sort_by: 'date',
    sort_direction: 'desc',
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.consumption'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onStart: () => { loading.value = true },
      onFinish: () => { loading.value = false },
    })
  }, 350),
  { deep: true },
)

</script>
