<template>
  <FormulaEditorForm :form="form" mode="edit" @submit="submit" @discard="discard" />
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import FormulaEditorForm from '@/Components/formulas/FormulaEditorForm.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, required: true },
})

const formula = props.record?.data ?? props.record
const form = useForm({
  id: formula.id,
  name: formula.name || '',
  code: formula.code || '',
  category: formula.category || 'general',
  expression: formula.expression || '',
  formula_expression: formula.formula_expression || '',
  variables: (formula.variables || []).map((variable) => ({ ...variable })),
  output_unit: formula.output_unit || '',
  decimal_places: formula.decimal_places ?? 2,
  description: formula.description || '',
  is_active: formula.is_active ?? true,
})

function submit() {
  form.transform((data) => ({
    ...data,
    decimal_places: Number(data.decimal_places),
    variables: data.variables.map((variable) => ({
      ...variable,
      label: variable.label || variable.name,
      unit: variable.unit || '',
      type: variable.type || 'number',
      description: variable.description || '',
    })),
  })).put(route('formulas.update', { formula: formula.id }), {
    preserveScroll: true,
  })
}

function discard() {
  form.reset()
  form.clearErrors()
}
</script>
