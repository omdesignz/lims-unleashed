<script setup>
import { useForm } from "@inertiajs/vue3";
import { Upload as ArrowUpTrayIcon, FileUp as DocumentArrowUpIcon } from "@lucide/vue";
import { ref } from "vue";

const fileInput = ref(null);
const form = useForm({
  file: null,
});

function onFileChange(event) {
  form.file = event.target.files?.[0] ?? null;
}

function submit() {
  if (form.processing || !form.file) return;
  form.post(route('occurrences.import.upload'), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset();

      if (fileInput.value) {
        fileInput.value.value = "";
      }
    },
  });
}
</script>

<template>
  <section class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4 sm:p-5">
    <form class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between" enctype="multipart/form-data" @submit.prevent="submit">
      <div class="flex min-w-0 items-start gap-3">
        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200">
          <DocumentArrowUpIcon class="h-5 w-5" />
        </span>
        <div class="min-w-0">
          <h2 class="text-sm font-bold text-[var(--ds-text)]">Importação em lote</h2>
          <p class="mt-1 text-sm text-[var(--ds-text-muted)]">CSV separado por ponto e vírgula; até 500 registos e 2 MB. A importação é integral: qualquer erro impede todos os registos.</p>
          <a :href="route('occurrences.import.template')" class="ds-button ds-button-secondary mt-2">Descarregar modelo CSV</a>
          <p class="mt-2 text-xs text-[var(--ds-text-muted)]">Laboratório e números são atribuídos automaticamente. Referências usam IDs existentes; booleanos usam 0 ou 1.</p>
          <p v-if="form.file" class="mt-2 truncate text-xs font-bold text-[var(--ds-text)]">{{ form.file.name }}</p>
          <p v-if="form.errors.file" id="occurrence-import-error" role="alert" class="ds-field-error mt-2">{{ form.errors.file }}</p>
        </div>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="ds-button ds-button-secondary cursor-pointer">
          <input ref="fileInput" type="file" accept=".csv,text/csv" class="sr-only" :disabled="form.processing" :aria-invalid="!!form.errors.file" :aria-describedby="form.errors.file ? 'occurrence-import-error' : undefined" required @change="onFileChange" />
          Seleccionar CSV
        </label>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.file">
          <ArrowUpTrayIcon class="h-4 w-4" />
          {{ form.processing ? "A importar..." : "Importar" }}
        </button>
      </div>
    </form>
  </section>
</template>
