<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  Fingerprint as FingerPrintIcon,
  KeyRound as KeyIcon,
  Plus as PlusIcon,
} from "@lucide/vue";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  manageGlobalAccess: { type: Boolean, default: false },
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: true },
});

const { hasPermission } = usePermission();
const editorOpen = ref(false);
const selectedAction = ref(null);
const showActionConfirmation = ref(false);

const form = useForm("PermissionEditor", {
  id: null,
  name: "",
  label: "",
  guard_name: "web",
});

const pageRecords = computed(() => props.record?.data || []);

const actions = computed(() => props.manageGlobalAccess ? [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
] : []);

const editorTitle = computed(() => form.id ? "Editar permissão" : "Nova permissão");
const editorDescription = computed(() => form.id
  ? `Actualize a apresentação e o contexto de ${form.name}.`
  : "Registe uma chave de autorização para funções e políticas do sistema.",
);
const confirmationDialogTitle = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`),
);
const confirmationDialogDescription = computed(() =>
  trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`),
);

function resetEditor() {
  form.reset();
  form.clearErrors();
}

function openCreatePanel() {
  resetEditor();
  editorOpen.value = true;
}

function openEditPanel(permission) {
  resetEditor();
  form.id = permission.id;
  form.name = permission.name ?? "";
  form.label = permission.label ?? "";
  form.guard_name = permission.guard_name ?? "web";
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
    form.put(route("permissions.update", { permission: form.id }), options);
    return;
  }

  form.post(route("permissions.store"), options);
}

function requestBulkAction(action) {
  selectedAction.value = action;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  selectedAction.value = null;
  showActionConfirmation.value = false;
}

function executeBulkAction() {
  const recordIds = pageRecords.value
    .filter((permission) => permission.selected)
    .map((permission) => permission.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.post(route(selectedAction.value === "delete" ? "permissions.destroy" : "permissions.restore"), { recordIds }, {
    preserveScroll: true,
    onFinish: closeActionConfirmation,
  });
}
</script>

<template>
  <div class="pl-page space-y-6">
    <PageHeader :title="$t('gestlab.general.labels.permissions.page_title')" lede="Chaves de autorização usadas por funções, políticas e operações protegidas do LIMS.">
      <template #actions>
        <button
          v-if="manageGlobalAccess && hasPermission('add_permissions')"
          type="button"
          class="ds-button ds-button-primary whitespace-nowrap"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Nova permissão
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
      :action-methods="{ delete: 'post', restore: 'post' }"
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
        <form id="permission-editor" class="space-y-6 px-6 py-6" @submit.prevent="submit">
          <div>
            <p class="ds-kicker">Regra de autorização</p>
            <h2 class="ds-heading mt-2 text-base">Identidade e contexto</h2>
          </div>

          <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex gap-3">
              <KeyIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <div>
                <h3 class="text-sm font-bold text-[var(--ds-text)]">Chave técnica estável</h3>
                <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">
                  Alterar a chave pode afetar políticas e verificações existentes. Prefira ajustar apenas a etiqueta quando a regra não mudou.
                </p>
              </div>
            </div>
          </div>

          <div>
            <label for="permission-name" class="ds-field-label">Chave técnica</label>
            <BaseInput id="permission-name" v-model="form.name" type="text" autocomplete="off" class="ds-field mt-2 font-mono" required />
            <p class="ds-field-hint mt-2">Exemplo: approve_quality_certificates</p>
            <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="permission-label" class="ds-field-label">Etiqueta</label>
            <BaseInput id="permission-label" v-model="form.label" type="text" class="ds-field mt-2" />
            <p class="ds-field-hint mt-2">Texto apresentado a administradores na configuração de funções.</p>
            <p v-if="form.errors.label" class="ds-field-error mt-2">{{ form.errors.label }}</p>
          </div>

          <div>
            <label for="permission-guard" class="ds-field-label">Guard</label>
            <BaseInput id="permission-guard" v-model="form.guard_name" type="text" autocomplete="off" class="ds-field mt-2 font-mono" />
            <p class="ds-field-hint mt-2">Use web para a sessão normal do backoffice.</p>
            <p v-if="form.errors.guard_name" class="ds-field-error mt-2">{{ form.errors.guard_name }}</p>
          </div>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="closeEditor">Cancelar</button>
          <button type="submit" form="permission-editor" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            <FingerPrintIcon class="h-4 w-4" />
            {{ form.processing ? "A guardar..." : form.id ? "Guardar alterações" : "Registar permissão" }}
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
