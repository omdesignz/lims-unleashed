<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { BadgePercent as ReceiptPercentIcon } from '@lucide/vue'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })

const extraFields = [
  { key: 'percent', label: 'Percentagem', type: 'number', required: true, min: 0, max: 100, step: 0.01, placeholder: 'Ex.: 14' },
  { key: 'compound_tax', label: 'Imposto composto', type: 'toggle', defaultValue: false, help: 'Calculado sobre outros impostos aplicaveis.' },
  { key: 'collective_tax', label: 'Imposto coletivo', type: 'toggle', defaultValue: false, help: 'Agrupa a incidencia fiscal no documento.' },
]
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="taxtypes" route-parameter="taxtype" permission-key="tax_types" title="Tipos de imposto" kicker="Governação fiscal" description="Taxas e regras de incidencia controladas para os documentos comerciais." entity-label="Tipo de imposto" new-entity-label="Novo imposto" name-label="Nome" description-label="Notas de aplicação" :supports-name="true" :supports-code="false" :extra-fields="extraFields" :icon="ReceiptPercentIcon" create-description="Registe uma taxa fiscal reutilizável na emissão comercial." form-description="Defina a taxa e as regras de composição segundo o enquadramento fiscal aplicavel." />
</template>
