<script setup>
import CardListItem from '@/Pages/Boards/CardListItem.vue'
import CardListItemCreateForm from '@/Pages/Boards/CardListItemCreateForm.vue'
import { store } from '@/Stores/store.js'
import { EllipsisHorizontalIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import { Link, router } from '@inertiajs/vue3'
import { VueDraggableNext } from 'vue-draggable-next'
import { ref, watch } from 'vue'

const props = defineProps({ list: Object })

const listRef = ref(null)
const cards = ref(props.list.cards)

watch(() => props.list.cards, (newCards) => {
  cards.value = newCards
})

function onCardCreated() {
  listRef.value?.scrollTo({ top: listRef.value.scrollHeight, behavior: 'smooth' })
}

function onChange(event) {
  const item = event.added || event.moved

  if (!item) {
    return
  }

  const index = item.newIndex
  const previousCard = cards.value[index - 1]
  const nextCard = cards.value[index + 1]
  const currentCard = cards.value[index]
  let position = currentCard.position

  if (previousCard && nextCard) {
    position = (previousCard.position + nextCard.position) / 2
  } else if (previousCard) {
    position = previousCard.position + (previousCard.position / 2)
  } else if (nextCard) {
    position = nextCard.position / 2
  }

  router.put(route('cards.move', { card: currentCard.id }), {
    position,
    cardListId: props.list.id,
  }, {
    preserveScroll: true,
  })
}
</script>

<template>
  <article class="ds-panel flex h-full flex-col overflow-hidden">
    <header class="flex items-center justify-between border-b border-[var(--ds-border)] px-4 py-3">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-bold text-[var(--ds-text)]">{{ list.name }}</h3>
        <p class="mt-0.5 text-xs font-semibold text-[var(--ds-text-muted)]">{{ list.cards.length }} {{ $t('gestlab.general.labels.kanban.cards_count') }}</p>
      </div>

      <Menu as="div" class="relative">
        <MenuButton class="ds-icon-button h-8 w-8" :title="$t('gestlab.general.buttons.actions')">
          <EllipsisHorizontalIcon class="h-5 w-5" />
        </MenuButton>
        <MenuItems class="absolute right-0 z-20 mt-2 w-48 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-1 shadow-xl focus:outline-none">
          <MenuItem v-slot="{ active }">
            <button
              type="button"
              class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-semibold text-[var(--ds-text)]"
              :class="active ? 'bg-[var(--ds-panel-subtle)]' : ''"
              @click="store.listCreatingCardId = list.id"
            >
              <PlusIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.kanban.add_card') }}
            </button>
          </MenuItem>
          <MenuItem v-slot="{ active }">
            <Link
              as="button"
              method="delete"
              :href="route('cardLists.destroy', { board: list.board_id, list: list.id })"
              class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm font-semibold text-red-600 dark:text-red-300"
              :class="active ? 'bg-red-50 dark:bg-red-500/10' : ''"
            >
              <TrashIcon class="h-4 w-4" />
              {{ $t('gestlab.general.labels.kanban.delete_list') }}
            </Link>
          </MenuItem>
        </MenuItems>
      </Menu>
    </header>

    <div ref="listRef" class="min-h-0 flex-1 overflow-y-auto bg-[var(--ds-panel-subtle)] p-3">
      <VueDraggableNext
        v-model="cards"
        :disabled="Boolean(store.editingCardId)"
        class="min-h-8 space-y-2.5"
        drag-class="rotate-1"
        ghost-class="opacity-40"
        group="cards"
        item-key="id"
        tag="ul"
        @change="onChange"
      >
        <CardListItem v-for="card in cards" :key="card.id" :card="card" />
      </VueDraggableNext>
    </div>

    <footer class="border-t border-[var(--ds-border)] p-3">
      <CardListItemCreateForm :list="list" @created="onCardCreated" />
    </footer>
  </article>
</template>
