<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <TagIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.create_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.create_description') }}
            <span v-if="selectedTemplate" class="font-semibold text-blue-900">
              {{ selectedTemplate.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ templates.length }} {{ $t('gestlab.general.labels.vap_labels.available_templates') }}
          </span>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- LEFT COLUMN (2/3 width) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- BASIC SETTINGS CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <!-- GRADIENT HEADER -->
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <Cog6ToothIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.basic_settings') }}
            </h2>
          </div>
          
          <!-- CARD CONTENT -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- NAME -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.name') }}
                  <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  :placeholder="$t('gestlab.general.labels.vap_labels.name_placeholder')"
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

              <!-- TYPE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <TagIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                  <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="form.type"
                  :class="[
                    'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                    form.errors.type 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                      : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                  ]"
                >
                  <option value="equipment">{{ $t('gestlab.general.labels.vap_labels.types.equipment') }}</option>
                  <option value="material">{{ $t('gestlab.general.labels.vap_labels.types.material') }}</option>
                  <option value="sample">{{ $t('gestlab.general.labels.vap_labels.types.sample') }}</option>
                  <option value="custom">{{ $t('gestlab.general.labels.vap_labels.types.custom') }}</option>
                </select>
                <p v-if="form.errors.type" class="text-xs text-red-600">
                  {{ form.errors.type }}
                </p>
              </div>

              <div class="space-y-3 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Origem operacional da etiqueta
                </label>
                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                  <button
                    v-for="option in sourceTypeOptions"
                    :key="option.value"
                    type="button"
                    @click="form.source_type = option.value; form.source_id = null"
                    :class="[
                      'rounded-xl border px-4 py-3 text-left text-sm transition',
                      form.source_type === option.value
                        ? 'border-blue-900 bg-blue-50 text-blue-900'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50/50'
                    ]"
                  >
                    <div class="font-semibold">{{ option.label }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ option.description }}</div>
                  </button>
                </div>
              </div>

              <div v-if="availableSources.length" class="space-y-2 md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">
                  Registo de origem
                </label>
                <select
                  v-model="form.source_id"
                  class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                >
                  <option :value="null">Selecionar origem</option>
                  <option v-for="source in availableSources" :key="source.id" :value="source.id">
                    {{ source.label }}
                  </option>
                </select>
              </div>

              <!-- WIDTH -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArrowsPointingOutIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.width') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.width"
                    type="number"
                    step="0.1"
                    min="1"
                    max="1000"
                    :placeholder="$t('gestlab.general.labels.vap_labels.width_placeholder')"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.width 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                </div>
                <p v-if="form.errors.width" class="text-xs text-red-600">
                  {{ form.errors.width }}
                </p>
              </div>

              <!-- HEIGHT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700 flex items-center gap-1">
                  <ArrowsPointingOutIcon class="h-4 w-4" />
                  {{ $t('gestlab.general.labels.vap_labels.height') }}
                  <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center gap-2">
                  <input
                    v-model="form.height"
                    type="number"
                    step="0.1"
                    min="1"
                    max="1000"
                    :placeholder="$t('gestlab.general.labels.vap_labels.height_placeholder')"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.height 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                  />
                  <span class="text-sm text-gray-500 whitespace-nowrap">mm</span>
                </div>
                <p v-if="form.errors.height" class="text-xs text-red-600">
                  {{ form.errors.height }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- CONTENT CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <DocumentTextIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.content') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_labels.label_content') }}
                <span class="text-red-500">*</span>
              </label>
              <textarea
                v-model="form.content"
                rows="4"
                :placeholder="$t('gestlab.general.labels.vap_labels.content_placeholder')"
                :class="[
                  'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                  form.errors.content 
                    ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                    : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                ]"
              />
              <p v-if="form.errors.content" class="text-xs text-red-600">
                {{ form.errors.content }}
              </p>
              <div class="flex items-center gap-2 text-xs text-gray-500">
                <span>{{ $t('gestlab.general.labels.vap_labels.template_variables') }}:</span>
                <code
                  v-for="placeholder in supportedPlaceholders"
                  :key="placeholder"
                  class="bg-gray-100 px-2 py-1 rounded text-xs"
                >
                  {{ placeholder }}
                </code>
              </div>
              <div v-if="sourcePreview" class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-900">
                Esta etiqueta pode ser gerada automaticamente a partir do registo selecionado, reutilizando código, lote, cliente, armazém e datas relevantes.
              </div>
            </div>
          </div>
        </div>

        <!-- APPEARANCE CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <PaintBrushIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.appearance') }}
            </h2>
          </div>
          
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- BACKGROUND COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.background_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.background_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                  />
                  <input
                    v-model="form.background_color"
                    type="text"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.background_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="#ffffff"
                  />
                </div>
                <p v-if="form.errors.background_color" class="text-xs text-red-600">
                  {{ form.errors.background_color }}
                </p>
              </div>

              <!-- TEXT COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.text_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.text_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                  />
                  <input
                    v-model="form.text_color"
                    type="text"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.text_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="#000000"
                  />
                </div>
                <p v-if="form.errors.text_color" class="text-xs text-red-600">
                  {{ form.errors.text_color }}
                </p>
              </div>

              <!-- FONT SIZE -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.font_size') }}
                </label>
                <input
                  v-model="form.font_size"
                  type="range"
                  min="6"
                  max="72"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-gray-500">
                  <span>6px</span>
                  <span class="font-medium">{{ form.font_size }}px</span>
                  <span>72px</span>
                </div>
                <p v-if="form.errors.font_size" class="text-xs text-red-600">
                  {{ form.errors.font_size }}
                </p>
              </div>

              <!-- TEXT ALIGNMENT -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
                </label>
                <div class="flex gap-2">
                  <button
                    v-for="align in ['left', 'center', 'right', 'justify']"
                    :key="align"
                    @click="form.text_alignment = align"
                    :class="[
                      'flex-1 py-2 text-sm font-medium rounded-lg border',
                      form.text_alignment === align
                        ? 'border-blue-900 bg-blue-50 text-blue-900'
                        : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    ]"
                    :title="$t('gestlab.general.labels.vap_labels.align_' + align)"
                  >
                    {{ align.charAt(0).toUpperCase() + align.slice(1) }}
                  </button>
                </div>
                <p v-if="form.errors.text_alignment" class="text-xs text-red-600">
                  {{ form.errors.text_alignment }}
                </p>
              </div>

              <!-- BORDER WIDTH -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.border_width') }}
                </label>
                <input
                  v-model="form.border_width"
                  type="range"
                  min="0"
                  max="10"
                  class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                />
                <div class="flex justify-between text-xs text-gray-500">
                  <span>0px</span>
                  <span class="font-medium">{{ form.border_width }}px</span>
                  <span>10px</span>
                </div>
                <p v-if="form.errors.border_width" class="text-xs text-red-600">
                  {{ form.errors.border_width }}
                </p>
              </div>

              <!-- BORDER COLOR -->
              <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">
                  {{ $t('gestlab.general.labels.vap_labels.border_color') }}
                </label>
                <div class="flex items-center gap-3">
                  <input
                    v-model="form.border_color"
                    type="color"
                    class="h-10 w-20 cursor-pointer rounded-lg border border-gray-300"
                  />
                  <input
                    v-model="form.border_color"
                    type="text"
                    :class="[
                      'block w-full rounded-lg border shadow-sm focus:ring-2 focus:ring-offset-0 text-sm',
                      form.errors.border_color 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
                        : 'border-gray-300 focus:border-blue-900 focus:ring-blue-900'
                    ]"
                    placeholder="#000000"
                  />
                </div>
                <p v-if="form.errors.border_color" class="text-xs text-red-600">
                  {{ form.errors.border_color }}
                </p>
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
            {{ $t('gestlab.general.labels.vap_labels.preview') }}
          </h3>
          <div class="flex items-center justify-center p-4 bg-gray-50 rounded-lg">
            <div 
              class="relative border border-gray-300"
              :style="{
                width: (form.width * 3) + 'px',
                height: (form.height * 3) + 'px',
                backgroundColor: form.background_color,
                color: form.text_color,
                fontSize: form.font_size + 'px',
                borderWidth: form.border_width + 'px',
                borderColor: form.border_color,
                textAlign: form.text_alignment,
                padding: '10px',
                overflow: 'hidden'
              }"
            >
              <div class="h-full flex items-center justify-center">
                <div>
                  {{ form.content || $t('gestlab.general.labels.vap_labels.preview_text') }}
                </div>
              </div>
            </div>
          </div>
          <div class="mt-4 text-center text-sm text-gray-500">
            {{ form.width }} × {{ form.height }} mm
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
              :class="[
                'cursor-pointer p-3 rounded-lg border transition-all duration-200',
                selectedTemplate?.id === template.id
                  ? 'border-blue-900 bg-blue-50'
                  : 'border-gray-200 hover:border-blue-900 hover:bg-blue-50'
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ template.name }}</h4>
                  <p class="text-xs text-gray-500 mt-1">{{ template.description }}</p>
                </div>
                <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                  {{ $t('gestlab.general.labels.vap_labels.featured') }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- LAB & DEPARTMENT CARD -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $t('gestlab.general.labels.vap_labels.assignment') }}
          </h3>
          <div class="space-y-4">
            <!-- LAB -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_labels.lab') }}
              </label>
              <select
                v-model="form.lab_id"
                class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_labels.select_lab') }}</option>
                <option v-for="lab in labs" :key="lab.id" :value="lab.id">
                  {{ lab.name }}
                </option>
              </select>
            </div>

            <!-- DEPARTMENT -->
            <div class="space-y-2">
              <label class="block text-sm font-medium text-gray-700">
                {{ $t('gestlab.general.labels.vap_labels.department') }}
              </label>
              <select
                v-model="form.department_id"
                class="block w-full rounded-lg border border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
              >
                <option value="">{{ $t('gestlab.general.labels.vap_labels.select_department') }}</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
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
              @click="submit"
              :disabled="form.processing"
              :class="[
                'w-full inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                form.processing
                  ? 'bg-gray-200 text-gray-500 cursor-not-allowed'
                  : 'bg-gradient-to-r from-blue-900 to-blue-800 text-white hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2'
              ]"
            >
              <CheckIcon class="h-5 w-5" />
              {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : $t('gestlab.general.labels.vap_labels.buttons.save_label') }}
            </button>
            
            <Link
              :href="route('vap_labels.labels.index')"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-all duration-200"
            >
              <ArrowLeftIcon class="h-5 w-5" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.cancel') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import { 
  TagIcon, 
  Cog6ToothIcon, 
  DocumentTextIcon, 
  PaintBrushIcon,
  CheckIcon, 
  ArrowLeftIcon,
  ArrowsPointingOutIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  templates: Array,
  selectedTemplateId: Number,
  labs: Array,
  departments: Array,
  defaultSettings: Object,
  label: Object,
  sourcePreview: Object,
  sourceOptions: Object,
  supportedPlaceholders: {
    type: Array,
    default: () => ['{name}', '{code}', '{lot}'],
  },
})

const selectedTemplate = ref(null)
const sourceTypeOptions = [
  { value: 'sample_entry', label: 'Amostra', description: 'Receção, retenção, QR e rastreabilidade analítica.' },
  { value: 'equipment', label: 'Equipamento', description: 'Identificação, estado metrológico e manutenção.' },
  { value: 'reagent', label: 'Reagente / material', description: 'Lote, validade, armazenagem e stock.' },
]

const form = useForm({
  name: props.label?.name || '',
  type: props.label?.type || 'custom',
  content: props.label?.content || '',
  width: props.label?.width || props.defaultSettings?.width || 50,
  height: props.label?.height || props.defaultSettings?.height || 25,
  background_color: props.label?.background_color || '#ffffff',
  text_color: props.label?.text_color || '#000000',
  font_size: props.label?.font_size || props.defaultSettings?.font_size || 12,
  border_width: props.label?.border_width || props.defaultSettings?.border_width || 1,
  border_color: props.label?.border_color || '#000000',
  text_alignment: props.label?.text_alignment || 'center',
  lab_id: props.label?.lab_id || null,
  department_id: props.label?.department_id || null,
  logo_path: props.label?.logo_path || null,
  logo_size: props.label?.logo_size || null,
  has_qr_code: props.label?.has_qr_code || false,
  qr_code_content: props.label?.qr_code_content || null,
  qr_code_size: props.label?.qr_code_size || null,
  has_barcode: props.label?.has_barcode || false,
  barcode_content: props.label?.barcode_content || null,
  barcode_type: props.label?.barcode_type || 'CODE128',
  barcode_width: props.label?.barcode_width || null,
  barcode_height: props.label?.barcode_height || null,
  is_active: props.label?.is_active ?? true,
  source_type: props.label?.template_data?.source_type || props.sourcePreview?.source_type || null,
  source_id: props.label?.template_data?.source_id || props.sourcePreview?.source_id || null,
  template_id: props.label?.template_data?.template_id || null,
})

const availableSources = computed(() => {
  if (form.source_type === 'sample_entry' || form.source_type === 'sample') {
    return props.sourceOptions?.samples ?? []
  }

  if (form.source_type === 'equipment' || form.source_type === 'reagent') {
    return props.sourceOptions?.inventory ?? []
  }

  if (form.source_type === 'collection_product') {
    return props.sourceOptions?.collection_products ?? []
  }

  return []
})

const applyTemplate = (template) => {
  selectedTemplate.value = template
  form.template_id = template.id
  if (template.template_data) {
    Object.keys(template.template_data).forEach(key => {
      if (key in form) {
        form[key] = template.template_data[key]
      }
    })
  }
}

onMounted(() => {
  if (! props.selectedTemplateId) {
    return
  }

  const preselectedTemplate = props.templates?.find((template) => template.id === props.selectedTemplateId)

  if (preselectedTemplate) {
    applyTemplate(preselectedTemplate)
  }
})

const submit = () => {
  if (props.label) {
    form.put(route('vap_labels.labels.update', props.label.id))
  } else {
    form.post(route('vap_labels.labels.store'))
  }
}
</script>
