<template>
  <section class="relative overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0]/95 p-5 shadow-[0_22px_70px_rgba(20,61,55,0.09)] transition dark:border-[#25443c] dark:bg-[#0c1714]/92 dark:shadow-black/25 sm:p-6">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top_right,rgba(var(--primary-400-rgb),0.12),transparent_36%)] dark:bg-[radial-gradient(circle_at_top_right,rgba(var(--accent-300-rgb),0.10),transparent_38%)]" />
    <div class="relative">
    <div v-if="title || description || $slots.actions" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 v-if="title" class="text-lg font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ title }}</h2>
        <p v-if="description" class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ description }}</p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
