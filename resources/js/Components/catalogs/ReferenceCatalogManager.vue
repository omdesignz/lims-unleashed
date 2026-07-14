<script setup>
import Combobox from "@/Components/combobox.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import ToggleField from "@/Components/base/ToggleField.vue";
import { usePermission } from "@/Composables/usePermissions";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import { router, useForm } from "@inertiajs/vue3";
import {
  ArchiveBoxIcon,
  CheckBadgeIcon,
  DocumentTextIcon,
  PlusIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
  openCreate: { type: Boolean, default: false },
  routePrefix: { type: String, required: true },
  routeParameter: { type: String, required: true },
  permissionKey: { type: String, required: true },
  title: { type: String, required: true },
  kicker: { type: String, default: "Configuração analítica" },
  description: { type: String, required: true },
  entityLabel: { type: String, required: true },
  newEntityLabel: { type: String, default: "" },
  codeLabel: { type: String, default: "Código" },
  descriptionLabel: { type: String, default: "Descrição" },
  nameLabel: { type: String, default: "Nome" },
  departmentLabel: { type: String, default: "Departamento" },
  supportsName: { type: Boolean, default: false },
  supportsCode: { type: Boolean, default: true },
  codeRequired: { type: Boolean, default: true },
  supportsDepartment: { type: Boolean, default: false },
  extraFields: { type: Array, default: () => [] },
  createDescription: { type: String, default: "Adicione um registo ao catálogo controlado usado pelos fluxos operacionais." },
  editDescription: { type: String, default: "Actualize a identificação e o contexto de utilização deste registo controlado." },
  formDescription: { type: String, default: "Use identificadores reconhecíveis e uma descrição que torne o contexto de utilização explícito." },
  icon: { type: [Object, Function], required: true },
});

const { hasPermission } = usePermission();
const isPanelOpen = ref(props.openCreate);
const showActionConfirmation = ref(false);
const selectedAction = ref(null);

const emptyForm = () => ({
  id: null,
  name: "",
  code: "",
  description: "",
  department_id: null,
  ...Object.fromEntries(props.extraFields.map((field) => [field.key, field.defaultValue ?? ""])),
});
const form = useForm(emptyForm());

const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const archivedRecords = computed(() => pageRecords.value.filter((record) => record.deleted).length);
const activeRecords = computed(() => pageRecords.value.length - archivedRecords.value);
const describedRecords = computed(() => pageRecords.value.filter((record) => record.description?.trim()).length);
const governedRecords = computed(() => pageRecords.value.filter((record) => record.department_id).length);

const metrics = computed(() => [
  { label: "Registos", value: totalRecords.value, detail: "catálogo total", icon: props.icon },
  { label: "Activos nesta página", value: activeRecords.value, detail: "disponíveis para uso", icon: CheckBadgeIcon },
  props.supportsDepartment
    ? { label: "Com departamento", value: governedRecords.value, detail: "responsabilidade definida", icon: DocumentTextIcon }
    : { label: "Com descrição", value: describedRecords.value, detail: "contexto documentado", icon: DocumentTextIcon },
  { label: "Arquivados nesta página", value: archivedRecords.value, detail: "fora da selecção activa", icon: ArchiveBoxIcon },
]);

const panelTitle = computed(() => form.id
  ? `Editar ${props.entityLabel.toLowerCase()} ${form.code || form.name}`
  : props.newEntityLabel || `Novo ${props.entityLabel.toLowerCase()}`);
const panelDescription = computed(() => form.id
  ? props.editDescription
  : props.createDescription);
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function loadDepartments(query, setOptions) {
  return loadSelectOptions("/departments/getDepartment", query, setOptions, optionMappers.name);
}

function openCreatePanel() {
  form.defaults(emptyForm());
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function openEditPanel(data) {
  form.defaults({
    id: data.id,
    name: data.name || "",
    code: data.code || "",
    description: data.description || "",
    department_id: data.department_id
      ? { value: data.department_id, label: data.department || String(data.department_id) }
      : null,
    ...Object.fromEntries(props.extraFields.map((field) => [field.key, data[field.key] ?? field.defaultValue ?? ""])),
  });
  form.reset();
  form.clearErrors();
  isPanelOpen.value = true;
}

function closePanel() {
  isPanelOpen.value = false;
  form.clearErrors();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      closePanel();
      form.defaults(emptyForm());
      form.reset();
    },
  };

  if (form.id) {
    form.put(route(`${props.routePrefix}.update`, { [props.routeParameter]: form.id }), options);
    return;
  }

  form.post(route(`${props.routePrefix}.store`), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function executeBulkAction() {
  const recordIds = pageRecords.value.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    showActionConfirmation.value = false;
    return;
  }

  router.get(route(`${props.routePrefix}.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      selectedAction.value = null;
    },
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">{{ kicker }}</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <component :is="icon" class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ title }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">{{ description }}</p>
            </div>
          </div>
        </div>

        <button v-if="hasPermission(`add_${permissionKey}`)" type="button" class="ds-button ds-button-primary" @click="openCreatePanel">
          <PlusIcon class="h-4 w-4" />
          Novo registo
        </button>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="metric in metrics" :key="metric.label" class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 break-words text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>
    </section>

    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="openCreatePanel"
      @slideover-on="openEditPanel"
    />

    <SlideOver v-if="isPanelOpen" :title="panelTitle" :description="panelDescription" @close="closePanel">
      <template #content>
        <form id="reference-catalog-form" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="px-5 py-5 sm:px-6">
            <p class="ds-kicker">Identificação</p>
            <h2 class="ds-heading mt-1 text-base">Dados do registo</h2>
            <p class="ds-copy mt-1 text-sm">{{ formDescription }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
              <div v-if="supportsName" class="ds-field-group">
                <label for="reference-name" class="ds-field-label">{{ nameLabel }} <span class="ds-field-required">*</span></label>
                <BaseInput id="reference-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" />
                <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
              </div>
              <div v-if="supportsCode" class="ds-field-group" :class="supportsName ? '' : 'sm:col-span-2'">
                <label for="reference-code" class="ds-field-label">
                  {{ codeLabel }}
                  <span v-if="codeRequired" class="ds-field-required">*</span>
                </label>
                <BaseInput id="reference-code" v-model="form.code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" />
                <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
              </div>
              <div v-if="supportsDepartment" class="ds-field-group sm:col-span-2">
                <label class="ds-field-label">{{ departmentLabel }} <span class="ds-field-required">*</span></label>
                <Combobox v-model="form.department_id" :load-options="loadDepartments" :has-error="Boolean(form.errors.department_id)" placeholder="Seleccione o departamento responsável" />
                <p v-if="form.errors.department_id" class="ds-field-error">{{ form.errors.department_id }}</p>
              </div>
              <div v-for="field in extraFields" :key="field.key" class="ds-field-group" :class="field.fullWidth ? 'sm:col-span-2' : ''">
                <ToggleField
                  v-if="field.type === 'toggle'"
                  :id="`reference-${field.key}`"
                  v-model="form[field.key]"
                  :label="field.label"
                  :description="field.help || ''"
                  class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]"
                />
                <template v-else>
                  <label :for="`reference-${field.key}`" class="ds-field-label">
                    {{ field.label }}
                    <span v-if="field.required" class="ds-field-required">*</span>
                  </label>
                  <textarea
                    v-if="field.type === 'textarea'"
                    :id="`reference-${field.key}`"
                    v-model="form[field.key]"
                    :rows="field.rows || 4"
                    class="ds-field resize-y"
                    :aria-invalid="Boolean(form.errors[field.key])"
                    :placeholder="field.placeholder || ''"
                  />
                  <BaseInput
                    v-else
                    :id="`reference-${field.key}`"
                    v-model="form[field.key]"
                    :type="field.type || 'text'"
                    :min="field.min"
                    :max="field.max"
                    :step="field.step"
                    class="ds-field"
                    :class="field.monospace ? 'font-mono' : ''"
                    :aria-invalid="Boolean(form.errors[field.key])"
                    :placeholder="field.placeholder || ''"
                  />
                  <p v-if="field.help" class="ds-field-help">{{ field.help }}</p>
                </template>
                <p v-if="form.errors[field.key]" class="ds-field-error">{{ form.errors[field.key] }}</p>
              </div>
            </div>
          </section>

          <section class="px-5 py-5 sm:px-6">
            <label for="reference-description" class="ds-field-label">{{ descriptionLabel }}</label>
            <textarea id="reference-description" v-model="form.description" rows="7" class="ds-field mt-2 min-h-40 resize-y" :aria-invalid="Boolean(form.errors.description)" placeholder="Aplicabilidade, origem documental ou restricoes de utilização" />
            <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closePanel">Cancelar</button>
          <button type="submit" form="reference-catalog-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Adicionar registo" }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @canceled="showActionConfirmation = false"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
