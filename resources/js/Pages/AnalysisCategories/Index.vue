<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

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
    kicker="Governação analítica"
    description="Classifique perfis e parâmetros por disciplina laboratorial e departamento responsável."
    entity-label="Categoria analítica"
    new-entity-label="Nova categoria analítica"
    :name-label="$t('gestlab.general.labels.analysis_categories.name')"
    :code-label="$t('gestlab.general.labels.analysis_categories.code')"
    :description-label="$t('gestlab.general.labels.analysis_categories.description')"
    :department-label="$t('gestlab.general.labels.analysis_categories.department_id')"
    :supports-name="true"
    :supports-department="true"

  />
</template>
