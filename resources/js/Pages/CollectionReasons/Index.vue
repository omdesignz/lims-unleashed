<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ClipboardDocumentListIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="collectionreasons" route-parameter="reason" permission-key="collection_reasons" title="Motivos de recolha" kicker="Pre-analitica" description="Vocabulario controlado para justificar recolhas, recolhas adicionais e reamostragens." entity-label="Motivo de recolha" new-entity-label="Novo motivo" name-label="Nome" code-label="Codigo" description-label="Criterio de utilizacao" :supports-name="true" :icon="ClipboardDocumentListIcon" />
</template>
