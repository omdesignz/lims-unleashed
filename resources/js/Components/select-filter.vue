<template>
  <div class="flex flex-wrap items-center gap-2">
    <Listbox :model-value="filterId" as="div" class="relative min-w-56" @update:model-value="selectFilter">
      <ListboxButton class="ds-combobox-control group inline-flex w-full items-center justify-between gap-3 px-3.5 py-2 text-left text-sm font-semibold">
        <span class="inline-flex min-w-0 items-center gap-2">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] transition group-hover:bg-[rgb(var(--primary-50-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">
            <FunnelIcon class="h-4 w-4" />
          </span>
          <span class="truncate">{{ $t(selectedFilterLabel) }}</span>
        </span>

        <ChevronUpDownIcon class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
      </ListboxButton>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <ListboxOptions class="ds-floating-panel absolute left-0 z-50 mt-2 max-h-72 w-full overflow-auto p-2 focus:outline-none">
          <ListboxOption
            v-for="filter in normalizedFilters"
            :key="filter.id ?? 'none'"
            v-slot="{ active, selected }"
            as="template"
            :value="filter.id"
          >
            <li
              class="ds-option ds-option-compact justify-between gap-3"
              :class="{ 'ds-option-active': active }"
            >
              <span class="truncate">{{ $t(filter.label) }}</span>
              <CheckIcon
                v-if="selected"
                class="h-4 w-4 text-current"
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
