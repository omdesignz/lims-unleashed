<script setup>
import InventoryDeliveryForm from "@/Pages/InventoryDeliveries/InventoryDeliveryForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { router, useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const form = useForm({
  id: props.record.id,
  sales_date: props.record.sales_date ?? "",
  customer_id: props.record.customer_id ?? null,
  items: (props.record.items ?? []).map((item, index) => ({
    ...item,
    client_key: `delivery-line-${index}-${item.item_id?.value ?? "new"}`,
  })),
});

function submit() {
  form.put(route("ideliveries.update", { idelivery: form.id }), {
    preserveScroll: true,
    onSuccess: () => router.visit(route("ideliveries.index")),
  });
}
</script>

<template>
  <InventoryDeliveryForm :form="form" mode="edit" @submit="submit" />
</template>
