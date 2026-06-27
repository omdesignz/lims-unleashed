<template>
    <button :type="type" class="ds-button ds-button-primary">
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<style scoped>

</style>
