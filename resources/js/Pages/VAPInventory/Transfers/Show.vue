<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TruckIcon class="h-7 w-7 text-blue-900" />
            Transferência #{{ transfer.id }}
          </h1>
          <p class="mt-2 text-gray-600">
            Detalhes da Transferência e Estado
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="[
            'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
            statusClass
          ]">
            {{ transferStatus }}
          </span>
          <button
            @click="goBack"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            
            Voltar
          </button>
        </div>
      </div>
    </div>

    <section class="grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
      <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">Fluxo de quantidade</h2>
            <p class="mt-1 text-sm text-gray-500">
              Comparação imediata entre a carga da transferência e o saldo dos dois armazéns.
            </p>
          </div>
          <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Unidades monitorizadas</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ quantityFlowTotal }}</p>
          </div>
        </div>

        <div class="mt-6">
          <apexchart type="bar" height="300" :options="quantityFlowChartOptions" :series="quantityFlowChartSeries" />
        </div>
      </article>

      <div class="grid gap-6">
        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pressão temporal</h2>
              <p class="mt-1 text-sm text-gray-500">Tempo em curso, expectativa e atraso desta transferência.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-800">
              {{ timingPressureTotal }} dias
            </span>
          </div>

          <div class="mt-6">
            <apexchart type="donut" height="300" :options="timingPressureChartOptions" :series="timingPressureChartSeries" />
          </div>
        </article>

        <article class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          <div class="flex items-start justify-between gap-4">
            <div>
              <h2 class="text-lg font-semibold text-gray-900">Pulso de execução</h2>
              <p class="mt-1 text-sm text-gray-500">Gap de destino e margem operacional para concluir ou cancelar.</p>
            </div>
          </div>

          <div class="mt-6">
            <apexchart type="bar" height="250" :options="executionPulseChartOptions" :series="executionPulseChartSeries" />
          </div>
        </article>
      </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- TRANSFER DETAILS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
            Detalhes da Transferência
          </h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Item Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Item
              </label>
              <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                  <CubeIcon class="h-5 w-5 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ transfer.item?.name }}</p>
                  <p class="text-xs text-gray-500">{{ transfer.item?.internal_code }}</p>
                </div>
              </div>
            </div>

            <!-- Quantity -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Quantidade
              </label>
              <div class="p-3 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-blue-900">{{ transfer.qty }}</p>
                <p class="text-xs text-gray-500">{{ transfer.item?.unit?.code || 'unidades' }}</p>
              </div>
            </div>

            <!-- Source Warehouse -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                De (Fonte)
              </label>
              <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                  <ArrowUpIcon class="h-5 w-5 text-red-600" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ transfer.source?.name }}</p>
                  <p class="text-xs text-gray-500">{{ transfer.source?.location?.name || 'N/A' }}</p>
                  <p v-if="sourceStock" class="text-xs text-gray-500 mt-1">
                    Estoque Actual: {{ sourceStock.qty_available }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Destination Warehouse -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Para (Destino)
              </label>
              <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                  <ArrowDownIcon class="h-5 w-5 text-green-600" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ transfer.destination?.name }}</p>
                  <p class="text-xs text-gray-500">{{ transfer.destination?.location?.name || 'N/A' }}</p>
                  <p v-if="destinationStock" class="text-xs text-gray-500 mt-1">
                    Estoque Actual: {{ destinationStock.qty_available }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Dates -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Datas
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Enviado:</span>
                  <span class="font-medium text-gray-900">{{ formatDate(transfer.sent_date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Esperado:</span>
                  <span class="font-medium text-gray-900">{{ formatDate(transfer.expected_date) || 'Não definido' }}</span>
                </div>
                <div v-if="transfer.received_date" class="flex justify-between text-sm">
                  <span class="text-gray-600">Recebido:</span>
                  <span class="font-medium text-green-600">{{ formatDate(transfer.received_date) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Criado:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(transfer.created_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Status Information -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Informações de Estado
              </label>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Estado:</span>
                  <span :class="statusClass">{{ transferStatus }}</span>
                </div>
                <div v-if="transfer.updated_at" class="flex justify-between text-sm">
                  <span class="text-gray-600">Última Actualização:</span>
                  <span class="font-medium text-gray-900">{{ formatDateTime(transfer.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Observations -->
          <div v-if="transfer.obs" class="mt-6 space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              Observações
            </label>
            <div class="p-3 bg-gray-50 rounded-lg">
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ transfer.obs }}</p>
            </div>
          </div>
        </div>

        <!-- RECEIVE TRANSFER SECTION -->
        <div v-if="canReceive" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <CheckCircleIcon class="h-5 w-5 text-blue-900" />
            Receber Transferência
          </h2>
          
          <form @submit.prevent="receiveTransfer" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Received Date -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Data de Recebimento
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="date"
                  v-model="receiveForm.received_date"
                  :max="maxDate"
                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
                  required
                />
              </div>

              <!-- Actual Quantity -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  Quantidade Actual Recebida
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <input
                    type="number"
                    v-model="receiveForm.actual_qty"
                    :min="1"
                    :max="transfer.qty"
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
                    required
                  />
                  <span class="text-sm text-gray-500 whitespace-nowrap">
                    / {{ transfer.qty }} esperado
                  </span>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                Observações
              </label>
              <textarea
                v-model="receiveForm.notes"
                rows="3"
                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
                placeholder="Qualquer observação sobre o recebimento..."
              ></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="cancelTransfer"
                :disabled="processing"
                :class="[
                  'rounded-lg px-4 py-2.5 text-sm font-semibold transition-all duration-200',
                  processing
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                ]"
              >
                Cancelar Transferência
              </button>
              <button
                type="submit"
                :disabled="processing || !isReceiveFormValid"
                :class="[
                  'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                  processing || !isReceiveFormValid
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2'
                ]"
              >
                <CheckCircleIcon class="h-5 w-5" />
                {{ processing ? 'Processando...' : 'Marcar como Recebida' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Acções
          </h3>
          <div class="space-y-3">
            <!-- Print Button -->
            <button 
              @click="printTransfer"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <PrinterIcon class="h-5 w-5" />
              Imprimir Transferência
            </button>

            <!-- Export Button -->
            <button 
              @click="exportTransfer"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <ArrowDownTrayIcon class="h-5 w-5" />
              Exportar
            </button>

            <!-- View Transactions -->
            <button 
              @click="viewTransactions"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <ArrowsRightLeftIcon class="h-5 w-5" />
              
              Visualizar Transações
            </button>
          </div>
        </div>

        <!-- STATUS TIMELINE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            Linha do Tempo do Transferência
          </h3>
          <div class="space-y-4">
            <div class="flex items-start">
              <div class="flex-shrink-0 h-6 w-6 rounded-full bg-green-500 flex items-center justify-center">
                <CheckIcon class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Transferência Criada</p>
                <p class="text-xs text-gray-500">{{ formatDateTime(transfer.created_at) }}</p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div :class="[
                'flex-shrink-0 h-6 w-6 rounded-full flex items-center justify-center',
                transfer.sent_date ? 'bg-green-500' : 'bg-gray-300'
              ]">
                <CheckIcon v-if="transfer.sent_date" class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Transferência Enviada</p>
                <p class="text-xs text-gray-500">
                  {{ transfer.sent_date ? formatDate(transfer.sent_date) : 'Pendente' }}
                </p>
              </div>
            </div>
            
            <div class="flex items-start">
              <div :class="[
                'flex-shrink-0 h-6 w-6 rounded-full flex items-center justify-center',
                transfer.received_date ? 'bg-green-500' : 'bg-gray-300'
              ]">
                <CheckIcon v-if="transfer.received_date" class="h-3 w-3 text-white" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900">Transferência Recebida</p>
                <p class="text-xs text-gray-500">
                  {{ transfer.received_date ? formatDate(transfer.received_date) : 'Pendente' }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- STOCK INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            Informações do Estoque
          </h3>
          <div class="space-y-3">
            <div>
              <p class="text-sm font-medium text-gray-600">Armazém de Origem</p>
              <p v-if="sourceStock" class="mt-1 text-lg font-bold text-gray-900">
                {{ sourceStock.qty_available }} UN
              </p>
              <p v-else class="mt-1 text-sm text-gray-500">Sem informações sobre o estoque</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600">Armazém de Destino</p>
              <p v-if="destinationStock" class="mt-1 text-lg font-bold text-gray-900">
                {{ destinationStock.qty_available }} UN
              </p>
              <p v-else class="mt-1 text-sm text-gray-500">Sem informações sobre o estoque</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import {
  TruckIcon,
  ArrowLeftIcon,
  ClipboardDocumentListIcon,
  CubeIcon,
  ArrowUpIcon,
  ArrowDownIcon,
  CheckCircleIcon,
  PrinterIcon,
  ArrowDownTrayIcon,
  ArrowsRightLeftIcon,
  ClockIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  transfer: {
    type: Object,
    required: true
  },
  sourceStock: {
    type: Object,
    default: null
  },
  destinationStock: {
    type: Object,
    default: null
  },
  canReceive: {
    type: Boolean,
    default: false
  },
  canCancel: {
    type: Boolean,
    default: false
  },
  charts: {
    type: Object,
    default: () => ({})
  }
})

const processing = ref(false)
const maxDate = new Date().toISOString().split('T')[0]

const quantityFlowChartSeries = computed(() => [
  {
    name: 'Quantidade',
    data: props.charts?.quantity_flow?.series || []
  }
])

const quantityFlowTotal = computed(() =>
  (props.charts?.quantity_flow?.series || []).reduce((sum, value) => sum + Number(value || 0), 0)
)

const timingPressureChartSeries = computed(() => props.charts?.timing_pressure?.series || [])

const timingPressureTotal = computed(() =>
  timingPressureChartSeries.value.reduce((sum, value) => sum + Number(value || 0), 0)
)

const executionPulseChartSeries = computed(() => [
  {
    name: 'Indicador',
    data: props.charts?.execution_pulse?.series || []
  }
])

const quantityFlowChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '48%'
    }
  },
  colors: ['#0f172a', '#dc2626', '#16a34a'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.quantity_flow?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const timingPressureChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  labels: props.charts?.timing_pressure?.labels || [],
  colors: ['#2563eb', '#f59e0b', '#dc2626'],
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

const executionPulseChartOptions = computed(() => ({
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit'
  },
  plotOptions: {
    bar: {
      borderRadius: 8,
      distributed: true,
      columnWidth: '52%'
    }
  },
  colors: ['#7c3aed', '#0f766e', '#334155'],
  dataLabels: { enabled: false },
  xaxis: {
    categories: props.charts?.execution_pulse?.labels || [],
    labels: { style: { fontSize: '12px' } }
  },
  yaxis: {
    labels: {
      formatter: (value) => Number(value || 0).toFixed(0)
    }
  },
  grid: {
    borderColor: '#e5e7eb',
    strokeDashArray: 4
  },
  legend: { show: false }
}))

const receiveForm = useForm({
  received_date: maxDate,
  actual_qty: props.transfer.qty,
  notes: ''
})

const transferStatus = computed(() => {
  if (props.transfer.deleted_at) return 'Cancelada'
  if (props.transfer.received_date) return 'Recebida'
  if (props.transfer.sent_date) return 'Em Trânsito'
  return 'Pendente'
})

const statusClass = computed(() => {
  const classMap = {
    'Cancelled': 'bg-red-100 text-red-800 ring-red-600/20',
    'Received': 'bg-green-100 text-green-800 ring-green-600/20',
    'In Transit': 'bg-blue-100 text-blue-800 ring-blue-600/20',
    'Pending': 'bg-yellow-100 text-yellow-800 ring-yellow-600/20'
  }
  return classMap[transferStatus.value] || 'bg-gray-100 text-gray-800 ring-gray-600/20'
})

const isReceiveFormValid = computed(() => {
  return receiveForm.received_date && 
         receiveForm.actual_qty > 0 && 
         receiveForm.actual_qty <= props.transfer.qty
})

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function receiveTransfer() {
  if (!confirm('Tem a certeza que deseja receber esta transferência?')) {
    return
  }

  processing.value = true
  receiveForm.post(route('vap-inventory.transfers.receive', props.transfer.id), {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => processing.value = false
  })
}

function cancelTransfer() {
  if (!confirm('Tem a certeza que deseja cancelar esta transferência? O estoque será devolvido para o armazém de origem.')) {
    return
  }

  processing.value = true
  router.post(route('vap-inventory.transfers.cancel', props.transfer.id), {}, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => processing.value = false
  })
}

function printTransfer() {
  window.print()
}

function exportTransfer() {
  window.print()
}

function viewTransactions() {
  // Navigate to transactions related to this transfer
  router.visit(route('vap-inventory.reports.stock-movement', {
    item_id: props.transfer.item_id,
    search: `Transfer #${props.transfer.id}`
  }))
}

function goBack() {
  router.visit(route('vap-inventory.transfers.index'))
}
</script>
