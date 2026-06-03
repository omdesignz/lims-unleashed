<template>
    <button :type="type" class="inline-flex items-center justify-center rounded-2xl border border-transparent bg-rose-600 px-4 py-2.5 text-xs font-bold uppercase tracking-[0.18em] text-white shadow-sm shadow-rose-900/15 transition hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/25 focus:ring-offset-2 active:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-950">
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
