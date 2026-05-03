<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_proposals.description') }}
            <span class="font-semibold text-blue-900">
              {{ stats.total }} {{ $t('gestlab.general.labels.vap_proposals.total') }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link 
            :href="route('vap-proposals.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_proposals.create_new') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- STATISTICS GRID -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div 
        v-for="(stat, key) in stats" 
        :key="key"
        class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t(`proposals.stats.${key}`) }}
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
              {{ key === 'total_value' ? formatCurrency(stat) : stat }}
            </p>
          </div>
          <div :class="[
            'flex h-12 w-12 items-center justify-center rounded-full',
            statConfigs[key].bgColor
          ]">
            <component 
              :is="statConfigs[key].icon" 
              class="h-6 w-6"
              :class="statConfigs[key].iconColor"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- CHART SECTION -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ChartBarIcon class="h-5 w-5 text-blue-900" />
          {{ $t('gestlab.general.labels.vap_proposals.chart.title') }}
        </h2>
        <select 
          v-model="chartPeriod"
          class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
        >
          <option value="7">{{ $t('gestlab.general.labels.vap_proposals.chart.last_7_days') }}</option>
          <option value="30">{{ $t('gestlab.general.labels.vap_proposals.chart.last_30_days') }}</option>
          <option value="90">{{ $t('gestlab.general.labels.vap_proposals.chart.last_90_days') }}</option>
        </select>
      </div>
      <div v-if="chartData" class="h-80">
        <apexchart
          type="line"
          height="100%"
          :options="chartOptions"
          :series="chartData"
        />
      </div>
    </div>

    <!-- PROPOSALS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_proposals.list.title') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ proposals.total }} {{ $t('general.items') }})
            </span>
          </h2>
          <div class="flex items-center gap-4">
            <div class="relative">
              <MagnifyingGlassIcon class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
              <input
                v-model="search"
                type="search"
                :placeholder="$t('general.search')"
                class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
                @input="debouncedSearch"
              />
            </div>
            <select 
              v-model="statusFilter"
              class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
            >
              <option value="all">{{ $t('general.all_status') }}</option>
              <option value="PENDING">{{ $t('gestlab.general.labels.vap_proposals.status.pending') }}</option>
              <option value="SENT">{{ $t('gestlab.general.labels.vap_proposals.status.sent') }}</option>
              <option value="VIEWED">{{ $t('gestlab.general.labels.vap_proposals.status.viewed') }}</option>
              <option value="ACCEPTED">{{ $t('gestlab.general.labels.vap_proposals.status.accepted') }}</option>
              <option value="REJECTED">{{ $t('gestlab.general.labels.vap_proposals.status.rejected') }}</option>
              <option value="REVISED">{{ $t('gestlab.general.labels.vap_proposals.status.revised') }}</option>
              <option value="EXPIRED">{{ $t('gestlab.general.labels.vap_proposals.status.expired') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="proposals.data.length === 0" class="p-12 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_proposals.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_proposals.empty_state.description') }}
        </p>
        <Link 
          :href="route('vap-proposals.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_proposals.create_first') }}
        </Link>
      </div>

      <!-- PROPOSALS TABLE -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.proposal_no') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.customer') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.department') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.total') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.expiry') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_proposals.table.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="proposal in proposals.data"
              :key="proposal.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <DocumentTextIcon class="h-6 w-6 text-blue-900" />
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ proposal.proposal_number }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ formatDate(proposal.created_at) }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">
                  {{ proposal.customer.name }}
                </div>
                <div class="text-sm text-gray-500">
                  {{ proposal.customer.code }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ proposal.department.name }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-blue-900">
                  {{ formatCurrency(proposal.total) }}
                </div>
                <div class="text-xs text-gray-500">
                  {{ proposal.items_count }} items
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span 
                  :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    proposal.status_badge.class
                  ]"
                >
                  {{ proposal.status_badge.text }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ formatDate(proposal.expiry_date) }}
                </div>
                <div class="text-xs" :class="proposal.days_until_expiry <= 3 ? 'text-red-600' : 'text-gray-500'">
                  {{ proposal.days_until_expiry }} days left
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <div class="flex items-center gap-2">
                  <Link 
                    :href="route('vap-proposals.show', proposal.id)"
                    class="text-blue-900 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                    :title="$t('general.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link 
                    v-if="proposal.status === 'PENDING'"
                    :href="route('vap-proposals.edit', proposal.id)"
                    class="text-yellow-600 hover:text-yellow-800 p-1 rounded hover:bg-yellow-50"
                    :title="$t('general.edit')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </Link>
                  <button 
                    v-if="['PENDING', 'REJECTED'].includes(proposal.status)"
                    @click="confirmDelete(proposal)"
                    class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50"
                    :title="$t('general.delete')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                  <a 
                    v-if="proposal.file_path"
                    :href="route('vap-proposals.download.pdf', proposal.id)"
                    class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50"
                    :title="$t('general.download_pdf')"
                  >
                    <ArrowDownTrayIcon class="h-5 w-5" />
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div v-if="proposals.data.length > 0" class="px-6 py-4 border-t border-gray-200">
        <Pagination :links="proposals.links" />
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showDeleteModal"
    @close="showDeleteModal = false"
    @confirm="deleteProposal"
  >
    <template #title>
      {{ $t('gestlab.general.labels.vap_proposals.delete.title') }}
    </template>
    <template #content>
      <p class="text-sm text-gray-600">
        {{ $t('gestlab.general.labels.vap_proposals.delete.message', { number: selectedProposal?.proposal_number }) }}
      </p>
      <p class="mt-2 text-sm text-red-600">
        {{ $t('gestlab.general.labels.vap_proposals.delete.warning') }}
      </p>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, PlusCircleIcon, ChartBarIcon,
  ListBulletIcon, MagnifyingGlassIcon, EyeIcon,
  PencilSquareIcon, TrashIcon, ArrowDownTrayIcon,
  BanknotesIcon, ClockIcon, CheckCircleIcon,
  XCircleIcon, ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/dialog-modal.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
  proposals: Object,
  filters: Object,
  stats: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const chartPeriod = ref('30')
const showDeleteModal = ref(false)
const selectedProposal = ref(null)

const statConfigs = {
  total: { icon: DocumentTextIcon, bgColor: 'bg-blue-50', iconColor: 'text-blue-900' },
  pending: { icon: ClockIcon, bgColor: 'bg-yellow-50', iconColor: 'text-yellow-900' },
  accepted: { icon: CheckCircleIcon, bgColor: 'bg-green-50', iconColor: 'text-green-900' },
  rejected: { icon: XCircleIcon, bgColor: 'bg-red-50', iconColor: 'text-red-900' },
  expired: { icon: ExclamationTriangleIcon, bgColor: 'bg-orange-50', iconColor: 'text-orange-900' },
  total_value: { icon: BanknotesIcon, bgColor: 'bg-emerald-50', iconColor: 'text-emerald-900' },
}

const chartOptions = {
  chart: {
    type: 'line',
    height: 350,
    toolbar: { show: false },
    zoom: { enabled: false }
  },
  colors: ['#1e3a8a', '#1e40af'],
  stroke: { curve: 'smooth', width: 3 },
  markers: { size: 5 },
  xaxis: { type: 'datetime' },
  yaxis: {
    labels: {
      formatter: function(val) {
        return val.toFixed(0)
      }
    }
  },
  grid: { borderColor: '#e5e7eb' },
  tooltip: {
    x: { format: 'dd MMM yyyy' }
  }
}

const chartData = computed(() => {
  // This would typically come from an API endpoint
  // For now, we'll return sample data
  return [
    {
      name: 'Proposals Created',
      data: [
        [Date.now() - 30*24*60*60*1000, 5],
        [Date.now() - 20*24*60*60*1000, 8],
        [Date.now() - 10*24*60*60*1000, 12],
        [Date.now(), 15]
      ]
    },
    {
      name: 'Proposals Accepted',
      data: [
        [Date.now() - 30*24*60*60*1000, 3],
        [Date.now() - 20*24*60*60*1000, 6],
        [Date.now() - 10*24*60*60*1000, 9],
        [Date.now(), 11]
      ]
    }
  ]
})

const debouncedSearch = debounce(() => {
  router.get(route('vap-proposals.index'), 
    { search: search.value, status: statusFilter.value },
    { preserveState: true }
  )
}, 500)

watch(statusFilter, () => {
  debouncedSearch()
})

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const confirmDelete = (proposal) => {
  selectedProposal.value = proposal
  showDeleteModal.value = true
}

const deleteProposal = () => {
  router.delete(route('vap-proposals.destroy', selectedProposal.value.id), {
    onSuccess: () => {
      showDeleteModal.value = false
    }
  })
}
</script>