<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Scale as ScaleIcon } from '@lucide/vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="iunits" route-parameter="iunit" permission-key="iunits" title="Unidades de inventário" kicker="Metrologia de existências" description="Unidades padronizadas para recepção, armazenamento, consumo e reconciliação." entity-label="Unidade de inventário" new-entity-label="Nova unidade" code-label="Código" description-label="Definição" :supports-name="false" :icon="ScaleIcon" />
</template>
