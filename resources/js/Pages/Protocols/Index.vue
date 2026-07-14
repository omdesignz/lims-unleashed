<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { ClipboardDocumentCheckIcon } from "@heroicons/vue/24/outline";

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
    route-prefix="protocols"
    route-parameter="protocol"
    permission-key="protocols"
    :title="$t('gestlab.general.labels.protocols.page_title')"
    description="Catálogo controlado das metodologias aplicadas aos ensaios e associadas aos perfis analíticos."
    entity-label="Metodologia"
    new-entity-label="Nova metodologia"
    :code-label="$t('gestlab.general.labels.protocols.code')"
    :description-label="$t('gestlab.general.labels.protocols.description')"
    :icon="ClipboardDocumentCheckIcon"
  />
</template>
