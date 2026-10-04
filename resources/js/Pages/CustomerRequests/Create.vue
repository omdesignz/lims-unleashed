<script setup>
import CustomerRequestForm from "@/Components/customer-requests/CustomerRequestForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

const form = useForm({
  email: "",
  contact: "",
  description: "",
  category_id: null,
  customer_id: null,
  warehouse_id: null,
});

function submit() {
  form.post(route("customerrequests.store"), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("customerrequests.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Pedidos de clientes', url: route('customerrequests.index') }, { title: 'Pedido de cliente' }]" title="Pedido de cliente" lede="Registe a necessidade, o local do cliente e o contacto responsável pela comunicação." />

    <section class="ds-card overflow-hidden">
      <CustomerRequestForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('customerrequests.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A registar..." : "Registar pedido" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
