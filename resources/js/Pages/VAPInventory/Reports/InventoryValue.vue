<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Inventory control</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument"></span>
              Valorização estimada
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <BanknotesIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Valor do inventário</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Acompanhe a concentração financeira por item, categoria e armazém para apoiar controlo e reconciliação.
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
            <div class="min-w-0">
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ card.label }}</p>
              <p class="mt-3 truncate text-2xl font-black text-[var(--ds-text)]">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5 shrink-0', card.tone]" />
          </div>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <BaseSelect v-model="filters.category_id" label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.warehouse_id" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>

        <BaseInput v-model="filters.search" label="Pesquisar item" placeholder="Nome ou código">
          <template #leading><MagnifyingGlassIcon class="h-4 w-4" /></template>
        </BaseInput>

        <BaseSelect v-model="filters.sort_direction" label="Ordenação por quantidade">
          <option value="desc">Maior primeiro</option>
          <option value="asc">Menor primeiro</option>
        </BaseSelect>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div>
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ inventory.total || inventoryRows.length }} posições de stock valorizadas</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">A mostrar todo o inventário com quantidade disponível.</p>
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
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Concentração financeira</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Distribuição do valor estimado</h2>
        </div>
        <span class="ds-chip">Base: {{ formatCurrency(valuationUnitPrice) }} / unidade</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] xl:grid-cols-[1.15fr_0.85fr] xl:divide-x xl:divide-y-0">
        <article class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Valor por categoria</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Famílias com maior capital estimado em stock.</p>
            </div>
            <span class="text-xl font-black text-[var(--ds-text)]">{{ categoryValueTotal }}</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="categoryValueChartOptions" :series="categoryValueChartSeries" />
          </div>
        </article>

        <div class="grid divide-y divide-[var(--ds-border)]">
          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Exposição por armazém</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Distribuição do valor entre locais ativos.</p>
              </div>
              <span class="ds-chip">{{ warehouseValueTotal }} locais</span>
            </div>
            <div class="mt-4 min-h-64">
              <apexchart type="donut" height="256" :options="warehouseValueChartOptions" :series="warehouseValueChartSeries" />
            </div>
          </article>

          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Itens de maior valor</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Posições que mais pesam no saldo estimado.</p>
              </div>
              <ArrowTrendingUpIcon class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
            </div>
            <div class="mt-4 min-h-56">
              <apexchart type="bar" height="224" :options="topItemValueChartOptions" :series="topItemValueChartSeries" />
            </div>
          </article>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Livro de valorização</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Posições de inventário</h2>
          </div>
          <span class="ds-chip">{{ inventory.total || inventoryRows.length }} registos</span>
        </div>

        <div v-if="loading" class="ds-empty-state m-5 p-8 text-center">
          <span class="mx-auto block h-7 w-7 animate-spin rounded-full border-2 border-[var(--ds-border)] border-t-[rgb(var(--primary-700-rgb))]"></span>
          <p class="mt-3 text-sm font-semibold text-[var(--ds-text-muted)]">A atualizar o relatório...</p>
        </div>

        <div v-else-if="inventoryRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="position in inventoryRows" :key="`mobile-${position.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ position.item?.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ position.item?.name || 'Item sem identificação' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ position.warehouse?.name || 'Sem armazém' }}</p>
              </div>
              <span class="ds-chip shrink-0">{{ position.qty_available }} un.</span>
            </div>
            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Valor unitário</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ formatCurrency(valuationUnitPrice) }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Valor da posição</dt>
                <dd class="mt-2 text-sm font-black text-emerald-700 dark:text-emerald-300">{{ positionValue(position) }}</dd>
              </div>
            </dl>
            <Link :href="route('vap-inventory.items.show', position.item_id)" class="ds-table-action">
              <EyeIcon class="h-4 w-4" />
              Abrir item
            </Link>
          </article>
        </div>

        <div v-else-if="!loading" class="ds-empty-state m-5 p-8 text-center">
          <BanknotesIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem posições valorizadas</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste os filtros ou confirme a existência de stock disponível.</p>
        </div>

        <div v-if="!loading && inventoryRows.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Item</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Categoria</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Valor estimado</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Ação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="position in inventoryRows" :key="position.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top">
                  <p class="font-black text-[var(--ds-text)]">{{ position.item?.name || 'Item sem identificação' }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ position.item?.code || 'Sem código' }}</p>
                </td>
                <td class="px-5 py-4 align-top font-semibold text-[var(--ds-text-muted)]">{{ position.item?.category?.name || 'Sem categoria' }}</td>
                <td class="px-5 py-4 align-top">
                  <p class="font-bold text-[var(--ds-text)]">{{ position.warehouse?.name || 'N/D' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ position.warehouse?.location?.name || 'Sem localização' }}</p>
                </td>
                <td class="px-5 py-4 text-right align-top font-mono font-black tabular-nums text-[var(--ds-text)]">{{ position.qty_available }}</td>
                <td class="px-5 py-4 text-right align-top">
                  <p class="font-black tabular-nums text-emerald-700 dark:text-emerald-300">{{ positionValue(position) }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ formatCurrency(valuationUnitPrice) }} / un.</p>
                </td>
                <td class="px-5 py-4 text-right align-top">
                  <Link :href="route('vap-inventory.items.show', position.item_id)" class="ds-icon-button ml-auto" title="Abrir item">
                    <EyeIcon class="h-4 w-4" />
                    <span class="sr-only">Abrir item</span>
                  </Link>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <Pagination
          v-if="inventoryRows.length"
          :links="inventory.links"
          :total="inventory.total"
          :from="inventory.from"
          :to="inventory.to"
          :last_page="inventory.last_page"
          :current_page="inventory.current_page"
        />
      </section>

      <aside class="space-y-6">
        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-300" />
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Base de cálculo</p>
              <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Estimativa técnica</h2>
              <p class="mt-2 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
                O valor atual usa uma referência fixa de {{ formatCurrency(valuationUnitPrice) }} por unidade. Use-o para exposição operacional, não para fecho contabilístico.
              </p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Maior exposição</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Categorias</h2>
          </div>
          <ol v-if="summaryByCategory.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="(category, index) in summaryByCategory.slice(0, 6)" :key="category.category_name || index" class="flex items-center gap-3 px-5 py-3">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-xs font-black text-[var(--ds-text-soft)]">{{ index + 1 }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ category.category_name || 'Sem categoria' }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ category.unique_items }} itens · {{ category.total_quantity }} un.</p>
              </div>
              <span class="shrink-0 text-xs font-black tabular-nums text-[var(--ds-text)]">{{ formatCurrency(category.total_value) }}</span>
            </li>
          </ol>
          <div v-else class="ds-empty-state m-4 p-4 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem dados por categoria.</div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Top posições</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Itens de maior valor</h2>
          </div>
          <ol v-if="topValuableItems.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="item in topValuableItems.slice(0, 6)" :key="item.item_id" class="px-5 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ item.item?.name || `Item #${item.item_id}` }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.item?.code || 'Sem código' }}</p>
                </div>
                <span class="shrink-0 text-xs font-black tabular-nums text-emerald-700 dark:text-emerald-300">{{ formatCurrency(item.total_value) }}</span>
              </div>
            </li>
          </ol>
          <div v-else class="ds-empty-state m-4 p-4 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem posições valorizadas.</div>
        </section>
      </aside>
    </div>

    <section class="grid gap-6 xl:grid-cols-2">
      <div class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Reconciliação</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Resumo por categoria</h2>
          </div>
          <RectangleStackIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Categoria</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Itens</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Valor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="category in summaryByCategory" :key="category.category_name" class="hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-3 font-black text-[var(--ds-text)]">{{ category.category_name || 'Sem categoria' }}</td>
                <td class="px-5 py-3 text-right font-semibold tabular-nums text-[var(--ds-text-muted)]">{{ category.unique_items }}</td>
                <td class="px-5 py-3 text-right font-black tabular-nums text-[var(--ds-text)]">{{ formatCurrency(category.total_value) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </div>

      <div class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Reconciliação</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Resumo por armazém</h2>
          </div>
          <BuildingStorefrontIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Itens</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Valor</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="warehouse in summaryByWarehouse" :key="warehouse.warehouse_name" class="hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-3 font-black text-[var(--ds-text)]">{{ warehouse.warehouse_name || 'Sem armazém' }}</td>
                <td class="px-5 py-3 text-right font-semibold tabular-nums text-[var(--ds-text-muted)]">{{ warehouse.unique_items }}</td>
                <td class="px-5 py-3 text-right font-black tabular-nums text-[var(--ds-text)]">{{ formatCurrency(warehouse.total_value) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowTrendingUpIcon,
  BanknotesIcon,
  BuildingStorefrontIcon,
  CalculatorIcon,
  CubeIcon,
  EyeIcon,
  FunnelIcon,
  InformationCircleIcon,
  MagnifyingGlassIcon,
  RectangleStackIcon,
  TrophyIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  inventory: { type: Object, default: () => ({ data: [] }) },
  summaryByCategory: { type: Array, default: () => [] },
  summaryByWarehouse: { type: Array, default: () => [] },
  topValuableItems: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  filters: { type: Object, default: () => ({}) },
  charts: { type: Object, default: () => ({}) },
  stats: { type: Object, default: () => ({}) },
})

const valuationUnitPrice = 100
const loading = ref(false)
const isDarkMode = ref(false)
let themeObserver

const filters = reactive({
  category_id: props.filters?.category_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: 'qty_available',
  sort_direction: props.filters?.sort_direction ?? 'desc',
})

const inventoryRows = computed(() => props.inventory?.data || [])
const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const summaryCards = computed(() => [
  {
    label: 'Valor estimado',
    value: formatCurrency(props.stats?.total_value),
    detail: 'Saldo financeiro de referência',
    icon: BanknotesIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Itens únicos',
    value: props.stats?.unique_items || 0,
    detail: 'Referências com stock',
    icon: CubeIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Valor médio',
    value: formatCurrency(props.stats?.avg_item_value),
    detail: 'Por referência única',
    icon: CalculatorIcon,
    tone: 'text-violet-700 dark:text-violet-300',
  },
  {
    label: 'Maior categoria',
    value: props.stats?.highest_value_category?.category_name || 'Sem dados',
    detail: formatCurrency(props.stats?.highest_value_category?.total_value),
    icon: TrophyIcon,
    tone: 'text-amber-700 dark:text-amber-300',
  },
])

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const categoryValueChartSeries = computed(() => props.charts?.category_value_breakdown?.series || [])
const categoryValueTotal = computed(() => props.charts?.category_value_breakdown?.labels?.length || 0)
const warehouseValueChartSeries = computed(() => (props.charts?.warehouse_value_breakdown?.series?.[0]?.data || []).map((value) => Number(value || 0)))
const warehouseValueTotal = computed(() => props.charts?.warehouse_value_breakdown?.labels?.length || 0)
const topItemValueChartSeries = computed(() => props.charts?.top_item_value?.series || [])

const categoryValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0f766e'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: {
    categories: props.charts?.category_value_breakdown?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { formatter: (value) => compactCurrency(value), style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { maxWidth: 220, style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
  legend: { show: false },
}))

const warehouseValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.warehouse_value_breakdown?.labels || [],
  colors: ['#0e7490', '#0f766e', '#7c3aed', '#d97706', '#be123c', '#475569'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
}))

const topItemValueChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#1d4ed8'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, columnWidth: '48%' } },
  xaxis: {
    categories: props.charts?.top_item_value?.labels || [],
    labels: { rotate: -25, trim: true, style: { colors: chartTextColor.value } },
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
  },
  yaxis: { labels: { formatter: compactCurrency, style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value, y: { formatter: formatCurrency } },
  legend: { show: false },
}))

function syncDarkMode() {
  if (typeof document === 'undefined') return
  isDarkMode.value = document.documentElement.classList.contains('dark')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

function compactCurrency(value) {
  return new Intl.NumberFormat('pt-AO', {
    style: 'currency',
    currency: 'AOA',
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(Number(value || 0))
}

function positionValue(position) {
  return formatCurrency(Number(position.qty_available || 0) * valuationUnitPrice)
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function clearFilters() {
  Object.assign(filters, {
    category_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'qty_available',
    sort_direction: 'desc',
  })
}

function exportReport() {
  router.post(route('vap-inventory.reports.export'), {
    report_type: 'inventory_value',
    format: 'pdf',
    filters: { ...filters },
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.reports.inventory-value'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      onStart: () => { loading.value = true },
      onFinish: () => { loading.value = false },
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
