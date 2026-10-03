<script setup>
import Combobox from "@/Components/combobox.vue";
import { loadSelectOptions, optionMappers } from "@/Utils/selectOptions";
import {
  Building2 as BuildingOffice2Icon,
  IdCard as IdentificationIcon,
  Tag as TagIcon,
} from "@lucide/vue";

defineProps({
  form: { type: Object, required: true },
});

function loadCategories(query, setOptions) {
  return loadSelectOptions("/customercategories/getCustomerCategory", query, setOptions, optionMappers.name);
}
</script>

<template>
  <div class="divide-y divide-[var(--ds-border)]">
    <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8">
      <div>
        <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
          <BuildingOffice2Icon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
          Identificação da conta
        </div>
        <p class="ds-copy mt-2 text-sm">Nome legal, código interno e contexto usados em propostas, amostras e documentos fiscais.</p>
      </div>

      <div class="grid gap-5 sm:grid-cols-2">
        <div class="ds-field-group sm:col-span-2">
          <label for="customer-name" class="ds-field-label">Nome do cliente <span class="ds-field-required">*</span></label>
          <BaseInput id="customer-name" v-model="form.name" type="text" class="ds-field" autocomplete="organization" :aria-invalid="Boolean(form.errors.name)" />
          <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
          <p v-else class="ds-field-hint">Use a designação reconhecida nos documentos comerciais e certificados.</p>
        </div>

        <div class="ds-field-group">
          <label for="customer-code" class="ds-field-label">Código interno</label>
          <BaseInput id="customer-code" v-model="form.code" type="text" class="ds-field font-mono uppercase" :aria-invalid="Boolean(form.errors.code)" />
          <p v-if="form.errors.code" class="ds-field-error">{{ form.errors.code }}</p>
          <p v-else class="ds-field-hint">Identificador curto para pesquisa e rastreabilidade.</p>
        </div>

        <div class="ds-field-group">
          <label class="ds-field-label">Categoria <span class="ds-field-required">*</span></label>
          <Combobox
            v-model="form.category_id"
            :load-options="loadCategories"
            :has-error="Boolean(form.errors.category_id)"
            placeholder="Seleccione a categoria comercial"
          />
          <p v-if="form.errors.category_id" class="ds-field-error">{{ form.errors.category_id }}</p>
          <p v-else class="ds-field-hint">Controla segmentacao e leitura da carteira.</p>
        </div>
      </div>
    </section>

    <section class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-8">
      <div>
        <div class="flex items-center gap-2 text-sm font-bold text-[var(--ds-text)]">
          <IdentificationIcon class="h-5 w-5 text-[rgb(var(--primary-700-rgb))]" />
          Contexto operacional
        </div>
        <p class="ds-copy mt-2 text-sm">Informação curta que ajuda recepção, comercial e laboratório a distinguir contas semelhantes.</p>
      </div>

      <div class="ds-field-group">
        <label for="customer-description" class="ds-field-label">Descrição</label>
        <textarea
          id="customer-description"
          v-model="form.description"
          rows="6"
          class="ds-field min-h-36 resize-y"
          :aria-invalid="Boolean(form.errors.description)"
          placeholder="Unidade de negocio, contrato, sector ou restricoes relevantes"
        />
        <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
      </div>
    </section>

    <section class="bg-[var(--ds-panel-subtle)] px-5 py-4 sm:px-6">
      <div class="flex items-start gap-3 text-xs font-semibold text-[var(--ds-text-muted)]">
        <TagIcon class="mt-0.5 h-4 w-4 shrink-0" /> Os contactos, locais de recolha, NIF e endereços de facturação são geridos depois de criar a conta. </div>
    </section>
  </div>
</template>
