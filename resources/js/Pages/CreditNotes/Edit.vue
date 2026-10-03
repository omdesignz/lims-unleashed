<script setup>
import Layout from '@/Shared/Layouts/Layout.vue';
import FinancialObservationForm from '@/Components/documents/FinancialObservationForm.vue';
defineOptions({ layout: Layout });
defineProps({ record: { type: Object, required: true } });
</script>
<template>
  <FinancialObservationForm kind="credit_note" :record="record" />
</template>
