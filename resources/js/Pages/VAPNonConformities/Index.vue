<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_non_conformities.index_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap_non_conformities.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.new_non_conformity') }}
          </Link>
        </div>

        <Menu as="div" class="relative">
          <MenuButton
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowDownTrayIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.export') }}
            <ChevronDownIcon class="h-4 w-4 text-gray-400" />
          </MenuButton>
          <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
          >
            <MenuItems
              class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10"
            >
              <div class="px-1 py-1">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportExcel"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.excel_list') }}
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportPdf"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.pdf_list') }}
                  </button>
                </MenuItem>
              </div>
              <div class="px-1 py-1" v-if="hasFilters">
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportFilteredExcel"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <FunnelIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.excel_filtered') }}
                  </button>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <button
                    @click="exportFilteredPdf"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <FunnelIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.pdf_filtered') }}
                  </button>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>

      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- SEARCH -->
        <div class="w-full md:w-1/3">
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
            <input
              v-model="search"
              type="text"
              :placeholder="$t('gestlab.general.labels.vap_non_conformities.search_placeholder')"
              class="block w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50"
              @keyup.enter="applyFilters"
            />
          </div>
        </div>

        <!-- FILTERS -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- STATUS FILTER -->
          <div class="relative">
            <Listbox v-model="statusFilter">
              <template #default="{ open }">
                <ListboxButton
                  :class="[
                    'relative w-full cursor-default rounded-lg border bg-white py-2.5 pl-3 pr-10 text-left text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50 sm:text-sm',
                    statusFilter ? 'border-blue-900' : 'border-gray-300'
                  ]"
                >
                  <span class="block truncate">
                    {{ statusFilter ? $t(`gestlab.general.labels.vap_non_conformities.status.${statusFilter}`) : $t('gestlab.general.labels.vap_non_conformities.all_statuses') }}
                  </span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <transition
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <ListboxOptions
                    v-show="open"
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                  >
                    <ListboxOption
                      v-slot="{ active, selected }"
                      :value="null"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-blue-50 text-blue-900' : 'text-gray-900']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t('gestlab.general.labels.vap_non_conformities.all_statuses') }}
                        </span>
                      </li>
                    </ListboxOption>
                    <ListboxOption
                      v-for="status in statuses"
                      v-slot="{ active, selected }"
                      :key="status"
                      :value="status"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-blue-50 text-blue-900' : 'text-gray-900']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t(`gestlab.general.labels.vap_non_conformities.status.${status}`) }}
                        </span>
                        <span
                          v-if="selected"
                          class="absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                          <CheckIcon class="h-5 w-5 text-blue-900" />
                        </span>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </template>
            </Listbox>
          </div>

          <!-- SEVERITY FILTER -->
          <div class="relative">
            <Listbox v-model="severityFilter">
              <template #default="{ open }">
                <ListboxButton
                  :class="[
                    'relative w-full cursor-default rounded-lg border bg-white py-2.5 pl-3 pr-10 text-left text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900/50 sm:text-sm',
                    severityFilter ? 'border-blue-900' : 'border-gray-300'
                  ]"
                >
                  <span class="block truncate">
                    {{ severityFilter ? $t(`gestlab.general.labels.vap_non_conformities.severity.${severityFilter}`) : $t('gestlab.general.labels.vap_non_conformities.all_severities') }}
                  </span>
                  <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                    <ChevronUpDownIcon class="h-5 w-5 text-gray-400" />
                  </span>
                </ListboxButton>
                <transition
                  leave-active-class="transition duration-100 ease-in"
                  leave-from-class="opacity-100"
                  leave-to-class="opacity-0"
                >
                  <ListboxOptions
                    v-show="open"
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                  >
                    <ListboxOption
                      v-slot="{ active, selected }"
                      :value="null"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-blue-50 text-blue-900' : 'text-gray-900']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t('gestlab.general.labels.vap_non_conformities.all_severities') }}
                        </span>
                      </li>
                    </ListboxOption>
                    <ListboxOption
                      v-for="severity in severities"
                      v-slot="{ active, selected }"
                      :key="severity"
                      :value="severity"
                    >
                      <li :class="['relative cursor-default select-none py-2 pl-3 pr-9', active ? 'bg-blue-50 text-blue-900' : 'text-gray-900']">
                        <span :class="['block truncate', selected ? 'font-semibold' : 'font-normal']">
                          {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${severity}`) }}
                        </span>
                        <span
                          v-if="selected"
                          class="absolute inset-y-0 right-0 flex items-center pr-3"
                        >
                          <CheckIcon class="h-5 w-5 text-blue-900" />
                        </span>
                      </li>
                    </ListboxOption>
                  </ListboxOptions>
                </transition>
              </template>
            </Listbox>
          </div>

          <!-- ACTION BUTTONS -->
          <button
            @click="applyFilters"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <FunnelIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.apply_filters') }}
          </button>
          <button
            @click="clearFilters"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <XMarkIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.clear_filters') }}
          </button>
        </div>
      </div>
    </div>

    <!-- STATS CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.total') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.total }}</p>
          </div>
          <DocumentTextIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-yellow-500 to-yellow-400 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.open') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.open }}</p>
          </div>
          <ExclamationTriangleIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.critical') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.critical }}</p>
          </div>
          <ExclamationCircleIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>

      <div class="bg-gradient-to-r from-orange-500 to-orange-400 rounded-xl shadow-sm p-6 text-white">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium opacity-90">{{ $t('gestlab.general.labels.vap_non_conformities.stats.overdue') }}</p>
            <p class="text-3xl font-bold mt-2">{{ stats.overdue }}</p>
          </div>
          <ClockIcon class="h-10 w-10 opacity-90" />
        </div>
      </div>
    </div>

    <!-- NON-CONFORMITIES TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_non_conformities.list_title') }}
        </h2>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="nonConformities.data.length === 0" class="p-12 text-center">
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_non_conformities.empty_list_description') }}
        </p>
        <Link
          :href="route('vap_non_conformities.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.create_first_nc') }}
        </Link>
      </div>

      <!-- TABLE -->
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.nc_number') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.status.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.reported_at') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}
              </th>
              <th scope="col" class="relative px-6 py-3">
                <span class="sr-only">{{ $t('gestlab.general.labels.vap_non_conformities.buttons.actions') }}</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
              v-for="nc in nonConformities.data" 
              :key="nc.id"
              class="hover:bg-gray-50 transition-colors duration-150"
            >
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-semibold text-blue-900">
                  {{ nc.nc_number }}
                </div>
              </td>
              <td class="px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ nc.title }}</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">
                  {{ truncateText(nc.description, 80) }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="statusClasses[nc.status]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nc.status}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="severityClasses[nc.severity]" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${nc.severity}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(nc.reported_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm" :class="{'text-red-600 font-semibold': isOverdue(nc)}">
                  {{ nc.due_date ? formatDate(nc.due_date) : '--' }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <Link
                    :href="route('vap_non_conformities.show', nc.id)"
                    class="text-blue-900 hover:text-blue-800 p-1 rounded-md hover:bg-blue-50 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.view')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="route('vap_non_conformities.edit', nc.id)"
                    class="text-gray-600 hover:text-gray-900 p-1 rounded-md hover:bg-gray-100 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.edit')"
                  >
                    <PencilSquareIcon class="h-5 w-5" />
                  </Link>
                  <button
                    @click="confirmDelete(nc)"
                    class="text-red-600 hover:text-red-800 p-1 rounded-md hover:bg-red-50 transition-colors duration-200"
                    :title="$t('gestlab.general.labels.vap_non_conformities.buttons.delete')"
                  >
                    <TrashIcon class="h-5 w-5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINATION -->
      <div v-if="nonConformities.data.length > 0" class="px-6 py-4 border-t border-gray-200">
        <Pagination :links="nonConformities.links" />
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal :show="showDeleteModal" @close="showDeleteModal = false">
    <template #title>
      {{ $t('gestlab.general.labels.vap_non_conformities.delete_title') }}
    </template>
    <template #content>
      {{ $t('gestlab.general.labels.vap_non_conformities.delete_message') }}
      <div class="mt-4 p-4 bg-gray-50 rounded-lg">
        <div class="font-semibold text-gray-900">{{ ncToDelete?.nc_number }}</div>
        <div class="text-sm text-gray-600 mt-1">{{ ncToDelete?.title }}</div>
      </div>
    </template>
    <template #footer>
      <button
        @click="showDeleteModal = false"
        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
      >
        {{ $t('gestlab.general.labels.vap_non_conformities.buttons.cancel') }}
      </button>
      <button
        @click="deleteNc"
        class="ml-3 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
      >
        {{ $t('gestlab.general.labels.vap_non_conformities.buttons.delete') }}
      </button>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  PlusCircleIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  XMarkIcon,
  DocumentTextIcon,
  ExclamationCircleIcon,
  ClockIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
  ChevronUpDownIcon,
  CheckIcon,
  ArrowDownTrayIcon,
  DocumentArrowDownIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import {
  Listbox,
  ListboxButton,
  ListboxOptions,
  ListboxOption,
  Menu, MenuButton, MenuItems, MenuItem
} from '@headlessui/vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/confirm-dialog.vue'

// Props
const props = defineProps({
  nonConformities: {
    type: Object,
    required: true
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    required: true
  },
  labs: {
    type: Array,
    default: () => []
  },
  departments: {
    type: Array,
    default: () => []
  }
})

// Reactive filters
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || null)
const severityFilter = ref(props.filters.severity || null)

// Delete modal
const showDeleteModal = ref(false)
const ncToDelete = ref(null)

// Constants
const statuses = ['opened', 'in_progress', 'resolved', 'closed']
const severities = ['low', 'medium', 'high', 'critical']

// Classes
const statusClasses = {
  opened: 'bg-blue-100 text-blue-800',
  in_progress: 'bg-yellow-100 text-yellow-800',
  resolved: 'bg-green-100 text-green-800',
  closed: 'bg-gray-100 text-gray-800'
}

const severityClasses = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-yellow-100 text-yellow-800',
  high: 'bg-orange-100 text-orange-800',
  critical: 'bg-red-100 text-red-800'
}

// Methods
function applyFilters() {
  const filters = {}
  if (search.value) filters.search = search.value
  if (statusFilter.value) filters.status = statusFilter.value
  if (severityFilter.value) filters.severity = severityFilter.value
  
  router.get(route('vap_non_conformities.index'), filters, {
    preserveState: true,
    preserveScroll: true
  })
}

function clearFilters() {
  search.value = ''
  statusFilter.value = null
  severityFilter.value = null
  router.get(route('vap_non_conformities.index'))
}

function truncateText(text, length) {
  if (!text) return ''
  return text.length > length ? text.substring(0, length) + '...' : text
}

function formatDate(dateString) {
  if (!dateString) return '--'
  return new Date(dateString).toLocaleDateString('pt-BR')
}

function isOverdue(nc) {
  if (!nc.due_date || nc.status === 'closed') return false
  return new Date(nc.due_date) < new Date() && nc.status !== 'closed'
}

function confirmDelete(nc) {
  ncToDelete.value = nc
  showDeleteModal.value = true
}

function deleteNc() {
  if (ncToDelete.value) {
    router.delete(route('vap_non_conformities.destroy', ncToDelete.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        showDeleteModal.value = false
      }
    })
  }
}

// Watch for filter changes with debounce
let filterTimeout
watch([search, statusFilter, severityFilter], () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
})

const hasFilters = computed(() => {
  return search.value || statusFilter.value || severityFilter.value
})

function exportExcel() {
  window.location.href = route('vap_non_conformities.export.excel')
}

function exportPdf() {
  window.location.href = route('vap_non_conformities.export.pdf')
}

function exportFilteredExcel() {
  const params = new URLSearchParams()
  if (search.value) params.append('search', search.value)
  if (statusFilter.value) params.append('status', statusFilter.value)
  if (severityFilter.value) params.append('severity', severityFilter.value)
  
  window.location.href = route('vap_non_conformities.export.excel') + '?' + params.toString()
}

function exportFilteredPdf() {
  const params = new URLSearchParams()
  if (search.value) params.append('search', search.value)
  if (statusFilter.value) params.append('status', statusFilter.value)
  if (severityFilter.value) params.append('severity', severityFilter.value)
  
  window.location.href = route('vap_non_conformities.export.pdf') + '?' + params.toString()
}
</script>