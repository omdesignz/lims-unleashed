<script setup>
import Layout from "@/Shared/Layouts/Layout.vue";
import { computed, ref } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import {
  ArrowLeftIcon,
  CheckIcon,
  DocumentMagnifyingGlassIcon,
  DocumentPlusIcon,
  DocumentTextIcon,
  ExclamationTriangleIcon,
  UserIcon,
} from "@heroicons/vue/24/outline";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  certificate: {
    type: Object,
    default: () => ({}),
  },
  updatableFields: {
    type: Array,
    default: () => [],
  },
  approvers: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  change_type: "",
  change_reason: "",
  iso_section: "8.9.1",
  risk_assessment: "",
  approved_by_id: "",
  fields: {},
});

const selectedFields = ref([]);

const fieldConfigurations = {
  status: {
    label: "Estado do certificado",
    type: "select",
    options: [
      { value: 0, label: "Rascunho" },
      { value: 1, label: "Validado" },
      { value: 2, label: "Retirado" },
      { value: 3, label: "Reemitido" },
    ],
  },
  obs: {
    label: "Observações",
    type: "textarea",
    placeholder: "Descreva a observação actualizada",
  },
  validated_by: {
    label: "Validado por",
    type: "text",
    placeholder: "Nome do responsável pela validação",
  },
  extra_data: {
    label: "Dados adicionais",
    type: "json",
    placeholder: "Insira um objecto JSON válido",
  },
};

const fieldDefinitions = computed(() => {
  const definitions = new Map();

  props.updatableFields.forEach((field) => {
    const configuration = fieldConfigurations[field.name] ?? {};
    definitions.set(field.name, {
      ...field,
      ...configuration,
      label: configuration.label || field.label || field.name,
      type: configuration.type || field.type || "text",
    });
  });

  Object.entries(fieldConfigurations).forEach(([name, configuration]) => {
    if (!definitions.has(name)) {
      definitions.set(name, { name, ...configuration });
    }
  });

  return Array.from(definitions.values());
});

const workflowSteps = computed(() => [
  {
    label: "Classificação",
    complete: Boolean(
      form.change_type &&
      form.change_reason.length >= 10 &&
      form.iso_section &&
      form.risk_assessment,
    ),
  },
  {
    label: "Campos alterados",
    complete: selectedFields.value.length > 0,
  },
  {
    label: "Aprovação",
    complete: Boolean(form.approved_by_id),
    optional: true,
  },
]);

const completedRequiredSteps = computed(
  () => workflowSteps.value.filter((step) => !step.optional && step.complete).length,
);

const isReadyToSubmit = computed(() => {
  return (
    workflowSteps.value
      .filter((step) => !step.optional)
      .every((step) => step.complete) && !form.processing
  );
});

function toggleField(fieldName) {
  const selectedIndex = selectedFields.value.indexOf(fieldName);

  if (selectedIndex >= 0) {
    selectedFields.value.splice(selectedIndex, 1);
    delete form.fields[fieldName];
    return;
  }

  selectedFields.value.push(fieldName);
  form.fields[fieldName] = fieldName === "status" ? 1 : "";
  form.clearErrors("fields");
}

function submitForm() {
  if (!selectedFields.value.length) {
    form.setError("fields", "Seleccione pelo menos um campo para actualizar.");
    return;
  }

  form.post(
    route("qualitycertificates.iso-revisions.store", props.certificate.id),
    {
      preserveScroll: true,
      onSuccess: () => {
        router.visit(
          route("qualitycertificates.iso-revisions.index", props.certificate.id),
        );
      },
    },
  );
}
</script>

<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 max-w-3xl">
          <Link
            :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
            class="ds-table-action -ml-2 mb-3"
          >
            <ArrowLeftIcon class="h-4 w-4" /> Voltar ao histórico </Link>
          <div class="flex flex-wrap items-center gap-2">
            <p class="ds-kicker">Nova alteração controlada</p>
            <span class="ds-chip font-mono">{{ certificate.code || "Sem código" }}</span>
          </div>
          <h1 class="ds-heading mt-2 text-2xl">Criar revisão ISO</h1>
          <p class="ds-copy mt-2 max-w-2xl text-sm"> Classifique a alteração, identifique os campos afectados e registe a justificação exigida pela cadeia de controlo documental. </p>
        </div>

        <div class="lims-status-strip flex items-center gap-3 px-4 py-3">
          <span class="lims-status-dot lims-status-dot-instrument" />
          <div>
            <p class="text-xs font-bold text-[var(--ds-text)]">
              v{{ certificate.current_revision?.version || "1.0" }} actual
            </p>
            <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">
              {{ selectedFields.length }} campo(s) seleccionado(s)
            </p>
          </div>
        </div>
      </div>
    </section>

    <form
      class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem] xl:items-start"
      @submit.prevent="submitForm"
    >
      <div class="space-y-6">
        <section class="ds-panel overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
            <p class="ds-kicker">Classificação da mudanca</p>
            <h2 class="ds-heading mt-2 text-lg">Motivo, norma e risco</h2>
            <p class="ds-copy mt-1 text-sm"> Estes metadados sustentam a decisão e a aprovação da revisão. </p>
          </div>

          <div class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-2">
            <div class="ds-field-group">
              <label class="ds-field-label" for="change-type"> Tipo de alteração <span class="ds-field-required">*</span>
              </label>
              <BaseSelect
                id="change-type"
                v-model="form.change_type"
                class="ds-field"
                :aria-invalid="Boolean(form.errors.change_type)"
                required
              >
                <option value="">Seleccionar tipo</option>
                <option value="UPDATED">Atualizacao</option>
                <option value="CORRECTED">Correcao</option>
                <option value="REISSUED">Reemissao</option>
                <option value="WITHDRAWN">Retirada</option>
              </BaseSelect>
              <p v-if="form.errors.change_type" class="ds-field-error">
                {{ form.errors.change_type }}
              </p>
            </div>

            <div class="ds-field-group">
              <label class="ds-field-label" for="risk-assessment">
                Avaliacao de risco <span class="ds-field-required">*</span>
              </label>
              <BaseSelect
                id="risk-assessment"
                v-model="form.risk_assessment"
                class="ds-field"
                :aria-invalid="Boolean(form.errors.risk_assessment)"
                required
              >
                <option value="">Seleccionar nivel</option>
                <option value="LOW">Baixo</option>
                <option value="MEDIUM">Medio</option>
                <option value="HIGH">Alto</option>
                <option value="CRITICAL">Crítico</option>
              </BaseSelect>
              <p v-if="form.errors.risk_assessment" class="ds-field-error">
                {{ form.errors.risk_assessment }}
              </p>
            </div>

            <div class="ds-field-group">
              <label class="ds-field-label" for="iso-section"> Secção ISO <span class="ds-field-required">*</span>
              </label>
              <div class="relative">
                <DocumentMagnifyingGlassIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" />
                <BaseInput
                  id="iso-section"
                  v-model="form.iso_section"
                  class="ds-field pl-10"
                  :aria-invalid="Boolean(form.errors.iso_section)"
                  placeholder="Ex.: 8.9.1"
                  required
                />
              </div>
              <p v-if="form.errors.iso_section" class="ds-field-error">
                {{ form.errors.iso_section }}
              </p>
            </div>

            <div class="ds-field-group lg:row-span-2">
              <label class="ds-field-label" for="change-reason"> Justificação da alteração <span class="ds-field-required">*</span>
              </label>
              <textarea
                id="change-reason"
                v-model="form.change_reason"
                class="ds-field min-h-36"
                :aria-invalid="Boolean(form.errors.change_reason)"
                placeholder="Descreva o problema, a decisão e o resultado esperado"
                required
              />
              <div class="flex items-center justify-between gap-3">
                <p class="ds-field-hint">Minimo de 10 caracteres.</p>
                <p class="ds-field-hint font-mono">{{ form.change_reason.length }}/1000</p>
              </div>
              <p v-if="form.errors.change_reason" class="ds-field-error">
                {{ form.errors.change_reason }}
              </p>
            </div>
          </div>
        </section>

        <section class="ds-panel overflow-hidden">
          <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
              <p class="ds-kicker">Âmbito da revisão</p>
              <h2 class="ds-heading mt-2 text-lg">Campos a actualizar</h2>
              <p class="ds-copy mt-1 text-sm"> Apenas os campos seleccionados serão incluidos na nova versão. </p>
            </div>
            <span class="ds-chip">{{ selectedFields.length }} seleccionado(s)</span>
          </div>

          <div class="divide-y divide-[var(--ds-border)]">
            <article
              v-for="field in fieldDefinitions"
              :key="field.name"
              class="px-5 py-4 sm:px-6"
            >
              <div class="flex items-start gap-3">
                <button
                  type="button"
                  role="checkbox"
                  :aria-checked="selectedFields.includes(field.name)"
                  :class="[
                    'mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded border transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-[var(--ds-focus)]',
                    selectedFields.includes(field.name)
                      ? 'border-[rgb(var(--primary-700-rgb))] bg-[rgb(var(--primary-700-rgb))] text-white dark:border-[rgb(var(--primary-300-rgb))] dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-950-rgb))]'
                      : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel-raised)]',
                  ]"
                  @click="toggleField(field.name)"
                >
                  <CheckIcon v-if="selectedFields.includes(field.name)" class="h-3.5 w-3.5" />
                </button>

                <div class="min-w-0 flex-1">
                  <button
                    type="button"
                    class="text-left text-sm font-bold text-[var(--ds-text)]"
                    @click="toggleField(field.name)"
                  >
                    {{ field.label }}
                  </button>
                  <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                    Campo: <span class="font-mono">{{ field.name }}</span>
                  </p>

                  <div v-if="selectedFields.includes(field.name)" class="mt-4">
                    <BaseSelect
                      v-if="field.type === 'select'"
                      v-model="form.fields[field.name]"
                      class="ds-field"
                    >
                      <option
                        v-for="option in field.options"
                        :key="option.value"
                        :value="option.value"
                      >
                        {{ option.label }}
                      </option>
                    </BaseSelect>
                    <textarea
                      v-else-if="field.type === 'textarea' || field.type === 'json'"
                      v-model="form.fields[field.name]"
                      class="ds-field min-h-28 font-mono"
                      :placeholder="field.placeholder"
                    />
                    <BaseInput
                      v-else
                      v-model="form.fields[field.name]"
                      class="ds-field"
                      :placeholder="field.placeholder"
                    />
                    <p v-if="form.errors[`fields.${field.name}`]" class="ds-field-error mt-2">
                      {{ form.errors[`fields.${field.name}`] }}
                    </p>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div v-if="!fieldDefinitions.length" class="ds-empty-state m-5 p-8 text-center">
            <DocumentTextIcon class="mx-auto h-6 w-6 text-[var(--ds-text-soft)]" />
            <p class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]"> Nenhum campo disponível para revisão. </p>
          </div>

          <p v-if="form.errors.fields" class="ds-field-error border-t border-[var(--ds-border)] px-5 py-3 sm:px-6">
            {{ form.errors.fields }}
          </p>
        </section>
      </div>

      <aside class="space-y-6 xl:sticky xl:top-24">
        <section class="ds-command-surface overflow-hidden">
          <div class="border-b border-[var(--ds-border)] px-5 py-4">
            <p class="ds-kicker">Progresso</p>
            <h2 class="ds-heading mt-2 text-base">Preparacao da revisão</h2>
            <p class="ds-copy mt-1 text-xs">
              {{ completedRequiredSteps }}/2 etapas obrigatórias concluídas </p>
          </div>
          <ol class="px-5 py-5">
            <li
              v-for="(step, index) in workflowSteps"
              :key="step.label"
              class="relative flex gap-3 pb-5 last:pb-0"
            >
              <div class="relative flex w-7 shrink-0 justify-center">
                <span
                  v-if="index !== workflowSteps.length - 1"
                  class="absolute bottom-0 top-7 w-px bg-[var(--ds-border-strong)]"
                />
                <span
                  :class="[
                    'grid h-7 w-7 place-items-center rounded-full border text-xs font-bold',
                    step.complete
                      ? 'border-emerald-500 bg-emerald-500 text-white'
                      : 'border-[var(--ds-border-strong)] bg-[var(--ds-panel-raised)] text-[var(--ds-text-muted)]',
                  ]"
                >
                  <CheckIcon v-if="step.complete" class="h-4 w-4" />
                  <span v-else>{{ index + 1 }}</span>
                </span>
              </div>
              <div class="pt-1">
                <p class="text-sm font-bold text-[var(--ds-text)]">{{ step.label }}</p>
                <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                  {{ step.optional ? "Opcional" : step.complete ? "Concluída" : "Pendente" }}
                </p>
              </div>
            </li>
          </ol>
        </section>

        <section class="ds-card p-5">
          <div class="ds-field-group">
            <label class="ds-field-label" for="approver">
              Aprovador designado
            </label>
            <div class="relative">
              <UserIcon class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[var(--ds-text-soft)]" />
              <BaseSelect
                id="approver"
                v-model="form.approved_by_id"
                class="ds-field pl-10"
                :aria-invalid="Boolean(form.errors.approved_by_id)"
              >
                <option value="">Sem aprovador designado</option>
                <option
                  v-for="approver in approvers"
                  :key="approver.id"
                  :value="approver.id"
                >
                  {{ approver.name }}
                </option>
              </BaseSelect>
            </div>
            <p class="ds-field-hint"> A designação pode ser concluída posteriormente, conforme o fluxo de aprovação. </p>
            <p v-if="form.errors.approved_by_id" class="ds-field-error">
              {{ form.errors.approved_by_id }}
            </p>
          </div>
        </section>

        <section class="lims-status-strip p-5">
          <div class="flex items-start gap-3">
            <span class="lims-status-dot lims-status-dot-hold mt-1" />
            <div>
              <h2 class="ds-heading text-sm">Impacto documental</h2>
              <p class="ds-copy mt-1 text-xs"> A submissão cria uma nova versão imutável e actualiza os campos seleccionados do certificado. </p>
            </div>
          </div>
        </section>

        <div class="grid gap-2">
          <button
            type="submit"
            class="ds-button ds-button-primary"
            :disabled="!isReadyToSubmit"
          >
            <DocumentPlusIcon class="h-4 w-4" />
            {{ form.processing ? "A criar..." : "Criar revisão" }}
          </button>
          <Link
            :href="route('qualitycertificates.iso-revisions.index', certificate.id)"
            class="ds-button ds-button-secondary"
          >
            Cancelar
          </Link>
        </div>

        <div v-if="form.hasErrors" class="ds-empty-state p-4">
          <div class="flex items-start gap-3">
            <ExclamationTriangleIcon class="h-5 w-5 shrink-0 text-[var(--lims-critical)]" />
            <p class="text-xs font-semibold text-[var(--ds-text-muted)]">
              Reveja os campos assinalados antes de submeter.
            </p>
          </div>
        </div>
      </aside>
    </form>
  </div>
</template>
