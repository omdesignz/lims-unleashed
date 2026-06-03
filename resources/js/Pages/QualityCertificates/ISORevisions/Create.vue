<template>
  
    <div class="iso-revision-create space-y-8" :class="commercialDocumentThemeClasses">
      <!-- HEADER CARD -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
              <DocumentPlusIcon class="h-7 w-7 text-blue-900" />
              {{ $t('gestlab.general.labels.iso_revisions.create.title') }}
            </h1>
            <p class="mt-2 text-gray-600">
              {{ $t('gestlab.general.labels.iso_revisions.create.description') }}
              <span class="font-semibold text-blue-900">
                {{ certificate.code }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
              v{{ certificate.current_revision?.version || '1.0' }}
            </span>
          </div>
        </div>
      </div>

      <!-- FORM SECTION -->
      <form @submit.prevent="submitForm" class="space-y-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- LEFT COLUMN - FORM FIELDS -->
          <div class="lg:col-span-2 space-y-6">
            
            <!-- CHANGE INFORMATION CARD -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <!-- GRADIENT HEADER -->
              <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                  <InformationCircleIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.iso_revisions.create.change_information') }}
                </h2>
              </div>
              
              <!-- FORM FIELDS -->
              <div class="p-6 space-y-6">
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
                    :class="[
                      'mt-1 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                      form.errors.change_type 
                        ? 'border-red-300 text-red-900 placeholder-red-300' 
                        : 'border-gray-300 bg-white text-gray-900'
                    ]"
                  >
                    <option value="">{{ $t('gestlab.general.labels.iso_revisions.create.select_change_type') }}</option>
                    <option value="UPDATED">{{ $t('gestlab.general.labels.iso_revisions.change_types.UPDATED') }}</option>
                    <option value="CORRECTED">{{ $t('gestlab.general.labels.iso_revisions.change_types.CORRECTED') }}</option>
                    <option value="REISSUED">{{ $t('gestlab.general.labels.iso_revisions.change_types.REISSUED') }}</option>
                    <option value="WITHDRAWN">{{ $t('gestlab.general.labels.iso_revisions.change_types.WITHDRAWN') }}</option>
                  </select>
                  <p v-if="form.errors.change_type" class="text-xs text-red-600 mt-1">
                    {{ form.errors.change_type }}
                  </p>
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
                    rows="4"
                    :placeholder="$t('gestlab.general.labels.iso_revisions.create.reason_placeholder')"
                    :class="[
                      'mt-1 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                      form.errors.change_reason 
                        ? 'border-red-300 text-red-900 placeholder-red-300' 
                        : 'border-gray-300 bg-white text-gray-900'
                    ]"
                  />
                  <p v-if="form.errors.change_reason" class="text-xs text-red-600 mt-1">
                    {{ form.errors.change_reason }}
                  </p>
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
                    :class="[
                      'mt-1 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                      form.errors.iso_section 
                        ? 'border-red-300 text-red-900 placeholder-red-300' 
                        : 'border-gray-300 bg-white text-gray-900'
                    ]"
                  />
                  <p v-if="form.errors.iso_section" class="text-xs text-red-600 mt-1">
                    {{ form.errors.iso_section }}
                  </p>
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
                    :class="[
                      'mt-1 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                      form.errors.risk_assessment 
                        ? 'border-red-300 text-red-900 placeholder-red-300' 
                        : 'border-gray-300 bg-white text-gray-900'
                    ]"
                  >
                    <option value="">{{ $t('gestlab.general.labels.iso_revisions.create.select_risk_level') }}</option>
                    <option value="LOW">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.LOW') }}</option>
                    <option value="MEDIUM">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.MEDIUM') }}</option>
                    <option value="HIGH">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.HIGH') }}</option>
                    <option value="CRITICAL">{{ $t('gestlab.general.labels.iso_revisions.risk_levels.CRITICAL') }}</option>
                  </select>
                  <p v-if="form.errors.risk_assessment" class="text-xs text-red-600 mt-1">
                    {{ form.errors.risk_assessment }}
                  </p>
                </div>
              </div>
            </div>

            <!-- FIELDS TO UPDATE CARD -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
              <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                  <DocumentTextIcon class="h-5 w-5 text-blue-900" />
                  {{ $t('gestlab.general.labels.iso_revisions.create.fields_to_update') }}
                </h2>
              </div>
              
              <div class="p-6">
                <div class="space-y-4">
                  <div v-for="field in updatableFields" :key="field.name" class="flex items-start">
                    <input 
                      type="checkbox" 
                      :id="`field-${field.name}`"
                      v-model="selectedFields"
                      :value="field.name"
                      class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                    <div class="ml-3 flex-1">
                      <label :for="`field-${field.name}`" class="text-sm font-medium text-gray-900">
                        {{ field.label }}
                      </label>
                      <div v-if="selectedFields.includes(field.name)" class="mt-2">
                        <component 
                          :is="getFieldComponent(field.type)" 
                          v-model="form.fields[field.name]"
                          :placeholder="field.placeholder"
                          :options="field.options"
                          :class="[
                            'block w-full rounded-lg border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                            form.errors.fields?.[field.name] 
                              ? 'border-red-300 text-red-900 placeholder-red-300' 
                              : 'border-gray-300 bg-white text-gray-900'
                          ]"
                        />
                        <p v-if="form.errors.fields?.[field.name]" class="text-xs text-red-600 mt-1">
                          {{ form.errors.fields[field.name] }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- NO FIELDS SELECTED MESSAGE -->
                <div v-if="selectedFields.length === 0" class="text-center py-8">
                  <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
                  <h3 class="mt-4 text-sm font-semibold text-gray-900">
                    {{ $t('gestlab.general.labels.iso_revisions.create.no_fields_selected') }}
                  </h3>
                  <p class="mt-2 text-sm text-gray-500">
                    {{ $t('gestlab.general.labels.iso_revisions.create.select_fields_to_update') }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN - ACTIONS & INFO -->
          <div class="space-y-6">
            <!-- ACTIONS CARD -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                {{ $t('actions.title') }}
              </h3>
              <div class="space-y-4">
                <!-- APPROVER SELECTION -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                    <UserIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.labels.iso_revisions.create.approver') }}
                  </label>
                  <select 
                    v-model="form.approved_by_id"
                    :class="[
                      'mt-1 block w-full rounded-lg border px-3 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                      form.errors.approved_by_id 
                        ? 'border-red-300 text-red-900 placeholder-red-300' 
                        : 'border-gray-300 bg-white text-gray-900'
                    ]"
                  >
                    <option value="">{{ $t('gestlab.general.labels.iso_revisions.create.select_approver') }}</option>
                    <option v-for="approver in approvers" :key="approver.id" :value="approver.id">
                      {{ approver.name }} ({{ approver.role }})
                    </option>
                  </select>
                  <p v-if="form.errors.approved_by_id" class="text-xs text-red-600 mt-1">
                    {{ form.errors.approved_by_id }}
                  </p>
                </div>

                <!-- SUBMIT BUTTON -->
                <button 
                  type="submit"
                  :disabled="form.processing || selectedFields.length === 0"
                  :class="[
                    'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    form.processing || selectedFields.length === 0
                      ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                      : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                  ]"
                >
                  <DocumentPlusIcon class="h-5 w-5" />
                  {{ form.processing ? $t('gestlab.general.labels.iso_revisions.creating') : $t('gestlab.general.labels.iso_revisions.create_revision') }}
                </button>
              </div>

              <!-- CURRENT VERSION INFO -->
              <div class="border-t border-gray-200 pt-4 mt-4">
                <h4 class="text-sm font-medium text-gray-900 mb-2">
                  {{ $t('gestlab.general.labels.iso_revisions.create.current_version') }}
                </h4>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.version') }}</span>
                    <span class="font-semibold text-blue-900">v{{ certificate.current_revision?.version || '1.0' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">{{ $t('gestlab.general.labels.iso_revisions.status') }}</span>
                    <span :class="[
                      'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                      certificate.status === 1 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-yellow-100 text-yellow-800'
                    ]">
                      {{ $t(`status.${certificate.status}`) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- HELP CARD -->
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
              <h3 class="text-lg font-semibold text-blue-900 mb-3 flex items-center gap-2">
                <QuestionMarkCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.iso_revisions.create.help_title') }}
              </h3>
              <div class="space-y-3 text-sm text-blue-800">
                <p>{{ $t('gestlab.general.labels.iso_revisions.create.help_description') }}</p>
                <ul class="list-disc list-inside space-y-1">
                  <li>{{ $t('gestlab.general.labels.iso_revisions.create.help_point_1') }}</li>
                  <li>{{ $t('gestlab.general.labels.iso_revisions.create.help_point_2') }}</li>
                  <li>{{ $t('gestlab.general.labels.iso_revisions.create.help_point_3') }}</li>
                </ul>
              </div>
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
            {{ $t('gestlab.general.labels.iso_revisions.cancel') }}
          </button>
          
          <div class="flex items-center gap-4">
            <button 
              @click="saveDraft"
              type="button"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
            >
              <BookmarkIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.iso_revisions.save_draft') }}
            </button>
          </div>
        </div>
      </form>
    </div>
 
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import Layout from "@/Shared/Layouts/Layout.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


// Icons
import {
  DocumentPlusIcon,
  InformationCircleIcon,
  TagIcon,
  ChatBubbleLeftRightIcon,
  DocumentMagnifyingGlassIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  UserIcon,
  QuestionMarkCircleIcon,
  ArrowLeftIcon,
  BookmarkIcon
} from '@heroicons/vue/24/outline'

defineOptions({
  layout: Layout
});

const props = defineProps({
  certificate: Object,
  updatableFields: Array,
  approvers: Array,
})

// Form
const form = useForm({
  change_type: '',
  change_reason: '',
  iso_section: '8.9.1',
  risk_assessment: '',
  approved_by_id: '',
  fields: {},
})

const selectedFields = ref([])

// Updatable fields configuration
const updatableFields = computed(() => [
  ...props.updatableFields,
  { 
    name: 'status', 
    label: 'Certificate Status', 
    type: 'select',
    options: [
      { value: 0, label: 'Draft' },
      { value: 1, label: 'Validated' },
      { value: 2, label: 'Withdrawn' },
      { value: 3, label: 'Reissued' }
    ]
  },
  { 
    name: 'obs', 
    label: 'Observations', 
    type: 'textarea',
    placeholder: 'Enter observations...'
  },
  { 
    name: 'validated_by', 
    label: 'Validated By', 
    type: 'text',
    placeholder: 'Name of validator...'
  },
  { 
    name: 'extra_data', 
    label: 'Additional Data', 
    type: 'json',
    placeholder: 'Enter additional data as JSON...'
  }
])

// Methods
const getFieldComponent = (type) => {
  const components = {
    text: 'input',
    textarea: 'textarea',
    select: 'select',
    json: 'textarea',
  }
  return components[type] || 'input'
}

const submitForm = () => {
  if (selectedFields.value.length === 0) {
    form.setError('fields', 'Please select at least one field to update')
    return
  }

  form.post(route('qualitycertificates.iso-revisions.store', props.certificate.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('qualitycertificates.iso-revisions.index', props.certificate.id))
    }
  })
}

const saveDraft = () => {
  // Save draft functionality
  console.log('Save draft clicked')
}
</script>

<style scoped>
.iso-revision-create :deep(.bg-blue-900),
.iso-revision-create :deep(.bg-blue-950) {
  background-color: rgb(var(--primary-900-rgb)) !important;
}

.iso-revision-create :deep(.from-blue-900),
.iso-revision-create :deep(.from-blue-950) {
  --tw-gradient-from: rgb(var(--primary-900-rgb)) var(--tw-gradient-from-position) !important;
  --tw-gradient-to: rgb(var(--primary-900-rgb) / 0) var(--tw-gradient-to-position) !important;
}

.iso-revision-create :deep(.to-blue-800) {
  --tw-gradient-to: rgb(var(--primary-700-rgb)) var(--tw-gradient-to-position) !important;
}

.iso-revision-create :deep(.text-blue-900),
.iso-revision-create :deep(.text-blue-800),
.iso-revision-create :deep(.text-blue-700) {
  color: rgb(var(--primary-800-rgb)) !important;
}

.iso-revision-create :deep(.bg-blue-50),
.iso-revision-create :deep(.bg-blue-100) {
  background-color: rgb(var(--primary-50-rgb) / 0.82) !important;
}

.iso-revision-create :deep(input),
.iso-revision-create :deep(select),
.iso-revision-create :deep(textarea) {
  border-color: #d8cbb8;
  background: #fffdf7;
  color: #15231f;
  border-radius: 0.875rem;
}

:global(.dark) .iso-revision-create :deep(.bg-white),
:global(.dark) .iso-revision-create :deep(.bg-gray-50),
:global(.dark) .iso-revision-create :deep(.bg-blue-50) {
  background-color: rgb(15 23 42 / 0.86) !important;
}

:global(.dark) .iso-revision-create :deep(.text-gray-900),
:global(.dark) .iso-revision-create :deep(.text-slate-900) {
  color: #f8fafc !important;
}

:global(.dark) .iso-revision-create :deep(.text-gray-700),
:global(.dark) .iso-revision-create :deep(.text-gray-600) {
  color: #cbd5e1 !important;
}

:global(.dark) .iso-revision-create :deep(input),
:global(.dark) .iso-revision-create :deep(select),
:global(.dark) .iso-revision-create :deep(textarea) {
  border-color: #315149;
  background: #10231f;
  color: #f7f1e7;
}
</style>
