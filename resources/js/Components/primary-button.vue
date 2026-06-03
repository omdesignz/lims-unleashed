<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-2xl border border-transparent bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-xs font-bold uppercase tracking-[0.18em] text-white shadow-[0_12px_30px_rgb(var(--primary-900-rgb)/0.14)] transition hover:bg-[rgb(var(--primary-700-rgb))] active:bg-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.32)] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-950">
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
