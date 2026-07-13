<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ScaleIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="iunits" route-parameter="iunit" permission-key="iunits" title="Unidades de inventario" kicker="Metrologia de stock" description="Unidades padronizadas para rececao, armazenamento, consumo e reconciliacao." entity-label="Unidade de inventario" new-entity-label="Nova unidade" code-label="Codigo" description-label="Definicao" :supports-name="false" :icon="ScaleIcon" />
</template>
