<script setup>
import Combobox from "@/Components/combobox.vue";
import ConfirmDialog from "@/Components/confirm-dialog.vue";
import RecordsTable from "@/Components/records-table.vue";
import SlideOver from "@/Components/slide-over.vue";
import {
  MessageSquareText as ChatBubbleBottomCenterTextIcon,
  Check as CheckIcon,
  CircleHelp as QuestionMarkCircleIcon,
} from "@lucide/vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { trans } from "laravel-vue-i18n";
import { computed, ref } from "vue";

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
  kind: { type: String, required: true },
});

const config = computed(() => props.kind === "answer" ? {
  routePrefix: "faqanswers",
  routeParameter: "answer",
  parentField: "faq_id",
  parentLabelKey: "faq",
  parentLabel: "Pergunta associada",
  parentPlaceholder: "Pesquisar pergunta",
  parentEndpoint: "/faqs/getFAQ",
  parentResultLabel: "description",
  counterpartRoute: "faqs.index",
  counterpartLabel: "Perguntas",
  kicker: "Conteúdo de apoio",
  title: "Respostas da base de conhecimento",
  description: "Respostas operacionais ligadas a perguntas frequentes sobre serviços, recolhas e utilização do portal.",
  entityLabel: "resposta",
  fieldLabel: "Resposta publicada",
  fieldPlaceholder: "Redija uma resposta objectiva e operacional",
  icon: ChatBubbleBottomCenterTextIcon,
} : {
  routePrefix: "faqs",
  routeParameter: "faq",
  parentField: "category_id",
  parentLabelKey: "category",
  parentLabel: "Categoria",
  parentPlaceholder: "Pesquisar categoria",
  parentEndpoint: "/faqcategories/getFAQCategory",
  parentResultLabel: "name",
  counterpartRoute: "faqanswers.index",
  counterpartLabel: "Respostas",
  kicker: "Conteúdo de apoio",
  title: "Perguntas frequentes",
  description: "Perguntas organizadas por tema para orientar clientes e equipas nos fluxos administrativos e laboratoriais.",
  entityLabel: "pergunta",
  fieldLabel: "Pergunta publicada",
  fieldPlaceholder: "Introduza a pergunta tal como será apresentada ao utilizador",
  icon: QuestionMarkCircleIcon,
});

const actionId = ref(null);
const isDrawerOpen = ref(false);
const showActionConfirmation = ref(false);
const totalRecords = computed(() => props.record.meta?.total ?? props.record.data.length);
const representedParents = computed(() => new Set(props.record.data.map((record) => record[config.value.parentField]).filter(Boolean)).size);
const drawerTitle = computed(() => form.id ? `Editar ${config.value.entityLabel}` : `Nova ${config.value.entityLabel}`);
const drawerDescription = computed(() => form.id
  ? "Actualize o conteúdo mantendo a associação temática existente."
  : `Adicione uma ${config.value.entityLabel} à base de conhecimento controlada.`);
const confirmationDialogTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${actionId.value}`));
const confirmationDialogDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${actionId.value}`));

const actions = [
  { id: null, label: "gestlab.actions.bulk_actions_text" },
  { id: "delete", label: "gestlab.actions.delete" },
  { id: "restore", label: "gestlab.actions.restore" },
];

function initialFormData() {
  return {
    id: null,
    description: "",
    [config.value.parentField]: null,
  };
}

const form = useForm(initialFormData());

function resetForm() {
  form.defaults(initialFormData());
  form.reset();
  form.clearErrors();
}

function openCreateDrawer() {
  resetForm();
  isDrawerOpen.value = true;
}

function openEditDrawer(record) {
  form.defaults({
    id: record.id,
    description: record.description ?? "",
    [config.value.parentField]: record[config.value.parentField]
      ? { value: record[config.value.parentField], label: record[config.value.parentLabelKey] }
      : null,
  });
  form.reset();
  form.clearErrors();
  isDrawerOpen.value = true;
}

function closeDrawer() {
  isDrawerOpen.value = false;
  resetForm();
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeDrawer,
  };

  if (form.id) {
    form.put(route(`${config.value.routePrefix}.update`, { [config.value.routeParameter]: form.id }), options);
    return;
  }

  form.post(route(`${config.value.routePrefix}.store`), options);
}

async function loadParents(query, setOptions) {
  const response = await fetch(`${config.value.parentEndpoint}?q=${encodeURIComponent(query)}`);
  const results = await response.json();
  setOptions(results.map((result) => ({ value: result.id, label: result[config.value.parentResultLabel] })));
}

function requestBulkAction(selectedActionId) {
  actionId.value = selectedActionId;
  showActionConfirmation.value = true;
}

function confirmAction() {
  const recordIds = props.record.data.filter((record) => record.selected).map((record) => record.id);

  if (!recordIds.length || !actionId.value) {
    showActionConfirmation.value = false;
    return;
  }

  const routeName = `${config.value.routePrefix}.${actionId.value === "restore" ? "restore" : "destroy"}`;

  router.get(route(routeName), { recordIds }, {
    preserveState: false,
    preserveScroll: true,
    onFinish: () => {
      showActionConfirmation.value = false;
      actionId.value = null;
    },
  });
}
</script>

<template>
  <div class="space-y-6">
    <section class="ds-panel overflow-hidden p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="flex items-start gap-3">
          <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
            <component :is="config.icon" class="h-5 w-5" aria-hidden="true" />
          </span>
          <div>
            <p class="ds-kicker">{{ config.kicker }}</p>
            <h1 class="ds-heading mt-1 text-2xl">{{ config.title }}</h1>
            <p class="ds-copy mt-1 max-w-3xl text-sm">{{ config.description }}</p>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 lg:justify-end">
          <span class="inline-flex items-center rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-3 py-2 text-xs font-bold text-[var(--ds-text-muted)]">
            {{ totalRecords }} registos · {{ representedParents }} grupos
          </span>
          <Link :href="route(config.counterpartRoute)" class="ds-button ds-button-secondary">{{ config.counterpartLabel }}</Link>
        </div>
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
      @execute-action="requestBulkAction"
      @create-record="openCreateDrawer"
      @slideover-on="openEditDrawer"
    />

    <SlideOver v-if="isDrawerOpen" :title="drawerTitle" :description="drawerDescription" @close="closeDrawer">
      <template #content>
        <div class="space-y-7 px-6 py-6">
          <div>
            <p class="ds-kicker">Classificação do conteúdo</p>
            <h2 class="ds-heading mt-1 text-base">Associação e texto publicado</h2>
          </div>

          <div>
            <Combobox
              v-model="form[config.parentField]"
              :title-label="config.parentLabel"
              :placeholder="config.parentPlaceholder"
              :has-error="Boolean(form.errors[config.parentField])"
              :load-options="loadParents"
            />
            <p v-if="form.errors[config.parentField]" class="ds-field-error mt-2">{{ form.errors[config.parentField] }}</p>
          </div>

          <div>
            <label for="knowledge-description" class="ds-field-label mb-2 block">{{ config.fieldLabel }}</label>
            <textarea id="knowledge-description" v-model="form.description" rows="8" class="ds-field min-h-48 resize-y" :placeholder="config.fieldPlaceholder" autofocus></textarea>
            <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
          </div>
        </div>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">Cancelar</button>
          <button type="button" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty" @click="submit">
            <CheckIcon class="h-4 w-4" aria-hidden="true" />
            {{ form.processing ? "A guardar..." : (form.id ? "Actualizar conteúdo" : "Guardar conteúdo") }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="showActionConfirmation"
      :title="confirmationDialogTitle"
      :description="confirmationDialogDescription"
      confirm="Confirmar"
      cancel="Cancelar"
      @canceled="showActionConfirmation = false"
      @close="showActionConfirmation = false"
      @confirmed="confirmAction"
    />
  </div>
</template>
