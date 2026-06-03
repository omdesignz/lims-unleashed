<template>
 
    <div class="iso-revision-show space-y-8" :class="commercialDocumentThemeClasses">
      <!-- HEADER CARD -->
      <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white/95 p-6 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.28)] backdrop-blur dark:border-slate-800 dark:bg-slate-950/85">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-950 text-white shadow-lg shadow-blue-950/20 dark:bg-blue-500 dark:text-slate-950">
              <EyeIcon class="h-6 w-6" />
            </div>
            <h1 class="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
              {{ $t('gestlab.general.labels.iso_revisions.show.title') }}
              <span class="ml-2 text-lg font-normal text-blue-900 dark:text-blue-300">
                v{{ revision.version }}
              </span>
            </h1>
            <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
              {{ $t('gestlab.general.labels.iso_revisions.show.description') }}
              <span class="font-semibold text-blue-900 dark:text-blue-300">
                {{ certificate.code }}
              </span>
              • {{ formatDate(revision.effective_date) }}
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <span :class="[
              'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium',
              revision.is_current
                ? 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20'
                : 'bg-gray-100 text-gray-800 ring-1 ring-inset ring-gray-600/20 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-600/20'
            ]">
              {{ revision.is_current ? $t('gestlab.general.labels.iso_revisions.current') : $t('gestlab.general.labels.iso_revisions.historical') }}
            </span>
            <span :class="getRevisionBadgeClass(revision.change_type)">
              {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revision.change_type}`) }}
            </span>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT SECTION -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN - REVISION DETAILS -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- SNAPSHOT DATA CARD -->
          <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
            <!-- GRADIENT HEADER -->
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <DocumentTextIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.show.snapshot_data') }}
              </h2>
            </div>
            
            <!-- SNAPSHOT CONTENT -->
            <div class="p-6">
              <!-- CERTIFICATE DATA -->
              <div class="space-y-6">
                <div>
                  <h3 class="mb-4 border-b border-slate-200 pb-2 text-base font-semibold text-slate-900 dark:border-slate-800 dark:text-white">
                    {{ $t('gestlab.general.labels.iso_revisions.show.certificate_data') }}
                  </h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(value, key) in certificateData" :key="key" class="space-y-1">
                      <label class="block text-xs font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        {{ formatFieldLabel(key) }}
                      </label>
                      <p class="text-sm text-slate-900 dark:text-white">
                        {{ formatValue(value) }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- OBSERVATIONS -->
                <div v-if="certificateData.obs" class="space-y-2">
                  <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    {{ $t('gestlab.general.labels.iso_revisions.show.observations') }}
                  </label>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900">
                    <p class="whitespace-pre-line text-sm text-slate-700 dark:text-slate-200">
                      {{ certificateData.obs }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- DIFFERENCES WITH CURRENT -->
              <div v-if="differences.length > 0" class="mt-8 border-t border-slate-200 pt-8 dark:border-slate-800">
                <h3 class="mb-4 text-base font-semibold text-slate-900 dark:text-white">
                  {{ $t('gestlab.general.labels.iso_revisions.show.differences_with_current') }}
                  <span class="text-sm font-normal text-slate-500 dark:text-slate-400">
                    ({{ differences.length }} {{ $t('gestlab.general.labels.iso_revisions.show.changes') }})
                  </span>
                </h3>
                <div class="space-y-3">
                  <div v-for="diff in differences" :key="diff.field" 
                       class="rounded-2xl border border-yellow-200 bg-yellow-50 p-4 dark:border-amber-400/20 dark:bg-amber-500/10">
                    <div class="flex items-center justify-between">
                      <span class="text-sm font-medium text-slate-900 dark:text-white">
                        {{ diff.label }}
                      </span>
                      <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-amber-500/15 dark:text-amber-200">
                        {{ $t('gestlab.general.labels.iso_revisions.show.changed') }}
                      </span>
                    </div>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                      <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">
                          {{ $t('gestlab.general.labels.iso_revisions.show.in_this_revision') }}
                        </label>
                        <p class="font-medium text-slate-900 dark:text-white">
                          {{ formatValue(diff.revision_value) }}
                        </p>
                      </div>
                      <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">
                          {{ $t('gestlab.general.labels.iso_revisions.show.current_value') }}
                        </label>
                        <p class="font-medium text-slate-900 dark:text-white">
                          {{ formatValue(diff.current_value) }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- RELATED DATA CARD -->
          <div class="overflow-hidden rounded-[26px] border border-slate-200 bg-white/95 shadow-[0_18px_50px_-24px_rgba(15,23,42,0.22)] dark:border-slate-800 dark:bg-slate-950/85">
            <div class="border-b border-slate-200 px-6 py-4 dark:border-slate-800">
              <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                <LinkIcon class="h-5 w-5 text-blue-900 dark:text-blue-300" />
                {{ $t('gestlab.general.labels.iso_revisions.show.related_data') }}
              </h2>
            </div>
            
            <div class="p-6">
              <!-- TABS FOR DIFFERENT RELATED DATA -->
              <div class="border-b border-slate-200 dark:border-slate-800">
                <nav class="-mb-px flex space-x-8">
                  <button 
                    v-for="tab in relatedDataTabs" 
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                      'whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium',
                      activeTab === tab.id
                        ? 'border-blue-900 text-blue-900 dark:border-blue-400 dark:text-blue-300'
                        : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:border-slate-700 dark:hover:text-slate-200'
                    ]"
                  >
                    {{ tab.label }}
                    <span v-if="tab.count" class="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-900 dark:bg-slate-800 dark:text-slate-200">
                      {{ tab.count }}
                    </span>
                  </button>
                </nav>
              </div>

              <!-- TAB CONTENT -->
              <div class="mt-6">
                <!-- COLLECTION PRODUCT -->
                <div v-if="activeTab === 'collection' && snapshot.relations?.collection" class="space-y-4">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="(value, key) in snapshot.relations.collection" :key="key" 
                         class="space-y-1">
                      <label class="block text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ formatFieldLabel(key) }}
                      </label>
                      <p class="text-sm text-slate-900 dark:text-white">
                        {{ formatValue(value) }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- RESULTS -->
                <div v-if="activeTab === 'results' && snapshot.relations?.results?.length > 0" class="space-y-4">
                  <div v-for="result in snapshot.relations.results" :key="result.id" 
                       class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700 dark:bg-slate-900/60">
                    <div class="flex items-center justify-between mb-3">
                      <h4 class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ result.parameter_label || result.parameter_id }}
                      </h4>
                      <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                        {{ result.type_label }}
                      </span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Result</label>
                        <p class="font-medium text-slate-900 dark:text-white">{{ result.approved_value || result.verified_value || result.inserted_value }}</p>
                      </div>
                      <div>
                        <label class="block text-xs text-slate-500 dark:text-slate-400">Unit</label>
                        <p class="text-slate-900 dark:text-white">{{ result.unit_label }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- EMPTY STATE -->
                <div v-if="!hasRelatedDataForTab(activeTab)" class="py-12 text-center">
                  <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
                  <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $t('gestlab.general.labels.iso_revisions.show.no_related_data') }}
                  </h3>
                  <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                    {{ $t('gestlab.general.labels.iso_revisions.show.no_data_for_tab') }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN - METADATA & ACTIONS -->
        <div class="space-y-6">
          <!-- REVISION METADATA CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.iso_revisions.show.revision_metadata') }}
            </h3>
            <div class="space-y-4">
              <!-- BASIC INFO -->
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.revision') }}</span>
                  <span class="text-sm font-semibold text-blue-900">#{{ revision.revision_number }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.change_type') }}</span>
                  <span :class="getRevisionBadgeClass(revision.change_type)" class="text-xs">
                    {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revision.change_type}`) }}
                  </span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.effective_date') }}</span>
                  <span class="text-sm text-gray-900">{{ formatDateTime(revision.effective_date) }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.created_by') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ revision.created_by?.name }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.approved_by') }}</span>
                  <span class="text-sm font-medium text-gray-900">{{ revision.approved_by?.name || $t('gestlab.general.labels.iso_revisions.not_approved') }}</span>
                </div>
              </div>

              <!-- ISO COMPLIANCE -->
              <div class="border-t border-gray-200 pt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">
                  {{ $t('gestlab.general.labels.iso_revisions.show.iso_compliance') }}
                </h4>
                <div class="space-y-2">
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">ISO Section</span>
                    <span class="text-xs font-medium text-blue-900">{{ revision.compliance_metadata?.iso_section || 'N/A' }}</span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">Change Category</span>
                    <span class="text-xs font-medium" :class="getCategoryClass(revision.compliance_metadata?.change_category)">
                      {{ revision.compliance_metadata?.change_category || 'N/A' }}
                    </span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-600">Risk Assessment</span>
                    <span class="text-xs font-medium" :class="getRiskClass(revision.compliance_metadata?.risk_assessment)">
                      {{ revision.compliance_metadata?.risk_assessment || 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ACTIONS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.iso_revisions.actions.title') }}
            </h3>
            <div class="space-y-3">
              <!-- COMPARE WITH CURRENT -->
              <button 
                @click="compareWithCurrent"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <ArrowsRightLeftIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.compare_with_current') }}
              </button>

              <!-- RESTORE REVISION -->
              <button 
                v-if="canRestore && !revision.is_current"
                @click="openRestoreModal"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700 shadow-sm hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2"
              >
                <ArrowPathIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.restore_this_version') }}
              </button>

              <!-- EXPORT THIS REVISION -->
              <button 
                @click="exportRevision"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <ArrowDownTrayIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.export_revision') }}
              </button>
            </div>

            <!-- QUICK LINKS -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.iso_revisions.show.quick_links') }}
              </h4>
              <div class="space-y-2">
                <a :href="route('qualitycertificates.iso-revisions.index', certificate.id)" 
                   class="block text-sm text-blue-900 hover:text-blue-800 hover:underline">
                  ← {{ $t('gestlab.general.labels.iso_revisions.back_to_revisions') }}
                </a>
                <a :href="route('qualitycertificates.show', certificate.id)" 
                   class="block text-sm text-blue-900 hover:text-blue-800 hover:underline">
                  ← {{ $t('gestlab.general.labels.iso_revisions.back_to_certificate') }}
                </a>
              </div>
            </div>
          </div>

          <!-- RELATED ACTIVITY CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
              <ClipboardDocumentListIcon class="h-5 w-5 text-blue-900" />
              {{ $t('gestlab.general.labels.iso_revisions.show.related_activity') }}
            </h3>
            <div class="space-y-3 max-h-64 overflow-y-auto">
              <div v-for="activity in relatedActivityLogs" :key="activity.id" class="text-sm">
                <div class="flex items-start gap-2">
                  <UserIcon class="h-4 w-4 text-gray-400 mt-0.5 flex-shrink-0" />
                  <div class="flex-1 min-w-0">
                    <p class="text-gray-900">
                      <span class="font-medium">{{ activity.causer?.name || 'System' }}</span>
                      {{ activity.description }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ formatDateTime(activity.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-if="relatedActivityLogs.length === 0" class="text-center py-4">
                <p class="text-sm text-gray-500">{{ $t('gestlab.general.labels.iso_revisions.show.no_activity') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- MODALS -->
    <RestoreRevisionModal 
      :show="restoreModalOpen"
      :revision="revision"
      :certificate="certificate"
      @close="restoreModalOpen = false"
      @restored="handleRevisionRestored"
    />
 
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";
import RestoreRevisionModal from './Partials/RestoreRevisionModal.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

// Icons
import {
  EyeIcon,
  DocumentTextIcon,
  LinkIcon,
  DocumentDuplicateIcon,
  ArrowsRightLeftIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ClipboardDocumentListIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  revision: Object,
  snapshot: Object,
  differences: Array,
  relatedActivityLogs: Array,
})

const activeTab = ref('collection')
const restoreModalOpen = ref(false)

// Computed properties
const certificateData = computed(() => {
  return props.snapshot?.certificate || {}
})

const relatedDataTabs = computed(() => [
  { id: 'collection', label: 'Collection', count: props.snapshot?.relations?.collection_product ? 1 : 0 },
  { id: 'results', label: 'Test Results', count: props.snapshot?.relations?.results?.length || 0 },
  { id: 'customer', label: 'Customer', count: props.snapshot?.relations?.customer ? 1 : 0 },
  { id: 'product', label: 'Product', count: props.snapshot?.relations?.product ? 1 : 0 },
])

const canRestore = computed(() => {
//   return props.$page.props.auth.user?.can?.restore_revisions || false
return true;
})

// Methods
const getRevisionBadgeClass = (changeType) => {
  const classes = {
    CREATED: 'inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800',
    UPDATED: 'inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800',
    CORRECTED: 'inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800',
    REISSUED: 'inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800',
    WITHDRAWN: 'inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800',
  }
  return classes[changeType] || 'inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800'
}

const getCategoryClass = (category) => {
  const classes = {
    CRITICAL: 'text-red-700',
    HIGH: 'text-orange-700',
    MEDIUM: 'text-yellow-700',
    LOW: 'text-green-700',
    ROUTINE: 'text-blue-700',
  }
  return classes[category] || 'text-gray-700'
}

const getRiskClass = (risk) => {
  const classes = {
    CRITICAL: 'text-red-700',
    HIGH: 'text-orange-700',
    MEDIUM: 'text-yellow-700',
    LOW: 'text-green-700',
  }
  return classes[risk] || 'text-gray-700'
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
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

const formatFieldLabel = (field) => {
  const labels = {
    code: 'Certificate Code',
    status: 'Status',
    validated_by: 'Validated By',
    validated_at: 'Validation Date',
    obs: 'Observations',
    created_at: 'Created Date',
    updated_at: 'Updated Date',
    // Add more field labels as needed
  }
  return labels[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatValue = (value) => {
  if (value === null || value === undefined) return 'N/A'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (Array.isArray(value)) return value.length > 0 ? value.join(', ') : 'None'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

const hasRelatedDataForTab = (tabId) => {
  switch (tabId) {
    case 'collection':
      return !!props.snapshot?.relations?.collection
    case 'results':
      return props.snapshot?.relations?.results?.length > 0
    case 'customer':
      return !!props.snapshot?.relations?.customer
    case 'product':
      return !!props.snapshot?.relations?.product
    default:
      return false
  }
}

// const compareWithCurrent = () => {
//   router.visit(route('qualitycertificates.iso-revisions.compare', {
//     certificate: props.certificate.id,
//     revision_a: props.revision.id,
//     revision_b: props.certificate.current_revision?.id
//   }))
// }

const compareWithCurrent = () => {

  // Use the compare-two route
  router.visit(route('qualitycertificates.iso-revisions.compare-two', {
    certificate: props.certificate.id,
    revision_a: props.revision.id,
    revision_b: props.certificate.current_revision?.id
  }))
}

const openRestoreModal = () => {
  restoreModalOpen.value = true
}

const exportRevision = async () => {
  try {
    // Export revision as PDF
    const response = await fetch(route('qualitycertificates.iso-revisions.snapshot', {
      certificate: props.certificate.id,
      revision: props.revision.id
    }), {
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
      a.download = `revision-${props.certificate.code}-v${props.revision.version}.pdf`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    }
  } catch (error) {
    console.error('Export failed:', error)
  }
}

const handleRevisionRestored = () => {
  router.reload()
}
</script>

<style scoped>
.iso-revision-show :deep(.bg-blue-900),
.iso-revision-show :deep(.bg-blue-950) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-revision-show :deep(.from-blue-900),
.iso-revision-show :deep(.from-blue-950) {
  --tw-gradient-from: rgb(var(--primary-900-rgb)) var(--tw-gradient-from-position) !important;
  --tw-gradient-to: rgb(var(--primary-900-rgb) / 0) var(--tw-gradient-to-position) !important;
}

.iso-revision-show :deep(.to-blue-800) {
  --tw-gradient-to: rgb(var(--primary-700-rgb)) var(--tw-gradient-to-position) !important;
}

.iso-revision-show :deep(.text-blue-900),
.iso-revision-show :deep(.text-blue-800),
.iso-revision-show :deep(.text-blue-700) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-revision-show :deep(.bg-blue-50),
.iso-revision-show :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-50-rgb) / 0.82) !important;
}

.iso-revision-show :deep(.border-blue-200) {
  border-color: rgb(var(--primary-200-rgb) / 0.78) !important;
}

.iso-revision-show :deep(input),
.iso-revision-show :deep(select),
.iso-revision-show :deep(textarea) {
  border-color: #d8cbb8;
  background: #fffdf7;
  color: #15231f;
  border-radius: 0.875rem;
}

.iso-revision-show :deep(input:focus),
.iso-revision-show :deep(select:focus),
.iso-revision-show :deep(textarea:focus) {
  border-color: rgb(var(--primary-500-rgb));
  box-shadow: 0 0 0 3px rgb(var(--primary-500-rgb) / 0.18);
  outline: none;
}

:global(.dark) .iso-revision-show :deep(.bg-white),
:global(.dark) .iso-revision-show :deep(.bg-gray-50),
:global(.dark) .iso-revision-show :deep(.bg-slate-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-revision-show :deep(.text-gray-900),
:global(.dark) .iso-revision-show :deep(.text-slate-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-revision-show :deep(.text-gray-700),
:global(.dark) .iso-revision-show :deep(.text-slate-700),
:global(.dark) .iso-revision-show :deep(.text-gray-600) {
  color: #cbd5e1 !important;
}

:global(.dark) .iso-revision-show :deep(input),
:global(.dark) .iso-revision-show :deep(select),
:global(.dark) .iso-revision-show :deep(textarea) {
  border-color: #315149;
  background: #10231f;
  color: #f7f1e7;
}
</style>
