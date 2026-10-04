<script setup>
import ParameterForm from "@/Components/parameters/ParameterForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createParameterDataFromRecord } from "@/Components/parameters/parameterFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    required: true,
  },
  formulas: {
    type: Array,
    default: () => [],
  },
});

const parameter = props.record?.data ?? props.record;
const form = useForm(createParameterDataFromRecord(parameter));

function submit() {
  form.put(route("parameters.update", { parameter: parameter.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("parameters.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Parâmetros analíticos', url: route('parameters.index') }, { title: parameter.name }]" :title="parameter.name" lede="Actualize a definição controlada usada nos perfis, worksheets e resultados laboratoriais.">
      <template #badges>
        <span v-if="parameter.code" class="ds-chip font-mono">{{ parameter.code }}</span>
        <span class="ds-chip">{{ parameter.result_is_qualitative ? "Qualitativo" : "Quantitativo" }}</span>
        <span v-if="parameter.requires_calculation" class="ds-chip">Calculado</span>
      </template>
    </PageHeader>

    <section class="ds-card overflow-hidden">
      <ParameterForm :form="form" :formulas="props.formulas" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('parameters.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
