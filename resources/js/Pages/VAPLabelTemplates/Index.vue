<template>
  <div class="space-y-6">
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.vap_labels.index.eyebrow')"
      :title="$t('gestlab.general.labels.vap_labels.templates.title')"
      :description="$t('gestlab.general.labels.vap_labels.templates.description')"
    >
      <template #actions>
        <span class="ds-chip">
          {{ totalTemplates }} {{ $t('gestlab.general.labels.vap_labels.templates.total_templates') }}
        </span>
        <Link
          :href="route('vap_labels.label-templates.create')"
          class="ds-button ds-button-primary"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_template') }}
        </Link>
      </template>

      <div class="grid gap-3 md:grid-cols-3">
        <article
          v-for="statCard in templateStatCards"
          :key="statCard.label"
          class="ds-card bg-[var(--ds-panel-raised)] p-4"
        >
          <p class="ds-kicker text-[0.64rem]">
            {{ statCard.label }}
          </p>
          <p class="mt-2 text-2xl font-black text-[var(--ds-text)]">
            {{ statCard.value }}
          </p>
          <p class="mt-1 text-xs font-semibold leading-5 text-[var(--ds-text-muted)]">
            {{ statCard.hint }}
          </p>
        </article>
      </div>
    </ModuleHero>

    <section class="ds-command-surface p-5 sm:p-6">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.index.filters_eyebrow') }}
          </p>
          <h2 class="ds-heading mt-2 text-2xl">
            {{ $t('gestlab.general.labels.vap_labels.templates.list') }}
          </h2>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            {{ $t('gestlab.general.labels.vap_labels.templates.usage_note') }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span class="ds-chip">
            {{ resultSummary }}
          </span>
          <button
            v-if="hasActiveFilters"
            type="button"
            class="ds-button ds-button-secondary"
            @click="clearFilters"
          >
            <AdjustmentsHorizontalIcon class="h-4 w-4" />
            {{ $t('gestlab.general.buttons.clear') }}
          </button>
        </div>
      </div>

      <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(16rem,1.35fr)_repeat(3,minmax(12rem,1fr))]">
        <BaseInput
          v-model="filters.search"
          type="search"
          :label="$t('gestlab.general.labels.vap_labels.templates.search')"
          :placeholder="$t('gestlab.general.labels.vap_labels.templates.search_placeholder')"
        >
          <template #leading>
            <MagnifyingGlassIcon class="h-5 w-5" />
          </template>
        </BaseInput>

        <BaseSelect
          v-model="filters.category"
          :label="$t('gestlab.general.labels.vap_labels.templates.category')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.templates.all_categories') }}
          </option>
          <option
            v-for="category in categoriesList"
            :key="category"
            :value="category"
          >
            {{ categoryLabel(category) }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="filters.featured"
          :label="$t('gestlab.general.labels.vap_labels.templates.featured')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.templates.all') }}
          </option>
          <option value="yes">
            {{ $t('gestlab.general.labels.vap_labels.templates.featured_only') }}
          </option>
          <option value="no">
            {{ $t('gestlab.general.labels.vap_labels.templates.not_featured') }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="filters.status"
          :label="$t('gestlab.general.labels.vap_labels.templates.status')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.templates.all_status') }}
          </option>
          <option value="active">
            {{ $t('gestlab.general.labels.vap_labels.templates.active') }}
          </option>
          <option value="inactive">
            {{ $t('gestlab.general.labels.vap_labels.templates.inactive') }}
          </option>
        </BaseSelect>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.templates.list') }}
          </p>
          <h2 class="ds-heading mt-1 text-xl">
            {{ $t('gestlab.general.labels.vap_labels.templates.title') }}
          </h2>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ resultSummary }}
          </p>
        </div>
      </div>

      <div
        v-if="!templateRows.length"
        class="p-5 sm:p-8"
      >
        <div class="ds-empty-state p-8 text-center">
          <DocumentTextIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-4 text-base">
            {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.title') }}
          </h3>
          <p class="ds-copy mx-auto mt-2 max-w-md text-sm">
            {{ $t('gestlab.general.labels.vap_labels.templates.empty_state.description') }}
          </p>
          <Link
            :href="route('vap_labels.label-templates.create')"
            class="ds-button ds-button-primary mt-6"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.create_first_template') }}
          </Link>
        </div>
      </div>

      <div v-else>
        <div class="divide-y divide-[var(--ds-border)]">
          <article
            v-for="template in templateRows"
            :key="template.id"
            class="grid gap-4 px-5 py-4 transition hover:bg-[var(--ds-panel-subtle)] lg:grid-cols-[9rem_minmax(0,1fr)_auto] lg:items-center"
          >
            <div class="flex h-20 items-center justify-center overflow-hidden rounded-md border p-2 text-center text-[0.6rem] font-black leading-tight" :style="templatePreviewStyle(template)">
              {{ templatePreviewText(template) }}
            </div>

            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-base font-black text-[var(--ds-text)]">{{ template.name }}</h3>
                <span :class="categoryBadgeClass(template.category)">{{ categoryLabel(template.category) }}</span>
                <span :class="statusBadgeClass(template.is_active)">{{ template.is_active ? $t('gestlab.general.labels.vap_labels.templates.active') : $t('gestlab.general.labels.vap_labels.templates.inactive') }}</span>
                <span v-if="template.is_featured" class="ds-badge ds-badge-warning">{{ $t('gestlab.general.labels.vap_labels.templates.featured') }}</span>
                <span v-if="template.is_system" class="ds-chip">Modelo do sistema</span>
              </div>
              <p class="mt-1 line-clamp-1 text-sm font-semibold text-[var(--ds-text-muted)]">{{ template.description || $t('gestlab.general.labels.vap_labels.templates.no_description') }}</p>
              <p class="mt-2 text-xs font-semibold text-[var(--ds-text-soft)]">
                {{ template.template_data?.width || 50 }} × {{ template.template_data?.height || 25 }} mm · {{ formatDate(template.updated_at) }}
                <span v-if="template.template_data?.has_qr_code"> · QR</span><span v-if="template.template_data?.has_barcode"> · {{ template.template_data?.barcode_type || 'CODE128' }}</span>
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-1 lg:justify-end">
              <Link :href="route('vap_labels.labels.create', { template_id: template.id })" class="ds-button ds-button-secondary mr-1">
                <PlusCircleIcon class="h-4 w-4" />
                {{ $t('gestlab.general.labels.vap_labels.buttons.use_template') }}
              </Link>
              <Link v-if="!template.is_system" :href="route('vap_labels.label-templates.edit', template.id)" class="ds-table-action" :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"><PencilIcon class="h-5 w-5" /></Link>
              <button v-if="!template.is_system" type="button" class="ds-table-action" :title="template.is_featured ? $t('gestlab.general.labels.vap_labels.buttons.remove_featured') : $t('gestlab.general.labels.vap_labels.buttons.mark_featured')" @click="toggleFeatured(template)"><StarIcon :class="['h-5 w-5', template.is_featured ? 'fill-amber-500 text-amber-500' : '']" /></button>
              <button v-if="!template.is_system" type="button" class="ds-table-action" :title="template.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')" @click="toggleStatus(template)"><PowerIcon class="h-5 w-5" /></button>
              <button v-if="!template.is_system" type="button" class="ds-table-action ds-table-action-danger" :title="$t('gestlab.general.labels.vap_labels.buttons.delete')" @click="confirmDelete(template)"><TrashIcon class="h-5 w-5" /></button>
            </div>
          </article>
        </div>

        <div
          v-if="paginationLinks.length"
          class="border-t border-[var(--ds-border)] px-5 py-4 sm:px-6"
        >
          <Pagination :links="paginationLinks" />
        </div>
      </div>
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
  SlidersHorizontal as AdjustmentsHorizontalIcon,
  FileText as DocumentTextIcon,
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
import ModuleHero from '@/Components/base/ModuleHero.vue'
import Pagination from '@/Components/Pagination.vue'

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

const templateStatCards = computed(() => [
  {
    label: trans('gestlab.general.labels.vap_labels.templates.total_templates'),
    value: Number(props.stats?.total ?? totalTemplates.value),
    hint: trans('gestlab.general.labels.vap_labels.templates.usage_note'),
  },
  {
    label: trans('gestlab.general.labels.vap_labels.templates.active_templates'),
    value: Number(props.stats?.active ?? 0),
    hint: trans('gestlab.general.labels.vap_labels.templates.active_description'),
  },
  {
    label: trans('gestlab.general.labels.vap_labels.templates.featured_templates'),
    value: Number(props.stats?.featured ?? 0),
    hint: trans('gestlab.general.labels.vap_labels.templates.featured_description'),
  },
])

const categoryLabel = (category) => trans(`gestlab.general.labels.vap_labels.templates.categories.${category}`) || category

const categoryBadgeClass = (category) => {
  const colors = {
    equipment: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-200',
    consumables: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200',
    samples: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800 dark:border-fuchsia-400/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-200',
    storage: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200',
    safety: 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-400/20 dark:bg-rose-500/10 dark:text-rose-200',
    custom: 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-200',
  }

  return `inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-black ${colors[category] || 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]'}`
}

const statusBadgeClass = (isActive) => {
  return isActive
    ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-0.5 text-xs font-black text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-2.5 py-0.5 text-xs font-black text-[var(--ds-text-muted)]'
}

const templatePreviewStyle = (template) => ({
  backgroundColor: template.template_data?.background_color || '#ffffff',
  color: template.template_data?.text_color || '#000000',
  fontSize: `${template.template_data?.font_size || 12}px`,
  borderWidth: `${template.template_data?.border_width || 1}px`,
  borderColor: template.template_data?.border_color || '#000000',
  textAlign: template.template_data?.text_alignment || 'center',
})

const templatePreviewText = (template) => {
  const content = String(template.template_data?.content || '').trim()

  if (!content) {
    return trans('gestlab.general.labels.vap_labels.templates.sample_content')
  }

  return content.length > 50 ? `${content.substring(0, 50)}...` : content
}

const formatDate = (dateString) => {
  if (!dateString) {
    return '—'
  }

  return new Date(dateString).toLocaleDateString('pt-PT', {
    month: 'short',
    day: 'numeric',
  })
}

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
