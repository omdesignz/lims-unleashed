<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Reagent stewardship</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument"></span>
              Registo de consumo
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <BeakerIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Consumo de reagentes</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Audite volume, frequência, responsáveis e desvios de utilização por reagente e armazém.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="exportReport">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar PDF
          </button>
          <Link :href="route('vap-inventory.reagents.consumption.index')" class="ds-button ds-button-primary">
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Registo operacional
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
        <BaseInput v-model="filters.date_from" type="date" label="Data inicial" />
        <BaseInput v-model="filters.date_to" type="date" label="Data final" />

        <div class="ds-field-group xl:col-span-2">
          <label class="ds-field-label">Reagente</label>
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

        <BaseInput v-model="filters.search" label="Pesquisa livre" placeholder="Reagente, utilizador ou observação">
          <template #leading><MagnifyingGlassIcon class="h-4 w-4" /></template>
        </BaseInput>

        <div class="grid grid-cols-2 gap-3">
          <BaseSelect v-model="filters.sort_by" label="Ordenar por">
            <option value="date">Data</option>
            <option value="quantity_used">Quantidade</option>
          </BaseSelect>
          <BaseSelect v-model="filters.sort_direction" label="Direção">
            <option value="desc">Descendente</option>
            <option value="asc">Ascendente</option>
          </BaseSelect>
        </div>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div>
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ consumptions.total || consumptionRows.length }} eventos de consumo</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">A mostrar todo o histórico de consumo disponível.</p>
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
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Análise de utilização</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Concentração, responsáveis e ritmo diário</h2>
        </div>
        <span class="ds-chip">{{ filterPeriod || 'Período completo' }}</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] xl:grid-cols-[1.15fr_0.85fr] xl:divide-x xl:divide-y-0">
        <article class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h3 class="text-sm font-black text-[var(--ds-text)]">Reagentes mais consumidos</h3>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Volume absoluto por reagente no período filtrado.</p>
            </div>
            <span class="text-xl font-black text-[var(--ds-text)]">{{ itemConsumptionTotal }}</span>
          </div>
          <div class="mt-4 min-h-72">
            <apexchart type="bar" height="288" :options="itemConsumptionChartOptions" :series="itemConsumptionChartSeries" />
          </div>
        </article>

        <div class="grid divide-y divide-[var(--ds-border)]">
          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Consumo por utilizador</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Responsáveis com maior volume registado.</p>
              </div>
              <span class="ds-chip">{{ userConsumptionTotal }} utilizadores</span>
            </div>
            <div class="mt-4 min-h-64">
              <apexchart type="donut" height="256" :options="userConsumptionChartOptions" :series="userConsumptionChartSeries" />
            </div>
          </article>

          <article class="min-w-0 p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-sm font-black text-[var(--ds-text)]">Ritmo diário</h3>
                <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Picos e estabilidade do volume consumido.</p>
              </div>
              <ChartBarSquareIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
            </div>
            <div class="mt-4 min-h-56">
              <apexchart type="line" height="224" :options="dailyConsumptionChartOptions" :series="dailyConsumptionChartSeries" />
            </div>
          </article>
        </div>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_21rem]">
      <section class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Rastreabilidade</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Eventos de consumo</h2>
          </div>
          <span class="ds-chip">{{ consumptions.total || consumptionRows.length }} registos</span>
        </div>

        <div v-if="loading" class="ds-empty-state m-5 p-8 text-center">
          <span class="mx-auto block h-7 w-7 animate-spin rounded-full border-2 border-[var(--ds-border)] border-t-[rgb(var(--primary-700-rgb))]"></span>
          <p class="mt-3 text-sm font-semibold text-[var(--ds-text-muted)]">A atualizar o relatório...</p>
        </div>

        <div v-else-if="consumptionRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="event in consumptionRows" :key="`mobile-${event.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ event.item?.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ event.reagent_name || event.item?.name || 'Reagente não identificado' }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ formatDate(event.date) }} · {{ event.warehouse?.name || 'Sem armazém' }}</p>
              </div>
              <span class="inline-flex shrink-0 items-center gap-2 text-xs font-black text-rose-700 dark:text-rose-300">
                <span class="h-2 w-2 rounded-full bg-rose-600"></span>
                {{ formatQuantity(event.quantity_used) }}
              </span>
            </div>
            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Utilizador</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ event.used_by || event.user?.name || 'N/D' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Categoria</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ event.item?.category?.name || 'N/D' }}</dd>
              </div>
            </dl>
            <p class="text-sm font-semibold leading-5 text-[var(--ds-text-muted)]">{{ event.remarks || 'Sem observações.' }}</p>
          </article>
        </div>

        <div v-else-if="!loading" class="ds-empty-state m-5 p-8 text-center">
          <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem consumo no período</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste os filtros para consultar outro conjunto de registos.</p>
        </div>

        <div v-if="!loading && consumptionRows.length" class="hidden overflow-x-auto lg:block">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Data</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Reagente</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Armazém</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Quantidade</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Utilizador</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Observação</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="event in consumptionRows" :key="event.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top font-mono text-xs font-black tabular-nums text-[var(--ds-text)]">{{ formatDate(event.date) }}</td>
                <td class="px-5 py-4 align-top">
                  <p class="font-black text-[var(--ds-text)]">{{ event.reagent_name || event.item?.name || 'Reagente não identificado' }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ event.item?.code || 'Sem código' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ event.item?.category?.name || 'Sem categoria' }}</p>
                </td>
                <td class="px-5 py-4 align-top font-semibold text-[var(--ds-text-muted)]">{{ event.warehouse?.name || 'N/D' }}</td>
                <td class="px-5 py-4 text-right align-top font-mono font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatQuantity(event.quantity_used) }}</td>
                <td class="px-5 py-4 align-top font-bold text-[var(--ds-text)]">{{ event.used_by || event.user?.name || 'N/D' }}</td>
                <td class="max-w-xs px-5 py-4 align-top text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ event.remarks || 'Sem observações.' }}</td>
              </tr>
            </tbody>
          </DataTable>
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

      <aside class="space-y-6">
        <section class="ds-panel p-5">
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Pico de utilização</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
              <FireIcon class="h-5 w-5" />
            </span>
            <div>
              <p class="text-lg font-black text-[var(--ds-text)]">{{ peakDayLabel }}</p>
              <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ formatQuantity(stats.peak_consumption_day?.total_consumption) }} em {{ stats.peak_consumption_day?.usage_count || 0 }} eventos</p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Maior consumo</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Reagentes</h2>
          </div>
          <ol v-if="summaryByItem.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="(item, index) in summaryByItem.slice(0, 7)" :key="item.reagent_id || index" class="flex items-center gap-3 px-5 py-3">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-xs font-black text-[var(--ds-text-soft)]">{{ index + 1 }}</span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ item.reagent_name || 'Sem reagente' }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ item.usage_count }} eventos · média {{ formatQuantity(item.avg_per_use) }}</p>
              </div>
              <span class="shrink-0 text-xs font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatQuantity(item.total_consumption) }}</span>
            </li>
          </ol>
          <div v-else class="ds-empty-state m-4 p-4 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem dados por reagente.</div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Responsabilidade</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Utilizadores mais ativos</h2>
          </div>
          <ol v-if="summaryByUser.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="user in summaryByUser.slice(0, 7)" :key="user.used_by" class="px-5 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ user.used_by || 'Sem utilizador' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ user.usage_count }} eventos</p>
                </div>
                <span class="shrink-0 text-xs font-black tabular-nums text-[var(--ds-text)]">{{ formatQuantity(user.total_consumption) }}</span>
              </div>
            </li>
          </ol>
          <div v-else class="ds-empty-state m-4 p-4 text-center text-sm font-semibold text-[var(--ds-text-muted)]">Sem dados por utilizador.</div>
        </section>
      </aside>
    </div>

    <section class="grid gap-6 xl:grid-cols-2">
      <div class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Resumo</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Consumo por reagente</h2>
          </div>
          <BeakerIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Reagente</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Eventos</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="item in summaryByItem" :key="item.reagent_id" class="hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-3 font-black text-[var(--ds-text)]">{{ item.reagent_name || 'Sem reagente' }}</td>
                <td class="px-5 py-3 text-right font-semibold tabular-nums text-[var(--ds-text-muted)]">{{ item.usage_count }}</td>
                <td class="px-5 py-3 text-right font-black tabular-nums text-rose-700 dark:text-rose-300">{{ formatQuantity(item.total_consumption) }}</td>
              </tr>
            </tbody>
          </DataTable>
        </div>
      </div>

      <div class="ds-table-shell">
        <div class="ds-table-summary px-5 py-4">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Resumo</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Consumo por utilizador</h2>
          </div>
          <UsersIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
        </div>
        <div class="overflow-x-auto">
          <DataTable class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Utilizador</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Eventos</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Total</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="user in summaryByUser" :key="user.used_by" class="hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-3 font-black text-[var(--ds-text)]">{{ user.used_by || 'Sem utilizador' }}</td>
                <td class="px-5 py-3 text-right font-semibold tabular-nums text-[var(--ds-text-muted)]">{{ user.usage_count }}</td>
                <td class="px-5 py-3 text-right font-black tabular-nums text-[var(--ds-text)]">{{ formatQuantity(user.total_consumption) }}</td>
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
import ComboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowDownTrayIcon,
  BeakerIcon,
  ChartBarSquareIcon,
  ClipboardDocumentListIcon,
  FireIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  TrophyIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'

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
const isDarkMode = ref(false)
let themeObserver

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
const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const summaryCards = computed(() => [
  {
    label: 'Consumo total',
    value: formatQuantity(props.stats?.total_consumption),
    detail: 'Volume acumulado',
    icon: FireIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Eventos',
    value: props.stats?.total_uses || 0,
    detail: 'Registos de utilização',
    icon: ClipboardDocumentListIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Média diária',
    value: formatQuantity(props.stats?.avg_daily_consumption),
    detail: 'Ritmo médio do período',
    icon: ChartBarSquareIcon,
    tone: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: 'Reagente principal',
    value: props.stats?.most_consumed_item?.reagent_name || 'Sem dados',
    detail: formatQuantity(props.stats?.most_consumed_item?.total_consumption),
    icon: TrophyIcon,
    tone: 'text-violet-700 dark:text-violet-300',
  },
])

const filterPeriod = computed(() => {
  if (filters.date_from && filters.date_to) return `${formatDate(filters.date_from)} - ${formatDate(filters.date_to)}`
  if (filters.date_from) return `Desde ${formatDate(filters.date_from)}`
  if (filters.date_to) return `Até ${formatDate(filters.date_to)}`
  return ''
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filterPeriod.value) pills.push(filterPeriod.value)
  if (filters.item_id) pills.push(`Reagente: ${selectedItem.value?.label || 'Selecionado'}`)
  if (filters.warehouse_id) pills.push(`Armazém: ${warehouseName(filters.warehouse_id)}`)
  if (filters.user_id) pills.push(`Utilizador: ${userName(filters.user_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})

const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)
const itemConsumptionChartSeries = computed(() => props.charts?.item_consumption?.series || [])
const itemConsumptionTotal = computed(() => props.charts?.item_consumption?.labels?.length || 0)
const userConsumptionChartSeries = computed(() => props.charts?.user_consumption?.series || [])
const userConsumptionTotal = computed(() => props.charts?.user_consumption?.labels?.length || 0)
const dailyConsumptionChartSeries = computed(() => props.charts?.daily_consumption?.series || [])
const peakDayLabel = computed(() => props.stats?.peak_consumption_day?.date ? formatDate(props.stats.peak_consumption_day.date) : 'Sem dados')

const itemConsumptionChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#be123c'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  plotOptions: { bar: { borderRadius: 4, horizontal: true } },
  xaxis: {
    categories: props.charts?.item_consumption?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value } },
  },
  yaxis: { labels: { maxWidth: 220, style: { colors: chartTextColor.value } } },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const userConsumptionChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.user_consumption?.labels || [],
  colors: ['#0f766e', '#1d4ed8', '#7c3aed', '#d97706', '#be123c', '#475569'],
  legend: { position: 'bottom', labels: { colors: chartTextColor.value } },
  dataLabels: { formatter: (value) => `${value.toFixed(0)}%` },
  stroke: { width: 0 },
  tooltip: { theme: chartTooltipTheme.value },
}))

const dailyConsumptionChartOptions = computed(() => ({
  chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  colors: ['#0e7490'],
  dataLabels: { enabled: false },
  grid: { borderColor: chartGridColor.value, strokeDashArray: 4 },
  stroke: { curve: 'straight', width: 3 },
  markers: { size: 3 },
  xaxis: {
    categories: props.charts?.daily_consumption?.labels || [],
    labels: { rotate: -20, trim: true, style: { colors: chartTextColor.value } },
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

function exportReport() {
  router.post(route('vap-inventory.reports.export'), {
    report_type: 'consumption',
    format: 'pdf',
    filters: { ...filters },
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
