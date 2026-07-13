<script setup>
import TradeCertificateDetail from "@/Components/certificates/TradeCertificateDetail.vue";
import Layout from "@/Shared/Layouts/Layout.vue";

defineOptions({ layout: Layout });

defineProps({
  record: { type: Object, required: true },
});
</script>

<template>
  <TradeCertificateDetail kind="import" :record="record" />
</template>
