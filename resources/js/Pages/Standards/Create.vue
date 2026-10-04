<script setup>
import StandardForm from "@/Components/standards/StandardForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

const form = useForm({ code: "", description: "" });

function submit() {
  form.post(route("standards.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("standards.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Referências normativas', url: route('standards.index') }, { title: 'Adicionar norma ao catálogo' }]" title="Adicionar norma ao catálogo" lede="Registe o código oficial e o contexto de utilização laboratorial." />

    <section class="ds-card overflow-hidden">
      <StandardForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('standards.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Adicionar referência" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
