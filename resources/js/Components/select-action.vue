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
        :class="[
          'group inline-flex w-full items-center justify-between gap-3 rounded-2xl border px-3.5 py-3 text-left text-sm font-semibold shadow-sm transition focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)]',
          recordIds.length
            ? 'border-[#d8cbb8] bg-white text-[#15231f] hover:border-[rgb(var(--primary-500-rgb))] dark:border-[#315149] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:hover:bg-[#10231f]'
            : 'cursor-not-allowed border-[#e7ddce] bg-[#f7f1e7]/70 text-[#8d9b94] dark:border-[#213a34] dark:bg-[#10231f]/50 dark:text-[#657970]',
        ]"
      >
        <span class="inline-flex min-w-0 items-center gap-2">
          <span
            :class="[
              'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition',
              recordIds.length
                ? 'bg-[#f7f1e7] text-[rgb(var(--primary-800-rgb))] group-hover:bg-[rgb(var(--primary-50-rgb))] dark:bg-[#10231f] dark:text-[rgb(var(--primary-200-rgb))]'
                : 'bg-[#eee4d5] text-[#8d9b94] dark:bg-[#0b1b17] dark:text-[#657970]',
            ]"
          >
            <QueueListIcon class="h-4 w-4" />
          </span>
          <span class="truncate">{{ $t(selectedActionLabel) }}</span>
        </span>

        <span class="inline-flex items-center gap-2">
          <span
            v-if="recordIds.length"
            class="rounded-full bg-[rgb(var(--primary-800-rgb))] px-2 py-0.5 text-[11px] font-black text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]"
          >
            {{ recordIds.length }}
          </span>
          <ChevronUpDownIcon class="h-5 w-5 shrink-0 text-[#8d9b94] dark:text-[#657970]" />
        </span>
      </ListboxButton>

      <TransitionRoot
        leave="transition ease-in duration-100"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <ListboxOptions class="absolute left-0 z-50 mt-2 max-h-72 w-full overflow-auto rounded-[1.35rem] border border-[#ded3bf] bg-[#fffdf7] p-2 shadow-[0_22px_70px_rgb(20_61_55/0.18)] ring-1 ring-white/70 focus:outline-none dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
          <ListboxOption
            v-for="action in actionableActions"
            :key="action.id"
            v-slot="{ active, selected }"
            as="template"
            :value="action.id"
          >
            <li
              :class="[
                'flex cursor-pointer select-none items-center justify-between gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition',
                active ? 'bg-[rgb(var(--primary-800-rgb))] text-white dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f]' : 'text-[#31413b] dark:text-[#d7e2dd]',
              ]"
            >
              <span class="truncate">{{ $t(action.label) }}</span>
              <CheckIcon
                v-if="selected"
                class="h-4 w-4"
                :class="active ? 'text-white dark:text-[#07110f]' : 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--primary-200-rgb))]'"
              />
            </li>
          </ListboxOption>

          <li v-if="!actionableActions.length" class="rounded-2xl px-3 py-2.5 text-sm font-medium text-[#73827b] dark:text-[#8ea49b]">
            {{ $t('gestlab.general.messages.no_items') }}
          </li>
        </ListboxOptions>
      </TransitionRoot>
    </Listbox>

    <button
      :disabled="!actionId || !recordIds.length"
      :class="[
        'inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-semibold shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 focus:ring-offset-[#fffdf7] dark:focus:ring-offset-[#07110f]',
        actionId && recordIds.length
          ? 'bg-[rgb(var(--primary-800-rgb))] text-white hover:bg-[rgb(var(--primary-700-rgb))] dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]'
          : 'cursor-not-allowed bg-[#f7f1e7] text-[#8d9b94] dark:bg-[#10231f] dark:text-[#657970]',
      ]"
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
