<template>
    <div v-show="message">
        <p class="mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-950/35 dark:text-rose-200">
            {{ message }}
        </p>
    </div>
</template>

<script setup>
defineProps({ message: String });
</script>
