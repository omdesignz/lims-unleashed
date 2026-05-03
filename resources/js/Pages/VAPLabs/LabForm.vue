<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
            <BeakerIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labs.title') }}
          </h1>
          <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ $t('gestlab.general.labels.vap_labs.description') }}
            <span v-if="labsCount" class="font-semibold text-blue-900 dark:text-blue-400">
              {{ labsCount }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-900/20 dark:text-blue-300 dark:ring-blue-700/30">
            {{ labsCount }} {{ $t('gestlab.general.labels.vap_labs.items') }}
          </span>
        </div>
      </div>
    </div>

    <!-- MAIN CONTENT SECTION -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- BASIC INFORMATION SECTION -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <InformationCircleIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.basic_info') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- LAB NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="form.name"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.name 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.name_placeholder')"
                />
                <p v-if="form.errors.name" class="text-xs text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- LAB CODE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <HashtagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.code') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  type="text"
                  v-model="form.code"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.code 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.code_placeholder')"
                />
                <p v-if="form.errors.code" class="text-xs text-red-600">
                  {{ form.errors.code }}
                </p>
              </div>

              <!-- ROOM NUMBER -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <BuildingOfficeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.room_no') }}
                </label>
                <input
                  type="text"
                  v-model="form.room_no"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.room_no 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.room_no_placeholder')"
                />
                <p v-if="form.errors.room_no" class="text-xs text-red-600">
                  {{ form.errors.room_no }}
                </p>
              </div>

              <!-- CONTACT PERSON -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <UserIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.contact') }}
                </label>
                <input
                  type="text"
                  v-model="form.contact"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.contact 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.contact_placeholder')"
                />
                <p v-if="form.errors.contact" class="text-xs text-red-600">
                  {{ form.errors.contact }}
                </p>
              </div>

              <!-- EXTENSION -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <PhoneIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.extension') }}
                </label>
                <input
                  type="text"
                  v-model="form.extension"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.extension 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.extension_placeholder')"
                />
                <p v-if="form.errors.extension" class="text-xs text-red-600">
                  {{ form.errors.extension }}
                </p>
              </div>

              <!-- EMAIL -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <EnvelopeIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.email') }}
                </label>
                <input
                  type="email"
                  v-model="form.email"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.email 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.email_placeholder')"
                />
                <p v-if="form.errors.email" class="text-xs text-red-600">
                  {{ form.errors.email }}
                </p>
              </div>

              <!-- SUPERVISOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <UserCircleIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.supervisor') }}
                </label>
                <comboboxEnhanced
                  v-model="selectedSupervisor"
                  :placeholder="$t('gestlab.general.labels.vap_labs.select_supervisor')"
                  :options="supervisorOptions"
                  :has-error="Boolean(form.errors.supervisor_id)"
                />
                <p v-if="form.errors.supervisor_id" class="text-xs text-red-600">
                  {{ form.errors.supervisor_id }}
                </p>
              </div>

              <!-- TECHNICAL HEAD -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <CogIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.technical_head') }}
                </label>
                <comboboxEnhanced
                  v-model="selectedTechnicalHead"
                  :placeholder="$t('gestlab.general.labels.vap_labs.select_technical_head')"
                  :options="technicalHeadOptions"
                  :has-error="Boolean(form.errors.technical_head_id)"
                />
                <p v-if="form.errors.technical_head_id" class="text-xs text-red-600">
                  {{ form.errors.technical_head_id }}
                </p>
              </div>

              <!-- DEPARTMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <BuildingLibraryIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.department') }}
                </label>
                <comboboxEnhanced
                  v-model="selectedDepartment"
                  :placeholder="$t('gestlab.general.labels.vap_labs.select_department')"
                  :options="departmentOptions"
                  :has-error="Boolean(form.errors.department_id)"
                />
                <p v-if="form.errors.department_id" class="text-xs text-red-600">
                  {{ form.errors.department_id }}
                </p>
              </div>

              <!-- DESCRIPTION (Full Width) -->
              <div class="md:col-span-2 lg:col-span-3 space-y-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-1">
                  <DocumentTextIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labs.description') }}
                </label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  :class="[
                    'w-full rounded-lg border px-4 py-2.5 text-sm transition-all duration-200 focus:outline-none focus:ring-2',
                    form.errors.description 
                      ? 'border-red-300 bg-red-50 focus:ring-red-500' 
                      : 'border-gray-300 bg-white focus:border-blue-900 focus:ring-blue-900 dark:border-gray-700 dark:bg-slate-950 dark:text-gray-100 dark:focus:border-blue-500 dark:focus:ring-blue-500'
                  ]"
                  :placeholder="$t('gestlab.general.labels.vap_labs.description_placeholder')"
                ></textarea>
                <p v-if="form.errors.description" class="text-xs text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labs.actions.title') }}
          </h3>
          <div class="space-y-4">
            <button 
              @click="submit"
              :disabled="form.processing || !isFormValid"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing || !isFormValid
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckCircleIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.vap_labs.buttons.processing') : $t('gestlab.general.labels.vap_labs.buttons.save_lab') }}
            </button>
            
            <button 
              @click="resetForm"
              type="button"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <ArrowPathIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labs.buttons.reset') }}
            </button>
            
            <!-- QUICK STATS -->
            <div class="border-t border-gray-200 pt-4">
              <h4 class="text-sm font-medium text-gray-900 mb-2">
                {{ $t('gestlab.general.labels.vap_labs.stats.title') }}
              </h4>
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.total_labs') }}</span>
                  <span class="font-semibold text-blue-900">{{ labsCount }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.active_labs') }}</span>
                  <span class="font-semibold text-green-600">{{ activeLabsCount }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">{{ $t('gestlab.general.labels.vap_labs.stats.available_supervisors') }}</span>
                  <span class="font-semibold text-blue-900">{{ supervisors.length }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- STATUS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <Cog6ToothIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labs.status.title') }}
          </h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.status.basic_info') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                isBasicInfoComplete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ isBasicInfoComplete ? $t('gestlab.general.labels.vap_labs.status.complete') : $t('gestlab.general.labels.vap_labs.status.incomplete') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.status.contact_info') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                isContactInfoComplete ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ isContactInfoComplete ? $t('gestlab.general.labels.vap_labs.status.complete') : $t('gestlab.general.labels.vap_labs.status.incomplete') }}
              </span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labs.status.staff_assignment') }}</span>
              <span :class="[
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                isStaffAssigned ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
              ]">
                {{ isStaffAssigned ? $t('gestlab.general.labels.vap_labs.status.complete') : $t('gestlab.general.labels.vap_labs.status.incomplete') }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="flex items-center justify-between pt-6">
      <div class="text-sm text-gray-500">
        {{ $t('gestlab.general.labels.vap_labs.last_updated') }}: {{ lastUpdated }}
      </div>
      <div class="flex items-center gap-4">
        <button 
          @click="viewLabsList"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
        >
          <ArrowLeftIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labs.buttons.back_to_labs') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import comboboxEnhanced from '@/Components/combobox-enhanced.vue'
import { ref, computed, watchEffect } from 'vue'
import { useForm } from '@inertiajs/vue3'
import {
  BeakerIcon,
  InformationCircleIcon,
  TagIcon,
  HashtagIcon,
  BuildingOfficeIcon,
  UserIcon,
  PhoneIcon,
  EnvelopeIcon,
  UserCircleIcon,
  CogIcon,
  BuildingLibraryIcon,
  DocumentTextIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  Cog6ToothIcon,
  ArrowLeftIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  lab: Object,
  labsCount: Number,
  activeLabsCount: Number,
  supervisors: Array,
  technicalHeads: Array,
  departments: Array,
  lastUpdated: String,
})

const mapOptions = (items = []) => items.map(item => ({
  value: item.id,
  label: item.name,
}))

const supervisorOptions = computed(() => mapOptions(props.supervisors))
const technicalHeadOptions = computed(() => mapOptions(props.technicalHeads))
const departmentOptions = computed(() => mapOptions(props.departments))

const selectedSupervisor = ref(
  supervisorOptions.value.find(option => option.value === props.lab?.supervisor_id) ?? null,
)
const selectedTechnicalHead = ref(
  technicalHeadOptions.value.find(option => option.value === props.lab?.technical_head_id) ?? null,
)
const selectedDepartment = ref(
  departmentOptions.value.find(option => option.value === props.lab?.department_id) ?? null,
)

const form = useForm({
  name: props.lab?.name || '',
  code: props.lab?.code || '',
  room_no: props.lab?.room_no || '',
  description: props.lab?.description || '',
  contact: props.lab?.contact || '',
  extension: props.lab?.extension || '',
  email: props.lab?.email || '',
  supervisor_id: props.lab?.supervisor_id || '',
  technical_head_id: props.lab?.technical_head_id || '',
  department_id: props.lab?.department_id || '',
})

watchEffect(() => {
  form.supervisor_id = selectedSupervisor.value?.value ?? ''
  form.technical_head_id = selectedTechnicalHead.value?.value ?? ''
  form.department_id = selectedDepartment.value?.value ?? ''
})

const isFormValid = computed(() => {
  return form.name.trim() !== '' && form.code.trim() !== ''
})

const isBasicInfoComplete = computed(() => {
  return form.name.trim() !== '' && form.code.trim() !== '' && form.room_no.trim() !== ''
})

const isContactInfoComplete = computed(() => {
  return form.contact.trim() !== '' && form.email.trim() !== '' && form.extension.trim() !== ''
})

const isStaffAssigned = computed(() => {
  return form.supervisor_id !== '' && form.technical_head_id !== '' && form.department_id !== ''
})

const submit = () => {
  if (props.lab?.id) {
    form.put(route('vap-labs.labs.update', props.lab.id))
  } else {
    form.post(route('vap-labs.labs.store'))
  }
}

const resetForm = () => {
  form.reset()
}

const viewLabsList = () => {
  window.location.href = route('vap-labs.labs.index')
}
</script>
