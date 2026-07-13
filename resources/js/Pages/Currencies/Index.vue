<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { CurrencyDollarIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })

const extraFields = [
  { key: 'symbol', label: 'Simbolo', required: true, monospace: true, placeholder: 'Ex.: Kz' },
  { key: 'thousand_separator', label: 'Separador de milhares', required: true, monospace: true, placeholder: 'Ex.: .' },
  { key: 'decimal_separator', label: 'Separador decimal', required: true, monospace: true, placeholder: 'Ex.: ,' },
  { key: 'swap_currency_symbol', label: 'Simbolo apos o valor', type: 'toggle', defaultValue: false, help: 'Apresenta o simbolo depois do montante.' },
]
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="currencies" route-parameter="currency" permission-key="currencies" title="Moedas" kicker="Configuracao financeira" description="Codigos, simbolos e formatacao monetaria para documentos e recebimentos." entity-label="Moeda" new-entity-label="Nova moeda" name-label="Nome" code-label="Codigo ISO" description-label="Notas" :supports-name="true" :extra-fields="extraFields" :icon="CurrencyDollarIcon" create-description="Adicione uma moeda suportada pelos fluxos comerciais." form-description="Use o codigo monetario reconhecido e configure separadores consistentes para impressao." />
</template>
