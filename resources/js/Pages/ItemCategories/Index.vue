<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ArchiveBoxIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="itemcategories" route-parameter="parent" permission-key="item_categories" title="Categorias de inventário" kicker="Governação de existências" description="Classificação reutilizável para materiais, consumíveis, reagentes e equipamentos." entity-label="Categoria de inventário" new-entity-label="Nova categoria" name-label="Nome" code-label="Código" description-label="Âmbito da categoria" :supports-name="true" :icon="ArchiveBoxIcon" />
</template>
