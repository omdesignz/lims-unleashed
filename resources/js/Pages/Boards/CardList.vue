<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import CardListItemCreateForm from '@/Pages/Boards/CardListItemCreateForm.vue';
import { ref, watch } from 'vue';
import CardListItem from '@/Pages/Boards/CardListItem.vue';
import { VueDraggableNext } from 'vue-draggable-next';
import { router } from '@inertiajs/vue3';
import { store } from '@/Stores/store.js';
import { EllipsisHorizontalIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  list: Object,
});

const listRef = ref();
const cards = ref(props.list.cards);

watch(() => props.list.cards, (newCards) => {
  cards.value = newCards;
});

function onCardCreated() {
  listRef.value.scrollTop = listRef.value.scrollHeight;
}

function onChange(event) {
  const item = event.added || event.moved;

  if (! item) {
    return;
  }

  const index = item.newIndex;
  const previousCard = cards.value[index - 1];
  const nextCard = cards.value[index + 1];
  const currentCard = cards.value[index];

  let position = currentCard.position;

  if (previousCard && nextCard) {
    position = (previousCard.position + nextCard.position) / 2;
  } else if (previousCard) {
    position = previousCard.position + (previousCard.position / 2);
  } else if (nextCard) {
    position = nextCard.position / 2;
  }

  router.put(route('cards.move', { card: currentCard.id }), {
    position,
    cardListId: props.list.id,
  });
}
</script>

<template>
  <div class="flex h-full flex-col rounded-[1.5rem] border border-slate-200 bg-white/90 shadow-sm backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
    <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4 dark:border-slate-800">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
        {{ list.name }}
        <span class="ml-2 text-xs font-normal text-slate-500 dark:text-slate-400">
          ({{ list.cards.length }})
        </span>
      </h3>

      <Menu as="div" class="relative">
        <MenuButton class="rounded-xl p-1.5 text-slate-400 transition-colors duration-200 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
          <EllipsisHorizontalIcon class="h-5 w-5" />
        </MenuButton>

        <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-2xl border border-slate-200 bg-white p-1 shadow-xl ring-1 ring-black/5 focus:outline-none dark:border-slate-800 dark:bg-slate-900">
          <div class="py-1">
            <MenuItem v-slot="{ active }">
              <button
                :class="[active ? 'bg-slate-100 dark:bg-slate-800' : '', 'block w-full rounded-xl px-4 py-2 text-left text-sm text-slate-700 dark:text-slate-200']"
                @click="store.listCreatingCardId = list.id"
              >
                {{ $t('gestlab.general.labels.kanban.add_card') }}
              </button>
            </MenuItem>

            <MenuItem v-slot="{ active }">
              <Link
                as="button"
                method="delete"
                :href="route('cardLists.destroy', { board: list.board_id, list: list.id })"
                :class="[active ? 'bg-slate-100 dark:bg-slate-800' : '', 'block w-full rounded-xl px-4 py-2 text-left text-sm text-red-600 hover:text-red-700']"
              >
                {{ $t('gestlab.general.labels.kanban.delete_list') }}
              </Link>
            </MenuItem>
          </div>
        </MenuItems>
      </Menu>
    </div>

    <div ref="listRef" class="flex-1 space-y-3 overflow-y-auto bg-slate-50/70 p-3 dark:bg-slate-950/20">
      <VueDraggableNext
        v-model="cards"
        :disabled="!!store.editingCardId"
        class="space-y-3"
        drag-class="drag"
        ghost-class="ghost"
        group="cards"
        item-key="id"
        tag="ul"
        @change="onChange"
      >
        <CardListItem v-for="card in cards" :key="card.id" :card="card" />
      </VueDraggableNext>
    </div>

    <div class="border-t border-slate-200 p-3 dark:border-slate-800">
      <CardListItemCreateForm :list="list" @created="onCardCreated()" />
    </div>
  </div>
</template>

<style scoped>
.drag > div {
  transform: rotate(5deg);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

.ghost {
  background: #f3f4f6;
  border: 2px dashed #d1d5db;
  border-radius: 16px;
}

.ghost > div {
  visibility: hidden;
}
</style>
