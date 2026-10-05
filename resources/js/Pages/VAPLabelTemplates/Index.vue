<template>
  <div class="pl-page space-y-6">
    <PageHeader
      :trail="[{ title: 'Etiquetas', url: route('vap_labels.labels.index') }, { title: $t('gestlab.general.labels.vap_labels.templates.title') }]"
      :title="$t('gestlab.general.labels.vap_labels.templates.title')"
      :lede="$t('gestlab.general.labels.vap_labels.templates.usage_note')"
    >
      <template #actions>
        <Link :href="route('vap_labels.label-templates.create')" class="ds-button ds-button-primary">
          <PlusCircleIcon class="h-4 w-4" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_template') }}
        </Link>
      </template>
    </PageHeader>

    <section class="pl-panel">
      <header class="pl-panel-head flex-wrap gap-3">
        <div class="relative min-w-[14rem] flex-1">
          <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--pl-faint)]" aria-hidden="true" />
          <BaseInput v-model="filters.search" type="search" class="ds-field pl-9" :placeholder="$t('gestlab.general.labels.vap_labels.templates.search_placeholder')" :aria-label="$t('gestlab.general.labels.vap_labels.templates.search')" />
        </div>
        <BaseSelect v-model="filters.category" class="min-w-[12rem]" :aria-label="$t('gestlab.general.labels.vap_labels.templates.category')">
          <option value="">{{ $t('gestlab.general.labels.vap_labels.templates.all_categories') }}</option>
          <option v-for="category in categoriesList" :key="category" :value="category">{{ categoryLabel(category) }}</option>
        </BaseSelect>
        <div class="pl-segmented" role="radiogroup" :aria-label="$t('gestlab.general.labels.vap_labels.templates.status')">
          <button v-for="option in statusOptions" :key="option.value" type="button" role="radio" :aria-checked="filters.status === option.value" @click="filters.status = option.value">{{ option.label }}</button>
        </div>
        <button type="button" class="pl-chip" :class="filters.featured === 'yes' ? 'pl-chip-wait' : ''" :aria-pressed="filters.featured === 'yes'" @click="filters.featured = filters.featured === 'yes' ? '' : 'yes'">
          <StarIcon class="h-3.5 w-3.5" aria-hidden="true" />
          {{ $t('gestlab.general.labels.vap_labels.templates.featured_only') }}
        </button>
      </header>

      <ul v-if="templateRows.length" class="grid sm:grid-cols-2 xl:grid-cols-3" :aria-label="resultSummary">
        <li v-for="template in templateRows" :key="template.id" class="flex flex-col border-b border-r border-[var(--pl-line)]">
          <div class="label-stage h-44 p-4">
            <LabelPreview :label="templateLabel(template)" :values="exampleValues" :scale="thumbnailScale(template)" :title="template.name" />
          </div>
          <div class="flex flex-1 flex-col gap-3 border-t border-[var(--pl-line)] p-4">
            <div>
              <div class="flex flex-wrap items-center gap-1.5">
                <h2 class="mr-1 truncate text-sm font-bold text-[var(--pl-fg)]">{{ template.name }}</h2>
                <StatusChip v-if="template.is_featured" tone="wait">{{ $t('gestlab.general.labels.vap_labels.templates.featured') }}</StatusChip>
                <StatusChip v-if="!template.is_active">{{ $t('gestlab.general.labels.vap_labels.templates.inactive') }}</StatusChip>
                <StatusChip v-if="template.is_system" tone="done">Modelo do sistema</StatusChip>
              </div>
              <p class="mt-1 text-xs text-[var(--pl-muted)]">
                {{ categoryLabel(template.category) }} · {{ template.template_data?.width || 50 }} × {{ template.template_data?.height || 25 }} mm
                <template v-if="template.template_data?.has_qr_code"> · QR</template>
                <template v-if="template.template_data?.has_barcode"> · {{ template.template_data?.barcode_type || 'CODE128' }}</template>
              </p>
              <p v-if="template.description" class="mt-2 line-clamp-2 text-xs text-[var(--pl-muted)]">{{ template.description }}</p>
            </div>
            <div class="mt-auto flex flex-wrap items-center gap-1">
              <Link :href="route('vap_labels.labels.create', { template_id: template.id })" class="ds-button ds-button-secondary mr-auto">
                <PlusCircleIcon class="h-4 w-4" aria-hidden="true" />
                {{ $t('gestlab.general.labels.vap_labels.buttons.use_template') }}
              </Link>
              <template v-if="!template.is_system">
                <Link :href="route('vap_labels.label-templates.edit', template.id)" class="ds-table-action" :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"><PencilIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}</span></Link>
                <button type="button" class="ds-table-action" :title="template.is_featured ? $t('gestlab.general.labels.vap_labels.buttons.remove_featured') : $t('gestlab.general.labels.vap_labels.buttons.mark_featured')" @click="toggleFeatured(template)"><StarIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ template.is_featured ? $t('gestlab.general.labels.vap_labels.buttons.remove_featured') : $t('gestlab.general.labels.vap_labels.buttons.mark_featured') }}</span></button>
                <button type="button" class="ds-table-action" :title="template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')" @click="toggleStatus(template)"><PowerIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}</span></button>
                <button type="button" class="ds-table-action ds-table-action-danger" :title="$t('gestlab.general.labels.vap_labels.buttons.delete')" @click="confirmDelete(template)"><TrashIcon class="h-4 w-4" aria-hidden="true" /><span class="sr-only">{{ $t('gestlab.general.labels.vap_labels.buttons.delete') }}</span></button>
              </template>
            </div>
          </div>
        </li>
      </ul>

      <div v-else class="px-5 py-14 text-center">
        <p class="text-sm font-bold text-[var(--pl-fg)]">{{ $t('gestlab.general.labels.vap_labels.templates.empty_state.title') }}</p>
        <p class="mx-auto mt-2 max-w-md text-sm text-[var(--pl-muted)]">{{ $t('gestlab.general.labels.vap_labels.templates.empty_state.description') }}</p>
        <button v-if="hasActiveFilters" type="button" class="ds-button ds-button-secondary mt-4" @click="clearFilters">{{ $t('gestlab.general.buttons.clear') }}</button>
      </div>

      <footer v-if="paginationLinks.length > 3" class="border-t border-[var(--pl-line)] px-4 py-3">
        <Pagination :links="paginationLinks" />
      </footer>
    </section>

    <confirm-dialog
      v-if="templatePendingDelete"
      :title="$t('gestlab.general.labels.vap_labels.buttons.delete_template')"
      :description="$t('gestlab.general.labels.vap_labels.templates.confirm_delete_template')"
      :cancel="$t('gestlab.general.buttons.cancel')"
      :confirm="$t('gestlab.general.labels.vap_labels.buttons.delete')"
      variant="danger"
      @confirmed="deleteTemplate"
      @canceled="templatePendingDelete = null"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { debounce } from 'lodash'
import {
  Search as MagnifyingGlassIcon,
  Pencil as PencilIcon,
  CirclePlus as PlusCircleIcon,
  Power as PowerIcon,
  Star as StarIcon,
  Trash2 as TrashIcon,
} from '@lucide/vue'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import LabelPreview from '@/Components/labels/LabelPreview.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import StatusChip from '@/Components/plano/StatusChip.vue'
import { labelExampleValues } from '@/Support/label-codes.mjs'
import Pagination from '@/Components/pagination.vue'

const props = defineProps({
  templates: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  categories: {
    type: Array,
    default: () => [],
  },
})

const filters = ref({
  search: props.filters?.search ?? '',
  category: props.filters?.category ?? '',
  featured: props.filters?.featured ?? '',
  status: props.filters?.status ?? '',
})
const templatePendingDelete = ref(null)

const templateRows = computed(() => Array.isArray(props.templates?.data) ? props.templates.data : [])
const categoriesList = computed(() => Array.isArray(props.categories) ? props.categories : [])
const paginationLinks = computed(() => Array.isArray(props.templates?.links) ? props.templates.links : [])
const totalTemplates = computed(() => Number(props.templates?.total ?? templateRows.value.length))
const hasActiveFilters = computed(() => Object.values(filters.value).some((value) => String(value ?? '').trim() !== ''))
const resultSummary = computed(() => trans('gestlab.general.labels.vap_labels.templates.result_summary', {
  count: totalTemplates.value,
}))

const categoryLabel = (category) => trans(`gestlab.general.labels.vap_labels.templates.categories.${category}`) || category

const statusOptions = computed(() => [
  { value: '', label: trans('gestlab.general.labels.vap_labels.templates.all_status') },
  { value: 'active', label: trans('gestlab.general.labels.vap_labels.templates.active') },
  { value: 'inactive', label: trans('gestlab.general.labels.vap_labels.templates.inactive') },
])

// A template is a label's settings: each card draws the label it makes.
const exampleValues = labelExampleValues()
const templateLabel = (template) => ({ ...(template.template_data || {}), content: template.template_data?.content || '{name}\n{code}' })
const thumbnailScale = (template) => Math.max(0.5, Math.min(240 / (Number(template.template_data?.width) || 50), 130 / (Number(template.template_data?.height) || 25), 4))

const confirmDelete = (template) => {
  templatePendingDelete.value = template
}

const deleteTemplate = () => {
  if (!templatePendingDelete.value?.id) {
    templatePendingDelete.value = null
    return
  }

  router.delete(route('vap_labels.label-templates.destroy', templatePendingDelete.value.id), {
    onFinish: () => {
      templatePendingDelete.value = null
    },
  })
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

const applyFilters = debounce(() => {
  router.get(route('vap_labels.label-templates.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300)

const clearFilters = () => {
  filters.value = {
    search: '',
    category: '',
    featured: '',
    status: '',
  }
}

watch(filters, applyFilters, { deep: true })
</script>
