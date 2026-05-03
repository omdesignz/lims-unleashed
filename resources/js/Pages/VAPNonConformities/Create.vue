<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.create_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_non_conformities.create_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ $t('gestlab.general.labels.vap_non_conformities.general.new') }}
          </span>
        </div>
      </div>
    </div>

    <!-- Reuse the Form component from previous Create/Edit -->
    <!-- You can extract the form to a separate component -->
    <!-- For brevity, using the same form structure as Edit.vue -->
    <NonConformityForm
      :nonConformity="form"
      :actions="actions"
      :labs="labs"
      :departments="departments"
      :is-editing="false"
      @submit="submit"
      @reset="reset"
      @add-action="addAction"
      @remove-action="removeAction"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import NonConformityForm from '@/Pages/VAPNonConformities/NonConformityForm.vue'

// Props
const props = defineProps({
  labs: {
    type: Array,
    default: () => []
  },
  departments: {
    type: Array,
    default: () => []
  },
  defaultNcNumber: {
    type: String,
    default: ''
  }
})

// Form
const form = useForm({
  lab_id: '',
  department_id: '',
  nc_number: props.defaultNcNumber,
  title: '',
  description: '',
  status: 'opened',
  severity: 'medium',
  category: 'quality',
  sample_id: '',
  test_method: '',
  equipment_id: '',
  batch_number: '',
  reported_by: '',
  reported_by_id: '',
  assigned_to: '',
  assigned_to_id: '',
  reported_at: new Date().toISOString().slice(0, 16),
  due_date: '',
  occurrence_area: '',
  root_cause: '',
  corrective_actions: '',
  preventive_actions: '',
  comments: '',
  attachments: []
})

// Actions
const actions = ref([])

// Methods
function submit() {
  form.actions = actions.value
  form.post(route('vap_non_conformities.store'))
}

function reset() {
  form.reset()
  actions.value = []
}

function addAction() {
  actions.value.push({
    correction: '',
    corrective_action: '',
    due_at: ''
  })
}

function removeAction(index) {
  actions.value.splice(index, 1)
}
</script>