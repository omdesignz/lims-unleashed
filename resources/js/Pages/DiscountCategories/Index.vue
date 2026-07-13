<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ReceiptPercentIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })

const extraFields = [
  { key: 'symbol', label: 'Simbolo', required: true, monospace: true, placeholder: 'Ex.: %' },
]
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="discountcategories" route-parameter="category" permission-key="discount_categories" title="Categorias de desconto" kicker="Configuracao comercial" description="Descontos reutilizaveis para propostas, faturas e restantes documentos comerciais." entity-label="Categoria de desconto" new-entity-label="Nova categoria" name-label="Nome" description-label="Criterio de aplicacao" :supports-name="true" :supports-code="false" :extra-fields="extraFields" :icon="ReceiptPercentIcon" create-description="Adicione uma classificacao reutilizavel nos documentos comerciais." form-description="Use um nome reconhecivel, um simbolo curto e descreva o criterio autorizado de aplicacao." />
</template>
