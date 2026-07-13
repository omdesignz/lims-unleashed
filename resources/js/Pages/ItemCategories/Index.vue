<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ArchiveBoxIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="itemcategories" route-parameter="parent" permission-key="item_categories" title="Categorias de inventario" kicker="Governanca de stock" description="Classificacao reutilizavel para materiais, consumiveis, reagentes e equipamentos." entity-label="Categoria de inventario" new-entity-label="Nova categoria" name-label="Nome" code-label="Codigo" description-label="Escopo da categoria" :supports-name="true" :icon="ArchiveBoxIcon" />
</template>
