<template>
    <div v-show="message">
        <p class="ds-field-error mt-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 dark:border-rose-500/20 dark:bg-rose-950/35">
            {{ message }}
        </p>
    </div>
</template>

<script setup>
defineProps({ message: String });
</script>
