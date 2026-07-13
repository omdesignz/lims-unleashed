<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { BeakerIcon } from "@heroicons/vue/24/outline";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
});
</script>

<template>
  <ReferenceCatalogManager
    v-bind="props"
    route-prefix="analysiscategories"
    route-parameter="category"
    permission-key="analysis_categories"
    :title="$t('gestlab.general.labels.analysis_categories.page_title')"
    kicker="Governanca analitica"
    description="Classifique perfis e parametros por disciplina laboratorial e departamento responsavel."
    entity-label="Categoria analitica"
    new-entity-label="Nova categoria analitica"
    :name-label="$t('gestlab.general.labels.analysis_categories.name')"
    :code-label="$t('gestlab.general.labels.analysis_categories.code')"
    :description-label="$t('gestlab.general.labels.analysis_categories.description')"
    :department-label="$t('gestlab.general.labels.analysis_categories.department_id')"
    :supports-name="true"
    :supports-department="true"
    :icon="BeakerIcon"
  />
</template>
