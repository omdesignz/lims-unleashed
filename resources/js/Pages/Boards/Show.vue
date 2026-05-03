<script setup>
import { TrashIcon } from '@heroicons/vue/24/outline';
import BoardNameForm from '@/Pages/Boards/BoardNameForm.vue';
import CardListCreateForm from '@/Pages/Boards/CardListCreateForm.vue';
import CardList from '@/Pages/Boards/CardList.vue';
import CardListItemModal from '@/Pages/Boards/CardListItemModal.vue';
import Layout from '@/Shared/Layouts/Layout.vue';

const props = defineProps({
  board: Object,
  card: Object,
});

defineOptions({
  layout: Layout,
});
</script>

<template>
  <div class="space-y-6">
    <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
              Organize laboratory work visually, keep the team aligned, and move tasks through execution without losing context.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-right backdrop-blur">
              <div class="text-xs uppercase tracking-wide text-white/70">Lists</div>
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

      <div class="grid gap-3 border-t border-slate-100 px-6 py-4 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300 sm:grid-cols-3">
        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
          <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Board</div>
          <div class="mt-1 font-semibold text-slate-900 dark:text-white">{{ board.name }}</div>
        </div>
        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
          <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Cards</div>
          <div class="mt-1 font-semibold text-slate-900 dark:text-white">
            {{ board.lists.reduce((total, list) => total + list.cards.length, 0) }}
          </div>
        </div>
        <div class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-800/80">
          <div class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">View</div>
          <div class="mt-1 font-semibold text-slate-900 dark:text-white">{{ $t('gestlab.general.labels.kanban.page_description') }}</div>
        </div>
      </div>
    </div>

    <div
      class="overflow-hidden rounded-[2rem] border border-slate-200 p-4 shadow-sm dark:border-slate-800"
      :style="{ background: `linear-gradient(180deg, color-mix(in srgb, ${board.bgcolor || '#0f172a'} 15%, white) 0%, rgba(248, 250, 252, 0.96) 100%)` }"
    >
      <div class="rounded-[1.75rem] border border-white/60 bg-white/55 p-4 backdrop-blur dark:border-slate-700/80 dark:bg-slate-950/50">
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
