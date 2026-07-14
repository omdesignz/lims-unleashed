<script setup>
import ReferenceCatalogManager from '@/Components/catalogs/ReferenceCatalogManager.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ScaleIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })
defineProps({ record: { type: Object, default: () => ({ data: [], meta: {} }) }, fields: { type: Array, default: () => [] }, model: String, abilities: { type: Array, default: () => [] }, query: { type: Object, default: () => ({}) }, slideOverEdit: { type: Boolean, default: false } })

const extraFields = [
  { key: 'reason', label: 'Motivo da isencao', required: true, fullWidth: true, placeholder: 'Fundamento resumido apresentado no documento' },
  { key: 'law', label: 'Referência legal', required: true, fullWidth: true, placeholder: 'Diploma, artigo ou norma aplicavel' },
]
</script>

<template>
  <ReferenceCatalogManager :record="record" :fields="fields" :model="model" :abilities="abilities" :query="query" :slide-over-edit="slideOverEdit" route-prefix="taxexemptions" route-parameter="taxexemption" permission-key="tax_exemptions" title="Isencoes fiscais" kicker="Governação fiscal" description="Fundamentos legais controlados para emissão coerente de documentos comerciais isentos." entity-label="Isencao fiscal" new-entity-label="Nova isencao" code-label="Código" description-label="Notas de aplicação" :supports-name="false" :supports-code="true" :extra-fields="extraFields" :icon="ScaleIcon" create-description="Registe um fundamento legal reutilizável na emissão comercial." form-description="Identifique o código fiscal e documente o motivo e a referência legal sem abreviacoes ambiguas." />
</template>
