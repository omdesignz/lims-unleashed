<script setup>
import { computed, reactive, ref, useId, watch } from "vue";
import Pagination from "@/Components/pagination.vue";
import selectFilter from "@/Components/select-filter.vue";
import emptyState from "@/Components/empty-state.vue";
import datePicker from "@/Components/date-picker.vue";
import selectAction from "@/Components/select-action.vue";
import debounce from "lodash/debounce";
import { pickBy } from "lodash";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { Plus as PlusIcon, X as XMarkIcon } from "@lucide/vue";
import { usePermission } from "@/Composables/usePermissions";
import { trans } from "laravel-vue-i18n";

const { hasPermission } = usePermission();

const props = defineProps({
  record: {
    type: Object,
    default: () => ({
      data: [],
      meta: {},
    }),
  },
  createAction: {
    type: Boolean,
    default: true,
  },
  hasQr: {
    type: Boolean,
    default: false,
  },
  slideOverEdit: Boolean,
  fields: {
    type: Array,
    default: () => [],
  },
  actions: {
    type: Array,
    default: () => [],
  },
  model: {
    type: String,
    default: "",
  },
  abilities: {
    type: Array,
    default: () => [],
  },
  query: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Array,
    default: () => [],
  },
  actionMethods: {
    type: Object,
    default: () => ({}),
  },
  actionProcessing: Boolean,
  archiveHandler: Function,
  actionConfirmation: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["execute-action", "slideover-on", "create-record"]);
const canCreate = computed(() => props.createAction && hasPermission('add_' + props.model));

// With no query string the server sends an empty list, whose `filter` is the array method.
const initialQuery = Array.isArray(props.query) ? {} : props.query ?? {};

const query = reactive({
  search: initialQuery.search ?? "",
  filter: initialQuery.filter ?? null,
  date: initialQuery.date ?? null,
  page: null,
});

const actionId = ref(null);
const recordId = ref(null);
const recordUrl = ref(null);
const showDeleteConfirmation = ref(false);
const isProcessingAction = ref(false);

const confirmationDialogTitle = computed(() => {
  return props.actionConfirmation[actionId.value]?.title ?? trans("gestlab.actions.confirmation_dialog_title." + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return props.actionConfirmation[actionId.value]?.description ?? trans(
    "gestlab.actions.confirmation_dialog_description." + actionId.value,
  );
});

const selectedRecordIds = computed(() => {
  return props.record.data
    .filter((record) => record.selected)
    .map((record) => record.id);
});

const displayFields = computed(() => {
  return props.fields.filter((field) => {
    return field.value !== "actions" && field.type !== "actions";
  });
});

const allVisibleSelected = computed(() => {
  return (
    props.record.data.length > 0 &&
    props.record.data.every((record) => Boolean(record.selected))
  );
});

const hasActiveQuery = computed(() => {
  const hasDate = typeof query.date === "object" && query.date !== null
    ? Boolean(query.date.start || query.date.end)
    : Boolean(query.date);

  return Boolean(query.search || query.filter || hasDate);
});

const searchId = `records-search-${useId()}`;

const defaultFilterOptions = [
  {
    id: null,
    label: "gestlab.filter.filter",
  },
  {
    id: "trashed",
    label: "gestlab.filter.excluded",
  },
];

const filters = computed(() => {
  return props.filterOptions.length ? props.filterOptions : defaultFilterOptions;
});

watch(
  query,
  debounce(function (value) {
    router.get(usePage().url, pickBy(value), {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }, 300),
  { deep: true },
);

function executeAction(selected) {
  emit("execute-action", selected);
}

function changeFilter(filterId) {
  query.filter = filterId;
}

function updateRange(value) {
  query.date = value;
}

function toggleSelectAll() {
  const nextValue = !allVisibleSelected.value;

  props.record.data.forEach((record) => {
    record.selected = nextValue;
  });
}

function clearSelection() {
  props.record.data.forEach((record) => {
    record.selected = false;
  });
}

function requestRowAction(record, action) {
  recordId.value = record.id;
  actionId.value = action;
  recordUrl.value = record.links[`${action}_path`];
  showDeleteConfirmation.value = true;
}

function clearQueryFilters() {
  query.search = "";
  query.filter = null;
  query.date = null;
}

function editRecord(record) {
  if (props.slideOverEdit) {
    emit("slideover-on", record);
    return;
  }

  router.visit(record.links.edit_path, {
    preserveScroll: true,
  });
}

function confirmAction() {
  processAction(actionId.value);
}

function processAction(currentActionId) {
  if (props.actionProcessing || isProcessingAction.value || !["delete", "restore"].includes(currentActionId)) return;

  if (props.archiveHandler) {
    props.archiveHandler(currentActionId, [recordId.value]);
    showDeleteConfirmation.value = false;
    return;
  }

  isProcessingAction.value = true;
  router.visit(recordUrl.value, {
    method: props.actionMethods[currentActionId] ?? "get",
    data: { recordIds: [recordId.value] },
    preserveState: false,
    preserveScroll: true,
    onSuccess: () => {
      actionId.value = null;
      recordId.value = null;
      recordUrl.value = null;
    },
    onFinish: () => { isProcessingAction.value = false; },
  });
  showDeleteConfirmation.value = false;
}

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});
</script>

<template>
  <div class="pl-records">
    <form class="pl-filter" role="search" @submit.prevent>
      <label :for="searchId" class="pl-filter-prompt">Filtro://</label>
      <BaseInput
        :id="searchId"
        v-model="query.search"
        type="search"
        data-bare
        class="pl-filter-input"
        :placeholder="$t('gestlab.general.search_input_placeholder')"
      />

      <select-filter
        :filters="filters"
        :model-value="query.filter"
        @execute="changeFilter"
      />

      <div class="pl-filter-dates">
        <date-picker
          v-model.range.string="query.date"
          locale="pt-PT"
          color="primary"
          mode="date"
          range
          :input-debounce="500"
          :masks="masks"
          @update:model-value="updateRange"
        />
      </div>

      <button v-if="hasActiveQuery" type="button" class="ds-chip" @click="clearQueryFilters">
        {{ $t("gestlab.general.buttons.clear") }}
        <XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" />
      </button>

      <button v-if="canCreate" type="button" class="ds-button ds-button-primary ml-auto" @click="$emit('create-record')">
        <PlusIcon class="h-4 w-4" aria-hidden="true" />
        {{ $t("gestlab.general.buttons.new_record") }}
      </button>
    </form>

    <section class="pl-panel" :aria-label="$t('gestlab.general.titles.records_list')" :aria-busy="actionProcessing || isProcessingAction">
      <DataTable v-if="record.data.length" class="pl-stack-table">
        <thead>
          <tr>
            <th scope="col" class="w-10">
              <CheckboxInput
                :checked="allVisibleSelected"
                type="checkbox"
                class="ds-checkbox"
                :aria-label="$t('gestlab.general.buttons.select_all')"
                @change="toggleSelectAll"
              />
            </th>
            <th v-if="props.hasQr" scope="col"><span class="sr-only">QR</span></th>
            <th v-for="field in displayFields" :key="field.value" scope="col">{{ $t(field.name) }}</th>
            <th scope="col"><span class="sr-only">{{ $t("gestlab.actions.action") }}</span></th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="row in record.data" :key="row.id" :data-selected="Boolean(row.selected)" :data-archived="row.deleted || undefined">
            <td class="pl-stack-check">
              <CheckboxInput
                v-model="row.selected"
                type="checkbox"
                class="ds-checkbox"
                :aria-label="`Seleccionar ${row[displayFields[0]?.value] ?? row.id}`"
              />
            </td>

            <td v-if="props.hasQr" data-label="QR">
              <slot name="qr" :data="row" class="h-24 w-24"></slot>
            </td>

            <td
              v-for="(field, index) in displayFields"
              :key="`${row.id}-${field.value}`"
              :data-label="$t(field.name)"
              :class="index === 0 ? 'pl-stack-lead font-medium' : ''"
            >
              {{ row[field.value] ?? "—" }}
            </td>

            <td class="pl-stack-actions">
              <div class="flex flex-wrap items-center justify-end gap-1">
                <button
                  v-if="row.action_capabilities?.restore !== false && row.deleted && hasPermission('restore_' + props.model)"
                  type="button"
                  :disabled="actionProcessing || isProcessingAction"
                  class="ds-table-action"
                  @click="requestRowAction(row, 'restore')"
                >
                  {{ $t("gestlab.actions.restore") }}
                </button>

                <button
                  v-if="row.action_capabilities?.edit !== false && !row.deleted && hasPermission('edit_' + props.model)"
                  type="button"
                  class="ds-table-action"
                  @click="editRecord(row)"
                >
                  {{ $t("gestlab.actions.edit") }}
                </button>

                <Link
                  v-if="!row.deleted && hasPermission('add_' + props.model) && !row?.placed_analysis && row.links.collection_type === 'programmed'"
                  :href="row.links.place_analysis_path"
                  method="post"
                  as="button"
                  preserve-scroll
                  class="ds-table-action"
                >
                  {{ $t("gestlab.actions.insert") }}
                </Link>

                <button
                  v-if="row.action_capabilities?.delete !== false && !row.deleted && hasPermission('delete_' + props.model)"
                  type="button"
                  :disabled="actionProcessing || isProcessingAction"
                  class="ds-table-action ds-table-action-danger"
                  @click="requestRowAction(row, 'delete')"
                >
                  {{ $t("gestlab.actions.delete") }}
                </button>

                <a
                  v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_path"
                  :href="row.links.pdf_path"
                  target="_blank"
                  class="ds-table-action"
                >
                  PDF
                </a>

                <a
                  v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_collection_term"
                  :href="row.links.pdf_collection_term"
                  target="_blank"
                  class="ds-table-action"
                >
                  {{ $t("gestlab.general.labels.collection_term") }}
                </a>

                <slot name="actions" :id="row.id" :is-active="row.is_active" :data="row" />
              </div>
            </td>
          </tr>
        </tbody>
      </DataTable>

      <empty-state
        v-else
        class="m-4"
        :name="$t('gestlab.general.labels.no_records')"
        :description="canCreate ? $t('gestlab.general.labels.start_creating') : ''"
        :show-create="canCreate"
        @create-record="$emit('create-record')"
      />
    </section>

    <Pagination
      v-if="props.record.data.length"
      class="mt-6"
      :links="props.record.meta.links"
      :from="props.record.meta.from"
      :to="props.record.meta.to"
      :total="props.record.meta.total"
      :current_page="props.record.meta.current_page"
      :last_page="props.record.meta.last_page"
    />

    <select-action
      :record-ids="selectedRecordIds"
      :records="record.data"
      :actions="actions"
      :processing="actionProcessing || isProcessingAction"
      @execute="executeAction"
      @clear="clearSelection"
    />

    <!-- Confirm dialog -->
    <confirm-dialog
      v-if="showDeleteConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Sim"
      cancel="Não"
      @canceled="showDeleteConfirmation = false"
      @close="showDeleteConfirmation = false"
      @confirmed="confirmAction"
    />
  </div>
</template>
