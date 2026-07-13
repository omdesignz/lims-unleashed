<script setup>
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  KeyIcon,
  PlusIcon,
  ShieldCheckIcon,
  TagIcon,
  UserGroupIcon,
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
  slideOverEdit: { type: Boolean, default: false },
});

const { hasPermission } = usePermission();
const editorOpen = ref(false);
const selectedAction = ref(null);
const showActionConfirmation = ref(false);

const form = useForm("RoleCreate", {
  name: "",
  label: "",
  guard_name: "web",
});

const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const metrics = computed(() => [
  {
    label: "Funções",
    value: totalRecords.value,
    detail: "perfis de acesso",
    icon: UserGroupIcon,
  },
  {
    label: "Com etiqueta",
    value: pageRecords.value.filter((role) => role.label).length,
    detail: "legíveis na interface",
    icon: TagIcon,
  },
  {
    label: "Guard web",
    value: pageRecords.value.filter((role) => !role.guard_name || role.guard_name === "web").length,
    detail: "sessão de backoffice",
    icon: ShieldCheckIcon,
  },
  {
    label: "Outros guards",
    value: pageRecords.value.filter((role) => role.guard_name && role.guard_name !== "web").length,
    detail: "contextos segregados",
    icon: KeyIcon,
  },
]);

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

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

function closeEditor() {
  editorOpen.value = false;
  resetEditor();
}

function submit() {
  form.post(route("roles.store"), {
    preserveScroll: true,
    onSuccess: closeEditor,
  });
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
    .filter((role) => role.selected)
    .map((role) => role.id);

  if (!recordIds.length || !["delete", "restore"].includes(selectedAction.value)) {
    closeActionConfirmation();
    return;
  }

  router.get(route(`roles.${selectedAction.value}`), { recordIds }, {
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
          <p class="ds-kicker">Controlo de acesso</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <ShieldCheckIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ $t("gestlab.general.labels.roles.page_title") }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Funções que agrupam permissões e definem responsabilidade operacional no LIMS.
              </p>
            </div>
          </div>
        </div>

        <button
          v-if="hasPermission('add_roles')"
          type="button"
          class="ds-button ds-button-primary whitespace-nowrap"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Nova função
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
      :slide-over-edit="false"
      :query="query"
      :actions="actions"
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="openCreatePanel"
    />

    <SlideOver
      v-if="editorOpen"
      title="Nova função"
      description="Defina a identidade da função antes de associar as respetivas permissões."
      @close="closeEditor"
    >
      <template #content>
        <form id="role-create" class="space-y-6 px-6 py-6" @submit.prevent="submit">
          <div>
            <p class="ds-kicker">Identidade da função</p>
            <h2 class="ds-heading mt-2 text-base">Nome, etiqueta e contexto</h2>
          </div>

          <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
            <div class="flex gap-3">
              <ShieldCheckIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
              <div>
                <h3 class="text-sm font-bold text-[var(--ds-text)]">Permissões na etapa seguinte</h3>
                <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">
                  Depois de registar, será encaminhado para associar permissões e rever o alcance da função.
                </p>
              </div>
            </div>
          </div>

          <div>
            <label for="role-name" class="ds-field-label">Nome técnico</label>
            <BaseInput id="role-name" v-model="form.name" type="text" autocomplete="off" class="ds-field mt-2 font-mono" required />
            <p class="ds-field-hint mt-2">Use um identificador curto e estável, por exemplo: quality_manager.</p>
            <p v-if="form.errors.name" class="ds-field-error mt-2">{{ form.errors.name }}</p>
          </div>

          <div>
            <label for="role-label" class="ds-field-label">Etiqueta</label>
            <BaseInput id="role-label" v-model="form.label" type="text" class="ds-field mt-2" required />
            <p class="ds-field-hint mt-2">Nome legível apresentado na gestão de utilizadores e acessos.</p>
            <p v-if="form.errors.label" class="ds-field-error mt-2">{{ form.errors.label }}</p>
          </div>

          <div>
            <label for="role-guard" class="ds-field-label">Guard</label>
            <BaseInput id="role-guard" v-model="form.guard_name" type="text" autocomplete="off" class="ds-field mt-2 font-mono" required />
            <p class="ds-field-hint mt-2">Use web para a sessão normal do backoffice.</p>
            <p v-if="form.errors.guard_name" class="ds-field-error mt-2">{{ form.errors.guard_name }}</p>
          </div>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="closeEditor">Cancelar</button>
          <button type="submit" form="role-create" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            <ShieldCheckIcon class="h-4 w-4" />
            {{ form.processing ? "A registar..." : "Registar e associar permissões" }}
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
