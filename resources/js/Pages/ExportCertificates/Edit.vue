<script setup>
import TradeCertificateForm from "@/Components/certificates/TradeCertificateForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  record: { type: Object, required: true },
});
</script>

<template>
  <TradeCertificateForm kind="export" :record="record" />
</template>
