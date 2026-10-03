<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Globe as GlobeAltIcon } from '@lucide/vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })

const extraFields = [
  { key: 'phone_code', label: 'Indicativo telefónico', required: true, monospace: true, placeholder: 'Ex.: +244' },
]
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="countries" route-parameter="country" permission-key="countries" title="Paises" kicker="Dados mestres" description="Referencial geografico para clientes, fornecedores, contactos e documentos." entity-label="Pais" new-entity-label="Novo pais" name-label="Nome" code-label="Código ISO" description-label="Notas" :supports-name="true" :extra-fields="extraFields" :icon="GlobeAltIcon" />
</template>
