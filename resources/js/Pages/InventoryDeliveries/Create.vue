<script setup>
import InventoryDeliveryForm from "@/Pages/InventoryDeliveries/InventoryDeliveryForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const today = new Date().toISOString().slice(0, 10);
const form = useForm({
  sales_date: today,
  customer_id: null,
  items: [
    {
      client_key: "delivery-line-initial",
      item_id: null,
      qty: 1,
      expected_date: today,
      actual_date: today,
      warehouse_id: null,
    },
  ],
});

function submit() {
  form.post(route("ideliveries.store"), {
    preserveScroll: true,
  });
}
</script>

<template>
  <InventoryDeliveryForm :form="form" mode="create" @submit="submit" />
</template>
