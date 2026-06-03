<script setup>
import confirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router } from "@inertiajs/vue3";
import {
  ArrowPathRoundedSquareIcon,
  DocumentCheckIcon,
  EyeIcon,
  GlobeAltIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

const props = defineProps({
  record: Object,
  fields: Array,
  model: String,
  abilities: Array,
  query: Object,
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

defineOptions({
  layout: Layout,
});

const actionId = ref(null);
const showBulkConfirmation = ref(false);

const actions = [
  {
    id: null,
    label: "gestlab.actions.bulk_actions_text",
  },
  {
    id: "delete",
    label: "gestlab.actions.delete",
  },
  {
    id: "restore",
    label: "gestlab.actions.restore",
  },
];

const totalRecords = computed(() => {
  return props.record?.meta?.total ?? props.record?.data?.length ?? 0;
});

const visibleRecords = computed(() => {
  return props.record?.data?.length ?? 0;
});

const selectedRecordIds = computed(() => {
  return props.record?.data
    ?.filter((record) => record.selected)
    .map((record) => record.id) ?? [];
});

const selectedCount = computed(() => selectedRecordIds.value.length);

const confirmationDialogTitle = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`);
});

const confirmationDialogDescription = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`);
});

function createRecord() {
  router.get(route("exportcertificates.create"));
}

function prepareBulkAction(selectedAction) {
  actionId.value = selectedAction;
  showBulkConfirmation.value = true;
}

function resetBulkAction() {
  showBulkConfirmation.value = false;
  actionId.value = null;
}

function confirmAction() {
  executeBulkAction(actionId.value);
}

function executeBulkAction(selectedAction) {
  if (!selectedRecordIds.value.length) {
    resetBulkAction();

    return;
  }

  const routeName = {
    delete: "exportcertificates.destroy",
    restore: "exportcertificates.restore",
  }[selectedAction];

  if (!routeName) {
    resetBulkAction();

    return;
  }

  router.get(
    route(routeName),
    {
      recordIds: selectedRecordIds.value,
    },
    {
      preserveState: false,
      preserveScroll: true,
      onFinish: resetBulkAction,
    },
  );
}
</script>

<template>
  <div class="export-certificates-index space-y-6" :class="commercialDocumentThemeClasses">
    <section class="relative isolate overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] p-5 shadow-[0_22px_70px_rgb(20_61_55/0.08)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:p-7">
      <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-200-rgb)/0.35),transparent_32%),linear-gradient(135deg,rgb(255_253_247/0.98),rgb(247_241_231/0.88))] dark:bg-[radial-gradient(circle_at_top_left,rgb(var(--primary-500-rgb)/0.20),transparent_34%),linear-gradient(135deg,rgb(7_17_15/0.98),rgb(16_35_31/0.92))]" />

      <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
        <div class="max-w-3xl">
          <div class="inline-flex items-center gap-2 rounded-full border border-[#ded3bf] bg-white/80 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[rgb(var(--primary-800-rgb))] shadow-sm dark:border-[#315149] dark:bg-[#10231f]/80 dark:text-[rgb(var(--primary-200-rgb))]">
            <GlobeAltIcon class="h-4 w-4" />
            {{ $t("gestlab.general.labels.export_certificates.module_label") }}
          </div>
          <h1 class="mt-4 text-2xl font-semibold tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-3xl">
            {{ $t("gestlab.general.labels.export_certificates.page_title") }}
          </h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-[#5f6f68] dark:text-[#a9bbb4]">
            {{ $t("gestlab.general.labels.export_certificates.overview_description") }}
          </p>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 xl:min-w-[32rem]">
          <div class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-[#315149] dark:bg-[#10231f]/80">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]">{{ $t("gestlab.general.labels.records") }}</p>
            <p class="mt-2 text-2xl font-semibold text-[#15231f] dark:text-[#f7f1e7]">{{ totalRecords }}</p>
          </div>
          <div class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-[#315149] dark:bg-[#10231f]/80">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]">{{ $t("gestlab.general.labels.export_certificates.visible_records") }}</p>
            <p class="mt-2 text-2xl font-semibold text-[#15231f] dark:text-[#f7f1e7]">{{ visibleRecords }}</p>
          </div>
          <div class="rounded-3xl border border-white/70 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-[#315149] dark:bg-[#10231f]/80">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#73827b] dark:text-[#8ea49b]">{{ $t("gestlab.general.labels.export_certificates.selection") }}</p>
            <p class="mt-2 text-2xl font-semibold text-[#15231f] dark:text-[#f7f1e7]">{{ selectedCount }}</p>
          </div>
        </div>
      </div>
    </section>

    <RecordsTable
      :record="props.record"
      :model="props.model"
      :abilities="props.abilities"
      :fields="props.fields"
      :slide-over-edit="props.slideOverEdit"
      :query="props.query"
      :actions="actions"
      @execute-action="prepareBulkAction"
      @create-record="createRecord"
    >
      <template #actions="{ data }">
        <div class="flex items-center justify-end gap-2">
          <Link
            v-if="!data.deleted"
            :href="route('exportcertificates.show', { exportcertificate: data.id })"
            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#ded3bf] bg-white text-[#5f6f68] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[rgb(var(--primary-50-rgb)/0.9)] hover:text-[rgb(var(--primary-800-rgb))] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:border-[rgb(var(--primary-400-rgb)/0.45)] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
            :title="$t('gestlab.general.buttons.show')"
          >
            <EyeIcon class="h-4 w-4" />
          </Link>
          <span
            v-if="data.deleted"
            class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-200"
          >
            <ArrowPathRoundedSquareIcon class="h-3.5 w-3.5" />
            {{ $t("gestlab.general.labels.export_certificates.archived") }}
          </span>
          <span
            v-else
            class="hidden items-center gap-1 rounded-full border border-[rgb(var(--primary-200-rgb)/0.85)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-2.5 py-1 text-xs font-semibold text-[rgb(var(--primary-800-rgb))] dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))] lg:inline-flex"
          >
            <DocumentCheckIcon class="h-3.5 w-3.5" />
            {{ $t("gestlab.general.labels.export_certificates.certificate_status") }}
          </span>
        </div>
      </template>
    </RecordsTable>

    <confirm-dialog
      v-if="showBulkConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :confirm="trans('gestlab.general.buttons.yes')"
      :cancel="trans('gestlab.general.buttons.no')"
      @canceled="resetBulkAction"
      @close="resetBulkAction"
      @confirmed="confirmAction"
    />
  </div>
</template>
