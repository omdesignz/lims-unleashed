<template>
  <div class="space-y-8">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ need.reference }}</span>
            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="statusClass">{{ statusLabel }}</span>
          </div>
          <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">{{ need.department?.name }}<span v-if="need.lab"> · {{ need.lab.name }}</span></h1>
          <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ need.justification || 'Sem justificação adicional.' }}</p>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
          <Link :href="route('vap-inventory.needs.index')" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Voltar
          </Link>
          <a
            :href="route('vap-inventory.needs.pdf', need.id)"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
          >
            Exportar PDF
          </a>
          <button v-if="canApprove" type="button" class="rounded-2xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800" @click="approve">
            Aprovar
          </button>
          <button v-if="canApprove" type="button" class="rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-50" @click="reject">
            Rejeitar
          </button>
          <button v-if="canConvertToOrder" type="button" class="rounded-2xl bg-cyan-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-cyan-800" @click="convertToOrder">
            Converter em pedido
          </button>
        </div>
      </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-slate-900">Escopo quantitativo</h2>
            <p class="mt-1 text-sm text-slate-500">
              Quantidade solicitada, aprovada e ainda pendente na necessidade.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Unidades monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ quantityScopeTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="quantityScopeChartOptions" :series="quantityScopeChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Mix de valor</h2>
              <p class="mt-1 text-sm text-slate-500">Distribuição do valor estimado pelos itens da necessidade.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-cyan-50 px-3 py-1 text-sm font-medium text-cyan-700">
              {{ itemValueMixTotal }} AOA
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="itemValueMixChartOptions" :series="itemValueMixChartSeries" />
          </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-slate-900">Pulso de governação</h2>
              <p class="mt-1 text-sm text-slate-500">Itens, prazo, estado de conversão e valor financeiro estimado.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="governancePulseChartOptions" :series="governancePulseChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Itens aprovados para aquisição</h2>
        <div class="mt-6 space-y-4">
          <article v-for="item in need.items" :key="item.id" class="rounded-2xl border border-slate-200 p-4">
            <div class="grid gap-4 md:grid-cols-4">
              <div class="md:col-span-2">
                <div class="text-sm font-semibold text-slate-900">{{ item.inventory_item?.name }}</div>
                <div class="text-xs text-slate-500">{{ item.inventory_item?.code || 'Sem código' }}</div>
              </div>
              <div class="text-sm text-slate-600">
                <div>Solicitado: <span class="font-semibold text-slate-900">{{ item.quantity_requested }}</span></div>
                <div>Aprovado: <span class="font-semibold text-slate-900">{{ item.quantity_approved || '—' }}</span></div>
              </div>
              <div class="text-sm text-slate-600">
                <div>Armazém: <span class="font-semibold text-slate-900">{{ item.warehouse?.name || 'A definir' }}</span></div>
                <div>Preço estimado: <span class="font-semibold text-slate-900">{{ formatMoney(item.estimated_unit_price) }}</span></div>
              </div>
            </div>
            <p v-if="item.notes" class="mt-3 text-sm text-slate-500">{{ item.notes }}</p>
          </article>
        </div>
      </div>

      <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-900">Rastreabilidade</h2>
          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Solicitante</dt>
              <dd class="font-medium text-slate-900">{{ need.requested_by?.name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Aprovador</dt>
              <dd class="font-medium text-slate-900">{{ need.approved_by?.name || '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Submetida em</dt>
              <dd class="font-medium text-slate-900">{{ formatDateTime(need.submitted_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Aprovada em</dt>
              <dd class="font-medium text-slate-900">{{ formatDateTime(need.approved_at) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Pedido criado</dt>
              <dd class="font-medium text-slate-900">{{ need.inventory_order?.reference || '—' }}</dd>
            </div>
          </dl>
        </section>

        <section v-if="canApprove || canConvertToOrder" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-900">Ação de gestão</h2>
          <textarea v-model="actionForm.approval_notes" rows="4" class="mt-4 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Notas de aprovação, rejeição ou instruções para a compra."></textarea>

          <div v-if="canApprove" class="mt-4 space-y-3">
            <div v-for="item in actionForm.items" :key="item.id" class="grid gap-3 md:grid-cols-[1fr_auto] md:items-center">
              <div class="text-sm text-slate-600">{{ item.name }}</div>
              <input v-model="item.quantity_approved" type="number" min="1" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
          </div>

          <div v-if="canConvertToOrder" class="mt-4 space-y-3">
            <select v-model="conversionForm.supplier_id" class="w-full rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
              <option value="">Selecione o fornecedor</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
            </select>
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
              class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800"
            >
              Este fornecedor ainda não tem avaliação registada. Recomenda-se revisão antes de concluir a compra.
            </div>
            <div class="grid gap-3 md:grid-cols-2">
              <input v-model="conversionForm.date" type="date" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
              <input v-model="conversionForm.expected_date" type="date" class="rounded-2xl border border-slate-300 px-4 py-2.5 text-sm">
            </div>
          </div>
        </section>
      </div>
    </section>
  </div>
</template>

<script setup>
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'

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

const quantityScopeChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
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
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const itemValueMixChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  labels: props.charts?.item_value_mix?.labels || [],
  colors: ['#0891b2', '#0f766e', '#4f46e5', '#f59e0b', '#dc2626', '#334155'],
  dataLabels: {
    enabled: true,
    formatter: (value) => `${Math.round(value)}%`
  },
  legend: {
    position: 'bottom'
  },
  stroke: {
    colors: ['#ffffff']
  }
}))

const governancePulseChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
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
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e2e8f0',
    strokeDashArray: 4
  },
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
    return 'border-slate-200 bg-slate-50 text-slate-700'
  }

  if (['suspended', 'rejected'].includes(selectedSupplierAssessment.value.status) || selectedSupplierAssessment.value.risk_level === 'critical') {
    return 'border-rose-200 bg-rose-50 text-rose-800'
  }

  if (selectedSupplierAssessment.value.status === 'conditional' || selectedSupplierAssessment.value.risk_level === 'high') {
    return 'border-amber-200 bg-amber-50 text-amber-800'
  }

  return 'border-emerald-200 bg-emerald-50 text-emerald-800'
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
</script>
