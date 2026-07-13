<script setup>
import ProductForm from "@/Pages/Products/ProductForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const form = useForm({
  name: "",
  description: "",
  price: 0,
  fixed_price: 0,
  tax_percentage: 0,
  exemption_id: null,
  exemption_code: "",
  tax_id: null,
  matrix_id: null,
  charge_tax: true,
  withhold_tax: false,
});

function submit() {
  form.post(route("products.store"), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}
</script>

<template>
  <ProductForm :form="form" mode="create" @submit="submit" />
</template>
