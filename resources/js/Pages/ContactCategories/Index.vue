<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Users as UserGroupIcon } from "@lucide/vue";

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
    route-prefix="contactcategories"
    route-parameter="category"
    permission-key="contact_categories"
    title="Categorias de contactos"
    kicker="Governação de contactos"
    description="Funcoes controladas para identificar interlocutores técnicos, financeiros e operacionais."
    entity-label="Categoria de contacto"
    new-entity-label="Nova categoria de contacto"
    name-label="Nome"
    code-label="Código"
    description-label="Descrição"
    :supports-name="true"
    :code-required="false"
    :icon="UserGroupIcon"
    create-description="Adicione uma funcao de contacto reutilizável nos clientes e locais operacionais."
    form-description="Identifique a responsabilidade do contacto; o código e opcional e pode apoiar integracoes."
  />
</template>
