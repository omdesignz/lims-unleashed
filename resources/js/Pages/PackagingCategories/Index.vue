<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { CubeIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="packagingcategories" route-parameter="category" permission-key="packaging_types" title="Tipos de embalagem" kicker="Cadeia de custódia" description="Recipientes e embalagens controlados para recolha, transporte e preservação." entity-label="Tipo de embalagem" new-entity-label="Novo tipo" name-label="Nome" description-label="Condições de utilização" :supports-name="true" :supports-code="false" :icon="CubeIcon" />
</template>
