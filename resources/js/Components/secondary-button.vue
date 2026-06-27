<template>
    <button :type="type" class="ds-button ds-button-secondary">
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<style scoped>

</style>
