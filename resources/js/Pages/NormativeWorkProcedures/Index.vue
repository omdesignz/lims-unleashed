<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { FileText as DocumentTextIcon } from "@lucide/vue";

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
    route-prefix="nwps"
    route-parameter="nwp"
    permission-key="nwps"
    :title="$t('gestlab.general.labels.nwps.page_title')"
    description="Procedimentos normativos de trabalho usados para demonstrar execução consistente e rastreável."
    entity-label="PNT"
    new-entity-label="Novo PNT"
    :code-label="$t('gestlab.general.labels.nwps.code')"
    :description-label="$t('gestlab.general.labels.nwps.description')"
    :icon="DocumentTextIcon"
  />
</template>
