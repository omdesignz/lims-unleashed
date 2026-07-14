<script setup>
import ComboboxMultiple from "@/Components/combobox-multiple.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import { usePermission } from "@/Composables/usePermissions";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  ArrowPathIcon,
  ArrowsRightLeftIcon,
  CheckBadgeIcon,
  ExclamationTriangleIcon,
  LockClosedIcon,
  LockOpenIcon,
  PlusIcon,
  ShieldCheckIcon,
  UsersIcon,
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
  competenceSummary: { type: Object, default: () => ({}) },
  openCreate: { type: Boolean, default: false },
  slideOverEdit: { type: Boolean, default: false },
});

const { hasPermission } = usePermission();
const createPanelOpen = ref(props.openCreate);
const selectedAction = ref(null);
const selectedRecordId = ref(null);
const showActionConfirmation = ref(false);

const form = useForm("CreateUser", {
  name: "",
  email: "",
  gender: "",
  username: "",
  departments: [],
});

const pageRecords = computed(() => props.record?.data || []);
const totalRecords = computed(() => props.record?.meta?.total ?? pageRecords.value.length);
const metrics = computed(() => [
  {
    label: "Utilizadores",
    value: totalRecords.value,
    detail: "equipa registada",
    icon: UsersIcon,
    tone: "neutral",
  },
  {
    label: "Competência monitorizada",
    value: props.competenceSummary?.tracked_users ?? 0,
    detail: "nesta página",
    icon: ShieldCheckIcon,
    tone: "good",
  },
  {
    label: "Qualificações expiradas",
    value: props.competenceSummary?.expired_qualifications ?? 0,
    detail: "requerem bloqueio ou renovação",
    icon: ExclamationTriangleIcon,
    tone: "critical",
  },
  {
    label: "Renovações próximas",
    value: props.competenceSummary?.expiring_soon ?? 0,
    detail: "janela de acompanhamento",
    icon: ArrowPathIcon,
    tone: "warning",
  },
  {
    label: "Evidência em falta",
    value: props.competenceSummary?.missing_evidence ?? 0,
    detail: "dossiers incompletos",
    icon: CheckBadgeIcon,
    tone: "warning",
  },
]);

const genderOptions = [
  { value: "F", label: "Feminino" },
  { value: "M", label: "Masculino" },
  { value: "O", label: "Outro" },
];

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
const confirmationVariant = computed(() =>
  ["delete", "ban"].includes(selectedAction.value) ? "danger" : "question",
);

function loadDepartments(search, setOptions) {
  return fetch(`/departments/getDepartment?q=${encodeURIComponent(search)}`)
    .then((response) => response.json())
    .then((results) => {
      const options = results.map((department) => ({
        value: department.id,
        label: department.name,
      }));

      setOptions(options);
      return options;
    });
}

function openCreatePanel() {
  form.reset();
  form.clearErrors();
  createPanelOpen.value = true;
}

function closeCreatePanel() {
  createPanelOpen.value = false;
  form.reset();
  form.clearErrors();
}

function submit() {
  form.post(route("users.store"), {
    preserveScroll: true,
    onSuccess: closeCreatePanel,
  });
}

function requestBulkAction(action) {
  selectedAction.value = action;
  selectedRecordId.value = null;
  showActionConfirmation.value = true;
}

function requestRecordAction(action, recordId) {
  selectedAction.value = action;
  selectedRecordId.value = recordId;
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  showActionConfirmation.value = false;
  selectedAction.value = null;
  selectedRecordId.value = null;
}

function executeAction() {
  const bulkRecordIds = pageRecords.value
    .filter((record) => record.selected)
    .map((record) => record.id);

  if (["delete", "restore"].includes(selectedAction.value)) {
    if (!bulkRecordIds.length) {
      closeActionConfirmation();
      return;
    }

    router.get(route(`users.${selectedAction.value}`), { recordIds: bulkRecordIds }, {
      preserveScroll: true,
      onFinish: closeActionConfirmation,
    });
    return;
  }

  if (["ban", "unban"].includes(selectedAction.value) && selectedRecordId.value) {
    router.get(route("users.toggleActiveStatus", { id: selectedRecordId.value }), {}, {
      preserveScroll: true,
      onFinish: closeActionConfirmation,
    });
    return;
  }

  if (selectedAction.value === "impersonate" && selectedRecordId.value) {
    router.get(route("users.impersonate"), { id: selectedRecordId.value }, {
      preserveScroll: true,
      onFinish: closeActionConfirmation,
    });
    return;
  }

  closeActionConfirmation();
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">Competência e autorização</p>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
              <UsersIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <h1 class="ds-heading text-2xl">{{ $t("gestlab.general.labels.users.page_title") }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">
                Equipa, acessos e evidência de competência para executar, verificar e aprovar trabalho laboratorial.
              </p>
            </div>
          </div>
        </div>

        <button
          v-if="hasPermission('add_users')"
          type="button"
          class="ds-button ds-button-primary whitespace-nowrap"
          @click="openCreatePanel"
        >
          <PlusIcon class="h-4 w-4" />
          Novo utilizador
        </button>
      </div>

      <dl class="mt-6 grid overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] sm:grid-cols-2 xl:grid-cols-5">
        <div
          v-for="metric in metrics"
          :key="metric.label"
          class="border-b border-[var(--ds-border)] px-4 py-3 sm:[&:nth-child(odd)]:border-r sm:[&:nth-child(5)]:border-b-0 xl:border-b-0 xl:border-r xl:last:border-r-0"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ metric.label }}</dt>
              <dd
                class="mt-2 break-words text-xl font-bold"
                :class="{
                  'text-[var(--ds-text)]': metric.tone === 'neutral',
                  'text-emerald-700 dark:text-emerald-300': metric.tone === 'good',
                  'text-amber-700 dark:text-amber-300': metric.tone === 'warning',
                  'text-rose-700 dark:text-rose-300': metric.tone === 'critical',
                }"
              >
                {{ metric.value }}
              </dd>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">{{ metric.detail }}</p>
            </div>
            <component :is="metric.icon" class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          </div>
        </div>
      </dl>

      <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold text-[var(--ds-text-muted)]">
        <span class="ds-chip ds-chip-neutral">
          {{ competenceSummary?.ready_for_renewal ?? 0 }} renovações prontas
        </span>
        <span>Os indicadores de competência refletem os registos visíveis nesta página.</span>
      </div>
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
    >
      <template #actions="{ data }">
        <button
          v-if="!data.deleted && hasPermission('impersonate_users')"
          type="button"
          class="ds-table-action"
          title="Entrar como este utilizador"
          :aria-label="`Entrar como ${data.name}`"
          @click="requestRecordAction('impersonate', data.id)"
        >
          <ArrowsRightLeftIcon class="h-4 w-4" />
          <span class="sr-only">Entrar como {{ data.name }}</span>
        </button>

        <button
          v-if="!data.deleted && hasPermission('ban_users')"
          type="button"
          class="ds-table-action"
          :class="{ 'ds-table-action-danger': data.is_active }"
          :title="data.is_active ? 'Desactivar acesso' : 'Reactivar acesso'"
          :aria-label="`${data.is_active ? 'Desactivar' : 'Reactivar'} acesso de ${data.name}`"
          @click="requestRecordAction(data.is_active ? 'ban' : 'unban', data.id)"
        >
          <LockClosedIcon v-if="data.is_active" class="h-4 w-4" />
          <LockOpenIcon v-else class="h-4 w-4" />
          <span class="sr-only">{{ data.is_active ? "Desactivar" : "Reactivar" }} {{ data.name }}</span>
        </button>
      </template>
    </RecordsTable>

    <SlideOver
      v-if="createPanelOpen"
      title="Novo utilizador"
      description="Defina a identidade e a unidade operacional; complete funções, permissões e competências no dossier individual."
      @close="closeCreatePanel"
    >
      <template #content>
        <form id="create-user-form" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="space-y-4 px-6 py-5">
            <div>
              <p class="ds-kicker">Identidade</p>
              <h2 class="ds-heading mt-2 text-base">Dados de acesso</h2>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
              <div class="sm:col-span-2">
                <label for="user-name" class="ds-field-label">Nome completo</label>
                <BaseInput id="user-name" v-model="form.name" type="text" autocomplete="name" class="ds-field mt-2" required />
                <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
              </div>

              <div>
                <label for="user-email" class="ds-field-label">Correio electrónico</label>
                <BaseInput id="user-email" v-model="form.email" type="email" autocomplete="email" class="ds-field mt-2" required />
                <p v-if="form.errors.email" class="ds-field-error">{{ form.errors.email }}</p>
              </div>

              <div>
                <label for="user-username" class="ds-field-label">Nome de utilizador</label>
                <BaseInput id="user-username" v-model="form.username" type="text" autocomplete="username" class="ds-field mt-2" />
                <p v-if="form.errors.username" class="ds-field-error">{{ form.errors.username }}</p>
              </div>
            </div>
          </section>

          <section class="space-y-4 px-6 py-5">
            <div>
              <p class="ds-kicker">Contexto operacional</p>
              <h2 class="ds-heading mt-2 text-base">Unidade e registo</h2>
            </div>

            <div>
              <label class="ds-field-label">Departamentos</label>
              <div class="mt-2">
                <ComboboxMultiple
                  v-model="form.departments"
                  :load-options="loadDepartments"
                  placeholder="Pesquisar departamentos"
                  multiple
                />
              </div>
              <p v-if="form.errors.departments" class="ds-field-error">{{ form.errors.departments }}</p>
            </div>

            <fieldset>
              <legend class="ds-field-label">Género</legend>
              <div class="mt-2 grid gap-2 sm:grid-cols-3">
                <label
                  v-for="option in genderOptions"
                  :key="option.value"
                  class="flex cursor-pointer items-center gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2.5 text-sm font-semibold text-[var(--ds-text-muted)] transition hover:border-[rgb(var(--primary-300-rgb))] hover:text-[var(--ds-text)]"
                >
                  <RadioInput v-model="form.gender" type="radio" class="ds-radio" :value="option.value" required />
                  {{ option.label }}
                </label>
              </div>
              <p v-if="form.errors.gender" class="ds-field-error">{{ form.errors.gender }}</p>
            </fieldset>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex items-center justify-end gap-3">
          <button type="button" class="ds-button ds-button-secondary" @click="closeCreatePanel">Cancelar</button>
          <button type="submit" form="create-user-form" class="ds-button ds-button-primary" :disabled="form.processing">
            <PlusIcon class="h-4 w-4" />
            {{ form.processing ? "A registar..." : "Registar utilizador" }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      :variant="confirmationVariant"
      confirm="Sim"
      cancel="Não"
      @canceled="closeActionConfirmation"
      @confirmed="executeAction"
    />
  </div>
</template>
