<script setup>
import { ArchiveBoxIcon, DocumentArrowUpIcon, InformationCircleIcon } from "@heroicons/vue/24/outline";

defineProps({
  form: { type: Object, required: true },
  currentFile: { type: String, default: "" },
});
</script>

<template>
  <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-start">
    <section class="ds-panel p-5 sm:p-6">
      <div class="flex items-start gap-3 border-b border-[var(--ds-border)] pb-4">
        <ArchiveBoxIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Registo de retenção</p>
          <h2 class="ds-heading mt-2 text-lg">Identidade e evidência</h2>
          <p class="ds-copy mt-1 text-sm">Use um título recuperável, contexto suficiente e o ficheiro final.</p>
        </div>
      </div>

      <div class="mt-6 space-y-6">
        <div>
          <label for="archived-document-title" class="ds-field-label">Título do registo</label>
          <BaseInput id="archived-document-title" v-model="form.title" type="text" class="ds-field mt-2" required />
          <p class="ds-field-hint mt-2">Exemplo: SOP substituída, certificado retido ou relatório histórico.</p>
          <p v-if="form.errors.title" class="ds-field-error mt-2">{{ form.errors.title }}</p>
        </div>

        <div>
          <label for="archived-document-description" class="ds-field-label">Descrição e contexto</label>
          <textarea id="archived-document-description" v-model="form.description" class="ds-field mt-2 min-h-44 resize-y" placeholder="Origem, motivo da retenção, período e informação útil para recuperação futura" />
          <p v-if="form.errors.description" class="ds-field-error mt-2">{{ form.errors.description }}</p>
        </div>

        <div>
          <label for="archived-document-file" class="ds-field-label">{{ currentFile ? "Substituir ficheiro" : "Ficheiro arquivado" }}</label>
          <FileInput id="archived-document-file" type="file" class="ds-field mt-2 file:mr-3 file:border-0 file:bg-transparent file:text-sm file:font-bold file:text-[var(--ds-text)]" @input="form.file = $event.target.files?.[0] ?? null" />
          <p v-if="form.file" class="ds-field-hint mt-2">Novo ficheiro: {{ form.file.name }}</p>
          <p v-else-if="currentFile" class="ds-field-hint mt-2 break-all">Ficheiro atual: {{ currentFile }}</p>
          <p v-if="form.errors.file" class="ds-field-error mt-2">{{ form.errors.file }}</p>
        </div>
      </div>
    </section>

    <aside class="space-y-4 xl:sticky xl:top-24">
      <section class="ds-panel p-5">
        <div class="flex gap-3">
          <DocumentArrowUpIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
          <div>
            <p class="ds-kicker">Checklist</p>
            <h2 class="ds-heading mt-2 text-base">Prontidão do arquivo</h2>
          </div>
        </div>
        <ul class="mt-4 space-y-3 text-sm leading-6 text-[var(--ds-text-muted)]">
          <li class="border-t border-[var(--ds-border)] pt-3">Título específico e fácil de pesquisar.</li>
          <li class="border-t border-[var(--ds-border)] pt-3">Descrição com origem e motivo de retenção.</li>
          <li class="border-t border-[var(--ds-border)] pt-3">Ficheiro final adequado para consulta futura.</li>
        </ul>
      </section>

      <section class="rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] p-4">
        <div class="flex gap-3">
          <InformationCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
          <div>
            <h2 class="text-sm font-bold text-[var(--ds-text)]">Retenção documental</h2>
            <p class="mt-1 text-sm leading-6 text-[var(--ds-text-muted)]">Este catálogo preserva documentos fora do circuito ativo sem perder proveniência.</p>
          </div>
        </div>
      </section>
    </aside>
  </div>
</template>
