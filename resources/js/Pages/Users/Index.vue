<script setup>
import ComboboxMultiple from "@/Components/combobox-multiple.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import LaboratoryMembershipForm from "@/Components/LaboratoryMembershipForm.vue";
import { usePermission } from "@/Composables/usePermissions";
import { submitStaffAccountMutation } from "@/Composables/useStaffAccountPayload";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";
import {
  RefreshCw as ArrowPathIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
  BadgeCheck as CheckBadgeIcon,
  TriangleAlert as ExclamationTriangleIcon,
  Lock as LockClosedIcon,
  LockOpen as LockOpenIcon,
  Plus as PlusIcon,
  ShieldCheck as ShieldCheckIcon,
  Users as UsersIcon,
  UserMinus as UserMinusIcon,
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
  competenceSummary: { type: Object, default: () => ({}) },
  openCreate: { type: Boolean, default: false },
  slideOverEdit: { type: Boolean, default: false },
  accountCapabilities: { type: Object, default: () => ({}) },
});

const { hasPermission } = usePermission();
const createPanelOpen = ref(props.openCreate);
const membershipPanelOpen = ref(false);
const selectedAction = ref(null);
const selectedRecordId = ref(null);
const pendingRecordIds = ref([]);
const showActionConfirmation = ref(false);
const mutationForm = useForm({});

const form = useForm({
  name: "",
  email: "",
  gender: "",
  username: "",
  departments: [],
  password: "",
  password_confirmation: "",
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

const actions = computed(() => [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
].filter((action) => !action.id || props.accountCapabilities[action.id]));

const confirmationDialogTitle = computed(() =>
  selectedAction.value === 'removeMembership' ? 'Remover adesão ao laboratório' :
  trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`),
);
const confirmationDialogDescription = computed(() =>
  selectedAction.value === 'removeMembership' ? 'Este membro deixará de ter acesso a este laboratório. A conta partilhada, a adesão a outros laboratórios e a evidência de qualificações serão preservadas.' :
  trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`),
);
const confirmationVariant = computed(() =>
  ["delete", "ban", "removeMembership"].includes(selectedAction.value) ? "danger" : "question",
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
  if (!props.accountCapabilities.create || form.processing) return;
  form.reset();
  form.clearErrors();
  createPanelOpen.value = true;
}

function closeCreatePanel() {
  if (form.processing) return;
  createPanelOpen.value = false;
  form.reset();
  form.clearErrors();
}

function submit() {
  if (form.processing || !props.accountCapabilities.create) return;
  form.post(route("users.store"), {
    preserveScroll: true,
    onSuccess: () => { createPanelOpen.value = false; form.reset(); form.clearErrors(); },
    onError: () => form.reset("password", "password_confirmation"),
  });
}

function requestBulkAction(action) {
  if (mutationForm.processing || !props.accountCapabilities[action]) return;
  mutationForm.clearErrors();
  selectedAction.value = action;
  selectedRecordId.value = null;
  pendingRecordIds.value = pageRecords.value.filter((record) => record.selected).map((record) => record.id);
  if (!pendingRecordIds.value.length) return;
  showActionConfirmation.value = true;
}

function requestRecordAction(action, recordId) {
  if (mutationForm.processing) return;
  mutationForm.clearErrors();
  selectedAction.value = action;
  selectedRecordId.value = recordId;
  pendingRecordIds.value = [recordId];
  showActionConfirmation.value = true;
}

function closeActionConfirmation() {
  if (mutationForm.processing) return;
  showActionConfirmation.value = false;
  selectedAction.value = null;
  selectedRecordId.value = null;
}

function executeAction() {
  submitStaffAccountMutation(mutationForm, selectedAction.value, pendingRecordIds.value, route, () => {
    showActionConfirmation.value = false;
    selectedAction.value = null;
    selectedRecordId.value = null;
  });
}

function archiveRecord(action, ids) {
  submitStaffAccountMutation(mutationForm, action, ids, route, () => {});
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

        <button v-if="accountCapabilities.join" type="button" class="ds-button ds-button-secondary whitespace-nowrap" @click="membershipPanelOpen = true">
          <PlusIcon class="h-4 w-4" />
          Adicionar membro
        </button>
        <button
          v-if="accountCapabilities.create"
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

    <p v-if="mutationForm.hasErrors && !showActionConfirmation" role="alert" class="ds-field-error">{{ Object.values(mutationForm.errors)[0] }}</p>
    <RecordsTable
      :record="record"
      :model="model"
      :abilities="abilities"
      :fields="fields"
      :slide-over-edit="slideOverEdit"
      :query="query"
      :actions="actions"
      :action-methods="{ delete: 'post', restore: 'post' }"
      :archive-handler="archiveRecord"
      :action-processing="mutationForm.processing"
      :create-action="false"
      @execute-action="requestBulkAction"
      @create-record="openCreatePanel"
    >
      <template #actions="{ data }">
        <button v-if="data.action_capabilities?.removeMembership" type="button" class="ds-table-action ds-table-action-danger" :aria-label="`Remover adesão de ${data.name} a este laboratório`" title="Remover adesão ao laboratório" :disabled="mutationForm.processing" @click="requestRecordAction('removeMembership', data.id)">
          <UserMinusIcon class="h-4 w-4" />
        </button>
        <button
          v-if="!data.deleted && data.action_capabilities?.impersonate"
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
          v-if="!data.deleted && data.action_capabilities?.status"
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

    <LaboratoryMembershipForm v-if="membershipPanelOpen" @close="membershipPanelOpen = false" />
    <SlideOver
      v-if="createPanelOpen"
      :disabled="form.processing"
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

              <div>
                <label for="new-user-password" class="ds-field-label">Palavra-passe inicial</label>
                <BaseInput id="new-user-password" v-model="form.password" type="password" autocomplete="new-password" class="ds-field mt-2" required :aria-invalid="Boolean(form.errors.password)" />
                <p v-if="form.errors.password" class="ds-field-error">{{ form.errors.password }}</p>
              </div>

              <div>
                <label for="new-user-password-confirmation" class="ds-field-label">Confirmar palavra-passe</label>
                <BaseInput id="new-user-password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" class="ds-field mt-2" required :aria-invalid="Boolean(form.errors.password_confirmation)" />
                <p v-if="form.errors.password_confirmation" class="ds-field-error">{{ form.errors.password_confirmation }}</p>
              </div>
            </div>
            <p class="ds-copy text-xs">Use pelo menos 8 caracteres, com maiúsculas, minúsculas, número e símbolo. Partilhe a credencial por um canal seguro.</p>
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
      :confirm="mutationForm.processing ? 'A processar...' : 'Sim'"
      cancel="Não"
      :disabled="mutationForm.processing"
      keep-open-on-confirm
      @canceled="closeActionConfirmation"
      @confirmed="executeAction"
    >
      <p v-if="mutationForm.hasErrors" role="alert" class="ds-field-error">{{ Object.values(mutationForm.errors)[0] }}</p>
    </ConfirmDialog>
  </div>
</template>
