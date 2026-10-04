<script setup>
import Combobox from "@/Components/combobox.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  IdCard as IdentificationIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
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

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

const editorTitle = computed(() => form.id ? "Editar unidade" : "Nova unidade");
const editorDescription = computed(() => form.id
  ? `Actualize a estrutura, supervisão e contactos de ${form.name}.`
  : "Registe uma unidade organizacional e associe a respectiva supervisão técnica.",
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
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.departments.page_title')" lede="Unidades, supervisão e contactos usados na atribuição de trabalho, competência e responsabilidade técnica.">
      <template #actions>
        <button
          v-if="hasPermission('add_departments')"
          type="button"
          class="ds-button ds-button-primary whitespace-nowrap"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Nova unidade
        </button>
      </template>
    </PageHeader>

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
                <BaseInput id="department-name" v-model="form.name" type="text" class="ds-field mt-2" required />
                <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
              </div>

              <div>
                <label for="department-code" class="ds-field-label">Código</label>
                <BaseInput id="department-code" v-model="form.code" type="text" class="ds-field mt-2" />
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
                <label for="department-email" class="ds-field-label">Correio electrónico</label>
                <BaseInput id="department-email" v-model="form.email" type="email" class="ds-field mt-2" required />
                <p v-if="form.errors.email" class="ds-field-error mt-2">{{ form.errors.email }}</p>
              </div>

              <div>
                <label for="department-contact" class="ds-field-label">Telefone</label>
                <BaseInput id="department-contact" v-model="form.contact" type="tel" class="ds-field mt-2" />
                <p v-if="form.errors.contact" class="ds-field-error mt-2">{{ form.errors.contact }}</p>
              </div>

              <div>
                <label for="department-extension" class="ds-field-label">Extensão</label>
                <BaseInput id="department-extension" v-model="form.extension" type="text" inputmode="numeric" class="ds-field mt-2" />
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
