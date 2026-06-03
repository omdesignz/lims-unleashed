<template>
  <div class="needs-show-shell space-y-8" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="Procurement evidence"
      :title="needTitle"
      :description="need.justification || 'Sem justificação adicional.'"
    >
      <template #actions>
        <div class="flex flex-col gap-2 sm:flex-row">
          <Link :href="route('vap-inventory.needs.index')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white/70 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-200 dark:hover:bg-slate-800">
            Voltar
          </Link>
          <a
            :href="route('vap-inventory.needs.pdf', need.id)"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white/70 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            Exportar PDF
          </a>
          <button v-if="canApprove" type="button" class="rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-emerald-700/20 transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400" @click="approve">
            Aprovar
          </button>
          <button v-if="canApprove" type="button" class="rounded-2xl border border-rose-200 bg-white/70 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-200 dark:hover:bg-rose-500/20" @click="reject">
            Rejeitar
          </button>
          <button v-if="canConvertToOrder" type="button" class="rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-primary-600/20 transition hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400" @click="convertToOrder">
            Converter em pedido
          </button>
        </div>
      </template>

      <div class="mt-6 flex flex-wrap items-center gap-2">
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600 dark:bg-slate-900 dark:text-slate-300">{{ need.reference }}</span>
        <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass">{{ statusLabel }}</span>
      </div>
    </ModuleHero>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <ModuleCard>
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Escopo quantitativo</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Quantidade solicitada, aprovada e ainda pendente na necessidade.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right dark:bg-slate-950/50">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Unidades monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">{{ quantityScopeTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="quantityScopeChartOptions" :series="quantityScopeChartSeries" />
        </div>
      </ModuleCard>

      <div class="grid gap-6">
        <ModuleCard>
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Mix de valor</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Distribuição do valor estimado pelos itens da necessidade.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-sm font-medium text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
              {{ itemValueMixTotal }} AOA
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="itemValueMixChartOptions" :series="itemValueMixChartSeries" />
          </div>
        </ModuleCard>

        <ModuleCard>
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pulso de governação</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Itens, prazo, estado de conversão e valor financeiro estimado.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="governancePulseChartOptions" :series="governancePulseChartSeries" />
          </div>
        </ModuleCard>
      </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
      <ModuleCard title="Itens aprovados para aquisição">
        <div class="mt-6 space-y-4">
          <article v-for="item in need.items" :key="item.id" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-800 dark:bg-slate-950/45">
            <div class="grid gap-4 md:grid-cols-4">
              <div class="md:col-span-2">
                <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ item.inventory_item?.name }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400">{{ item.inventory_item?.code || 'Sem código' }}</div>
              </div>
              <div class="text-sm text-slate-600 dark:text-slate-300">
                <div>Solicitado: <span class="font-semibold text-slate-900 dark:text-white">{{ item.quantity_requested }}</span></div>
                <div>Aprovado: <span class="font-semibold text-slate-900 dark:text-white">{{ item.quantity_approved || '—' }}</span></div>
              </div>
              <div class="text-sm text-slate-600 dark:text-slate-300">
                <div>Armazém: <span class="font-semibold text-slate-900 dark:text-white">{{ item.warehouse?.name || 'A definir' }}</span></div>
                <div>Preço estimado: <span class="font-semibold text-slate-900 dark:text-white">{{ formatMoney(item.estimated_unit_price) }}</span></div>
              </div>
            </div>
            <p v-if="item.notes" class="mt-3 text-sm text-slate-500 dark:text-slate-400">{{ item.notes }}</p>
          </article>
        </div>
      </ModuleCard>

      <div class="space-y-6">
        <ModuleCard title="Rastreabilidade">
          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Solicitante</dt>
              <dd class="font-medium text-slate-900 dark:text-white">{{ need.requested_by?.name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Aprovador</dt>
              <dd class="font-medium text-slate-900 dark:text-white">{{ need.approved_by?.name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Submetida em</dt>
              <dd class="font-medium text-slate-900 dark:text-white">{{ formatDateTime(need.submitted_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Aprovada em</dt>
              <dd class="font-medium text-slate-900 dark:text-white">{{ formatDateTime(need.approved_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500 dark:text-slate-400">Pedido criado</dt>
              <dd class="font-medium text-slate-900 dark:text-white">{{ need.inventory_order?.reference || '—' }}</dd>
            </div>
          </dl>
        </ModuleCard>

        <ModuleCard v-if="canApprove || canConvertToOrder" title="Ação de gestão">
          <BaseTextarea v-model="actionForm.approval_notes" rows="4" placeholder="Notas de aprovação, rejeição ou instruções para a compra." />

          <div v-if="canApprove" class="mt-4 space-y-3">
            <div v-for="item in actionForm.items" :key="item.id" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
              <div class="text-sm text-slate-600 dark:text-slate-300">{{ item.name }}</div>
              <BaseInput v-model="item.quantity_approved" type="number" min="1" />
            </div>
          </div>

          <div v-if="canConvertToOrder" class="mt-4 space-y-3">
            <comboboxEnhanced
              v-model="selectedSupplierOption"
              :has-error="conversionForm.errors.supplier_id"
              :options="supplierOptions"
              placeholder="Pesquisar fornecedor aprovado"
            />
            <p v-if="conversionForm.errors.supplier_id" class="text-xs text-rose-600 dark:text-rose-300">
              {{ conversionForm.errors.supplier_id }}
            </p>
            <div
              v-if="selectedSupplierAssessment"
              class="rounded-2xl border px-4 py-4 text-sm"
              :class="supplierAssessmentPanelClass"
            >
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <div class="font-semibold">Avaliação do fornecedor</div>
                  <div class="mt-1 text-xs opacity-80">
                    Estado {{ supplierAssessmentStatus }} · Risco {{ supplierAssessmentRisk }}
                  </div>
                </div>
                <div class="text-right text-xs opacity-80">
                  <div>Score {{ selectedSupplierAssessment.total_score ?? '—' }}</div>
                  <div>Próxima revisão {{ supplierAssessmentReviewLabel }}</div>
                </div>
              </div>
              <p v-if="!selectedSupplierAssessment.approved_supplier" class="mt-3 text-xs font-medium">
                Este fornecedor não está marcado como aprovado. Reveja a avaliação antes de concluir a conversão.
              </p>
            </div>
            <div
              v-else-if="selectedSupplier"
              class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100"
            >
              Este fornecedor ainda não tem avaliação registada. Recomenda-se revisão antes de concluir a compra.
            </div>
            <div class="grid gap-3 md:grid-cols-2">
              <BaseInput v-model="conversionForm.date" type="date" />
              <BaseInput v-model="conversionForm.expected_date" type="date" />
            </div>
          </div>
        </ModuleCard>
      </div>
    </section>
  </div>
</template>

<script setup>
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseTextarea from '@/Components/base/BaseTextarea.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import ModuleCard from '@/Components/base/ModuleCard.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { Link, useForm } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  need: Object,
  canApprove: Boolean,
  canConvertToOrder: Boolean,
  suppliers: Array,
  charts: {
    type: Object,
    default: () => ({})
  },
})

const isDarkMode = ref(false)
const selectedSupplierOption = ref(null)
let themeObserver

const chartTextColor = computed(() => isDarkMode.value ? '#cbd5e1' : '#475569')
const chartGridColor = computed(() => isDarkMode.value ? '#1e293b' : '#e2e8f0')
const chartTooltipTheme = computed(() => isDarkMode.value ? 'dark' : 'light')

const syncDarkMode = () => {
  if (typeof document === 'undefined') {
    return
  }

  isDarkMode.value = document.documentElement.classList.contains('dark')
}

const quantityScopeChartSeries = computed(() => [
  {
    name: 'Quantidade',
    data: props.charts?.quantity_scope?.series || []
  }
])

const quantityScopeTotal = computed(() =>
  (props.charts?.quantity_scope?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
)

const itemValueMixChartSeries = computed(() => props.charts?.item_value_mix?.series || [])

const itemValueMixTotal = computed(() =>
  itemValueMixChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0).toLocaleString('pt-PT')
)

const governancePulseChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.governance_pulse?.series || []
  }
])

const needTitle = computed(() => {
  const department = props.need.department?.name || 'Departamento'

  if (!props.need.lab) {
    return department
  }

  return `${department} · ${props.need.lab.name}`
})

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
      borderRadius: 10,
      distributed: true,
      columnWidth: '48%'
    }
  },
  colors: ['#0f172a', '#16a34a', '#f59e0b'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.quantity_scope?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    }
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false }
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
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom',
    labels: { colors: chartTextColor.value },
  },
  stroke: {
    colors: [isDarkMode.value ? '#020617' : '#ffffff']
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
      borderRadius: 10,
      distributed: true,
      columnWidth: '52%'
    }
  },
  colors: ['#334155', '#f59e0b', '#0891b2', '#16a34a'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.governance_pulse?.labels || [],
    axisBorder: { color: chartGridColor.value },
    axisTicks: { color: chartGridColor.value },
    labels: { style: { colors: chartTextColor.value, fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0),
      style: { colors: chartTextColor.value },
    }
  },
  grid: {
    borderColor: chartGridColor.value,
    strokeDashArray: 4
  },
  tooltip: { theme: chartTooltipTheme.value },
  legend: { show: false }
}))

const actionForm = useForm({
  approval_notes: props.need.approval_notes || '',
  items: props.need.items.map((item) => ({
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

const statusClass = computed(() => ({
  'bg-amber-50 text-amber-700': props.need.status === 'submitted',
  'bg-emerald-50 text-emerald-700': props.need.status === 'approved' || props.need.status === 'fulfilled',
  'bg-rose-50 text-rose-700': props.need.status === 'rejected',
  'bg-cyan-50 text-cyan-700': props.need.status === 'ordered' || props.need.status === 'partially_fulfilled',
  'bg-slate-100 text-slate-600': !['submitted', 'approved', 'rejected', 'ordered', 'partially_fulfilled', 'fulfilled'].includes(props.need.status),
}))

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
    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200'
  }

  if (['suspended', 'rejected'].includes(selectedSupplierAssessment.value.status) || selectedSupplierAssessment.value.risk_level === 'critical') {
    return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-100'
  }

  if (selectedSupplierAssessment.value.status === 'conditional' || selectedSupplierAssessment.value.risk_level === 'high') {
    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100'
  }

  return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-100'
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

const formatMoney = (value) => value ? new Intl.NumberFormat('pt-PT', { style: 'currency', currency: 'AOA' }).format(value) : '—'
const formatDateTime = (value) => value ? new Date(value).toLocaleString('pt-PT') : '—'
const formatDate = (value) => value ? new Date(value).toLocaleDateString('pt-PT') : '—'

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

<style scoped>
.needs-show-shell :deep(input[type='date']) {
  color-scheme: light;
}

:global(.dark) .needs-show-shell :deep(input[type='date']) {
  color-scheme: dark;
}

.needs-show-shell :deep(.apexcharts-tooltip),
.needs-show-shell :deep(.apexcharts-menu) {
  border-radius: 0.875rem;
  border-color: rgb(226 232 240);
  box-shadow: 0 20px 45px rgb(15 23 42 / 0.14);
}
</style>
