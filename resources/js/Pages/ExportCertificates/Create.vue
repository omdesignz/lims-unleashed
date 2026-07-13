<script setup>
import TradeCertificateForm from "@/Components/certificates/TradeCertificateForm.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });
</script>

<template>
  <TradeCertificateForm kind="export" />
</template>
