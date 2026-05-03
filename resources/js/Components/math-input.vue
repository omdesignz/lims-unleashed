<!-- resources/js/Pages/MathInput.vue -->
<template>
  <div>
    <math-field
      class="block w-full rounded-md border-0 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-blue-800 sm:text-sm sm:leading-6"
      ref="mathfield"
      :value="value"
      @input="updateValue"
    ></math-field>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from "vue";
import { MathfieldElement } from "mathlive";
import { ComputeEngine } from '@cortex-js/compute-engine';
import { translations } from "../mathlive-translations.js"

const props = defineProps({
  value: {
    type: String,
    default: "",
  },
  locale: {
      type: String,
      default: 'es'
  }
});


const emit = defineEmits(["update:value", "update:expression", "letters"]);

const mathfield = ref(null);

const computeEngine = new ComputeEngine();
const knownFunctions = ['sqrt', 'sin', 'cos', 'tan', 'log', 'ln', 'exp', 'placeholder', 'forall', 'pi', 'stddev', 'x', 'n', 'exponentialE', 'imaginaryI', 'infty', 'int', 'mathrm', 'left', 'right', 'double', 'sum', 'frac', 'cdot']; // Add other functions as needed

onMounted(() => {
  mathfield.value.setValue(props.value);

  if (mathfield) {
      // Apply translations and locale
      // MathfieldElement.locales = { ...MathfieldElement.locales, ...translations };
      mathfield.locale = props.locale;

      window.mathVirtualKeyboard.layouts = ["compact"];

      // window.mathfield.locales = { ...MathfieldElement.locales, ...translations };

      // mathfield.value.mathVirtualKeyboard.layouts = ["minimalist"];

      // Customize the context menu
      mathfield.contextMenu = {
          // Specify the commands you want in the context menu
          commands: [
              'clear', 'undo', 'redo', 'copy', 'cut', 'paste', 'delete', 'selectAll'
          ]
      };
  }
});

const updateValue = (event) => {
  const latex = event.target.value;
  emit("update:value", latex);
  emit("update:expression", latex); // Emit plain math expression
  extractLetters(latex);
};

const extractLetters = (latex) => {
      const letters = new Set();
      const regex = /[a-zA-Z]+/g;
      let match;
      while ((match = regex.exec(latex)) !== null) {
        if (!knownFunctions.includes(match[0])) {
          for (const letter of match[0]) {
            letters.add(letter);
          }
        }
      }
      emit('letters', Array.from(letters));
    };

watch(
  () => props.value,
  (newVal) => {
    if (mathfield.value.getValue() !== newVal) {
      mathfield.value.setValue(newVal);
    }
  },
);
</script>

<style>
/* @import "mathlive/dist/mathlive-static.css"; */
</style>
