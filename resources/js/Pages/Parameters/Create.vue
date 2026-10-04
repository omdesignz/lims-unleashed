<script setup>
import ParameterForm from "@/Components/parameters/ParameterForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createEmptyParameterData } from "@/Components/parameters/parameterFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

defineProps({
  formulas: {
    type: Array,
    default: () => [],
  },
});

const form = useForm(createEmptyParameterData());

function submit() {
  form.post(route("parameters.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("parameters.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Parâmetros analíticos', url: route('parameters.index') }, { title: 'Adicionar parâmetro analítico' }]" title="Adicionar parâmetro analítico" lede="Configure identidade, prazo, preço, tipo de resultado e cálculo numa definição controlada." />

    <section class="ds-card overflow-hidden">
      <ParameterForm :form="form" :formulas="formulas" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('parameters.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Adicionar parâmetro" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
