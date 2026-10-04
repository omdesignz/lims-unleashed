<script setup>
import MatrixForm from "@/Components/matrixes/MatrixForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createEmptyMatrixData } from "@/Components/matrixes/matrixFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const form = useForm(createEmptyMatrixData());

function submit() {
  form.post(route("matrixes.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("matrixes.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Matrizes', url: route('matrixes.index') }, { title: 'Adicionar matriz' }]" title="Adicionar matriz" lede="Agrupe perfis analíticos compativeis e configure preço, imposto e controlo departamental." />

    <section class="ds-card overflow-hidden">
      <MatrixForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('matrixes.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Adicionar matriz" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
