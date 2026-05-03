<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.details_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_non_conformities.details_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusClasses" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset">
            {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nonConformity.status}`) }}
          </span>
          <Link
            :href="route('vap_non_conformities.edit', nonConformity.id)"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit') }}
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
                  <a
                    :href="route('vap_non_conformities.export.details.excel', nonConformity.id)"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.excel_details') }}
                  </a>
                </MenuItem>
                <MenuItem v-slot="{ active }">
                  <a
                    :href="route('vap_non_conformities.export.details.pdf', nonConformity.id)"
                    :class="[
                      active ? 'bg-blue-50 text-blue-900' : 'text-gray-700',
                      'group flex w-full items-center gap-2 rounded-md px-3 py-2.5 text-sm'
                    ]"
                  >
                    <DocumentArrowDownIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_non_conformities.export.pdf_details') }}
                  </a>
                </MenuItem>
              </div>
            </MenuItems>
          </transition>
        </Menu>

      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.basic_info') }}
            </h2>
          </div>
          <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.nc_number') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ nonConformity.nc_number }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.title') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.title }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.severity.title') }}</dt>
                <dd :class="severityClasses" class="mt-1 text-sm font-medium">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.severity.${nonConformity.severity}`) }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.categories.title') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">
                  {{ $t(`gestlab.general.labels.vap_non_conformities.categories.${nonConformity.category}`) }}
                </dd>
              </div>
              <div class="md:col-span-2">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.description') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ nonConformity.description }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- RELATED INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_non_conformities.related_info') }}
            </h2>
          </div>
          <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.sample_id') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.sample_id || '--' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.test_method') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.test_method || '--' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.equipment_id') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.equipment_id || '--' }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.batch_number') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.batch_number || '--' }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- CORRECTIVE ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <WrenchScrewdriverIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.vap_non_conformities.corrective_actions') }}
              <span class="text-sm font-normal text-gray-500 ml-2">
                ({{ nonConformity.actions?.length || 0 }})
              </span>
            </h2>
          </div>
          <div v-if="nonConformity.actions?.length === 0" class="p-6 text-center">
            <WrenchScrewdriverIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-4 text-sm font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_title') }}
            </h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ $t('gestlab.general.labels.vap_non_conformities.no_actions_description') }}
            </p>
          </div>
          <div v-else class="divide-y divide-gray-200">
            <div 
              v-for="(action, index) in nonConformity.actions" 
              :key="action.id"
              class="p-6"
            >
              <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                    {{ index + 1 }}
                  </div>
                  <div>
                    <h3 class="text-sm font-semibold text-gray-900">
                      {{ $t('gestlab.general.labels.vap_non_conformities.action') }} #{{ index + 1 }}
                    </h3>
                    <p class="text-xs text-gray-500">
                      {{ action.due_at ? $t('gestlab.general.labels.vap_non_conformities.due') + ': ' + formatDate(action.due_at) : $t('gestlab.general.labels.vap_non_conformities.no_due_date') }}
                    </p>
                  </div>
                </div>
                <span v-if="action.approved_at" class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                  {{ $t('gestlab.general.labels.vap_non_conformities.approved') }}
                </span>
              </div>
              <div class="mt-4 space-y-4">
                <div v-if="action.correction">
                  <h4 class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.vap_non_conformities.correction') }}</h4>
                  <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ action.correction }}</p>
                </div>
                <div v-if="action.corrective_action">
                  <h4 class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.vap_non_conformities.corrective_action') }}</h4>
                  <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ action.corrective_action }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="space-y-6">
        <!-- TIMELINE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_non_conformities.timeline') }}
          </h3>
          <div class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.reported_by') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.reported_by }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.reported_at') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(nonConformity.reported_at) }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.assigned_to') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ nonConformity.assigned_to || '--' }}</dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.due_date') }}</dt>
              <dd :class="{'text-red-600 font-semibold': isOverdue}" class="mt-1 text-sm">
                {{ nonConformity.due_date ? formatDateTime(nonConformity.due_date) : '--' }}
              </dd>
            </div>
            <div v-if="nonConformity.resolved_at">
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.resolved_at') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(nonConformity.resolved_at) }}</dd>
            </div>
            <div v-if="nonConformity.closed_at">
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_non_conformities.closed_at') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(nonConformity.closed_at) }}</dd>
            </div>
          </div>
        </div>

        <!-- ADDITIONAL INFO -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_non_conformities.additional_info') }}
          </h3>
          <div class="space-y-4">
            <div v-if="nonConformity.root_cause">
              <h4 class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.vap_non_conformities.root_cause') }}</h4>
              <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ nonConformity.root_cause }}</p>
            </div>
            <div v-if="nonConformity.preventive_actions">
              <h4 class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.vap_non_conformities.preventive_actions') }}</h4>
              <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ nonConformity.preventive_actions }}</p>
            </div>
            <div v-if="nonConformity.comments">
              <h4 class="text-sm font-medium text-gray-700">{{ $t('gestlab.general.labels.vap_non_conformities.comments') }}</h4>
              <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ nonConformity.comments }}</p>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_non_conformities.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link
              :href="route('vap_non_conformities.edit', nonConformity.id)"
              class="block w-full text-center rounded-lg bg-blue-900 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit_nc') }}
            </Link>
            <button
              @click="confirmDelete"
              class="block w-full text-center rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2"
            >
              {{ $t('gestlab.general.labels.vap_non_conformities.buttons.delete_nc') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.vap_non_conformities.created_at') }}: {{ formatDateTime(nonConformity.created_at) }}
        <span v-if="nonConformity.updated_at" class="ml-4">
          {{ $t('gestlab.general.labels.vap_non_conformities.updated_at') }}: {{ formatDateTime(nonConformity.updated_at) }}
        </span>
      </div>
      <div class="flex items-center gap-4">
        <Link
          :href="route('vap_non_conformities.index')"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.back_to_list') }}
        </Link>
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
        <div class="font-semibold text-gray-900">{{ nonConformity.nc_number }}</div>
        <div class="text-sm text-gray-600 mt-1">{{ nonConformity.title }}</div>
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
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  LinkIcon,
  WrenchScrewdriverIcon,
  PencilSquareIcon,
  ArrowDownTrayIcon,
  DocumentArrowDownIcon,
  ChevronDownIcon
} from '@heroicons/vue/24/outline'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'

// Props
const props = defineProps({
  nonConformity: {
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

// Delete modal
const showDeleteModal = ref(false)

// Computed
const statusClasses = computed(() => {
  const classes = {
    opened: 'bg-blue-50 text-blue-700 ring-blue-700/10',
    in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-700/10',
    resolved: 'bg-green-50 text-green-700 ring-green-700/10',
    closed: 'bg-gray-50 text-gray-700 ring-gray-700/10'
  }
  return classes[props.nonConformity.status] || classes.opened
})

const severityClasses = computed(() => {
  const classes = {
    low: 'text-green-600',
    medium: 'text-yellow-600',
    high: 'text-orange-600',
    critical: 'text-red-600'
  }
  return classes[props.nonConformity.severity] || classes.medium
})

const isOverdue = computed(() => {
  if (!props.nonConformity.due_date || props.nonConformity.status === 'closed') return false
  return new Date(props.nonConformity.due_date) < new Date()
})

// Methods
function formatDate(dateString) {
  if (!dateString) return '--'
  return new Date(dateString).toLocaleDateString('pt-BR')
}

function formatDateTime(dateString) {
  if (!dateString) return '--'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR')
}

function confirmDelete() {
  showDeleteModal.value = true
}

function deleteNc() {
  router.delete(route('vap_non_conformities.destroy', props.nonConformity.id), {
    onSuccess: () => {
      router.visit(route('vap_non_conformities.index'))
    }
  })
}
</script>