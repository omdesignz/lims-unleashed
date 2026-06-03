<template>
  
    <div class="iso-revision-manager space-y-8" :class="commercialDocumentThemeClasses">
      <!-- HEADER CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <DocumentDuplicateIcon class="h-7 w-7 text-blue-900" />
              {{ $t('iso_revisions.title') }}
              <span class="text-lg font-normal text-blue-900 ml-2">
                {{ certificate.code }}
              </span>
            </h1>
            <p class="mt-2 text-gray-600">
              {{ $t('iso_revisions.description') }}
              <span class="font-semibold text-blue-900">
                {{ certificate.product?.name || certificate.code }}
              </span>
              {{ $t('iso_revisions.for_customer') }}
              <span class="font-semibold text-blue-900">
                {{ certificate.customer?.name }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
              v{{ currentRevision?.version || '1.0' }}
            </span>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium"
                  :class="certificate.status === 1 
                    ? 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20' 
                    : 'bg-yellow-100 text-yellow-800 ring-1 ring-inset ring-yellow-600/20'">
              {{ $t(`status.${certificate.status}`) }}
            </span>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT SECTION -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN - REVISION HISTORY -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- CURRENT REVISION SECTION -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <!-- GRADIENT HEADER -->
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <DocumentCheckIcon class="h-5 w-5" />
                {{ $t('iso_revisions.current_version') }}
              </h2>
            </div>
            
            <!-- CURRENT REVISION INFO -->
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <HashtagIcon class="h-4 w-4" />
                    {{ $t('iso_revisions.version') }}
                  </label>
                  <div class="flex items-center gap-2">
                    <span class="text-lg font-bold text-blue-900">
                      v{{ currentRevision?.version || '1.0' }}
                    </span>
                    <span class="text-sm text-gray-500">
                      ({{ $t('iso_revisions.revision') }} {{ currentRevision?.revision_number || 1 }})
                    </span>
                  </div>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <CalendarIcon class="h-4 w-4" />
                    {{ $t('iso_revisions.effective_date') }}
                  </label>
                  <p class="text-sm text-gray-900">
                    {{ formatDate(currentRevision?.effective_date || certificate.validated_at) }}
                  </p>
                </div>

                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <UserIcon class="h-4 w-4" />
                    {{ $t('iso_revisions.approved_by') }}
                  </label>
                  <p class="text-sm text-gray-900">
                    {{ currentRevision?.approved_by?.name || certificate.validated_by || $t('iso_revisions.not_approved') }}
                  </p>
                </div>
              </div>

              <!-- CHANGE REASON -->
              <div class="mt-6 space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ChatBubbleLeftRightIcon class="h-4 w-4" />
                  {{ $t('iso_revisions.change_reason') }}
                </label>
                <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg border border-gray-200">
                  {{ currentRevision?.change_reason || $t('iso_revisions.initial_issue') }}
                </p>
              </div>
            </div>
          </div>

          <!-- REVISION HISTORY SECTION -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
              <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <ClockIcon class="h-5 w-5 text-blue-900" />
                  {{ $t('iso_revisions.revision_history') }}
                  <span class="text-sm font-normal text-gray-500 ml-2">
                    ({{ revisions.total }} {{ $t('iso_revisions.revisions') }})
                  </span>
                </h2>
                <button 
                  @click="compareModalOpen = true"
                  type="button"
                  :disabled="selectedRevisions.length !== 2"
                  :class="[
                    'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                    selectedRevisions.length === 2
                      ? 'bg-blue-900 text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                      : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  ]"
                >
                  <ArrowsRightLeftIcon class="h-5 w-5" />
                  {{ $t('buttons.compare') }}
                </button>
              </div>
            </div>

            <!-- EMPTY STATE -->
            <div v-if="revisions.data.length === 0" class="p-12 text-center">
              <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-gray-300" />
              <h3 class="mt-4 text-sm font-semibold text-gray-900">
                {{ $t('iso_revisions.no_revisions_title') }}
              </h3>
              <p class="mt-2 text-sm text-gray-500">
                {{ $t('iso_revisions.no_revisions_description') }}
              </p>
            </div>

            <!-- REVISION LIST -->
            <div v-else class="divide-y divide-gray-200">
              <div 
                v-for="(revision, index) in revisions.data"
                :key="revision.id"
                class="px-6 py-4 hover:bg-gray-50 transition-colors duration-150"
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
                          {{ $t(`iso_revisions.change_types.${revision.change_type}`) }}
                        </span>
                        
                        <div>
                          <h3 class="text-sm font-semibold text-gray-900">
                            {{ $t('iso_revisions.version') }} {{ revision.version }}
                          </h3>
                          <p class="text-xs text-gray-500">
                            {{ $t('iso_revisions.revision') }} {{ revision.revision_number }}
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
                          :title="$t('buttons.view')"
                        >
                          <EyeIcon class="h-5 w-5" />
                        </button>
                        <button 
                          @click="compareWithCurrent(revision)"
                          type="button"
                          class="text-blue-900 hover:text-blue-800 p-1 rounded-lg hover:bg-blue-50"
                          :title="$t('buttons.compare_with_current')"
                        >
                          <ArrowsRightLeftIcon class="h-5 w-5" />
                        </button>
                        <button 
                          v-if="$page.props.auth.user.can.restore_revisions && !revision.is_current"
                          @click="openRestoreModal(revision)"
                          type="button"
                          class="text-green-600 hover:text-green-800 p-1 rounded-lg hover:bg-green-50"
                          :title="$t('buttons.restore')"
                        >
                          <ArrowPathIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </div>

                    <!-- CHANGE DETAILS -->
                    <div class="mt-3">
                      <p class="text-sm text-gray-700 line-clamp-2">
                        {{ revision.change_reason }}
                      </p>
                      <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
                        <span class="flex items-center gap-1">
                          <UserIcon class="h-3 w-3" />
                          {{ revision.created_by?.name }}
                        </span>
                        <span class="flex items-center gap-1">
                          <CheckCircleIcon class="h-3 w-3" />
                          {{ revision.approved_by?.name || $t('iso_revisions.not_approved') }}
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
                          {{ $t(`iso_revisions.categories.${revision.compliance_metadata.change_category}`) }}
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
              {{ $t('actions.title') }}
            </h3>
            <div class="space-y-4">
              <!-- CREATE NEW REVISION -->
              <button 
                @click="createRevisionModalOpen = true"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200 bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <DocumentPlusIcon class="h-5 w-5" />
                {{ $t('buttons.create_revision') }}
              </button>

              <!-- VIEW AUDIT TRAIL -->
              <button 
                @click="viewAuditTrail"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              >
                <ClipboardDocumentListIcon class="h-5 w-5" />
                {{ $t('buttons.view_audit_trail') }}
              </button>

              <!-- EXPORT REVISION HISTORY -->
              <button 
                @click="exportRevisionHistory"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
              >
                <ArrowDownTrayIcon class="h-5 w-5" />
                {{ $t('buttons.export_history') }}
              </button>
            </div>

            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('stats.total_revisions') }}</span>
                  <span class="font-semibold text-blue-900">{{ revisions.total }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('stats.compliance_rate') }}</span>
                  <span class="font-semibold text-green-600">{{ complianceStats.rate }}%</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('stats.last_audit') }}</span>
                  <span class="font-semibold text-gray-900">{{ formatDate(complianceStats.last_audit) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- CERTIFICATE STATUS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5 text-blue-900" />
              {{ $t('certificate.status.title') }}
            </h3>
            <div class="space-y-3">
              <!-- STATUS ITEM -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('certificate.status.validated') }}</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  certificate.validated_at 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-yellow-100 text-yellow-800'
                ]">
                  {{ certificate.validated_at 
                     ? formatDate(certificate.validated_at) 
                     : $t('status.pending') }}
                </span>
              </div>

              <!-- REVISION STATUS -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('certificate.status.revision_status') }}</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                  revisions.total > 1 
                    ? 'bg-blue-100 text-blue-800' 
                    : 'bg-gray-100 text-gray-800'
                ]">
                  {{ revisions.total > 1 
                     ? $t('certificate.status.revised') 
                     : $t('certificate.status.original') }}
                </span>
              </div>

              <!-- ISO COMPLIANCE -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('certificate.status.iso_compliance') }}</span>
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                  {{ $t('certificate.status.compliant') }}
                </span>
              </div>
            </div>

            <!-- RELATED INFO -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('certificate.related_info') }}
              </h4>
              <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">{{ $t('certificate.collection') }}</span>
                  <span class="font-medium text-gray-900">{{ certificate.collection_product?.collection_ref || 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">{{ $t('certificate.product') }}</span>
                  <span class="font-medium text-gray-900">{{ certificate.product?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">{{ $t('certificate.customer') }}</span>
                  <span class="font-medium text-gray-900">{{ certificate.customer?.name }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- FOOTER ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <div class="text-sm text-gray-500">
          {{ $t('iso_revisions.footer_note', { 
            certificate: certificate.code,
            date: formatDate(certificate.created_at)
          }) }}
        </div>
        <div class="flex items-center gap-4">
          <button 
            @click="router.get(route('certificates.show', certificate.id))"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('buttons.back_to_certificate') }}
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
      @close="createRevisionModalOpen = false"
      @created="handleRevisionCreated"
    />

    <!-- Restore Revision Modal -->
    <RestoreRevisionModal 
      :show="restoreModalOpen"
      :revision="selectedRevision"
      :certificate="certificate"
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
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

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
  DocumentTextIcon,
  ArrowLeftIcon,
  CheckCircleIcon,
  PlusCircleIcon,
  TrashIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  revisions: Object,
  currentRevision: Object,
  complianceStats: Object,
})

const selectedRevisions = ref([])
const selectedRevision = ref(null)
const compareModalOpen = ref(false)
const createRevisionModalOpen = ref(false)
const restoreModalOpen = ref(false)

// Computed properties
const isFormValid = computed(() => {
  // Add validation logic if needed
  return true
})

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

const compareWithCurrent = (revision) => {
  selectedRevisions.value = [revision.id, props.currentRevision?.id]
  compareModalOpen.value = true
}

const openRestoreModal = (revision) => {
  selectedRevision.value = revision
  restoreModalOpen.value = true
}

const viewAuditTrail = () => {
  router.visit(route('qualitycertificates.iso-revisions.audit-trail', props.certificate.id))
}

const exportRevisionHistory = async () => {
  try {
    const response = await fetch(route('qualitycertificates.iso-revisions.export', props.certificate.id), {
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
  router.reload({ only: ['revisions', 'currentRevision'] })
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

.iso-revision-manager :deep(.bg-blue-900),
.iso-revision-manager :deep(.bg-blue-950) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-revision-manager :deep(.from-blue-900),
.iso-revision-manager :deep(.from-blue-950) {
  --tw-gradient-from: rgb(var(--primary-900-rgb)) var(--tw-gradient-from-position) !important;
  --tw-gradient-to: rgb(var(--primary-900-rgb) / 0) var(--tw-gradient-to-position) !important;
}

.iso-revision-manager :deep(.to-blue-800) {
  --tw-gradient-to: rgb(var(--primary-700-rgb)) var(--tw-gradient-to-position) !important;
}

.iso-revision-manager :deep(.text-blue-900),
.iso-revision-manager :deep(.text-blue-800),
.iso-revision-manager :deep(.text-blue-700) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-revision-manager :deep(.bg-blue-50),
.iso-revision-manager :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-50-rgb) / 0.82) !important;
}

:global(.dark) .iso-revision-manager :deep(.bg-white),
:global(.dark) .iso-revision-manager :deep(.bg-gray-50),
:global(.dark) .iso-revision-manager :deep(.bg-blue-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-revision-manager :deep(.text-gray-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-revision-manager :deep(.text-gray-700),
:global(.dark) .iso-revision-manager :deep(.text-gray-600) {
  color: #cbd5e1 !important;
}
</style>
