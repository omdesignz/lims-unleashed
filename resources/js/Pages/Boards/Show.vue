<script setup>
import { TrashIcon } from '@heroicons/vue/24/outline';
import BoardNameForm from '@/Pages/Boards/BoardNameForm.vue';
import CardListCreateForm from '@/Pages/Boards/CardListCreateForm.vue';
import CardList from '@/Pages/Boards/CardList.vue';
import CardListItemModal from '@/Pages/Boards/CardListItemModal.vue';
import Layout from '@/Shared/Layouts/Layout.vue';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";

const props = defineProps({
  board: Object,
  card: Object,
});

defineOptions({
  layout: Layout,
});
</script>

<template>
  <div class="space-y-6" :class="commercialDocumentThemeClasses">
    <div class="overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_24px_80px_rgb(20_61_55/0.10)] ring-1 ring-white/70 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
      <div
        class="border-b border-white/10 px-6 py-6 text-white"
        :style="{ background: `linear-gradient(135deg, ${board.bgcolor || '#0f172a'} 0%, rgba(15, 23, 42, 0.92) 100%)` }"
      >
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="space-y-3">
            <span class="inline-flex items-center rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-white/90">
              {{ $t('gestlab.general.labels.kanban.page_title') }}
            </span>
            <BoardNameForm :board="board" />
            <p class="max-w-2xl text-sm leading-6 text-white/75">
              {{ $t('gestlab.general.labels.kanban.board_description') }}
            </p>
          </div>

          <div class="flex items-center gap-3">
            <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-right backdrop-blur">
              <div class="text-xs uppercase tracking-wide text-white/70">{{ $t('gestlab.general.labels.kanban.lists') }}</div>
              <div class="mt-1 text-2xl font-semibold text-white">{{ board.lists.length }}</div>
            </div>

            <Link
              :href="route('boards.destroy', { recordIds: [board.id] })"
              as="button"
              method="delete"
              class="inline-flex items-center gap-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition-colors duration-200 hover:bg-red-500/15"
            >
              <TrashIcon class="h-4 w-4" />
              {{ $t('gestlab.general.buttons.delete') }}
            </Link>
          </div>
        </div>
      </div>

      <div class="grid gap-3 border-t border-[#ded3bf] px-6 py-4 text-sm text-[#5f6f68] dark:border-[#25443c] dark:text-[#a9bbb4] sm:grid-cols-3">
        <div class="rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/80 px-4 py-3 dark:border-[#25443c] dark:bg-[#10231f]/80">
          <div class="text-xs font-semibold uppercase tracking-wide text-[#73827b] dark:text-[#8ea49b]">{{ $t('gestlab.general.labels.kanban.board') }}</div>
          <div class="mt-1 font-semibold text-[#15231f] dark:text-[#f7f1e7]">{{ board.name }}</div>
        </div>
        <div class="rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/80 px-4 py-3 dark:border-[#25443c] dark:bg-[#10231f]/80">
          <div class="text-xs font-semibold uppercase tracking-wide text-[#73827b] dark:text-[#8ea49b]">{{ $t('gestlab.general.labels.kanban.cards_count') }}</div>
          <div class="mt-1 font-semibold text-[#15231f] dark:text-[#f7f1e7]">
            {{ board.lists.reduce((total, list) => total + list.cards.length, 0) }}
          </div>
        </div>
        <div class="rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/80 px-4 py-3 dark:border-[#25443c] dark:bg-[#10231f]/80">
          <div class="text-xs font-semibold uppercase tracking-wide text-[#73827b] dark:text-[#8ea49b]">{{ $t('gestlab.general.labels.kanban.view') }}</div>
          <div class="mt-1 font-semibold text-[#15231f] dark:text-[#f7f1e7]">{{ $t('gestlab.general.labels.kanban.page_description') }}</div>
        </div>
      </div>
    </div>

    <div
      class="overflow-hidden rounded-[2rem] border border-[#ded3bf] p-4 shadow-[0_24px_80px_rgb(20_61_55/0.08)] dark:border-[#25443c]"
      :style="{ background: `linear-gradient(180deg, color-mix(in srgb, ${board.bgcolor || '#143d37'} 12%, #fffaf0) 0%, rgba(247, 241, 231, 0.96) 100%)` }"
    >
      <div class="rounded-[1.75rem] border border-white/70 bg-[#fffdf7]/72 p-4 backdrop-blur dark:border-[#25443c]/80 dark:bg-[#07110f]/70">
        <div class="min-h-[calc(100vh-240px)] rounded-[1.5rem] p-3 sm:p-4">
          <div class="flex items-start gap-5 overflow-x-auto pb-4">
            <CardList
              v-for="list in board.lists"
              :key="list.id"
              :list="list"
              class="w-[21rem] shrink-0"
            />

            <div class="w-[21rem] shrink-0">
              <CardListCreateForm :board="board" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <CardListItemModal :card="props.card" />
</template>
