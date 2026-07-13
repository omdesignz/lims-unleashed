<script setup>
import ArchivedDocumentForm from "@/Pages/ArchivedDocument/ArchivedDocumentForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ArchiveBoxArrowDownIcon, ArrowLeftIcon, CheckIcon } from "@heroicons/vue/24/outline";

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
  <form class="space-y-6" enctype="multipart/form-data" @submit.prevent="submit">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <Link :href="route('archived_documents.index')" class="ds-button ds-button-ghost -ml-3 w-fit"><ArrowLeftIcon class="h-4 w-4" />Arquivo</Link>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><ArchiveBoxArrowDownIcon class="h-5 w-5" /></span>
            <div>
              <p class="ds-kicker">Controlo documental</p>
              <h1 class="ds-heading mt-1 text-2xl">Novo documento arquivado</h1>
              <p class="ds-copy mt-1 max-w-3xl text-sm">Preserve evidência histórica com contexto e proveniência recuperáveis.</p>
            </div>
          </div>
        </div>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.title.trim()"><CheckIcon class="h-4 w-4" />{{ form.processing ? "A guardar..." : "Guardar registo" }}</button>
      </div>
    </section>

    <ArchivedDocumentForm :form="form" />
  </form>
</template>
