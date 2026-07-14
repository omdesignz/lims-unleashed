<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="occurrencecategories" route-parameter="category" permission-key="occurrence_categories" title="Categorias de ocorrência" kicker="Sistema de qualidade" description="Classificação controlada para desvios, incidentes, reclamações e não conformidades." entity-label="Categoria de ocorrência" new-entity-label="Nova categoria" name-label="Nome" code-label="Código" description-label="Âmbito da categoria" :supports-name="true" :icon="ExclamationTriangleIcon" />
</template>
