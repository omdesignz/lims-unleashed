<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { MessagesSquare as ChatBubbleLeftRightIcon } from "@lucide/vue";

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
    route-prefix="customerrequestcategories"
    route-parameter="category"
    permission-key="request_categories"
    title="Categorias de pedidos"
    kicker="Triagem do portal"
    description="Taxonomia usada para classificar, encaminhar e acompanhar pedidos dos clientes."
    entity-label="Categoria de pedido"
    new-entity-label="Nova categoria de pedido"
    name-label="Nome"
    description-label="Descrição"
    :supports-name="true"
    :supports-code="false"
    :icon="ChatBubbleLeftRightIcon"
    create-description="Adicione um tipo de pedido que possa ser seleccionado e encaminhado no portal."
    form-description="Use nomes orientados a serviço para facilitar a triagem e a leitura pelo cliente."
  />
</template>
