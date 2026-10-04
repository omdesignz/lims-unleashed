<script setup>
import ArchivedDocumentForm from "@/Pages/ArchivedDocument/ArchivedDocumentForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";
import { Check as CheckIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const props = defineProps({ record: { type: Object, required: true } });
const document = props.record?.data ?? props.record;
const form = useForm("ArchivedDocumentEdit", {
  title: document.title ?? "",
  description: document.description ?? "",
  file: null,
});

function submit() {
  form
    .transform((data) => ({ ...data, _method: "put" }))
    .post(route("archived_documents.update", document.id), {
      forceFormData: true,
      preserveScroll: true,
    });
}
</script>

<template>
  <form class="pl-page space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Arquivo', url: route('archived_documents.index') }, { title: 'Editar documento arquivado' }]" title="Editar documento arquivado" lede="Mantenha o contexto coerente com a evidência retida.">
      <template #actions>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || !form.title.trim()"><CheckIcon class="h-4 w-4" />{{ form.processing ? "A guardar..." : "Guardar alterações" }}</button>
      </template>
    </PageHeader>

    <ArchivedDocumentForm :form="form" :current-file="document.file_path" />
  </form>
</template>
