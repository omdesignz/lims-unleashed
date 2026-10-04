<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="transportcategories" route-parameter="category" permission-key="trans_types" title="Tipos de transporte" kicker="Logistica de amostras" description="Modalidades controladas para recolha, expedicao e recepção de amostras." entity-label="Tipo de transporte" new-entity-label="Novo tipo" name-label="Nome" description-label="Condições aplicaveis" :supports-name="true" :supports-code="false" />
</template>
