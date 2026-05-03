<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            Ocorrência #{{ record.data?.occurrence_no }}
          </h1>
          <div class="mt-2 flex flex-wrap items-center gap-4">
            <!-- STATUS BADGE -->
            <span :class="[
              'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
              getStatusColor(record.data?.status)
            ]">
              <ClockIcon class="h-4 w-4" />
              {{ record.data?.status || 'Sem Status' }}
            </span>
            
            <!-- CATEGORY BADGE -->
            <span v-if="record.data?.category" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
              <FolderIcon class="h-4 w-4" />
              {{ record.data?.category }}
            </span>
            
            <!-- ORIGIN BADGE -->
            <span v-if="record.data?.origin" class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-3 py-1 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-700/10">
              <MapPinIcon class="h-4 w-4" />
              {{ record.data?.origin }}
            </span>
            
            <!-- EFFECTIVENESS BADGE -->
            <span v-if="record.data?.was_effective !== null" :class="[
              'inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset',
              record.data?.was_effective ? 'bg-green-100 text-green-800 ring-green-600/20' : 'bg-red-100 text-red-800 ring-red-600/20'
            ]">
              <CheckCircleIcon class="h-4 w-4" />
              {{ record.data?.was_effective ? 'Efetiva' : 'Não Efetiva' }}
            </span>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
          <Link
            as="button" 
            :href="route('occurrences.index')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.buttons.back') }}
        </Link>
          
          <Link 
            :href="route('occurrences.edit', { occurrence: record.data?.id })"
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
        <!-- ISSUE DESCRIPTION CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.occurrences.issue_description') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.issue_description }}</p>
            </div>
            
            <!-- REPORTING INFO -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
              <div>
                <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.occurrences.date_reported') }}</h3>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                  <p class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.date_reported) }}</p>
                </div>
              </div>
              
              <div v-if="record.data?.responsible_name">
                <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.occurrences.responsible_name') }}</h3>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                  <p class="text-sm font-medium text-gray-900">{{ record.data?.responsible_name }}</p>
                </div>
              </div>
              
              <div v-if="record.data?.department">
                <h3 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.occurrences.department_id') }}</h3>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                  <p class="text-sm font-medium text-gray-900">{{ record.data?.department }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ANALYSIS & INVESTIGATION CARD -->
        <div v-if="record.data?.analysis || record.data?.cause_corrective_actions || record.data?.effect_corrective_actions" 
             class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <MagnifyingGlassIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.occurrences.analysis_investigation') }}
            </h2>
          </div>

          <div class="p-6 space-y-6">
            <!-- ANALYSIS -->
            <div v-if="record.data?.analysis" class="space-y-3">
              <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.analysis') }}</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.analysis }}</p>
              </div>
            </div>
            
            <!-- CAUSE ANALYSIS -->
            <div v-if="record.data?.cause_corrective_actions" class="space-y-3">
              <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.cause_corrective_actions') }}</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.cause_corrective_actions }}</p>
              </div>
            </div>
            
            <!-- EFFECT ANALYSIS -->
            <div v-if="record.data?.effect_corrective_actions" class="space-y-3">
              <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.effect_corrective_actions') }}</h3>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.effect_corrective_actions }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- CORRECTIVE ACTION CARD -->
        <div v-if="record.data?.corrective_action" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
              <WrenchIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.occurrences.corrective_action') }}
            </h2>
          </div>

          <div class="p-6">
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
              <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ record.data?.corrective_action }}</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- IMPLEMENTATION DATE -->
              <div v-if="record.data?.implementation_date" class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.implementation_date') }}</h3>
                <div class="bg-white rounded-lg border border-gray-200 p-3">
                  <p class="text-sm font-medium text-gray-900">{{ formatDate(occurrence.implementation_date) }}</p>
                </div>
              </div>
              
              <!-- EFFECTIVENESS -->
              <div v-if="record.data?.was_effective !== null" class="space-y-3">
                <h3 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.was_effective') }}</h3>
                <div :class="[
                  'rounded-lg border p-3',
                  record.data?.was_effective ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
                ]">
                  <p :class="[
                    'text-sm font-medium',
                    record.data?.was_effective ? 'text-green-800' : 'text-red-800'
                  ]">
                    {{ record.data?.was_effective ? $t('gestlab.general.labels.occurrences.effective') : $t('gestlab.general.labels.occurrences.not_effective') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- STATUS & TIMELINE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ClockIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.timeline') }}
          </h3>
          <div class="space-y-4">
            <!-- TIMELINE ITEMS -->
            <div class="space-y-3">
              <div v-if="record.data?.date_reported" class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                  <CalendarIcon class="h-4 w-4 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_reported') }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(record.data?.date_reported) }}</p>
                </div>
              </div>
              
              <div v-if="record.data?.notification_date" class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                  <BellIcon class="h-4 w-4 text-blue-900" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.notification_date') }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(record.data?.notification_date) }}</p>
                </div>
              </div>
              
              <div v-if="record.data?.date_resolved" class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                  <CheckCircleIcon class="h-4 w-4 text-green-800" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_resolved') }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(record.data?.date_resolved) }}</p>
                </div>
              </div>
              
              <div v-if="record.data?.date_closed" class="flex items-start gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                  <LockClosedIcon class="h-4 w-4 text-gray-800" />
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.date_closed') }}</p>
                  <p class="text-xs text-gray-500">{{ formatDate(record.data?.date_closed) }}</p>
                </div>
              </div>
            </div>
            
            <!-- CURRENT STATUS -->
            <div class="pt-4 border-t border-gray-200">
              <h4 class="text-sm font-medium text-gray-900 mb-2">{{ $t('gestlab.general.labels.occurrences.current_status') }}</h4>
              <div :class="[
                'rounded-lg p-3 text-center',
                getStatusBgColor(record.data?.status)
              ]">
                <p class="text-sm font-semibold text-gray-900">{{ record.data?.status?.name || 'Não Definido' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- CLIENT PROCESS CARD -->
        <div v-if="record.data?.client_process_open_notification_date || record.data?.client_process_close_notification_date || record.data?.client_acceptance !== null" 
             class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <UsersIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.client_process') }}
          </h3>
          <div class="space-y-4">
            <!-- ACCEPTANCE STATUS -->
            <div v-if="record.data?.client_acceptance !== null" class="space-y-3">
              <h4 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.occurrences.client_acceptance') }}</h4>
              <div :class="[
                'rounded-lg border p-3',
                record.data?.client_acceptance ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'
              ]">
                <p :class="[
                  'text-sm font-medium',
                  record.data?.client_acceptance ? 'text-green-800' : 'text-red-800'
                ]">
                  {{ record.data?.client_acceptance ? $t('gestlab.general.labels.occurrences.accepted') : $t('gestlab.general.labels.occurrences.rejected') }}
                </p>
                <p v-if="record.data?.client_acceptance_comments" class="text-xs text-gray-600 mt-2 whitespace-pre-wrap">
                  {{ record.data?.client_acceptance_comments }}
                </p>
              </div>
            </div>
            
            <!-- CLIENT PROCESS DATES -->
            <div class="space-y-2">
              <div v-if="record.data?.client_process_open_notification_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.client_process_open_notification_date') }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.client_process_open_notification_date) }}</span>
              </div>
              
              <div v-if="record.data?.client_process_close_notification_date" class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.client_process_close_notification_date') }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatDate(record.data?.client_process_close_notification_date) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- RISK & COMPLIANCE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ShieldCheckIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.risk_compliance') }}
          </h3>
          <div class="space-y-3">
            <!-- RISK BUDGET -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.has_risk_correction_budget') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.has_risk_correction_budget ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ record.data?.has_risk_correction_budget ? $t('gestlab.general.labels.occurrences.yes') : $t('gestlab.general.labels.occurrences.no') }}
              </span>
            </div>
            
            <!-- REASON FOR NO BUDGET -->
            <div v-if="record.data?.reason_for_no_risk_correction_budget" class="space-y-2">
              <p class="text-xs text-gray-600">{{ $t('gestlab.general.labels.occurrences.reason_for_no_risk_correction_budget') }}</p>
              <div class="bg-gray-50 rounded-lg p-2">
                <p class="text-xs text-gray-700">{{ record.data?.reason_for_no_risk_correction_budget }}</p>
              </div>
            </div>
            
            <!-- NON-CONFORMITY TERMS -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.has_non_conformity_terms') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.has_non_conformity_terms ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
              ]">
                {{ record.data?.has_non_conformity_terms ? $t('gestlab.general.labels.occurrences.yes') : $t('gestlab.general.labels.occurrences.no') }}
              </span>
            </div>
            
            <!-- UPDATE RISK MATRIX -->
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.update_risk_matrix') }}</span>
              <span :class="[
                'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
                record.data?.update_risk_matrix ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
              ]">
                {{ record.data?.update_risk_matrix ? $t('gestlab.general.labels.occurrences.updated') : $t('gestlab.general.labels.occurrences.not_updated') }}
              </span>
            </div>
            
          </div>
        </div>

        <!-- METADATA CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.metadata') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.created_at') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ formatDateTime(record.data?.created_at) }}</span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.updated_at') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ formatDateTime(record.data?.updated_at) }}</span>
            </div>
            
            <div v-if="record.data?.deleted_at" class="flex items-center justify-between">
              <span class="text-sm text-red-600">{{ $t('gestlab.general.labels.occurrences.deleted_at') }}</span>
              <span class="text-sm font-medium text-red-900">{{ formatDateTime(record.data?.deleted_at) }}</span>
            </div>
            
            <div v-if="record.data?.user" class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.occurrences.created_by') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ record.data?.user.name }}</span>
            </div>
          </div>
        </div>

        <!-- OBSERVATIONS CARD -->
        <div v-if="record.data?.obs" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.occurrences.observations') }}
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
        {{ $t('gestlab.general.labels.occurrences.last_updated') }}: {{ formatDateTime(record.data?.updated_at) }}
      </div>
      <div class="flex items-center gap-3">
        <button 
          @click="markAsResolved"
          v-if="!record.data?.date_resolved"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <CheckCircleIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.occurrences.mark_resolved') }}
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
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  ExclamationTriangleIcon,
  ArrowLeftIcon,
  PencilIcon,
  DocumentTextIcon,
  FolderIcon,
  MapPinIcon,
  ClockIcon,
  CheckCircleIcon,
  MagnifyingGlassIcon,
  WrenchIcon,
  UsersIcon,
  ShieldCheckIcon,
  InformationCircleIcon,
  ChatBubbleLeftRightIcon,
  BellIcon,
  LockClosedIcon,
  TrashIcon,
  CalendarIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  record: Object
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
  if (!status) return 'bg-gray-100 text-gray-800 ring-gray-600/20'
  
  const colors = {
    'open': 'bg-yellow-100 text-yellow-800 ring-yellow-600/20',
    'in_progress': 'bg-blue-100 text-blue-800 ring-blue-600/20',
    'resolved': 'bg-green-100 text-green-800 ring-green-600/20',
    'closed': 'bg-gray-100 text-gray-800 ring-gray-600/20'
  }
  
  return colors[status.toLowerCase()] || 'bg-gray-100 text-gray-800 ring-gray-600/20'
}

const getStatusBgColor = (status) => {
  if (!status) return 'bg-gray-50 border-gray-200'
  
  const colors = {
    'open': 'bg-yellow-50 border-yellow-200',
    'in_progress': 'bg-blue-50 border-blue-200',
    'resolved': 'bg-green-50 border-green-200',
    'closed': 'bg-gray-50 border-gray-200'
  }
  
  return colors[status.toLowerCase()] || 'bg-gray-50 border-gray-200'
}

const markAsResolved = () => {
  if (confirm('Tem certeza que deseja marcar esta ocorrência como resolvida?')) {
    router.patch(route('occurrences.update', props.record.data?.id), {
      date_resolved: new Date().toISOString().split('T')[0],
      status_id: 3 // Assuming 3 is the ID for "resolved" status
    })
  }
}

const confirmDelete = () => {
  if (confirm('Tem certeza que deseja excluir esta ocorrência?')) {
    router.delete(route('occurrences.destroy', props.record.data?.id))
  }
}
</script>