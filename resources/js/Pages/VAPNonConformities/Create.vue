<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate flex flex-col gap-5 overflow-hidden p-6 sm:flex-row sm:items-center sm:justify-between">
        <div class="absolute inset-x-0 top-0 -z-10 h-28 bg-gradient-to-r from-primary-600/15 via-rose-400/10 to-amber-400/10 dark:from-primary-500/20 dark:via-rose-500/10 dark:to-amber-500/10"></div>
        <div>
          <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-600/20">
              <ExclamationTriangleIcon class="h-6 w-6" />
            </span>
            {{ $t('gestlab.general.labels.vap_non_conformities.create_title') }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_non_conformities.create_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-800 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
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
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
  attachments: [],
  media_attachments: [],
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
