<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_auto] xl:items-start">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Inventory intelligence</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-instrument"></span>
              Janela móvel de 30 dias
            </span>
          </div>
          <div class="mt-3 flex flex-wrap items-center gap-3">
            <span class="grid h-11 w-11 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ChartBarSquareIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="text-2xl font-black tracking-tight text-[var(--ds-text)]">Inteligência de inventário</h1>
              <p class="mt-1 max-w-3xl text-sm font-medium leading-6 text-[var(--ds-text-muted)]">
                Consolide consumo, disponibilidade, risco de validade e desempenho de abastecimento para decisões operacionais rastreáveis.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row xl:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="openReportModal('comprehensive')">
            <ArrowDownTrayIcon class="h-4 w-4" />
            Gerar relatório
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

    <section class="ds-command-surface overflow-hidden">
      <div class="ds-table-summary px-5 py-4">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.14em] text-[var(--ds-text-soft)]">Assurance queue</p>
          <h2 class="mt-1 text-base font-black text-[var(--ds-text)]">Áreas que exigem acompanhamento</h2>
        </div>
        <span class="ds-chip">{{ totalAlerts }} alertas ativos</span>
      </div>

      <div class="grid divide-y divide-[var(--ds-border)] md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-4">
        <Link
          v-for="queue in assuranceQueues"
          :key="queue.label"
          :href="queue.href"
          class="group flex min-w-0 items-start gap-3 p-5 transition-colors hover:bg-[var(--ds-panel-subtle)]"
        >
          <span :class="['grid h-10 w-10 shrink-0 place-items-center rounded-lg', queue.iconSurface]">
            <component :is="queue.icon" class="h-5 w-5" />
          </span>
          <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-3">
              <span class="text-sm font-black text-[var(--ds-text)]">{{ queue.label }}</span>
              <span class="font-mono text-sm font-black tabular-nums text-[var(--ds-text)]">{{ queue.value }}</span>
            </span>
            <span class="mt-1 block text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">{{ queue.detail }}</span>
            <span class="mt-3 inline-flex items-center gap-1 text-xs font-black text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              Abrir controlo
              <ArrowRightIcon class="h-3.5 w-3.5 transition-transform group-hover:translate-x-0.5" />
            </span>
          </span>
        </Link>
      </div>
    </section>

    <InventoryAnalytics
      :initial-data="initialData"
      :categories="categories"
      :warehouses="warehouses"
      @request-report="openReportModal"
    />

    <TransitionRoot as="template" :show="showReportModal">
      <Dialog as="div" class="relative z-50" @close="closeReportModal">
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-150"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center">
            <TransitionChild
              as="template"
              enter="ease-out duration-200"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-150"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="ds-modal-panel w-full max-w-2xl overflow-hidden text-left transition-all">
                <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <p class="ds-kicker">Exportação controlada</p>
                      <DialogTitle class="mt-1 text-lg font-black text-[var(--ds-text)]">Gerar relatório de inventário</DialogTitle>
                      <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">Defina o conteúdo, formato e período do documento.</p>
                    </div>
                    <button type="button" class="ds-icon-button" title="Fechar" @click="closeReportModal">
                      <XMarkIcon class="h-4 w-4" />
                    </button>
                  </div>
                </div>

                <form class="space-y-5 px-5 py-5 sm:px-6" @submit.prevent="generateReport">
                  <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect v-model="report.type" label="Conteúdo">
                      <option value="comprehensive">Relatório integrado</option>
                      <option value="consumption">Consumo de reagentes</option>
                      <option value="stock">Disponibilidade de stock</option>
                      <option value="expiry">Validade de reagentes</option>
                      <option value="calibration">Agenda de calibração</option>
                    </BaseSelect>

                    <BaseSelect v-model="report.format" label="Formato">
                      <option value="pdf">Documento PDF</option>
                      <option value="excel">Livro Excel</option>
                      <option value="csv">Ficheiro CSV</option>
                    </BaseSelect>

                    <BaseInput v-model="report.startDate" type="date" label="Data inicial" />
                    <BaseInput v-model="report.endDate" type="date" label="Data final" />

                    <BaseSelect v-model="report.categoryId" label="Categoria">
                      <option value="">Todas as categorias</option>
                      <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </BaseSelect>

                    <BaseSelect v-model="report.warehouseId" label="Armazém">
                      <option value="">Todos os armazéns</option>
                      <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                    </BaseSelect>
                  </div>

                  <div v-if="reportError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-bold text-rose-800 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300" role="alert">
                    {{ reportError }}
                  </div>

                  <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-secondary" :disabled="generatingReport" @click="closeReportModal">Cancelar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="generatingReport">
                      <ArrowPathIcon v-if="generatingReport" class="h-4 w-4 animate-spin" />
                      <ArrowDownTrayIcon v-else class="h-4 w-4" />
                      {{ generatingReport ? 'A preparar...' : 'Gerar ficheiro' }}
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import InventoryAnalytics from '@/Components/charts/inventory-analytics.vue'
import {
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  ArrowRightIcon,
  ArrowsUpDownIcon,
  BanknotesIcon,
  BeakerIcon,
  CalendarDaysIcon,
  ChartBarSquareIcon,
  ExclamationTriangleIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  initialData: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
  warehouses: { type: Array, default: () => [] },
})

const showReportModal = ref(false)
const generatingReport = ref(false)
const reportError = ref('')
const metrics = computed(() => props.initialData?.metrics ?? {})
const totalAlerts = computed(() => Number(metrics.value.reorderAlerts || 0) + Number(metrics.value.criticalAlerts || 0) + Number(metrics.value.expiringAlerts || 0))

const report = reactive({
  type: 'comprehensive',
  format: 'pdf',
  startDate: dateOffset(-30),
  endDate: dateOffset(0),
  categoryId: '',
  warehouseId: '',
})

const summaryCards = computed(() => [
  {
    label: 'Posições de stock',
    value: formatNumber(metrics.value.total_items),
    detail: 'Registos monitorizados',
    icon: BeakerIcon,
    tone: 'text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200',
  },
  {
    label: 'Consumo no período',
    value: formatNumber(metrics.value.totalConsumption),
    detail: `${formatNumber(metrics.value.dailyAverage)} unidades / dia`,
    icon: ChartBarSquareIcon,
    tone: 'text-rose-700 dark:text-rose-300',
  },
  {
    label: 'Valor de inventário',
    value: formatCurrency(metrics.value.inventoryValue),
    detail: 'Valor contabilizado',
    icon: BanknotesIcon,
    tone: 'text-emerald-700 dark:text-emerald-300',
  },
  {
    label: 'Alertas ativos',
    value: totalAlerts.value,
    detail: `${metrics.value.criticalAlerts || 0} críticos`,
    icon: ExclamationTriangleIcon,
    tone: totalAlerts.value ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300',
  },
])

const assuranceQueues = computed(() => [
  {
    label: 'Reposição de stock',
    value: metrics.value.reorderAlerts || 0,
    detail: `${metrics.value.criticalAlerts || 0} posições críticas ou sem stock`,
    href: route('vap-inventory.reports.low-stock'),
    icon: ExclamationTriangleIcon,
    iconSurface: 'bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
  },
  {
    label: 'Validade de reagentes',
    value: metrics.value.expiringAlerts || 0,
    detail: 'Lotes dentro da janela de validade controlada',
    href: route('vap-inventory.items.reagents.expiry'),
    icon: CalendarDaysIcon,
    iconSurface: 'bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300',
  },
  {
    label: 'Calibração de equipamento',
    value: metrics.value.itemsNeedingCalibration || 0,
    detail: 'Equipamentos vencidos ou com intervenção devida',
    href: route('vap-inventory.items.calibration.schedule'),
    icon: ChartBarSquareIcon,
    iconSurface: 'bg-violet-50 text-violet-800 dark:bg-violet-500/10 dark:text-violet-300',
  },
  {
    label: 'Livro de movimentos',
    value: 'Audit',
    detail: 'Entradas, saídas, ajustes, consumo e transferências',
    href: route('vap-inventory.reports.stock-movement'),
    icon: ArrowsUpDownIcon,
    iconSurface: 'bg-cyan-50 text-cyan-800 dark:bg-cyan-500/10 dark:text-cyan-200',
  },
])

function dateOffset(days) {
  const date = new Date()
  date.setDate(date.getDate() + days)
  return date.toISOString().split('T')[0]
}

function formatNumber(value) {
  return new Intl.NumberFormat('pt-AO', { maximumFractionDigits: 2 }).format(Number(value || 0))
}

function formatCurrency(value) {
  return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA', maximumFractionDigits: 0 }).format(Number(value || 0))
}

function openReportModal(type = 'comprehensive') {
  report.type = ['consumption', 'stock', 'expiry', 'calibration', 'comprehensive'].includes(type) ? type : 'comprehensive'
  reportError.value = ''
  showReportModal.value = true
}

function closeReportModal() {
  if (generatingReport.value) return
  showReportModal.value = false
  reportError.value = ''
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
}

function responseFilename(response) {
  const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition') || ''
  const encoded = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1]
  const plain = disposition.match(/filename="?([^";]+)"?/i)?.[1]
  if (encoded) return decodeURIComponent(encoded)
  if (plain) return plain
  const extension = report.format === 'excel' ? 'xlsx' : report.format
  return `${report.type}_report_${dateOffset(0)}.${extension}`
}

async function generateReport() {
  generatingReport.value = true
  reportError.value = ''

  try {
    const response = await fetch(route('vap-inventory.analytics.report'), {
      method: 'POST',
      headers: {
        Accept: 'application/octet-stream',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
      },
      body: JSON.stringify({
        reportType: report.type,
        format: report.format,
        dateRange: 'custom',
        startDate: report.startDate,
        endDate: report.endDate,
        categoryId: report.categoryId || null,
        warehouseId: report.warehouseId || null,
      }),
    })

    if (!response.ok) throw new Error('Não foi possível gerar o relatório com os filtros selecionados.')

    const blob = await response.blob()
    const objectUrl = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = objectUrl
    anchor.download = responseFilename(response)
    document.body.appendChild(anchor)
    anchor.click()
    anchor.remove()
    URL.revokeObjectURL(objectUrl)
    showReportModal.value = false
  } catch (error) {
    reportError.value = error instanceof Error ? error.message : 'Não foi possível gerar o relatório.'
  } finally {
    generatingReport.value = false
  }
}
</script>
