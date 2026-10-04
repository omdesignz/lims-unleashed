<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="temperatures" route-parameter="temperature" permission-key="temperatures" title="Condições de temperatura" kicker="Controlo ambiental" description="Designações padronizadas para transporte, armazenamento e execução de actividades laboratoriais." entity-label="Condição de temperatura" new-entity-label="Nova condição" name-label="Nome" code-label="Código" description-label="Aplicabilidade" :supports-name="true" create-description="Adicione uma condição reutilizável nos fluxos de amostras e inventário." form-description="Use um código curto e descreva limites ou contexto de aplicação quando relevante." />
</template>
