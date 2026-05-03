<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TagIcon class="h-7 w-7 text-blue-900" />
            {{ label.name }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.view_description') }}
            <span class="font-semibold text-blue-900">
              {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
            </span>
            • {{ label.width }} × {{ label.height }} mm
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusBadgeClass(label.is_active)">
            {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
          </span>
          <span :class="typeBadgeClass(label.type)">
            {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
          </span>
          <div class="flex items-center gap-2">
            <Link
              :href="route('vap_labels.labels.edit', label.id)"
              class="inline-flex items-center gap-2 rounded-lg bg-white border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <PencilIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}
            </Link>
            <button
              @click="previewPDF"
              class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
            >
              <EyeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.preview_pdf') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- LABEL PREVIEW CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <EyeIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.preview') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="flex flex-col items-center justify-center p-8 bg-gray-50 rounded-lg">
              <!-- LABEL PREVIEW -->
              <div 
                class="relative border border-gray-300 shadow-lg"
                :style="{
                  width: (label.width * 3) + 'px',
                  height: (label.height * 3) + 'px',
                  backgroundColor: label.background_color,
                  color: label.text_color,
                  fontSize: label.font_size + 'px',
                  borderWidth: label.border_width + 'px',
                  borderColor: label.border_color,
                  textAlign: label.text_alignment,
                  padding: '15px',
                  overflow: 'hidden',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: label.text_alignment
                }"
              >
                <!-- QR Code Preview -->
                <div 
                  v-if="label.has_qr_code"
                  class="absolute"
                  :style="{
                    top: '5px',
                    left: '5px',
                    width: '30px',
                    height: '30px',
                    border: '1px solid #000',
                    fontSize: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: 'white'
                  }"
                >
                  QR
                </div>
                
                <!-- Barcode Preview -->
                <div 
                  v-if="label.has_barcode"
                  class="absolute"
                  :style="{
                    bottom: '5px',
                    left: '5px',
                    width: '80px',
                    height: '20px',
                    border: '1px solid #000',
                    fontSize: '6px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    backgroundColor: 'white'
                  }"
                >
                  BARCODE
                </div>
                
                <!-- Logo Preview -->
                <div 
                  v-if="label.logo_path"
                  class="absolute"
                  :style="{
                    top: '5px',
                    right: '5px',
                    width: '40px',
                    height: '40px',
                    border: '1px dashed #ccc',
                    fontSize: '8px',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }"
                >
                  LOGO
                </div>
                
                <div class="w-full">
                  {{ previewData.sample_text }}
                </div>
              </div>
              
              <!-- DIMENSIONS INFO -->
              <div class="mt-6 text-center">
                <div class="inline-flex items-center gap-4 text-sm text-gray-600">
                  <div class="flex items-center gap-2">
                    <ArrowsPointingOutIcon class="h-4 w-4" />
                    <span>{{ label.width }} × {{ label.height }} mm</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="['fas', 'font']" class="h-4 w-4" />
                    <span>{{ label.font_size }}px</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="h-4 w-4 rounded border" :style="{ backgroundColor: label.background_color, borderColor: label.border_color }"></div>
                    <span>{{ label.background_color }}</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- LABEL CONTENT -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
              <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ $t('gestlab.general.labels.vap_labels.content') }}:</h3>
              <pre class="text-sm text-gray-700 whitespace-pre-wrap bg-white p-4 rounded border border-gray-200">{{ label.content }}</pre>
            </div>
          </div>
        </div>

        <!-- GENERATE LABELS FORM -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <PrinterIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
            </h2>
          </div>
          
          <div class="p-6">
            <!-- GENERATION OPTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labels.generate_single') }}</h3>
                <div class="space-y-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.content') }}
                    </label>
                    <textarea
                      v-model="singleLabel.content"
                      rows="3"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      :placeholder="label.content"
                    />
                  </div>
                  
                  <div v-if="label.has_qr_code" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.qr_content') }}
                    </label>
                    <input
                      v-model="singleLabel.qr_content"
                      type="text"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      :placeholder="previewData.sample_qr"
                    />
                  </div>
                  
                  <div v-if="label.has_barcode" class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.barcode_content') }}
                    </label>
                    <input
                      v-model="singleLabel.barcode_content"
                      type="text"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      :placeholder="previewData.sample_barcode"
                    />
                  </div>
                  
                  <button
                    @click="generateSinglePDF"
                    :disabled="!singleLabel.content"
                    :class="[
                      'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                      !singleLabel.content
                        ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                        : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
                    ]"
                  >
                    <PrinterIcon class="h-5 w-5" />
                    {{ $t('gestlab.general.labels.vap_labels.generate_pdf') }}
                  </button>
                </div>
              </div>
              
              <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ $t('gestlab.general.labels.vap_labels.generate_multiple') }}</h3>
                <div class="space-y-4">
                  <!-- BATCH LABELS LIST -->
                  <div v-for="(item, index) in batchLabels" :key="index" class="space-y-2 p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center justify-between">
                      <h4 class="text-sm font-medium text-gray-900">{{ $t('gestlab.general.labels.vap_labels.label') }} #{{ index + 1 }}</h4>
                      <button
                        @click="removeBatchLabel(index)"
                        type="button"
                        class="text-red-600 hover:text-red-800"
                      >
                        <TrashIcon class="h-4 w-4" />
                      </button>
                    </div>
                    
                    <div class="space-y-2">
                      <input
                        v-model="item.content"
                        type="text"
                        :placeholder="label.content"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      />
                      
                      <div v-if="label.has_qr_code" class="flex gap-2">
                        <input
                          v-model="item.qr_content"
                          type="text"
                          :placeholder="$t('gestlab.general.labels.vap_labels.qr_content')"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                        />
                      </div>
                      
                      <div v-if="label.has_barcode" class="flex gap-2">
                        <input
                          v-model="item.barcode_content"
                          type="text"
                          :placeholder="$t('gestlab.general.labels.vap_labels.barcode_content')"
                          class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                        />
                      </div>
                    </div>
                  </div>
                  
                  <div class="flex gap-4">
                    <button
                      @click="addBatchLabel"
                      type="button"
                      class="flex-1 inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
                    >
                      <PlusCircleIcon class="h-5 w-5" />
                      {{ $t('gestlab.general.labels.vap_labels.buttons.add_label') }}
                    </button>
                    
                    <button
                      @click="generateBatchPDF"
                      :disabled="batchLabels.length === 0 || !batchLabelsValid"
                      :class="[
                        'flex-1 inline-flex justify-center items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                        batchLabels.length === 0 || !batchLabelsValid
                          ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                          : 'bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2'
                      ]"
                    >
                      <DocumentDuplicateIcon class="h-5 w-5" />
                      {{ $t('gestlab.general.labels.vap_labels.batch_print') }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- PRINT SETTINGS -->
            <LabelPrintSettings v-model="printSettings" />
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN (1/3 width) -->
      <div class="space-y-6">
        <!-- LABEL DETAILS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <InformationCircleIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.details') }}
          </h3>
          
          <div class="space-y-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.created_at') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(label.created_at) }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.updated_at') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ formatDate(label.updated_at) }}</dd>
            </div>
            
            <div v-if="label.lab">
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.lab') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ label.lab.name }}</dd>
            </div>
            
            <div v-if="label.department">
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.department') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ label.department.name }}</dd>
            </div>
            
            <div v-if="label.user">
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.created_by') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ label.user.name }}</dd>
            </div>
            
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.text_alignment') }}</dt>
              <dd class="mt-1 text-sm text-gray-900 capitalize">{{ label.text_alignment }}</dd>
            </div>
          </div>
        </div>

        <!-- FEATURES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labels.features') }}
          </h3>
          
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labels.qr_code') }}</span>
              <span :class="featureBadgeClass(label.has_qr_code)">
                {{ label.has_qr_code ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labels.barcode') }}</span>
              <span :class="featureBadgeClass(label.has_barcode)">
                {{ label.has_barcode ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labels.logo') }}</span>
              <span :class="featureBadgeClass(label.logo_path)">
                {{ label.logo_path ? $t('gestlab.general.labels.vap_labels.general.yes') : $t('gestlab.general.labels.vap_labels.general.no') }}
              </span>
            </div>
            
            <div v-if="label.has_qr_code && label.qr_code_content" class="mt-4 p-3 bg-blue-50 rounded-lg">
              <p class="text-xs font-medium text-blue-900">{{ $t('gestlab.general.labels.vap_labels.qr_content') }}:</p>
              <p class="text-xs text-blue-700 mt-1 break-all">{{ label.qr_code_content }}</p>
            </div>
            
            <div v-if="label.has_barcode && label.barcode_content" class="mt-4 p-3 bg-green-50 rounded-lg">
              <p class="text-xs font-medium text-green-900">{{ $t('gestlab.general.labels.vap_labels.barcode_content') }}:</p>
              <p class="text-xs text-green-700 mt-1 break-all">{{ label.barcode_content }}</p>
            </div>
          </div>
        </div>

        <!-- TEMPLATES CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </h3>
          
          <div class="space-y-3">
            <div 
              v-for="template in templates"
              :key="template.id"
              @click="applyTemplate(template)"
              class="cursor-pointer p-3 rounded-lg border border-gray-200 hover:border-blue-900 hover:bg-blue-50 transition-all duration-200"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
                  <p class="text-xs text-gray-500 mt-1">{{ template.description }}</p>
                  <div class="mt-2">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
                      {{ template.category }}
                    </span>
                  </div>
                </div>
                <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                  {{ $t('gestlab.general.labels.vap_labels.featured') }}
                </span>
              </div>
            </div>
            
            <div class="pt-4 border-t border-gray-200">
              <Link
                :href="route('vap_labels.label-templates.index')"
                class="text-sm text-blue-900 hover:text-blue-800 font-medium flex items-center gap-2"
              >
                <span>{{ $t('gestlab.general.labels.vap_labels.view_all_templates') }}</span>
                <ArrowRightIcon class="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>

        <!-- QUICK ACTIONS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
          </h3>
          
          <div class="space-y-3">
            <button
              @click="duplicateLabel"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
            >
              <DocumentDuplicateIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.duplicate_label') }}
            </button>
            
            <button
              @click="toggleStatus"
              :class="[
                'w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm transition-all duration-200',
                label.is_active
                  ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border border-yellow-300'
                  : 'bg-green-100 text-green-800 hover:bg-green-200 border border-green-300'
              ]"
            >
              <PowerIcon class="h-5 w-5" />
              {{ label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}
            </button>
            
            <button
              @click="confirmDelete"
              class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 transition-all duration-200"
            >
              <TrashIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.delete_label') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
  TagIcon, 
  PencilIcon, 
  EyeIcon, 
  PrinterIcon, 
  InformationCircleIcon,
  PlusCircleIcon,
  TrashIcon,
  PowerIcon,
  DocumentDuplicateIcon,
  ArrowRightIcon,
  ArrowsPointingOutIcon
} from '@heroicons/vue/24/outline'
import LabelPrintSettings from '@/Components/LabelPrintSettings.vue'

const props = defineProps({
  label: Object,
  previewData: Object,
  templates: Array,
})

const appendCsrfToken = (form) => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

  if (! csrfToken) {
    return
  }

  const csrfInput = document.createElement('input')
  csrfInput.type = 'hidden'
  csrfInput.name = '_token'
  csrfInput.value = csrfToken
  form.appendChild(csrfInput)
}

const singleLabel = ref({
  content: props.previewData.sample_text,
  qr_content: props.label.has_qr_code ? props.previewData.sample_qr : null,
  barcode_content: props.label.has_barcode ? props.previewData.sample_barcode : null,
})

const batchLabels = ref([
  {
    content: props.previewData.sample_text,
    qr_content: props.label.has_qr_code ? props.previewData.sample_qr : null,
    barcode_content: props.label.has_barcode ? props.previewData.sample_barcode : null,
  }
])

const printSettings = ref({
  include_cutouts: true,
  labels_per_page: 1,
  margin: 5,
  columns: 2,
  rows: 4,
  spacing: 5,
  page_size: 'A4',
  orientation: 'portrait',
})

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800'
}

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'bg-blue-100 text-blue-800',
    material: 'bg-green-100 text-green-800',
    sample: 'bg-purple-100 text-purple-800',
    custom: 'bg-gray-100 text-gray-800',
  }
  return `inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${classes[type] || 'bg-gray-100 text-gray-800'}`
}

const featureBadgeClass = (hasFeature) => {
  return hasFeature
    ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800'
}

const batchLabelsValid = computed(() => {
  return batchLabels.value.every(label => label.content.trim() !== '')
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const addBatchLabel = () => {
  batchLabels.value.push({
    content: '',
    qr_content: props.label.has_qr_code ? '' : null,
    barcode_content: props.label.has_barcode ? '' : null,
  })
}

const removeBatchLabel = (index) => {
  batchLabels.value.splice(index, 1)
}

const previewPDF = () => {
  window.open(route('vap_labels.preview-pdf', props.label.id), '_blank')
}

const generateSinglePDF = () => {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('vap_labels.generate-pdf', props.label.id)
  form.target = '_blank'
  appendCsrfToken(form)
  
  const dataInput = document.createElement('input')
  dataInput.type = 'hidden'
  dataInput.name = 'data[0][content]'
  dataInput.value = singleLabel.value.content || props.previewData.sample_text
  
  if (props.label.has_qr_code) {
    const qrInput = document.createElement('input')
    qrInput.type = 'hidden'
    qrInput.name = 'data[0][qr_content]'
    qrInput.value = singleLabel.value.qr_content || props.previewData.sample_qr
    form.appendChild(qrInput)
  }
  
  if (props.label.has_barcode) {
    const barcodeInput = document.createElement('input')
    barcodeInput.type = 'hidden'
    barcodeInput.name = 'data[0][barcode_content]'
    barcodeInput.value = singleLabel.value.barcode_content || props.previewData.sample_barcode
    form.appendChild(barcodeInput)
  }
  
  // Add print settings
  Object.keys(printSettings.value).forEach(key => {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = key
    input.value = printSettings.value[key]
    form.appendChild(input)
  })
  
  form.appendChild(dataInput)
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}

const generateBatchPDF = () => {
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = route('vap_labels.generate-batch-pdf', props.label.id)
  form.target = '_blank'
  appendCsrfToken(form)
  
  batchLabels.value.forEach((label, index) => {
    const contentInput = document.createElement('input')
    contentInput.type = 'hidden'
    contentInput.name = `data[${index}][content]`
    contentInput.value = label.content
    
    if (props.label.has_qr_code && label.qr_content) {
      const qrInput = document.createElement('input')
      qrInput.type = 'hidden'
      qrInput.name = `data[${index}][qr_content]`
      qrInput.value = label.qr_content
      form.appendChild(qrInput)
    }
    
    if (props.label.has_barcode && label.barcode_content) {
      const barcodeInput = document.createElement('input')
      barcodeInput.type = 'hidden'
      barcodeInput.name = `data[${index}][barcode_content]`
      barcodeInput.value = label.barcode_content
      form.appendChild(barcodeInput)
    }
    
    form.appendChild(contentInput)
  })
  
  // Add batch settings
  Object.keys(printSettings.value).forEach(key => {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = key
    input.value = printSettings.value[key]
    form.appendChild(input)
  })
  
  document.body.appendChild(form)
  form.submit()
  document.body.removeChild(form)
}

const applyTemplate = (template) => {
  if (confirm('Aplicar este modelo? As configurações atuais serão substituídas.')) {
    router.post(route('vap_labels.apply-template', props.label.id), {
      template_id: template.id
    })
  }
}

const duplicateLabel = () => {
  if (confirm('Deseja duplicar esta etiqueta?')) {
    router.post(route('vap_labels.duplicate', props.label.id))
  }
}

const toggleStatus = () => {
  router.post(route('vap_labels.toggle-status', props.label.id))
}

const confirmDelete = () => {
  if (confirm('Tem certeza que deseja eliminar esta etiqueta? Esta ação não pode ser desfeita.')) {
    router.delete(route('vap_labels.labels.destroy', props.label.id))
  }
}
</script>
