<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import VapTable from "@/Components/vap-table/table.vue";
import ComboboxMultiple from "@/Components/combobox-multiple-enhanced.vue";
import { loadSelectOptions } from "@/Utils/selectOptions";
import { Link, router } from "@inertiajs/vue3";
import { ExternalLink as ArrowTopRightOnSquareIcon, FlaskConical as BeakerIcon, ClipboardList as ClipboardDocumentListIcon, Funnel as FunnelIcon } from "@lucide/vue";
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
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.samples.page_title')" :lede="$t('gestlab.general.labels.samples.legacy_description')">
      <template #actions>
        <Link
          :href="sampleEntryUrl"
          class="ds-button ds-button-primary shrink-0"
        >
          <BeakerIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.sample_entry') }}
        </Link>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.samples.records') }}</dt>
        <dd class="pl-cell-value">{{ props.record?.meta?.total ?? 0 }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.samples.main_flow') }}</dt>
        <dd class="pl-cell-text">{{ $t('gestlab.general.labels.sample_entry') }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">{{ $t('gestlab.general.labels.samples.active_filters') }}</dt>
        <dd class="pl-cell-value">{{ selectedParameters.length }}
            <FunnelIcon class="h-4 w-4 text-[var(--ds-text-soft)]" /></dd>
      </div>
    </dl>

    <section class="ds-command-surface overflow-hidden">
      <header class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div class="flex items-start gap-3">
          <ClipboardDocumentListIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))]" />
          <div>
            <h2 class="ds-heading text-base">{{ $t('gestlab.general.labels.samples.worksheet_title') }}</h2>
            <p class="ds-copy mt-1 text-sm">{{ $t('gestlab.general.labels.samples.worksheet_description') }}</p>
          </div>
        </div>
      </header>

      <div class="grid grid-cols-1 gap-4 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
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
          class="ds-button ds-button-primary"
        >
          <ClipboardDocumentListIcon class="h-4 w-4" />
          {{ $t('gestlab.actions.multiple_sample_worksheet') }}
          <ArrowTopRightOnSquareIcon class="h-4 w-4" />
        </a>
      </div>
    </section>

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
        <strong class="font-mono text-xs font-bold text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">{{ row.code || '-' }}</strong>
      </template>

      <template #column-collection="{ row }">
        <span class="font-semibold text-[var(--ds-text)]">{{ row.collection || '-' }}</span>
      </template>
    </vap-table>
  </div>
</template>
