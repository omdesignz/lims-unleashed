<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="collectioncollaborations" route-parameter="collaboration" permission-key="collaboration_categories" title="Colaboracoes de recolha" kicker="Pre-analítica" description="Papeis controlados para equipas e entidades envolvidas na recolha de amostras." entity-label="Colaboracao de recolha" new-entity-label="Nova colaboracao" name-label="Nome" description-label="Responsabilidade" :supports-name="true" :supports-code="false" />
</template>
