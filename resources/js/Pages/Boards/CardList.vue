<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import CardListItemCreateForm from '@/Pages/Boards/CardListItemCreateForm.vue';
import { ref, watch } from 'vue';
import CardListItem from '@/Pages/Boards/CardListItem.vue';
import { VueDraggableNext } from 'vue-draggable-next';
import { router } from '@inertiajs/vue3';
import { store } from '@/Stores/store.js';
import { EllipsisHorizontalIcon } from '@heroicons/vue/24/outline';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

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
  <div class="flex h-full flex-col rounded-[1.6rem] border border-[#ded3bf] bg-[#fffdf7]/95 shadow-[0_18px_55px_rgb(20_61_55/0.10)] ring-1 ring-white/70 backdrop-blur dark:border-[#25443c] dark:bg-[#07110f]/95 dark:ring-white/10" :class="commercialDocumentThemeClasses">
    <div class="flex items-center justify-between border-b border-[#ded3bf] px-4 py-4 dark:border-[#25443c]">
      <h3 class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
        {{ list.name }}
        <span class="ml-2 rounded-full bg-[#f7f1e7] px-2 py-0.5 text-xs font-semibold text-[#5f6f68] dark:bg-[#10231f] dark:text-[#a9bbb4]">
          ({{ list.cards.length }})
        </span>
      </h3>

      <Menu as="div" class="relative">
        <MenuButton class="rounded-xl p-1.5 text-[#73827b] transition-colors duration-200 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:text-[#8ea49b] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]">
          <EllipsisHorizontalIcon class="h-5 w-5" />
        </MenuButton>

        <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-2xl border border-[#ded3bf] bg-[#fffdf7] p-1 shadow-[0_22px_70px_rgb(20_61_55/0.16)] ring-1 ring-white/70 focus:outline-none dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
          <div class="py-1">
            <MenuItem v-slot="{ active }">
              <button
                :class="[active ? 'bg-[#f7f1e7] dark:bg-[#10231f]' : '', 'block w-full rounded-xl px-4 py-2 text-left text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]']"
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
                :class="[active ? 'bg-rose-50 dark:bg-rose-500/10' : '', 'block w-full rounded-xl px-4 py-2 text-left text-sm font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-300']"
              >
                {{ $t('gestlab.general.labels.kanban.delete_list') }}
              </Link>
            </MenuItem>
          </div>
        </MenuItems>
      </Menu>
    </div>

    <div ref="listRef" class="flex-1 space-y-3 overflow-y-auto bg-[#f7f1e7]/65 p-3 dark:bg-[#10231f]/45">
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

    <div class="border-t border-[#ded3bf] p-3 dark:border-[#25443c]">
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
  background: #f7f1e7;
  border: 2px dashed #d8cbb8;
  border-radius: 16px;
}

.ghost > div {
  visibility: hidden;
}
</style>
