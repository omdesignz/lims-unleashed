<template>
  <section class="relative isolate overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffaf0] p-6 shadow-[0_24px_80px_rgba(20,61,55,0.10)] dark:border-[#25443c] dark:bg-[#0c1714] dark:shadow-black/30 sm:p-8">
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-400-rgb),0.18),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(var(--accent-300-rgb),0.14),transparent_38%),linear-gradient(135deg,#fffaf0,#f4efe4)] dark:bg-[radial-gradient(circle_at_top_left,rgba(var(--primary-300-rgb),0.16),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(var(--accent-300-rgb),0.10),transparent_42%),linear-gradient(135deg,#07110f,#10231f)]" />
    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
      <div class="max-w-4xl">
        <p v-if="eyebrow" class="text-xs font-bold uppercase tracking-[0.24em] text-primary-700 dark:text-accent-200">
          {{ eyebrow }}
        </p>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-[#15231f] dark:text-[#f7f1e7] sm:text-4xl">
          {{ title }}
        </h1>
        <p v-if="description" class="mt-4 max-w-3xl text-sm font-medium leading-7 text-slate-600 dark:text-slate-300">
          {{ description }}
        </p>
      </div>
      <div v-if="$slots.actions" class="flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <div v-if="$slots.default" class="mt-6">
      <slot />
    </div>
  </section>
</template>

<script setup>
defineProps({
  eyebrow: {
    type: String,
    default: '',
  },
  title: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
})
</script>
