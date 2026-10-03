<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[color:var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Procurement evidence</span>
            <span class="ds-chip">
              <span class="lims-status-dot" :class="statusDotClass" />
              {{ statusLabel }}
            </span>
            <span class="ds-chip">{{ need.reference }}</span>
          </div>
          <h1 class="ds-heading mt-3 text-2xl">{{ needTitle }}</h1>
          <p class="ds-copy mt-2 text-sm">{{ need.justification || 'Sem justificação adicional.' }}</p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end">
          <Link :href="route('vap-inventory.needs.index')" class="ds-button ds-button-secondary">
            Voltar
          </Link>
          <a :href="route('vap-inventory.needs.pdf', need.id)" target="_blank" rel="noopener noreferrer" class="ds-button ds-button-secondary">
            Exportar PDF
          </a>
          <button v-if="canApprove" type="button" class="ds-button ds-button-primary" :disabled="actionForm.processing" @click="approve">
            Aprovar
          </button>
          <button v-if="canApprove" type="button" class="ds-button ds-button-danger" :disabled="actionForm.processing" @click="reject">
            Rejeitar
          </button>
          <button v-if="canConvertToOrder" type="button" class="ds-button ds-button-primary" :disabled="conversionForm.processing" @click="convertToOrder">
            Converter em pedido
          </button>
        </div>
      </div>

      <dl class="grid grid-cols-2 divide-x divide-y divide-[color:var(--ds-border)] md:grid-cols-4 xl:grid-cols-6 xl:divide-y-0">
        <div v-for="metric in summaryCards" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[color:var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass" />
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold" :class="metric.valueClass">{{ metric.value }}</dd>
          <p class="mt-1 truncate text-xs font-semibold text-[color:var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="ds-panel overflow-hidden">
        <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
          <div>
            <h2 class="ds-heading text-base">Âmbito de aprovação</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Linhas solicitadas, aprovadas e pendentes. Quantidades mantidas por unidade.</p>
          </div>
          <span class="ds-chip">{{ itemRows.length }} linhas</span>
        </div>
        <div class="p-4">
          <apexchart type="bar" height="300" :options="quantityScopeChartOptions" :series="quantityScopeChartSeries" />
        </div>
      </article>

      <div class="grid gap-4">
        <article class="ds-panel overflow-hidden">
          <div class="flex items-start justify-between gap-3 border-b border-[color:var(--ds-border)] px-5 py-4">
            <div>
              <h2 class="ds-heading text-base">Mix de valor</h2>
              <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Distribuição do valor estimado por item.</p>
            </div>
            <span class="ds-chip">{{ itemValueMixTotal }}</span>
          </div>
          <div class="p-4">
            <apexchart type="donut" height="300" :options="itemValueMixChartOptions" :series="itemValueMixChartSeries" />
          </div>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Pulso de governação</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Itens, prazo, conversão e valor financeiro estimado.</p>
          </div>
          <div class="p-4">
            <apexchart type="bar" height="250" :options="governancePulseChartOptions" :series="governancePulseChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1fr_22rem]">
      <div class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="ds-table-summary px-5 py-4">
            <div class="flex items-start gap-3">
              <div>
                <h2 class="ds-heading text-base">Itens aprovados para aquisição</h2>
                <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Materiais, quantidade, armazém e preço estimado.</p>
              </div>
            </div>
          </div>

          <div v-if="itemRows.length" class="ds-table-shell overflow-x-auto">
            <DataTable class="min-w-[58rem]">
              <thead class="ds-table-head">
                <tr>
                  <th class="ds-table-cell text-left">Item</th>
                  <th class="ds-table-cell text-left">Quantidade</th>
                  <th class="ds-table-cell text-left">Armazém</th>
                  <th class="ds-table-cell text-left">Preço estimado</th>
                  <th class="ds-table-cell text-left">Notas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in itemRows" :key="item.id" class="ds-table-row">
                  <td class="ds-table-cell align-top">
                    <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ item.inventory_item?.name || 'Item N/A' }}</p>
                    <p class="mt-1 font-mono text-xs text-[color:var(--ds-text-soft)]">{{ item.inventory_item?.code || 'Sem código' }}</p>
                  </td>
                  <td class="ds-table-cell align-top text-sm text-[color:var(--ds-text)]">
                    <div>Solicitado: <span class="font-bold">{{ formatQuantity(item.quantity_requested) }} {{ item.inventory_item?.unit?.code }}</span></div>
                    <div>Aprovado: <span class="font-bold">{{ item.quantity_approved ? `${formatQuantity(item.quantity_approved)} ${item.inventory_item?.unit?.code || ''}` : '—' }}</span></div>
                  </td>
                  <td class="ds-table-cell align-top text-sm font-semibold text-[color:var(--ds-text)]">{{ item.warehouse?.name || 'A definir' }}</td>
                  <td class="ds-table-cell align-top text-sm font-semibold text-[color:var(--ds-text)]">{{ formatMoney(item.estimated_unit_price) }}</td>
                  <td class="ds-table-cell max-w-xs align-top text-sm text-[color:var(--ds-text-muted)]">
                    <span class="line-clamp-2">{{ item.notes || '—' }}</span>
                  </td>
                </tr>
              </tbody>
            </DataTable>
          </div>

          <div v-else class="p-5">
            <div class="ds-empty-state p-6 text-center">
              <p class="text-sm font-semibold text-[color:var(--ds-text-soft)]">Sem itens registados nesta necessidade.</p>
            </div>
          </div>
        </article>

        <article v-if="managementActionAvailable" class="ds-command-surface overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Acção de gestão</h2>
            <p class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">Aprovação, rejeição ou conversão para pedido de compra.</p>
          </div>

          <div class="space-y-4 p-5">
            <BaseTextarea v-model="actionForm.approval_notes" rows="4" label="Notas" placeholder="Notas de aprovação, rejeição ou instruções para a compra." />

            <div v-if="canApprove" class="ds-table-shell overflow-x-auto">
              <DataTable class="min-w-[36rem]">
                <thead class="ds-table-head">
                  <tr>
                    <th class="ds-table-cell text-left">Item</th>
                    <th class="ds-table-cell text-left">Quantidade aprovada</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in actionForm.items" :key="item.id" class="ds-table-row">
                    <td class="ds-table-cell text-sm font-semibold text-[color:var(--ds-text)]">{{ item.name }}</td>
                    <td class="ds-table-cell">
                      <BaseInput v-model="item.quantity_approved" type="number" min="0.0001" step="0.0001" :max="item.quantity_requested" />
                    </td>
                  </tr>
                </tbody>
              </DataTable>
            </div>

            <div v-if="canConvertToOrder" class="space-y-4">
              <comboboxEnhanced
                v-model="selectedSupplierOption"
                :has-error="Boolean(conversionForm.errors.supplier_id)"
                :options="supplierOptions"
                placeholder="Pesquisar fornecedor aprovado"
              />
              <p v-if="conversionForm.errors.supplier_id" class="ds-field-error">{{ conversionForm.errors.supplier_id }}</p>

              <div v-if="selectedSupplierAssessment" class="ds-card border-l-4 p-4" :class="supplierAssessmentPanelClass">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <div class="text-sm font-bold text-[color:var(--ds-text)]">Avaliação do fornecedor</div>
                    <div class="mt-1 text-xs font-semibold text-[color:var(--ds-text-soft)]">
                      Estado {{ supplierAssessmentStatus }} · Risco {{ supplierAssessmentRisk }}
                    </div>
                  </div>
                  <div class="text-right text-xs font-semibold text-[color:var(--ds-text-soft)]">
                    <div>Score {{ selectedSupplierAssessment.total_score ?? '—' }}</div>
                    <div>Próxima revisão {{ supplierAssessmentReviewLabel }}</div>
                  </div>
                </div>
                <p v-if="!selectedSupplierAssessment.approved_supplier" class="mt-3 text-xs font-semibold text-amber-700 dark:text-amber-300">
                  Este fornecedor não está marcado como aprovado. Reveja a avaliação antes de concluir a conversão.
                </p>
              </div>

              <div v-else-if="selectedSupplier" class="ds-card border-l-4 border-amber-500 p-4">
                <p class="text-sm font-semibold text-[color:var(--ds-text)]">
                  Este fornecedor ainda não tem avaliação registada. Recomenda-se revisão antes de concluir a compra.
                </p>
              </div>

              <div class="grid gap-3 md:grid-cols-2">
                <BaseInput v-model="conversionForm.date" type="date" label="Data do pedido" />
                <BaseInput v-model="conversionForm.expected_date" type="date" label="Data esperada" />
              </div>
            </div>
          </div>
        </article>
      </div>

      <aside class="space-y-4">
        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Rastreabilidade</h2>
          </div>
          <dl class="divide-y divide-[color:var(--ds-border)]">
            <div v-for="field in traceabilityFields" :key="field.label" class="flex items-start justify-between gap-4 px-5 py-3 text-sm">
              <dt class="font-semibold text-[color:var(--ds-text-soft)]">{{ field.label }}</dt>
              <dd class="text-right font-bold text-[color:var(--ds-text)]">{{ field.value }}</dd>
            </div>
          </dl>
        </article>

        <article class="ds-panel overflow-hidden">
          <div class="border-b border-[color:var(--ds-border)] px-5 py-4">
            <h2 class="ds-heading text-base">Fluxo</h2>
          </div>
          <ol class="space-y-4 p-5">
            <li v-for="step in workflowSteps" :key="step.label" class="flex gap-3">
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-[color:var(--ds-panel-subtle)]">
                <span class="lims-status-dot" :class="step.dotClass" />
              </span>
              <div>
                <p class="text-sm font-bold text-[color:var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs text-[color:var(--ds-text-soft)]">{{ step.caption }}</p>
              </div>
            </li>
          </ol>
        </article>
      </aside>
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  need: {
    type: Object,
    required: true,
  },
  canApprove: {
    type: Boolean,
    default: false,
  },
  canConvertToOrder: {
    type: Boolean,
    default: false,
  },
  suppliers: {
    type: Array,
    default: () => [],
  },
  charts: {
    type: Object,
    default: () => ({}),
  },
})

const isDarkMode = ref(false)
const selectedSupplierOption = ref(null)
let themeObserver

const chartTextColor = computed(() => (isDarkMode.value ? '#cbd5e1' : '#475569'))
const chartGridColor = computed(() => (isDarkMode.value ? '#1e293b' : '#e2e8f0'))
const chartTooltipTheme = computed(() => (isDarkMode.value ? 'dark' : 'light'))

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

const quantityScopeChartSeries = computed(() => [
  {
    name: 'Linhas',
    data: props.charts?.quantity_scope?.series || [],
  },
])

const itemValueMixChartSeries = computed(() => props.charts?.item_value_mix?.series || [])

const itemValueMixRawTotal = computed(() => (
  itemValueMixChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0)
))

const itemValueMixTotal = computed(() => formatMoney(itemValueMixRawTotal.value))

const governancePulseChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.governance_pulse?.series || [],
  },
])

const needTitle = computed(() => {
  const department = props.need.department?.name || 'Departamento'

  if (!props.need.lab) {
    return department
  }

  return `${department} · ${props.need.lab.name}`
})

const itemRows = computed(() => props.need.items || [])

const approvedLineCount = computed(() => itemRows.value.filter((item) => Number(item.quantity_approved || 0) > 0).length)
const estimatedNeedValue = computed(() => itemRows.value.reduce((sum, item) => {
  const quantity = Number(item.quantity_approved || item.quantity_requested || 0)
  const unitPrice = Number(item.estimated_unit_price || 0)

  return sum + (quantity * unitPrice)
}, 0))

const quantityScopeChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: {
    bar: {
      borderRadius: 6,
      distributed: true,
      columnWidth: '48%',
    },
  },
  colors: ['#0f172a', '#16a34a', '#f59e0b'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.quantity_scope?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    },
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const itemValueMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  labels: props.charts?.item_value_mix?.labels || [],
  colors: ['#0891b2', '#0f766e', '#4f46e5', '#f59e0b', '#dc2626', '#334155'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`,
  },
  legend: {
    position: 'bottom',
    labels: { colors: chartTextColor.value },
  },
  stroke: {
    colors: [isDarkMode.value ? '#020617' : '#ffffff'],
  },
  tooltip: { theme: chartTooltipTheme.value },
}))

const governancePulseChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
    background: 'transparent',
  },
  theme: { mode: isDarkMode.value ? 'dark' : 'light' },
  foreColor: chartTextColor.value,
  plotOptions: {
    bar: {
      borderRadius: 6,
      distributed: true,
      columnWidth: '52%',
    },
  },
  colors: ['#334155', '#f59e0b', '#0891b2', '#16a34a'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.governance_pulse?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } },
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    },
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4,
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false },
}))

const actionForm = useForm({
  approval_notes: props.need.approval_notes || '',
  items: itemRows.value.map((item) => ({
    id: item.id,
    name: item.inventory_item?.name || 'Item',
    quantity_approved: item.quantity_approved || item.quantity_requested,
  })),
})

const conversionForm = useForm({
  supplier_id: '',
  date: new Date().toISOString().slice(0, 10),
  expected_date: props.need.needed_by_date || '',
  reference: props.need.reference,
  obs: props.need.justification || '',
})

const supplierOptions = computed(() => props.suppliers.map((supplier) => ({
  value: supplier.id,
  label: supplier.address ? `${supplier.name} - ${supplier.address.substring(0, 30)}...` : supplier.name,
})))

watch(selectedSupplierOption, (supplier) => {
  conversionForm.supplier_id = supplier?.value || ''
})

const selectedSupplier = computed(() => props.suppliers.find((supplier) => String(supplier.id) === String(conversionForm.supplier_id)) ?? null)
const selectedSupplierAssessment = computed(() => selectedSupplier.value?.latest_assessment ?? null)

const statusLabel = computed(() => ({
  submitted: 'Submetida',
  approved: 'Aprovada',
  rejected: 'Rejeitada',
  ordered: 'Convertida em pedido',
  partially_fulfilled: 'Parcialmente satisfeita',
  fulfilled: 'Satisfeita',
}[props.need.status] ?? props.need.status))

const statusDotClass = computed(() => ({
  submitted: 'lims-status-dot-hold',
  approved: 'lims-status-dot-release',
  fulfilled: 'lims-status-dot-release',
  rejected: 'lims-status-dot-critical',
  ordered: 'lims-status-dot-instrument',
  partially_fulfilled: 'lims-status-dot-instrument',
}[props.need.status] ?? 'lims-status-dot-instrument'))

const summaryCards = computed(() => [
  {
    label: 'Itens',
    value: formatQuantity(itemRows.value.length),
    caption: 'Linhas da necessidade',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Solicitadas',
    value: formatQuantity(itemRows.value.length),
    caption: 'Linhas pedidas',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Aprovadas',
    value: formatQuantity(approvedLineCount.value),
    caption: 'Linhas aprovadas',
    dotClass: approvedLineCount.value ? 'lims-status-dot-release' : 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Valor',
    value: formatMoney(estimatedNeedValue.value),
    caption: 'Estimativa financeira',
    dotClass: 'lims-status-dot-instrument',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Prazo',
    value: formatDate(props.need.needed_by_date),
    caption: 'Necessário até',
    dotClass: 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
  {
    label: 'Pedido',
    value: props.need.inventory_order?.reference || '—',
    caption: props.need.inventory_order ? 'Convertido' : 'Sem pedido',
    dotClass: props.need.inventory_order ? 'lims-status-dot-release' : 'lims-status-dot-hold',
    valueClass: 'text-[color:var(--ds-text)]',
  },
])

const traceabilityFields = computed(() => [
  ['Solicitante', props.need.requested_by?.name || '—'],
  ['Aprovador', props.need.approved_by?.name || '—'],
  ['Submetida em', formatDateTime(props.need.submitted_at)],
  ['Aprovada em', formatDateTime(props.need.approved_at)],
  ['Pedido criado', props.need.inventory_order?.reference || '—'],
].map(([label, value]) => ({ label, value })))

const workflowSteps = computed(() => [
  {
    label: 'Submissão',
    caption: formatDateTime(props.need.submitted_at),
    dotClass: props.need.submitted_at ? 'lims-status-dot-release' : 'lims-status-dot-hold',
  },
  {
    label: 'Aprovação',
    caption: formatDateTime(props.need.approved_at),
    dotClass: props.need.approved_at ? 'lims-status-dot-release' : 'lims-status-dot-hold',
  },
  {
    label: 'Conversão',
    caption: props.need.inventory_order?.reference || 'A aguardar pedido',
    dotClass: props.need.inventory_order ? 'lims-status-dot-release' : 'lims-status-dot-hold',
  },
])

const managementActionAvailable = computed(() => props.canApprove || props.canConvertToOrder)

const supplierAssessmentStatus = computed(() => ({
  approved: 'Aprovado',
  conditional: 'Condicionado',
  suspended: 'Suspenso',
  rejected: 'Rejeitado',
}[selectedSupplierAssessment.value?.status] ?? 'Sem estado'))

const supplierAssessmentRisk = computed(() => ({
  low: 'Baixo',
  medium: 'Médio',
  high: 'Alto',
  critical: 'Crítico',
}[selectedSupplierAssessment.value?.risk_level] ?? 'Não classificado'))

const supplierAssessmentReviewLabel = computed(() => formatDate(selectedSupplierAssessment.value?.next_review_at))

const supplierAssessmentPanelClass = computed(() => {
  if (!selectedSupplierAssessment.value) {
    return 'border-[color:var(--ds-border-strong)]'
  }

  if (['suspended', 'rejected'].includes(selectedSupplierAssessment.value.status) || selectedSupplierAssessment.value.risk_level === 'critical') {
    return 'border-rose-500'
  }

  if (selectedSupplierAssessment.value.status === 'conditional' || selectedSupplierAssessment.value.risk_level === 'high') {
    return 'border-amber-500'
  }

  return 'border-emerald-500'
})

const approve = () => {
  actionForm.post(route('vap-inventory.needs.approve', props.need.id))
}

const reject = () => {
  actionForm.post(route('vap-inventory.needs.reject', props.need.id))
}

const convertToOrder = () => {
  conversionForm.post(route('vap-inventory.needs.convert-to-order', props.need.id))
}

function formatQuantity(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue)) {
    return '0'
  }

  return new Intl.NumberFormat('pt-PT', { maximumFractionDigits: 4 }).format(numericValue)
}

function formatMoney(value) {
  const numericValue = Number(value ?? 0)

  if (!Number.isFinite(numericValue) || numericValue === 0) {
    return '—'
  }

  return new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'AOA' }).format(numericValue)
}

const formatDateTime = (value) => (value ? new Date(value).toLocaleString('pt-PT') : '—')
const formatDate = (value) => (value ? new Date(value).toLocaleDateString('pt-PT') : '—')

onMounted(() => {
  syncDarkMode()

  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(syncDarkMode)
    themeObserver.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['class'],
    })
  }
})

onBeforeUnmount(() => {
  themeObserver?.disconnect()
})
</script>
