<template>
  <Modal :show="show" @close="closeModal" max-width="4xl">
    <form @submit.prevent="restoreRevision" class="iso-revision-restore-modal p-6">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            <ArrowPathIcon class="h-6 w-6 text-green-600" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.title') }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.iso_revisions.restore.description') }}
          </p>
        </div>
        <button 
          @click="closeModal"
          type="button"
          class="rounded-full p-2 text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-900"
        >
          <XMarkIcon class="h-6 w-6" />
        </button>
      </div>

      <!-- CURRENT VERSION WARNING -->
      <div class="mb-6 rounded-lg bg-yellow-50 p-4 border border-yellow-200">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-600" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">
              {{ $t('gestlab.general.labels.iso_revisions.restore.warning_title') }}
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>{{ $t('gestlab.general.labels.iso_revisions.restore.warning_description') }}</p>
              <ul class="mt-2 list-disc list-inside space-y-1">
                <li>{{ $t('gestlab.general.labels.iso_revisions.restore.warning_point_1') }}</li>
                <li>{{ $t('gestlab.general.labels.iso_revisions.restore.warning_point_2') }}</li>
                <li>{{ $t('gestlab.general.labels.iso_revisions.restore.warning_point_3') }}</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- REVISION INFO CARD -->
      <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- SOURCE REVISION -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-600 text-white font-semibold">
                <ArrowDownIcon class="h-4 w-4" />
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.source_revision') }}
                </h4>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.restoring_from') }}
                </p>
              </div>
            </div>
            
            <div class="space-y-2 pl-10">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.version') }}</span>
                <span class="text-sm font-semibold text-green-600">v{{ revision?.version }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.revision') }}</span>
                <span class="text-sm font-medium text-gray-900">#{{ revision?.revision_number }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.change_type') }}</span>
                <span :class="[
                  'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                  getChangeTypeClass(revision?.change_type)
                ]">
                  {{ $t(`gestlab.general.labels.iso_revisions.change_types.${revision?.change_type}`) }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.effective_date') }}</span>
                <span class="text-sm text-gray-900">{{ formatDate(revision?.effective_date) }}</span>
              </div>
            </div>
          </div>

          <!-- CURRENT VERSION -->
          <div class="space-y-3">
            <div class="flex items-center gap-2">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                <ArrowUpIcon class="h-4 w-4" />
              </div>
              <div>
                <h4 class="text-sm font-semibold text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.current_version') }}
                </h4>
                <p class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.replacing') }}
                </p>
              </div>
            </div>
            
            <div class="space-y-2 pl-10">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.version') }}</span>
                <span class="text-sm font-semibold text-blue-900">v{{ certificate?.current_revision?.version || '1.0' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.revision') }}</span>
                <span class="text-sm font-medium text-gray-900">#{{ certificate?.current_revision?.revision_number || 1 }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.restore.status.title') }}</span>
                <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.status.active') }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.effective_date') }}</span>
                <span class="text-sm text-gray-900">{{ formatDate(certificate?.validated_at) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- CHANGE REASON PREVIEW -->
        <div class="mt-4 pt-4 border-t border-gray-200">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('gestlab.general.labels.iso_revisions.restore.change_reason_preview') }}
          </label>
          <div class="bg-white rounded-lg border border-gray-200 p-3">
            <p class="text-sm text-gray-700 italic">
              "{{ revision?.change_reason }}"
            </p>
          </div>
        </div>
      </div>

      <!-- RESTORE FORM -->
      <div class="space-y-6">
        <!-- RESTORE REASON -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <ChatBubbleLeftRightIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.restore_reason') }}
            <span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.restore_reason"
            required
            rows="3"
            :placeholder="$t('gestlab.general.labels.iso_revisions.restore.reason_placeholder')"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          />
          <p class="text-xs text-gray-500">
            {{ $t('gestlab.general.labels.iso_revisions.restore.reason_help') }}
          </p>
          <p v-if="form.errors.restore_reason" class="text-xs text-red-600 mt-1">
            {{ form.errors.restore_reason }}
          </p>
        </div>

        <!-- ISO SECTION -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <DocumentMagnifyingGlassIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.iso_section') }}
            <span class="text-red-500">*</span>
          </label>
          <div class="flex items-center gap-3">
            <input 
              v-model="form.iso_section"
              required
              placeholder="e.g., 8.9.1"
              class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
            />
            <span class="text-xs text-gray-500 whitespace-nowrap">
              {{ $t('gestlab.general.labels.iso_revisions.restore.iso_17025') }}
            </span>
          </div>
          <p v-if="form.errors.iso_section" class="text-xs text-red-600 mt-1">
            {{ form.errors.iso_section }}
          </p>
        </div>

        <!-- APPROVER -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <UserIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.approver') }}
            <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.approved_by_id"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="">{{ $t('gestlab.general.labels.iso_revisions.restore.select_approver') }}</option>
            <option v-for="approver in approvers" :key="approver.id" :value="approver.id">
              {{ approver.name }} - {{ approver.role }}
            </option>
          </select>
          <p v-if="form.errors.approved_by_id" class="text-xs text-red-600 mt-1">
            {{ form.errors.approved_by_id }}
          </p>
        </div>

        <!-- RESTORE SCOPE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.iso_revisions.restore.restore_scope') }}
          </label>
          <div class="space-y-3">
            <!-- OPTION 1: FULL RESTORE -->
            <div class="flex items-start">
              <input 
                type="radio" 
                id="scope-full"
                v-model="form.restore_scope"
                value="FULL"
                class="mt-1 h-4 w-4 border-gray-300 text-blue-900 focus:ring-blue-900"
              />
              <div class="ml-3">
                <label for="scope-full" class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.full_restore') }}
                </label>
                <p class="text-xs text-gray-500 mt-1">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.full_restore_description') }}
                </p>
              </div>
            </div>

            <!-- OPTION 2: SELECTIVE RESTORE -->
            <div class="flex items-start">
              <input 
                type="radio" 
                id="scope-selective"
                v-model="form.restore_scope"
                value="SELECTIVE"
                class="mt-1 h-4 w-4 border-gray-300 text-blue-900 focus:ring-blue-900"
              />
              <div class="ml-3">
                <label for="scope-selective" class="text-sm font-medium text-gray-900">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.selective_restore') }}
                </label>
                <p class="text-xs text-gray-500 mt-1">
                  {{ $t('gestlab.general.labels.iso_revisions.restore.selective_restore_description') }}
                </p>
              </div>
            </div>

            <!-- SELECTIVE FIELDS -->
            <div v-if="form.restore_scope === 'SELECTIVE'" class="ml-7 space-y-2">
              <div v-for="field in restorableFields" :key="field.name" class="flex items-center">
                <input 
                  type="checkbox" 
                  :id="`field-${field.name}`"
                  v-model="form.selected_fields"
                  :value="field.name"
                  class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                />
                <label :for="`field-${field.name}`" class="ml-3 text-sm text-gray-700">
                  {{ field.label }}
                  <span class="text-xs text-gray-500 ml-1">
                    ({{ field.category }})
                  </span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- CHANGE CATEGORY -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <TagIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.change_category') }}
            <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.change_category"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="CORRECTION">{{ $t('gestlab.general.labels.iso_revisions.restore.categories.CORRECTION') }}</option>
            <option value="REISSUE">{{ $t('gestlab.general.labels.iso_revisions.restore.categories.REISSUE') }}</option>
            <option value="EMERGENCY">{{ $t('gestlab.general.labels.iso_revisions.restore.categories.EMERGENCY') }}</option>
            <option value="REGULATORY">{{ $t('gestlab.general.labels.iso_revisions.restore.categories.REGULATORY') }}</option>
          </select>
        </div>

        <!-- RISK ASSESSMENT -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <ExclamationTriangleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.risk_assessment') }}
          </label>
          <select 
            v-model="form.risk_assessment"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="LOW">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.LOW') }}</option>
            <option value="MEDIUM">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.MEDIUM') }}</option>
            <option value="HIGH">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.HIGH') }}</option>
          </select>
        </div>

        <!-- ADDITIONAL NOTES -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <DocumentTextIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.restore.additional_notes') }}
          </label>
          <textarea 
            v-model="form.additional_notes"
            rows="2"
            :placeholder="$t('gestlab.general.labels.iso_revisions.restore.notes_placeholder')"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          />
        </div>

        <!-- CONFIRMATION CHECKBOX -->
        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
          <div class="flex items-start">
            <input 
              type="checkbox" 
              id="confirm-restore"
              v-model="form.confirmed"
              required
              class="mt-1 h-4 w-4 rounded border-red-300 text-red-900 focus:ring-red-900"
            />
            <div class="ml-3">
              <label for="confirm-restore" class="text-sm font-medium text-red-900">
                {{ $t('gestlab.general.labels.iso_revisions.restore.confirmation_label') }}
              </label>
              <p class="text-xs text-red-700 mt-1">
                {{ $t('gestlab.general.labels.iso_revisions.restore.confirmation_description') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- FOOTER -->
      <div class="mt-8 flex items-center justify-end gap-4">
        <button 
          @click="closeModal"
          type="button"
          class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          {{ $t('gestlab.general.labels.iso_revisions.cancel') }}
        </button>
        <button 
          type="submit"
          :disabled="form.processing || !form.confirmed"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing || !form.confirmed
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-900 focus:ring-offset-2'
          ]"
        >
          <ArrowPathIcon class="h-4 w-4" />
          {{ form.processing ? $t('gestlab.general.labels.iso_revisions.restoring') : $t('gestlab.general.labels.iso_revisions.restore_version') }}
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import {
  ArrowPathIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
  ArrowDownIcon,
  ArrowUpIcon,
  ChatBubbleLeftRightIcon,
  DocumentMagnifyingGlassIcon,
  UserIcon,
  TagIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  revision: Object,
  certificate: Object,
  approvers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'restored'])

// Form
const form = useForm({
  restore_reason: '',
  iso_section: '8.9.1',
  approved_by_id: '',
  restore_scope: 'FULL',
  selected_fields: [],
  change_category: 'CORRECTION',
  risk_assessment: 'MEDIUM',
  additional_notes: '',
  confirmed: false
})

// Restorable fields configuration
const restorableFields = computed(() => [
  // Certificate fields
  { name: 'status', label: 'Certificate Status', category: 'CERTIFICATE' },
  { name: 'obs', label: 'Observations', category: 'CERTIFICATE' },
  { name: 'validated_by', label: 'Validated By', category: 'CERTIFICATE' },
  { name: 'validated_at', label: 'Validation Date', category: 'CERTIFICATE' },
  { name: 'extra_data', label: 'Additional Data', category: 'CERTIFICATE' },
  
  // Related data
  { name: 'collection_data', label: 'Collection Information', category: 'RELATED' },
  { name: 'product_data', label: 'Product Details', category: 'RELATED' },
  { name: 'customer_data', label: 'Customer Information', category: 'RELATED' },
  { name: 'warehouse_data', label: 'Warehouse Details', category: 'RELATED' },
  
  // Results data
  { name: 'test_results', label: 'Test Results', category: 'RESULTS' },
  { name: 'methodology', label: 'Test Methodology', category: 'RESULTS' },
  { name: 'equipment', label: 'Equipment Used', category: 'RESULTS' },
  { name: 'personnel', label: 'Testing Personnel', category: 'RESULTS' }
])

// Watch for revision changes to pre-fill form
watch(() => props.revision, (revision) => {
  if (revision) {
    // Pre-fill form with revision data
    form.restore_reason = `Restoring to revision v${revision.version} (${revision.change_reason})`
    
    // If revision has ISO section metadata, use it
    if (revision.compliance_metadata?.iso_section) {
      form.iso_section = revision.compliance_metadata.iso_section
    }
    
    // If revision has change category, use it
    if (revision.compliance_metadata?.change_category) {
      form.change_category = revision.compliance_metadata.change_category
    }
  }
}, { immediate: true })

// Methods
const closeModal = () => {
  form.reset()
  form.confirmed = false
  form.restore_scope = 'FULL'
  form.selected_fields = []
  emit('close')
}

const getChangeTypeClass = (changeType) => {
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

const restoreRevision = () => {
  // Validate selective restore has fields selected
  if (form.restore_scope === 'SELECTIVE' && form.selected_fields.length === 0) {
    form.setError('selected_fields', 'Please select at least one field to restore')
    return
  }

  // Prepare the approval data
  const approvalData = {
    approved_by_id: form.approved_by_id,
    iso_section: form.iso_section,
    change_category: form.change_category,
    risk_assessment: form.risk_assessment,
    additional_notes: form.additional_notes,
    restore_scope: form.restore_scope,
    selected_fields: form.selected_fields
  }

  // Submit the restore request
  form.transform((data) => ({
    ...data,
    approval_data: approvalData
  })).post(route('qualitycertificates.iso-revisions.restore', {
    certificate: props.certificate.id,
    revision: props.revision.id
  }), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
      emit('restored')
    },
    onError: (errors) => {
      console.error('Restore failed:', errors)
    }
  })
}
</script>

<style scoped>
.iso-revision-restore-modal :deep(.text-blue-900),
.iso-revision-restore-modal :deep(.text-blue-800) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-revision-restore-modal :deep(.bg-blue-900) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-revision-restore-modal :deep(.bg-blue-50),
.iso-revision-restore-modal :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-50-rgb) / 0.82) !important;
}

.iso-revision-restore-modal :deep(input),
.iso-revision-restore-modal :deep(select),
.iso-revision-restore-modal :deep(textarea) {
  border-color: #d8cbb8;
  background: #fffdf7;
  color: #15231f;
  border-radius: 0.875rem;
}

:global(.dark) .iso-revision-restore-modal {
  background-color: rgb(2 6 23 / 0.92);
}

:global(.dark) .iso-revision-restore-modal :deep(.bg-white),
:global(.dark) .iso-revision-restore-modal :deep(.bg-gray-50),
:global(.dark) .iso-revision-restore-modal :deep(.bg-gray-100),
:global(.dark) .iso-revision-restore-modal :deep(.bg-blue-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-revision-restore-modal :deep(.text-gray-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-revision-restore-modal :deep(.text-gray-700),
:global(.dark) .iso-revision-restore-modal :deep(.text-gray-600) {
  color: #cbd5e1 !important;
}

:global(.dark) .iso-revision-restore-modal :deep(input),
:global(.dark) .iso-revision-restore-modal :deep(select),
:global(.dark) .iso-revision-restore-modal :deep(textarea) {
  border-color: #315149;
  background: #10231f;
  color: #f7f1e7;
}
</style>
