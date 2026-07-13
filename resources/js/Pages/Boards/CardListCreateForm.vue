<script setup>
import { CheckIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const props = defineProps({ board: Object })
const inputNameRef = ref(null)
const isShowingForm = ref(false)
const form = useForm({ name: '' })

async function showForm() {
  isShowingForm.value = true
  await nextTick()
  inputNameRef.value?.focus()
}

function closeForm() {
  form.reset()
  form.clearErrors()
  isShowingForm.value = false
}

function submit() {
  form.post(route('cardLists.store', { board: props.board.id }), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      inputNameRef.value?.focus()
    },
  })
}
</script>

<template>
  <form v-if="isShowingForm" class="ds-panel space-y-3 p-3" @keydown.esc="closeForm" @submit.prevent="submit">
    <div class="ds-field-group">
      <label for="new-list-name" class="ds-field-label">{{ $t('gestlab.general.labels.kanban.list_name_placeholder') }}</label>
      <input
        id="new-list-name"
        ref="inputNameRef"
        v-model="form.name"
        type="text"
        class="ds-field"
        :aria-invalid="Boolean(form.errors.name)"
        :placeholder="$t('gestlab.general.labels.kanban.list_name_placeholder')"
      />
      <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
    </div>
    <div class="flex items-center gap-2">
      <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
        <CheckIcon class="h-4 w-4" />
        {{ $t('gestlab.general.labels.kanban.add_list') }}
      </button>
      <button type="button" class="ds-icon-button border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.cancel')" @click="closeForm">
        <XMarkIcon class="h-4 w-4" />
      </button>
    </div>
  </form>

  <button v-else type="button" class="flex w-full items-center gap-2 rounded-lg border border-dashed border-[var(--ds-border-strong)] bg-[var(--ds-panel)] px-4 py-3 text-sm font-bold text-[var(--ds-text-muted)] hover:border-[rgb(var(--primary-400-rgb))] hover:text-[rgb(var(--primary-700-rgb))]" @click="showForm">
    <PlusIcon class="h-4 w-4" />
    {{ $t('gestlab.general.labels.kanban.add_list') }}
  </button>
</template>
