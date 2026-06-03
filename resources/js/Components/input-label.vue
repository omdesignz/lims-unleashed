<template>
    <label class="block text-sm font-semibold text-slate-800 dark:text-slate-100">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>

<script setup>
defineProps({ value: String });
</script>
