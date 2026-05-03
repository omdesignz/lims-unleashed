<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labs.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labs.manage_labs_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-labs.labs.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labs.buttons.add_lab') }}
          </Link>
        </div>
      </div>

      <!-- STATS CARDS -->
      <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
              <BuildingLibraryIcon class="h-5 w-5 text-blue-900" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.total_labs') }}</p>
              <p class="text-2xl font-bold text-blue-900">{{ stats.total }}</p>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
              <CheckCircleIcon class="h-5 w-5 text-green-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.active_labs') }}</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.active }}</p>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-r from-yellow-50 to-white rounded-lg border border-gray-200 p-4">
          <div class="flex items-center">
            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-100">
              <UserGroupIcon class="h-5 w-5 text-yellow-600" />
            </div>
            <div class="ml-4">
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.labs_with_supervisor') }}</p>
              <p class="text-2xl font-bold text-yellow-600">{{ stats.with_supervisor }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- SEARCH AND FILTERS -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex-1">
          <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              v-model="search"
              type="text"
              class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm placeholder-gray-500 focus:border-blue-900 focus:ring-blue-900 focus:outline-none"
              :placeholder="$t('gestlab.general.labels.vap_labs.search.placeholder')"
              @input="debouncedSearch"
            />
          </div>
        </div>
        <div class="flex items-center gap-3">
          <select
            v-model="filters.status"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-900 focus:ring-blue-900 focus:outline-none"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labs.filters.all_status') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_labs.filters.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_labs.filters.inactive') }}</option>
          </select>
          
          <button
            @click="resetFilters"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <ArrowPathIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_labs.buttons.reset') }}
          </button>
        </div>
      </div>
    </div>

    <!-- LABS LIST -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <!-- TABLE HEADER -->
      <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
          <ListBulletIcon class="h-5 w-5 text-blue-900" />
          {{ $t('gestlab.general.labels.vap_labs.list_title') }}
          <span class="text-sm font-normal text-gray-500 ml-2">
            ({{ labs.total }} {{ $t('gestlab.general.labels.vap_labs.items') }})
          </span>
        </h2>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="labs.data.length === 0" class="p-12 text-center">
        <BuildingLibraryIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_labs.messages.empty_labs.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_labs.messages.empty_labs.description') }}
        </p>
        <Link
          :href="route('vap-labs.labs.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labs.buttons.add_first_lab') }}
        </Link>
      </div>

      <!-- LABS TABLE -->
      <div v-else>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.name') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.code') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.location') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.supervisor') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.status') }}
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('gestlab.general.labels.vap_labs.table.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="lab in labs.data"
                :key="lab.id"
                class="hover:bg-gray-50 transition-colors duration-150"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-900 font-semibold mr-3">
                      <BeakerIcon class="h-4 w-4" />
                    </div>
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ lab.name }}
                      </div>
                      <div class="text-xs text-gray-500">
                        {{ lab.department?.name || $t('gestlab.general.labels.vap_labs.no_department') }}
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-900">
                    {{ lab.code }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                  {{ lab.room_no || $t('gestlab.general.labels.vap_labs.not_specified') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                  <div v-if="lab.supervisor" class="flex items-center">
                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                      <UserIcon class="h-4 w-4 text-gray-600" />
                    </div>
                    <span>{{ lab.supervisor.name }}</span>
                  </div>
                  <span v-else class="text-gray-400 italic">
                    {{ $t('gestlab.general.labels.vap_labs.not_assigned') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="[
                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                    lab.deleted_at 
                      ? 'bg-red-100 text-red-800' 
                      : 'bg-green-100 text-green-800'
                  ]">
                    {{ lab.deleted_at ? $t('gestlab.general.labels.vap_labs.status.inactive') : $t('gestlab.general.labels.vap_labs.status.active') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex items-center gap-2">
                    <Link
                      :href="route('vap-labs.labs.show', lab.id)"
                      class="text-blue-900 hover:text-blue-700 p-1 rounded hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_labs.buttons.view')"
                    >
                      <EyeIcon class="h-4 w-4" />
                    </Link>
                    <Link
                      :href="route('vap-labs.labs.edit', lab.id)"
                      class="text-green-600 hover:text-green-800 p-1 rounded hover:bg-green-50"
                      :title="$t('gestlab.general.labels.vap_labs.buttons.edit')"
                    >
                      <PencilSquareIcon class="h-4 w-4" />
                    </Link>
                    <button
                      v-if="!lab.deleted_at"
                      @click="confirmDelete(lab)"
                      class="text-red-600 hover:text-red-800 p-1 rounded hover:bg-red-50"
                      :title="$t('gestlab.general.labels.vap_labs.buttons.delete')"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                    <button
                      v-else
                      @click="restoreLab(lab.id)"
                      class="text-yellow-600 hover:text-yellow-800 p-1 rounded hover:bg-yellow-50"
                      :title="$t('gestlab.general.labels.vap_labs.buttons.restore')"
                    >
                      <ArrowPathIcon class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="border-t border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <div class="text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_labs.pagination.showing') }} {{ labs.from }}-{{ labs.to }} {{ $t('gestlab.general.labels.vap_labs.pagination.of') }} {{ labs.total }}
            </div>
            <div class="flex items-center gap-2">
              <Link
                v-if="labs.prev_page_url"
                :href="labs.prev_page_url"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                {{ $t('gestlab.general.labels.vap_labs.pagination.previous') }}
              </Link>
              <Link
                v-if="labs.next_page_url"
                :href="labs.next_page_url"
                class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                {{ $t('gestlab.general.labels.vap_labs.pagination.next') }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <ConfirmationModal
    :show="showDeleteModal"
    @close="showDeleteModal = false"
    @confirm="deleteLab"
  >
    <template #title>
      {{ $t('gestlab.general.labels.vap_labs.modal.delete_lab.title') }}
    </template>
    <template #content>
      <p class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.vap_labs.modal.delete_lab.message') }}
      </p>
      <p class="mt-2 text-sm font-semibold text-gray-900">
        {{ selectedLab?.name }}
      </p>
      <p class="mt-1 text-xs text-gray-500">
        {{ selectedLab?.code }}
      </p>
    </template>
    <template #footer>
      <button
        type="button"
        @click="showDeleteModal = false"
        class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900"
      >
        {{ $t('gestlab.general.labels.vap_labs.buttons.cancel') }}
      </button>
      <button
        type="button"
        @click="deleteLab"
        class="ml-3 inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-600"
      >
        {{ $t('gestlab.general.labels.vap_labs.buttons.delete') }}
      </button>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import {
  BeakerIcon,
  PlusCircleIcon,
  BuildingLibraryIcon,
  CheckCircleIcon,
  UserGroupIcon,
  MagnifyingGlassIcon,
  ArrowPathIcon,
  ListBulletIcon,
  UserIcon,
  EyeIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  labs: Object,
  stats: Object,
  filters: Object,
})

const search = ref(props.filters.search || '')
const filters = ref({
  status: props.filters.status || '',
})

const showDeleteModal = ref(false)
const selectedLab = ref(null)

const debouncedSearch = debounce(() => {
  router.get(route('vap-labs.labs.index'), {
    search: search.value,
    status: filters.value.status,
  }, {
    preserveState: true,
    replace: true,
  })
}, 300)

watch(filters, debouncedSearch, { deep: true })

const resetFilters = () => {
  search.value = ''
  filters.value = {
    status: '',
  }
  router.get(route('vap-labs.labs.index'), {}, {
    preserveState: true,
    replace: true,
  })
}

const confirmDelete = (lab) => {
  selectedLab.value = lab
  showDeleteModal.value = true
}

const deleteLab = () => {
  if (selectedLab.value) {
    router.delete(route('vap-labs.labs.destroy', selectedLab.value.id), {
      onSuccess: () => {
        showDeleteModal.value = false
        selectedLab.value = null
      },
    })
  }
}

const restoreLab = (labId) => {
  if (confirm('Are you sure you want to restore this lab?')) {
    router.post(route('vap-labs.labs.restore', labId), {}, {
      onSuccess: () => {
        router.reload({ only: ['labs'] })
      },
    })
  }
}
</script>