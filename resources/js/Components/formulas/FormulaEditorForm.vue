<template>
  <form class="space-y-6" @submit.prevent="submit">
    <section class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:px-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <Link :href="route('formulas.index')" class="ds-link inline-flex items-center gap-1.5 text-xs font-bold">
            <ArrowLeftIcon class="h-4 w-4" />
            Biblioteca de fórmulas
          </Link>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))]">
              <CalculatorIcon class="h-5 w-5" />
            </span>
            <div class="min-w-0">
              <p class="ds-kicker">Configuração analítica</p>
              <h1 class="ds-heading mt-1 text-xl sm:text-2xl">{{ isEdit ? `Editar ${form.name || 'fórmula'}` : 'Nova fórmula' }}</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Definição controlada da expressão, entradas, arredondamento e unidade reportável.</p>
            </div>
          </div>
        </div>

        <div class="flex shrink-0 flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" :disabled="!form.isDirty || form.processing" @click="$emit('discard')">
            <ArrowPathIcon class="h-4 w-4" />
            Repor
          </button>
          <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            <CheckIcon class="h-4 w-4" />
            {{ form.processing ? 'A guardar...' : isEdit ? 'Guardar alterações' : 'Criar fórmula' }}
          </button>
        </div>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="px-5 py-4 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Estado</dt>
          <dd class="mt-2"><span class="ds-badge" :class="form.is_active ? 'ds-badge-success' : 'ds-badge-neutral'">{{ form.is_active ? 'Activa' : 'Inactiva' }}</span></dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Categoria</dt>
          <dd class="mt-2"><span class="ds-badge ds-badge-info">{{ categoryLabel }}</span></dd>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0 sm:px-6">
          <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Entradas definidas</dt>
          <dd class="mt-1 text-2xl font-bold tabular-nums text-[var(--ds-text)]">{{ form.variables.length }}</dd>
        </div>
      </dl>
    </section>

    <section v-if="!isEdit" class="ds-panel overflow-hidden">
      <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <p class="ds-kicker">Pontos de partida</p>
        <h2 class="ds-heading mt-1 text-base">Modelos de cálculo</h2>
      </div>
      <div class="grid divide-y divide-[var(--ds-border)] lg:grid-cols-2 lg:divide-x lg:divide-y-0">
        <button
          v-for="template in formulaTemplates"
          :key="template.code"
          type="button"
          class="flex items-start gap-3 px-5 py-4 text-left transition hover:bg-[var(--ds-row-hover)] sm:px-6"
          @click="loadTemplate(template)"
        >
          <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]">
            <BeakerIcon class="h-4 w-4" />
          </span>
          <span class="min-w-0">
            <span class="block text-sm font-bold text-[var(--ds-text)]">{{ template.name }}</span>
            <span class="mt-1 block font-mono text-xs text-[var(--ds-text-soft)]">{{ template.expression }}</span>
            <span class="mt-2 block text-xs font-semibold text-[var(--ds-text-muted)]">{{ template.output_unit }} · {{ template.variables.length }} entradas</span>
          </span>
        </button>
      </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr)_minmax(19rem,0.75fr)]">
      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Identidade</p>
          <h2 class="ds-heading mt-1 text-base">Registo da fórmula</h2>
        </div>
        <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 sm:px-6">
          <div class="ds-field-group sm:col-span-2">
            <label for="formula-name" class="ds-field-label">Nome <span class="ds-field-required">*</span></label>
            <BaseInput id="formula-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" placeholder="Ex.: Teor de humidade" />
            <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
          </div>
          <div class="ds-field-group">
            <label for="formula-code" class="ds-field-label">Código <span class="ds-field-required">*</span></label>
            <BaseInput id="formula-code" v-model="form.code" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.code)" placeholder="teor_humidade" />
            <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
          </div>
          <div class="ds-field-group">
            <label for="formula-category" class="ds-field-label">Categoria <span class="ds-field-required">*</span></label>
            <BaseSelect id="formula-category" v-model="form.category" class="ds-field" :aria-invalid="Boolean(form.errors.category)">
              <option value="general">Geral</option>
              <option value="microbiology">Microbiologia</option>
              <option value="physicochemical">Físico-química</option>
              <option value="custom">Personalizada</option>
            </BaseSelect>
            <p v-if="form.errors.category" class="ds-field-error">{{ form.errors.category }}</p>
          </div>
          <div class="ds-field-group sm:col-span-2">
            <label for="formula-description" class="ds-field-label">Descrição</label>
            <textarea id="formula-description" v-model="form.description" rows="3" class="ds-field" :aria-invalid="Boolean(form.errors.description)" placeholder="Finalidade, método ou contexto de aplicação"></textarea>
            <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
          </div>
        </div>
      </section>

      <section class="ds-panel overflow-hidden">
        <div class="border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
          <p class="ds-kicker">Saída</p>
          <h2 class="ds-heading mt-1 text-base">Regras de reporte</h2>
        </div>
        <div class="grid gap-5 px-5 py-5 sm:grid-cols-2 xl:grid-cols-1 sm:px-6">
          <div class="ds-field-group">
            <label for="formula-output-unit" class="ds-field-label">Unidade do resultado <span class="ds-field-required">*</span></label>
            <BaseInput id="formula-output-unit" v-model="form.output_unit" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.output_unit)" placeholder="%, UFC/g, mg/L" />
            <p v-if="form.errors.output_unit" class="ds-field-error">{{ form.errors.output_unit }}</p>
          </div>
          <div class="ds-field-group">
            <label for="formula-decimal-places" class="ds-field-label">Casas decimais <span class="ds-field-required">*</span></label>
            <BaseInput id="formula-decimal-places" v-model.number="form.decimal_places" type="number" min="0" max="8" step="1" class="ds-field" :aria-invalid="Boolean(form.errors.decimal_places)" />
            <p v-if="form.errors.decimal_places" class="ds-field-error">{{ form.errors.decimal_places }}</p>
          </div>
        </div>
        <div class="border-t border-[var(--ds-border)]">
          <ToggleField
            id="formula-is-active"
            v-model="form.is_active"
            label="Disponível para utilização"
            description="Permite associar esta definição a novos parâmetros e cálculos."
          />
        </div>
      </section>
    </div>

    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div>
          <p class="ds-kicker">Motor de cálculo</p>
          <h2 class="ds-heading mt-1 text-base">Expressão e validação</h2>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="synchronizeVariables">
            <ArrowsRightLeftIcon class="h-4 w-4" />
            Sincronizar entradas
          </button>
          <button type="button" class="ds-button ds-button-secondary" @click="evaluateFormula">
            <PlayIcon class="h-4 w-4" />
            Testar expressão
          </button>
        </div>
      </div>

      <div class="grid lg:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)] lg:divide-x lg:divide-[var(--ds-border)]">
        <div class="px-5 py-5 sm:px-6">
          <div class="ds-field-group">
            <label for="formula-expression" class="ds-field-label">Expressão <span class="ds-field-required">*</span></label>
            <BaseInput
              id="formula-expression"
              v-model="form.expression"
              type="text"
              class="ds-field font-mono"
              :aria-invalid="Boolean(form.errors.expression || form.errors.formula_expression)"
              placeholder="((massa_inicial - massa_final) * 100) / massa_inicial" />
            <p class="ds-field-help">Operadores: +, -, *, /, %, parênteses; funções: sqrt, log, log10, exp, abs, round, ceil, floor, max, min, avg e sum.</p>
            <p v-if="form.errors.expression" class="ds-field-error">{{ form.errors.expression }}</p>
            <p v-if="form.errors.formula_expression" class="ds-field-error">{{ form.errors.formula_expression }}</p>
          </div>

          <div class="mt-5 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-4 py-3">
            <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Formato persistido</p>
            <code class="mt-2 block overflow-x-auto text-sm font-semibold text-[var(--ds-text)]">{{ form.formula_expression || 'A expressão controlada será apresentada aqui.' }}</code>
          </div>
        </div>

        <div class="border-t border-[var(--ds-border)] px-5 py-5 lg:border-t-0 sm:px-6">
          <p class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">Pré-visualização</p>
          <div class="mt-3 min-h-16 overflow-x-auto rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2">
            <FormulaDisplay :formula="form.expression || ''" />
          </div>

          <div v-if="calculation" class="mt-4 rounded-lg border px-4 py-3" :class="calculation.status === 'success' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30' : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30'">
            <p class="text-xs font-bold uppercase" :class="calculation.status === 'success' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'">{{ calculation.status === 'success' ? 'Resultado de teste' : 'Expressão inválida' }}</p>
            <p v-if="calculation.status === 'success'" class="mt-1 text-2xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200">{{ calculation.value }} <span class="text-sm">{{ form.output_unit }}</span></p>
            <p v-else class="mt-1 text-sm font-semibold text-red-800 dark:text-red-200">{{ calculation.message }}</p>
          </div>

          <p v-if="undefinedVariables.length" class="ds-field-error mt-4">Entradas ainda não definidas: {{ undefinedVariables.join(', ') }}</p>
        </div>
      </div>
    </section>

    <section class="ds-panel overflow-hidden">
      <div class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4 sm:px-6">
        <div>
          <p class="ds-kicker">Entradas</p>
          <h2 class="ds-heading mt-1 text-base">Variáveis da fórmula</h2>
        </div>
        <button type="button" class="ds-button ds-button-secondary" @click="addVariable">
          <PlusIcon class="h-4 w-4" />
          Adicionar
        </button>
      </div>

      <div v-if="form.variables.length" class="overflow-x-auto">
        <DataTable class="min-w-[66rem] divide-y divide-[var(--ds-border)]">
          <thead class="ds-table-head">
            <tr>
              <th class="ds-table-heading w-44 px-4 py-3 text-left sm:pl-6">Nome</th>
              <th class="ds-table-heading min-w-56 px-4 py-3 text-left">Etiqueta</th>
              <th class="ds-table-heading w-36 px-4 py-3 text-left">Tipo</th>
              <th class="ds-table-heading w-32 px-4 py-3 text-left">Unidade</th>
              <th class="ds-table-heading w-40 px-4 py-3 text-left">Valor de teste</th>
              <th class="ds-table-heading w-14 px-4 py-3 text-right sm:pr-6"><span class="sr-only">Remover</span></th>
            </tr>
          </thead>
          <tbody class="ds-table-body divide-y divide-[var(--ds-border)]">
            <tr v-for="(variable, index) in form.variables" :key="`${index}-${variable.name}`" class="ds-table-row align-top">
              <td class="ds-table-cell px-4 py-3 sm:pl-6">
                <BaseInput v-model="variable.name" type="text" class="ds-field font-mono" :aria-invalid="Boolean(variableError(index, 'name'))" placeholder="massa_inicial" />
                <p v-if="variableError(index, 'name')" class="ds-field-error">{{ variableError(index, 'name') }}</p>
              </td>
              <td class="ds-table-cell px-4 py-3">
                <BaseInput v-model="variable.label" type="text" class="ds-field" :aria-invalid="Boolean(variableError(index, 'label'))" placeholder="Massa inicial" />
                <p v-if="variableError(index, 'label')" class="ds-field-error">{{ variableError(index, 'label') }}</p>
              </td>
              <td class="ds-table-cell px-4 py-3">
                <BaseSelect v-model="variable.type" class="ds-field">
                  <option value="number">Número</option>
                  <option value="integer">Inteiro</option>
                  <option value="decimal">Decimal</option>
                </BaseSelect>
              </td>
              <td class="ds-table-cell px-4 py-3">
                <BaseInput v-model="variable.unit" type="text" class="ds-field" placeholder="g, mL, °C" />
              </td>
              <td class="ds-table-cell px-4 py-3">
                <BaseInput v-model="variable.value" type="number" :step="variable.type === 'integer' ? 1 : 'any'" class="ds-field tabular-nums" placeholder="0" />
              </td>
              <td class="ds-table-cell px-4 py-3 text-right sm:pr-6">
                <button type="button" class="ds-icon-button hover:!text-red-600" title="Remover variável" @click="removeVariable(index)">
                  <TrashIcon class="h-4 w-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </DataTable>
      </div>

      <div v-else class="p-5 sm:p-8">
        <div class="ds-empty-state px-6 py-8 text-center">
          <VariableIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
          <h3 class="ds-heading mt-3 text-sm">Nenhuma entrada definida</h3>
          <p class="ds-copy mt-1 text-sm">Introduza uma expressão e sincronize as entradas, ou adicione uma variável manualmente.</p>
        </div>
      </div>

      <p v-if="form.errors.variables" class="ds-field-error border-t border-[var(--ds-border)] px-5 py-3 sm:px-6">{{ form.errors.variables }}</p>
    </section>

    <div class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] pt-5 sm:flex-row sm:items-center sm:justify-end">
      <Link :href="route('formulas.index')" class="ds-button ds-button-secondary">Cancelar</Link>
      <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
        <CheckIcon class="h-4 w-4" />
        {{ form.processing ? 'A guardar...' : isEdit ? 'Guardar alterações' : 'Criar fórmula' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  ArrowLeft as ArrowLeftIcon,
  RefreshCw as ArrowPathIcon,
  ArrowLeftRight as ArrowsRightLeftIcon,
  FlaskConical as BeakerIcon,
  Calculator as CalculatorIcon,
  Check as CheckIcon,
  Play as PlayIcon,
  Plus as PlusIcon,
  Trash2 as TrashIcon,
  Variable as VariableIcon,
} from '@lucide/vue'
import ToggleField from '@/Components/base/ToggleField.vue'
import FormulaDisplay from '@/Components/formula-display.vue'

const props = defineProps({
  form: { type: Object, required: true },
  mode: { type: String, default: 'create' },
})

const emit = defineEmits(['submit', 'discard'])

const calculation = ref(null)
const isEdit = computed(() => props.mode === 'edit')
const categoryLabels = {
  general: 'Geral',
  microbiology: 'Microbiologia',
  physicochemical: 'Físico-química',
  custom: 'Personalizada',
}
const categoryLabel = computed(() => categoryLabels[props.form.category] || 'Não definida')

const safeFunctions = {
  sqrt: Math.sqrt,
  log: Math.log,
  log10: Math.log10,
  exp: Math.exp,
  abs: Math.abs,
  round: Math.round,
  ceil: Math.ceil,
  floor: Math.floor,
  max: Math.max,
  min: Math.min,
  avg: (...values) => values.reduce((total, value) => total + value, 0) / values.length,
  sum: (...values) => values.reduce((total, value) => total + value, 0),
}
const reservedIdentifiers = new Set([...Object.keys(safeFunctions), 'PI', 'E'])

const formulaTemplates = [
  {
    name: 'Teor de humidade',
    code: 'teor_humidade',
    category: 'physicochemical',
    expression: '((massa_cadinho + massa_amostra) - massa_final) * 100 / massa_amostra',
    variables: [
      { name: 'massa_cadinho', label: 'Massa do cadinho', unit: 'g', type: 'decimal', value: 44.356 },
      { name: 'massa_amostra', label: 'Massa da amostra', unit: 'g', type: 'decimal', value: 5.007 },
      { name: 'massa_final', label: 'Massa após secagem', unit: 'g', type: 'decimal', value: 48.778 },
    ],
    output_unit: '%',
    decimal_places: 2,
    description: 'Determinação gravimétrica do teor de humidade.',
  },
  {
    name: 'Contagem microbiológica',
    code: 'contagem_microbiologica',
    category: 'microbiology',
    expression: '(colonia_1 + colonia_2) * fator_diluicao / 2',
    variables: [
      { name: 'colonia_1', label: 'Contagem da placa 1', unit: 'UFC', type: 'integer', value: 215 },
      { name: 'colonia_2', label: 'Contagem da placa 2', unit: 'UFC', type: 'integer', value: 14 },
      { name: 'fator_diluicao', label: 'Fator de diluição', unit: '', type: 'number', value: 100 },
    ],
    output_unit: 'UFC/g',
    decimal_places: 0,
    description: 'Média de contagens ajustada pelo fator de diluição.',
  },
]

function extractVariables(expression) {
  const identifiers = String(expression || '').match(/[a-zA-Z_][a-zA-Z0-9_]*/g) || []

  return [...new Set(identifiers)].filter((identifier) => !reservedIdentifiers.has(identifier))
}

const detectedVariables = computed(() => extractVariables(props.form.expression))
const definedVariableNames = computed(() => props.form.variables.map((variable) => variable.name).filter(Boolean))
const undefinedVariables = computed(() => detectedVariables.value.filter((name) => !definedVariableNames.value.includes(name)))

function toFormulaExpression(expression) {
  return String(expression || '').replace(/[a-zA-Z_][a-zA-Z0-9_]*/g, (identifier) => {
    return reservedIdentifiers.has(identifier) ? identifier : `{${identifier}}`
  })
}

function defaultLabel(name) {
  return name
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function synchronizeVariables() {
  props.form.formula_expression = toFormulaExpression(props.form.expression)

  detectedVariables.value.forEach((name) => {
    if (!props.form.variables.some((variable) => variable.name === name)) {
      props.form.variables.push({
        name,
        label: defaultLabel(name),
        value: null,
        unit: '',
        type: 'number',
        description: '',
      })
    }
  })
}

function addVariable() {
  props.form.variables.push({
    name: '',
    label: '',
    value: null,
    unit: '',
    type: 'number',
    description: '',
  })
}

function removeVariable(index) {
  props.form.variables.splice(index, 1)
  calculation.value = null
}

function variableError(index, field) {
  return props.form.errors[`variables.${index}.${field}`]
}

function loadTemplate(template) {
  props.form.name = template.name
  props.form.code = template.code
  props.form.category = template.category
  props.form.expression = template.expression
  props.form.formula_expression = toFormulaExpression(template.expression)
  props.form.variables = template.variables.map((variable) => ({ ...variable, description: '' }))
  props.form.output_unit = template.output_unit
  props.form.decimal_places = template.decimal_places
  props.form.description = template.description
  calculation.value = null
}

function evaluateFormula() {
  synchronizeVariables()
  const expression = String(props.form.expression || '').trim()

  try {
    if (!expression) {
      throw new Error('Introduza uma expressão antes de executar o teste.')
    }

    if (!/^[0-9a-zA-Z_+\-*/%().,\s]+$/.test(expression)) {
      throw new Error('A expressão contém caracteres não permitidos.')
    }

    if (undefinedVariables.value.length) {
      throw new Error(`Defina valores para: ${undefinedVariables.value.join(', ')}.`)
    }

    const variableValues = Object.fromEntries(props.form.variables
      .filter((variable) => variable.name)
      .map((variable) => {
        const value = Number(variable.value)

        if (!Number.isFinite(value)) {
          throw new Error(`O valor de teste de ${variable.name} não é numérico.`)
        }

        return [variable.name, value]
      }))
    const scope = { ...safeFunctions, PI: Math.PI, E: Math.E, ...variableValues }
    const calculate = new Function(...Object.keys(scope), `"use strict"; return (${expression});`)
    const value = Number(calculate(...Object.values(scope)))

    if (!Number.isFinite(value)) {
      throw new Error('O resultado não é finito; verifique divisões por zero e os valores de teste.')
    }

    calculation.value = {
      status: 'success',
      value: value.toFixed(Number(props.form.decimal_places) || 0),
    }
  } catch (error) {
    calculation.value = {
      status: 'error',
      message: error.message || 'Não foi possível avaliar a expressão.',
    }
  }
}

function submit() {
  synchronizeVariables()
  emit('submit')
}

watch(() => props.form.expression, (expression) => {
  props.form.formula_expression = toFormulaExpression(expression)
  calculation.value = null
})

watch(() => props.form.name, (name) => {
  if (!isEdit.value && name && !props.form.code) {
    props.form.code = name
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_|_$/g, '')
  }
})
</script>
