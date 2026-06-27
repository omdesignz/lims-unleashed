<template>
  <div class="flex flex-wrap items-center gap-2.5">
    <Listbox
      :disabled="!recordIds.length"
      :model-value="actionId"
      as="div"
      class="relative min-w-60"
      @update:model-value="selectAction"
    >
      <ListboxButton
        class="ds-combobox-control group inline-flex w-full items-center justify-between gap-3 px-3.5 py-2 text-left text-sm font-semibold"
        :data-disabled="!recordIds.length"
      >
        <span class="inline-flex min-w-0 items-center gap-2">
          <span
            :class="[
              'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition',
              recordIds.length
                ? 'bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))] group-hover:bg-[rgb(var(--primary-50-rgb))] dark:text-[rgb(var(--accent-200-rgb))]'
                : 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-soft)]',
            ]"
          >
            <QueueListIcon class="h-4 w-4" />
          </span>
          <span class="truncate">{{ $t(selectedActionLabel) }}</span>
        </span>

        <span class="inline-flex items-center gap-2">
          <span
            v-if="recordIds.length"
            class="rounded-full bg-[rgb(var(--primary-800-rgb))] px-2 py-0.5 text-[11px] font-black text-white dark:bg-[rgb(var(--primary-400-rgb))] dark:text-[rgb(var(--primary-950-rgb))]"
          >
            {{ recordIds.length }}
          </span>
          <ChevronUpDownIcon class="h-5 w-5 shrink-0 text-[var(--ds-text-soft)]" />
        </span>
      </ListboxButton>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <ListboxOptions class="ds-floating-panel absolute left-0 z-50 mt-2 max-h-72 w-full overflow-auto p-2 focus:outline-none">
          <ListboxOption
            v-for="action in actionableActions"
            :key="action.id"
            v-slot="{ active, selected }"
            as="template"
            :value="action.id"
          >
            <li
              class="ds-option ds-option-compact justify-between gap-3"
              :class="{ 'ds-option-active': active }"
            >
              <span class="truncate">{{ $t(action.label) }}</span>
              <CheckIcon
                v-if="selected"
                class="h-4 w-4 text-current"
              />
            </li>
          </ListboxOption>

          <li v-if="!actionableActions.length" class="rounded-2xl px-3 py-2.5 text-sm font-semibold text-[var(--ds-text-muted)]">
            {{ $t('gestlab.general.messages.no_items') }}
          </li>
        </ListboxOptions>
      </TransitionRoot>
    </Listbox>

    <button
      :disabled="!actionId || !recordIds.length"
      class="ds-button ds-button-primary"
      @click="executeSelectedAction"
    >
      <CursorArrowRippleIcon class="h-4 w-4" />
      {{ $t('gestlab.actions.apply') }}
    </button>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Listbox, ListboxButton, ListboxOption, ListboxOptions, TransitionRoot } from '@headlessui/vue'
import { CheckIcon, ChevronUpDownIcon, CursorArrowRippleIcon, QueueListIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  actions: {
    type: Array,
    default: () => [],
  },
  recordIds: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['execute'])
const actionId = ref(null)

const actionableActions = computed(() => props.actions.filter(action => action.id))
const selectedAction = computed(() => actionableActions.value.find(action => action.id === actionId.value))
const selectedActionLabel = computed(() => selectedAction.value?.label ?? 'gestlab.actions.select_action')

watch(
  () => props.recordIds.length,
  count => {
    if (!count) {
      actionId.value = null
    }
  },
)

function selectAction(value) {
  actionId.value = value
}

function executeSelectedAction() {
  if (!actionId.value || !props.recordIds.length) {
    return
  }

  emit('execute', actionId.value)
}
</script>
