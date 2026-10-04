<script setup>
import ArchivedDocumentForm from "@/Pages/ArchivedDocument/ArchivedDocumentForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";
import { Check as CheckIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const form = useForm("ArchivedDocumentCreate", {
  title: "",
  description: "",
  file: null,
});

function submit() {
  form.post(route("archived_documents.store"), {
    forceFormData: true,
    preserveScroll: true,
  });
}
</script>

<template>
  <form class="pl-page space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Arquivo', url: route('archived_documents.index') }, { title: 'Novo documento arquivado' }]" title="Novo documento arquivado" lede="Preserve evidência histórica com contexto e proveniência recuperáveis.">
      <template #actions>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.title.trim()"><CheckIcon class="h-4 w-4" />{{ form.processing ? "A guardar..." : "Guardar registo" }}</button>
      </template>
    </PageHeader>

    <ArchivedDocumentForm :form="form" />
  </form>
</template>
