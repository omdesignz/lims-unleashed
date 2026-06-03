<template>
    <input
        ref="input"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        class="block w-full rounded-2xl border border-slate-300/90 bg-white/95 px-4 py-3 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-white/50 transition placeholder:text-slate-400 focus:border-[rgb(var(--primary-500-rgb))] focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.2)] dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:ring-slate-800/60 dark:placeholder:text-slate-500"
    >
</template>

<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>
