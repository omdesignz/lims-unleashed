<script setup>
import ContractGuideForm from "@/Pages/ContractGuides/ContractGuideForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const props = defineProps({
  record: { type: Object, required: true },
});
const guide = props.record?.data ?? props.record;

function normalizedDate(value) {
  return value ? String(value).slice(0, 10) : null;
}

const form = useForm("ContractGuideEdit", {
  id: guide.id,
  obs: guide.obs ?? "",
  guide_no: guide.guide_no ?? "",
  ref_no: guide.ref_no ?? "",
  entry_point: guide.entry_point ?? "",
  collection_point: guide.collection_point ?? "",
  du_no: guide.du_no ?? "",
  nif: guide.nif ?? "",
  contact: guide.contact ?? "",
  email: guide.email ?? "",
  bl: guide.bl ?? "",
  date: normalizedDate(guide.date),
  customer_id: guide.customer_id ?? null,
  warehouse_id: guide.warehouse_id ?? null,
  collection_id: guide.collection_id ?? null,
  items: (guide.items ?? []).map((item) => ({
    ...item,
    date: normalizedDate(item.date),
  })),
});

if (!form.items.length) {
  form.items.push({
    id: null,
    guide_id: guide.id,
    product_id: null,
    country_id: null,
    manufacturer: "",
    brand: "",
    lot: "",
    du_no: "",
    bl: "",
    collection_id: null,
    obs: "",
    date: null,
  });
}

function submit() {
  form.put(route("contractguides.update", { guide: guide.id }), {
    preserveScroll: true,
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Guias de contrato', url: route('contractguides.index') }, { title: `Editar guia ${guide.guide_no}` }]" :title="`Editar guia ${guide.guide_no}`" lede="Actualize a guia e os produtos numa única transacção rastreável." />

    <ContractGuideForm :form="form" :guide-number="guide.guide_no" submit-label="Guardar alterações" />
  </form>
</template>
