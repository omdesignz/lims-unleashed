<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import RecordsTable from "@/Components/records-table.vue";
import confirmDialog from "@/Components/confirm-dialog.vue";
import slideOver from "@/Components/slide-over.vue";
import combobox from "@/Components/combobox-enhanced.vue";
import { computed, ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { EyeIcon } from "@heroicons/vue/24/outline";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";

const props = defineProps({
  record: {
    type: Object,
    default: () => ({ data: [], meta: {} }),
  },
  fields: {
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
  slideOverEdit: {
    type: Boolean,
    default: false,
  },
});

defineOptions({
  layout: Layout,
});

const form = useForm({
  obs: "",
  customer_id: null,
  status: true,
  warehouse_id: null,
  invoice_id: null,
  code: "",
  cl_id: null,
  id: null,
});

const actionId = ref(null);
const openslideover = ref(false);
const showDeleteConfirmation = ref(false);

const visibleRecords = computed(() => props.record?.data ?? []);
const totalRecords = computed(
  () => props.record?.meta?.total ?? visibleRecords.value.length,
);
const validatedRecords = computed(
  () => visibleRecords.value.filter((record) => Boolean(record.validated_at)).length,
);
const pendingRecords = computed(
  () => visibleRecords.value.filter((record) => !record.validated_at && !record.deleted).length,
);

const registryMetrics = computed(() => [
  {
    label: "Registos no arquivo",
    value: totalRecords.value,
    note: "total pesquisavel",
  },
  {
    label: "Validados nesta pagina",
    value: validatedRecords.value,
    note: "prontos para distribuicao",
  },
  {
    label: "Pendentes nesta pagina",
    value: pendingRecords.value,
    note: "a aguardar libertacao",
  },
]);

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

const slideOverDescription = computed(() => {
  const translationKey = form.id
    ? "gestlab.slideover.updating.description"
    : "gestlab.slideover.creating.description";

  return `${trans(translationKey)}${form.code || ""}`;
});

const slideOverTitle = computed(() => {
  return trans(
    form.id
      ? "gestlab.slideover.updating.title"
      : "gestlab.slideover.creating.title",
  );
});

const confirmationDialogTitle = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`);
});

const confirmationDialogDescription = computed(() => {
  return trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`);
});

function optionFromValue(value, label) {
  if (!value) {
    return null;
  }

  return {
    value,
    label: label || String(value),
  };
}

function closeSlideover() {
  openslideover.value = false;
  form.clearErrors();
  form.reset();
}

function openSlideoverWithData(data) {
  openslideover.value = true;
  form.id = data.id;
  form.obs = data.obs ?? "";
  form.code = data.code ?? "";
  form.status = Boolean(data.status);
  form.customer_id = optionFromValue(data.customer_id, data.customer);
  form.warehouse_id = optionFromValue(data.warehouse_id, data.warehouse);
  form.cl_id = optionFromValue(data.cl_id, data.lab_code);
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeSlideover,
  };

  if (form.id) {
    form.put(
      route("qualitycertificates.update", { certificate: form.id }),
      options,
    );
    return;
  }

  form.post(route("qualitycertificates.store"), options);
}

function prepareBulkAction(selectedActionId) {
  actionId.value = selectedActionId;
  showDeleteConfirmation.value = true;
}

function resetBulkAction() {
  showDeleteConfirmation.value = false;
  actionId.value = null;
}

function executeAction(selectedActionId) {
  const recordIds = visibleRecords.value
    .filter((record) => record.selected)
    .map((record) => record.id);

  if (!recordIds.length) {
    resetBulkAction();
    return;
  }

  const routeName = selectedActionId === "restore"
    ? "qualitycertificates.restore"
    : "qualitycertificates.destroy";

  router.get(
    route(routeName),
    { recordIds },
    {
      preserveState: false,
      preserveScroll: true,
      onFinish: resetBulkAction,
    },
  );
}

function confirmAction() {
  executeAction(actionId.value);
}

function loadCustomers(query, setOptions) {
  return loadSelectOptions(
    "/customers/getCustomer",
    query,
    setOptions,
    optionMappers.name,
  );
}

function loadWarehouses(query, setOptions) {
  return loadSelectOptions(
    "/warehouses/getWarehouse",
    query,
    setOptions,
    optionMappers.address,
  );
}

function loadLabCodes(query, setOptions) {
  return loadSelectOptions(
    "/labcodes/getCode",
    query,
    setOptions,
    optionMappers.code,
  );
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="max-w-3xl">
          <p class="ds-kicker">Controlo documental</p>
          <h1 class="ds-heading mt-2 text-2xl">Certificados de qualidade</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm">
            Registo mestre dos certificados emitidos, com estado de validacao,
            rastreabilidade laboratorial e acesso direto ao dossier final.
          </p>
        </div>

        <div class="lims-status-strip flex items-center gap-3 px-4 py-3">
          <span class="lims-status-dot lims-status-dot-instrument" />
          <div>
            <p class="text-xs font-bold text-[var(--ds-text)]">Arquivo de emissao</p>
            <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">
              Pesquisa, validacao e distribuicao
            </p>
          </div>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div
          v-for="metric in registryMetrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-5 py-4 last:border-b-0 sm:border-b-0"
        >
          <dt class="ds-table-heading">{{ metric.label }}</dt>
          <dd class="mt-2 flex items-baseline gap-2">
            <span class="ds-heading text-xl">{{ metric.value }}</span>
            <span class="text-xs font-semibold text-[var(--ds-text-soft)]">
              {{ metric.note }}
            </span>
          </dd>
        </div>
      </dl>
    </section>

    <RecordsTable
      :create-action="false"
      :model="props.model"
      :abilities="props.abilities"
      :record="props.record"
      :fields="props.fields"
      :slide-over-edit="props.slideOverEdit"
      :query="props.query"
      :actions="actions"
      @execute-action="prepareBulkAction"
      @slideover-on="openSlideoverWithData"
    >
      <template #actions="{ id }">
        <Link
          :href="route('qualitycertificates.show', { certificate: id })"
          class="ds-table-action"
          title="Abrir dossier do certificado"
        >
          <EyeIcon class="h-4 w-4" />
          <span class="sr-only">Abrir dossier do certificado</span>
        </Link>
      </template>
    </RecordsTable>

    <slide-over
      v-if="openslideover"
      :title="slideOverTitle"
      :description="slideOverDescription"
      @close="closeSlideover"
    >
      <template #content>
        <div class="space-y-0">
          <div class="ds-command-surface m-5 p-5 sm:m-6">
            <p class="ds-kicker">Dados de emissao</p>
            <h2 class="ds-heading mt-2 text-base">Identificacao do certificado</h2>
            <p class="ds-copy mt-1 text-sm">
              Confirme o cliente, local, codigo laboratorial e disponibilidade
              antes de guardar o registo.
            </p>
          </div>

          <div class="grid gap-5 border-t border-[var(--ds-border)] px-5 py-5 sm:grid-cols-2 sm:px-6">
            <div class="ds-field-group">
              <label class="ds-field-label" for="customer_id">
                {{ $t("gestlab.general.labels.quality_certificates.customer_id") }}
              </label>
              <combobox
                v-model="form.customer_id"
                :has-error="Boolean(form.errors.customer_id)"
                :load-options="loadCustomers"
                placeholder="Pesquisar cliente"
              />
              <p v-if="form.errors.customer_id" class="ds-field-error">
                {{ form.errors.customer_id }}
              </p>
            </div>

            <div class="ds-field-group">
              <label class="ds-field-label" for="warehouse_id">
                {{ $t("gestlab.general.labels.quality_certificates.warehouse_id") }}
              </label>
              <combobox
                v-model="form.warehouse_id"
                :has-error="Boolean(form.errors.warehouse_id)"
                :load-options="loadWarehouses"
                placeholder="Pesquisar armazem"
              />
              <p v-if="form.errors.warehouse_id" class="ds-field-error">
                {{ form.errors.warehouse_id }}
              </p>
            </div>

            <div class="ds-field-group">
              <label class="ds-field-label" for="cl_id">
                {{ $t("gestlab.general.labels.quality_certificates.cl_id") }}
              </label>
              <combobox
                v-model="form.cl_id"
                :has-error="Boolean(form.errors.cl_id)"
                :load-options="loadLabCodes"
                placeholder="Pesquisar codigo laboratorial"
              />
              <p v-if="form.errors.cl_id" class="ds-field-error">
                {{ form.errors.cl_id }}
              </p>
            </div>

            <label class="ds-command-toolbar flex items-center justify-between gap-4 p-4">
              <span>
                <span class="ds-field-label block">
                  {{ $t("gestlab.general.labels.quality_certificates.status") }}
                </span>
                <span class="ds-field-hint mt-1 block">
                  Disponivel para operacoes de emissao.
                </span>
              </span>
              <input
                v-model="form.status"
                type="checkbox"
                class="ds-checkbox"
              />
            </label>
          </div>

          <div class="ds-field-group border-t border-[var(--ds-border)] px-5 py-5 sm:px-6">
            <label class="ds-field-label" for="obs">
              {{ $t("gestlab.general.labels.quality_certificates.obs") }}
            </label>
            <textarea
              id="obs"
              v-model="form.obs"
              rows="6"
              class="ds-field"
              :aria-invalid="Boolean(form.errors.obs)"
              placeholder="Observacoes tecnicas ou comerciais do certificado"
            />
            <p v-if="form.errors.obs" class="ds-field-error">
              {{ form.errors.obs }}
            </p>
          </div>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button
            type="button"
            class="ds-button ds-button-secondary"
            @click="closeSlideover"
          >
            {{ $t("gestlab.general.buttons.cancel") }}
          </button>
          <button
            v-if="form.isDirty"
            type="button"
            class="ds-button ds-button-primary"
            :disabled="form.processing"
            @click="submit"
          >
            {{ form.id ? $t("gestlab.general.buttons.update") : $t("gestlab.general.buttons.submit") }}
          </button>
        </div>
      </template>
    </slide-over>

    <confirm-dialog
      v-if="showDeleteConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Sim"
      cancel="Nao"
      @canceled="resetBulkAction"
      @close="resetBulkAction"
      @confirmed="confirmAction"
    />
  </div>
</template>
