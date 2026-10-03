<template>
  <div class="pl-page" data-template="page">
    <PageHeader
      :crumbs="[{ title: 'Inventário', url: route('vap-inventory.items.index') }, { title: 'Análise' }]"
      title="Análise de inventário"
      :lede="lede"
    >
      <template #actions>
        <button type="button" class="ds-button ds-button-secondary" @click="openReportModal('comprehensive')">
          <ArrowDownTrayIcon class="h-4 w-4" aria-hidden="true" />
          Gerar relatório
        </button>
        <button type="button" class="ds-button ds-button-quiet" @click="openReportModal('consumption')">Exportar consumo</button>
      </template>
    </PageHeader>

    <InventoryAnalytics :initial-data="initialData" :categories="categories" :warehouses="warehouses">
      <template #areas>
        <section class="pl-panel" aria-labelledby="analytics-areas">
          <div class="pl-panel-head">
            <h2 id="analytics-areas" class="pl-k">Áreas que exigem acompanhamento</h2>
            <span class="pl-k pl-faint">{{ totalAlerts }} {{ totalAlerts === 1 ? 'alerta activo' : 'alertas activos' }}</span>
          </div>
          <Link v-for="queue in assuranceQueues" :key="queue.label" :href="queue.href" class="pl-row">
            <span class="min-w-0">
              <span class="block font-medium">{{ queue.label }}</span>
              <span class="block text-[12.5px] text-[var(--pl-muted)]">{{ queue.detail }}</span>
            </span>
            <span class="flex items-center gap-3">
              <span v-if="queue.value !== null" class="pl-num" :class="{ 'text-[var(--pl-bad)]': queue.value > 0 }">{{ queue.value }}</span>
              <ArrowRightIcon class="h-4 w-4 text-[var(--pl-muted)]" aria-hidden="true" />
            </span>
          </Link>
        </section>
      </template>
    </InventoryAnalytics>

    <TransitionRoot as="template" :show="showReportModal">
      <Dialog as="div" class="relative z-50" @close="closeReportModal">
        <TransitionChild
          as="template"
          enter="ease-out duration-200"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-out duration-150"
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
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-out duration-150"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
            >
              <DialogPanel class="ds-modal-panel w-full max-w-2xl overflow-hidden text-left transition-all">
                <div class="border-b border-[var(--pl-line)] px-5 py-4 sm:px-6">
                  <div class="flex items-start justify-between gap-4">
                    <div class="grid gap-1">
                      <DialogTitle class="pl-d3">Gerar relatório de inventário</DialogTitle>
                      <p class="text-sm text-[var(--pl-muted)]">Defina o conteúdo, o formato e o período do documento.</p>
                    </div>
                    <button type="button" class="ds-icon-button" aria-label="Fechar" @click="closeReportModal">
                      <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                  </div>
                </div>

                <form class="space-y-5 px-5 py-5 sm:px-6" @submit.prevent="generateReport">
                  <div class="grid gap-4 sm:grid-cols-2">
                    <BaseSelect v-model="report.type" label="Conteúdo">
                      <option value="comprehensive">Relatório integrado</option>
                      <option value="consumption">Consumo de reagentes</option>
                      <option value="stock">Disponibilidade de existências</option>
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

                  <p v-if="reportError" class="pl-banner pl-banner-bad text-sm" role="alert">{{ reportError }}</p>

                  <div class="flex flex-col-reverse gap-3 border-t border-[var(--pl-line)] pt-5 sm:flex-row sm:justify-end">
                    <button type="button" class="ds-button ds-button-quiet" :disabled="generatingReport" @click="closeReportModal">Cancelar</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="generatingReport">
                      <ArrowPathIcon v-if="generatingReport" class="h-4 w-4 animate-spin" aria-hidden="true" />
                      <ArrowDownTrayIcon v-else class="h-4 w-4" aria-hidden="true" />
                      {{ generatingReport ? 'A preparar…' : 'Gerar ficheiro' }}
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
import PageHeader from '@/Components/plano/PageHeader.vue'
import InventoryAnalytics from '@/Components/charts/inventory-analytics.vue'
import {
  Download as ArrowDownTrayIcon,
  RefreshCw as ArrowPathIcon,
  ArrowRight as ArrowRightIcon,
  X as XMarkIcon,
} from '@lucide/vue'

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

/** The key numbers of the whole inventory, folded into one sentence under the title. */
const lede = computed(() => {
  const items = formatNumber(metrics.value.total_items)
  const value = formatCurrency(metrics.value.inventoryValue)
  const critical = Number(metrics.value.criticalAlerts || 0)

  if (!totalAlerts.value) {
    return `${items} posições de existências monitorizadas, ${value} em inventário e nenhum alerta activo.`
  }

  return `${items} posições de existências monitorizadas, ${value} em inventário e ${totalAlerts.value} ${totalAlerts.value === 1 ? 'alerta activo' : 'alertas activos'}${critical ? ` (${critical} ${critical === 1 ? 'crítico' : 'críticos'})` : ''}.`
})

const assuranceQueues = computed(() => [
  {
    label: 'Reposição de existências',
    value: Number(metrics.value.reorderAlerts || 0),
    detail: `${metrics.value.criticalAlerts || 0} posições críticas ou sem existências`,
    href: route('vap-inventory.reports.low-stock'),
  },
  {
    label: 'Validade de reagentes',
    value: Number(metrics.value.expiringAlerts || 0),
    detail: 'Lotes dentro da janela de validade controlada',
    href: route('vap-inventory.items.reagents.expiry'),
  },
  {
    label: 'Calibração de equipamento',
    value: Number(metrics.value.itemsNeedingCalibration || 0),
    detail: 'Equipamentos vencidos ou com intervenção devida',
    href: route('vap-inventory.items.calibration.schedule'),
  },
  {
    label: 'Livro de movimentos',
    value: null,
    detail: 'Entradas, saídas, ajustes, consumo e transferências',
    href: route('vap-inventory.reports.stock-movement'),
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

    if (!response.ok) throw new Error('Não foi possível gerar o relatório com os filtros seleccionados.')

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
