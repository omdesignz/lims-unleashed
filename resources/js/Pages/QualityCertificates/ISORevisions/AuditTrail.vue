<template>

    <div class="iso-audit-trail-page space-y-8" :class="commercialDocumentThemeClasses">
      <!-- HEADER CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <ClipboardDocumentCheckIcon class="h-7 w-7 text-blue-900" />
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.title') }}
            </h1>
            <p class="mt-2 text-gray-600">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.description') }}
              <span class="font-semibold text-blue-900">
                {{ certificate.code }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
              {{ logs.total }} {{ $t('gestlab.general.labels.iso_revisions.audit_trail.entries') }}
            </span>
          </div>
        </div>
      </div>

      <!-- FILTERS CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- DATE RANGE -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.date_range') }}
            </label>
            <div class="flex gap-2">
              <input 
                type="date" 
                v-model="localFilters.date_from"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
              />
              <input 
                type="date" 
                v-model="localFilters.date_to"
                class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
              />
            </div>
          </div>

          <!-- USER FILTER -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.user') }}
            </label>
            <select 
              v-model="localFilters.user_id"
              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
            >
              <option value="">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.all_users') }}</option>
              <option v-for="user in uniqueUsers" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </div>

          <!-- ACTION TYPE FILTER -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.action_type') }}
            </label>
            <select 
              v-model="localFilters.action"
              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
            >
              <option value="">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.all_actions') }}</option>
              <option v-for="action in uniqueActions" :key="action" :value="action">
                {{ action }}
              </option>
            </select>
          </div>

          <!-- ENTITY TYPE FILTER -->
          <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.entity_type') }}
            </label>
            <select 
              v-model="localFilters.entity_type"
              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
            >
              <option value="">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.all_entities') }}</option>
              <option v-for="type in uniqueEntityTypes" :key="type" :value="type">
                {{ type }}
              </option>
            </select>
          </div>
        </div>

        <!-- FILTER ACTIONS -->
        <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
          <button 
            @click="resetFilters"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <XMarkIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.clear_filters') }}
          </button>
          <button 
            @click="applyFilters"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <FunnelIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.apply_filters') }}
          </button>
        </div>
      </div>

      <!-- AUDIT LOGS SECTION -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">
              {{ $t('gestlab.general.labels.iso_revisions.audit_trail.audit_logs') }}
            </h2>
            <div class="flex items-center gap-3">
              <button 
                @click="exportAuditLogs"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
              >
                <ArrowDownTrayIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.iso_revisions.export_logs') }}
              </button>
            </div>
          </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="logs.data.length === 0" class="p-12 text-center">
          <ClipboardDocumentCheckIcon class="mx-auto h-12 w-12 text-gray-300" />
          <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ $t('gestlab.general.labels.iso_revisions.audit_trail.no_logs_title') }}
          </h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ $t('gestlab.general.labels.iso_revisions.audit_trail.no_logs_description') }}
          </p>
          <button 
            @click="resetFilters"
            v-if="hasActiveFilters"
            type="button"
            class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
          >
            <XMarkIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.iso_revisions.clear_filters') }}
          </button>
        </div>

        <!-- AUDIT LOGS TABLE -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-300">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                  {{ $t('gestlab.general.labels.iso_revisions.audit_trail.timestamp') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.audit_trail.user') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.audit_trail.action') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.audit_trail.entity') }}
                </th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.audit_trail.details') }}
                </th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                  <span class="sr-only">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.actions') }}</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="log in logs.data" :key="log.id" 
                  class="hover:bg-gray-50 transition-colors duration-150"
                  v-motion
                  :initial="{ opacity: 0, y: 10 }"
                  :enter="{ opacity: 1, y: 0 }">
                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                  {{ formatDateTime(log.created_at) }}
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                  <div class="flex items-center gap-2">
                    <UserIcon class="h-4 w-4 text-gray-400" />
                    {{ log.causer?.name || 'System' }}
                  </div>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm">
                  <span :class="getActionBadgeClass(log.action)" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                    {{ log.action }}
                  </span>
                </td>
                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                  {{ getEntityLabel(log) }}
                </td>
                <td class="px-3 py-4 text-sm text-gray-900">
                  <p class="line-clamp-2 max-w-xs">{{ log.description }}</p>
                  <button 
                    @click="toggleLogDetails(log.id)"
                    class="text-xs text-blue-900 hover:text-blue-800 mt-1"
                  >
                    {{ expandedLogs[log.id] ? $t('gestlab.general.labels.iso_revisions.show_less') : $t('gestlab.general.labels.iso_revisions.show_more') }}
                  </button>
                  
                  <!-- EXPANDED DETAILS -->
                  <div v-if="expandedLogs[log.id]" class="mt-3 space-y-3">
                    <!-- CHANGE DETAILS -->
                    <div v-if="log.properties" class="bg-gray-50 rounded-lg p-3">
                      <h4 class="text-xs font-medium text-gray-900 mb-2">
                        {{ $t('gestlab.general.labels.iso_revisions.audit_trail.change_details') }}
                      </h4>
                      <div class="space-y-2 text-xs">
                        <div v-if="log.properties.change_reason" class="flex">
                          <span class="w-24 text-gray-600">Reason:</span>
                          <span class="flex-1 text-gray-900">{{ log.properties.change_reason }}</span>
                        </div>
                        <div v-if="log.properties.version" class="flex">
                          <span class="w-24 text-gray-600">Version:</span>
                          <span class="flex-1 text-gray-900">{{ log.properties.version }}</span>
                        </div>
                      </div>
                    </div>

                    <!-- FIELD CHANGES -->
                    <div v-if="log.properties?.attributes && log.properties?.old" class="bg-yellow-50 rounded-lg p-3">
                      <h4 class="text-xs font-medium text-gray-900 mb-2">
                        {{ $t('gestlab.general.labels.iso_revisions.audit_trail.field_changes') }}
                      </h4>
                      <div class="space-y-1">
                        <div v-for="(value, key) in log.properties.attributes" :key="key" 
                             v-if="log.properties.old[key] !== value"
                             class="flex items-center text-xs">
                          <span class="w-32 text-gray-600 truncate">{{ formatFieldLabel(key) }}:</span>
                          <span class="flex-1 text-gray-900 truncate">
                            {{ formatValue(log.properties.old[key]) }} → {{ formatValue(value) }}
                          </span>
                        </div>
                      </div>
                    </div>

                    <!-- TECHNICAL DETAILS -->
                    <div class="bg-gray-100 rounded-lg p-3">
                      <h4 class="text-xs font-medium text-gray-900 mb-2">
                        {{ $t('gestlab.general.labels.iso_revisions.audit_trail.technical_details') }}
                      </h4>
                      <div class="space-y-1 text-xs">
                        <div class="flex">
                          <span class="w-20 text-gray-600">IP:</span>
                          <span class="flex-1 text-gray-900">{{ log.ip_address || 'N/A' }}</span>
                        </div>
                        <div class="flex">
                          <span class="w-20 text-gray-600">User Agent:</span>
                          <span class="flex-1 text-gray-900 truncate">{{ log.user_agent || 'N/A' }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                  <button 
                    @click="viewLogDetails(log)"
                    class="text-blue-900 hover:text-blue-800"
                    :title="$t('gestlab.general.labels.iso_revisions.view_details')"
                  >
                    <EyeIcon class="h-5 w-5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div v-if="logs.data.length > 0" class="border-t border-gray-200 px-6 py-4">
          <Pagination :links="logs.links" />
        </div>
      </div>

      <!-- SUMMARY STATISTICS -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.total_entries') }}</p>
              <p class="mt-2 text-2xl font-bold text-blue-900">{{ logs.total }}</p>
            </div>
            <ClipboardDocumentListIcon class="h-8 w-8 text-blue-900" />
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.unique_users') }}</p>
              <p class="mt-2 text-2xl font-bold text-green-600">{{ uniqueUsers.length }}</p>
            </div>
            <UsersIcon class="h-8 w-8 text-green-600" />
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.compliance_rate') }}</p>
              <p class="mt-2 text-2xl font-bold text-green-600">{{ complianceRate }}%</p>
            </div>
            <CheckCircleIcon class="h-8 w-8 text-green-600" />
          </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.audit_trail.period') }}</p>
              <p class="mt-2 text-sm font-semibold text-gray-900">{{ auditPeriod }}</p>
            </div>
            <CalendarIcon class="h-8 w-8 text-gray-900" />
          </div>
        </div>
      </div>

      <!-- FOOTER ACTIONS -->
      <div class="flex items-center justify-between pt-6">
        <button 
          @click="router.get(route('qualitycertificates.iso-revisions.index', certificate.id))"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <ArrowLeftIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.iso_revisions.back_to_revisions') }}
        </button>
        
        <button 
          @click="refreshLogs"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <ArrowPathIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.iso_revisions.refresh') }}
        </button>
      </div>
    </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";
import Pagination from '@/Components/Pagination.vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

// Icons
import {
  ClipboardDocumentCheckIcon,
  XMarkIcon,
  FunnelIcon,
  ArrowDownTrayIcon,
  EyeIcon,
  ClipboardDocumentListIcon,
  UsersIcon,
  CheckCircleIcon,
  CalendarIcon,
  ArrowLeftIcon,
  ArrowPathIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  logs: Object,
  filters: Object,
})

// Local state
const expandedLogs = ref({})
const localFilters = reactive({
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || '',
  user_id: props.filters?.user_id || '',
  action: props.filters?.action || '',
  entity_type: props.filters?.entity_type || '',
})

// Computed properties
const uniqueUsers = computed(() => {
  const users = new Map()
  props.logs.data.forEach(log => {
    if (log.causer) {
      users.set(log.causer.id, log.causer)
    }
  })
  return Array.from(users.values())
})

const uniqueActions = computed(() => {
  const actions = new Set()
  props.logs.data.forEach(log => {
    actions.add(log.action)
  })
  return Array.from(actions).sort()
})

const uniqueEntityTypes = computed(() => {
  const types = new Set()
  props.logs.data.forEach(log => {
    types.add(log.subject_type)
  })
  return Array.from(types).sort()
})

const hasActiveFilters = computed(() => {
  return Object.values(localFilters).some(value => value !== '')
})

const complianceRate = computed(() => {
  const compliantLogs = props.logs.data.filter(log => 
    log.properties?.change_reason || 
    (log.properties?.iso_section && log.properties?.risk_assessment)
  ).length
  
  return props.logs.data.length > 0 
    ? Math.round((compliantLogs / props.logs.data.length) * 100) 
    : 100
})

const auditPeriod = computed(() => {
  if (props.logs.data.length === 0) return 'N/A'
  
  const dates = props.logs.data.map(log => new Date(log.created_at))
  const oldest = new Date(Math.min(...dates))
  const newest = new Date(Math.max(...dates))
  
  return `${oldest.toLocaleDateString()} - ${newest.toLocaleDateString()}`
})

// Methods
const getActionBadgeClass = (action) => {
  const actionMap = {
    CREATED: 'bg-green-100 text-green-800',
    UPDATED: 'bg-blue-100 text-blue-800',
    DELETED: 'bg-red-100 text-red-800',
    RESTORED: 'bg-yellow-100 text-yellow-800',
    APPROVED: 'bg-purple-100 text-purple-800',
    REJECTED: 'bg-orange-100 text-orange-800',
    REVISION_CREATED: 'bg-indigo-100 text-indigo-800',
    REVISION_RESTORE: 'bg-pink-100 text-pink-800',
  }
  return actionMap[action] || 'bg-gray-100 text-gray-800'
}

const getEntityLabel = (log) => {
  const entityMap = {
    'App\\Models\\QualityCertificate': 'Quality Certificate',
    'App\\Models\\QualityCertificateRevision': 'Revision',
    'App\\Models\\CollectionProduct': 'Collection',
    'App\\Models\\Result': 'Test Result',
  }
  return entityMap[log.subject_type] || log.subject_type?.split('\\').pop() || 'Unknown'
}

const formatDateTime = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const formatFieldLabel = (field) => {
  const labels = {
    status: 'Status',
    obs: 'Observations',
    validated_by: 'Validated By',
    validated_at: 'Validation Date',
    change_reason: 'Change Reason',
    iso_section: 'ISO Section',
    risk_assessment: 'Risk Assessment',
  }
  return labels[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatValue = (value) => {
  if (value === null || value === undefined) return 'N/A'
  if (typeof value === 'boolean') return value ? 'Yes' : 'No'
  if (Array.isArray(value)) return value.length > 0 ? JSON.stringify(value) : '[]'
  if (typeof value === 'object') return JSON.stringify(value)
  return String(value)
}

const toggleLogDetails = (logId) => {
  expandedLogs.value[logId] = !expandedLogs.value[logId]
}

const viewLogDetails = (log) => {
  // Open modal or detailed view for the log
  console.log('View log details:', log)
}

const applyFilters = () => {
  router.visit(route('qualitycertificates.iso-revisions.audit-trail', props.certificate.id), {
    data: localFilters,
    preserveState: true,
    preserveScroll: true,
  })
}

const resetFilters = () => {
  Object.keys(localFilters).forEach(key => {
    localFilters[key] = ''
  })
  applyFilters()
}

const exportAuditLogs = async () => {
  try {
    const params = new URLSearchParams()
    Object.entries(localFilters).forEach(([key, value]) => {
      if (value) params.append(key, value)
    })
    
    const url = route('qualitycertificates.iso-revisions.export', props.certificate.id) + '?' + params.toString()
    window.open(url, '_blank')
  } catch (error) {
    console.error('Export failed:', error)
  }
}

const refreshLogs = () => {
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

.iso-audit-trail-page :deep(.bg-blue-900) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-audit-trail-page :deep(.text-blue-900),
.iso-audit-trail-page :deep(.text-blue-800) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-audit-trail-page :deep(.bg-blue-50),
.iso-audit-trail-page :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-50-rgb) / 0.82) !important;
}

.iso-audit-trail-page :deep(input),
.iso-audit-trail-page :deep(select),
.iso-audit-trail-page :deep(textarea) {
  border-color: #d8cbb8;
  background: #fffdf7;
  color: #15231f;
  border-radius: 0.875rem;
}

.iso-audit-trail-page :deep(input:focus),
.iso-audit-trail-page :deep(select:focus),
.iso-audit-trail-page :deep(textarea:focus) {
  border-color: rgb(var(--primary-500-rgb));
  box-shadow: 0 0 0 3px rgb(var(--primary-500-rgb) / 0.18);
  outline: none;
}

:global(.dark) .iso-audit-trail-page :deep(.bg-white),
:global(.dark) .iso-audit-trail-page :deep(.bg-gray-50),
:global(.dark) .iso-audit-trail-page :deep(.bg-gray-100),
:global(.dark) .iso-audit-trail-page :deep(.bg-yellow-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-audit-trail-page :deep(.border-gray-200),
:global(.dark) .iso-audit-trail-page :deep(.divide-gray-200),
:global(.dark) .iso-audit-trail-page :deep(.divide-gray-300) {
  border-color: rgb(51 65 85) !important;
}

:global(.dark) .iso-audit-trail-page :deep(.text-gray-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-audit-trail-page :deep(.text-gray-700),
:global(.dark) .iso-audit-trail-page :deep(.text-gray-600),
:global(.dark) .iso-audit-trail-page :deep(.text-gray-500) {
  color: #cbd5e1 !important;
}

:global(.dark) .iso-audit-trail-page :deep(input),
:global(.dark) .iso-audit-trail-page :deep(select),
:global(.dark) .iso-audit-trail-page :deep(textarea) {
  border-color: #315149;
  background: #10231f;
  color: #f7f1e7;
}
</style>
