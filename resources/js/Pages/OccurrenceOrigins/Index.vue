<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="occurrenceorigins" route-parameter="category" permission-key="occurrence_origins" title="Origens de ocorrência" kicker="Sistema de qualidade" description="Pontos de origem controlados para análise de causa, tendência e responsabilidade." entity-label="Origem de ocorrência" new-entity-label="Nova origem" name-label="Nome" code-label="Código" description-label="Contexto de origem" :supports-name="true" />
</template>
