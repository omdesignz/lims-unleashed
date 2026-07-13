<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { ScaleIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  openCreate: { type: Boolean, default: false },
  slideOverEdit: { type: Boolean, default: false },
});
</script>

<template>
  <ReferenceCatalogManager
    v-bind="props"
    route-prefix="units"
    route-parameter="unit"
    permission-key="units"
    :title="$t('gestlab.general.labels.units.page_title')"
    description="Unidades normalizadas para resultados, limites de referencia, certificados e relatorios."
    entity-label="Unidade"
    new-entity-label="Nova unidade"
    :code-label="$t('gestlab.general.labels.units.code')"
    :description-label="$t('gestlab.general.labels.units.description')"
    :icon="ScaleIcon"
  />
</template>
