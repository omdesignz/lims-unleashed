<script setup>
import PaidServiceForm from '@/Components/paid-services/PaidServiceForm.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { useForm } from '@inertiajs/vue3'

defineOptions({ layout: Layout })

const form = useForm({ name: '', description: '', price: 0, fixed_price: 0, tax_percentage: 0, exemption_id: null, exemption_code: '', tax_id: null, charge_tax: true, withhold_tax: false })
const submit = () => form.post(route('paidservices.store'))
</script>

<template>
  <div class="pl-page space-y-5">
    <PageHeader :trail="[{ title: 'Serviços', url: route('paidservices.index') }, { title: 'Novo serviço facturável' }]" title="Novo serviço facturável" lede="Defina o âmbito, o preço e o tratamento fiscal utilizado nos documentos comerciais." />
    <PaidServiceForm :form="form" submit-label="Adicionar ao catálogo" @submit="submit" />
  </div>
</template>
