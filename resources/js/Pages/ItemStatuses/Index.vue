<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="itemstatuses" route-parameter="status" permission-key="item_statuses" title="Estados de inventário" kicker="Controlo de existências" description="Estados normalizados para disponibilidade, quarentena e utilização de itens." entity-label="Estado de inventário" new-entity-label="Novo estado" name-label="Nome" description-label="Significado operacional" :supports-name="true" :supports-code="false" />
</template>
