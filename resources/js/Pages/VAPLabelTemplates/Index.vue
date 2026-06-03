<template>
  <div class="label-template-surface space-y-8" :class="commercialDocumentThemeClasses">
    <!-- HEADER CARD -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="relative isolate flex flex-col gap-5 overflow-hidden p-6 lg:flex-row lg:items-center lg:justify-between">
        <div class="absolute inset-x-0 top-0 -z-10 h-32 bg-gradient-to-r from-primary-600/15 via-sky-400/10 to-emerald-400/10 dark:from-primary-500/20 dark:via-sky-500/10 dark:to-emerald-500/10"></div>
        <div>
          <h1 class="flex items-center gap-3 text-2xl font-bold text-slate-950 dark:text-white">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-600/20">
              <DocumentTextIcon class="h-6 w-6" />
            </span>
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
            {{ $t('gestlab.general.labels.vap_labels.templates.description') }}
            <span class="font-semibold text-primary-700 dark:text-primary-300">
              {{ filters.category ? filters.category : $t('gestlab.general.labels.vap_labels.templates.all_categories') }}
            </span>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <span class="inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-800 ring-1 ring-inset ring-primary-700/10 dark:bg-primary-500/10 dark:text-primary-200 dark:ring-primary-400/20">
            {{ stats.total }} {{ $t('gestlab.general.labels.vap_labels.templates.total_templates') }}
          </span>
          <Link
            :href="route('vap_labels.label-templates.create')"
            class="inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-primary-600/20 transition-colors duration-200 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.create_template') }}
          </Link>
        </div>
      </div>
    </div>

    <!-- FILTERS CARD -->
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ $t('gestlab.general.labels.vap_labels.templates.search') }}
          </label>
          <input
            v-model="filters.search"
            type="search"
            :placeholder="$t('gestlab.general.labels.vap_labels.templates.search_placeholder')"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500"
          />
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ $t('gestlab.general.labels.vap_labels.templates.category') }}
          </label>
          <select
            v-model="filters.category"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all_categories') }}</option>
            <option v-for="category in categories" :key="category" :value="category">
              {{ $t('gestlab.general.labels.vap_labels.templates.categories.' + category) || category }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ $t('gestlab.general.labels.vap_labels.templates.featured') }}
          </label>
          <select
            v-model="filters.featured"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
          >
            <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all') }}</option>
            <option value="yes">{{ $t('gestlab.general.labels.vap_labels.templates.featured_only') }}</option>
            <option value="no">{{ $t('gestlab.general.labels.vap_labels.templates.not_featured') }}</option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-200">
            {{ $t('gestlab.general.labels.vap_labels.templates.status') }}
          </label>
          <select
            v-model="filters.status"
            class="block w-full rounded-2xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
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
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.templates.total_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-primary-700 dark:text-primary-300">{{ stats.total }}</p>
          </div>
          <DocumentTextIcon class="h-10 w-10 text-blue-100" />
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.templates.active_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-green-500">{{ stats.active }}</p>
          </div>
          <CheckCircleIcon class="h-10 w-10 text-green-100" />
        </div>
      </div>
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.templates.featured_templates') }}</p>
            <p class="mt-2 text-3xl font-bold text-yellow-500">{{ stats.featured }}</p>
          </div>
          <StarIcon class="h-10 w-10 text-yellow-100" />
        </div>
      </div>
    </div>

    <!-- TEMPLATES LIST -->
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
      <div class="border-b border-slate-200 bg-slate-50 px-6 py-4 dark:border-slate-800 dark:bg-slate-950/60">
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-950 dark:text-white">
            <ListBulletIcon class="h-5 w-5 text-primary-600 dark:text-primary-300" />
            {{ $t('gestlab.general.labels.vap_labels.templates.list') }}
            <span class="ml-2 text-sm font-normal text-slate-500 dark:text-slate-400">
              ({{ templates.total }} {{ $t('gestlab.general.labels.vap_labels.general.items') }})
            </span>
          </h2>
        </div>
      </div>

      <!-- EMPTY STATE -->
      <div v-if="templates.data.length === 0" class="p-12 text-center">
        <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-700" />
        <h3 class="mt-4 text-sm font-semibold text-slate-900 dark:text-white">
          {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.title') }}
        </h3>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.description') }}
        </p>
        <Link
          :href="route('vap_labels.label-templates.create')"
          class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900"
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
          class="group relative overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:border-primary-400 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/80 dark:hover:border-primary-500/60"
          v-motion
          :initial="{ opacity: 0, y: 20 }"
          :enter="{ opacity: 1, y: 0 }"
          :delay="100"
        >
          <!-- TEMPLATE HEADER -->
          <div class="border-b border-slate-200 bg-gradient-to-r from-primary-50 to-white px-4 py-3 dark:border-slate-800 dark:from-primary-500/10 dark:to-slate-950">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-2xl bg-primary-600 text-white font-semibold">
                  <DocumentTextIcon class="h-4 w-4" />
                </div>
                <div>
                  <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
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
                  class="rounded-xl p-1 text-slate-400 transition-all duration-200 hover:bg-yellow-50 hover:text-yellow-500 dark:hover:bg-yellow-500/10"
                  :title="template.is_featured ? $t('gestlab.general.labels.vap_labels.buttons.remove_featured') : $t('gestlab.general.labels.vap_labels.buttons.mark_featured')"
                >
                  <StarIcon :class="['h-5 w-5', template.is_featured ? 'text-yellow-500 fill-yellow-500' : '']" />
                </button>
                <button 
                  @click="confirmDelete(template)"
                  type="button"
                  class="rounded-xl p-1 text-slate-400 transition-all duration-200 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10"
                  :title="$t('gestlab.general.labels.vap_labels.buttons.delete')"
                >
                  <TrashIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- TEMPLATE CONTENT -->
          <div class="p-4">
            <p class="mb-4 line-clamp-2 text-sm text-slate-600 dark:text-slate-300">
              {{ template.description || $t('gestlab.general.labels.vap_labels.templates.no_description') }}
            </p>
            
            <!-- TEMPLATE PREVIEW -->
            <div class="mb-4 rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/60">
              <div class="mb-2 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.templates.preview') }}:</div>
              <div 
                class="mx-auto rounded-xl border border-slate-300 dark:border-slate-700"
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
              <div class="mt-2 text-center text-xs text-slate-500 dark:text-slate-400">
                {{ template.template_data?.width || 50 }} × {{ template.template_data?.height || 25 }} mm
              </div>
            </div>
            
            <!-- TEMPLATE FEATURES -->
            <div class="mb-4">
              <div class="mb-2 text-xs font-medium text-slate-700 dark:text-slate-300">{{ $t('gestlab.general.labels.vap_labels.templates.features') }}:</div>
              <div class="flex flex-wrap gap-2">
                <span v-if="template.template_data?.has_qr_code" class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800 dark:bg-blue-500/10 dark:text-blue-200">
                  <QrCodeIcon class="h-3 w-3 mr-1" />
                  QR Code
                </span>
                <span v-if="template.template_data?.has_barcode" class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800 dark:bg-green-500/10 dark:text-green-200">
                  <Bars2Icon class="h-3 w-3 mr-1" />
                  {{ template.template_data?.barcode_type || 'CODE128' }}
                </span>
                <span v-if="template.template_data?.logo_path" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-200">
                  <PhotoIcon class="h-3 w-3 mr-1" />
                  Logo
                </span>
              </div>
            </div>
            
            <!-- TEMPLATE ACTIONS -->
            <div class="flex items-center justify-between border-t border-slate-200 pt-4 dark:border-slate-800">
              <div class="flex items-center gap-2">
                <span :class="statusBadgeClass(template.is_active)">
                  {{ template.is_active ? $t('gestlab.general.labels.vap_labels.templates.active') : $t('gestlab.general.labels.vap_labels.templates.inactive') }}
                </span>
                <span class="text-xs text-slate-500 dark:text-slate-400">
                  {{ formatDate(template.updated_at) }}
                </span>
              </div>
              
              <div class="flex items-center gap-2">
                <Link
                  :href="route('vap_labels.label-templates.edit', template.id)"
                  class="rounded-xl p-1.5 text-primary-700 hover:bg-primary-50 hover:text-primary-800 dark:text-primary-300 dark:hover:bg-primary-500/10"
                  :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
                >
                  <PencilIcon class="h-5 w-5" />
                </Link>
                <button
                  @click="toggleStatus(template)"
                  :class="[
                    'p-1 rounded',
                    template.is_active
                      ? 'text-yellow-500 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-500/10'
                      : 'text-green-500 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-500/10'
                  ]"
                  :title="template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')"
                >
                  <PowerIcon class="h-5 w-5" />
                </button>
              </div>
            </div>
          </div>
          
          <!-- USE TEMPLATE BUTTON -->
          <div class="border-t border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/60">
            <Link
              :href="route('vap_labels.labels.create', { template_id: template.id })"
              class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-primary-500 bg-white px-4 py-2 text-sm font-semibold text-primary-700 shadow-sm transition-all duration-200 hover:bg-primary-50 dark:bg-slate-900 dark:text-primary-300 dark:hover:bg-primary-500/10"
            >
              <PlusCircleIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.vap_labels.buttons.use_template') }}
            </Link>
          </div>
        </div>
      </div>

      <!-- PAGINATION -->
      <div v-if="templates.data.length > 0" class="border-t border-slate-200 px-6 py-4 dark:border-slate-800">
        <Pagination :links="templates.links" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
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
import { trans } from 'laravel-vue-i18n'
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
    equipment: 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-200',
    consumables: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
    samples: 'bg-purple-100 text-purple-800 dark:bg-purple-500/10 dark:text-purple-200',
    storage: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/10 dark:text-yellow-200',
    safety: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
    general: 'bg-slate-100 text-slate-800 dark:bg-slate-700/70 dark:text-slate-200',
    custom: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-200',
  }
  return `inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${colors[category] || 'bg-slate-100 text-slate-800 dark:bg-slate-700/70 dark:text-slate-200'}`
}

const statusBadgeClass = (isActive) => {
  return isActive 
    ? 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200'
    : 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-700/70 dark:text-slate-200'
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('pt-PT', {
    month: 'short',
    day: 'numeric'
  })
}

const confirmDelete = (template) => {
  if (confirm(trans('gestlab.general.labels.vap_labels.templates.confirm_delete_template'))) {
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
