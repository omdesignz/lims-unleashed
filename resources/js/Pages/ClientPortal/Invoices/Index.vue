<template>
  <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white">
    <!-- Header Section -->
    <div class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb and Stats -->
        <div class="py-6">
          <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
              <li class="inline-flex items-center">
                <Link :href="route('portal.home')" class="inline-flex items-center text-sm text-gray-500 hover:text-blue-900">
                  <HomeModernIcon class="h-4 w-4 mr-2" />
                  {{ $t('gestlab.portal_menu.dashboard') }}
                </Link>
              </li>
              <li aria-current="page">
                <div class="flex items-center">
                  <ChevronRightIcon class="h-4 w-4 text-gray-400 mx-2" />
                  <span class="text-sm font-medium text-blue-900">{{ $t('gestlab.pages.portal_invoices.title') }}</span>
                </div>
              </li>
            </ol>
          </nav>

          <!-- Page Header -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                <BanknotesIcon class="h-7 w-7 text-blue-900" />
                {{ $t('gestlab.pages.portal_invoices.title') }}
              </h1>
              <p class="mt-2 text-gray-600">
                {{ $t('gestlab.pages.portal_invoices.subtitle') }}
                <span class="font-semibold text-blue-900">{{ props.record.meta.total }}</span>
                {{ $t('gestlab.pages.portal_invoices.invoices_total') }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="inline-flex items-center rounded-full bg-gradient-to-r from-blue-50 to-white px-4 py-2 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 shadow-sm">
                <DocumentTextIcon class="h-4 w-4 mr-2" />
                {{ props.record.meta.total }} {{ $t('gestlab.pages.portal_invoices.invoices') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8"
           v-motion
           :initial="{ opacity: 0, y: 20 }"
           :enter="{ opacity: 1, y: 0 }"
      >
        <!-- Paid Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_invoices.stats.paid') }}</p>
              <p class="mt-2 text-3xl font-bold text-green-600">{{ paidCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
              <CheckCircleIcon class="h-6 w-6 text-green-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.stats.paid_description') }}</p>
          </div>
        </div>

        <!-- Pending Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_invoices.stats.pending') }}</p>
              <p class="mt-2 text-3xl font-bold text-yellow-600">{{ pendingCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <ClockIcon class="h-6 w-6 text-yellow-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.stats.pending_description') }}</p>
          </div>
        </div>

        <!-- Overdue Invoices -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_invoices.stats.overdue') }}</p>
              <p class="mt-2 text-3xl font-bold text-red-600">{{ overdueCount }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
              <ExclamationTriangleIcon class="h-6 w-6 text-red-600" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.stats.overdue_description') }}</p>
          </div>
        </div>

        <!-- Total Amount -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.pages.portal_invoices.stats.total_amount') }}</p>
              <p class="mt-2 text-3xl font-bold text-blue-900">{{ formatCurrency(totalAmount) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
              <CurrencyDollarIcon class="h-6 w-6 text-blue-900" />
            </div>
          </div>
          <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.stats.total_description') }}</p>
          </div>
        </div>
      </div>

      <!-- Search and Filter Bar -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div class="flex-1">
            <div class="relative max-w-md">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.pages.portal_invoices.search_placeholder')"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-500 focus:border-blue-900 focus:ring-2 focus:ring-blue-900/20 focus:outline-none transition-colors duration-200"
              />
            </div>
          </div>
          
          <div class="flex items-center gap-3">
            <!-- Filter Dropdown -->
            <Menu as="div" class="relative">
              <MenuButton class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                <FunnelIcon class="h-4 w-4" />
                {{ $t('gestlab.pages.portal_invoices.filter') }}
                <ChevronDownIcon class="h-4 w-4" />
              </MenuButton>
              <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
              >
                <MenuItems class="absolute right-0 mt-2 w-56 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                  <div class="px-4 py-2">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                      {{ $t('gestlab.pages.portal_invoices.filter_by_status') }}
                    </p>
                  </div>
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.filter = ''"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          !query.filter ? 'font-semibold text-blue-900' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_invoices.all_invoices') }}
                        <span v-if="!query.filter" class="h-2 w-2 rounded-full bg-blue-900"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.filter = 'paid'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.filter === 'paid' ? 'font-semibold text-green-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_invoices.paid') }}
                        <span v-if="query.filter === 'paid'" class="h-2 w-2 rounded-full bg-green-600"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.filter = 'pending'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.filter === 'pending' ? 'font-semibold text-yellow-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_invoices.pending') }}
                        <span v-if="query.filter === 'pending'" class="h-2 w-2 rounded-full bg-yellow-600"></span>
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="query.filter = 'overdue'"
                        :class="[
                          active ? 'bg-gray-50' : '',
                          'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center justify-between',
                          query.filter === 'overdue' ? 'font-semibold text-red-600' : ''
                        ]"
                      >
                        {{ $t('gestlab.pages.portal_invoices.overdue') }}
                        <span v-if="query.filter === 'overdue'" class="h-2 w-2 rounded-full bg-red-600"></span>
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>

            <!-- Export Button -->
            <button
              @click="exportInvoices"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowDownTrayIcon class="h-4 w-4" />
              {{ $t('gestlab.pages.portal_invoices.export') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Invoices Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <DocumentTextIcon class="h-5 w-5" />
            {{ $t('gestlab.pages.portal_invoices.recent_invoices') }}
          </h2>
        </div>

        <!-- Empty State -->
        <div v-if="!props.record.data.length" class="p-12 text-center">
          <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.pages.portal_invoices.empty_state.title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.pages.portal_invoices.empty_state.description') }}
          </p>
        </div>

        <!-- Invoices List -->
        <div v-else class="divide-y divide-gray-200">
          <div 
            v-for="(invoice, index) in props.record.data" 
            :key="invoice.id"
            class="group hover:bg-gray-50 transition-colors duration-200"
            v-motion
            :initial="{ opacity: 0, x: -20 }"
            :enter="{ opacity: 1, x: 0 }"
            :delay="index * 50"
          >
            <div class="px-6 py-4">
              <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <!-- Invoice Info -->
                <div class="flex-1">
                  <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-lg bg-gradient-to-r from-blue-50 to-white flex items-center justify-center ring-1 ring-blue-100">
                        <DocumentTextIcon class="h-5 w-5 text-blue-900" />
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-3 mb-2">
                        <p class="text-sm font-semibold text-gray-900 truncate">
                          {{ $t('gestlab.pages.portal_invoices.invoice') }} #{{ invoice.inv_no }}
                        </p>
                        <span :class="[
                          'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                          invoice.status 
                            ? 'bg-green-100 text-green-800 ring-1 ring-green-600/20'
                            : invoice.is_overdue
                              ? 'bg-red-100 text-red-800 ring-1 ring-red-600/20'
                              : 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-600/20'
                        ]">
                          {{ getStatusText(invoice) }}
                        </span>
                      </div>
                      
                      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.issue_date') }}</p>
                          <p class="text-sm font-medium text-gray-900">{{ formatDate(invoice.date) }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.due_date') }}</p>
                          <p class="text-sm font-medium text-gray-900">{{ formatDate(invoice.due_date) }}</p>
                        </div>
                        <div>
                          <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.paid_date') }}</p>
                          <p class="text-sm font-medium" :class="invoice.paid_date ? 'text-green-600' : 'text-gray-500'">
                            {{ invoice.paid_date ? formatDate(invoice.paid_date) : $t('gestlab.pages.portal_invoices.not_paid') }}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Amount and Actions -->
                <div class="flex items-center gap-6">
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ $t('gestlab.pages.portal_invoices.total_amount') }}</p>
                    <p class="text-lg font-bold text-gray-900">{{ formatCurrency(invoice.total) }}</p>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <a
                      :href="route('portal.invoices.getInvoicePDF', { id: invoice.id })"
                      target="_blank"
                      class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-900 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 ring-1 ring-blue-100"
                      :title="$t('gestlab.pages.portal_invoices.view_pdf')"
                    >
                      <EyeIcon class="h-4 w-4" />
                      <span class="hidden sm:inline">{{ $t('gestlab.pages.portal_invoices.view') }}</span>
                    </a>
                    
                    <Menu as="div" class="relative">
                      <MenuButton class="inline-flex items-center rounded-lg p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200">
                        <span class="sr-only">Open options</span>
                        <EllipsisVerticalIcon class="h-5 w-5" />
                      </MenuButton>
                      <transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                      >
                        <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right rounded-xl bg-white shadow-lg border border-gray-200 py-2 z-10">
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="downloadInvoice(invoice)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ArrowDownTrayIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_invoices.download') }}
                            </button>
                          </MenuItem>
                          <MenuItem v-slot="{ active }">
                            <button
                              @click="shareInvoice(invoice)"
                              :class="[active ? 'bg-gray-50' : '', 'w-full text-left px-4 py-2 text-sm text-gray-700 flex items-center gap-2']"
                            >
                              <ShareIcon class="h-4 w-4" />
                              {{ $t('gestlab.pages.portal_invoices.share') }}
                            </button>
                          </MenuItem>
                        </MenuItems>
                      </transition>
                    </Menu>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="props.record.data.length" class="border-t border-gray-200 px-6 py-4">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-700">
              {{ $t('gestlab.pages.portal_invoices.showing') }}
              <span class="font-semibold">{{ props.record.meta.from }}</span>
              {{ $t('gestlab.pages.portal_invoices.to') }}
              <span class="font-semibold">{{ props.record.meta.to }}</span>
              {{ $t('gestlab.pages.portal_invoices.of') }}
              <span class="font-semibold">{{ props.record.meta.total }}</span>
              {{ $t('gestlab.pages.portal_invoices.results') }}
            </div>
            <Pagination 
              :links="props.record.meta.links" 
              :from="props.record.meta.from" 
              :to="props.record.meta.to" 
              :total="props.record.meta.total" 
              :current_page="props.record.meta.current_page" 
              :last_page="props.record.meta.last_page" 
              class="mt-2"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import debounce from 'lodash/debounce'
import { useForm, router, Link, usePage } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
  import Layout from "@/Shared/Layouts/PortalLayout.vue";
import Pagination from '@/Components/pagination.vue'
import {
  HomeModernIcon,
  BanknotesIcon,
  DocumentTextIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  CurrencyDollarIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  EllipsisVerticalIcon,
  ShareIcon
} from '@heroicons/vue/24/outline'

 defineOptions({
    layout: Layout
    });

const props = defineProps({
  record: Object,
  fields: Array,
  query: Object,
})

const page = usePage()

const query = reactive({
  search: props.query?.search || '',
  filter: props.query?.filter || '',
  page: props.query?.page || 1
})

// Computed properties for stats
const paidCount = computed(() => {
  return props.record.data.filter(invoice => invoice.status).length
})

const pendingCount = computed(() => {
  return props.record.data.filter(invoice => !invoice.status && !invoice.is_overdue).length
})

const overdueCount = computed(() => {
  return props.record.data.filter(invoice => invoice.is_overdue).length
})

const totalAmount = computed(() => {
  return props.record.data.reduce((sum, invoice) => sum + parseFloat(invoice.total), 0)
})

// Watch for query changes with debounce
watch(query, debounce(function(value) {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}, 300))

// Helper functions
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('pt-PT', {
    style: 'currency',
    currency: 'AOA'
  }).format(amount)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('pt-PT')
}

const getStatusText = (invoice) => {
  if (invoice.status) return 'PAGO'
  if (invoice.is_overdue) return 'VENCIDO'
  return 'PENDENTE'
}

const exportRowsToCsv = (filename, columns, rows) => {
  const header = columns.join(',')
  const content = rows.map((row) => row.map((value) => `"${String(value ?? '').replace(/"/g, '""')}"`).join(','))
  const blob = new Blob([[header, ...content].join('\n')], { type: 'text/csv;charset=utf-8;' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', filename)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

const copyToClipboard = async (value) => {
  if (!value) return
  await navigator.clipboard.writeText(value)
}

const exportInvoices = () => {
  exportRowsToCsv(
    'portal-invoices.csv',
    ['Referência', 'Emissão', 'Cliente', 'Total', 'Estado'],
    props.record.data.map((invoice) => [
      invoice.inv_no,
      formatDate(invoice.date),
      invoice.customer,
      invoice.total,
      getStatusText(invoice),
    ]),
  )
}

const downloadInvoice = (invoice) => {
  // Implement download functionality
  window.open(route('portal.invoices.getInvoicePDF', { id: invoice.id }), '_blank')
}

const shareInvoice = (invoice) => {
  copyToClipboard(route('portal.invoices.getInvoicePDF', { id: invoice.id }))
}
</script>
