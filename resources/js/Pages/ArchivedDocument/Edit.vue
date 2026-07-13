<script setup>
import ArchivedDocumentForm from "@/Pages/ArchivedDocument/ArchivedDocumentForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ArchiveBoxIcon, ArrowLeftIcon, CheckIcon } from "@heroicons/vue/24/outline";

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
  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <Link :href="route('archived_documents.index')" class="ds-button ds-button-ghost -ml-3 w-fit"><ArrowLeftIcon class="h-4 w-4" />Arquivo</Link>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><ArchiveBoxIcon class="h-5 w-5" /></span>
            <div>
              <p class="ds-kicker">Controlo documental</p>
              <h1 class="ds-heading mt-1 text-2xl">Editar documento arquivado</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Mantenha o contexto coerente com a evidência retida.</p>
            </div>
          </div>
        </div>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty || !form.title.trim()"><CheckIcon class="h-4 w-4" />{{ form.processing ? "A guardar..." : "Guardar alterações" }}</button>
      </div>
    </section>

    <ArchivedDocumentForm :form="form" :current-file="document.file_path" />
  </form>
</template>
