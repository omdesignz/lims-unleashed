<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Metrology assurance</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument"></span>
              ISO/IEC 17025 readiness
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-cyan-700 dark:text-cyan-300">
              <WrenchScrewdriverIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Agenda metrológica</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Controle vencimentos, bloqueios de aptidão e evidência técnica para equipamentos sujeitos a calibração.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <Link :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-secondary">
            <ClipboardDocumentListIcon class="h-4 w-4" />
            Criar tarefa
          </Link>
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
              <p class="mt-3 truncate text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ formatNumber(card.value) }}</p>
            </div>
            <component :is="card.icon" :class="['h-5 w-5 shrink-0', card.tone]" />
          </div>
          <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ card.detail }}</p>
        </article>
      </div>
    </section>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
        <BaseSelect v-model="filters.status" label="Estado metrológico">
          <option value="">Todos os estados</option>
          <option value="overdue">Atrasado</option>
          <option value="due_soon">Até 30 dias</option>
          <option value="upcoming">Mais de 30 dias</option>
        </BaseSelect>

        <BaseSelect v-model="filters.type_id" label="Tipo de equipamento">
          <option value="">Todos os tipos</option>
          <option v-for="type in types" :key="type.id" :value="type.id">{{ type.name }}</option>
        </BaseSelect>

        <BaseSelect v-model="filters.category_id" label="Categoria">
          <option value="">Todas as categorias</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
        </BaseSelect>

        <BaseInput v-model="filters.search" label="Pesquisar equipamento" placeholder="Nome, código, série">
          <template #leading><MagnifyingGlassIcon class="h-4 w-4" /></template>
        </BaseInput>

        <div class="grid grid-cols-2 gap-3 xl:col-span-2">
          <BaseSelect v-model="filters.sort_by" label="Ordenar por">
            <option value="next_calibration_date">Próxima calibração</option>
            <option value="last_calibration_date">Última calibração</option>
            <option value="name">Nome</option>
          </BaseSelect>
          <BaseSelect v-model="filters.sort_direction" label="Direção">
            <option value="asc">Ascendente</option>
            <option value="desc">Descendente</option>
          </BaseSelect>
        </div>
      </div>

      <div class="ds-command-toolbar mt-5 grid gap-3 p-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div class="min-w-0">
          <p class="text-sm font-bold text-[var(--ds-text)]">{{ items.total || itemRows.length }} equipamentos no âmbito</p>
          <div v-if="activeFilterPills.length" class="mt-2 flex flex-wrap gap-2">
            <span v-for="pill in activeFilterPills" :key="pill" class="ds-chip">{{ pill }}</span>
          </div>
          <p v-else class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Ordenação por data de calibração com prioridade a vencimentos mais próximos.</p>
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
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Calibration windows</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Carga metrológica nos próximos 90 dias</h2>
        </div>
        <span class="ds-chip">{{ formatNumber(calibrationWindowTotal) }} ocorrências</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] sm:grid-cols-2 sm:divide-x sm:divide-y-0 xl:grid-cols-4">
        <article v-for="window in calibrationWindows" :key="window.label" class="min-w-0 p-5">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">{{ window.label }}</p>
              <p class="mt-2 text-2xl font-black tabular-nums text-[var(--ds-text)]">{{ formatNumber(window.count) }}</p>
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
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Instrument calibration ledger</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Equipamentos por prioridade metrológica</h2>
          </div>
          <span class="ds-chip">{{ items.total || itemRows.length }} registos</span>
        </div>

        <div v-if="itemRows.length" class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article v-for="item in itemRows" :key="`mobile-${item.id}`" class="space-y-4 p-5">
            <div class="flex items-start justify-between gap-4">
              <div class="min-w-0">
                <p class="font-mono text-xs font-bold text-[var(--ds-text-soft)]">{{ item.internal_code || item.code || 'Sem código' }}</p>
                <h3 class="mt-1 text-base font-black text-[var(--ds-text)]">{{ item.name }}</h3>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ item.category?.name || 'Sem categoria' }}</p>
              </div>
              <span :class="['ds-chip shrink-0', statusChipClass(item)]">{{ statusLabel(item) }}</span>
            </div>

            <dl class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Próxima</dt>
                <dd :class="['mt-2 text-sm font-black', statusTextClass(item)]">{{ formatDate(item.next_calibration_date) }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Dias</dt>
                <dd :class="['mt-2 text-sm font-black tabular-nums', statusTextClass(item)]">{{ daysLabel(item) }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Última</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ formatDate(item.last_calibration_date) || 'Nunca' }}</dd>
              </div>
              <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-3">
                <dt class="text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Aptidão</dt>
                <dd class="mt-2 text-sm font-black text-[var(--ds-text)]">{{ metrologyLabel(item.metrology_status) }}</dd>
              </div>
            </dl>

            <div>
              <div class="flex items-center justify-between gap-3 text-xs font-bold">
                <span class="text-[var(--ds-text-muted)]">Ciclo de calibração usado</span>
                <span class="text-[var(--ds-text)]">{{ formatNumber(calibrationUtilization(item)) }}%</span>
              </div>
              <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
                <div :class="['h-full rounded-full', utilizationBarClass(item)]" :style="{ width: `${calibrationUtilization(item)}%` }"></div>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <Link :href="route('vap-inventory.items.show', item.id)" class="ds-table-action">
                <EyeIcon class="h-4 w-4" />
                Abrir dossiê
              </Link>
              <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action">
                <PencilSquareIcon class="h-4 w-4" />
                Rever metrologia
              </Link>
            </div>
          </article>
        </div>

        <div v-else class="ds-empty-state m-5 p-8 text-center">
          <WrenchScrewdriverIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="mt-3 text-sm font-black text-[var(--ds-text)]">Sem equipamentos no âmbito</h3>
          <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Ajuste os filtros ou confirme que a próxima calibração foi registada.</p>
        </div>

        <div v-if="itemRows.length" class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-[var(--ds-border)] text-left text-sm">
            <thead class="bg-[var(--ds-panel-subtle)]">
              <tr>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Equipamento</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Calendário</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Rastreabilidade</th>
                <th class="px-5 py-3 text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Estado</th>
                <th class="px-5 py-3 text-right text-xs font-black uppercase tracking-[0.12em] text-[var(--ds-text-soft)]">Ações</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
              <tr v-for="item in itemRows" :key="item.id" class="transition-colors hover:bg-[var(--ds-panel-subtle)]">
                <td class="px-5 py-4 align-top">
                  <div class="flex items-start gap-3">
                    <span :class="['grid h-9 w-9 shrink-0 place-items-center rounded-lg', statusIconSurface(item)]">
                      <WrenchScrewdriverIcon class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                      <p class="font-black text-[var(--ds-text)]">{{ item.name }}</p>
                      <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.internal_code || item.code || 'Sem código' }}</p>
                      <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                        {{ item.category?.name || 'Sem categoria' }}
                        <span v-if="item.type"> · {{ item.type.name }}</span>
                      </p>
                      <p v-if="item.model || item.brand" class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ [item.brand, item.model].filter(Boolean).join(' · ') }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <p :class="['font-mono text-sm font-black tabular-nums', statusTextClass(item)]">{{ formatDate(item.next_calibration_date) }}</p>
                  <p :class="['mt-1 text-xs font-black', statusTextClass(item)]">{{ daysLabel(item) }}</p>
                  <div class="mt-3 min-w-40">
                    <div class="flex items-center justify-between gap-3 text-xs font-semibold text-[var(--ds-text-muted)]">
                      <span>Última {{ formatDate(item.last_calibration_date) || 'Nunca' }}</span>
                      <span>{{ formatNumber(calibrationUtilization(item)) }}%</span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[var(--ds-panel-muted)]">
                      <div :class="['h-full rounded-full', utilizationBarClass(item)]" :style="{ width: `${calibrationUtilization(item)}%` }"></div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4 align-top">
                  <p class="font-mono text-xs font-black text-[var(--ds-text)]">{{ item.serial_number || 'Sem série' }}</p>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ item.location || 'Sem localização' }}</p>
                  <p v-if="item.metrological_traceability_reference" class="mt-1 max-w-52 text-xs font-semibold leading-5 text-[var(--ds-text-soft)]">{{ item.metrological_traceability_reference }}</p>
                  <p v-if="item.metrological_uncertainty_value" class="mt-1 font-mono text-xs font-black text-[var(--ds-text-soft)]">
                    U={{ item.metrological_uncertainty_value }} {{ item.metrological_uncertainty_unit || '' }}
                  </p>
                </td>
                <td class="px-5 py-4 align-top">
                  <span :class="['ds-chip', statusChipClass(item)]">{{ statusLabel(item) }}</span>
                  <p class="mt-2 max-w-44 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ statusInstruction(item) }}</p>
                  <p v-if="item.metrology_status" class="mt-2 text-xs font-black" :class="metrologyTextClass(item.metrology_status)">
                    {{ metrologyLabel(item.metrology_status) }}
                  </p>
                </td>
                <td class="px-5 py-4 text-right align-top">
                  <div class="flex justify-end gap-1">
                    <Link :href="route('vap-inventory.items.show', item.id)" class="ds-icon-button" title="Abrir dossiê">
                      <EyeIcon class="h-4 w-4" />
                    </Link>
                    <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-icon-button" title="Rever metrologia">
                      <PencilSquareIcon class="h-4 w-4" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination
          v-if="itemRows.length"
          :links="items.links"
          :total="items.total"
          :from="items.from"
          :to="items.to"
          :last_page="items.last_page"
          :current_page="items.current_page"
        />
      </section>

      <aside class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Metrology hold review</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Equipamentos em contenção</h2>
          </div>
          <ol v-if="holdRows.length" class="divide-y divide-[var(--ds-border)]">
            <li v-for="item in holdRows.slice(0, 7)" :key="item.id" class="px-5 py-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-black text-[var(--ds-text)]">{{ item.name }}</p>
                  <p class="mt-1 font-mono text-xs font-semibold text-[var(--ds-text-soft)]">{{ item.internal_code || item.serial_number || 'Sem identificação' }}</p>
                </div>
                <span class="shrink-0 font-mono text-xs font-black tabular-nums text-rose-700 dark:text-rose-300">{{ daysLabel(item) }}</span>
              </div>
              <Link :href="route('vap-inventory.items.edit', item.id)" class="ds-table-action mt-2">
                Rever aptidão
              </Link>
            </li>
          </ol>
          <div v-else class="px-5 py-4 text-sm font-semibold text-[var(--ds-text-muted)]">Nenhum bloqueio nesta página.</div>
        </section>

        <section class="ds-panel p-5">
          <div class="flex items-start gap-3">
            <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300" />
            <div>
              <p class="text-sm font-black text-[var(--ds-text)]">Regra de aptidão</p>
              <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">Equipamentos atrasados ou com rastreabilidade incompleta devem permanecer fora de uso até revisão técnica documentada.</p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Planning links</p>
            <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Execução e evidência</h2>
          </div>
          <div class="space-y-3 p-5">
            <p class="text-sm font-semibold leading-6 text-[var(--ds-text-muted)]">Registe execução através de tarefas de manutenção/calibração ou atualize o dossiê do equipamento.</p>
            <Link :href="route('vap-maintenance.tasks.create')" class="ds-button ds-button-primary w-full">
              <ClipboardDocumentListIcon class="h-4 w-4" />
              Criar tarefa
            </Link>
            <Link :href="route('vap-inventory.items.index')" class="ds-button ds-button-secondary w-full">
              <CpuChipIcon class="h-4 w-4" />
              Abrir inventário
            </Link>
          </div>
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import Pagination from '@/Components/Pagination.vue'
import {
  ArrowLeftIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
  ClipboardDocumentListIcon,
  ClockIcon,
  CpuChipIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  FunnelIcon,
  MagnifyingGlassIcon,
  PencilSquareIcon,
  ShieldCheckIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  items: { type: Object, default: () => ({ data: [] }) },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  types: { type: Array, default: () => [] },
  stats: { type: Object, default: () => ({}) },
})

const filters = reactive({
  status: props.filters?.status ?? '',
  type_id: props.filters?.type_id ?? '',
  category_id: props.filters?.category_id ?? '',
  search: props.filters?.search ?? '',
  sort_by: ['next_calibration_date', 'last_calibration_date', 'name'].includes(props.filters?.sort_by) ? props.filters.sort_by : 'next_calibration_date',
  sort_direction: props.filters?.sort_direction === 'desc' ? 'desc' : 'asc',
})

const itemRows = computed(() => props.items?.data || [])
const holdRows = computed(() => itemRows.value.filter((item) => item.needs_calibration || ['hold', 'incomplete', 'review_due'].includes(item.metrology_status)))
const upcomingCount = computed(() => Math.max(Number(props.stats?.total_scheduled || 0) - Number(props.stats?.total_due || 0) - Number(props.stats?.due_soon || 0), 0))
const calibrationWindowTotal = computed(() => Number(props.stats?.total_due || 0) + Number(props.stats?.due_soon || 0) + Number(props.stats?.due_31_90 || 0))

const summaryCards = computed(() => [
  {
    label: 'Atrasados',
    value: props.stats?.total_due || 0,
    detail: 'Bloqueio e revisão imediata',
    icon: ExclamationTriangleIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Até 30 dias',
    value: props.stats?.due_soon || 0,
    detail: 'Execução no ciclo atual',
    icon: ClockIcon,
    tone: 'text-amber-700 dark:text-amber-300',
  },
  {
    label: '31 a 90 dias',
    value: props.stats?.due_31_90 || 0,
    detail: 'Planeamento preventivo',
    icon: CalendarDaysIcon,
    tone: 'text-violet-700 dark:text-violet-300',
  },
  {
    label: 'Programados',
    value: props.stats?.total_scheduled || 0,
    detail: `${upcomingCount.value} fora da janela crítica`,
    icon: CheckCircleIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
])

const calibrationWindows = computed(() => {
  const total = Math.max(calibrationWindowTotal.value, 1)
  return [
    { label: 'Atrasado', count: Number(props.stats?.total_due || 0), detail: 'Retirar de uso até revisão', dot: 'bg-rose-500', bar: 'bg-rose-500' },
    { label: '0-30 dias', count: Number(props.stats?.due_soon || 0), detail: 'Executar ou confirmar fornecedor', dot: 'bg-amber-500', bar: 'bg-amber-500' },
    { label: '31-90 dias', count: Number(props.stats?.due_31_90 || 0), detail: 'Preparar tarefa e evidência', dot: 'bg-violet-500', bar: 'bg-violet-500' },
    { label: 'Total', count: Number(props.stats?.total_scheduled || 0), detail: 'Equipamentos com agenda ativa', dot: 'bg-cyan-600', bar: 'bg-cyan-600' },
  ].map((window) => ({ ...window, percentage: (window.count / total) * 100 }))
})

const activeFilterPills = computed(() => {
  const pills = []
  if (filters.status) pills.push(`Estado: ${statusFilterLabel(filters.status)}`)
  if (filters.type_id) pills.push(`Tipo: ${typeName(filters.type_id)}`)
  if (filters.category_id) pills.push(`Categoria: ${categoryName(filters.category_id)}`)
  if (filters.search) pills.push(`Pesquisa: ${filters.search}`)
  return pills
})
const hasActiveFilters = computed(() => activeFilterPills.value.length > 0)

function daysToCalibration(item) {
  if (Number.isFinite(Number(item.days_to_calibration))) return Number(item.days_to_calibration)
  if (!item.next_calibration_date) return null
  const next = new Date(`${String(item.next_calibration_date).slice(0, 10)}T00:00:00Z`)
  const today = new Date()
  const todayUtc = Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate())
  return Math.round((next.getTime() - todayUtc) / 86400000)
}

function statusLabel(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Sem data'
  if (days < 0) return 'Atrasado'
  if (days <= 30) return 'Prioridade'
  if (days <= 90) return 'Planeado'
  return 'Programado'
}

function statusInstruction(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Completar calendário metrológico.'
  if (days < 0) return 'Bloquear uso e documentar decisão técnica.'
  if (days <= 30) return 'Executar calibração ou confirmar data externa.'
  if (days <= 90) return 'Preparar tarefa e disponibilidade do equipamento.'
  return 'Manter monitorização periódica.'
}

function statusChipClass(item) {
  const days = daysToCalibration(item)
  if (days === null || days < 0) return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
  if (days <= 30) return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300'
  if (days <= 90) return 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-500/20 dark:bg-violet-500/10 dark:text-violet-300'
  return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
}

function statusTextClass(item) {
  const days = daysToCalibration(item)
  if (days === null || days < 0) return 'text-rose-700 dark:text-rose-300'
  if (days <= 30) return 'text-amber-700 dark:text-amber-300'
  if (days <= 90) return 'text-violet-700 dark:text-violet-300'
  return 'text-emerald-700 dark:text-emerald-300'
}

function statusIconSurface(item) {
  const days = daysToCalibration(item)
  if (days === null || days < 0) return 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'
  if (days <= 30) return 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300'
  if (days <= 90) return 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300'
  return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
}

function daysLabel(item) {
  const days = daysToCalibration(item)
  if (days === null) return 'Sem data'
  if (days < 0) return `${formatNumber(Math.abs(days))} dias atraso`
  if (days === 0) return 'Hoje'
  return `${formatNumber(days)} dias`
}

function calibrationUtilization(item) {
  if (!item.last_calibration_date || !item.next_calibration_date) return 0
  const last = new Date(item.last_calibration_date).getTime()
  const next = new Date(item.next_calibration_date).getTime()
  const total = next - last
  if (total <= 0) return 100
  return Math.min(Math.max(((Date.now() - last) / total) * 100, 0), 100)
}

function utilizationBarClass(item) {
  if (daysToCalibration(item) < 0) return 'bg-rose-500'
  const percentage = calibrationUtilization(item)
  if (percentage >= 80) return 'bg-amber-500'
  if (percentage >= 50) return 'bg-violet-500'
  return 'bg-emerald-500'
}

function metrologyLabel(status) {
  if (status === 'hold') return 'Bloqueado'
  if (status === 'incomplete') return 'Incompleto'
  if (status === 'review_due') return 'Revisão em breve'
  if (status === 'validated') return 'Validado'
  return 'Não aplicável'
}

function metrologyTextClass(status) {
  if (status === 'hold' || status === 'incomplete') return 'text-rose-700 dark:text-rose-300'
  if (status === 'review_due') return 'text-amber-700 dark:text-amber-300'
  if (status === 'validated') return 'text-emerald-700 dark:text-emerald-300'
  return 'text-[var(--ds-text-soft)]'
}

function statusFilterLabel(value) {
  return ({ overdue: 'Atrasado', due_soon: 'Até 30 dias', upcoming: 'Mais de 30 dias' })[value] || 'Todos'
}

function categoryName(id) {
  return props.categories.find((category) => String(category.id) === String(id))?.name || 'N/D'
}

function typeName(id) {
  return props.types.find((type) => String(type.id) === String(id))?.name || 'N/D'
}

function formatDate(value) {
  if (!value) return ''
  return new Intl.DateTimeFormat('pt-AO', { day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC' }).format(new Date(value))
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 1 }).format(Number(value || 0))
}

function clearFilters() {
  Object.assign(filters, {
    status: '',
    type_id: '',
    category_id: '',
    search: '',
    sort_by: 'next_calibration_date',
    sort_direction: 'asc',
  })
}

watch(
  filters,
  debounce((value) => {
    router.get(route('vap-inventory.items.calibration.schedule'), value, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    })
  }, 350),
  { deep: true },
)
</script>
