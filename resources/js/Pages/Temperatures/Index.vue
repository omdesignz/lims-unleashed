<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { SunIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="temperatures" route-parameter="temperature" permission-key="temperatures" title="Condicoes de temperatura" kicker="Controlo ambiental" description="Designacoes padronizadas para transporte, armazenamento e execucao de atividades laboratoriais." entity-label="Condicao de temperatura" new-entity-label="Nova condicao" name-label="Nome" code-label="Codigo" description-label="Aplicabilidade" :supports-name="true" :icon="SunIcon" create-description="Adicione uma condicao reutilizavel nos fluxos de amostras e inventario." form-description="Use um codigo curto e descreva limites ou contexto de aplicacao quando relevante." />
</template>
