<script setup>
import { store } from '@/Stores/store.js'
import { Equal as Bars2Icon, Check as CheckIcon, Pencil as PencilIcon, Users as UserGroupIcon, X as XMarkIcon } from '@lucide/vue'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref } from 'vue'

const props = defineProps({
  card: Object,
  accentColor: {
    type: String,
    default: '#0f766e',
  },
})
const inputTitleRef = ref(null)
const isShowingForm = computed(() => props.card.id === store.value.editingCardId)
const form = useForm({ title: props.card.title })
const members = computed(() => props.card.members ?? [])
const responsibleMembers = computed(() => members.value.filter((member) => member.is_responsible))

function initials(name) {
  return String(name || '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0))
    .join('')
    .toUpperCase()
}

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
    <article class="group relative overflow-hidden rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] shadow-sm transition hover:-translate-y-px hover:border-[var(--ds-border-strong)] hover:shadow-md focus-within:border-[rgb(var(--primary-500-rgb))]">
      <span class="absolute inset-y-0 left-0 w-0.5" :style="{ backgroundColor: accentColor }" />

      <form v-if="isShowingForm" class="space-y-2 p-3 pl-4" @keydown.esc="cancel" @submit.prevent="submit">
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
        <div class="flex items-start gap-2 p-3 pl-3.5">
          <button type="button" class="kanban-card-handle mt-0.5 grid h-7 w-6 shrink-0 cursor-grab place-items-center rounded text-[var(--ds-text-soft)] hover:bg-[var(--ds-panel-subtle)] hover:text-[var(--ds-text-muted)] active:cursor-grabbing" title="Mover cartão">
            <Bars2Icon class="h-4 w-4" />
          </button>

          <div class="min-w-0 flex-1">
            <Link
              :href="route('boards.show', { board: card.board_id, card: card.id })"
              preserve-state
              class="block text-sm font-bold leading-5 text-[var(--ds-text)] hover:text-[rgb(var(--primary-700-rgb))]"
            >
              {{ card.title }}
            </Link>
            <p v-if="card.description" class="mt-1.5 line-clamp-3 text-xs font-medium leading-5 text-[var(--ds-text-muted)]">
              {{ card.description }}
            </p>

            <div v-if="members.length" class="mt-3 flex items-center justify-between gap-3 border-t border-[var(--ds-border)] pt-2.5">
              <div class="flex -space-x-1.5">
                <span
                  v-for="member in members.slice(0, 4)"
                  :key="member.id ?? member.user_id"
                  class="grid h-6 w-6 place-items-center rounded-full border-2 border-[var(--ds-panel-raised)] bg-[rgb(var(--primary-100-rgb))] text-[9px] font-black text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-800-rgb))] dark:text-white"
                  :title="member.user?.name"
                >
                  {{ initials(member.user?.name) }}
                </span>
                <span v-if="members.length > 4" class="grid h-6 min-w-6 place-items-center rounded-full border-2 border-[var(--ds-panel-raised)] bg-[var(--ds-panel-subtle)] px-1 text-[9px] font-black text-[var(--ds-text-muted)]">
                  +{{ members.length - 4 }}
                </span>
              </div>
              <span class="inline-flex items-center gap-1 text-[10px] font-bold text-[var(--ds-text-soft)]">
                <UserGroupIcon class="h-3.5 w-3.5" />
                {{ responsibleMembers.length || members.length }}
              </span>
            </div>
          </div>

          <button type="button" class="ds-icon-button h-7 w-7 shrink-0 opacity-0 group-hover:opacity-100 focus:opacity-100" :title="$t('gestlab.general.buttons.edit')" @click="showForm">
            <PencilIcon class="h-3.5 w-3.5" />
          </button>
        </div>
      </template>
    </article>
  </li>
</template>
