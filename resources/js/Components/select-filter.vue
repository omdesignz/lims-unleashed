<template>
  <div class="flex flex-wrap items-center gap-2">
    <Listbox :model-value="filterId" as="div" class="relative min-w-56" @update:model-value="selectFilter">
      <ListboxButton class="group inline-flex w-full items-center justify-between gap-3 rounded-2xl border border-[#d8cbb8] bg-white px-3.5 py-3 text-left text-sm font-semibold text-[#15231f] shadow-sm transition hover:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:hover:bg-[#10231f]">
        <span class="inline-flex min-w-0 items-center gap-2">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#f7f1e7] text-[rgb(var(--primary-800-rgb))] transition group-hover:bg-[rgb(var(--primary-50-rgb))] dark:bg-[#10231f] dark:text-[rgb(var(--primary-200-rgb))]">
            <FunnelIcon class="h-4 w-4" />
          </span>
          <span class="truncate">{{ $t(selectedFilterLabel) }}</span>
        </span>

        <ChevronUpDownIcon class="h-5 w-5 shrink-0 text-[#8d9b94] dark:text-[#657970]" />
      </ListboxButton>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <ListboxOptions class="absolute left-0 z-50 mt-2 max-h-72 w-full overflow-auto rounded-[1.35rem] border border-[#ded3bf] bg-[#fffdf7] p-2 shadow-[0_22px_70px_rgb(20_61_55/0.18)] ring-1 ring-white/70 focus:outline-none dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
          <ListboxOption
            v-for="filter in normalizedFilters"
            :key="filter.id ?? 'none'"
            v-slot="{ active, selected }"
            as="template"
            :value="filter.id"
          >
            <li
              :class="[
                'flex cursor-pointer select-none items-center justify-between gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition',
                active ? 'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]' : 'text-[#31413b] dark:text-[#d7e2dd]',
              ]"
            >
              <span class="truncate">{{ $t(filter.label) }}</span>
              <CheckIcon
                v-if="selected"
                class="h-4 w-4"
                :class="active ? 'text-white dark:text-[#07110f]' : 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]'"
              />
            </li>
          </ListboxOption>
        </ListboxOptions>
      </TransitionRoot>
    </Listbox>

    <div
      v-if="hasActiveFilter"
      class="inline-flex items-center gap-1.5 rounded-full border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-3 py-1.5 text-xs dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)]"
    >
      <span class="font-semibold text-[rgb(var(--primary-900-rgb))] dark:text-[rgb(var(--primary-100-rgb))]">
        {{ $t(selectedFilterLabel) }}
      </span>
      <button
        type="button"
        class="rounded-full p-0.5 text-[rgb(var(--primary-700-rgb))] transition-colors duration-150 hover:text-[rgb(var(--primary-900-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)] dark:text-[rgb(var(--primary-200-rgb))] dark:hover:text-[rgb(var(--primary-50-rgb))]"
        :title="$t('gestlab.general.buttons.clear')"
        @click="clearFilter"
      >
        <XMarkIcon class="h-3 w-3" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Listbox, ListboxButton, ListboxOption, ListboxOptions, TransitionRoot } from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, FunnelIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  filters: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['execute'])

const filterId = ref(null)

const normalizedFilters = computed(() => {
  return props.filters.length ? props.filters : [{ id: null, label: 'gestlab.filter.filter' }]
})

const selectedFilter = computed(() => {
  return normalizedFilters.value.find(filter => filter.id === filterId.value) ?? normalizedFilters.value[0]
})

const selectedFilterLabel = computed(() => selectedFilter.value?.label ?? 'gestlab.filter.select_filter')
const hasActiveFilter = computed(() => filterId.value !== null && filterId.value !== '')

function selectFilter(value) {
  filterId.value = value
  emit('execute', value)
}

function clearFilter() {
  selectFilter(null)
}
</script>
