<script setup>
import ReferenceCatalogManager from "@/Components/catalogs/ReferenceCatalogManager.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { UserGroupIcon } from "@heroicons/vue/24/outline";

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
    kicker="Governanca de contactos"
    description="Funcoes controladas para identificar interlocutores tecnicos, financeiros e operacionais."
    entity-label="Categoria de contacto"
    new-entity-label="Nova categoria de contacto"
    name-label="Nome"
    code-label="Codigo"
    description-label="Descricao"
    :supports-name="true"
    :code-required="false"
    :icon="UserGroupIcon"
    create-description="Adicione uma funcao de contacto reutilizavel nos clientes e locais operacionais."
    form-description="Identifique a responsabilidade do contacto; o codigo e opcional e pode apoiar integracoes."
  />
</template>
