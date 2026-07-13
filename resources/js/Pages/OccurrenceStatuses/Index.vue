<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="occurrencestatuses" route-parameter="status" permission-key="occurrence_statuses" title="Estados de ocorrencias" kicker="Governanca QMS" description="Estados controlados para classificar, acompanhar e encerrar ocorrencias de qualidade." entity-label="Estado de ocorrencia" new-entity-label="Novo estado" name-label="Nome" description-label="Descricao e criterio de uso" :supports-name="true" :supports-code="false" :icon="ExclamationCircleIcon" create-description="Adicione um estado reutilizavel ao fluxo de tratamento de ocorrencias." form-description="Defina um nome inequivoco e descreva quando o estado deve ser aplicado." />
</template>
