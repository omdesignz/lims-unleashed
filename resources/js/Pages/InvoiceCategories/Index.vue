<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { DocumentCurrencyDollarIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="invoicecategories" route-parameter="category" permission-key="invoice_categories" title="Tipos de fatura" kicker="Configuracao comercial" description="Classificacao controlada para emissao, series e reporte de documentos fiscais." entity-label="Tipo de fatura" new-entity-label="Novo tipo de fatura" code-label="Codigo" description-label="Aplicabilidade" :supports-name="false" :icon="DocumentCurrencyDollarIcon" />
</template>
