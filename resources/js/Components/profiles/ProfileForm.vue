<script setup>
import ToggleField from "@/Components/base/ToggleField.vue";
import Combobox from "@/Components/combobox.vue";
import { createEmptyProfileParameter } from "@/Components/profiles/profileFormData";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import {
  BeakerIcon,
  CalculatorIcon,
  CheckCircleIcon,
  ClockIcon,
  DocumentCheckIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
  PlusIcon,
  ScaleIcon,
  TrashIcon,
} from "@heroicons/vue/24/outline";
import { computed, watch } from "vue";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
});

const totalPrice = computed(() => props.form.parameters.reduce((total, parameter) => {
  return total + Number(parameter.price || 0);
}, 0));

const duplicateParameterLabels = computed(() => {
  const counts = new Map();

  props.form.parameters.forEach((parameter) => {
    const id = parameter.parameter_id?.value;
    if (id) counts.set(id, (counts.get(id) || 0) + 1);
  });

  return props.form.parameters
    .filter((parameter) => counts.get(parameter.parameter_id?.value) > 1)
    .map((parameter) => parameter.parameter_id?.label)
    .filter((label, index, labels) => label && labels.indexOf(label) === index);
});

const invalidRangeLabels = computed(() => props.form.parameters
  .filter((parameter) => parameter.min_ref_value !== "" && parameter.max_ref_value !== "" && Number(parameter.max_ref_value) < Number(parameter.min_ref_value))
  .map((parameter, index) => parameter.parameter_id?.label || `Parametro ${index + 1}`));

const governanceIssues = computed(() => {
  const issues = [];

  if (props.form.category_id && !props.form.category_id.department_id) {
    issues.push("A categoria analitica nao esta vinculada a um departamento.");
  }

  if (duplicateParameterLabels.value.length) {
    issues.push(`Parametros repetidos: ${duplicateParameterLabels.value.join(", ")}.`);
  }

  if (invalidRangeLabels.value.length) {
    issues.push(`Faixas de referencia invalidas: ${invalidRangeLabels.value.join(", ")}.`);
  }

  return issues;
});

watch(totalPrice, (price) => {
  props.form.price = Number(price.toFixed(2));
}, { immediate: true });

function addParameter() {
  props.form.parameters.push(createEmptyProfileParameter());
}

function selectParameter(parameter, selected) {
  parameter.parameter_id = selected;
  parameter.optimal_analysis_time = selected?.optimal_analysis_time || "";
  parameter.price = Number(selected?.price || 0);
}

function addDilution(parameter) {
  if (!Array.isArray(parameter.extra_data?.dilutions)) {
    parameter.extra_data = { ...(parameter.extra_data || {}), dilutions: [] };
  }

  parameter.extra_data.dilutions.push({ quantity: "", ratio: "" });
}

function loadCategories(query, setOptions) {
  return loadSelectOptions("/analysiscategories/getAnalysisCategory", query, setOptions, (item) => ({
    value: item.id,
    label: [item.code, item.name].filter(Boolean).join(" · "),
    name: item.name,
    department_id: item.department_id,
    department_name: item.department_name,
  }));
}

function loadParameters(query, setOptions) {
  return loadSelectOptions("/parameters/getParameter", query, setOptions, (item) => ({
    value: item.id,
    label: [item.code, item.name].filter(Boolean).join(" · "),
    code: item.code,
    active: Boolean(item.active),
    optimal_analysis_time: item.optimal_analysis_time,
    price: Number(item.price || 0),
  }));
}

const loadUnits = (query, setOptions) => loadSelectOptions("/units/getUnit", query, setOptions, optionMappers.code);
const loadProtocols = (query, setOptions) => loadSelectOptions("/protocols/getProtocol", query, setOptions, optionMappers.code);
const loadStandards = (query, setOptions) => loadSelectOptions("/standards/getStandard", query, setOptions, optionMappers.code);
const loadNwps = (query, setOptions) => loadSelectOptions("/nwps/getNwp", query, setOptions, optionMappers.code);
const loadResultCategories = (query, setOptions) => loadSelectOptions("/resultcategories/getResultCategory", query, setOptions, optionMappers.name);
const loadFormulas = (query, setOptions) => loadSelectOptions("/formulas/getFormula", query, setOptions, (item) => ({
  value: item.id,
  label: [item.code, item.name].filter(Boolean).join(" · "),
  expression: item.expression,
}));
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <IdentificationIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Identidade e escopo</p>
          <h2 class="ds-heading mt-1 text-base">Perfil analitico controlado</h2>
          <p class="ds-copy mt-1 text-sm">Agrupe ensaios que partilham uma finalidade, matriz e responsabilidade laboratorial.</p>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <label for="profile-name" class="ds-field-label">{{ $t('gestlab.general.labels.profiles.name') }} <span class="ds-field-required">*</span></label>
          <BaseInput id="profile-name" v-model="form.name" class="ds-field" type="text" :aria-invalid="Boolean(form.errors.name)" placeholder="Nome do pacote analitico" />
          <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
        </div>

        <div class="ds-field-group">
          <label for="profile-code" class="ds-field-label">{{ $t('gestlab.general.labels.profiles.code') }}</label>
          <BaseInput id="profile-code" v-model="form.code" class="ds-field font-mono" type="text" :aria-invalid="Boolean(form.errors.code)" placeholder="Codigo controlado" />
          <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
        </div>

        <div class="ds-field-group lg:col-span-2">
          <label for="profile-description" class="ds-field-label">{{ $t('gestlab.general.labels.profiles.description') }}</label>
          <textarea id="profile-description" v-model="form.description" class="ds-field min-h-24 resize-y" :aria-invalid="Boolean(form.errors.description)" placeholder="Finalidade, matriz e criterio de utilizacao" />
          <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        </div>

        <div class="ds-field-group lg:col-span-2">
          <label class="ds-field-label">{{ $t('gestlab.general.labels.profiles.category_id_1') }} <span class="ds-field-required">*</span></label>
          <Combobox v-model="form.category_id" :load-options="loadCategories" :has-error="Boolean(form.errors.category_id)" placeholder="Pesquisar categoria analitica" />
          <p v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</p>
          <p v-else-if="form.category_id?.department_name" class="ds-field-hint">Departamento responsavel: {{ form.category_id.department_name }}</p>
        </div>
      </div>

      <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
          <p class="ds-kicker">Ensaios</p>
          <p class="ds-heading mt-1 text-lg">{{ form.parameters.length }}</p>
        </div>
        <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
          <p class="ds-kicker">Preco composto</p>
          <p class="ds-heading mt-1 text-lg">{{ totalPrice.toLocaleString('pt-PT', { minimumFractionDigits: 2 }) }} AOA</p>
        </div>
        <div class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
          <p class="ds-kicker">Governanca</p>
          <p class="mt-1 flex items-center gap-1.5 text-sm font-bold" :class="governanceIssues.length ? 'text-amber-700 dark:text-amber-300' : 'text-emerald-700 dark:text-emerald-300'">
            <ExclamationTriangleIcon v-if="governanceIssues.length" class="h-4 w-4" />
            <CheckCircleIcon v-else class="h-4 w-4" />
            {{ governanceIssues.length ? `${governanceIssues.length} pendencia(s)` : 'Escopo coerente' }}
          </p>
        </div>
      </div>

      <div v-if="governanceIssues.length" class="mt-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100">
        <p v-for="issue in governanceIssues" :key="issue">{{ issue }}</p>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
          <BeakerIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Composicao do perfil</p>
            <h2 class="ds-heading mt-1 text-base">Ensaios, metodo e criterios</h2>
            <p class="ds-copy mt-1 text-sm">Cada linha define o mensurando, rastreabilidade, faixa de referencia e classificacao do resultado.</p>
          </div>
        </div>
        <button type="button" class="ds-button ds-button-secondary shrink-0" @click="addParameter">
          <PlusIcon class="h-4 w-4" />
          Adicionar ensaio
        </button>
      </div>

      <p v-if="form.errors.parameters" class="ds-field-error mt-4">{{ form.errors.parameters }}</p>

      <div v-if="!form.parameters.length" class="ds-empty-state mt-5 py-10 text-center">
        <BeakerIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
        <h3 class="ds-heading mt-3 text-sm">Nenhum ensaio configurado</h3>
        <p class="ds-copy mx-auto mt-1 max-w-md text-sm">Adicione pelo menos um parametro ativo para tornar este perfil utilizavel na entrada de amostras.</p>
        <button type="button" class="ds-button ds-button-primary mt-4" @click="addParameter">
          <PlusIcon class="h-4 w-4" />
          Adicionar primeiro ensaio
        </button>
      </div>

      <div v-else class="mt-5 space-y-4">
        <article v-for="(parameter, index) in form.parameters" :key="index" class="overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)]">
          <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3 sm:px-5">
            <div class="flex min-w-0 items-start gap-3">
              <span class="grid h-7 w-7 shrink-0 place-items-center rounded-md bg-[rgb(var(--primary-700-rgb))] text-xs font-bold text-white">{{ index + 1 }}</span>
              <div class="min-w-0">
                <h3 class="ds-heading truncate text-sm">{{ parameter.parameter_id?.label || 'Ensaio por configurar' }}</h3>
                <p class="ds-copy mt-0.5 text-xs">
                  {{ parameter.unit_id?.label || 'Sem unidade' }}
                  <span aria-hidden="true"> · </span>
                  {{ Number(parameter.price || 0).toLocaleString('pt-PT', { minimumFractionDigits: 2 }) }} AOA
                </p>
              </div>
            </div>
            <button type="button" class="ds-table-action ds-table-action-danger shrink-0" title="Remover ensaio" @click="form.parameters.splice(index, 1)">
              <TrashIcon class="h-4 w-4" />
              <span class="sr-only">Remover ensaio</span>
            </button>
          </header>

          <div class="grid gap-5 px-4 py-5 sm:px-5 lg:grid-cols-6">
            <div class="ds-field-group lg:col-span-4">
              <label class="ds-field-label">{{ $t('gestlab.general.labels.profiles.parameter_id') }} <span class="ds-field-required">*</span></label>
              <Combobox :model-value="parameter.parameter_id" :load-options="loadParameters" :has-error="Boolean(form.errors[`parameters.${index}.parameter_id`])" placeholder="Pesquisar parametro ativo" @update:model-value="selectParameter(parameter, $event)" />
              <p v-if="form.errors[`parameters.${index}.parameter_id`]" class="ds-field-error">{{ form.errors[`parameters.${index}.parameter_id`] }}</p>
            </div>
            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label"><ClockIcon class="inline h-4 w-4" /> Prazo otimo</label>
              <BaseInput v-model="parameter.optimal_analysis_time" class="ds-field" type="text" readonly placeholder="Definido no parametro" />
            </div>

            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label">{{ $t('gestlab.general.labels.profiles.unit_id') }} <span class="ds-field-required">*</span></label>
              <Combobox v-model="parameter.unit_id" :load-options="loadUnits" :has-error="Boolean(form.errors[`parameters.${index}.unit_id`])" placeholder="Unidade" />
              <p v-if="form.errors[`parameters.${index}.unit_id`]" class="ds-field-error">{{ form.errors[`parameters.${index}.unit_id`] }}</p>
            </div>
            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label">Categoria do resultado <span class="ds-field-required">*</span></label>
              <Combobox v-model="parameter.category_id" :load-options="loadResultCategories" :has-error="Boolean(form.errors[`parameters.${index}.category_id`])" placeholder="Categoria" />
              <p v-if="form.errors[`parameters.${index}.category_id`]" class="ds-field-error">{{ form.errors[`parameters.${index}.category_id`] }}</p>
            </div>
            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label"><CalculatorIcon class="inline h-4 w-4" /> Formula</label>
              <Combobox v-model="parameter.formula_id" :load-options="loadFormulas" :has-error="Boolean(form.errors[`parameters.${index}.formula_id`])" placeholder="Opcional" />
              <p v-if="form.errors[`parameters.${index}.formula_id`]" class="ds-field-error">{{ form.errors[`parameters.${index}.formula_id`] }}</p>
            </div>

            <div class="lg:col-span-6">
              <div class="grid gap-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 lg:grid-cols-3">
                <div class="ds-field-group">
                  <label class="ds-field-label">Limite minimo <span class="ds-field-required">*</span></label>
                  <BaseInput v-model="parameter.min_ref_value" class="ds-field" type="number" step="any" :aria-invalid="Boolean(form.errors[`parameters.${index}.min_ref_value`])" />
                  <p v-if="form.errors[`parameters.${index}.min_ref_value`]" class="ds-field-error">{{ form.errors[`parameters.${index}.min_ref_value`] }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label">Limite maximo</label>
                  <BaseInput v-model="parameter.max_ref_value" class="ds-field" type="number" step="any" :aria-invalid="Boolean(form.errors[`parameters.${index}.max_ref_value`])" />
                  <p v-if="form.errors[`parameters.${index}.max_ref_value`]" class="ds-field-error">{{ form.errors[`parameters.${index}.max_ref_value`] }}</p>
                </div>
                <div class="ds-field-group">
                  <label class="ds-field-label"><ScaleIcon class="inline h-4 w-4" /> Origem da referencia</label>
                  <BaseInput v-model="parameter.ref_val_origin" class="ds-field" type="text" placeholder="Norma, metodo ou populacao" />
                  <p v-if="form.errors[`parameters.${index}.ref_val_origin`]" class="ds-field-error">{{ form.errors[`parameters.${index}.ref_val_origin`] }}</p>
                </div>
              </div>
            </div>

            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label">Protocolo / metodo</label>
              <Combobox v-model="parameter.protocol_id" :load-options="loadProtocols" placeholder="Opcional" />
            </div>
            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label"><DocumentCheckIcon class="inline h-4 w-4" /> Norma</label>
              <Combobox v-model="parameter.standard_id" :load-options="loadStandards" placeholder="Opcional" />
            </div>
            <div class="ds-field-group lg:col-span-2">
              <label class="ds-field-label">Procedimento interno</label>
              <Combobox v-model="parameter.nwp_id" :load-options="loadNwps" placeholder="Opcional" />
            </div>

            <div class="ds-field-group lg:col-span-6">
              <label class="ds-field-label">{{ form.category_id?.value === 1 ? 'Analitos' : $t('gestlab.general.labels.profiles.dilutions') }}</label>
              <BaseInput v-model="parameter.dilutions" class="ds-field" type="text" :placeholder="form.category_id?.value === 1 ? 'Lista de analitos' : 'Plano geral de diluicao'" />
            </div>

            <div v-if="form.category_id?.value === 2" class="lg:col-span-6">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="ds-field-label">Etapas de diluicao</p>
                  <p class="ds-field-hint">Registe quantidade e proporcao para execucao consistente na bancada.</p>
                </div>
                <button type="button" class="ds-button ds-button-secondary" @click="addDilution(parameter)">
                  <PlusIcon class="h-4 w-4" />
                  Etapa
                </button>
              </div>
              <div v-if="parameter.extra_data?.dilutions?.length" class="mt-3 overflow-hidden rounded-lg border border-[var(--ds-border)]">
                <div v-for="(dilution, dilutionIndex) in parameter.extra_data.dilutions" :key="dilutionIndex" class="grid gap-3 border-b border-[var(--ds-border)] p-3 last:border-b-0 sm:grid-cols-[1fr_1fr_auto]">
                  <BaseInput v-model="dilution.quantity" class="ds-field" type="text" placeholder="Quantidade" />
                  <BaseInput v-model="dilution.ratio" class="ds-field" type="text" placeholder="Proporcao" />
                  <button type="button" class="ds-table-action ds-table-action-danger" title="Remover etapa" @click="parameter.extra_data.dilutions.splice(dilutionIndex, 1)">
                    <TrashIcon class="h-4 w-4" />
                    <span class="sr-only">Remover etapa</span>
                  </button>
                </div>
              </div>
            </div>

            <div class="lg:col-span-6 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
              <ToggleField v-model="parameter.count" :id="`profile-parameter-count-${index}`" label="Contabilizar no resultado do perfil" description="Inclui este ensaio nas verificacoes e no resultado consolidado do perfil." />
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>
