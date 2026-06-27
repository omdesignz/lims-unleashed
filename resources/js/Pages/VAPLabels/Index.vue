<template>
  <div class="space-y-6">
    <ModuleHero
      :eyebrow="$t('gestlab.general.labels.vap_labels.index.eyebrow')"
      :title="$t('gestlab.general.labels.vap_labels.title')"
      :description="$t('gestlab.general.labels.vap_labels.index.description')"
    >
      <template #actions>
        <Link
          :href="route('vap_labels.labels.create')"
          class="ds-button ds-button-primary"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.create_label') }}
        </Link>
      </template>

      <div class="grid gap-3 md:grid-cols-3">
        <article
          v-for="statCard in labelStatCards"
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
            {{ $t('gestlab.general.labels.vap_labels.index.filters_title') }}
          </h2>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            {{ $t('gestlab.general.labels.vap_labels.index.filters_description') }}
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

      <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(16rem,1.45fr)_repeat(3,minmax(12rem,1fr))]">
        <BaseInput
          v-model="filters.search"
          type="search"
          :label="$t('gestlab.general.labels.vap_labels.search')"
          :placeholder="$t('gestlab.general.labels.vap_labels.search_placeholder')"
        >
          <template #leading>
            <MagnifyingGlassIcon class="h-5 w-5" />
          </template>
        </BaseInput>

        <BaseSelect
          v-model="filters.type"
          :label="$t('gestlab.general.labels.vap_labels.type')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.all_types') }}
          </option>
          <option value="equipment">
            {{ $t('gestlab.general.labels.vap_labels.types.equipment') }}
          </option>
          <option value="material">
            {{ $t('gestlab.general.labels.vap_labels.types.material') }}
          </option>
          <option value="sample">
            {{ $t('gestlab.general.labels.vap_labels.types.sample') }}
          </option>
          <option value="custom">
            {{ $t('gestlab.general.labels.vap_labels.types.custom') }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="filters.lab_id"
          :label="$t('gestlab.general.labels.vap_labels.lab')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.all_labs') }}
          </option>
          <option
            v-for="lab in labsList"
            :key="lab.id"
            :value="lab.id"
          >
            {{ lab.name }}
          </option>
        </BaseSelect>

        <BaseSelect
          v-model="filters.status"
          :label="$t('gestlab.general.labels.vap_labels.status')"
        >
          <option value="">
            {{ $t('gestlab.general.labels.vap_labels.all_status') }}
          </option>
          <option value="active">
            {{ $t('gestlab.general.labels.vap_labels.active') }}
          </option>
          <option value="inactive">
            {{ $t('gestlab.general.labels.vap_labels.inactive') }}
          </option>
        </BaseSelect>
      </div>
    </section>

    <section
      v-if="visibleGalleryLabels.length"
      class="ds-panel p-5 sm:p-6"
    >
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.index.gallery_eyebrow') }}
          </p>
          <h2 class="ds-heading mt-2 text-2xl">
            {{ $t('gestlab.general.labels.vap_labels.index.gallery_title') }}
          </h2>
          <p class="ds-copy mt-2 max-w-3xl text-sm">
            {{ $t('gestlab.general.labels.vap_labels.index.gallery_description') }}
          </p>
        </div>
        <Link
          :href="route('vap_labels.labels.create')"
          class="ds-button ds-button-secondary"
        >
          <PlusCircleIcon class="h-5 w-5" />
          {{ $t('gestlab.general.labels.vap_labels.buttons.add_label') }}
        </Link>
      </div>

      <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="label in visibleGalleryLabels"
          :key="`gallery-${label.id}`"
          class="group rounded-[1.7rem] border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-4 shadow-[var(--ds-shadow-control)] transition duration-200 hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb)/0.72)]"
        >
          <div
            class="relative flex min-h-36 items-center justify-center overflow-hidden rounded-[1.25rem] border p-5 text-center shadow-inner"
            :style="labelPreviewStyle(label)"
          >
            <span class="absolute left-3 top-3 rounded-full bg-white/85 px-2.5 py-1 text-[0.62rem] font-black uppercase tracking-[0.14em] text-slate-800 shadow-sm ring-1 ring-black/5 dark:bg-slate-950/85 dark:text-slate-100">
              {{ label.width }} × {{ label.height }} mm
            </span>
            <p class="max-w-[15rem] whitespace-pre-line text-sm font-black leading-6">
              {{ labelContentPreview(label, 92) }}
            </p>
          </div>

          <div class="mt-4 flex items-start justify-between gap-3">
            <div class="min-w-0">
              <h3 class="truncate text-base font-black text-[var(--ds-text)]">
                {{ label.name }}
              </h3>
              <p class="mt-1 truncate text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ label.lab?.name || $t('gestlab.general.labels.vap_labels.index.no_lab') }}
              </p>
            </div>
            <span :class="typeBadgeClass(label.type)">
              {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
            </span>
          </div>

          <div class="mt-4 flex items-center gap-2">
            <Link
              :href="route('vap_labels.labels.show', label.id)"
              class="ds-table-action"
              :title="$t('gestlab.general.labels.vap_labels.buttons.view')"
            >
              <EyeIcon class="h-4 w-4" />
            </Link>
            <Link
              :href="route('vap_labels.labels.edit', label.id)"
              class="ds-table-action"
              :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
            >
              <PencilIcon class="h-4 w-4" />
            </Link>
            <button
              type="button"
              class="ds-button ds-button-primary ml-auto min-h-0 rounded-full px-3 py-2 text-xs"
              :title="$t('gestlab.general.labels.vap_labels.buttons.preview_pdf')"
              @click="previewPdf(label)"
            >
              {{ $t('gestlab.general.labels.vap_labels.preview_pdf') }}
            </button>
          </div>
        </article>
      </div>
    </section>

    <section class="ds-table-shell">
      <div class="ds-table-summary px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.vap_labels.index.records_eyebrow') }}
          </p>
          <h2 class="ds-heading mt-1 text-xl">
            {{ $t('gestlab.general.labels.vap_labels.list') }}
          </h2>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ resultSummary }}
          </p>
        </div>
      </div>

      <div
        v-if="!labelRows.length"
        class="p-5 sm:p-8"
      >
        <div class="ds-empty-state p-8 text-center">
          <TagIcon class="mx-auto h-12 w-12 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-4 text-base">
            {{ $t('gestlab.general.labels.vap_labels.empty_state.title') }}
          </h3>
          <p class="ds-copy mx-auto mt-2 max-w-md text-sm">
            {{ $t('gestlab.general.labels.vap_labels.empty_state.description') }}
          </p>
          <Link
            :href="route('vap_labels.labels.create')"
            class="ds-button ds-button-primary mt-6"
          >
            <PlusCircleIcon class="h-5 w-5" />
            {{ $t('gestlab.general.labels.vap_labels.buttons.create_first_label') }}
          </Link>
        </div>
      </div>

      <div v-else>
        <div class="divide-y divide-[var(--ds-border)] lg:hidden">
          <article
            v-for="label in labelRows"
            :key="`card-${label.id}`"
            class="space-y-4 px-5 py-5"
          >
            <div class="flex items-start gap-3">
              <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm ring-1 ring-black/5"
                :style="labelIconStyle(label)"
              >
                <TagIcon class="h-6 w-6" />
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="truncate text-base font-black text-[var(--ds-text)]">
                    {{ label.name }}
                  </h3>
                  <span :class="statusBadgeClass(label.is_active)">
                    {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
                  </span>
                </div>
                <p class="mt-1 text-sm font-semibold text-[var(--ds-text-muted)]">
                  {{ labelContentPreview(label, 64) }}
                </p>
              </div>
            </div>

            <dl class="grid gap-2 sm:grid-cols-3">
              <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2">
                <dt class="ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                </dt>
                <dd class="mt-1">
                  <span :class="typeBadgeClass(label.type)">
                    {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
                  </span>
                </dd>
              </div>
              <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2">
                <dt class="ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.dimensions') }}
                </dt>
                <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">
                  {{ label.width }} × {{ label.height }} mm
                </dd>
              </div>
              <div class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2">
                <dt class="ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.lab') }}
                </dt>
                <dd class="mt-1 text-sm font-bold text-[var(--ds-text)]">
                  {{ label.lab?.name || '—' }}
                </dd>
              </div>
            </dl>

            <div class="flex flex-wrap items-center gap-2 border-t border-[var(--ds-border)] pt-3">
              <Link
                :href="route('vap_labels.labels.show', label.id)"
                class="ds-table-action"
              >
                {{ $t('gestlab.general.labels.vap_labels.buttons.view') }}
              </Link>
              <Link
                :href="route('vap_labels.labels.edit', label.id)"
                class="ds-table-action"
              >
                {{ $t('gestlab.general.labels.vap_labels.buttons.edit') }}
              </Link>
              <button
                type="button"
                class="ds-table-action"
                @click="toggleStatus(label)"
              >
                {{ label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate') }}
              </button>
              <button
                type="button"
                class="ds-table-action ds-table-action-danger"
                @click="confirmDelete(label)"
              >
                {{ $t('gestlab.general.labels.vap_labels.buttons.delete_label') }}
              </button>
            </div>
          </article>
        </div>

        <div class="hidden overflow-x-auto lg:block">
          <table class="min-w-full divide-y divide-[var(--ds-border)]">
            <thead class="ds-table-head">
              <tr>
                <th scope="col" class="px-6 py-4 text-left ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.name') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.type') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.dimensions') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.lab') }}
                </th>
                <th scope="col" class="px-6 py-4 text-left ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.status') }}
                </th>
                <th scope="col" class="px-6 py-4 text-right ds-table-heading">
                  {{ $t('gestlab.general.labels.vap_labels.actions.title') }}
                </th>
              </tr>
            </thead>
            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr
                v-for="label in labelRows"
                :key="label.id"
                class="ds-table-row"
              >
                <td class="px-6 py-5">
                  <div class="flex items-center gap-4">
                    <div
                      class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-sm ring-1 ring-black/5"
                      :style="labelIconStyle(label)"
                    >
                      <TagIcon class="h-6 w-6" />
                    </div>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-black text-[var(--ds-text)]">
                        {{ label.name }}
                      </p>
                      <p class="mt-1 max-w-xs truncate text-sm font-semibold text-[var(--ds-text-muted)]">
                        {{ labelContentPreview(label, 52) }}
                      </p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-5">
                  <span :class="typeBadgeClass(label.type)">
                    {{ $t('gestlab.general.labels.vap_labels.types.' + label.type) }}
                  </span>
                </td>
                <td class="ds-table-cell whitespace-nowrap px-6 py-5">
                  {{ label.width }} × {{ label.height }} mm
                </td>
                <td class="ds-table-cell whitespace-nowrap px-6 py-5">
                  {{ label.lab?.name || '—' }}
                </td>
                <td class="px-6 py-5">
                  <span :class="statusBadgeClass(label.is_active)">
                    {{ label.is_active ? $t('gestlab.general.labels.vap_labels.active') : $t('gestlab.general.labels.vap_labels.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-5">
                  <div class="flex items-center justify-end gap-1">
                    <Link
                      :href="route('vap_labels.labels.show', label.id)"
                      class="ds-table-action"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.view')"
                    >
                      <EyeIcon class="h-5 w-5" />
                    </Link>
                    <Link
                      :href="route('vap_labels.labels.edit', label.id)"
                      class="ds-table-action"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.edit')"
                    >
                      <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button
                      type="button"
                      class="ds-table-action"
                      :title="label.is_active ? $t('gestlab.general.labels.vap_labels.buttons.deactivate') : $t('gestlab.general.labels.vap_labels.buttons.activate')"
                      @click="toggleStatus(label)"
                    >
                      <PowerIcon class="h-5 w-5" />
                    </button>
                    <button
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      :title="$t('gestlab.general.labels.vap_labels.buttons.delete_label')"
                      @click="confirmDelete(label)"
                    >
                      <TrashIcon class="h-5 w-5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
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
      v-if="showDeleteConfirmation"
      :title="$t('gestlab.general.labels.vap_labels.delete_label')"
      :description="$t('gestlab.general.labels.vap_labels.confirm_delete_label_irreversible')"
      :cancel="$t('gestlab.general.buttons.cancel')"
      :confirm="$t('gestlab.general.labels.vap_labels.buttons.delete_label')"
      variant="danger"
      @confirmed="deleteLabel"
      @canceled="resetDeleteConfirmation"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { trans } from 'laravel-vue-i18n'
import { debounce } from 'lodash'
import {
  AdjustmentsHorizontalIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  PencilIcon,
  PlusCircleIcon,
  PowerIcon,
  TagIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import BaseInput from '@/Components/base/BaseInput.vue'
import BaseSelect from '@/Components/base/BaseSelect.vue'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import ModuleHero from '@/Components/base/ModuleHero.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  labels: {
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
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
})

const filters = ref({
  search: props.filters?.search ?? '',
  type: props.filters?.type ?? '',
  lab_id: props.filters?.lab_id ?? '',
  status: props.filters?.status ?? '',
})

const showDeleteConfirmation = ref(false)
const labelPendingDelete = ref(null)

const labelRows = computed(() => Array.isArray(props.labels?.data) ? props.labels.data : [])
const labsList = computed(() => Array.isArray(props.labs) ? props.labs : [])
const paginationLinks = computed(() => Array.isArray(props.labels?.links) ? props.labels.links : [])
const totalRecords = computed(() => Number(props.labels?.total ?? labelRows.value.length))
const activeRecords = computed(() => Number(props.stats?.active ?? labelRows.value.filter((label) => Boolean(label.is_active)).length))
const typeStats = computed(() => Array.isArray(props.stats?.by_type) ? props.stats.by_type : [])
const visibleGalleryLabels = computed(() => labelRows.value.slice(0, 6))
const hasActiveFilters = computed(() => Object.values(filters.value).some((value) => String(value ?? '').trim() !== ''))
const resultSummary = computed(() => trans('gestlab.general.labels.vap_labels.index.result_summary', {
  count: totalRecords.value,
}))

const labelStatCards = computed(() => [
  {
    label: trans('gestlab.general.labels.vap_labels.index.stats_active'),
    value: activeRecords.value,
    hint: trans('gestlab.general.labels.vap_labels.index.stats_active_hint'),
  },
  {
    label: trans('gestlab.general.labels.vap_labels.index.stats_types'),
    value: typeStats.value.length,
    hint: trans('gestlab.general.labels.vap_labels.index.stats_types_hint'),
  },
  {
    label: trans('gestlab.general.labels.vap_labels.index.stats_scope'),
    value: filters.value.type
      ? trans(`gestlab.general.labels.vap_labels.types.${filters.value.type}`)
      : trans('gestlab.general.labels.vap_labels.all_types'),
    hint: trans('gestlab.general.labels.vap_labels.index.stats_scope_hint'),
  },
])

const typeBadgeClass = (type) => {
  const classes = {
    equipment: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-400/20 dark:bg-sky-500/10 dark:text-sky-200',
    material: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200',
    sample: 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-800 dark:border-fuchsia-400/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-200',
    custom: 'border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)]',
  }

  return `inline-flex items-center rounded-full border px-3 py-1 text-xs font-black ${classes[type] || classes.custom}`
}

const statusBadgeClass = (isActive) => {
  return isActive
    ? 'inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-800 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-200'
    : 'inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-1 text-xs font-black text-[var(--ds-text-muted)]'
}

const labelContentPreview = (label, length = 64) => {
  const content = String(label?.content || '').trim()

  if (!content) {
    return trans('gestlab.general.labels.vap_labels.preview_text')
  }

  return content.length > length ? `${content.substring(0, length)}...` : content
}

const labelIconStyle = (label) => ({
  backgroundColor: label?.background_color || '#fffdf7',
  border: `${label?.border_width || 1}px solid ${label?.border_color || '#d8cfbe'}`,
  color: label?.text_color || '#15231f',
})

const labelPreviewStyle = (label) => ({
  ...labelIconStyle(label),
  fontSize: `${Math.max(Number(label?.font_size || 12), 10)}px`,
  textAlign: label?.text_alignment || 'center',
})

const previewPdf = (label) => {
  window.open(route('vap_labels.preview-pdf', label.id), '_blank', 'noopener')
}

const confirmDelete = (label) => {
  labelPendingDelete.value = label
  showDeleteConfirmation.value = true
}

const resetDeleteConfirmation = () => {
  showDeleteConfirmation.value = false
  labelPendingDelete.value = null
}

const deleteLabel = () => {
  if (!labelPendingDelete.value?.id) {
    resetDeleteConfirmation()
    return
  }

  router.delete(route('vap_labels.labels.destroy', labelPendingDelete.value.id), {
    preserveScroll: true,
    onFinish: resetDeleteConfirmation,
  })
}

const toggleStatus = (label) => {
  router.post(route('vap_labels.toggle-status', label.id), {}, {
    preserveScroll: true,
  })
}

const applyFilters = debounce(() => {
  router.get(route('vap_labels.labels.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300)

const clearFilters = () => {
  filters.value = {
    search: '',
    type: '',
    lab_id: '',
    status: '',
  }
}

watch(filters, applyFilters, { deep: true })
</script>
