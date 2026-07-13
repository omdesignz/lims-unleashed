<template>
  <FormulaEditorForm :form="form" mode="create" @submit="submit" @discard="discard" />
</template>

<script setup>
import { router, useForm } from '@inertiajs/vue3'
import FormulaEditorForm from '@/Components/formulas/FormulaEditorForm.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const form = useForm({
  name: '',
  code: '',
  category: 'general',
  expression: '',
  formula_expression: '',
  variables: [],
  output_unit: '',
  decimal_places: 2,
  description: '',
  is_active: true,
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
  })).post(route('formulas.store'), {
    preserveScroll: true,
  })
}

function discard() {
  if (form.isDirty) {
    form.reset()
    form.clearErrors()
    return
  }

  router.visit(route('formulas.index'))
}
</script>
