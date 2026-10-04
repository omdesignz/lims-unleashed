<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="equipmentcategories" route-parameter="parent" permission-key="equipment_categories" title="Categorias de equipamentos" kicker="Activos laboratoriais" description="Classificação controlada para inventário, manutenção, calibração e leitura de risco dos equipamentos." entity-label="Categoria de equipamento" new-entity-label="Nova categoria" name-label="Nome" code-label="Código" description-label="Âmbito da categoria" :supports-name="true" create-description="Adicione uma categoria reutilizável no registo e nos planos de manutenção." form-description="A designação deve permitir identificar rapidamente o tipo de activo e o seu contexto de controlo." />
</template>
