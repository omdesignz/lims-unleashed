<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Boxes as CubeTransparentIcon } from "@lucide/vue";

defineOptions({ layout: Layout });

defineProps({
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
    :record="record"
    :fields="fields"
    :model="model"
    :abilities="abilities"
    :query="query"
    :slide-over-edit="slideOverEdit"
    route-prefix="itypes"
    route-parameter="itype"
    permission-key="itypes"
    title="Tipos de inventário"
    kicker="Classificação de existências"
    description="Tipos controlados usados para distinguir famílias de materiais nos fluxos de recepção, conservação e consumo."
    entity-label="Tipo de inventário"
    new-entity-label="Novo tipo de inventário"
    name-label="Designação"
    description-label="Definição operacional"
    :supports-name="true"
    :supports-code="false"
    :icon="CubeTransparentIcon"
  />
</template>
