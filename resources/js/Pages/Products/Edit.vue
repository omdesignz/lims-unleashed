<script setup>
import ProductForm from "@/Pages/Products/ProductForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});

const product = props.record.data ?? props.record;
const exemption = product.exemption_id?.data ?? product.exemption_id;
const form = useForm({
  id: product.id,
  name: product.name ?? "",
  description: product.description ?? "",
  price: product.price ?? 0,
  fixed_price: product.fixed_price ?? 0,
  tax_percentage: product.tax_percentage ?? 0,
  exemption_id: exemption?.id ? { value: exemption.id, label: exemption.code || product.exemption } : null,
  exemption_code: product.exemption_code ?? "",
  tax_id: product.tax_id ? { value: product.tax_id, label: product.tax_category || `Imposto ${product.tax_id}` } : null,
  matrix_id: product.matrix_id ? { value: product.matrix_id, label: product.matrix || `Matriz ${product.matrix_id}` } : null,
  charge_tax: Boolean(product.charge_tax),
  withhold_tax: Boolean(product.withhold_tax),
  is_control_material: Boolean(product.is_control_material),
});

function submit() {
  form.put(route("products.update", { product: form.id }), {
    preserveScroll: true,
  });
}
</script>

<template>
  <ProductForm :form="form" mode="edit" @submit="submit" />
</template>
