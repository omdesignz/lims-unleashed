<script setup>
import StandardForm from "@/Components/standards/StandardForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
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
});

const standard = props.record?.data ?? props.record;
const form = useForm({
  code: standard.code || "",
  description: standard.description || "",
});

function submit() {
  form.put(route("standards.update", { standard: standard.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("standards.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Referências normativas', url: route('standards.index') }, { title: standard.code }]" :title="standard.code" lede="Actualize o código controlado ou o contexto de aplicabilidade da norma." />

    <section class="ds-card overflow-hidden">
      <StandardForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('standards.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
