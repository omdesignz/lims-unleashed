<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-2xl border border-[#d8cfbe] bg-[#fffdf7] px-4 py-2.5 text-xs font-bold uppercase tracking-[0.18em] text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-300-rgb))] hover:bg-[#f7f1e7] hover:text-[rgb(var(--primary-800-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] focus:ring-offset-2 active:bg-[#ede5d6] disabled:cursor-not-allowed disabled:opacity-50 dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:hover:border-[rgb(var(--primary-400-rgb))] dark:hover:bg-[#10231f] dark:focus:ring-offset-slate-950">
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
