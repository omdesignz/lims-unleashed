<template>
  
    <div class="space-y-8">
      <!-- HEADER CARD -->
      <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
              <DocumentDuplicateIcon class="h-6 w-6" />
            </div>
            <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.iso_revisions.page_title') }}
              <span class="ml-2 text-lg font-normal text-blue-900 dark:text-blue-300">
                {{ certificate.code }}
              </span>
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
              {{ $t('gestlab.general.labels.iso_revisions.page_description') }}
              <span class="font-semibold text-blue-900 dark:text-blue-300">
                {{ certificate.product?.name || certificate.code }}
              </span>
              {{ $t('gestlab.general.labels.iso_revisions.for_customer') }}
              <span class="font-semibold text-blue-900 dark:text-blue-300">
                {{ certificate.customer?.name }}
              </span>
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20">
              v{{ currentRevision?.version || '1.0' }}
            </span>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                  :class="certificate.status === 1 
                    ? 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20' 
                    : 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20'">
              {{ $t(`gestlab.general.labels.iso_revisions.status.${certificate.status}`) }}
            </span>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT SECTION -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN - REVISION HISTORY -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- CURRENT REVISION SECTION -->
          <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
            <!-- GRADIENT HEADER -->
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <DocumentCheckIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.current_version') }}
              </h2>
            </div>
            
            <!-- CURRENT REVISION INFO -->
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <HashtagIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.iso_revisions.version') }}
                  </label>
                  <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-blue-900">
                      v{{ currentRevision?.version || '1.0' }}
                    </span>
                    <span class="text-sm text-slate-500 dark:text-slate-400">
                      ({{ $t('gestlab.general.labels.iso_revisions.revision') }} {{ currentRevision?.revision_number || 1 }})
                    </span>
                  </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <CalendarIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.iso_revisions.effective_date') }}
                  </label>
                  <p class="text-sm text-slate-900 dark:text-white">
                    {{ formatDate(currentRevision?.effective_date || certificate.validated_at) }}
                  </p>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                    <UserIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.iso_revisions.approved_by') }}
                  </label>
                  <p class="text-sm text-slate-900 dark:text-white">
                    {{ currentRevision?.approved_by?.name || certificate.validated_by || $t('gestlab.general.labels.iso_revisions.not_approved') }}
                  </p>
                </div>
              </div>

              <!-- CHANGE REASON -->
              <div class="mt-6 space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-1">
                  <ChatBubbleLeftRightIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.iso_revisions.change_reason') }}
                </label>
                <p class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                  {{ currentRevision?.change_reason || $t('gestlab.general.labels.iso_revisions.initial_issue') }}
                </p>
              </div>
            </div>
          </div>

          <!-- REVISION HISTORY SECTION -->
          <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
              <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                  <ClockIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
                  {{ $t('gestlab.general.labels.iso_revisions.revision_history') }}
                  <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
                    ({{ revisions.total }} {{ $t('gestlab.general.labels.iso_revisions.revisions') }})
                  </span>
                </h2>
                <div class="flex items-center gap-3">
                  <button 
                    @click="compareModalOpen = true"
                    type="button"
                    :disabled="selectedRevisions.length !== 2"
                    :class="[
                      'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                      selectedRevisions.length === 2
                        ? 'bg-blue-900 text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed dark:bg-slate-900 dark:text-slate-500'
                    ]"
                  >
                    <ArrowsRightLeftIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.iso_revisions.compare.title') }}
                  </button>
                  <button 
                    @click="createRevisionModalOpen = true"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-950 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-900 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200 dark:focus:ring-blue-400 dark:focus:ring-offset-slate-950"
                  >
                    <DocumentPlusIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.iso_revisions.new_revision') }}
                  </button>
                </div>
              </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="revisions.data.length === 0" class="p-12 text-center">
              <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
              <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
                {{ $t('gestlab.general.labels.iso_revisions.no_revisions_title') }}
              </h3>
              <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                {{ $t('gestlab.general.labels.iso_revisions.no_revisions_description') }}
              </p>
              <button 
                @click="createRevisionModalOpen = true"
                type="button"
                class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <DocumentPlusIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.create_first_revision') }}
              </button>
            </div>

            <!-- REVISION LIST -->
            <div v-else class="divide-y divide-gray-200">
              <div 
                v-for="(revision, index) in revisions.data"
                :key="revision.id"
                class="group px-6 py-4 transition-colors duration-150 hover:bg-slate-50 dark:hover:bg-slate-900/60"
                v-motion
                :initial="{ opacity: 0, x: -20 }"
                :enter="{ opacity: 1, x: 0 }"
                :delay="index * 50"
              >
                <div class="flex items-start gap-4">
                  <!-- REVISION SELECTOR -->
                  <div class="flex items-center mt-1">
                    <input 
                      type="checkbox" 
                      :id="`revision-${revision.id}`"
                      v-model="selectedRevisions" 
                      :value="revision.id"
                      :disabled="selectedRevisions.length >= 2 && !selectedRevisions.includes(revision.id)"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </div>

                  <!-- REVISION CONTENT -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-3">
                        <!-- REVISION BADGE -->
                        <span :class="[
                          'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium',
                          getRevisionBadgeClass(revision.change_type)
                        ]">
                          {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revision.change_type}`) }}
                        </span>
                        
                        <div>
                          <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                            {{ $t('gestlab.general.labels.iso_revisions.version') }} {{ revision.version }}
                          </h3>
                          <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ $t('gestlab.general.labels.iso_revisions.revision') }} {{ revision.revision_number }}
                            • {{ formatDate(revision.effective_date) }}
                          </p>
                        </div>
                      </div>
                      
                      <!-- ACTION BUTTONS -->
                      <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                        <button 
                          @click="viewRevision(revision)"
                          type="button"
                          class="text-blue-900 hover:text-blue-800 p-1 rounded-lg hover:bg-blue-50"
                          :title="$t('gestlab.general.labels.iso_revisions.view')"
                        >
                          <EyeIcon class="h-5 w-5" />
                        </button>
                        <button 
                          @click="compareWithCurrent(revision)"
                          type="button"
                          class="text-blue-900 hover:text-blue-800 p-1 rounded-lg hover:bg-blue-50"
                          :title="$t('gestlab.general.labels.iso_revisions.compare_with_current')"
                        >
                          <ArrowsRightLeftIcon class="h-5 w-5" />
                        </button>
                        <!-- <button 
                          v-if="$page.props.auth.user.can.restore_revisions && !revision.is_current"
                          @click="openRestoreModal(revision)"
                          type="button"
                          class="text-green-600 hover:text-green-800 p-1 rounded-lg hover:bg-green-50"
                          :title="$t('gestlab.general.labels.iso_revisions.restore')"
                        >
                          <ArrowPathIcon class="h-5 w-5" />
                        </button> -->
                        <button 
                          v-if="!revision.is_current"
                          @click="openRestoreModal(revision)"
                          type="button"
                          class="text-green-600 hover:text-green-800 p-1 rounded-lg hover:bg-green-50"
                          :title="$t('gestlab.general.labels.iso_revisions.restore')"
                        >
                          <ArrowPathIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </div>

                    <!-- CHANGE DETAILS -->
                    <div class="mt-3">
                      <p class="line-clamp-2 text-sm text-slate-700 dark:text-slate-200">
                        {{ revision.change_reason }}
                      </p>
                      <div class="mt-2 flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <span class="flex items-center gap-1">
                          <UserIcon class="h-3 w-3" />
                          {{ revision.created_by?.name }}
                        </span>
                        <span class="flex items-center gap-1">
                          <CheckCircleIcon class="h-3 w-3" />
                          {{ revision.approved_by?.name || $t('gestlab.general.labels.iso_revisions.not_approved') }}
                        </span>
                      </div>

                      <!-- ISO COMPLIANCE TAGS -->
                      <div class="mt-2 flex flex-wrap gap-1">
                        <span v-if="revision.compliance_metadata?.iso_section"
                              class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-900">
                          ISO {{ revision.compliance_metadata.iso_section }}
                        </span>
                        <span v-if="revision.compliance_metadata?.change_category"
                              :class="[
                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                revision.compliance_metadata.change_category === 'CRITICAL'
                                  ? 'bg-red-100 text-red-800'
                                  : revision.compliance_metadata.change_category === 'ROUTINE'
                                  ? 'bg-blue-100 text-blue-800'
                                  : 'bg-yellow-100 text-yellow-800'
                              ]">
                          {{ $t(`gestlab.general.labels.iso_revisions.categories.${revision.compliance_metadata.change_category}`) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- PAGINATION -->
            <div v-if="revisions.data.length > 0" class="border-t border-gray-200 px-6 py-4">
              <Pagination :links="revisions.links" />
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - ACTIONS & INFO -->
        <div class="space-y-6">
          <!-- ACTIONS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.iso_revisions.actions.title') }}
            </h3>
            <div class="space-y-4">
              <!-- VIEW AUDIT TRAIL -->
              <button 
                @click="viewAuditTrail"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              >
                <ClipboardDocumentListIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.view_audit_trail') }}
              </button>

              <!-- EXPORT REVISION HISTORY -->
              <button 
                @click="exportRevisionHistory"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              >
                <ArrowDownTrayIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.export_history') }}
              </button>
            </div>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.iso_revisions.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.stats.total_revisions') }}</span>
                  <span class="font-semibold text-blue-900">{{ revisions.total }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.stats.compliance_rate') }}</span>
                  <span class="font-semibold text-green-600">{{ complianceStats.compliance_rate }}%</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.stats.last_audit') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatDate(complianceStats.last_audit) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- RECENT ACTIVITY CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <ClockIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.iso_revisions.recent_activity') }}
            </h3>
            <div class="space-y-4">
              <div v-for="activity in activityLogs" :key="activity.id" class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                    <UserIcon class="h-4 w-4 text-blue-900" />
                  </div>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-sm text-gray-900">
                    <span class="font-medium">{{ activity.causer?.name || 'System' }}</span>
                    {{ activity.description }}
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ formatDateTime(activity.created_at) }}
                  </p>
                </div>
              </div>
              <button 
                @click="viewAuditTrail"
                v-if="activityLogs.length > 0"
                type="button"
                class="w-full text-center text-sm text-blue-900 hover:text-blue-800 font-medium mt-2"
              >
                {{ $t('gestlab.general.labels.iso_revisions.view_all_activity') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- FOOTER ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          {{ $t('gestlab.general.labels.iso_revisions.footer_note', { 
            certificate: certificate.code,
            date: formatDate(certificate.created_at)
          }) }}
        </div>
        <div class="flex items-center gap-4">
          <button 
            @click="router.get(route('qualitycertificates.show', certificate.id))"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.back_to_certificate') }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODALS -->
    <!-- Compare Revisions Modal -->
    <ComparisonModal 
      :show="compareModalOpen"
      :certificate="certificate"
      :revisionIds="selectedRevisions"
      @close="compareModalOpen = false"
      @compared="handleCompared"
    />

    <!-- Create Revision Modal -->
    <CreateRevisionModal 
      :show="createRevisionModalOpen"
      :certificate="certificate"
      :approvers="approvers"
      @close="createRevisionModalOpen = false"
      @created="handleRevisionCreated"
    />

    <!-- Restore Revision Modal -->
    <RestoreRevisionModal 
      :show="restoreModalOpen"
      :revision="selectedRevision"
      :certificate="certificate"
      :approvers="approvers"
      @close="restoreModalOpen = false"
      @restored="handleRevisionRestored"
    />

</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";
import Pagination from '@/Components/Pagination.vue'
import ComparisonModal from './Partials/ComparisonModal.vue'
import CreateRevisionModal from './Partials/CreateRevisionModal.vue'
import RestoreRevisionModal from './Partials/RestoreRevisionModal.vue'

// Icons - using Heroicons v2
import {
  DocumentDuplicateIcon,
  DocumentCheckIcon,
  HashtagIcon,
  CalendarIcon,
  UserIcon,
  ChatBubbleLeftRightIcon,
  ClockIcon,
  ArrowsRightLeftIcon,
  EyeIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
  DocumentPlusIcon,
  ClipboardDocumentListIcon,
  ArrowDownTrayIcon,
  ArrowLeftIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  revisions: Object,
  currentRevision: Object,
  activityLogs: Array,
  complianceStats: Object,
  approvers: Array,
})

const selectedRevisions = ref([])
const selectedRevision = ref(null)
const compareModalOpen = ref(false)
const createRevisionModalOpen = ref(false)
const restoreModalOpen = ref(false)

// Methods
const getRevisionBadgeClass = (changeType) => {
  const classes = {
    CREATED: 'bg-green-100 text-green-800',
    UPDATED: 'bg-blue-100 text-blue-800',
    CORRECTED: 'bg-yellow-100 text-yellow-800',
    REISSUED: 'bg-purple-100 text-purple-800',
    WITHDRAWN: 'bg-red-100 text-red-800',
  }
  return classes[changeType] || 'bg-gray-100 text-gray-800'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const viewRevision = (revision) => {
  router.visit(route('qualitycertificates.iso-revisions.show', {
    certificate: props.certificate.id,
    revision: revision.id
  }))
}

// const compareWithCurrent = (revision) => {
//   selectedRevisions.value = [revision.id, props.currentRevision?.id]
//   compareModalOpen.value = true
// }

const compareWithCurrent = (revision) => {
  // Use the compare-two route instead
  router.visit(route('qualitycertificates.iso-revisions.compare-two', {
    certificate: props.certificate.id,
    revision_a: revision.id,
    revision_b: props.currentRevision?.id
  }))
}

// Or if you want to use query parameters:
const compareWithCurrentQuery = (revision) => {
  router.visit(route('qualitycertificates.iso-revisions.compare', {
    certificate: props.certificate.id,
    revision_a: revision.id,
    revision_b: props.currentRevision?.id
  }))
}

const openRestoreModal = (revision) => {
  selectedRevision.value = revision
  restoreModalOpen.value = true
}

const viewAuditTrail = () => {
  router.visit(route('qualitycertificates.revisions.audit-trail', props.certificate.id))
}

const exportRevisionHistory = async () => {
  try {
    const response = await fetch(route('qualitycertificates.revisions.export', props.certificate.id), { 
      method: 'GET',
      headers: {
        'Accept': 'application/pdf',
      },
    })
    
    if (response.ok) {
      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `revision-history-${props.certificate.code}.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    }
  } catch (error) {
    console.error('Export failed:', error)
  }
}

// Event handlers
const handleCompared = () => {
  selectedRevisions.value = []
  compareModalOpen.value = false
}

const handleRevisionCreated = () => {
  createRevisionModalOpen.value = false
  router.reload({ only: ['revisions', 'currentRevision', 'activityLogs'] })
}

const handleRevisionRestored = () => {
  restoreModalOpen.value = false
  selectedRevision.value = null
  router.reload()
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.group:hover .group-hover\:opacity-100 {
  opacity: 1 !important;
}
</style>
