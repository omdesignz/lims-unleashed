<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="itransactiontypes" route-parameter="type" permission-key="itransaction_types" title="Tipos de movimento de inventário" kicker="Rastreabilidade de existências" description="Motivos controlados para entradas, saídas, ajustes e transferências de materiais." entity-label="Tipo de movimento" new-entity-label="Novo tipo de movimento" name-label="Nome" code-label="Código" description-label="Critério de utilização" :supports-name="true" create-description="Adicione um motivo reutilizável ao diário de movimentos de existências." form-description="Use um código estável e descreva quando o movimento deve ser seleccionado." />
</template>
