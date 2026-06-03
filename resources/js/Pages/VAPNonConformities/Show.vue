<template>
  <div class="nc-detail-surface space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate flex flex-col gap-5 overflow-hidden p-6 xl:flex-row xl:items-center xl:justify-between">
        <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-r from-primary-600/15 via-rose-400/10 to-amber-400/10 dark:from-primary-500/20 dark:via-rose-500/10 dark:to-amber-500/10"></div>
        <div>
          <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-600/20">
              <ExclamationTriangleIcon class="h-6 w-6" />
            </span>
            {{ $t('gestlab.general.labels.vap_non_conformities.details_title') }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_non_conformities.details_description') }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span :class="statusClasses" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset">
            {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nonConformity.status}`) }}
          </span>
          <Link
            :href="route('vap_non_conformities.edit', nonConformity.id)"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.edit') }}
          </Link>
        </div>

        <Menu as="div" class="relative">
          <MenuButton
            class="inline-flex items-center gap-2 rounded-2xl border border-slate-300 bg-white/90 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:border-slate-700 dark:bg-slate-950/80 dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:ring-offset-slate-900"
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
              class="absolute right-0 z-10 mt-2 w-60 origin-top-right divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5 focus:outline-none dark:divide-slate-800 dark:border-slate-800 dark:bg-slate-900"
            >
              <div class="px-1 py-1">
                <MenuItem v-slot="{ active }">
                  <a
                    :href="route('vap_non_conformities.export.details.excel', nonConformity.id)"
                    :class="[
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
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
                      active ? 'bg-primary-50 text-primary-900 dark:bg-primary-500/10 dark:text-primary-100' : 'text-slate-700 dark:text-slate-200',
                      'group flex w-full items-center gap-2 rounded-xl px-3 py-2.5 text-sm'
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
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4">
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
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="bg-gradient-to-r from-primary-700 to-primary-600 px-6 py-4">
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
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
              <WrenchScrewdriverIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
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
          <div v-else class="divide-y divide-slate-200 dark:divide-slate-800">
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
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-white">
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
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-white">
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

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
            <PaperClipIcon class="h-5 w-5 text-primary-700 dark:text-primary-300" />
            {{ $t('gestlab.general.labels.vap_non_conformities.attachments_notes') }}
          </h3>
          <div v-if="mediaAttachments.length" class="space-y-2">
            <a
              v-for="attachment in mediaAttachments"
              :key="attachment.id"
              :href="attachment.url"
              target="_blank"
              class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-800 dark:border-slate-800 dark:bg-slate-950/60 dark:text-slate-200 dark:hover:border-primary-500/40 dark:hover:bg-primary-500/10 dark:hover:text-primary-100"
            >
              <span class="truncate font-medium">{{ attachment.name || attachment.file_name }}</span>
              <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">{{ attachment.human_readable_size }}</span>
            </a>
          </div>
          <p v-else class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
            Nenhum anexo registado para esta não conformidade.
          </p>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
          <h3 class="mb-4 text-lg font-semibold text-slate-950 dark:text-white">
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
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { router, Link } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  InformationCircleIcon,
  LinkIcon,
  WrenchScrewdriverIcon,
  PencilSquareIcon,
  ArrowDownTrayIcon,
  DocumentArrowDownIcon,
  ChevronDownIcon,
  PaperClipIcon
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
const mediaAttachments = computed(() => props.nonConformity.media_attachments || [])

// Computed
const statusClasses = computed(() => {
  const classes = {
    opened: 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20',
    in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-700/10 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20',
    resolved: 'bg-green-50 text-green-700 ring-green-700/10 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20',
    closed: 'bg-slate-50 text-slate-700 ring-slate-700/10 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600'
  }
  return classes[props.nonConformity.status] || classes.opened
})

const severityClasses = computed(() => {
  const classes = {
    low: 'text-green-600 dark:text-green-300',
    medium: 'text-yellow-600 dark:text-yellow-300',
    high: 'text-orange-600 dark:text-orange-300',
    critical: 'text-red-600 dark:text-red-300'
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
  return new Date(dateString).toLocaleDateString('pt-Pt')
}

function formatDateTime(dateString) {
  if (!dateString) return '--'
  const date = new Date(dateString)
  return date.toLocaleDateString('pt-Pt') + ' ' + date.toLocaleTimeString('pt-Pt')
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

<style scoped>
.nc-detail-surface :deep(.bg-white) {
  border-radius: 1.5rem;
}

:global(.dark) .nc-detail-surface :deep(.bg-white) {
  background-color: rgb(15 23 42 / 0.8);
}

:global(.dark) .nc-detail-surface :deep(.bg-gray-50),
:global(.dark) .nc-detail-surface :deep(.from-blue-50) {
  background-color: rgb(2 6 23 / 0.65);
}

:global(.dark) .nc-detail-surface :deep(.border-gray-200),
:global(.dark) .nc-detail-surface :deep(.border-gray-300),
:global(.dark) .nc-detail-surface :deep(.divide-gray-200 > :not([hidden]) ~ :not([hidden])) {
  border-color: rgb(51 65 85);
}

:global(.dark) .nc-detail-surface :deep(.text-gray-900) {
  color: rgb(248 250 252);
}

:global(.dark) .nc-detail-surface :deep(.text-gray-700),
:global(.dark) .nc-detail-surface :deep(.text-gray-600) {
  color: rgb(203 213 225);
}

:global(.dark) .nc-detail-surface :deep(.text-gray-500),
:global(.dark) .nc-detail-surface :deep(.text-gray-400) {
  color: rgb(148 163 184);
}

:global(.dark) .nc-detail-surface :deep(.bg-gradient-to-r.from-blue-900),
.nc-detail-surface :deep(.bg-gradient-to-r.from-blue-900) {
  background-image: linear-gradient(90deg, rgb(var(--color-primary-700, 14 116 144)), rgb(var(--color-primary-600, 8 145 178)));
}
</style>
