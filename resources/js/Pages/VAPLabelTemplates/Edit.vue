<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.templates.edit_title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.templates.edit_description') }}
            <span class="font-semibold text-blue-900">
              {{ template.name }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span :class="statusBadgeClass(template.is_active)">
            {{ template.is_active ? $t('gestlab.general.labels.vap_labels.templates.active') : $t('gestlab.general.labels.vap_labels.templates.inactive') }}
          </span>
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
                <div class="space-y-2">
                  <label class="block text-sm font-medium text-gray-700">
                    {{ $t('gestlab.general.labels.vap_labels.content') }}
                    <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    v-model="form.template_data.content"
                    rows="4"
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
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
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
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      />
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.font_size') }}
                    </label>
                    <input
                      v-model="form.template_data.font_size"
                      type="range"
                      min="6"
                      max="72"
                      class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500">{{ form.template_data.font_size }}px</div>
                  </div>

                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.text_alignment') }}
                    </label>
                    <select
                      v-model="form.template_data.text_alignment"
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                    >
                      <option value="left">Left</option>
                      <option value="center">Center</option>
                      <option value="right">Right</option>
                      <option value="justify">Justify</option>
                    </select>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.border_width') }}
                    </label>
                    <input
                      v-model="form.template_data.border_width"
                      type="range"
                      min="0"
                      max="10"
                      class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                    />
                    <div class="text-xs text-gray-500">{{ form.template_data.border_width }}px</div>
                  </div>

                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">
                      {{ $t('gestlab.general.labels.vap_labels.border_color') }}
                    </label>
                    <div class="flex items-center gap-2">
                      <input
                        v-model="form.template_data.border_color"
                        type="color"
                        class="h-10 w-16 cursor-pointer rounded-lg border border-gray-300"
                      />
                      <input
                        v-model="form.template_data.border_color"
                        type="text"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
                      />
                    </div>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                  <label class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700">
                    <span>{{ $t('gestlab.general.labels.vap_labels.has_qr_code') }}</span>
                    <input
                      v-model="form.template_data.has_qr_code"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </label>

                  <label class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-700">
                    <span>{{ $t('gestlab.general.labels.vap_labels.has_barcode') }}</span>
                    <input
                      v-model="form.template_data.has_barcode"
                      type="checkbox"
                      class="h-4 w-4 rounded border-gray-300 text-blue-900 focus:ring-blue-900"
                    />
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- TEMPLATE PREVIEW CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
              <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                <EyeIcon class="h-5 w-5" />
                {{ $t('gestlab.general.labels.vap_labels.templates.template_preview') }}
              </h2>
            </div>
            
            <div class="p-6">
              <div class="p-4 bg-gray-50 rounded-lg">
                <div class="text-sm font-medium text-gray-700 mb-4">{{ $t('gestlab.general.labels.vap_labels.templates.current_data') }}:</div>
                
                <!-- DATA PREVIEW -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label class="block text-xs font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.type') }}</label>
                    <span class="text-sm text-gray-900">{{ form.template_data.type || 'custom' }}</span>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-500">{{ $t('gestlab.general.labels.vap_labels.dimensions') }}</label>
                    <span class="text-sm text-gray-900">{{ form.template_data.width || 50 }} × {{ form.template_data.height || 25 }} mm</span>
                  </div>
                </div>
                
                <!-- VISUAL PREVIEW -->
                <div class="flex items-center justify-center p-4 bg-white rounded border border-gray-200">
                  <div 
                    class="border border-gray-300"
                    :style="{
                      width: ((form.template_data.width || 50) * 2) + 'px',
                      height: ((form.template_data.height || 25) * 2) + 'px',
                      backgroundColor: form.template_data.background_color || '#ffffff',
                      color: form.template_data.text_color || '#000000',
                      fontSize: (form.template_data.font_size || 12) + 'px',
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
                      {{ form.template_data.content ? form.template_data.content.substring(0, 30) + '...' : $t('gestlab.general.labels.vap_labels.templates.sample_content') }}
                    </div>
                  </div>
                </div>
                
                <div class="mt-4 text-center text-sm text-gray-500">
                  {{ $t('gestlab.general.labels.vap_labels.templates.template_data_note') }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN (1/3 width) -->
        <div class="space-y-6">
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

          <!-- STATS CARD -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('gestlab.general.labels.vap_labels.templates.stats') }}
            </h3>
            
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labels.templates.created_at') }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatDate(template.created_at) }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">{{ $t('gestlab.general.labels.vap_labels.templates.updated_at') }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatDate(template.updated_at) }}</span>
              </div>
              
              <div class="pt-3 border-t border-gray-200">
                <div class="text-xs text-gray-500">
                  {{ $t('gestlab.general.labels.vap_labels.templates.usage_note') }}
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
                {{ form.processing ? $t('gestlab.general.labels.vap_labels.buttons.processing') : $t('gestlab.general.labels.vap_labels.buttons.update_template') }}
              </button>
              
              <div class="grid grid-cols-2 gap-3">
                <button
                  @click="toggleStatus"
                  type="button"
                  :class="[
                    'inline-flex justify-center items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold shadow-sm transition-all duration-200',
                    template.is_active
                      ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border border-yellow-300'
                      : 'bg-green-100 text-green-800 hover:bg-green-200 border border-green-300'
                  ]"
                >
                  <PowerIcon class="h-5 w-5" />
                  {{ template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}
                </button>
                
                <button
                  @click="confirmDelete"
                  type="button"
                  class="inline-flex justify-center items-center gap-2 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-100 transition-all duration-200"
                >
                  <TrashIcon class="h-5 w-5" />
                  {{ $t('gestlab.general.labels.vap_labels.buttons.delete') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { useForm, Link, router } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, 
  InformationCircleIcon,
  Cog6ToothIcon,
  EyeIcon,
  CheckIcon,
  ArrowLeftIcon,
  PowerIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  template: Object,
  categories: Object,
})

const form = useForm({
  name: props.template.name,
  description: props.template.description,
  category: props.template.category,
  template_data: {
    type: props.template.template_data?.type || 'custom',
    content: props.template.template_data?.content || '',
    width: props.template.template_data?.width || 50,
    height: props.template.template_data?.height || 25,
    background_color: props.template.template_data?.background_color || '#ffffff',
    text_color: props.template.template_data?.text_color || '#000000',
    font_size: props.template.template_data?.font_size || 12,
    border_width: props.template.template_data?.border_width || 1,
    border_color: props.template.template_data?.border_color || '#000000',
    text_alignment: props.template.template_data?.text_alignment || 'center',
    has_qr_code: props.template.template_data?.has_qr_code || false,
    qr_code_content: props.template.template_data?.qr_code_content || null,
    qr_code_size: props.template.template_data?.qr_code_size || null,
    has_barcode: props.template.template_data?.has_barcode || false,
    barcode_content: props.template.template_data?.barcode_content || null,
    barcode_type: props.template.template_data?.barcode_type || 'CODE128',
    barcode_width: props.template.template_data?.barcode_width || null,
    barcode_height: props.template.template_data?.barcode_height || null,
    logo_path: props.template.template_data?.logo_path || null,
    logo_size: props.template.template_data?.logo_size || null,
  },
  is_active: props.template.is_active,
  is_featured: props.template.is_featured,
})

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const toggleStatus = () => {
  router.post(route('vap_labels.templates.toggle-status', props.template.id))
}

const confirmDelete = () => {
  if (confirm('Tem certeza que deseja eliminar este modelo? Esta ação não pode ser desfeita.')) {
    router.delete(route('vap_labels.label-templates.destroy', props.template.id))
  }
}

const submit = () => {
  form.put(route('vap_labels.label-templates.update', props.template.id))
}
</script>
