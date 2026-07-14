<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { TagIcon } from "@heroicons/vue/24/outline";

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
    route-prefix="customercategories"
    route-parameter="category"
    permission-key="customer_categories"
    title="Categorias de clientes"
    kicker="Segmentacao da carteira"
    description="Classificação controlada para organizar contas, propostas e leitura comercial da carteira."
    entity-label="Categoria de cliente"
    new-entity-label="Nova categoria de cliente"
    name-label="Nome"
    code-label="Código"
    description-label="Descrição"
    :supports-name="true"
    :icon="TagIcon"
    create-description="Adicione uma classificação reutilizável nas contas e nos relatórios comerciais."
    form-description="Use um nome claro e um código curto que possam ser reconhecidos em pesquisa e relatórios."
  />
</template>
