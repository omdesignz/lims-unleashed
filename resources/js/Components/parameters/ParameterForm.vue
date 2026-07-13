<script setup>
import Combobox from "@/Components/combobox.vue";
import ToggleField from "@/Components/base/ToggleField.vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import {
  BanknotesIcon,
  BeakerIcon,
  CalculatorIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  IdentificationIcon,
} from "@heroicons/vue/24/outline";
import { computed, ref, watch } from "vue";

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  formulas: {
    type: Array,
    default: () => [],
  },
});

function mapFormula(formula) {
  return {
    ...formula,
    value: formula.id,
    label: [formula.code, formula.name].filter(Boolean).join(" · ") || `Formula #${formula.id}`,
  };
}

function findSelectedFormula() {
  const formulaId = props.form.formula_id?.value ?? props.form.formula_id;
  const formula = props.formulas.find((item) => String(item.id) === String(formulaId));
  return formula ? mapFormula(formula) : null;
}

const selectedFormula = ref(findSelectedFormula());

const declaredCalculationParameters = computed(() => {
  return (Array.isArray(props.form.calculation_parameters) ? props.form.calculation_parameters : [])
    .map((parameter) => String(parameter).trim())
    .filter(Boolean);
});

const expressionVariables = computed(() => extractVariables(props.form.formula_expression));
const selectedFormulaVariables = computed(() => formulaVariables(selectedFormula.value));

const governanceIssues = computed(() => {
  const issues = [];

  if (props.form.result_is_qualitative && props.form.requires_calculation) {
    issues.push("Resultados qualitativos nao podem depender de calculo automatico.");
  }

  if (!props.form.requires_calculation) {
    return issues;
  }

  if (!selectedFormula.value && !props.form.formula_expression?.trim()) {
    issues.push("Selecione uma formula ativa ou defina uma expressao personalizada.");
  }

  if (!declaredCalculationParameters.value.length) {
    issues.push("A expressao deve declarar pelo menos um parametro de entrada entre chavetas.");
  }

  const authoritativeVariables = expressionVariables.value.length
    ? expressionVariables.value
    : selectedFormulaVariables.value;

  const missingVariables = authoritativeVariables.filter((variable) => !declaredCalculationParameters.value.includes(variable));
  const extraVariables = declaredCalculationParameters.value.filter((variable) => !authoritativeVariables.includes(variable));

  if (missingVariables.length) {
    issues.push(`Faltam entradas declaradas: ${missingVariables.join(", ")}.`);
  }

  if (extraVariables.length) {
    issues.push(`Entradas fora da expressao: ${extraVariables.join(", ")}.`);
  }

  return issues;
});

watch(
  () => props.form.formula_expression,
  (expression) => {
    if (props.form.requires_calculation && expression?.trim()) {
      props.form.calculation_parameters = extractVariables(expression);
      return;
    }

    if (props.form.requires_calculation && selectedFormula.value) {
      props.form.calculation_parameters = formulaVariables(selectedFormula.value);
    }
  },
);

watch(
  () => props.form.requires_calculation,
  (requiresCalculation) => {
    if (!requiresCalculation) {
      selectedFormula.value = null;
      props.form.formula_id = null;
      props.form.formula_expression = "";
      props.form.calculation_parameters = [];
    }
  },
);

function formulaVariables(formula) {
  if (!formula?.variables) {
    return [];
  }

  const variables = Array.isArray(formula.variables) ? formula.variables : Object.values(formula.variables);

  return [...new Set(variables.map((variable) => {
    return typeof variable === "string" ? variable : variable?.name;
  }).filter(Boolean))];
}

function extractVariables(expression) {
  if (!expression) {
    return [];
  }

  const matches = [...String(expression).matchAll(/\{([^}]+)\}/g)];
  return [...new Set(matches.map((match) => match[1].trim()).filter(Boolean))];
}

function loadFormulas(query, setOptions) {
  const normalizedQuery = String(query || "").trim().toLocaleLowerCase("pt-PT");
  const options = props.formulas
    .filter((formula) => {
      if (!normalizedQuery) {
        return true;
      }

      return [formula.name, formula.code]
        .filter(Boolean)
        .some((value) => String(value).toLocaleLowerCase("pt-PT").includes(normalizedQuery));
    })
    .map(mapFormula);

  setOptions(options);
  return options;
}

function selectFormula(formula) {
  selectedFormula.value = formula;
  props.form.formula_id = formula?.value ?? null;

  if (!formula) {
    return;
  }

  props.form.formula_expression = formula.formula_expression || formula.expression || "";
  props.form.decimal_places = Number.isInteger(Number(formula.decimal_places))
    ? Number(formula.decimal_places)
    : props.form.decimal_places;
  props.form.calculation_parameters = formulaVariables(formula);
}

function setResultType(type) {
  const isQualitative = type === "qualitative";
  props.form.result_type = type;
  props.form.result_is_qualitative = isQualitative;

  if (isQualitative) {
    props.form.requires_calculation = false;
  }
}

function loadExemptions(query, setOptions) {
  return loadSelectOptions("/taxexemptions/getExemption", query, setOptions, optionMappers.code);
}

function loadTaxTypes(query, setOptions) {
  return loadSelectOptions(
    "/taxtypes/getTaxType",
    query,
    setOptions,
    (item) => ({ value: item.id, label: item.name, percent: Number(item.percent || 0) }),
  );
}

function updateTaxType(taxType) {
  props.form.tax_id = taxType;
  props.form.tax_percentage = Number(taxType?.percent || 0);
}
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <IdentificationIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Identidade tecnica</p>
          <h2 class="ds-heading mt-1 text-base">Parametro e rastreabilidade</h2>
          <p class="ds-copy mt-1 text-sm">Defina uma designacao inequívoca para perfis, worksheets e certificados.</p>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <label for="parameter-name" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.name') }} <span class="ds-field-required">*</span></label>
          <BaseInput id="parameter-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" placeholder="Nome analitico completo" />
          <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
        </div>

        <div class="ds-field-group">
          <label for="parameter-code" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.code') }}</label>
          <BaseInput id="parameter-code" v-model="form.code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" placeholder="Abreviacao controlada" />
          <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
        </div>

        <div class="ds-field-group lg:col-span-2">
          <label for="parameter-description" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.description') }}</label>
          <textarea id="parameter-description" v-model="form.description" class="ds-field min-h-28 resize-y" :aria-invalid="Boolean(form.errors.description)" placeholder="Matriz, principio do ensaio ou observacoes de utilizacao" />
          <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        </div>
      </div>

      <div class="mt-5 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
        <ToggleField v-model="form.active" id="parameter-active" :label="$t('gestlab.general.labels.parameters.active')" description="Disponibiliza o parametro para novos perfis e fluxos analiticos." />
      </div>
      <p v-if="form.errors.active" class="ds-field-error mt-2">{{ form.errors.active }}</p>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <BanknotesIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Servico e fiscalidade</p>
          <h2 class="ds-heading mt-1 text-base">Prazo, preco e tributacao</h2>
          <p class="ds-copy mt-1 text-sm">Dados usados no planeamento da bancada e na composicao comercial do ensaio.</p>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-2">
        <div class="ds-field-group">
          <label for="parameter-time" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.optimal_analysis_time') }}</label>
          <BaseInput id="parameter-time" v-model="form.optimal_analysis_time" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.optimal_analysis_time)" placeholder="Ex.: 24h ou 3 dias uteis" />
          <p v-if="form.errors.optimal_analysis_time" class="ds-field-error">{{ form.errors.optimal_analysis_time }}</p>
        </div>

        <div class="ds-field-group">
          <label for="parameter-price" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.price') }} <span class="ds-field-required">*</span></label>
          <BaseInput id="parameter-price" v-model.number="form.price" type="number" min="0" step="0.01" class="ds-field" :aria-invalid="Boolean(form.errors.price)" />
          <p v-if="form.errors.price" class="ds-field-error">{{ form.errors.price }}</p>
        </div>
      </div>

      <div class="mt-5 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] divide-y divide-[var(--ds-border)]">
        <ToggleField v-model="form.charge_tax" id="parameter-charge-tax" :label="$t('gestlab.general.labels.parameters.charge_tax')" description="Aplica a categoria fiscal selecionada ao preco do parametro." />
        <ToggleField v-model="form.withhold_tax" id="parameter-withhold-tax" :label="$t('gestlab.general.labels.parameters.withhold_tax')" description="Marca o servico para retencao fiscal quando aplicavel." />
      </div>

      <div class="mt-5 ds-field-group">
        <Combobox
          v-if="form.charge_tax"
          :model-value="form.tax_id"
          :has-error="Boolean(form.errors.tax_id)"
          :load-options="loadTaxTypes"
          :title-label="$t('gestlab.general.labels.parameters.tax_id')"
          placeholder="Selecionar taxa"
          @update:model-value="updateTaxType"
        />
        <Combobox
          v-else
          v-model="form.exemption_id"
          :has-error="Boolean(form.errors.exemption_id)"
          :load-options="loadExemptions"
          :title-label="$t('gestlab.general.labels.parameters.exemption_id')"
          placeholder="Selecionar motivo de isencao"
        />
        <p v-if="form.errors.tax_id" class="ds-field-error">{{ form.errors.tax_id }}</p>
        <p v-if="form.errors.exemption_id" class="ds-field-error">{{ form.errors.exemption_id }}</p>
        <p v-if="form.charge_tax && form.tax_id" class="ds-field-hint">Taxa configurada: {{ form.tax_percentage }}%</p>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <BeakerIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Definicao do resultado</p>
          <h2 class="ds-heading mt-1 text-base">Tipo e tratamento do valor</h2>
          <p class="ds-copy mt-1 text-sm">O tipo selecionado governa a entrada, verificacao e apresentacao do resultado.</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-2 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-1" aria-label="Tipo de resultado">
        <button
          type="button"
          class="rounded-md px-3 py-2.5 text-sm font-bold transition"
          :class="!form.result_is_qualitative ? 'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-950-rgb))]' : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-raised)]'"
          :aria-pressed="!form.result_is_qualitative"
          @click="setResultType('quantitative')"
        >
          Quantitativo
        </button>
        <button
          type="button"
          class="rounded-md px-3 py-2.5 text-sm font-bold transition"
          :class="form.result_is_qualitative ? 'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-300-rgb))] dark:text-[rgb(var(--primary-950-rgb))]' : 'text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-raised)]'"
          :aria-pressed="form.result_is_qualitative"
          @click="setResultType('qualitative')"
        >
          Qualitativo
        </button>
      </div>
      <p v-if="form.errors.result_is_qualitative" class="ds-field-error mt-2">{{ form.errors.result_is_qualitative }}</p>

      <div v-if="!form.result_is_qualitative" class="mt-5 overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)]">
        <ToggleField v-model="form.requires_calculation" id="parameter-calculated" :label="$t('gestlab.general.labels.parameters.requires_calculation')" description="Calcula o resultado a partir de entradas declaradas e de uma expressao controlada." />
      </div>
      <p v-if="form.errors.requires_calculation" class="ds-field-error mt-2">{{ form.errors.requires_calculation }}</p>
    </section>

    <section v-if="form.requires_calculation" class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <CalculatorIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Governanca do calculo</p>
          <h2 class="ds-heading mt-1 text-base">Formula, entradas e precisao</h2>
          <p class="ds-copy mt-1 text-sm">A expressao e os parametros declarados devem permanecer coerentes para liberar o calculo.</p>
        </div>
      </div>

      <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_12rem]">
        <div class="ds-field-group">
          <Combobox
            :model-value="selectedFormula"
            :has-error="Boolean(form.errors.formula_id)"
            :load-options="loadFormulas"
            :title-label="$t('gestlab.general.labels.parameters.formula_id')"
            placeholder="Selecionar formula ativa"
            @update:model-value="selectFormula"
          />
          <p v-if="form.errors.formula_id" class="ds-field-error">{{ form.errors.formula_id }}</p>
        </div>

        <div class="ds-field-group">
          <label for="parameter-decimals" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.decimal_places') }}</label>
          <BaseInput id="parameter-decimals" v-model.number="form.decimal_places" type="number" min="0" max="8" class="ds-field" :aria-invalid="Boolean(form.errors.decimal_places)" />
          <p v-if="form.errors.decimal_places" class="ds-field-error">{{ form.errors.decimal_places }}</p>
        </div>
      </div>

      <div class="mt-5 ds-field-group">
        <label for="parameter-expression" class="ds-field-label">{{ $t('gestlab.general.labels.parameters.formula_expression') }}</label>
        <textarea
          id="parameter-expression"
          v-model="form.formula_expression"
          class="ds-field min-h-28 resize-y font-mono"
          :aria-invalid="Boolean(form.errors.formula_expression)"
          placeholder="Ex.: ({massa} / {volume})"
        />
        <p v-if="form.errors.formula_expression" class="ds-field-error">{{ form.errors.formula_expression }}</p>
        <p v-else class="ds-field-hint">Declare cada entrada entre chavetas, por exemplo <span class="font-mono">{massa}</span>.</p>
      </div>

      <div class="mt-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-sm font-black text-[var(--ds-text)]">Entradas de calculo</p>
            <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">Extraidas automaticamente da expressao ativa.</p>
          </div>
          <span class="ds-chip">{{ declaredCalculationParameters.length }} entradas</span>
        </div>
        <div v-if="declaredCalculationParameters.length" class="mt-3 flex flex-wrap gap-2">
          <span v-for="parameter in declaredCalculationParameters" :key="parameter" class="ds-chip font-mono">{{ parameter }}</span>
        </div>
        <p v-else class="mt-3 text-sm font-semibold text-amber-700 dark:text-amber-200">Nenhuma entrada foi declarada na expressao.</p>
        <p v-if="form.errors.calculation_parameters" class="ds-field-error mt-3">{{ form.errors.calculation_parameters }}</p>
      </div>

      <div
        class="mt-5 rounded-lg border p-4"
        :class="governanceIssues.length
          ? 'border-amber-200 bg-amber-50 dark:border-amber-400/20 dark:bg-amber-500/10'
          : 'border-emerald-200 bg-emerald-50 dark:border-emerald-400/20 dark:bg-emerald-500/10'"
      >
        <div class="flex items-start gap-3">
          <ExclamationTriangleIcon v-if="governanceIssues.length" class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-200" />
          <CheckCircleIcon v-else class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-200" />
          <div>
            <p class="text-sm font-black" :class="governanceIssues.length ? 'text-amber-900 dark:text-amber-100' : 'text-emerald-900 dark:text-emerald-100'">
              {{ governanceIssues.length ? "Revisao necessaria" : "Escopo de calculo controlado" }}
            </p>
            <ul v-if="governanceIssues.length" class="mt-2 space-y-1 text-sm font-semibold text-amber-800 dark:text-amber-200">
              <li v-for="issue in governanceIssues" :key="issue">{{ issue }}</li>
            </ul>
            <p v-else class="mt-1 text-sm font-semibold text-emerald-800 dark:text-emerald-200">A formula, as entradas e o tipo de resultado estao coerentes.</p>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
