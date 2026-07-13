<script setup>
import { store } from '@/Stores/store.js'
import { CheckIcon, PencilIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'

const props = defineProps({ card: Object })
const inputTitleRef = ref(null)
const isShowingForm = computed(() => props.card.id === store.value.editingCardId)
const form = useForm({ title: props.card.title })

async function showForm() {
  store.value.editingCardId = props.card.id
  await nextTick()
  inputTitleRef.value?.focus()
}

function cancel() {
  form.title = props.card.title
  form.clearErrors()
  store.value.editingCardId = null
}

function submit() {
  form.put(route('cards.update', { card: props.card.id }), {
    preserveScroll: true,
    onSuccess: () => {
      store.value.editingCardId = null
    },
  })
}
</script>

<template>
  <li>
    <div class="group relative rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3 hover:border-[var(--ds-border-strong)]">
      <form v-if="isShowingForm" class="space-y-2" @keydown.esc="cancel" @submit.prevent="submit">
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
          <button type="submit" class="ds-icon-button h-8 w-8 border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.submit')" :disabled="form.processing">
            <CheckIcon class="h-4 w-4" />
          </button>
          <button type="button" class="ds-icon-button h-8 w-8 border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.cancel')" @click="cancel">
            <XMarkIcon class="h-4 w-4" />
          </button>
        </div>
      </form>

      <template v-else>
        <Link
          :href="route('boards.show', { board: card.board_id, card: card.id })"
          preserve-state
          class="block min-h-9 pr-7 text-sm font-semibold leading-5 text-[var(--ds-text)] hover:text-[rgb(var(--primary-700-rgb))]"
        >
          {{ card.title }}
        </Link>
        <button type="button" class="ds-icon-button absolute right-1.5 top-1.5 h-7 w-7 opacity-0 group-hover:opacity-100 focus:opacity-100" :title="$t('gestlab.general.buttons.edit')" @click="showForm">
          <PencilIcon class="h-3.5 w-3.5" />
        </button>
      </template>
    </div>
  </li>
</template>
