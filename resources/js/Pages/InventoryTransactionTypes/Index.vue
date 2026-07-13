<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ArrowsRightLeftIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="itransactiontypes" route-parameter="type" permission-key="itransaction_types" title="Tipos de movimento de inventario" kicker="Rastreabilidade de stock" description="Motivos controlados para entradas, saidas, ajustes e transferencias de materiais." entity-label="Tipo de movimento" new-entity-label="Novo tipo de movimento" name-label="Nome" code-label="Codigo" description-label="Criterio de utilizacao" :supports-name="true" :icon="ArrowsRightLeftIcon" create-description="Adicione um motivo reutilizavel ao diario de movimentos de stock." form-description="Use um codigo estavel e descreva quando o movimento deve ser selecionado." />
</template>
