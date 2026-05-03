<template>
  <div class="space-y-8">
    <!-- HEADER CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
            <DocumentTextIcon class="h-7 w-7 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </h1>
          <p class="mt-2 text-gray-600">
            {{ $t('gestlab.general.labels.vap_labels.templates.description') }}
            <span class="font-semibold text-blue-900">
              {{ filters.category ? filters.category : $t('gestlab.general.labels.vap_labels.templates.all_categories') }}
            </span>
          </p>
        </div>
        <div class="flex items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-sm font-medium text-blue-900 ring-1 ring-inset ring-blue-700/10">
            {{ stats.total }} {{ $t('gestlab.general.labels.vap_labels.templates.total_templates') }}
          </span>
          <Link
            :href="route('vap_labels.label-templates.create')"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-colors duration-200"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.create_template') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.templates.search') }}
          </label>
          <input
            v-model="filters.search"
            type="search"
            :placeholder="$t('gestlab.general.labels.vap_labels.templates.search_placeholder')"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.templates.category') }}
          </label>
          <select
            v-model="filters.category"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all_categories') }}</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ $t('gestlab.general.labels.vap_labels.templates.categories.' + category) || category }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.templates.featured') }}
          </label>
          <select
            v-model="filters.featured"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all') }}</option>
            <option value="yes">{{ $t('gestlab.general.labels.vap_labels.templates.featured_only') }}</option>
            <option value="no">{{ $t('gestlab.general.labels.vap_labels.templates.not_featured') }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700">
            {{ $t('gestlab.general.labels.vap_labels.templates.status') }}
          </label>
          <select
            v-model="filters.status"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-900 focus:ring-blue-900 text-sm"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all_status') }}</option>
            <option value="active">{{ $t('gestlab.general.labels.vap_labels.templates.active') }}</option>
            <option value="inactive">{{ $t('gestlab.general.labels.vap_labels.templates.inactive') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- STATS CARD -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.templates.total_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-900">{{ stats.total }}</p>
          </div>
          <DocumentTextIcon class="h-10 w-10 text-blue-100" />
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.templates.active_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-green-500">{{ stats.active }}</p>
          </div>
          <CheckCircleIcon class="h-10 w-10 text-green-100" />
        </div>
      </div>
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ $t('gestlab.general.labels.vap_labels.templates.featured_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-yellow-500">{{ stats.featured }}</p>
          </div>
          <StarIcon class="h-10 w-10 text-yellow-100" />
        </div>
      </div>
    </div>

    <!-- TEMPLATES LIST -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
      <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <ListBulletIcon class="h-5 w-5 text-blue-900" />
            {{ $t('gestlab.general.labels.vap_labels.templates.list') }}
            <span class="text-sm font-normal text-gray-500 ml-2">
              ({{ templates.total }} {{ $t('gestlab.general.labels.vap_labels.general.items') }})
            </span>
          </h2>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="templates.data.length === 0" class="p-12 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
          {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-gray-500">
          {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.description') }}
        </p>
        <Link
          :href="route('vap_labels.label-templates.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_first_template') }}
        </Link>
      </div>

      <!-- TEMPLATES GRID -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
        <div 
          v-for="template in templates.data"
          :key="template.id"
          class="group relative bg-white rounded-xl border border-gray-200 hover:border-blue-900 transition-all duration-200 overflow-hidden shadow-sm hover:shadow-md"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :enter="{ opacity: 1, y: 0 }"
          :delay="100"
        >
          <!-- TEMPLATE HEADER -->
          <div class="bg-gradient-to-r from-blue-50 to-white px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-900 text-white font-semibold">
                  <DocumentTextIcon class="h-4 w-4" />
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-gray-900">
                    {{ template.name }}
                  </h3>
                  <div class="flex items-center gap-2 mt-1">
                    <span :class="categoryBadgeClass(template.category)">
                      {{ $t('gestlab.general.labels.vap_labels.templates.categories.' + template.category) || template.category }}
                    </span>
                    <span v-if="template.is_featured" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">
                      {{ $t('gestlab.general.labels.vap_labels.templates.featured') }}
                    </span>
                  </div>
                </div>
              </div>
              <div class="flex items-center gap-1">
                <button 
                  @click="toggleFeatured(template)"
                  type="button"
                  class="text-gray-400 hover:text-yellow-500 transition-all duration-200 p-1 rounded hover:bg-yellow-50"
                  :title="template.is_featured ? $t('gestlab.general.labels.vap_labels.buttons.remove_featured') : $t('gestlab.general.labels.vap_labels.buttons.mark_featured')"
                >
                  <StarIcon :class="['h-5 w-5', template.is_featured ? 'text-yellow-500 fill-yellow-500' : '']" />
                </button>
                <button 
                  @click="confirmDelete(template)"
                  type="button"
                  class="text-gray-400 hover:text-red-600 transition-all duration-200 p-1 rounded hover:bg-red-50"
                  :title="$t('gestlab.general.labels.vap_labels.buttons.delete')"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- TEMPLATE CONTENT -->
          <div class="p-4">
            <p class="text-sm text-gray-600 mb-4 line-clamp-2">
              {{ template.description || $t('gestlab.general.labels.vap_labels.templates.no_description') }}
            </p>
            
            <!-- TEMPLATE PREVIEW -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
              <div class="text-xs font-medium text-gray-700 mb-2">{{ $t('gestlab.general.labels.vap_labels.templates.preview') }}:</div>
              <div 
                class="mx-auto border border-gray-300"
                :style="{
                  width: '100%',
                  height: '100px',
                  backgroundColor: template.template_data?.background_color || '#ffffff',
                  color: template.template_data?.text_color || '#000000',
                  fontSize: (template.template_data?.font_size || 12) + 'px',
                  borderWidth: (template.template_data?.border_width || 1) + 'px',
                  borderColor: template.template_data?.border_color || '#000000',
                  textAlign: template.template_data?.text_alignment || 'center',
                  padding: '8px',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: template.template_data?.text_alignment || 'center',
                  overflow: 'hidden'
                }"
              >
                <div class="text-xs truncate">
                  {{ template.template_data?.content ? template.template_data.content.substring(0, 50) + '...' : $t('gestlab.general.labels.vap_labels.templates.sample_content') }}
                </div>
              </div>
              <div class="mt-2 text-center text-xs text-gray-500">
                {{ template.template_data?.width || 50 }} × {{ template.template_data?.height || 25 }} mm
              </div>
            </div>
            
            <!-- TEMPLATE FEATURES -->
            <div class="mb-4">
              <div class="text-xs font-medium text-gray-700 mb-2">{{ $t('gestlab.general.labels.vap_labels.templates.features') }}:</div>
              <div class="flex flex-wrap gap-2">
                <span v-if="template.template_data?.has_qr_code" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800">
                  <QrCodeIcon class="h-3 w-3 mr-1" />
                  QR Code
                </span>
                <span v-if="template.template_data?.has_barcode" class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                  <Bars2Icon class="h-3 w-3 mr-1" />
                  {{ template.template_data?.barcode_type || 'CODE128' }}
                </span>
                <span v-if="template.template_data?.logo_path" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                  <PhotoIcon class="h-3 w-3 mr-1" />
                  Logo
                </span>
              </div>
            </div>
            
            <!-- TEMPLATE ACTIONS -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
              <div class="flex items-center gap-2">
                <span :class="statusBadgeClass(template.is_active)">
                  {{ template.is_active ? $t('gestlab.general.labels.vap_labels.templates.active') : $t('gestlab.general.labels.vap_labels.templates.inactive') }}
                </span>
                <span class="text-xs text-gray-500">
                  {{ formatDate(template.updated_at) }}
                </span>
              </div>
              
              <div class="flex items-center gap-2">
                <Link
                  :href="route('vap_labels.label-templates.edit', template.id)"
                  class="text-blue-900 hover:text-blue-800 p-1 rounded hover:bg-blue-50"
                  :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
                >
                  <PencilIcon class="h-5 w-5" />
                </Link>
                <button
                  @click="toggleStatus(template)"
                  :class="[
                    'p-1 rounded',
                    template.is_active 
                      ? 'text-yellow-500 hover:text-yellow-600 hover:bg-yellow-50' 
                      : 'text-green-500 hover:text-green-600 hover:bg-green-50'
                  ]"
                  :title="template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')"
                >
                  <PowerIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- USE TEMPLATE BUTTON -->
          <div class="border-t border-gray-200 p-4 bg-gray-50">
            <Link
              :href="route('vap_labels.labels.create', { template_id: template.id })"
              class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-white border border-blue-900 px-4 py-2 text-sm font-semibold text-blue-900 shadow-sm hover:bg-blue-50 transition-all duration-200"
            >
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.use_template') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="templates.data.length > 0" class="border-t border-gray-200 px-6 py-4">
        <Pagination :links="templates.links" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { 
  DocumentTextIcon, 
  PlusCircleIcon, 
  ListBulletIcon, 
  PencilIcon, 
  TrashIcon, 
  PowerIcon,
  CheckCircleIcon,
  StarIcon,
  QrCodeIcon,
  Bars2Icon,
  PhotoIcon
} from '@heroicons/vue/24/outline'
import { debounce } from 'lodash'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  templates: Object,
  filters: Object,
  stats: Object,
  categories: Array,
})

const filters = ref(props.filters)

const categoryBadgeClass = (category) => {
  const colors = {
    equipment: 'bg-blue-100 text-blue-800',
    consumables: 'bg-green-100 text-green-800',
    samples: 'bg-purple-100 text-purple-800',
    storage: 'bg-yellow-100 text-yellow-800',
    safety: 'bg-red-100 text-red-800',
    general: 'bg-gray-100 text-gray-800',
    custom: 'bg-indigo-100 text-indigo-800',
  }
  return `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[category] || 'bg-gray-100 text-gray-800'}`
}

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800'
    : 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    month: 'short',
    day: 'numeric'
  })
}

const confirmDelete = (template) => {
  if (confirm('Tem certeza que deseja eliminar este modelo? Esta ação não pode ser desfeita.')) {
    router.delete(route('vap_labels.label-templates.destroy', template.id))
  }
}

const toggleStatus = (template) => {
  router.post(route('vap_labels.templates.toggle-status', template.id), {}, {
    preserveScroll: true,
  })
}

const toggleFeatured = (template) => {
  router.post(route('vap_labels.templates.toggle-featured', template.id), {}, {
    preserveScroll: true,
  })
}

// Debounce filter changes
const applyFilters = debounce(() => {
  router.get(route('vap_labels.label-templates.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
  })
}, 300)

watch(filters, applyFilters, { deep: true })
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
