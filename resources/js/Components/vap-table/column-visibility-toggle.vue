<template>
  <Menu as="div" class="relative inline-block text-left">
    <MenuButton
      :class="[
        'ds-button ds-button-secondary h-12',
        props.compact ? 'w-12 px-0' : 'gap-2 px-4',
      ]"
      :title="$t('gestlab.general.labels.columns')"
    >
      <EyeIcon class="h-5 w-5 text-[rgb(var(--primary-800-rgb))] dark:text-[rgb(var(--primary-200-rgb))]" aria-hidden="true" />
      <span :class="props.compact ? 'sr-only' : ''">
        {{ $t('gestlab.general.labels.columns') }}
      </span>
    </MenuButton>

    <transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <MenuItems class="ds-card absolute right-0 z-20 mt-3 w-80 origin-top-right overflow-hidden focus:outline-none">
        <div class="ds-surface-subtle border-b px-4 py-4">
          <p class="ds-kicker">
            {{ $t('gestlab.general.labels.columns') }}
          </p>
          <p class="mt-1 text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
            {{ visibleColumnsCount }} / {{ props.columns.length }}
          </p>
        </div>

        <div class="max-h-80 space-y-1 overflow-y-auto p-2">
          <MenuItem
            v-for="column in props.columns"
            :key="column.field"
            v-slot="{ active }"
          >
            <button
              type="button"
              class="flex w-full items-center justify-between gap-3 rounded-2xl px-3 py-3 text-left transition"
              :class="[
                active ? 'bg-[#f7f1e7] dark:bg-[#10231f]' : '',
                column.visible ? 'text-[#15231f] dark:text-[#f7f1e7]' : 'text-[#73827b] dark:text-[#8ea49b]',
              ]"
              @click="toggleColumnVisibility(column.field, $event)"
            >
              <span class="flex min-w-0 items-center gap-3">
                <span
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border"
                  :class="column.visible
                    ? 'border-[rgb(var(--primary-800-rgb))] bg-[rgb(var(--primary-800-rgb))] text-white dark:border-[rgb(var(--primary-500-rgb))] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]'
                    : 'border-[#ded3bf] bg-white text-[#8d9b94] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#657970]'"
                >
                  <EyeIcon v-if="column.visible" class="h-4 w-4" aria-hidden="true" />
                  <EyeSlashIcon v-else class="h-4 w-4" aria-hidden="true" />
                </span>
                <span class="truncate text-sm font-semibold">
                  {{ $t(column.label) }}
                </span>
              </span>

              <span
                class="rounded-full px-2.5 py-1 text-[11px] font-black uppercase tracking-[0.12em]"
                :class="column.visible
                  ? 'bg-[rgb(var(--primary-50-rgb)/0.85)] text-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.14)] dark:text-[rgb(var(--primary-100-rgb))]'
                  : 'bg-[#f0e6d6] text-[#6b7b74] dark:bg-[#152f29] dark:text-[#8ea49b]'"
              >
                {{ column.visible ? $t('gestlab.general.labels.visible') : $t('gestlab.general.labels.hidden') }}
              </span>
            </button>
          </MenuItem>
        </div>
      </MenuItems>
    </transition>
  </Menu>
</template>

<script setup>
import { computed } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import {
  EyeIcon,
  EyeSlashIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
  columns: {
    type: Array,
    default: () => [],
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update-columns']);

const visibleColumnsCount = computed(() => props.columns.filter(column => column.visible).length);

const toggleColumnVisibility = (field, event) => {
  event.preventDefault();

  props.columns.forEach(column => {
    if (column.field === field) {
      column.visible = !column.visible;
    }
  });

  emit('update-columns', [...props.columns]);
};
</script>
