<script setup>
import PaidServiceForm from '@/Components/paid-services/PaidServiceForm.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeftIcon, CubeIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: Layout })

const form = useForm({ name: '', description: '', price: 0, fixed_price: 0, tax_percentage: 0, exemption_id: null, exemption_code: '', tax_id: null, charge_tax: true, withhold_tax: false })
const submit = () => form.post(route('paidservices.store'))
</script>

<template>
  <div class="space-y-5">
    <section class="ds-panel overflow-hidden">
      <header class="flex flex-col gap-4 border-b border-[var(--ds-border)] px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
        <div class="flex items-start gap-3"><span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] dark:text-cyan-200"><CubeIcon class="h-5 w-5" /></span><div><p class="ds-kicker">Catalogo comercial</p><h1 class="ds-heading mt-1 text-xl sm:text-2xl">Novo servico faturavel</h1><p class="ds-copy mt-1 text-sm">Defina o escopo, preco e tratamento fiscal usado nos documentos comerciais.</p></div></div>
        <Link :href="route('paidservices.index')" class="ds-button ds-button-secondary"><ArrowLeftIcon class="h-4 w-4" />Catalogo</Link>
      </header>
    </section>
    <PaidServiceForm :form="form" submit-label="Adicionar ao catalogo" @submit="submit" />
  </div>
</template>
