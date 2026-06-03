<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import VapTable from "@/Components/vap-table/table.vue";
import ModuleHero from "@/Components/base/ModuleHero.vue";
import ModuleCard from "@/Components/base/ModuleCard.vue";
import ComboboxMultiple from "@/Components/combobox-multiple-enhanced.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import { loadSelectOptions } from "@/Utils/selectOptions";
import { Link, router } from "@inertiajs/vue3";
import { ArrowTopRightOnSquareIcon, BeakerIcon, ClipboardDocumentListIcon } from "@heroicons/vue/24/outline";
import { computed, ref, watch } from "vue";
import { trans } from "laravel-vue-i18n";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: Object,
  parameters: {
    type: Array,
    default: () => [],
  },
  fields: {
    type: Array,
    default: () => [],
  },
  model: String,
  abilities: {
    type: Array,
    default: () => [],
  },
  query: {
    type: Object,
    default: () => ({}),
  },
  trashedFilter: {
    type: Boolean,
    default: false,
  },
  trashedOptions: {
    type: [Array, Object],
    default: () => [],
  },
  initialFilters: {
    type: Object,
    default: () => ({}),
  },
  initialSortField: {
    type: String,
    default: "",
  },
  initialSortDirection: {
    type: String,
    default: "asc",
  },
  initialIncludes: {
    type: Array,
    default: () => [],
  },
  initialGlobalFilter: {
    type: String,
    default: "",
  },
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
  createAction: {
    type: Boolean,
    default: false,
  },
  entrypoint: {
    type: Object,
    default: () => ({}),
  },
});

const selectedParameters = ref([...(props.parameters || [])]);
const selectedIDs = ref([]);

const columns = computed(() =>
  props.fields.map(field => ({
    field: field.value,
    filter_field: field.filter_field,
    label: field.name,
    visible: true,
    filterable: field.filterable,
    type: field.type,
    format: field.format,
    filter: field.filter,
    options: field.options ? field.options : [],
    config: field.config ? field.config : {},
  })),
);

const selectedParameterIds = computed(() =>
  selectedParameters.value
    .map(parameter => parameter?.value)
    .filter(value => value !== undefined && value !== null),
);

const sampleEntryUrl = computed(() => props.entrypoint?.create_sample_url || route("vap_samples.index"));

const worksheetUrl = computed(() => {
  if (!selectedParameterIds.value.length) {
    return null;
  }

  return route("directcollections.getMultipleParametersToAnalyzePDF", {
    recordIds: selectedParameterIds.value,
  });
});

const filters = [
  {
    id: null,
    label: trans("gestlab.filter.none"),
  },
  {
    id: "trashed",
    label: trans("gestlab.filter.excluded"),
  },
];

const actions = [];

const mapParameterOption = parameter => ({
  value: parameter.id,
  label: [parameter.code, parameter.name].filter(Boolean).join(" - ") || parameter.name || parameter.code || `#${parameter.id}`,
});

function loadParameters(query, setOptions) {
  return loadSelectOptions("/parameters/getParameter", query, setOptions, mapParameterOption);
}

function applyParameterFilter() {
  router.get(
    route("samples.index"),
    {
      parameters: selectedParameterIds.value,
      filter: props.query?.filter || {},
      sort: props.query?.sort || undefined,
      per_page: props.record?.meta?.per_page || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    },
  );
}

function handleBulkAction() {
  selectedIDs.value = [];
}

watch(selectedParameters, applyParameterFilter, { deep: true });
</script>

<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <ModuleHero
      eyebrow="LIMS"
      :title="$t('gestlab.general.labels.samples.page_title')"
      :description="$t('gestlab.general.labels.samples.legacy_description')"
    >
      <template #actions>
        <Link
          :href="sampleEntryUrl"
          class="inline-flex items-center gap-2 rounded-2xl bg-primary-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-offset-slate-900"
        >
          <BeakerIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.sample_entry') }}
        </Link>
      </template>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/70 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-slate-700/70 dark:bg-slate-950/45">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.samples.records') }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ props.record?.meta?.total ?? 0 }}</p>
        </div>
        <div class="rounded-2xl border border-white/70 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-slate-700/70 dark:bg-slate-950/45">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.samples.main_flow') }}</p>
          <p class="mt-2 text-sm font-semibold text-slate-950 dark:text-white">{{ $t('gestlab.general.labels.sample_entry') }}</p>
        </div>
        <div class="rounded-2xl border border-white/70 bg-white/70 p-4 shadow-sm backdrop-blur dark:border-slate-700/70 dark:bg-slate-950/45">
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $t('gestlab.general.labels.samples.active_filters') }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">{{ selectedParameters.length }}</p>
        </div>
      </div>
    </ModuleHero>

    <ModuleCard
      :title="$t('gestlab.general.labels.samples.worksheet_title')"
      :description="$t('gestlab.general.labels.samples.worksheet_description')"
    >
      <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
        <ComboboxMultiple
          v-model="selectedParameters"
          :multiple="true"
          :load-options="loadParameters"
          :title-label="$t('gestlab.general.labels.samples.parameters')"
          :placeholder="$t('gestlab.general.search_input_placeholder')"
          :loading-label="$t('gestlab.general.buttons.searching')"
          :no-results-label="$t('gestlab.general.messages.no_results')"
        />

        <a
          v-if="worksheetUrl"
          :href="worksheetUrl"
          target="_blank"
          rel="noopener"
          class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 dark:focus:ring-offset-slate-900"
        >
          <ClipboardDocumentListIcon class="h-4 w-4" />
          {{ $t('gestlab.actions.multiple_sample_worksheet') }}
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
        </a>
      </div>
    </ModuleCard>

    <vap-table
      :model="props.model"
      :abilities="props.abilities"
      :data="props.record.data"
      :columns="columns"
      :query="props.query"
      :filters="filters"
      :initialFilters="props.initialFilters"
      :initialSortField="props.initialSortField"
      :initialSortDirection="props.initialSortDirection"
      :initialIncludes="props.initialIncludes"
      :trashedFilter="props.trashedFilter"
      :trashedOptions="props.trashedOptions"
      :slideOverEdit="props.slideOverEdit"
      :createAction="props.createAction"
      :pagination="props.record.meta"
      :actions="actions"
      @create-record="router.get(sampleEntryUrl)"
      @update-selected-ids="selectedIDs = $event"
      @execute-bulk-action="handleBulkAction"
    >
      <template #column-code="{ row }">
        <strong class="font-semibold text-primary-900 dark:text-primary-300">{{ row.code || '-' }}</strong>
      </template>

      <template #column-collection="{ row }">
        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ row.collection || '-' }}</span>
      </template>
    </vap-table>
  </div>
</template>
