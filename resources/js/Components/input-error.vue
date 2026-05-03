<template>
    <div v-show="message">
        <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">
            {{ message }}
        </p>
    </div>
</template>

<script setup>
defineProps({ message: String });
</script>