<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Stock assurance</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-hold"></span>
              Fila de reabastecimento
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Relatório de stock baixo</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Priorize ruturas, gaps de reposição e exposição por armazém antes de abrir pedidos de compra.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="exportReport">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar PDF
          </button>
          <Link :href="route('vap-inventory.items.index')" class="ds-button ds-button-primary">
            <ArrowLeftIcon class="h-4 w-4" />
            Voltar ao inventário
          </Link>
        </div>
      </div>

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
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseSelect v-model="filters.warehouse_id" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.category_id" label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.severity" label="Severidade">
          <option value="">Todos os níveis</option>
          <option value="critical">Crítico / sem stock</option>
          <option value="low">Abaixo do ponto de reposição</option>
        </BaseSelect>

        <BaseSelect v-model="filters.sort_by" label="Ordenar por">
          <option value="severity">Severidade</option>
          <option value="current_stock">Stock atual</option>
          <option value="reorder_point">Ponto de reposição</option>
          <option value="item_name">Nome do item</option>
        </BaseSelect>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div>
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ inventory.total || inventory.data?.length || 0 }} registos na fila</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">A mostrar toda a exposição de stock baixo.</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary" :disabled="!hasActiveFilters" @click="clearFilters">
          <FunnelIcon class="h-4 w-4" />
          Limpar filtros
        </button>
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Análise de risco</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Severidade, exposição e gap de reposição</h2>
        </div>
        <span class="ds-chip">{{ severityMixTotal }} itens em atenção</span>
      </div>

      <div class="grid gap-4 p-4 xl:grid-cols-[1.15fr_0.85fr]">
        <article class="ds-card p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Severidade da fila</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Itens sem stock, críticos e abaixo do ponto de reposição.</p>
            </div>
            <span class="text-xl font-black text-[var(--ds-text)]">{{ severityMixTotal }}</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="severityMixChartOptions" :series="severityMixChartSeries" />
          </div>
        </article>

        <div class="grid gap-4">
          <article class="ds-card p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Exposição por armazém</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Concentração da pressão de reabastecimento.</p>
              </div>
              <span class="ds-chip">{{ warehouseExposureTotal }} locais</span>
            </div>
            <div class="mt-4 min-h-64">
              <apexchart type="donut" height="256" :options="warehouseExposureChartOptions" :series="warehouseExposureChartSeries" />
            </div>
          </article>

          <article class="ds-card p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Gap de reabastecimento</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Maiores distâncias até ao nível desejado.</p>
              </div>
              <ChartBarSquareIcon class="h-5 w-5 text-amber-600 dark:text-amber-300" />
            </div>
            <div class="mt-4 min-h-56">
              <apexchart type="bar" height="224" :options="replenishmentGapChartOptions" :series="replenishmentGapChartSeries" />
            </div>
          </article>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Fila de reabastecimento</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Itens com stock baixo</h2>
          </div>
          <button type="button" class="ds-button ds-button-primary" :disabled="!recommendedOrders.length" @click="generateOrder">
            <ShoppingCartIcon class="h-4 w-4" />
            Criar pedido
          </button>
        </div>

        <div v-if="inventoryRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="item in inventoryRows" :key="`mobile-${item.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ item.item?.internal_code || item.item?.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ item.item?.name || 'Item sem identificação' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ item.warehouse?.name || 'Sem armazém' }}</p>
              </div>
              <span class="inline-flex items-center gap-2 text-xs font-black" :class="statusTextClass(item)">
                <span :class="['h-2 w-2 rounded-full', statusDotClass(item)]"></span>
                {{ statusText(item) }}
              </span>
            </div>

            <dl class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Atual</dt>
                <dd class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ item.qty_available }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Mínimo</dt>
                <dd class="mt-2 text-lg font-black text-[var(--ds-text)]">{{ item.min_stock_level }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Gap</dt>
                <dd class="mt-2 text-lg font-black text-rose-700 dark:text-rose-300">{{ reorderGap(item) }}</dd>
              </div>
            </dl>

            <div class="h-1.5 overflow-hidden rounded-full bg-[var(--ds-border)]">
              <div :class="['h-full rounded-full', stockBarClass(item)]" :style="{ width: `${stockPercentage(item)}%` }"></div>
            </div>

            <div class="flex flex-wrap gap-2">
              <Link :href="route('vap-inventory.items.show', item.item_id)" class="ds-table-action">
                <EyeIcon class="h-4 w-4" />
                Abrir
              </Link>
              <Link :href="route('vap-inventory.items.edit', item.item_id)" class="ds-table-action">
                <PencilSquareIcon class="h-4 w-4" />
                Ajustar
              </Link>
              <button type="button" class="ds-table-action" @click="createOrderForItem(item)">
                <ShoppingCartIcon class="h-4 w-4" />
                Comprar
              </button>
            </div>
          </article>
        </div>

        <div v-if="inventoryRows.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Item</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Postura de stock</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Estado</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="item in inventoryRows" :key="item.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top">
                  <p class="font-black text-[var(--ds-text)]">{{ item.item?.name || 'Item sem identificação' }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.item?.internal_code || item.item?.code || 'Sem código' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ item.item?.category?.name || 'Sem categoria' }}</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-bold text-[var(--ds-text)]">{{ item.warehouse?.name || 'N/D' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ item.warehouse?.location?.name || 'Sem localização' }}</p>
                </td>
                <td class="min-w-64 px-5 py-4 align-top">
                  <div class="grid grid-cols-3 gap-3">
                    <div><p class="text-xs font-bold text-[var(--ds-text-soft)]">Atual</p><p class="mt-1 font-black text-[var(--ds-text)]">{{ item.qty_available }}</p></div>
                    <div><p class="text-xs font-bold text-[var(--ds-text-soft)]">Reposição</p><p class="mt-1 font-black text-[var(--ds-text)]">{{ item.reorder_point }}</p></div>
                    <div><p class="text-xs font-bold text-[var(--ds-text-soft)]">Gap</p><p class="mt-1 font-black text-rose-700 dark:text-rose-300">{{ reorderGap(item) }}</p></div>
                  </div>
                  <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-[var(--ds-border)]">
                    <div :class="['h-full rounded-full', stockBarClass(item)]" :style="{ width: `${stockPercentage(item)}%` }"></div>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <span class="inline-flex items-center gap-2 text-xs font-black" :class="statusTextClass(item)">
                    <span :class="['h-2 w-2 rounded-full', statusDotClass(item)]"></span>
                    {{ statusText(item) }}
                  </span>
                  <p v-if="item.item?.needs_calibration" class="mt-2 text-xs font-bold text-violet-700 dark:text-violet-300">Calibração necessária</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <div class="flex justify-end gap-2">
                    <Link :href="route('vap-inventory.items.show', item.item_id)" class="ds-table-action" title="Abrir item">
                      <EyeIcon class="h-4 w-4" />
                      <span class="sr-only">Abrir {{ item.item?.name }}</span>
                    </Link>
                    <Link :href="route('vap-inventory.items.edit', item.item_id)" class="ds-table-action" title="Ajustar stock">
                      <PencilSquareIcon class="h-4 w-4" />
                      <span class="sr-only">Ajustar {{ item.item?.name }}</span>
                    </Link>
                    <button type="button" class="ds-table-action" title="Criar pedido" @click="createOrderForItem(item)">
                      <ShoppingCartIcon class="h-4 w-4" />
                      <span class="sr-only">Criar pedido para {{ item.item?.name }}</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <div v-if="!inventoryRows.length" class="ds-empty-state p-10 text-center">
          <CheckCircleIcon class="mx-auto h-9 w-9 text-emerald-600 dark:text-emerald-300" />
          <h3 class="mt-4 text-base font-black text-[var(--ds-text)]">Sem itens com stock baixo</h3>
          <p class="mx-auto mt-2 max-w-md text-sm font-medium text-[var(--ds-text-muted)]">Nenhuma rutura ou nível abaixo do ponto de reposição foi encontrado para os filtros atuais.</p>
        </div>

        <div v-if="inventoryRows.length" class="border-t border-[var(--ds-border)] px-5 py-4">
          <Pagination :links="inventory.links" />
        </div>
      </section>

      <aside class="space-y-5">
        <section class="ds-card p-5">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Recomendação de compra</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Reposição sugerida</h2>
            </div>
            <ShoppingCartIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
          </div>

          <dl class="mt-5 divide-y divide-[var(--ds-border)] border-y border-[var(--ds-border)]">
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Itens sugeridos</dt>
              <dd class="text-lg font-black text-[var(--ds-text)]">{{ recommendedOrders.length }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Unidades</dt>
              <dd class="text-lg font-black text-[var(--ds-text)]">{{ recommendedUnitTotal }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 py-3">
              <dt class="text-sm font-semibold text-[var(--ds-text-muted)]">Valor estimado</dt>
              <dd class="text-right text-sm font-black text-[var(--ds-text)]">{{ formatMoney(recommendedValueTotal) }}</dd>
            </div>
          </dl>

          <button type="button" class="ds-button ds-button-primary mt-5 w-full" :disabled="!recommendedOrders.length" @click="generateOrder">
            <ShoppingCartIcon class="h-4 w-4" />
            Criar pedido consolidado
          </button>
        </section>

        <section class="ds-card overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Itens prioritários</p>
          </div>
          <ul v-if="topRecommendedOrders.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="item in topRecommendedOrders" :key="item.id" class="p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ item.name }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.code || 'Sem código' }}</p>
                </div>
                <span class="ds-chip shrink-0">+{{ item.recommended_qty }}</span>
              </div>
              <p class="mt-2 text-xs font-semibold text-[var(--ds-text-muted)]">{{ item.supplier?.name || 'Fornecedor por definir' }}</p>
              <p class="mt-1 text-xs font-black text-[var(--ds-text)]">{{ formatMoney(item.recommended_qty * item.unit_price) }}</p>
            </li>
          </ul>
          <div v-else class="ds-empty-state p-5 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem recomendações.</div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ChartBarSquareIcon,
  CheckCircleIcon,
  CubeIcon,
  ExclamationCircleIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  PencilSquareIcon,
  ShoppingCartIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  inventory: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  warehouses: {
    type: Array,
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const filters = reactive({
  warehouse_id: props.filters?.warehouse_id ?? '',
  category_id: props.filters?.category_id ?? '',
  severity: props.filters?.severity ?? '',
  sort_by: props.filters?.sort_by ?? 'severity',
})

const isDarkMode = ref(false)
let themeObserver

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const inventoryRows = computed(() => props.inventory?.data || [])

const summaryCards = computed(() => [
  {
    label: 'Stock crítico',
    value: props.stats?.critical_stock || 0,
    detail: 'No mínimo ou abaixo dele',
    icon: ExclamationTriangleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Stock baixo',
    value: Math.max(Number(props.stats?.total_low_stock || 0) - Number(props.stats?.critical_stock || 0), 0),
    detail: 'Abaixo do ponto de reposição',
    icon: ExclamationCircleIcon,
    tone: 'text-amber-600 dark:text-amber-300',
  },
  {
    label: 'Sem stock',
    value: props.stats?.out_of_stock || 0,
    detail: 'Rutura confirmada',
    icon: XCircleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Itens monitorizados',
    value: props.stats?.total_items || 0,
    detail: 'Base do inventário filtrado',
    icon: CubeIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
])

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.severity) pills.push(`Severidade: ${filters.severity === 'critical' ? 'Crítico' : 'Baixo'}`)
  if (filters.sort_by !== 'severity') pills.push(`Ordem: ${sortLabel(filters.sort_by)}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)

const severityMixChartSeries = computed(() => [{
  name: 'Itens',
  data: props.charts?.severity_mix?.series || [],
}])

const severityMixTotal = computed(() => (props.charts?.severity_mix?.series || []).reduce((total, value) => total + Number(value || 0), 0))
const warehouseExposureChartSeries = computed(() => props.charts?.warehouse_exposure?.series || [])
const warehouseExposureTotal = computed(() => props.charts?.warehouse_exposure?.labels?.length || 0)
const replenishmentGapChartSeries = computed(() => props.charts?.replenishment_gap?.series || [])

const recommendedOrders = computed(() => inventoryRows.value.map((item) => {
  const recommendedQuantity = Math.max(
    Number(item.reorder_point || 0) * 2 - Number(item.qty_available || 0),
    Number(item.min_stock_level || 0) * 3 - Number(item.qty_available || 0),
    1,
  )

  return {
    id: item.item_id,
    name: item.item?.name,
    code: item.item?.code || item.item?.internal_code,
    current_stock: Number(item.qty_available || 0),
    recommended_qty: recommendedQuantity,
    supplier: item.item?.supplier,
    unit_price: Number(item.unit_price || item.item?.unit_price || item.item?.purchase_price || 0),
  }
}))

const topRecommendedOrders = computed(() => recommendedOrders.value.slice(0, 8))
const recommendedUnitTotal = computed(() => recommendedOrders.value.reduce((total, item) => total + item.recommended_qty, 0))
const recommendedValueTotal = computed(() => recommendedOrders.value.reduce((total, item) => total + (item.recommended_qty * item.unit_price), 0))

const severityMixChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#be123c'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: {
    categories: props.charts?.severity_mix?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const warehouseExposureChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.warehouse_exposure?.labels || [],
  colors: ['#d97706', '#be123c', '#0e7490', '#7c3aed', '#047857', '#475569'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const replenishmentGapChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0e7490'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '48%' } },
  xaxis: {
    categories: props.charts?.replenishment_gap?.labels || [],
    labels: { rotate: -25, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function statusText(item) {
  if (Number(item.qty_available) <= 0) return 'Sem stock'
  if (Number(item.qty_available) <= Number(item.min_stock_level)) return 'Crítico'
  return 'Baixo'
}

function statusDotClass(item) {
  return statusText(item) === 'Baixo' ? 'bg-amber-500' : 'bg-rose-600'
}

function statusTextClass(item) {
  return statusText(item) === 'Baixo'
    ? 'text-amber-800 dark:text-amber-200'
    : 'text-rose-800 dark:text-rose-200'
}

function stockBarClass(item) {
  return statusText(item) === 'Baixo' ? 'bg-amber-500' : 'bg-rose-600'
}

function stockPercentage(item) {
  const maximum = Math.max(Number(item.reorder_point || 0) * 2, Number(item.min_stock_level || 0) * 3, Number(item.qty_available || 0), 1)
  return Math.min(Math.max((Number(item.qty_available || 0) / maximum) * 100, 0), 100)
}

function reorderGap(item) {
  return Math.max(Number(item.reorder_point || 0) - Number(item.qty_available || 0), 0)
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function sortLabel(sortBy) {
  return {
    current_stock: 'Stock atual',
    reorder_point: 'Ponto de reposição',
    item_name: 'Nome do item',
  }[sortBy] || 'Severidade'
}

function formatMoney(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

function clearFilters() {
  Object.assign(filters, {
    warehouse_id: '',
    category_id: '',
    severity: '',
    sort_by: 'severity',
  })
}

function exportReport() {
  router.post(route('vap-inventory.reports.export'), {
    report_type: 'low_stock',
    format: 'pdf',
    filters: { ...filters },
  })
}

function generateOrder() {
  if (!recommendedOrders.value.length) return
  router.visit(route('vap-inventory.orders.create', {
    items: recommendedOrders.value.map((item) => ({ item_id: item.id, qty: item.recommended_qty })),
  }))
}

function createOrderForItem(item) {
  router.visit(route('vap-inventory.orders.create', {
    items: [{
      item_id: item.item_id,
      qty: Math.max(Number(item.reorder_point || 0) * 2 - Number(item.qty_available || 0), 1),
    }],
  }))
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.low-stock'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350),
  { deep: true },
)

onMounted(() => {
  syncDarkMode()
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
