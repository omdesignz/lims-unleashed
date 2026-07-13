<script setup>
import PaidServiceForm from '@/Components/paid-services/PaidServiceForm.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, CubeIcon } from '@heroicons/vue/24/outline'

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
  <div class="space-y-5">
    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><CubeIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Catalogo comercial</p><h1 class="ds-heading mt-1 text-xl sm:text-2xl">Editar servico</h1><p class="ds-copy mt-1 text-sm">Atualize a configuracao usada em novas propostas e documentos.</p></div></div>
        <Link :href="route('paidservices.index')" class="ds-button ds-button-secondary"><ArrowLeftIcon class="h-4 w-4" />Catalogo</Link>
      </header>
    </section>
    <PaidServiceForm :form="form" submit-label="Guardar alteracoes" @submit="submit" />
  </div>
</template>
