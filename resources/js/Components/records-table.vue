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
    <!-- Unified card: toolbar + table -->
    <section class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgb(20_61_55/0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <!-- Toolbar -->
      <div class="space-y-5 px-5 py-5 sm:px-7 sm:py-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#6b7b74] dark:text-[#83978d]">
              Pesquisa e filtros
            </p>
            <h2 class="mt-2 text-lg font-black tracking-tight text-[#15231f] dark:text-[#f7f1e7]">
              {{ $t("gestlab.general.titles.records_list") }}
            </h2>
          </div>

          <div class="flex flex-wrap items-center gap-2 xl:justify-end">
            <span
              v-if="totalRecords"
              class="inline-flex items-center gap-1.5 rounded-full border border-[#ded3bf] bg-[#f7f1e7] px-3 py-1.5 text-xs font-semibold text-[#5f6f68] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#a9bbb4]"
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
              class="inline-flex items-center justify-center rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-3 text-sm font-semibold text-white shadow-[0_12px_30px_rgb(var(--primary-900-rgb)/0.14)] transition-colors duration-150 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus-visible:ring-2 focus-visible:ring-[rgb(var(--primary-500-rgb)/0.32)] focus-visible:ring-offset-2 focus-visible:ring-offset-[#fffdf7] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))] dark:focus-visible:ring-offset-[#07110f]"
              @click="$emit('create-record')"
            >
              <SquaresPlusIcon class="mr-2 h-5 w-5" />
              {{ $t("gestlab.general.buttons.new_record") }}
            </button>
          </div>
        </div>

        <div class="grid gap-3 xl:grid-cols-[minmax(18rem,1.3fr)_minmax(13rem,0.6fr)_minmax(24rem,1.4fr)_auto] xl:items-center">
          <div class="relative min-w-0">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.general.search_input_placeholder')"
                class="block w-full rounded-2xl border-[#d8cbb8] bg-white py-3 pl-10 pr-3 text-sm font-medium text-[#15231f] placeholder:text-[#8d9b94] shadow-sm transition focus:border-[rgb(var(--primary-500-rgb))] focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
              />
          </div>

          <select-filter :filters="filters" @execute="changeFilter" />

          <date-picker
            v-model.range.string="query.date"
            locale="pt-PT"
            color="blue"
            mode="date"
            range
            :input-debounce="500"
            :masks="masks"
            @update:model-value="updateRange"
          />

          <button
            v-if="hasActiveQuery"
            type="button"
            class="inline-flex h-12 items-center justify-center gap-2 rounded-2xl border border-[#ded3bf] bg-[#fffdf7] px-4 text-sm font-semibold text-[#5f6f68] transition-colors duration-150 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#a9bbb4] dark:hover:bg-[#16342e] dark:hover:text-[#f7f1e7]"
            @click="clearQueryFilters"
          >
            <AdjustmentsHorizontalIcon class="h-4 w-4" />
            <span>{{ $t("gestlab.general.buttons.clear") }}</span>
          </button>
        </div>

        <div v-if="selectedRecordIds.length" class="rounded-[1.5rem] border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.7)] p-3 dark:border-[rgb(var(--primary-300-rgb)/0.18)] dark:bg-[rgb(var(--primary-500-rgb)/0.1)]">
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

      <!-- Divider + table header -->
      <div
        class="flex items-center justify-between border-y border-[#ded3bf] bg-[#f7f1e7]/85 px-5 py-4 dark:border-[#25443c] dark:bg-[#10231f]/80 sm:px-7"
      >
        <div>
          <p class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
            {{ $t("gestlab.general.titles.records_list") }}
          </p>
          <p class="mt-0.5 text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
            {{ totalRecords }} {{ $t("gestlab.general.labels.records") }}
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center rounded-full border border-[#d8cbb8] bg-white px-3 py-1.5 text-xs font-semibold text-[#5f6f68] transition hover:bg-[#fffaf0] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] md:hidden"
          @click="toggleSelectAll"
        >
          {{ allVisibleSelected ? $t("gestlab.general.labels.clear_selection") : $t("gestlab.general.buttons.select_all") }}
        </button>
      </div>

      <!-- Records -->
      <div v-if="record.data.length">
        <!-- Mobile cards -->
        <div class="divide-y divide-[#ded3bf] dark:divide-[#25443c] md:hidden">
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
                  class="h-4 w-4 rounded border-[#d8cbb8] text-[rgb(var(--primary-700-rgb))] focus:ring-[rgb(var(--primary-500-rgb))] dark:border-[#315149] dark:bg-[#07110f]"
                />
                <div>
                  <p class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
                    {{ item[displayFields[0]?.value] ?? `#${item.id}` }}
                  </p>
                  <p class="text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
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
                class="rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/70 px-3 py-2 dark:border-[#25443c] dark:bg-[#10231f]/70"
              >
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-[#73827b] dark:text-[#8ea49b]">
                  {{ $t(field.name) }}
                </dt>
                <dd class="mt-1 break-words text-sm font-medium text-[#15231f] dark:text-[#f7f1e7]">
                  {{ item[field.value] ?? "—" }}
                </dd>
              </div>
            </dl>

            <div class="flex flex-wrap items-center gap-2 border-t border-[#ded3bf] pt-3 text-sm font-medium dark:border-[#25443c]">
              <button
                v-if="item.deleted && hasPermission('restore_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
                @click="() => { recordId = item.id; actionId = 'restore'; recordUrl = item.links.restore_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.restore") }}
              </button>

              <button
                v-if="!item.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
                @click="() => { recordId = item.id; actionId = 'edit'; recordUrl = item.links.edit_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.edit") }}
              </button>

              <button
                v-if="!item.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
                @click="() => { recordId = item; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.edit") }}
              </button>

              <Link
                v-if="!item.deleted && hasPermission('add_' + props.model) && !item?.placed_analysis && item.links.collection_type === 'programmed'"
                :href="item.links.place_analysis_path"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
              >
                {{ $t("gestlab.actions.insert") }}
              </Link>

              <button
                v-if="!item.deleted && hasPermission('delete_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-rose-600 transition-colors duration-150 hover:bg-rose-50 hover:text-rose-800 dark:text-rose-300 dark:hover:bg-rose-500/10"
                @click="() => { recordId = item.id; actionId = 'delete'; recordUrl = item.links.delete_path; showDeleteConfirmation = true; }"
              >
                {{ $t("gestlab.actions.delete") }}
              </button>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_path"
                :href="item.links.pdf_path"
                target="_blank"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
              >
                PDF
              </a>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_collection_term"
                :href="item.links.pdf_collection_term"
                target="_blank"
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-sm font-semibold text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)]"
              >
                {{ $t("gestlab.general.labels.collection_term") }}
              </a>

              <slot name="actions" :id="item.id" :is-active="item.is_active" :data="item" />
            </div>
          </article>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="min-w-full divide-y divide-[#ded3bf] dark:divide-[#25443c]">
            <thead>
              <tr class="bg-[#f7f1e7]/85 dark:bg-[#10231f]/85">
                <th class="py-4 pl-7 pr-3 text-left">
                  <input
                    :checked="allVisibleSelected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-[#d8cbb8] text-[rgb(var(--primary-700-rgb))] focus:ring-[rgb(var(--primary-500-rgb))] dark:border-[#315149] dark:bg-[#07110f]"
                    @change="toggleSelectAll"
                  />
                </th>
                <th v-if="props.hasQr" class="px-4 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]"></th>
                <th
                  v-for="field in displayFields"
                  :key="field.value"
                  class="px-4 py-4 text-left text-[11px] font-black uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]"
                >
                  {{ $t(field.name) }}
                </th>
                <th class="px-7 py-4 text-right text-[11px] font-black uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]">
                  {{ $t("gestlab.actions.action") }}
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-[#ded3bf] dark:divide-[#25443c]">
              <tr
                v-for="(row, idx) in record.data"
                :key="row.id"
                :class="idx % 2 === 0 ? 'bg-[#fffdf7] dark:bg-[#07110f]' : 'bg-[#f7f1e7]/45 dark:bg-[#10231f]/35'"
                class="transition-colors duration-100 hover:bg-[rgb(var(--primary-50-rgb)/0.5)] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.1)]"
              >
                <td class="py-5 pl-7 pr-3">
                  <input
                    v-model="row.selected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-[#d8cbb8] text-[rgb(var(--primary-700-rgb))] focus:ring-[rgb(var(--primary-500-rgb))] dark:border-[#315149] dark:bg-[#07110f]"
                  />
                </td>

                <td v-if="props.hasQr" class="px-4 py-5">
                  <slot name="qr" :data="row" class="h-24 w-24"></slot>
                </td>

                <td
                  v-for="field in displayFields"
                  :key="`${row.id}-${field.value}`"
                  class="whitespace-nowrap px-4 py-5 text-sm font-medium text-[#31413b] dark:text-[#d7e2dd]"
                >
                  {{ row[field.value] ?? "—" }}
                </td>

                <td class="px-7 py-5">
                  <div class="flex items-center justify-end gap-1 text-sm font-medium">
                    <button
                      v-if="row.deleted && hasPermission('restore_' + props.model)"
                      type="button"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
                      @click="() => { recordId = row.id; actionId = 'restore'; recordUrl = row.links.restore_path; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.restore") }}
                    </button>

                    <button
                      v-if="!row.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
                      @click="() => { recordId = row.id; actionId = 'edit'; recordUrl = row.links.edit_path; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.edit") }}
                    </button>

                    <button
                      v-if="!row.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
                      @click="() => { recordId = row; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.edit") }}
                    </button>

                    <Link
                      v-if="!row.deleted && hasPermission('add_' + props.model) && !row?.placed_analysis && row.links.collection_type === 'programmed'"
                      :href="row.links.place_analysis_path"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
                    >
                      {{ $t("gestlab.actions.insert") }}
                    </Link>

                    <button
                      v-if="!row.deleted && hasPermission('delete_' + props.model)"
                      type="button"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-rose-50 hover:text-rose-700 dark:text-[#a9bbb4] dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                      @click="() => { recordId = row.id; actionId = 'delete'; recordUrl = row.links.delete_path; showDeleteConfirmation = true; }"
                    >
                      {{ $t("gestlab.actions.delete") }}
                    </button>

                    <a
                      v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_path"
                      :href="row.links.pdf_path"
                      target="_blank"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
                    >
                      PDF
                    </a>

                    <a
                      v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_collection_term"
                      :href="row.links.pdf_collection_term"
                      target="_blank"
                      class="rounded-full px-2.5 py-1.5 text-[#5f6f68] transition-colors duration-150 hover:bg-[rgb(var(--primary-50-rgb)/0.8)] hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#a9bbb4] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
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
