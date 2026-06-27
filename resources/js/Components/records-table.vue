<script setup>
import { computed, reactive, ref, watch } from "vue";
import Pagination from "@/Components/pagination.vue";
import selectFilter from "@/Components/select-filter.vue";
import emptyState from "@/Components/empty-state.vue";
import datePicker from "@/Components/date-picker.vue";
import selectAction from "@/Components/select-action.vue";
import debounce from "lodash/debounce";
import { pickBy } from "lodash";
import confirmDialog from "@/Components/confirm-dialog.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import {
  ChevronDownIcon,
  MagnifyingGlassIcon,
  SquaresPlusIcon,
  AdjustmentsHorizontalIcon,
} from "@heroicons/vue/24/outline";
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
});

const emit = defineEmits(["execute-action", "slideover-on", "create-record"]);

const query = reactive({
  search: props.query?.search ?? "",
  filter: props.query?.filter ?? null,
  date: props.query?.date ?? null,
  page: null,
});

const actionId = ref(null);
const recordId = ref(null);
const recordUrl = ref(null);
const showDeleteConfirmation = ref(false);

const confirmationDialogTitle = computed(() => {
  return trans("gestlab.actions.confirmation_dialog_title." + actionId.value);
});

const confirmationDialogDescription = computed(() => {
  return trans(
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
  return Boolean(query.search || query.filter || query.date);
});

const totalRecords = computed(() => {
  return props.record?.meta?.total ?? props.record.data.length;
});

const resultSummary = computed(() => {
  if (!props.record?.meta) {
    return "";
  }

  const from = props.record.meta.from ?? 0;
  const to = props.record.meta.to ?? 0;
  const total = props.record.meta.total ?? props.record.data.length;

  return `${from}–${to} de ${total}`;
});

const filters = [
  {
    id: null,
    label: "gestlab.filter.filter",
  },
  {
    id: "trashed",
    label: "gestlab.filter.excluded",
  },
];

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

function clearQueryFilters() {
  query.search = "";
  query.filter = null;
  query.date = null;
}

function confirmAction() {
  processAction(actionId.value);
}

function processAction(currentActionId) {
  switch (currentActionId) {
    case "delete":
      router.get(
        recordUrl.value,
        { recordIds: [recordId.value] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId.value = null;
            recordId.value = null;
            recordUrl.value = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
      break;

    case "edit":
      router.get(
        recordUrl.value,
        { recordIds: [recordId.value] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId.value = null;
            recordId.value = null;
            recordUrl.value = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
      break;

    case "edit_slide":
      emit("slideover-on", recordId.value);
      showDeleteConfirmation.value = false;
      break;

    case "restore":
      router.get(
        recordUrl.value,
        { recordIds: [recordId.value] },
        {
          preserveState: false,
          preserveScroll: true,
          onSuccess: () => {
            showDeleteConfirmation.value = false;
            actionId.value = null;
            recordId.value = null;
            recordUrl.value = null;
          },
        },
      );
      showDeleteConfirmation.value = false;
      break;
  }
}

const masks = ref({
  modelValue: "YYYY-MM-DD",
  data: "YYYY-MM-DD",
});
</script>

<template>
  <div class="space-y-8">
    <section class="ds-command-surface">
      <div class="space-y-5 px-5 py-5 sm:px-7 sm:py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <p class="ds-kicker">
              {{ $t("gestlab.general.titles.search_and_filters") }}
            </p>
            <h2 class="ds-heading mt-2 text-lg">
              {{ $t("gestlab.general.titles.records_list") }}
            </h2>
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <span
              v-if="totalRecords"
              class="inline-flex items-center gap-1.5 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-1.5 text-xs font-bold text-[var(--ds-text-muted)]"
            >
              {{ resultSummary }}
            </span>
            <span
              v-if="selectedRecordIds.length"
              class="inline-flex items-center gap-1 rounded-full border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-3 py-1.5 text-xs font-semibold text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))]"
            >
              {{ selectedRecordIds.length }} {{ $t("gestlab.general.labels.selected_records") }}
            </span>
            <button
              v-if="props.createAction && hasPermission('add_' + props.model)"
              type="button"
              class="ds-button ds-button-primary"
              @click="$emit('create-record')"
            >
              <SquaresPlusIcon class="mr-2 h-5 w-5" />
              {{ $t("gestlab.general.buttons.new_record") }}
            </button>
          </div>
        </div>

        <div class="ds-command-toolbar grid gap-3 p-3 lg:grid-cols-[minmax(16rem,1fr)_auto] lg:items-center">
          <div class="relative min-w-0">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-[var(--ds-text-soft)]" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.general.search_input_placeholder')"
                class="ds-field pl-10"
              />
          </div>

          <div class="grid min-w-0 gap-2 sm:grid-cols-2 lg:flex lg:items-center lg:justify-end">
            <select-filter :filters="filters" @execute="changeFilter" />

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

            <button
              v-if="hasActiveQuery"
              type="button"
              class="ds-button ds-button-secondary sm:col-span-2 lg:col-span-1"
              @click="clearQueryFilters"
            >
              <AdjustmentsHorizontalIcon class="h-4 w-4" />
              <span>{{ $t("gestlab.general.buttons.clear") }}</span>
            </button>
          </div>
        </div>

        <div v-if="selectedRecordIds.length" class="rounded-[1.35rem] border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.7)] p-3 dark:border-[rgb(var(--primary-300-rgb)/0.18)] dark:bg-[rgb(var(--primary-500-rgb)/0.1)]">
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">
              {{ selectedRecordIds.length }} {{ $t("gestlab.general.labels.selected_records") }}
            </p>
            <select-action
              :record-ids="selectedRecordIds"
              :actions="actions"
              @execute="executeAction"
            />
          </div>
        </div>
      </div>
    </section>

    <section class="ds-table-shell">
      <div
        class="ds-table-summary px-5 py-4 sm:px-7"
      >
        <div>
          <p class="ds-heading text-sm">
            {{ $t("gestlab.general.titles.records_list") }}
          </p>
          <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">
            {{ totalRecords }} {{ $t("gestlab.general.labels.records") }}
          </p>
        </div>

        <button
          type="button"
          class="ds-button ds-button-secondary min-h-0 rounded-full px-3 py-1.5 text-xs md:hidden"
          @click="toggleSelectAll"
        >
          {{ allVisibleSelected ? $t("gestlab.general.labels.clear_selection") : $t("gestlab.general.buttons.select_all") }}
        </button>
      </div>

      <!-- Records -->
      <div v-if="record.data.length">
        <!-- Mobile cards -->
        <div class="divide-y divide-[var(--ds-border)] md:hidden">
          <article
            v-for="item in record.data"
            :key="item.id"
            class="space-y-4 px-5 py-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <input
                  v-model="item.selected"
                  type="checkbox"
                  class="ds-checkbox"
                />
                <div>
                  <p class="ds-heading text-sm">
                    {{ item[displayFields[0]?.value] ?? `#${item.id}` }}
                  </p>
                  <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
                    ID {{ item.id }}
                  </p>
                </div>
              </div>

              <slot v-if="props.hasQr" name="qr" :data="item" class="h-16 w-16" />
            </div>

            <dl class="grid grid-cols-1 gap-2">
              <div
                v-for="field in displayFields"
                :key="`${item.id}-${field.value}`"
                class="rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2"
              >
                <dt class="ds-table-heading">
                  {{ $t(field.name) }}
                </dt>
                <dd class="mt-1 break-words text-sm font-semibold text-[var(--ds-text)]">
                  {{ item[field.value] ?? "—" }}
                </dd>
              </div>
            </dl>

            <div class="flex flex-wrap items-center gap-2 border-t border-[var(--ds-border)] pt-3 text-sm font-medium">
              <button
                v-if="item.deleted && hasPermission('restore_' + props.model)"
                type="button"
                class="ds-table-action"
                @click="() => { recordId = item.id; actionId = 'restore'; recordUrl = item.links.restore_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.restore") }}
              </button>

              <button
                v-if="!item.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="ds-table-action"
                @click="() => { recordId = item.id; actionId = 'edit'; recordUrl = item.links.edit_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.edit") }}
              </button>

              <button
                v-if="!item.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="ds-table-action"
                @click="() => { recordId = item; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.edit") }}
              </button>

              <Link
                v-if="!item.deleted && hasPermission('add_' + props.model) && !item?.placed_analysis && item.links.collection_type === 'programmed'"
                :href="item.links.place_analysis_path"
                class="ds-table-action"
              >
                {{ $t("gestlab.actions.insert") }}
              </Link>

              <button
                v-if="!item.deleted && hasPermission('delete_' + props.model)"
                type="button"
                class="ds-table-action ds-table-action-danger"
                @click="() => { recordId = item.id; actionId = 'delete'; recordUrl = item.links.delete_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.delete") }}
              </button>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_path"
                :href="item.links.pdf_path"
                target="_blank"
                class="ds-table-action"
              >
                PDF
              </a>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_collection_term"
                :href="item.links.pdf_collection_term"
                target="_blank"
                class="ds-table-action"
              >
                {{ $t("gestlab.general.labels.collection_term") }}
              </a>

              <slot name="actions" :id="item.id" :is-active="item.is_active" :data="item" />
            </div>
          </article>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="min-w-full">
            <thead class="ds-table-head">
              <tr>
                <th class="py-4 pl-7 pr-3 text-left">
                  <input
                    :checked="allVisibleSelected"
                    type="checkbox"
                    class="ds-checkbox"
                    @change="toggleSelectAll"
                  />
                </th>
                <th v-if="props.hasQr" class="ds-table-heading px-4 py-4 text-left"></th>
                <th
                  v-for="field in displayFields"
                  :key="field.value"
                  class="ds-table-heading px-4 py-4 text-left"
                >
                  {{ $t(field.name) }}
                </th>
                <th class="ds-table-heading px-7 py-4 text-right">
                  {{ $t("gestlab.actions.action") }}
                </th>
              </tr>
            </thead>

            <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
              <tr
                v-for="row in record.data"
                :key="row.id"
                class="ds-table-row"
              >
                <td class="py-5 pl-7 pr-3">
                  <input
                    v-model="row.selected"
                    type="checkbox"
                    class="ds-checkbox"
                  />
                </td>

                <td v-if="props.hasQr" class="px-4 py-5">
                  <slot name="qr" :data="row" class="h-24 w-24"></slot>
                </td>

                <td
                  v-for="field in displayFields"
                  :key="`${row.id}-${field.value}`"
                  class="ds-table-cell whitespace-nowrap px-4 py-5"
                >
                  {{ row[field.value] ?? "—" }}
                </td>

                <td class="px-7 py-5">
                  <div class="flex items-center justify-end gap-1 text-sm font-medium">
                    <button
                      v-if="row.deleted && hasPermission('restore_' + props.model)"
                      type="button"
                      class="ds-table-action"
                      @click="() => { recordId = row.id; actionId = 'restore'; recordUrl = row.links.restore_path; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.restore") }}
                    </button>

                    <button
                      v-if="!row.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="ds-table-action"
                      @click="() => { recordId = row.id; actionId = 'edit'; recordUrl = row.links.edit_path; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.edit") }}
                    </button>

                    <button
                      v-if="!row.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="ds-table-action"
                      @click="() => { recordId = row; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.edit") }}
                    </button>

                    <Link
                      v-if="!row.deleted && hasPermission('add_' + props.model) && !row?.placed_analysis && row.links.collection_type === 'programmed'"
                      :href="row.links.place_analysis_path"
                      class="ds-table-action"
                    >
                      {{ $t("gestlab.actions.insert") }}
                    </Link>

                    <button
                      v-if="!row.deleted && hasPermission('delete_' + props.model)"
                      type="button"
                      class="ds-table-action ds-table-action-danger"
                      @click="() => { recordId = row.id; actionId = 'delete'; recordUrl = row.links.delete_path; showDeleteConfirmation = true; }"
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
          </table>
        </div>
      </div>

      <!-- Empty state -->
      <empty-state
        v-else
        class="px-4 py-12"
        :name="$t('gestlab.general.labels.no_records')"
        :description="$t('gestlab.general.labels.start_creating')"
        @create-record="$emit('create-record')"
      />
    </section>

    <!-- Pagination -->
    <div v-if="props.record.data.length" class="flex justify-center">
      <Pagination
        :links="props.record.meta.links"
        :from="props.record.meta.from"
        :to="props.record.meta.to"
        :total="props.record.meta.total"
        :current_page="props.record.meta.current_page"
        :last_page="props.record.meta.last_page"
      />
    </div>

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
