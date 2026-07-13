<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="equipmentcategories" route-parameter="parent" permission-key="equipment_categories" title="Categorias de equipamentos" kicker="Ativos laboratoriais" description="Classificacao controlada para inventario, manutencao, calibracao e leitura de risco dos equipamentos." entity-label="Categoria de equipamento" new-entity-label="Nova categoria" name-label="Nome" code-label="Codigo" description-label="Escopo da categoria" :supports-name="true" :icon="WrenchScrewdriverIcon" create-description="Adicione uma categoria reutilizavel no cadastro e nos planos de manutencao." form-description="A designacao deve permitir identificar rapidamente o tipo de ativo e o seu contexto de controlo." />
</template>
