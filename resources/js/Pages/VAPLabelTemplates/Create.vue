<template>
  <div class="space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.templates.create_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.templates.create_description') }}
          </p>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="route('vap_labels.label-templates.index')"
            class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <ArrowLeftIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
          </Link>
        </div>
      </div>
    </div>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- LEFT COLUMN (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- BASIC INFO CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <InformationCircleIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.templates.basic_info') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="grid grid-cols-1 gap-6">
                <!-- NAME -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.templates.name') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <input
                    v-model="form.name"
                    type="text"
                    :placeholder="$t('gestlab.general.labels.vap_labels.templates.name_placeholder')"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.name 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <p v-if="form.errors.name" class="text-xs text-red-600">
                    {{ form.errors.name }}
                  </p>
                </div>

                <!-- DESCRIPTION -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.templates.description') }}
                  </label>
                  <textarea
                    v-model="form.description"
                    rows="3"
                    :placeholder="$t('gestlab.general.labels.vap_labels.templates.description_placeholder')"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.description 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <p v-if="form.errors.description" class="text-xs text-red-600">
                    {{ form.errors.description }}
                  </p>
                </div>

                <!-- CATEGORY -->
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.templates.category') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <select
                    v-model="form.category"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.category 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  >
                    <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_category') }}</option>
                    <option v-for="(label, key) in categories" :key="key" :value="key">
                      {{ label }}
                    </option>
                  </select>
                  <p v-if="form.errors.category" class="text-xs text-red-600">
                    {{ form.errors.category }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- TEMPLATE DATA CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <Cog6ToothIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.templates.template_data') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="space-y-6">
                <!-- SELECT FROM EXISTING LABEL -->
                <div class="space-y-4">
                  <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labels.templates.select_from_label') }}</h3>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $t('gestlab.general.labels.vap_labels.lab') }}
                      </label>
                      <select
                        v-model="selectedLab"
                        @change="loadLabels"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      >
                        <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_lab') }}</option>
                        <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                          {{ lab.name }}
                        </option>
                      </select>
                    </div>
                    
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $t('gestlab.general.labels.vap_labels.templates.select_label') }}
                      </label>
                      <select
                        v-model="selectedLabel"
                        @change="loadLabelData"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                        :disabled="!availableLabels.length"
                      >
                        <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.select_label') }}</option>
                        <option v-for="label in availableLabels" :key="label.id" :value="label.id">
                          {{ label.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <div v-if="selectedLabelData" class="p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center justify-between">
                      <div>
                        <h4 class="text-sm font-medium text-blue-900">{{ selectedLabelData.name }}</h4>
                        <p class="text-xs text-blue-700 mt-1">
                          {{ selectedLabelData.width }} × {{ selectedLabelData.height }} mm • 
                          {{ $t('gestlab.general.labels.vap_labels.types.' + selectedLabelData.type) }}
                        </p>
                      </div>
                      <button
                        @click="applyLabelData"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-800"
                      >
                        <CheckIcon class="h-3 w-3" />
                        {{ $t('gestlab.general.labels.vap_labels.buttons.apply_data') }}
                      </button>
                    </div>
                  </div>
                </div>

                <!-- OR MANUAL ENTRY -->
                <div class="space-y-4">
                  <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labels.templates.or_manual_entry') }}</h3>
                  
                  <div class="space-y-4">
                    <!-- CONTENT -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.vap_labels.content') }}
                        <span class="text-red-500">*</span>
                      </label>
                      <textarea
                        v-model="form.template_data.content"
                        rows="4"
                        :placeholder="$t('gestlab.general.labels.vap_labels.content_placeholder')"
                        :class="[
                          'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm font-mono',
                          form.errors['template_data.content'] 
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                            : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                        ]"
                      />
                      <p v-if="form.errors['template_data.content']" class="text-xs text-red-600">
                        {{ form.errors['template_data.content'] }}
                      </p>
                    </div>

                    <!-- DIMENSIONS -->
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.vap_labels.width') }}
                        </label>
                        <div class="flex items-center gap-2">
                          <input
                            v-model="form.template_data.width"
                            type="number"
                            step="0.1"
                            min="1"
                            max="1000"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                          />
                          <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.vap_labels.height') }}
                        </label>
                        <div class="flex items-center gap-2">
                          <input
                            v-model="form.template_data.height"
                            type="number"
                            step="0.1"
                            min="1"
                            max="1000"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                          />
                          <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                        </div>
                      </div>
                    </div>

                    <!-- APPEARANCE -->
                    <div class="grid grid-cols-2 gap-4">
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.vap_labels.background_color') }}
                        </label>
                        <div class="flex items-center gap-2">
                          <input
                            v-model="form.template_data.background_color"
                            type="color"
                            class="h-10 w-16 cursor-pointer rounded-lg border border-gray-300"
                          />
                          <input
                            v-model="form.template_data.background_color"
                            type="text"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm font-mono"
                          />
                        </div>
                      </div>
                      
                      <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                          {{ $t('gestlab.general.labels.vap_labels.text_color') }}
                        </label>
                        <div class="flex items-center gap-2">
                          <input
                            v-model="form.template_data.text_color"
                            type="color"
                            class="h-10 w-16 cursor-pointer rounded-lg border border-gray-300"
                          />
                          <input
                            v-model="form.template_data.text_color"
                            type="text"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm font-mono"
                          />
                        </div>
                      </div>
                    </div>

                    <!-- FONT SIZE -->
                    <div class="space-y-2">
                      <label class="block text-sm font-medium text-gray-700">
                        {{ $t('gestlab.general.labels.vap_labels.font_size') }}
                      </label>
                      <div class="flex items-center gap-3">
                        <input
                          v-model="form.template_data.font_size"
                          type="range"
                          min="6"
                          max="72"
                          class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                        />
                        <span class="text-sm text-gray-600 w-12 text-right">
                          {{ form.template_data.font_size }}px
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN (1/3 width) -->
        <div class="space-y-6">
          <!-- PREVIEW CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.templates.preview') }}
            </h3>
            <div class="flex items-center justify-center p-4 bg-gray-50 rounded-lg">
              <div 
                class="border border-gray-300"
                :style="{
                  width: (form.template_data.width * 2) + 'px',
                  height: (form.template_data.height * 2) + 'px',
                  backgroundColor: form.template_data.background_color,
                  color: form.template_data.text_color,
                  fontSize: form.template_data.font_size + 'px',
                  borderWidth: (form.template_data.border_width || 1) + 'px',
                  borderColor: form.template_data.border_color || '#000000',
                  textAlign: form.template_data.text_alignment || 'center',
                  padding: '8px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: form.template_data.text_alignment || 'center',
                  overflow: 'hidden'
                }"
              >
                <div class="text-xs truncate">
                  {{ form.template_data.content || $t('gestlab.general.labels.vap_labels.templates.sample_content') }}
                </div>
              </div>
            </div>
            <div class="mt-4 text-center text-sm text-gray-500">
              {{ form.template_data.width }} × {{ form.template_data.height }} mm
            </div>
          </div>

          <!-- SETTINGS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.templates.settings') }}
            </h3>
            
            <div class="space-y-4">
              <!-- IS ACTIVE -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.templates.is_active') }}
                  </label>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ $t('gestlab.general.labels.vap_labels.templates.active_description') }}
                  </p>
                </div>
                <div class="flex items-center">
                  <input
                    v-model="form.is_active"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                </div>
              </div>

              <!-- IS FEATURED -->
              <div class="flex items-center justify-between">
                <div>
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.templates.is_featured') }}
                  </label>
                  <p class="text-xs text-gray-500 mt-1">
                    {{ $t('gestlab.general.labels.vap_labels.templates.featured_description') }}
                  </p>
                </div>
                <div class="flex items-center">
                  <input
                    v-model="form.is_featured"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- ACTIONS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
            </h3>
            
            <div class="space-y-4">
              <button 
                type="submit"
                :disabled="form.processing"
                :class="[
                  'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                  form.processing
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                    : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                ]"
              >
                <CheckIcon class="h-5 w-5" />
                {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : $t('gestlab.general.labels.vap_labels.buttons.save_template') }}
              </button>
              
              <button
                @click="resetForm"
                type="button"
                class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
              >
                <ArrowPathIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.buttons.reset') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { useForm, Link, router } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, 
  InformationCircleIcon,
  Cog6ToothIcon,
  CheckIcon,
  ArrowLeftIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  categories: Object,
  labs: {
    type: Array,
    default: () => []
  },
})

const selectedLab = ref('')
const selectedLabel = ref('')
const selectedLabelData = ref(null)
const availableLabels = ref([])

const form = useForm({
  name: '',
  description: '',
  category: '',
  template_data: {
    type: 'custom',
    content: '',
    width: 50,
    height: 25,
    background_color: '#ffffff',
    text_color: '#000000',
    font_size: 12,
    border_width: 1,
    border_color: '#000000',
    text_alignment: 'center',
    has_qr_code: false,
    has_barcode: false,
  },
  is_active: true,
  is_featured: false,
})

const loadLabels = async () => {
  if (!selectedLab.value) {
    availableLabels.value = []
    return
  }
  
  try {
    const response = await fetch(route('vap_labels.templates.list', { lab_id: selectedLab.value }))
    availableLabels.value = await response.json()
  } catch (error) {
    console.error('Error loading labels:', error)
    availableLabels.value = []
  }
}

const loadLabelData = async () => {
  if (!selectedLabel.value) {
    selectedLabelData.value = null
    return
  }
  
  try {
    const response = await fetch(route('vap_labels.labels.show', selectedLabel.value), {
      headers: {
        Accept: 'application/json',
      },
    })
    const data = await response.json()
    selectedLabelData.value = data.label
  } catch (error) {
    console.error('Error loading label data:', error)
    selectedLabelData.value = null
  }
}

const applyLabelData = () => {
  if (selectedLabelData.value) {
    // Copy label data to template form
    const label = selectedLabelData.value
    form.template_data = {
      type: label.type,
      content: label.content,
      width: label.width,
      height: label.height,
      background_color: label.background_color,
      text_color: label.text_color,
      font_size: label.font_size,
      border_width: label.border_width,
      border_color: label.border_color,
      text_alignment: label.text_alignment,
      has_qr_code: label.has_qr_code,
      qr_code_content: label.qr_code_content,
      qr_code_size: label.qr_code_size,
      has_barcode: label.has_barcode,
      barcode_content: label.barcode_content,
      barcode_type: label.barcode_type,
      barcode_width: label.barcode_width,
      barcode_height: label.barcode_height,
      logo_path: label.logo_path,
      logo_size: label.logo_size,
    }
  }
}

const resetForm = () => {
  if (confirm('Restaurar formulário para valores padrão?')) {
    form.reset()
    selectedLab.value = ''
    selectedLabel.value = ''
    selectedLabelData.value = null
    availableLabels.value = []
  }
}

const submit = () => {
  form.post(route('vap_labels.label-templates.store'))
}
</script>
