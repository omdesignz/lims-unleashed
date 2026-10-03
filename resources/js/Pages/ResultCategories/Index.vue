<script setup>
import { Tag as TagIcon } from '@lucide/vue'
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: { type: Object, default: () => ({ data: [], meta: {} }) },
  fields: { type: Array, default: () => [] },
  model: String,
  abilities: { type: Array, default: () => [] },
  query: { type: Object, default: () => ({}) },
  slideOverEdit: { type: Boolean, default: false },
})
</script>

<template>
  <ReferenceCatalogManager
    v-bind="props"
    route-prefix="resultcategories"
    route-parameter="category"
    permission-key="result_categories"
    title="Categorias de resultado"
    kicker="Classificação analítica"
    description="Gira as categorias controladas usadas para classificar resultados, organizar perfis analíticos e manter consistência na emissão de relatórios."
    entity-label="Categoria de resultado"
    new-entity-label="Nova categoria de resultado"
    name-label="Designação"
    description-label="Âmbito e critérios de utilização"
    create-description="Crie uma categoria inequívoca para agrupar resultados com o mesmo propósito analítico."
    edit-description="Actualize a designação e o âmbito sem perder a rastreabilidade dos resultados associados."
    form-description="Use uma designação curta e descreva quando esta classificação deve ser aplicada."
    :supports-name="true"
    :supports-code="false"
    :icon="TagIcon"
  />
</template>
