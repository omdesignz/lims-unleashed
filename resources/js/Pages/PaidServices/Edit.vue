<script setup>
import PaidServiceForm from '@/Components/paid-services/PaidServiceForm.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })
const props = defineProps({ record: { type: Object, required: true } })
const service = props.record.data || {}
const form = useForm({
  id: service.id,
  name: service.name || '',
  description: service.description || '',
  price: service.price || 0,
  fixed_price: service.fixed_price || service.price || 0,
  tax_percentage: service.tax_percentage || 0,
  exemption_id: service.exemption_id ? { value: service.exemption_id.id ?? service.exemption_id, label: service.exemption_code || service.exemption } : null,
  exemption_code: service.exemption_code || '',
  tax_id: service.tax_id ? { value: service.tax_id, label: service.tax_category, percent: service.tax_percentage || 0 } : null,
  charge_tax: Boolean(service.charge_tax),
  withhold_tax: Boolean(service.withhold_tax),
})
const submit = () => form.put(route('paidservices.update', { service: service.id }), { preserveScroll: true })
</script>

<template>
  <div class="pl-page space-y-5">
    <PageHeader :trail="[{ title: 'Serviços', url: route('paidservices.index') }, { title: 'Editar serviço' }]" title="Editar serviço" lede="Actualize a configuração usada em novas propostas e documentos." />
    <PaidServiceForm :form="form" submit-label="Guardar alterações" @submit="submit" />
  </div>
</template>
