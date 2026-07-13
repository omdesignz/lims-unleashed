<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { MapPinIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="occurrenceorigins" route-parameter="category" permission-key="occurrence_origins" title="Origens de ocorrencia" kicker="Sistema de qualidade" description="Pontos de origem controlados para analise de causa, tendencia e responsabilidade." entity-label="Origem de ocorrencia" new-entity-label="Nova origem" name-label="Nome" code-label="Codigo" description-label="Contexto de origem" :supports-name="true" :icon="MapPinIcon" />
</template>
