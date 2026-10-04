<script setup>
import CustomerRequestForm from "@/Components/customer-requests/CustomerRequestForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import NextStepBar from "@/Components/plano/NextStepBar.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { Link, router, useForm } from "@inertiajs/vue3";

defineOptions({
  layout: Layout,
});

const props = defineProps({
  record: {
    type: Object,
    required: true,
  },
});

const request = props.record?.data ?? props.record;

const form = useForm({
  id: request.id,
  email: request.email || "",
  contact: request.contact || "",
  description: request.description || "",
  category_id: request.category_id ? { value: request.category_id, label: request.category } : null,
  customer_id: request.customer_id ? { value: request.customer_id, label: request.customer } : null,
  warehouse_id: request.warehouse_id ? { value: request.warehouse_id, label: request.warehouse } : null,
});

function submit() {
  form.put(route("customerrequests.update", { request: request.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("customerrequests.index")),
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Pedidos de clientes', url: route('customerrequests.index') }, { title: 'Editar pedido de cliente' }]" title="Editar pedido de cliente" lede="Actualize a classificação e os dados de contacto. O cliente, local e laboratório permanecem fixos.">
      <template #badges>
        <span v-if="request.reference" class="ds-chip">{{ request.reference }}</span>
        <span class="ds-chip">{{ request.status || "pending" }}</span>
        <span v-if="request.customer" class="ds-chip">{{ request.customer }}</span>
      </template>
    </PageHeader>

    <section class="ds-card overflow-hidden">
      <CustomerRequestForm :form="form" />
    </section>
    <NextStepBar>
      {{ form.processing ? 'A guardar…' : form.isDirty ? 'Alterações por guardar.' : 'Sem alterações por guardar.' }}
      <template #actions>
        <Link :href="route('customerrequests.index')" class="ds-button ds-button-secondary">Cancelar</Link>
        <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing || !form.isDirty">
          {{ form.processing ? "A guardar..." : "Guardar alterações" }}
        </button>
      </template>
    </NextStepBar>
  </form>
</template>
