<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-r from-blue-900 to-blue-800">
              <BeakerIcon class="h-6 w-6 text-white" />
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ lab.name }}
              </h1>
              <div class="mt-1 flex items-center gap-3">
                <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
                  {{ lab.code }}
                </span>
                <span :class="[
                  'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
                  lab.deleted_at 
                    ? 'bg-red-100 text-red-800 ring-red-700/10' 
                    : 'bg-green-100 text-green-800 ring-green-700/10'
                ]">
                  {{ lab.deleted_at ? $t('gestlab.general.labels.vap_labs.status.inactive') : $t('gestlab.general.labels.vap_labs.status.active') }}
                </span>
              </div>
            </div>
          </div>
          <p class="mt-3 text-gray-600 max-w-3xl">
            {{ lab.description || $t('gestlab.general.labels.vap_labs.no_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap-labs.labs.edit', lab.id)"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <PencilSquareIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labs.buttons.edit') }}
          </Link>
          <Link
            :href="route('vap-labs.labs.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labs.buttons.back') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LAB INFORMATION -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.lab_information') }}
            </h2>
          </div>
          <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.room_no') }}</dt>
                <dd class="text-sm text-gray-900">{{ lab.room_no || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd>
              </div>
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.department') }}</dt>
                <dd class="text-sm text-gray-900">{{ lab.department?.name || $t('gestlab.general.labels.vap_labs.not_assigned') }}</dd>
              </div>
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.contact') }}</dt>
                <dd class="text-sm text-gray-900">{{ lab.contact || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd>
              </div>
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.extension') }}</dt>
                <dd class="text-sm text-gray-900">{{ lab.extension || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd>
              </div>
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.email') }}</dt>
                <dd class="text-sm text-gray-900">{{ lab.email || $t('gestlab.general.labels.vap_labs.not_specified') }}</dd>
              </div>
              <div class="space-y-1">
                <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labs.created_at') }}</dt>
                <dd class="text-sm text-gray-900">{{ formatDate(lab.created_at) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- STAFF ASSIGNMENT -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <UserGroupIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.staff_assignment') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- SUPERVISOR -->
              <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labs.supervisor') }}</h3>
                <div v-if="lab.supervisor" class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                  <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                    <UserIcon class="h-5 w-5 text-blue-900" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ lab.supervisor.name }}</p>
                    <p class="text-xs text-gray-500">{{ lab.supervisor.email }}</p>
                  </div>
                </div>
                <div v-else class="p-3 bg-gray-50 rounded-lg text-center">
                  <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.vap_labs.no_supervisor_assigned') }}</p>
                </div>
              </div>

              <!-- TECHNICAL HEAD -->
              <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labs.technical_head') }}</h3>
                <div v-if="lab.technical_head" class="flex items-center gap-3 p-3 bg-green-50 rounded-lg">
                  <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                    <CogIcon class="h-5 w-5 text-green-900" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ lab.technical_head.name }}</p>
                    <p class="text-xs text-gray-500">{{ lab.technical_head.email }}</p>
                  </div>
                </div>
                <div v-else class="p-3 bg-gray-50 rounded-lg text-center">
                  <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.vap_labs.no_technical_head_assigned') }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PARENT/CHILD LABS -->
        <div v-if="lab.parent_lab || lab.sub_labs?.length > 0" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <LinkIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.lab_hierarchy') }}
            </h2>
          </div>
          <div class="p-6">
            <div class="space-y-6">
              <!-- PARENT LAB -->
              <div v-if="lab.parent_lab" class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labs.parent_lab') }}</h3>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <BuildingLibraryIcon class="h-5 w-5 text-gray-600" />
                  </div>
                  <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">{{ lab.parent_lab.name }}</p>
                    <p class="text-xs text-gray-500">{{ lab.parent_lab.code }}</p>
                  </div>
                  <Link
                    :href="route('vap-labs.labs.show', lab.parent_lab.id)"
                    class="text-blue-900 hover:text-blue-700 p-1 rounded hover:bg-blue-50"
                    :title="$t('gestlab.general.labels.vap_labs.buttons.view')"
                  >
                    <ArrowRightIcon class="h-4 w-4" />
                  </Link>
                </div>
              </div>

              <!-- SUB LABS -->
              <div v-if="lab.sub_labs?.length > 0" class="space-y-3">
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labs.sub_labs') }}</h3>
                  <span class="text-xs text-gray-500">{{ lab.sub_labs.length }} {{ $t('gestlab.general.labels.vap_labs.items') }}</span>
                </div>
                <div class="space-y-2">
                  <div 
                    v-for="subLab in lab.sub_labs"
                    :key="subLab.id"
                    class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50"
                  >
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                      <BeakerIcon class="h-4 w-4 text-blue-900" />
                    </div>
                    <div class="flex-1">
                      <p class="text-sm font-medium text-gray-900">{{ subLab.name }}</p>
                      <p class="text-xs text-gray-500">{{ subLab.code }}</p>
                    </div>
                    <Link
                      :href="route('vap-labs.labs.show', subLab.id)"
                      class="text-blue-900 hover:text-blue-700 p-1 rounded hover:bg-blue-50"
                      :title="$t('gestlab.general.labels.vap_labs.buttons.view')"
                    >
                      <ArrowRightIcon class="h-4 w-4" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3) -->
      <div class="space-y-6">
        <!-- STATISTICS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labs.stats.title') }}
          </h3>
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.total_equipment') }}</span>
              <span class="font-semibold text-blue-900">{{ stats.total_equipment || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.active_tests') }}</span>
              <span class="font-semibold text-green-600">{{ stats.active_tests || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.staff_count') }}</span>
              <span class="font-semibold text-yellow-600">{{ stats.staff_count || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.created') }}</span>
              <span class="font-semibold text-gray-900">{{ formatDate(lab.created_at) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.last_updated') }}</span>
              <span class="font-semibold text-gray-900">{{ formatDate(lab.updated_at) }}</span>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labs.actions.title') }}
          </h3>
          <div class="space-y-3">
            <Link
              :href="route('vap-labs.labs.edit', lab.id)"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 transition-colors duration-200"
            >
              <PencilSquareIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.buttons.edit_lab') }}
            </Link>
            <button
              v-if="!lab.deleted_at"
              @click="confirmDelete"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-600 transition-colors duration-200"
            >
              <TrashIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.buttons.delete_lab') }}
            </button>
            <button
              v-else
              @click="restoreLab"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-yellow-300 bg-white px-4 py-3 text-sm font-semibold text-yellow-700 hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-yellow-600 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.buttons.restore_lab') }}
            </button>
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
        {{ lab.name }}
      </p>
      <p class="mt-1 text-xs text-gray-500">
        {{ lab.code }}
      </p>
    </template>
  </ConfirmationModal>
</template>

<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import ConfirmationModal from '@/Components/confirm-dialog.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import {
  BeakerIcon,
  PencilSquareIcon,
  ArrowLeftIcon,
  InformationCircleIcon,
  UserGroupIcon,
  UserIcon,
  CogIcon,
  LinkIcon,
  BuildingLibraryIcon,
  ArrowRightIcon,
  TrashIcon,
  ArrowPathIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  lab: Object,
  stats: Object,
})

const showDeleteModal = ref(false)

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const confirmDelete = () => {
  showDeleteModal.value = true
}

const deleteLab = () => {
  router.delete(route('vap-labs.labs.destroy', props.lab.id), {
    onSuccess: () => {
      router.visit(route('vap-labs.labs.index'))
    },
  })
}

const restoreLab = () => {
  if (confirm('Are you sure you want to restore this lab?')) {
    router.post(route('vap-labs.labs.restore', props.lab.id), {}, {
      onSuccess: () => {
        router.reload()
      },
    })
  }
}
</script>
