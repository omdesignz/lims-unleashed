<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { BadgeCheck as CheckBadgeIcon } from '@lucide/vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="collectionendresults" route-parameter="endresult" permission-key="collection_end_results" title="Resultados de recolha" kicker="Pre-analítica" description="Desfechos controlados para documentar a conclusão de cada recolha." entity-label="Resultado de recolha" new-entity-label="Novo resultado" name-label="Nome" description-label="Critério de aplicação" :supports-name="true" :supports-code="false" :icon="CheckBadgeIcon" />
</template>
