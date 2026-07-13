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
    default: "verify",
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
    mode="verify"
    :form="form"
    :record="record"
    :action="action"
    :separated-results="separatedResults"
    @open-calculation="emit('open-calculation')"
    @submit="emit('submit')"
  />
</template>
