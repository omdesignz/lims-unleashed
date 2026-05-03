<!-- resources/js/Pages/FormulaDisplay.vue -->
<template>
  <div v-html="renderedFormula" class="m-2"></div>
</template>

<script setup>
import { ref, watch } from "vue";
import katex from "katex";
import "katex/dist/katex.min.css";

const props = defineProps({
  formula: {
    type: String,
    default: '',
    required: true,
  },
});

const renderedFormula = ref("");

const renderFormula = (formula) => {
  try {
    renderedFormula.value = katex.renderToString(formula, {
      throwOnError: false,
    });
  } catch (e) {
    renderedFormula.value = "";
  }
};

watch(
  () => props.formula,
  (newVal) => {
    renderFormula(newVal);
  },
  { immediate: true },
);
</script>
