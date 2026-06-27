<template>
    <label class="ds-field-label block">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>

<script setup>
defineProps({ value: String });
</script>
