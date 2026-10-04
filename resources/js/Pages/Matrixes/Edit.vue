<script setup>
import MatrixForm from "@/Components/matrixes/MatrixForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import { createMatrixDataFromRecord } from "@/Components/matrixes/matrixFormData";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { Eye as EyeIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const matrix = props.record?.data ?? props.record;
const form = useForm(createMatrixDataFromRecord(matrix));

function submit() {
  form.put(route("matrixes.update", { matrix: matrix.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("matrixes.show", { matrix: matrix.id })),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Matrizes', url: route('matrixes.index') }, { title: matrix.code }]" :title="matrix.code" lede="Actualize o âmbito de perfis, o preço comercial e a regra fiscal usada no catálogo.">
      <template #badges>
        <span class="ds-chip">{{ form.profiles.length }} perfil(is)</span>
        <span class="ds-chip">{{ form.charge_tax ? `${form.tax_percentage}% imposto` : "Isenta" }}</span>
      </template>
    </PageHeader>

    <section class="ds-card overflow-hidden">
      <MatrixForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('matrixes.show', { matrix: matrix.id })" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
