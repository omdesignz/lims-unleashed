<template>
  <div class="pl-page space-y-6">
    <PageHeader title="Variáveis de fórmula" lede="Constantes reutilizáveis associadas às fórmulas controladas do laboratório.">
      <template #actions>
        <Link :href="route('formulas.index')" class="ds-button ds-button-secondary">
          <CalculatorIcon class="h-4 w-4" />
          Biblioteca de fórmulas
        </Link>
        <button v-if="hasPermission('add_variables')" type="button" class="ds-button ds-button-primary" @click="openCreatePanel">
          <PlusIcon class="h-4 w-4" />
          Nova variável
        </button>
      </template>
    </PageHeader>

    <dl class="pl-cells">
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Registos</dt>
        <dd class="pl-cell-value">{{ totalRecords }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Activas nesta página</dt>
        <dd class="pl-cell-value">{{ activeRecords }}</dd>
      </div>
      <div class="pl-cell">
        <dt class="pl-k pl-muted">Fórmulas representadas</dt>
        <dd class="pl-cell-value">{{ representedFormulas }}</dd>
      </div>
    </dl>

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

    <SlideOver v-if="isPanelOpen" :title="panelTitle" :description="panelDescription" @close="closePanel">
      <template #content>
        <form id="variable-form" class="divide-y divide-[var(--ds-border)]" @submit.prevent="submit">
          <section class="px-5 py-5 sm:px-6">
            <p class="ds-kicker">Identificação</p>
            <h2 class="ds-heading mt-1 text-base">Dados da variável</h2>
            <p class="ds-copy mt-1 text-sm">Use um nome reconhecível e um valor compatível com a expressão onde será aplicado.</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
              <div class="ds-field-group">
                <label for="variable-name" class="ds-field-label">Nome <span class="ds-field-required">*</span></label>
                <BaseInput id="variable-name" v-model="form.name" type="text" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.name)" placeholder="Ex.: fator_diluicao" />
                <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
              </div>
              <div class="ds-field-group">
                <label for="variable-value" class="ds-field-label">Valor <span class="ds-field-required">*</span></label>
                <BaseInput id="variable-value" v-model="form.value" type="text" inputmode="decimal" class="ds-field font-mono" :aria-invalid="Boolean(form.errors.value)" placeholder="Ex.: 100" />
                <p v-if="form.errors.value" class="ds-field-error">{{ form.errors.value }}</p>
              </div>
            </div>
          </section>

          <section class="px-5 py-5 sm:px-6">
            <div class="ds-field-group">
              <label for="variable-formula" class="ds-field-label">Fórmula associada <span class="ds-field-required">*</span></label>
              <BaseSelect id="variable-formula" v-model="form.formula_id" class="ds-field" :aria-invalid="Boolean(form.errors.formula_id)">
                <option :value="null">Seleccione uma fórmula</option>
                <option v-for="formula in formulas" :key="formula.value" :value="formula.value">{{ formula.label }}</option>
              </BaseSelect>
              <p class="ds-field-help">A associação documenta o contexto de cálculo e evita constantes sem utilização conhecida.</p>
              <p v-if="form.errors.formula_id" class="ds-field-error">{{ form.errors.formula_id }}</p>
            </div>
          </section>
        </form>
      </template>

      <template #action_buttons>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="ds-button ds-button-secondary" @click="closePanel">Cancelar</button>
          <button type="submit" form="variable-form" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
            {{ form.processing ? 'A guardar...' : form.id ? 'Guardar alterações' : 'Adicionar variável' }}
          </button>
        </div>
      </template>
    </SlideOver>

    <ConfirmDialog
      v-if="selectedAction"
      :title="confirmationTitle"
      :description="confirmationDescription"
      :variant="selectedAction === 'restore' ? 'question' : 'danger'"
      confirm="Sim"
      cancel="Não"
      @confirmed="executeBulkAction"
      @canceled="selectedAction = null"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import { Calculator as CalculatorIcon, Plus as PlusIcon } from '@lucide/vue'
import { trans } from 'laravel-vue-i18n'
import ConfirmDialog from '@/Components/confirm-dialog.vue'
import RecordsTable from '@/Components/records-table.vue'
import SlideOver from '@/Components/slide-over.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  formulas: { type: Array, default: () => [] },
  slideOverEdit: { type: Boolean, default: false },
})

const { hasPermission } = usePermission()
const isPanelOpen = ref(false)
const selectedAction = ref(null)
const form = useForm({
  id: null,
  name: '',
  value: '',
  formula_id: null,
})

const pageRecords = computed(() => props.record.data || [])
const totalRecords = computed(() => props.record.meta?.total ?? pageRecords.value.length)
const activeRecords = computed(() => pageRecords.value.filter((record) => !record.deleted).length)
const representedFormulas = computed(() => new Set(pageRecords.value.map((record) => record.formula_id?.value).filter(Boolean)).size)
const panelTitle = computed(() => form.id ? `Editar variável ${form.name}` : 'Nova variável')
const panelDescription = computed(() => form.id
  ? 'Actualize o valor ou a associação da variável controlada.'
  : 'Registe uma constante reutilizável para os cálculos laboratoriais.')
const confirmationTitle = computed(() => trans(`gestlab.actions.confirmation_dialog_title.${selectedAction.value}`))
const confirmationDescription = computed(() => trans(`gestlab.actions.confirmation_dialog_description.${selectedAction.value}`))

const actions = [
  { id: null, label: 'gestlab.actions.bulk_actions_text' },
  { id: 'delete', label: 'gestlab.actions.delete' },
  { id: 'restore', label: 'gestlab.actions.restore' },
]

function resetForm() {
  form.defaults({ id: null, name: '', value: '', formula_id: null })
  form.reset()
  form.clearErrors()
}

function openCreatePanel() {
  resetForm()
  isPanelOpen.value = true
}

function openEditPanel(record) {
  form.defaults({
    id: record.id,
    name: record.name || '',
    value: record.value || '',
    formula_id: record.formula_id?.value ?? null,
  })
  form.reset()
  form.clearErrors()
  isPanelOpen.value = true
}

function closePanel() {
  isPanelOpen.value = false
  form.clearErrors()
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      closePanel()
      resetForm()
    },
  }

  if (form.id) {
    form.put(route('variables.update', { variableVariable: form.id }), options)
    return
  }

  form.post(route('variables.store'), options)
}

function requestBulkAction(action) {
  selectedAction.value = action
}

function executeBulkAction() {
  const recordIds = pageRecords.value.filter((record) => record.selected).map((record) => record.id)

  if (!recordIds.length || !['delete', 'restore'].includes(selectedAction.value)) {
    selectedAction.value = null
    return
  }

  router.get(route(`variables.${selectedAction.value}`), { recordIds }, {
    preserveScroll: true,
    onFinish: () => {
      selectedAction.value = null
    },
  })
}
</script>
