<script setup>
import { computed } from 'vue'
import { X as XMarkIcon } from '@lucide/vue'

/**
 * The Plano selection bar: an ink plane that appears once rows are selected and
 * offers each bulk action as its own button. Archiving is offered only while a
 * live record is selected, restoring only while an archived one is.
 */
const props = defineProps({
  actions: {
    type: Array,
    default: () => [],
  },
  recordIds: {
    type: Array,
    default: () => [],
  },
  records: {
    type: Array,
    default: () => [],
  },
  processing: Boolean,
})

const emit = defineEmits(['execute', 'clear'])

const selectedRecords = computed(() => props.records.filter(record => props.recordIds.includes(record.id)))
const hasArchived = computed(() => selectedRecords.value.some(record => record.deleted))
const hasLive = computed(() => selectedRecords.value.some(record => !record.deleted))

const actionableActions = computed(() => props.actions.filter((action) => {
  if (!action.id) return false
  if (!selectedRecords.value.length) return true
  if (action.id === 'restore') return hasArchived.value
  if (action.id === 'delete') return hasLive.value

  return true
}))

function executeAction(actionId) {
  if (props.processing || !actionId || !props.recordIds.length) {
    return
  }

  emit('execute', actionId)
}
</script>

<template>
  <div v-if="recordIds.length" class="pl-selection" role="region" :aria-label="$t('gestlab.actions.bulk_actions_text')" :aria-busy="processing">
    <span role="status"><span class="pl-num">{{ recordIds.length }}</span> {{ $t('gestlab.general.labels.selected_records') }}</span>
    <button
      v-for="action in actionableActions"
      :key="action.id"
      type="button"
      :disabled="processing"
      @click="executeAction(action.id)"
    >
      {{ $t(action.label) }}
    </button>
    <button type="button" :disabled="processing" :aria-label="$t('gestlab.general.labels.clear_selection')" @click="emit('clear')">
      <XMarkIcon class="h-4 w-4" aria-hidden="true" />
    </button>
  </div>
</template>
