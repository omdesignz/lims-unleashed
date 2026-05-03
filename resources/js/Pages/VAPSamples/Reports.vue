<template>
  <div class="space-y-8">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900">
            <ChartBarSquareIcon class="h-7 w-7 text-blue-900" />
            {{ title || 'Relatórios de Amostras' }}
          </h1>
          <p class="mt-2 max-w-3xl text-sm text-gray-600">
            Consolidação operacional das amostras recebidas, descartes, tempos de análise e distribuição por estado.
          </p>
          <p class="mt-2 text-xs text-gray-500">
            Gerado em {{ formatDateTime(generatedAt) }}
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <a
            :href="sampleExportUrl"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
          >
            <ArrowDownTrayIcon class="h-4 w-4" />
            Exportar amostras
          </a>
          <a
            :href="discardExportUrl"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
          >
            <DocumentArrowDownIcon class="h-4 w-4" />
            Exportar descartes
          </a>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Total de amostras</p>
        <p class="mt-3 text-3xl font-bold text-gray-900">{{ summary.total_samples }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Completadas</p>
        <p class="mt-3 text-3xl font-bold text-green-600">{{ summary.completed_samples }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Por iniciar</p>
        <p class="mt-3 text-3xl font-bold text-amber-600">{{ summary.pending_samples }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Em progresso</p>
        <p class="mt-3 text-3xl font-bold text-blue-700">{{ summary.in_progress_samples }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Total de descartes</p>
        <p class="mt-3 text-3xl font-bold text-rose-600">{{ summary.total_discards }}</p>
      </div>
      <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-gray-500">Tempo médio</p>
        <p class="mt-3 text-3xl font-bold text-purple-600">{{ summary.avg_turnaround_hours }}h</p>
        <p class="mt-1 text-xs text-gray-500">Taxa de descarte: {{ summary.discard_rate }}%</p>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Data inicial</label>
          <input
            v-model="form.date_from"
            type="date"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Data final</label>
          <input
            v-model="form.date_to"
            type="date"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Estado</label>
          <select
            v-model="form.status"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Tipo de amostra</label>
          <select
            v-model="form.sample_type"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="type in sampleTypes" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Cliente</label>
          <select
            v-model="form.customer_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="customer in customers" :key="customer.id" :value="String(customer.id)">
              {{ customer.name }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Laboratório</label>
          <select
            v-model="form.lab_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="lab in labs" :key="lab.id" :value="String(lab.id)">
              {{ lab.name }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Departamento</label>
          <select
            v-model="form.department_id"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="department in departments" :key="department.id" :value="String(department.id)">
              {{ department.name }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">Método de descarte</label>
          <select
            v-model="form.discard_method"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900"
          >
            <option value="">Todos</option>
            <option v-for="method in discardMethods" :key="method" :value="method">
              {{ method }}
            </option>
          </select>
        </div>
      </div>

      <div class="mt-6 flex flex-wrap justify-end gap-3 border-t border-gray-200 pt-6">
        <button
          type="button"
          class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50"
          @click="resetFilters"
        >
          Redefinir
        </button>
        <button
          type="button"
          class="rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
          @click="applyFilters"
        >
          Aplicar filtros
        </button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Distribuição por estado</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in statusBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3"
          >
            <span class="text-sm font-medium text-gray-700">{{ getStatusLabel(item.label) }}</span>
            <span class="text-sm font-semibold text-gray-900">{{ item.total }}</span>
          </div>
          <p v-if="statusBreakdown.length === 0" class="text-sm text-gray-500">Sem dados para o filtro atual.</p>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Tipos de amostra</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in sampleTypeBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3"
          >
            <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
            <span class="text-sm font-semibold text-gray-900">{{ item.total }}</span>
          </div>
          <p v-if="sampleTypeBreakdown.length === 0" class="text-sm text-gray-500">Sem dados para o filtro atual.</p>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-900">Métodos de descarte</h2>
        <div class="mt-4 space-y-3">
          <div
            v-for="item in discardMethodBreakdown"
            :key="item.label"
            class="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3"
          >
            <span class="text-sm font-medium text-gray-700">{{ item.label }}</span>
            <span class="text-sm font-semibold text-gray-900">{{ item.total }}</span>
          </div>
          <p v-if="discardMethodBreakdown.length === 0" class="text-sm text-gray-500">Sem descartes para o filtro atual.</p>
        </div>
      </div>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
      <h2 class="text-lg font-semibold text-gray-900">Linha temporal de recebimento</h2>
      <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="item in sampleTimeline"
          :key="item.date"
          class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3"
        >
          <p class="text-sm font-medium text-gray-700">{{ formatDate(item.date) }}</p>
          <p class="mt-1 text-2xl font-bold text-blue-900">{{ item.total }}</p>
        </div>
        <p v-if="sampleTimeline.length === 0" class="text-sm text-gray-500">Sem movimentação no período selecionado.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Amostras</h2>
          <p class="mt-1 text-sm text-gray-500">
            {{ samples.total }} registros filtrados
          </p>
        </div>

        <div v-if="samples.data.length === 0" class="p-6 text-sm text-gray-500">
          Nenhuma amostra encontrada para os filtros informados.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Código</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Recebida</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="sample in samples.data" :key="sample.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-blue-900">{{ sample.code }}</div>
                  <div class="text-sm text-gray-600">{{ sample.name }}</div>
                  <div class="text-xs text-gray-500">{{ sample.sample_type }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ sample.customer?.name || 'N/A' }}
                </td>
                <td class="px-6 py-4">
                  <span :class="statusBadgeClass(sample.status)" class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold">
                    {{ getStatusLabel(sample.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ formatDateTime(sample.received_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="samples.data.length > 0" class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
          <p class="text-sm text-gray-500">
            Mostrando {{ samples.from }}-{{ samples.to }} de {{ samples.total }}
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              :disabled="!samples.prev_page_url"
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:text-gray-400"
              @click="visitPage(samples.prev_page_url)"
            >
              Anterior
            </button>
            <button
              type="button"
              :disabled="!samples.next_page_url"
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:text-gray-400"
              @click="visitPage(samples.next_page_url)"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-900">Descartes</h2>
          <p class="mt-1 text-sm text-gray-500">
            {{ discards.total }} registros filtrados
          </p>
        </div>

        <div v-if="discards.data.length === 0" class="p-6 text-sm text-gray-500">
          Nenhum descarte encontrado para os filtros informados.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amostra</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Método</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Qtd.</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Data</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="discard in discards.data" :key="discard.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-blue-900">{{ discard.sample?.code || 'N/A' }}</div>
                  <div class="text-sm text-gray-600">{{ discard.sample?.name || 'Amostra removida' }}</div>
                  <div class="text-xs text-gray-500">{{ discard.sample?.customer?.name || 'Sem cliente' }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ discard.discard_method }}
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-rose-600">
                  {{ discard.qty }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">
                  {{ formatDateTime(discard.discarded_at) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="discards.data.length > 0" class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
          <p class="text-sm text-gray-500">
            Mostrando {{ discards.from }}-{{ discards.to }} de {{ discards.total }}
          </p>
          <div class="flex gap-2">
            <button
              type="button"
              :disabled="!discards.prev_page_url"
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:text-gray-400"
              @click="visitPage(discards.prev_page_url)"
            >
              Anterior
            </button>
            <button
              type="button"
              :disabled="!discards.next_page_url"
              class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 disabled:cursor-not-allowed disabled:text-gray-400"
              @click="visitPage(discards.next_page_url)"
            >
              Próxima
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ArrowDownTrayIcon,
  ChartBarSquareIcon,
  DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  title: {
    type: String,
    default: 'Relatórios de Amostras',
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  samples: {
    type: Object,
    default: () => ({ data: [] }),
  },
  discards: {
    type: Object,
    default: () => ({ data: [] }),
  },
  statusBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTypeBreakdown: {
    type: Array,
    default: () => [],
  },
  discardMethodBreakdown: {
    type: Array,
    default: () => [],
  },
  sampleTimeline: {
    type: Array,
    default: () => [],
  },
  customers: {
    type: Array,
    default: () => [],
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  discardMethods: {
    type: Array,
    default: () => [],
  },
  sampleTypes: {
    type: Array,
    default: () => [],
  },
  generatedAt: {
    type: String,
    default: '',
  },
})

const statusOptions = [
  { value: 'POR_INICIAR', label: 'Por iniciar' },
  { value: 'EN_PROGRESO', label: 'Em progresso' },
  { value: 'COMPLETADO', label: 'Completado' },
  { value: 'CANCELADO', label: 'Cancelado' },
  { value: 'EN_PAUSA', label: 'Em pausa' },
]

const form = useForm({
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || '',
  status: props.filters.status || '',
  sample_type: props.filters.sample_type || '',
  customer_id: props.filters.customer_id || '',
  lab_id: props.filters.lab_id || '',
  department_id: props.filters.department_id || '',
  discard_method: props.filters.discard_method || '',
})

const sampleExportUrl = computed(() => buildUrl(route('vap_samples.samples.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  status: form.status,
}))

const discardExportUrl = computed(() => buildUrl(route('vap_samples.discards.export'), {
  start_date: form.date_from,
  end_date: form.date_to,
  method: form.discard_method,
}))

function applyFilters() {
  router.get(route('vap_samples.reports'), normalizeFilters(), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  form.reset()
  applyFilters()
}

function visitPage(url) {
  if (!url) return

  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
  })
}

function normalizeFilters() {
  return Object.fromEntries(
    Object.entries({
      date_from: form.date_from,
      date_to: form.date_to,
      status: form.status,
      sample_type: form.sample_type,
      customer_id: form.customer_id,
      lab_id: form.lab_id,
      department_id: form.department_id,
      discard_method: form.discard_method,
    }).filter(([, value]) => value !== '' && value !== null && value !== undefined)
  )
}

function buildUrl(baseUrl, params) {
  const search = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      search.set(key, value)
    }
  })

  const query = search.toString()

  return query ? `${baseUrl}?${query}` : baseUrl
}

function formatDate(value) {
  if (!value) return '-'

  return new Date(value).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getStatusLabel(status) {
  return statusOptions.find((option) => option.value === status)?.label || status || 'N/A'
}

function statusBadgeClass(status) {
  const map = {
    COMPLETADO: 'bg-green-100 text-green-800',
    EN_PROGRESO: 'bg-blue-100 text-blue-800',
    POR_INICIAR: 'bg-amber-100 text-amber-800',
    CANCELADO: 'bg-rose-100 text-rose-800',
    EN_PAUSA: 'bg-gray-100 text-gray-800',
  }

  return map[status] || 'bg-gray-100 text-gray-800'
}
</script>
