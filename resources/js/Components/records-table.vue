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
  record: Object,
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
    default: [],
  },
  actions: Array,
  model: {
    type: String,
    default: "",
  },
  abilities: {
    type: Array,
    default: [],
  },
  query: Object,
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
  <div class="space-y-5">
    <!-- Unified card: toolbar + table -->
    <section class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <!-- Toolbar -->
      <div class="px-5 py-4 sm:px-6 space-y-4 xl:space-y-0">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
          <!-- Left: search & filters -->
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:flex-1">
            <div class="relative w-full lg:max-w-xs">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400 dark:text-gray-500" />
              </div>
              <input
                v-model="query.search"
                type="search"
                :placeholder="$t('gestlab.general.search_input_placeholder')"
                class="block w-full rounded-xl border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 py-2.5 pl-10 pr-3 text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20"
              />
            </div>

            <div class="flex flex-wrap items-center gap-2">
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

              <select-action
                :record-ids="selectedRecordIds"
                :actions="actions"
                @execute="executeAction"
              />

              <button
                v-if="hasActiveQuery"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-300 dark:hover:bg-gray-800 transition-colors duration-150"
                @click="clearQueryFilters"
              >
                <AdjustmentsHorizontalIcon class="h-4 w-4" />
                <span class="sm:hidden">Limpar</span>
              </button>
            </div>
          </div>

          <!-- Right: create button -->
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end shrink-0">
            <span
              v-if="totalRecords"
              class="inline-flex items-center gap-1.5 rounded-full bg-gray-50 dark:bg-gray-800 px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300"
            >
              {{ resultSummary }}
            </span>
            <span
              v-if="selectedRecordIds.length"
              class="inline-flex items-center gap-1 rounded-full bg-primary-50 dark:bg-primary-500/10 px-3 py-1 text-xs font-semibold text-primary-700 dark:text-primary-300"
            >
              {{ selectedRecordIds.length }} seleccionados
            </span>

            <button
              v-if="props.createAction && hasPermission('add_' + props.model)"
              type="button"
              class="inline-flex items-center justify-center rounded-xl bg-primary-800 dark:bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150 hover:bg-primary-700 dark:hover:bg-primary-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2"
              @click="$emit('create-record')"
            >
              <SquaresPlusIcon class="mr-2 h-5 w-5" />
              {{ $t("gestlab.general.buttons.new_record") }}
            </button>
          </div>
        </div>
      </div>

      <!-- Divider + table header -->
      <div
        class="flex items-center justify-between border-y border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/50 px-4 py-2.5 sm:px-6"
      >
        <div>
          <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            {{ $t("gestlab.general.titles.records_list") }}
          </p>
          <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            {{ totalRecords }} {{ $t("gestlab.general.labels.records") }}
          </p>
        </div>

        <button
          type="button"
          class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 transition hover:border-gray-300 dark:hover:border-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700 md:hidden"
          @click="toggleSelectAll"
        >
          {{ allVisibleSelected ? "Limpar selecção" : "Seleccionar todos" }}
        </button>
      </div>

      <!-- Records -->
      <div v-if="record.data.length">
        <!-- Mobile cards -->
        <div class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
          <article
            v-for="item in record.data"
            :key="item.id"
            class="space-y-4 px-4 py-4"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <input
                  v-model="item.selected"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                />
                <div>
                  <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {{ item[props.fields[0]?.value] ?? `#${item.id}` }}
                  </p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">
                    ID {{ item.id }}
                  </p>
                </div>
              </div>

              <slot v-if="props.hasQr" name="qr" :data="item" class="h-16 w-16" />
            </div>

            <dl class="grid grid-cols-1 gap-2">
              <div
                v-for="field in props.fields"
                :key="`${item.id}-${field.value}`"
                class="rounded-xl bg-gray-50 dark:bg-gray-800 px-3 py-2"
              >
                <dt class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                  {{ field.name }}
                </dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 break-words">
                  {{ item[field.value] ?? "—" }}
                </dd>
              </div>
            </dl>

            <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 dark:border-gray-700 pt-3 text-sm font-medium">
              <button
                v-if="item.deleted && hasPermission('restore_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
                @click="() => { recordId = item.id; actionId = 'restore'; recordUrl = item.links.restore_path; showDeleteConfirmation = true; }"
              >
                Restaurar
              </button>

              <button
                v-if="!item.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
                @click="() => { recordId = item.id; actionId = 'edit'; recordUrl = item.links.edit_path; showDeleteConfirmation = true; }"
              >
                Editar
              </button>

              <button
                v-if="!item.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
                @click="() => { recordId = item; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
              >
                Editar
              </button>

              <Link
                v-if="!item.deleted && hasPermission('add_' + props.model) && !item?.placed_analysis && item.links.collection_type === 'programmed'"
                :href="item.links.place_analysis_path"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
              >
                Analisar
              </Link>

              <button
                v-if="!item.deleted && hasPermission('delete_' + props.model)"
                type="button"
                class="inline-flex items-center gap-1 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors duration-150"
                @click="() => { recordId = item.id; actionId = 'delete'; recordUrl = item.links.delete_path; showDeleteConfirmation = true; }"
              >
                Eliminar
              </button>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_path"
                :href="item.links.pdf_path"
                target="_blank"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
              >
                PDF
              </a>

              <a
                v-if="!item.deleted && hasPermission('view_' + props.model) && item?.links?.pdf_collection_term"
                :href="item.links.pdf_collection_term"
                target="_blank"
                class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 transition-colors duration-150"
              >
                Termo
              </a>

              <slot name="actions" :id="item.id" :is-active="item.is_active" :data="item" />
            </div>
          </article>
        </div>

        <!-- Desktop table -->
        <div class="hidden md:block overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr class="bg-gray-50 dark:bg-gray-800">
                <th class="pl-6 pr-3 py-3 text-left">
                  <input
                    :checked="allVisibleSelected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                    @change="toggleSelectAll"
                  />
                </th>
                <th v-if="props.hasQr" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"></th>
                <th
                  v-for="field in props.fields"
                  :key="field.value"
                  class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400"
                >
                  {{ field.name }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                  {{ $t("gestlab.general.buttons.action") }}
                </th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <tr
                v-for="(row, idx) in record.data"
                :key="row.id"
                :class="idx % 2 === 0 ? 'bg-white dark:bg-gray-900' : 'bg-gray-50/40 dark:bg-gray-800/20'"
                class="transition-colors duration-100 hover:bg-primary-50/40 dark:hover:bg-gray-700/50"
              >
                <td class="pl-6 pr-3 py-3.5">
                  <input
                    v-model="row.selected"
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800"
                  />
                </td>

                <td v-if="props.hasQr" class="px-4 py-3.5">
                  <slot name="qr" :data="row" class="h-24 w-24"></slot>
                </td>

                <td
                  v-for="field in props.fields"
                  :key="`${row.id}-${field.value}`"
                  class="whitespace-nowrap px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300"
                >
                  {{ row[field.value] ?? "—" }}
                </td>

                <td class="px-6 py-3.5">
                  <div class="flex items-center justify-end gap-1 text-sm font-medium">
                    <button
                      v-if="row.deleted && hasPermission('restore_' + props.model)"
                      type="button"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                      @click="() => { recordId = row.id; actionId = 'restore'; recordUrl = row.links.restore_path; showDeleteConfirmation = true; }"
                    >
                      Restaurar
                    </button>

                    <button
                      v-if="!row.deleted && !props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                      @click="() => { recordId = row.id; actionId = 'edit'; recordUrl = row.links.edit_path; showDeleteConfirmation = true; }"
                    >
                      Editar
                    </button>

                    <button
                      v-if="!row.deleted && props.slideOverEdit && hasPermission('edit_' + props.model)"
                      type="button"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                      @click="() => { recordId = row; actionId = 'edit_slide'; showDeleteConfirmation = true; }"
                    >
                      Editar
                    </button>

                    <Link
                      v-if="!row.deleted && hasPermission('add_' + props.model) && !row?.placed_analysis && row.links.collection_type === 'programmed'"
                      :href="row.links.place_analysis_path"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                    >
                      Analisar
                    </Link>

                    <button
                      v-if="!row.deleted && hasPermission('delete_' + props.model)"
                      type="button"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-500/10 transition-colors duration-150"
                      @click="() => { recordId = row.id; actionId = 'delete'; recordUrl = row.links.delete_path; showDeleteConfirmation = true; }"
                    >
                      Eliminar
                    </button>

                    <a
                      v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_path"
                      :href="row.links.pdf_path"
                      target="_blank"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                    >
                      PDF
                    </a>

                    <a
                      v-if="!row.deleted && hasPermission('view_' + props.model) && row?.links?.pdf_collection_term"
                      :href="row.links.pdf_collection_term"
                      target="_blank"
                      class="rounded-lg px-2.5 py-1.5 text-gray-500 dark:text-gray-400 hover:text-primary-700 hover:bg-primary-50 dark:hover:text-primary-400 dark:hover:bg-primary-500/10 transition-colors duration-150"
                    >
                      Termo
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
        name="Sem registos para apresentar"
        description="Comece por adicionar um novo registo clicando no botão acima."
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
