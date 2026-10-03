<script setup>
import { BookOpen as BookOpenIcon, FileText as DocumentTextIcon } from "@lucide/vue";

defineProps({
  form: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <BookOpenIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Identificação normativa</p>
          <h2 class="ds-heading mt-1 text-base">Código de referência</h2>
          <p class="ds-copy mt-1 text-sm">Use a designação oficial que será citada em perfis, métodos e resultados.</p>
        </div>
      </div>

      <div class="ds-field-group">
        <label for="standard-code" class="ds-field-label">
          {{ $t('gestlab.general.labels.standards.code') }}
          <span class="ds-field-required">*</span>
        </label>
        <BaseInput
          id="standard-code"
          v-model="form.code"
          type="text"
          class="ds-field font-mono"
          :aria-invalid="Boolean(form.errors.code)"
          autocomplete="off"
          placeholder="Ex.: ISO 17025:2017"
        />
        <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
        <p v-else class="ds-field-hint">Inclua o ano ou a versão quando fizer parte da referência oficial.</p>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <DocumentTextIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Contexto</p>
          <h2 class="ds-heading mt-1 text-base">Descrição e aplicabilidade</h2>
          <p class="ds-copy mt-1 text-sm">Resuma o âmbito para facilitar a selecção correcta durante a configuração analítica.</p>
        </div>
      </div>

      <div class="ds-field-group">
        <label for="standard-description" class="ds-field-label">
          {{ $t('gestlab.general.labels.standards.description') }}
        </label>
        <textarea
          id="standard-description"
          v-model="form.description"
          class="ds-field min-h-40 resize-y"
          :aria-invalid="Boolean(form.errors.description)"
          placeholder="Objecto, sector, matriz ou procedimento ao qual a norma se aplica"
        />
        <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        <p v-else class="ds-field-hint">Evite repetir o código; descreva como a norma e usada no laboratório.</p>
      </div>
    </section>
  </div>
</template>
