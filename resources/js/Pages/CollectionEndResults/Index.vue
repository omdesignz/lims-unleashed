<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { CheckBadgeIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="collectionendresults" route-parameter="endresult" permission-key="collection_end_results" title="Resultados de recolha" kicker="Pre-analitica" description="Desfechos controlados para documentar a conclusao de cada recolha." entity-label="Resultado de recolha" new-entity-label="Novo resultado" name-label="Nome" description-label="Criterio de aplicacao" :supports-name="true" :supports-code="false" :icon="CheckBadgeIcon" />
</template>
