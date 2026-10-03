<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { ShieldCheck as ShieldCheckIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
  openCreate: { type: Boolean, default: false },
});
</script>

<template>
  <ReferenceCatalogManager
    v-bind="props"
    route-prefix="phytosanitary_products"
    route-parameter="product"
    permission-key="phytosanitary_products"
    title="Produtos fitossanitários"
    kicker="Catálogo regulamentado"
    description="Mantenha a designação e o âmbito de utilização dos produtos fitossanitários usados em propostas, recolhas e documentação oficial."
    entity-label="Produto fitossanitário"
    new-entity-label="Novo produto fitossanitário"
    name-label="Designação comercial"
    description-label="Aplicação e enquadramento"
    form-description="Registe uma designação inequívoca e descreva a aplicação autorizada para manter consistência nos documentos emitidos."
    :supports-name="true"
    :supports-code="false"
    :icon="ShieldCheckIcon"
  />
</template>
