<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <WrenchScrewdriverIcon class="h-7 w-7 text-blue-900" />
            {{ record.data?.name }}
          </h1>
          <div class="mt-2 flex flex-wrap items-center gap-4">
            <!-- TASK NUMBER BADGE -->
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
              <HashtagIcon class="h-4 w-4" />
              {{ record.data?.maintenance_task_no }}
            </span>
            
            <!-- CATEGORY BADGE -->
            <span class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-3 py-1 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-700/10">
              <FolderIcon class="h-4 w-4" />
              {{ record.data?.category }}
            </span>
            
            <!-- STATUS BADGES -->
            <span :class="[
              'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
              record.data?.is_executed ? 'bg-green-100 text-green-800 ring-green-600/20' : 'bg-yellow-100 text-yellow-800 ring-yellow-600/20'
            ]">
              <CheckCircleIcon class="h-4 w-4" />
              {{ record.data?.is_executed ? $t('gestlab.general.labels.maintenance_tasks.status.executed') : $t('gestlab.general.labels.maintenance_tasks.status.pending') }}
            </span>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <Link
            as="button"
            :href="route('maintenancetasks.index')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.buttons.back') }}
        </Link>
          
          <Link
            as="button"
            :href="route('maintenancetasks.edit', { maintenancetask: record?.data?.id })" 
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
                <PencilIcon class="h-4 w-4" />
                {{ $t('gestlab.general.buttons.edit') }}
        </Link>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC INFORMATION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.maintenance_tasks.basic_information') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- DESCRIPTION -->
              <div class="md:col-span-2 space-y-3">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.description') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.description || $t('gestlab.general.labels.maintenance_tasks.no_description') }}</p>
                </div>
              </div>
              
              <!-- EQUIPMENT -->
              <div class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.equipment_id') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm font-medium text-gray-900">{{ record.data?.equipment || $t('gestlab.general.labels.maintenance_tasks.no_equipment') }}</p>
                  <p v-if="record.data?.equipment?.code" class="text-xs text-gray-500 mt-1">{{ record.data?.equipment.code }}</p>
                </div>
              </div>
              
            </div>
          </div>
        </div>

        <!-- SCHEDULING CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <CalendarIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.maintenance_tasks.scheduling') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <!-- DATES -->
              <div class="space-y-4">
                <div>
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.dates') }}</h3>
                  <div class="space-y-3">
                    <div>
                      <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.maintenance_tasks.due_date') }}</p>
                      <p class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.due_date) }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.maintenance_tasks.previous_date') }}</p>
                      <p class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.previous_date) }}</p>
                    </div>
                    <div>
                      <p class="text-xs text-gray-500">{{ $t('gestlab.general.labels.maintenance_tasks.next_date') }}</p>
                      <p class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.next_date) }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- PERIODICITY -->
              <div class="space-y-4">
                <div>
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.periodicity') }}</h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div v-if="record.data?.periodicity && record.data?.periodicity_unit" class="text-center">
                      <p class="text-2xl font-bold text-blue-900">{{ record.data?.periodicity }}</p>
                      <p class="text-sm text-gray-600 capitalize">{{ $t(`gestlab.general.labels.maintenance_tasks.periodicity_unit_options.${record.data?.periodicity_unit}`) }}</p>
                    </div>
                    <p v-else class="text-sm text-gray-500 text-center">{{ $t('gestlab.general.labels.maintenance_tasks.no_periodicity') }}</p>
                  </div>
                </div>
              </div>

              <!-- STATUS INDICATORS -->
              <div class="space-y-4">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.status.title') }}</h3>
                <div class="space-y-3">
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.is_planned') }}</span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      record.data?.is_planned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    ]">
                      {{ record.data?.is_planned ? $t('gestlab.general.labels.maintenance_tasks.is_planned') : $t('gestlab.general.labels.maintenance_tasks.status.unplanned') }}
                    </span>
                  </div>
                  
                  <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.is_executed') }}</span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                      record.data?.is_executed ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'
                    ]">
                      {{ record.data?.is_executed ? $t('gestlab.general.labels.maintenance_tasks.is_executed') : $t('gestlab.general.labels.maintenance_tasks.status.pending') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- CALIBRATION DETAILS CARD -->
        <div v-if="record.data?.calibration_status || record.data?.calibration_certificate_no" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <BeakerIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.maintenance_tasks.calibration_details') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- CALIBRATION INFO -->
              <div class="space-y-4">
                <div>
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.calibration_status') }}</h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <span :class="[
                      'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium capitalize',
                      getStatusColor(record.data?.calibration_status)
                    ]">
                      {{ $t('gestlab.general.labels.maintenance_tasks.status.' + record.data?.calibration_status) || $t('gestlab.general.labels.maintenance_tasks.no_status') }}
                    </span>
                  </div>
                </div>
                
                <div v-if="record.data?.calibration_certificate_no">
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.calibration_certificate_no') }}</h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm font-medium text-gray-900">{{ record.data?.calibration_certificate_no }}</p>
                  </div>
                </div>
              </div>

              <!-- SPECIFICATIONS -->
              <div class="space-y-4">
                <div v-if="record.data?.acceptance_criteria">
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.acceptance_criteria') }}</h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-700">{{ record.data?.acceptance_criteria }}</p>
                  </div>
                </div>
                
                <div v-if="record.data?.range">
                  <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.range') }}</h3>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-700">{{ record.data?.range }}</p>
                  </div>
                </div>
              </div>
              
              <!-- CALIBRATION POINTS -->
              <div v-if="record.data?.calibration_points" class="md:col-span-2 space-y-4">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.calibration_points') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.calibration_points }}</p>
                </div>
              </div>
              
              <!-- RESULT -->
              <div v-if="record.data?.result" class="md:col-span-2 space-y-4">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.result') }}</h3>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.result }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- SUPPLIER & COST CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <TruckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.supplier_cost') }}
          </h3>
          <div class="space-y-4">
            <!-- SUPPLIER INFO -->
            <div v-if="record.data?.executed_by_supplier && record.data?.supplier" class="space-y-3">
              <div class="bg-gradient-to-r from-blue-50 to-white rounded-lg p-4 border border-blue-100">
                <h4 class="text-sm font-semibold text-blue-900 mb-2">{{ $t('gestlab.general.labels.maintenance_tasks.supplier_id') }}</h4>
                <p class="text-sm text-gray-900">{{ record.data?.supplier.name }}</p>
                <p v-if="record.data?.supplier" class="text-xs text-gray-500 mt-1">{{ record.data?.supplier }}</p>
              </div>
            </div>

            <div v-else class="bg-gradient-to-r from-blue-50 to-white rounded-lg p-4 border border-blue-100 space-y-3">
              <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.maintenance_tasks.no_supplier') }}</p>
            </div>
            
            <!-- COST -->
            <div class="space-y-3">
              <h4 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.maintenance_tasks.cost') }}</h4>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-2xl font-bold text-blue-900">AOA{{ parseFloat(record.data?.cost).toFixed(2) }}</p>
              </div>
            </div>
            
            <!-- EXECUTION STATUS -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.executed_by_supplier') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.executed_by_supplier ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ record.data?.executed_by_supplier ? $t('gestlab.general.labels.maintenance_tasks.yes') : $t('gestlab.general.labels.maintenance_tasks.no') }}
              </span>
            </div>
          </div>
        </div>

        <!-- METADATA CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.metadata') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.created_at') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ formatDateTime(record.data?.created_at) }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.maintenance_tasks.updated_at') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ formatDateTime(record.data?.updated_at) }}</span>
            </div>
            
            <div v-if="record.data?.deleted_at" class="flex items-center justify-between">
              <span class="text-sm text-red-600">{{ $t('gestlab.general.labels.maintenance_tasks.deleted_at') }}</span>
              <span class="text-sm font-medium text-red-900">{{ formatDateTime(record.data?.deleted_at) }}</span>
            </div>
          </div>
        </div>

        <!-- OBSERVATIONS CARD -->
        <div v-if="record.data?.obs" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.maintenance_tasks.observations') }}
          </h3>
          <div class="bg-gray-50 rounded-lg p-4">
            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.obs }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.maintenance_tasks.last_updated') }}: {{ formatDateTime(record.data?.updated_at) }}
      </div>
      <div class="flex items-center gap-3">
        <button 
          @click="markAsExecuted"
          v-if="!record.data?.is_executed"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <CheckCircleIcon class="h-4 w-4" />
          {{ $t('buttons.mark_executed') }}
        </button>
        
        <button 
          @click="confirmDelete"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <TrashIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.delete') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  WrenchScrewdriverIcon,
  ArrowLeftIcon,
  PencilIcon,
  InformationCircleIcon,
  FolderIcon,
  HashtagIcon,
  CheckCircleIcon,
  CalendarIcon,
  BeakerIcon,
  TruckIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  record: Object,
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString()
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const editTask = () => {
  router.get(route('maintenancetasks.edit', { maintenancetask: form.id }))
}

const markAsExecuted = () => {
  if (confirm('Tem certeza que deseja marcar esta tarefa como executada?')) {
    router.patch(route('maintenancetasks.update', props.record?.id), {
      is_executed: true
    })
  }
}

const confirmDelete = () => {
  if (confirm('Tem certeza que deseja excluir esta tarefa de manutenção?')) {
    router.delete(route('maintenance-tasks.destroy', props.task.id))
  }
}
</script>
