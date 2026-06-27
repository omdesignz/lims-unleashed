<template>
    <button :type="type" class="ds-button ds-button-danger">
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
