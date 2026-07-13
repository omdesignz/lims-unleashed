<script setup>
import Combobox from "@/Components/combobox.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  BuildingOffice2Icon,
  EnvelopeIcon,
  IdentificationIcon,
  PhoneIcon,
  PlusIcon,
  UserCircleIcon,
} from "@heroicons/vue/24/outline";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: true },
});

const { hasPermission } = usePermission();
const editorOpen = ref(false);
const selectedAction = ref(null);
const showActionConfirmation = ref(false);

const form = useForm("DepartmentEditor", {
  id: null,
  name: "",
  code: "",
  description: "",
  contact: "",
  extension: "",
  supervisor_id: null,
  email: "",
});

const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const metrics = computed(() => [
  {
    label: "Unidades",
    value: totalRecords.value,
    detail: "estrutura registada",
    icon: BuildingOffice2Icon,
  },
  {
    label: "Com supervisão",
    value: pageRecords.value.filter((department) => department.supervisor).length,
    detail: "responsável identificado",
    icon: UserCircleIcon,
  },
  {
    label: "Email configurado",
    value: pageRecords.value.filter((department) => department.email).length,
    detail: "contacto institucional",
    icon: EnvelopeIcon,
  },
  {
    label: "Extensão interna",
    value: pageRecords.value.filter((department) => department.extension).length,
    detail: "contacto direto",
    icon: PhoneIcon,
  },
]);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const editorTitle = computed(() => form.id ? "Editar unidade" : "Nova unidade");
const editorDescription = computed(() => form.id
  ? `Atualize a estrutura, supervisão e contactos de ${form.name}.`
  : "Registe uma unidade organizacional e associe a respetiva supervisão técnica.",
);
const confirmationDialogTitle = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`),
);
const confirmationDialogDescription = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`),
);

function loadUsers(search, setOptions) {
  return fetch(`/users/getUser?q=${encodeURIComponent(search)}`)
    .then((response) => response.json())
    .then((results) => {
      const options = results.map((user) => ({ value: user.id, label: user.name }));
      setOptions(options);
      return options;
    });
}

function resetEditor() {
  form.reset();
  form.clearErrors();
}

function openCreatePanel() {
  resetEditor();
  editorOpen.value = true;
}

function openEditPanel(department) {
  resetEditor();
  const supervisor = department.supervisor_id?.data ?? department.supervisor_id;

  form.id = department.id;
  form.name = department.name ?? "";
  form.code = department.code ?? "";
  form.description = department.description ?? "";
  form.contact = department.contact ?? "";
  form.extension = department.extension ?? "";
  form.email = department.email ?? "";
  form.supervisor_id = supervisor?.id
    ? { value: supervisor.id, label: department.supervisor ?? supervisor.name }
    : null;
  editorOpen.value = true;
}

function closeEditor() {
  editorOpen.value = false;
  resetEditor();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeEditor,
  };

  if (form.id) {
    form.put(route("departments.update", { department: form.id }), options);
    return;
  }

  form.post(route("departments.store"), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  showActionConfirmation.value = false;
  selectedAction.value = null;
}

function executeBulkAction() {
  const recordIds = pageRecords.value
    .filter((department) => department.selected)
    .map((department) => department.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route(`departments.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: closeActionConfirmation,
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Estrutura organizacional</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <BuildingOffice2Icon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ $t("gestlab.general.labels.departments.page_title") }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Unidades, supervisão e contactos usados na atribuição de trabalho, competência e responsabilidade técnica.
              </p>
            </div>
          </div>
        </div>

        <button
          v-if="hasPermission('add_departments')"
          type="button"
          class="ds-button ds-button-primary whitespace-nowrap"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Nova unidade
        </button>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-4">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(n+3)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
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

    <SlideOver
      v-if="editorOpen"
      :title="editorTitle"
      :description="editorDescription"
      @close="closeEditor"
    >
      <template #content>
        <form id="department-editor" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="space-y-5 px-6 py-6">
            <div>
              <p class="ds-kicker">Identificação</p>
              <h2 class="ds-heading mt-2 text-base">Unidade e responsabilidade</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label for="department-name" class="ds-field-label">Nome da unidade</label>
                <input id="department-name" v-model="form.name" type="text" class="ds-field mt-2" required />
                <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
              </div>

              <div>
                <label for="department-code" class="ds-field-label">Código</label>
                <input id="department-code" v-model="form.code" type="text" class="ds-field mt-2" />
                <p v-if="form.errors.code" class="ds-field-error mt-2">{{ form.errors.code }}</p>
              </div>

              <div>
                <label class="ds-field-label">Supervisor</label>
                <div class="mt-2">
                  <Combobox
                    v-model="form.supervisor_id"
                    :has-error="Boolean(form.errors.supervisor_id)"
                    :load-options="loadUsers"
                    placeholder="Pesquisar utilizadores"
                  />
                </div>
                <p v-if="form.errors.supervisor_id" class="ds-field-error mt-2">{{ form.errors.supervisor_id }}</p>
              </div>

              <div class="sm:col-span-2">
                <label for="department-description" class="ds-field-label">Descrição operacional</label>
                <textarea id="department-description" v-model="form.description" rows="4" class="ds-field mt-2 min-h-28 resize-y" />
                <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
              </div>
            </div>
          </section>

          <section class="space-y-5 px-6 py-6">
            <div>
              <p class="ds-kicker">Comunicação</p>
              <h2 class="ds-heading mt-2 text-base">Contactos institucionais</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label for="department-email" class="ds-field-label">Email</label>
                <input id="department-email" v-model="form.email" type="email" class="ds-field mt-2" required />
                <p v-if="form.errors.email" class="ds-field-error mt-2">{{ form.errors.email }}</p>
              </div>

              <div>
                <label for="department-contact" class="ds-field-label">Telefone</label>
                <input id="department-contact" v-model="form.contact" type="tel" class="ds-field mt-2" />
                <p v-if="form.errors.contact" class="ds-field-error mt-2">{{ form.errors.contact }}</p>
              </div>

              <div>
                <label for="department-extension" class="ds-field-label">Extensão</label>
                <input id="department-extension" v-model="form.extension" type="text" inputmode="numeric" class="ds-field mt-2" />
                <p v-if="form.errors.extension" class="ds-field-error mt-2">{{ form.errors.extension }}</p>
              </div>
            </div>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="closeEditor">Cancelar</button>
          <button type="submit" form="department-editor" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            <IdentificationIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Registar unidade" }}
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
      @canceled="closeActionConfirmation"
      @confirmed="executeBulkAction"
    />
  </div>
</template>
