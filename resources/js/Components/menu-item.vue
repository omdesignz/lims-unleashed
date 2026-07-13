<template>
  <li>
    <a href="#" class="ds-command-palette-item">
      <component
        v-if="element.icon"
        :is="element.icon"
        class="h-5 w-5 shrink-0 text-[rgb(var(--primary-700-rgb)/1)] dark:text-cyan-100"
        aria-hidden="true"
      />
      <span class="min-w-0 flex-1 truncate text-sm font-bold text-[var(--ds-text)]">
        {{ element.title }} {{ index }}
      </span>
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]">
        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
      </svg>
    </a>
    <nested-menu-structure :items="element.children" class="px-4 py-3" />
  </li>
</template>

<script setup>
import NestedMenuStructure from "./nested-menu-structure.vue";

defineProps({
  element: [Array, Object],
  index: Number,
});
</script>
