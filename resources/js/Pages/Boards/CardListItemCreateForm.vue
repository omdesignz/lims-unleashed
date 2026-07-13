<script setup>
import { store } from '@/Stores/store.js'
import { CheckIcon, PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'

const props = defineProps({ list: Object })
const emit = defineEmits(['created'])
const inputTitleRef = ref(null)
const isShowingForm = computed(() => props.list.id === store.value.listCreatingCardId)
const form = useForm({
  title: '',
  card_list_id: props.list.id,
  board_id: props.list.board_id,
})

async function showForm() {
  store.value.listCreatingCardId = props.list.id
  await nextTick()
  inputTitleRef.value?.focus()
}

function closeForm() {
  form.reset('title')
  form.clearErrors()
  store.value.listCreatingCardId = null
}

function submit() {
  form.post(route('cards.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('title')
      inputTitleRef.value?.focus()
      emit('created')
    },
  })
}
</script>

<template>
  <form v-if="isShowingForm" class="space-y-2" @keydown.esc="closeForm" @submit.prevent="submit">
    <textarea
      ref="inputTitleRef"
      v-model="form.title"
      rows="3"
      class="ds-field resize-none"
      :aria-invalid="Boolean(form.errors.title)"
      :placeholder="$t('gestlab.general.labels.kanban.card_title_placeholder')"
      @keydown.enter.exact.prevent="submit"
    />
    <p v-if="form.errors.title" class="ds-field-error">{{ form.errors.title }}</p>
    <div class="flex items-center gap-2">
      <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">
        <CheckIcon class="h-4 w-4" />
        {{ $t('gestlab.general.labels.kanban.add_card') }}
      </button>
      <button type="button" class="ds-icon-button border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.cancel')" @click="closeForm">
        <XMarkIcon class="h-4 w-4" />
      </button>
    </div>
  </form>

  <button v-else type="button" class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-sm font-bold text-[var(--ds-text-muted)] hover:bg-[var(--ds-panel-subtle)] hover:text-[rgb(var(--primary-700-rgb))]" @click="showForm">
    <PlusIcon class="h-4 w-4" />
    {{ $t('gestlab.general.labels.kanban.add_card') }}
  </button>
</template>
