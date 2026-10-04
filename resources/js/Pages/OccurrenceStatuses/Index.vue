<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="occurrencestatuses" route-parameter="status" permission-key="occurrence_statuses" title="Estados de ocorrências" kicker="Governação do SGQ" description="Estados controlados para classificar, acompanhar e encerrar ocorrências de qualidade." entity-label="Estado de ocorrência" new-entity-label="Novo estado" name-label="Nome" description-label="Descrição e critério de utilização" :supports-name="true" :supports-code="false" create-description="Adicione um estado reutilizável ao fluxo de tratamento de ocorrências." form-description="Defina um nome inequívoco e descreva quando o estado deve ser aplicado." />
</template>
