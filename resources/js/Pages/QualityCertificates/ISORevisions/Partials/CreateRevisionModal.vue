<template>
  <Modal :show="show" @close="closeModal" max-width="2xl">
    <form @submit.prevent="createRevision" class="iso-revision-create-modal p-6">
      <!-- HEADER -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentPlusIcon class="h-6 w-6 text-blue-900" />
            {{ $t('gestlab.general.labels.iso_revisions.create.title') }}
          </h2>
          <p class="mt-1 text-sm text-gray-600">
            {{ $t('gestlab.general.labels.iso_revisions.create.description') }}
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

      <!-- FORM -->
      <div class="space-y-6">
        <!-- CHANGE TYPE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <TagIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.create.change_type') }}
            <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.change_type"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="UPDATED">{{ $t('gestlab.general.labels.iso_revisions.change_types.UPDATED') }}</option>
            <option value="CORRECTED">{{ $t('gestlab.general.labels.iso_revisions.change_types.CORRECTED') }}</option>
            <option value="REISSUED">{{ $t('gestlab.general.labels.iso_revisions.change_types.REISSUED') }}</option>
            <option value="WITHDRAWN">{{ $t('gestlab.general.labels.iso_revisions.change_types.WITHDRAWN') }}</option>
          </select>
        </div>

        <!-- CHANGE REASON -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <ChatBubbleLeftRightIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.create.change_reason') }}
            <span class="text-red-500">*</span>
          </label>
          <textarea 
            v-model="form.change_reason"
            required
            rows="3"
            :placeholder="$t('gestlab.general.labels.iso_revisions.create.reason_placeholder')"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          />
          <p class="text-xs text-gray-500">
            {{ $t('gestlab.general.labels.iso_revisions.create.reason_help') }}
          </p>
        </div>

        <!-- ISO SECTION -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <DocumentMagnifyingGlassIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.create.iso_section') }}
            <span class="text-red-500">*</span>
          </label>
          <input 
            v-model="form.iso_section"
            required
            placeholder="e.g., 8.9.1"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          />
        </div>

        <!-- RISK ASSESSMENT -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <ExclamationTriangleIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.create.risk_assessment') }}
            <span class="text-red-500">*</span>
          </label>
          <select 
            v-model="form.risk_assessment"
            required
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="LOW">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.LOW') }}</option>
            <option value="MEDIUM">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.MEDIUM') }}</option>
            <option value="HIGH">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.HIGH') }}</option>
            <option value="CRITICAL">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.CRITICAL') }}</option>
          </select>
        </div>

        <!-- APPROVER -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
            <UserIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.iso_revisions.create.approver') }}
          </label>
          <select 
            v-model="form.approved_by_id"
            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 shadow-sm focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900"
          >
            <option value="">{{ $t('gestlab.general.labels.iso_revisions.create.select_approver') }}</option>
            <option v-for="approver in approvers" :key="approver.id" :value="approver.id">
              {{ approver.name }} - {{ approver.role }}
            </option>
          </select>
        </div>

        <!-- FIELDS TO UPDATE -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.iso_revisions.create.fields_to_update') }}
          </label>
          <div class="space-y-3">
            <div v-for="field in updatableFields" :key="field.name" class="flex items-center">
              <input 
                type="checkbox" 
                :id="`field-${field.name}`"
                v-model="selectedFields"
                :value="field.name"
                class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
              />
              <label :for="`field-${field.name}`" class="ml-3 text-sm text-gray-700">
                {{ field.label }}
              </label>
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
          :disabled="form.processing"
          :class="[
            'inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
            form.processing
              ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
              : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
          ]"
        >
          <DocumentPlusIcon class="h-4 w-4" />
          {{ form.processing ? $t('gestlab.general.labels.iso_revisions.creating') : $t('gestlab.general.labels.iso_revisions.create_revision') }}
        </button>
      </div>
    </form>
  </Modal>
</template>

<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import {
  DocumentPlusIcon,
  XMarkIcon,
  TagIcon,
  ChatBubbleLeftRightIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  UserIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  show: Boolean,
  certificate: Object,
  approvers: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'created'])

// Form
const form = useForm({
  change_type: 'UPDATED',
  change_reason: '',
  iso_section: '8.9.1',
  risk_assessment: 'LOW',
  approved_by_id: '',
  fields: {}
})

const selectedFields = ref([])
const updatableFields = [
  { name: 'status', label: 'Certificate Status' },
  { name: 'obs', label: 'Observations' },
  { name: 'validated_by', label: 'Validated By' },
  { name: 'extra_data', label: 'Additional Data' }
]

// Methods
const closeModal = () => {
  form.reset()
  selectedFields.value = []
  emit('close')
}

const createRevision = () => {
  // Get updated values for selected fields
  const updates = {}
  selectedFields.value.forEach(field => {
    updates[field] = props.certificate[field]
  })

  form.fields = updates

  form.post(route('qualitycertificates.iso-revisions.store', props.certificate.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeModal()
      emit('created')
    }
  })
}
</script>

<style scoped>
.iso-revision-create-modal :deep(.text-blue-900),
.iso-revision-create-modal :deep(.text-blue-800) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-revision-create-modal :deep(.bg-blue-900) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-revision-create-modal :deep(.from-blue-900) {
  --tw-gradient-from: rgb(var(--primary-900-rgb)) var(--tw-gradient-from-position) !important;
  --tw-gradient-to: rgb(var(--primary-900-rgb) / 0) var(--tw-gradient-to-position) !important;
}

.iso-revision-create-modal :deep(.to-blue-800) {
  --tw-gradient-to: rgb(var(--primary-700-rgb)) var(--tw-gradient-to-position) !important;
}

.iso-revision-create-modal :deep(input),
.iso-revision-create-modal :deep(select),
.iso-revision-create-modal :deep(textarea) {
  border-color: #d8cbb8;
  background: #fffdf7;
  color: #15231f;
  border-radius: 0.875rem;
}

:global(.dark) .iso-revision-create-modal {
  background-color: rgb(2 6 23 / 0.92);
}

:global(.dark) .iso-revision-create-modal :deep(.bg-white),
:global(.dark) .iso-revision-create-modal :deep(.bg-gray-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-revision-create-modal :deep(.text-gray-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-revision-create-modal :deep(.text-gray-700),
:global(.dark) .iso-revision-create-modal :deep(.text-gray-600) {
  color: #cbd5e1 !important;
}

:global(.dark) .iso-revision-create-modal :deep(input),
:global(.dark) .iso-revision-create-modal :deep(select),
:global(.dark) .iso-revision-create-modal :deep(textarea) {
  border-color: #315149;
  background: #10231f;
  color: #f7f1e7;
}
</style>
