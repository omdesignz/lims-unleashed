<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <ExclamationTriangleIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_non_conformities.edit_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_non_conformities.edit_description') }}
            <span class="font-semibold text-blue-900">
              #{{ nonConformity.nc_number }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusClasses" class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset">
            {{ $t(`gestlab.general.labels.vap_non_conformities.status.${form.status}`) }}
          </span>
        </div>
      </div>
    </div>

    <!-- Reuse the Form component -->
    <NonConformityForm
      :nonConformity="form"
      :actions="actions"
      :labs="labs"
      :departments="departments"
      :is-editing="true"
      @submit="submit"
      @reset="reset"
      @add-action="addAction"
      @remove-action="removeAction"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import NonConformityForm from '@/Pages/VAPNonConformities/NonConformityForm.vue'

// Props
const props = defineProps({
  nonConformity: {
    type: Object,
    required: true
  },
  labs: {
    type: Array,
    default: () => []
  },
  departments: {
    type: Array,
    default: () => []
  }
})

// Form
const form = useForm({
  id:  props.nonConformity.id,
  lab_id: props.nonConformity.lab_id || '',
  department_id: props.nonConformity.department_id || '',
  nc_number: props.nonConformity.nc_number,
  title: props.nonConformity.title,
  description: props.nonConformity.description,
  status: props.nonConformity.status,
  severity: props.nonConformity.severity,
  category: props.nonConformity.category,
  sample_id: props.nonConformity.sample_id || '',
  test_method: props.nonConformity.test_method || '',
  equipment_id: props.nonConformity.equipment_id || '',
  batch_number: props.nonConformity.batch_number || '',
  reported_by: props.nonConformity.reported_by,
  reported_by_id: props.nonConformity.reported_by_id || '',
  assigned_to: props.nonConformity.assigned_to || '',
  assigned_to_id: props.nonConformity.assigned_to_id || '',
  reported_at: formatDateForInput(props.nonConformity.reported_at),
  due_date: formatDateForInput(props.nonConformity.due_date),
  occurrence_area: props.nonConformity.occurrence_area || '',
  root_cause: props.nonConformity.root_cause || '',
  corrective_actions: props.nonConformity.corrective_actions || '',
  preventive_actions: props.nonConformity.preventive_actions || '',
  comments: props.nonConformity.comments || '',
  attachments: props.nonConformity.attachments || [],
  actions: props.nonConformity.actions || [],
})

// Actions
const actions = ref(props.nonConformity.actions || [])

// Computed
const statusClasses = computed(() => {
  const classes = {
    opened: 'bg-blue-50 text-blue-700 ring-blue-700/10',
    in_progress: 'bg-yellow-50 text-yellow-700 ring-yellow-700/10',
    resolved: 'bg-green-50 text-green-700 ring-green-700/10',
    closed: 'bg-gray-50 text-gray-700 ring-gray-700/10'
  }
  return classes[form.status] || classes.opened
})

// Methods
function formatDateForInput(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toISOString().slice(0, 16)
}

function submit() {
  form.actions = actions.value
  form.put(route('vap_non_conformities.update', props.nonConformity.id))
}

function reset() {
  form.reset()
  actions.value = props.nonConformity.actions || []
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