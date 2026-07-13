<script setup>
import { BookOpenIcon, DocumentTextIcon } from "@heroicons/vue/24/outline";

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
          <p class="ds-kicker">Identificacao normativa</p>
          <h2 class="ds-heading mt-1 text-base">Codigo de referencia</h2>
          <p class="ds-copy mt-1 text-sm">Use a designacao oficial que sera citada em perfis, metodos e resultados.</p>
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
        <p v-else class="ds-field-hint">Inclua o ano ou a versao quando fizer parte da referencia oficial.</p>
      </div>
    </section>

    <section class="px-5 py-5 sm:px-6">
      <div class="mb-5 flex items-start gap-3">
        <DocumentTextIcon class="mt-0.5 h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200" />
        <div>
          <p class="ds-kicker">Contexto</p>
          <h2 class="ds-heading mt-1 text-base">Descricao e aplicabilidade</h2>
          <p class="ds-copy mt-1 text-sm">Resuma o escopo para facilitar a selecao correta durante a configuracao analitica.</p>
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
          placeholder="Objeto, setor, matriz ou procedimento ao qual a norma se aplica"
        />
        <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
        <p v-else class="ds-field-hint">Evite repetir o codigo; descreva como a norma e usada no laboratorio.</p>
      </div>
    </section>
  </div>
</template>
