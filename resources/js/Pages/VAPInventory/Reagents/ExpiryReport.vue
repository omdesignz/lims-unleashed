<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Reagent lifecycle</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-hold"></span>
              FEFO · First expiry, first out
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <CalendarDaysIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Controlo de validade de reagentes</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Priorize consumo, segregação e substituição com rastreabilidade de validade, lote, fornecedor e posição de stock.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" class="ds-button ds-button-secondary" :disabled="exporting" @click="exportReport">
            <ArrowPathIcon v-if="exporting" class="h-4 w-4 animate-spin" />
            <ArrowDownTrayIcon v-else class="h-4 w-4" />
            {{ exporting ? 'A preparar...' : 'Exportar PDF' }}
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
              <p class="mt-3 truncate text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ card.value }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5 shrink-0', card.tone]" />
          </div>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <BaseSelect v-model="filters.status" label="Estado de validade">
          <option value="">Todos os estados</option>
          <option value="expired">Expirado</option>
          <option value="expiring_soon">Até 60 dias</option>
          <option value="good">Mais de 60 dias</option>
        </BaseSelect>

        <BaseSelect v-model="filters.category_id" label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.warehouse_id" label="Armazém">
          <option value="">Todos os armazéns</option>
          <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
        </BaseSelect>

        <BaseInput v-model="filters.search" label="Pesquisar reagente" placeholder="Nome, código ou lote">
          <template #leading><MagnifyingGlassIcon class="h-4 w-4" /></template>
        </BaseInput>

        <div class="grid grid-cols-2 gap-3">
          <BaseSelect v-model="filters.sort_by" label="Ordenar por">
            <option value="expiry_date">Validade</option>
            <option value="name">Nome</option>
            <option value="current_stock">Stock</option>
          </BaseSelect>
          <BaseSelect v-model="filters.sort_direction" label="Direção">
            <option value="asc">Ascendente</option>
            <option value="desc">Descendente</option>
          </BaseSelect>
        </div>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ reagents.total || reagentRows.length }} reagentes no âmbito</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Ordenação FEFO aplicada a todos os reagentes com validade definida.</p>
        </div>
        <button type="button" class="ds-button ds-button-secondary" :disabled="!hasActiveFilters" @click="clearFilters">
          <FunnelIcon class="h-4 w-4" />
          Limpar filtros
        </button>
      </div>

      <div v-if="exportError" class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
        {{ exportError }}
      </div>
    </section>

    <section class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">FEFO control windows</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Carga de revisão nos próximos 90 dias</h2>
        </div>
        <span class="ds-chip">{{ expiryWindowTotal }} ocorrências</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <article v-for="window in expiryWindows" :key="window.label" class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ window.label }}</p>
              <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ window.count }}</p>
            </div>
            <span :class="['h-2.5 w-2.5 rounded-full', window.dot]"></span>
          </div>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ window.detail }}</p>
          <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
            <div :class="['h-full rounded-full', window.bar]" :style="{ width: `${window.percentage}%` }"></div>
          </div>
          <p class="mt-2 text-right font-mono text-xs font-black tabular-nums text-[var(--ds-text-soft)]">{{ formatNumber(window.percentage) }}%</p>
        </article>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Reagent expiry ledger</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Reagentes por prioridade FEFO</h2>
          </div>
          <span class="ds-chip">{{ reagents.total || reagentRows.length }} registos</span>
        </div>

        <div v-if="loading" class="ds-empty-state m-5 p-8 text-center">
          <span class="mx-auto block h-7 w-7 animate-spin rounded-full border-2 border-[var(--ds-border)] border-t-[rgb(var(--primary-700-rgb))]"></span>
          <p class="mt-3 text-sm font-semibold text-[var(--ds-text-muted)]">A atualizar o controlo de validade...</p>
        </div>

        <div v-else-if="reagentRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="reagent in reagentRows" :key="`mobile-${reagent.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ reagent.internal_code || reagent.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ reagent.name }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ reagent.category?.name || 'Sem categoria' }}</p>
              </div>
              <span :class="['ds-chip shrink-0', statusChipClass(reagent)]">{{ statusLabel(reagent) }}</span>
            </div>

            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Validade</dt>
                <dd :class="['mt-2 text-sm font-black', statusTextClass(reagent)]">{{ formatDate(reagent.reagent_expiry_date) }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Stock total</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ formatNumber(reagent.total_stock) }} un.</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Lote</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ reagent.lot || 'N/D' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Posições</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ reagent.warehouse_count || 0 }}</dd>
              </div>
            </dl>

            <div>
              <div class="flex items-center justify-between gap-3 text-xs font-bold">
                <span class="text-[var(--ds-text-muted)]">Vida útil desde abertura</span>
                <span class="text-[var(--ds-text)]">{{ formatNumber(shelfLifePercentage(reagent)) }}%</span>
              </div>
              <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
                <div :class="['h-full rounded-full', shelfLifeBarClass(reagent)]" :style="{ width: `${shelfLifePercentage(reagent)}%` }"></div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <Link :href="route('vap-inventory.items.show', reagent.id)" class="ds-table-action">
                <EyeIcon class="h-4 w-4" />
                Abrir dossiê
              </Link>
              <Link :href="route('vap-inventory.items.edit', reagent.id)" class="ds-table-action">
                <PencilSquareIcon class="h-4 w-4" />
                Rever dados
              </Link>
            </div>
          </article>
        </div>

        <div v-else-if="!loading" class="ds-empty-state m-5 p-8 text-center">
          <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem reagentes no âmbito</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste os filtros ou confirme que as datas de validade foram registadas.</p>
        </div>

        <div v-if="!loading && reagentRows.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Reagente</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Validade</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Lote e fornecedor</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Posições de stock</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Estado</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="reagent in reagentRows" :key="reagent.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top">
                  <div class="flex items-start gap-3">
                    <span :class="['grid h-9 w-9 shrink-0 place-items-center rounded-lg', statusIconSurface(reagent)]">
                      <BeakerIcon class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                      <p class="font-black text-[var(--ds-text)]">{{ reagent.name }}</p>
                      <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ reagent.internal_code || reagent.code || 'Sem código' }}</p>
                      <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ reagent.category?.name || 'Sem categoria' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <p :class="['font-mono text-sm font-black tabular-nums', statusTextClass(reagent)]">{{ formatDate(reagent.reagent_expiry_date) }}</p>
                  <p :class="['mt-1 text-xs font-black', statusTextClass(reagent)]">{{ daysLabel(reagent) }}</p>
                  <div v-if="reagent.reagent_open_date" class="mt-3 min-w-36">
                    <div class="flex items-center justify-between gap-3 text-xs font-semibold text-[var(--ds-text-muted)]">
                      <span>Aberto {{ formatDate(reagent.reagent_open_date) }}</span>
                      <span>{{ formatNumber(shelfLifePercentage(reagent)) }}%</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
                      <div :class="['h-full rounded-full', shelfLifeBarClass(reagent)]" :style="{ width: `${shelfLifePercentage(reagent)}%` }"></div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-mono text-xs font-black text-[var(--ds-text)]">{{ reagent.lot || 'Sem lote' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ reagent.supplier?.name || 'Sem fornecedor' }}</p>
                  <span v-if="reagent.refrigerated" class="mt-2 inline-flex items-center gap-1 text-xs font-black text-cyan-800 dark:text-cyan-200">
                    <CubeTransparentIcon class="h-3.5 w-3.5" />
                    Cadeia de frio
                  </span>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-mono text-sm font-black tabular-nums text-[var(--ds-text)]">{{ formatNumber(reagent.total_stock) }} un.</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ reagent.warehouse_count || 0 }} posições</p>
                  <p class="mt-1 max-w-52 text-xs font-semibold leading-5 text-[var(--ds-text-soft)]">{{ warehouseNames(reagent) }}</p>
                </td>
                <td class="px-5 py-4 align-top">
                  <span :class="['ds-chip', statusChipClass(reagent)]">{{ statusLabel(reagent) }}</span>
                  <p class="mt-2 max-w-44 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ statusInstruction(reagent) }}</p>
                </td>
                <td class="px-5 py-4 text-right align-top">
                  <div class="flex justify-end gap-1">
                    <Link :href="route('vap-inventory.items.show', reagent.id)" class="ds-icon-button" title="Abrir dossiê">
                      <EyeIcon class="h-4 w-4" />
                    </Link>
                    <Link :href="route('vap-inventory.items.edit', reagent.id)" class="ds-icon-button" title="Rever dados de validade">
                      <PencilSquareIcon class="h-4 w-4" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </DataTable>
        </div>

        <Pagination
          v-if="reagentRows.length"
          :links="reagents.links"
          :total="reagents.total"
          :from="reagents.from"
          :to="reagents.to"
          :last_page="reagents.last_page"
          :current_page="reagents.current_page"
        />
      </section>

      <aside class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Quarantine review</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Expirados no resultado</h2>
          </div>
          <ol v-if="expiredRows.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="reagent in expiredRows.slice(0, 7)" :key="reagent.id" class="px-5 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ reagent.name }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ reagent.lot || 'Sem lote' }}</p>
                </div>
                <span class="shrink-0 font-mono text-xs font-black tabular-nums text-rose-700 dark:text-rose-300">{{ daysLabel(reagent) }}</span>
              </div>
              <Link :href="route('vap-inventory.items.edit', reagent.id)" class="ds-table-action mt-2">
                Rever disposição
              </Link>
            </li>
          </ol>
          <div v-else class="px-5 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">Nenhum expirado nesta página.</div>
        </section>

        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
            <div>
              <p class="text-sm font-black text-[var(--ds-text)]">Regra de segregação</p>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Reagentes expirados devem sair de uso, ser segregados fisicamente e seguir o procedimento documentado de disposição.</p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Replacement planning</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Substituição e abastecimento</h2>
          </div>
          <div class="space-y-3 p-5">
            <p class="text-sm font-semibold leading-6 text-[var(--ds-text-muted)]">Crie uma necessidade ou ordem apenas após rever stock remanescente, consumo previsto e lotes alternativos.</p>
            <Link :href="route('vap-inventory.orders.create')" class="ds-button ds-button-primary w-full">
              <ShoppingCartIcon class="h-4 w-4" />
              Preparar ordem
            </Link>
            <Link :href="route('vap-inventory.needs.create')" class="ds-button ds-button-secondary w-full">
              <ClipboardDocumentListIcon class="h-4 w-4" />
              Registar necessidade
            </Link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  BeakerIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CubeTransparentIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  ShoppingCartIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  reagents: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

const loading = ref(false)
const exporting = ref(false)
const exportError = ref('')
const filters = reactive({
  status: props.filters?.status ?? '',
  category_id: props.filters?.category_id ?? '',
  warehouse_id: props.filters?.warehouse_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: ['expiry_date', 'name', 'current_stock'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'expiry_date',
  sort_direction: props.filters?.sort_direction === 'desc' ? 'desc' : 'asc',
})

const reagentRows = computed(() => props.reagents?.data || [])
const expiredRows = computed(() => reagentRows.value.filter(isExpired))
const goodCount = computed(() => Math.max(Number(props.stats?.total_reagents || 0) - Number(props.stats?.expired || 0) - Number(props.stats?.expiring_soon || 0), 0))
const expiryWindowTotal = computed(() => Number(props.stats?.expired || 0) + Number(props.stats?.expiring_30 || 0) + Number(props.stats?.expiring_31_60 || 0) + Number(props.stats?.expiring_61_90 || 0))

const summaryCards = computed(() => [
  {
    label: 'Expirados',
    value: props.stats?.expired || 0,
    detail: 'Segregar e rever disposição',
    icon: XCircleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Até 30 dias',
    value: props.stats?.expiring_30 || 0,
    detail: 'Prioridade de consumo',
    icon: ExclamationTriangleIcon,
    tone: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: '31 a 60 dias',
    value: props.stats?.expiring_31_60 || 0,
    detail: 'Revisão de utilização',
    icon: ClockIcon,
    tone: 'text-violet-700 dark:text-violet-300',
  },
  {
    label: 'Conformes',
    value: goodCount.value,
    detail: `${props.stats?.total_reagents || 0} com validade registada`,
    icon: CheckCircleIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
])

const expiryWindows = computed(() => {
  const total = Math.max(expiryWindowTotal.value, 1)
  return [
    { label: 'Expirado', count: Number(props.stats?.expired || 0), detail: 'Segregação imediata', dot: 'bg-rose-500', bar: 'bg-rose-500' },
    { label: '0-30 dias', count: Number(props.stats?.expiring_30 || 0), detail: 'Consumir primeiro', dot: 'bg-amber-500', bar: 'bg-amber-500' },
    { label: '31-60 dias', count: Number(props.stats?.expiring_31_60 || 0), detail: 'Plano de utilização', dot: 'bg-violet-500', bar: 'bg-violet-500' },
    { label: '61-90 dias', count: Number(props.stats?.expiring_61_90 || 0), detail: 'Monitorização preventiva', dot: 'bg-cyan-600', bar: 'bg-cyan-600' },
  ].map((window) => ({ ...window, percentage: (window.count / total) * 100 }))
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.status) pills.push(`Estado: ${statusFilterLabel(filters.status)}`)
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})
const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)

function daysToExpiry(reagent) {
  if (Number.isFinite(Number(reagent.days_to_expiry))) return Number(reagent.days_to_expiry)
  if (!reagent.reagent_expiry_date) return null
  const expiry = new Date(`${String(reagent.reagent_expiry_date).slice(0, 10)}T00:00:00Z`)
  const today = new Date()
  const todayUtc = Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate())
  return Math.round((expiry.getTime() - todayUtc) / 86400000)
}

function isExpired(reagent) {
  return reagent.is_expired === true || Number(daysToExpiry(reagent)) < 0
}

function statusLabel(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Sem data'
  if (days < 0) return 'Expirado'
  if (days <= 30) return 'Prioridade FEFO'
  if (days <= 60) return 'Revisão próxima'
  return 'Conforme'
}

function statusInstruction(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Completar dados de validade.'
  if (days < 0) return 'Segregar e iniciar revisão de disposição.'
  if (days <= 30) return 'Consumir primeiro ou planear substituição.'
  if (days <= 60) return 'Confirmar plano de utilização do lote.'
  return 'Manter monitorização FEFO.'
}

function statusChipClass(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null || days < 0) return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
  if (days <= 30) return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300'
  if (days <= 60) return 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300'
  return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
}

function statusTextClass(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null || days < 0) return 'text-rose-700 dark:text-rose-300'
  if (days <= 30) return 'text-amber-700 dark:text-amber-300'
  if (days <= 60) return 'text-violet-700 dark:text-violet-300'
  return 'text-emerald-700 dark:text-emerald-300'
}

function statusIconSurface(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null || days < 0) return 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
  if (days <= 30) return 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
  if (days <= 60) return 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'
  return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
}

function daysLabel(reagent) {
  const days = daysToExpiry(reagent)
  if (days === null) return 'Sem data'
  if (days < 0) return `${formatNumber(Math.abs(days))} dias vencido`
  if (days === 0) return 'Expira hoje'
  return `${formatNumber(days)} dias`
}

function shelfLifePercentage(reagent) {
  if (!reagent.reagent_open_date || !reagent.reagent_expiry_date) return 0
  const opened = new Date(reagent.reagent_open_date).getTime()
  const expiry = new Date(reagent.reagent_expiry_date).getTime()
  const total = expiry - opened
  if (total <= 0) return 100
  return Math.min(Math.max(((Date.now() - opened) / total) * 100, 0), 100)
}

function shelfLifeBarClass(reagent) {
  const percentage = shelfLifePercentage(reagent)
  if (isExpired(reagent)) return 'bg-rose-500'
  if (percentage >= 80) return 'bg-amber-500'
  if (percentage >= 50) return 'bg-violet-500'
  return 'bg-emerald-500'
}

function warehouseNames(reagent) {
  const names = (reagent.inventory || []).map((position) => position.warehouse?.name).filter(Boolean)
  return names.length ? [...new Set(names)].join(', ') : 'Sem posição de stock'
}

function formatDate(value) {
  if (!value) return 'N/D'
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 1 }).format(Number(value || 0))
}

function statusFilterLabel(value) {
  return ({ expired: 'Expirado', expiring_soon: 'Até 60 dias', good: 'Mais de 60 dias' })[value] || 'Todos'
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function warehouseName(id) {
  return props.warehouses.find((warehouse) => String(warehouse.id) === String(id))?.name || 'N/D'
}

function clearFilters() {
  Object.assign(filters, {
    status: '',
    category_id: '',
    warehouse_id: '',
    search: '',
    sort_by: 'expiry_date',
    sort_direction: 'asc',
  })
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

async function exportReport() {
  exporting.value = true
  exportError.value = ''

  try {
    const response = await fetch(route('vap-inventory.analytics.report'), {
      method: 'POST',
      headers: {
        Accept: 'application/pdf',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({ reportType: 'expiry', format: 'pdf', dateRange: '1y' }),
    })
    if (!response.ok) throw new Error('Não foi possível gerar o relatório de validade.')
    const blob = await response.blob()
    const objectUrl = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = objectUrl
    anchor.download = `expiry_report_${new Date().toISOString().split('T')[0]}.pdf`
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(objectUrl)
  } catch (error) {
    exportError.value = error instanceof Error ? error.message : 'Não foi possível gerar o relatório.'
  } finally {
    exporting.value = false
  }
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.items.reagents.expiry'), value, {
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
