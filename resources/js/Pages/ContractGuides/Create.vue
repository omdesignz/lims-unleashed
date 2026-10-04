<script setup>
import ContractGuideForm from "@/Pages/ContractGuides/ContractGuideForm.vue";
import PageHeader from "@/Components/plano/PageHeader.vue";
import Layout from "@/Shared/Layouts/Layout.vue";
import { useForm } from "@inertiajs/vue3";

defineOptions({ layout: Layout });

const today = new Date();
const localDate = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
const form = useForm("ContractGuideCreate", {
  obs: "",
  ref_no: "",
  entry_point: "",
  collection_point: "",
  du_no: "",
  nif: "",
  contact: "",
  email: "",
  bl: "",
  date: localDate,
  customer_id: null,
  warehouse_id: null,
  collection_id: null,
  items: [{
    id: null,
    guide_id: null,
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
  }],
});

function submit() {
  form.post(route("contractguides.store"), {
    preserveScroll: true,
  });
}
</script>

<template>
  <form class="pl-page space-y-6" @submit.prevent="submit">
    <PageHeader :trail="[{ title: 'Guias de contrato', url: route('contractguides.index') }, { title: 'Nova guia de contrato' }]" title="Nova guia de contrato" lede="Registe destino, referências de transporte e rastreabilidade dos produtos." />

    <ContractGuideForm :form="form" submit-label="Criar guia" />
  </form>
</template>
