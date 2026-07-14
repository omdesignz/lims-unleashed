<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { BanknotesIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="paymentcategories" route-parameter="category" permission-key="payment_categories" title="Categorias de pagamento" kicker="Controlo financeiro" description="Meios de liquidação normalizados para pagamentos recebidos e reconciliação comercial." entity-label="Categoria de pagamento" new-entity-label="Nova categoria" name-label="Nome" code-label="Código" description-label="Condições de utilização" :supports-name="true" :icon="BanknotesIcon" />
</template>
