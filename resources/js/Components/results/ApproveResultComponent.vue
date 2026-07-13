<script setup>
import ResultReviewSurface from "@/Components/results/ResultReviewSurface.vue";

defineProps({
  form: {
    type: Object,
    required: true,
  },
  record: {
    type: Object,
    default: () => ({}),
  },
  action: {
    type: String,
    default: "approve",
  },
  separatedResults: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(["open-calculation", "submit"]);
</script>

<template>
  <ResultReviewSurface
    mode="approve"
    :form="form"
    :record="record"
    :action="action"
    :separated-results="separatedResults"
    @open-calculation="emit('open-calculation')"
    @submit="emit('submit')"
  />
</template>
