<template>
  <div class="flex flex-wrap items-center gap-2">
    <Listbox :model-value="filterId" as="div" class="relative min-w-44" :data-active="hasActiveFilter || undefined" @update:model-value="selectFilter">
      <ListboxButton class="ds-combobox-control group inline-flex w-full items-center justify-between gap-3 px-3.5 py-2 text-left text-sm font-semibold">
        <span class="inline-flex min-w-0 items-center gap-2">
          <FunnelIcon class="h-4 w-4 shrink-0" :class="hasActiveFilter ? 'text-[var(--pl-accent-text)]' : 'text-[var(--pl-faint)]'" aria-hidden="true" />
          <span class="truncate">{{ $t(selectedFilterLabel) }}</span>
        </span>

        <ChevronUpDownIcon class="h-4 w-4 shrink-0 text-[var(--ds-text-soft)]" />
      </ListboxButton>

      <TransitionRoot
        leave="transition ease-out duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <ListboxOptions class="ds-floating-panel absolute left-0 z-50 mt-1.5 max-h-72 w-full overflow-auto focus:outline-none">
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

  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Listbox, ListboxButton, ListboxOption, ListboxOptions, TransitionRoot } from '@headlessui/vue'
import { Check as CheckIcon, ChevronsUpDown as ChevronUpDownIcon, Funnel as FunnelIcon } from '@lucide/vue'

const props = defineProps({
  filters: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: [String, Number],
    default: null,
  },
})

const emit = defineEmits(['execute'])

const filterId = ref(props.modelValue)

watch(
  () => props.modelValue,
  value => {
    filterId.value = value
  },
)

const normalizedFilters = computed(() => {
  return props.filters.length ? props.filters : [{ id: null, label: 'gestlab.filter.filter' }]
})

const selectedFilter = computed(() => {
  return normalizedFilters.value.find(filter => filter.id === filterId.value) ?? normalizedFilters.value[0]
})

const selectedFilterLabel = computed(() => selectedFilter.value?.label ?? 'gestlab.filter.select_filter')
const hasActiveFilter = computed(() => ![null, undefined, ''].includes(selectedFilter.value?.id) && selectedFilter.value?.id === filterId.value)

function selectFilter(value) {
  filterId.value = value
  emit('execute', value)
}

</script>
