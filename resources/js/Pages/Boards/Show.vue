<script setup>
import confirmDialog from '@/Components/confirm-dialog.vue'
import BoardNameForm from '@/Pages/Boards/BoardNameForm.vue'
import CardList from '@/Pages/Boards/CardList.vue'
import CardListCreateForm from '@/Pages/Boards/CardListCreateForm.vue'
import CardListItemModal from '@/Pages/Boards/CardListItemModal.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ArrowLeftIcon, RectangleStackIcon, TrashIcon, ViewColumnsIcon } from '@heroicons/vue/24/outline'
import { Link, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  board: Object,
  card: Object,
})

const isDeleteDialogOpen = ref(false)
const cardCount = computed(() => props.board.lists.reduce((total, list) => total + list.cards.length, 0))

function deleteBoard() {
  router.get(route('boards.destroy'), {
    recordIds: [props.board.id],
  })
}
</script>

<template>
  <div class="space-y-5">
    <header class="ds-panel overflow-hidden">
      <div class="h-1.5" :style="{ backgroundColor: board.bgcolor }" />
      <div class="flex flex-col gap-5 px-5 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0">
          <Link :href="route('boards')" class="mb-3 inline-flex items-center gap-1.5 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
            <ArrowLeftIcon class="h-3.5 w-3.5" />
            {{ $t('gestlab.general.labels.kanban.page_title') }}
          </Link>
          <BoardNameForm :board="board" />
          <p class="ds-copy mt-1 max-w-2xl text-sm">{{ board.description || $t('gestlab.general.labels.kanban.board_description') }}</p>
        </div>
        <button type="button" class="ds-button ds-button-danger shrink-0" @click="isDeleteDialogOpen = true">
          <TrashIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.delete') }}
        </button>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="flex items-center gap-3 px-5 py-4">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]">
            <RectangleStackIcon class="h-4 w-4" />
          </span>
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.kanban.board') }}</dt>
            <dd class="mt-0.5 max-w-56 truncate text-sm font-bold text-[var(--ds-text)]">{{ board.name }}</dd>
          </div>
        </div>
        <div class="flex items-center gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-cyan-700 dark:text-cyan-300">
            <ViewColumnsIcon class="h-4 w-4" />
          </span>
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.kanban.lists') }}</dt>
            <dd class="mt-0.5 text-lg font-black tabular-nums text-[var(--ds-text)]">{{ board.lists.length }}</dd>
          </div>
        </div>
        <div class="flex items-center gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-amber-700 dark:text-amber-300">
            <RectangleStackIcon class="h-4 w-4" />
          </span>
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.kanban.cards_count') }}</dt>
            <dd class="mt-0.5 text-lg font-black tabular-nums text-[var(--ds-text)]">{{ cardCount }}</dd>
          </div>
        </div>
      </dl>
    </header>

    <section class="ds-command-surface overflow-hidden">
      <header class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-3.5">
        <div>
          <p class="ds-kicker">{{ $t('gestlab.general.labels.kanban.page_title') }}</p>
          <h2 class="ds-heading mt-1 text-sm">{{ $t('gestlab.general.labels.kanban.view') }}</h2>
        </div>
        <p class="hidden text-xs font-semibold text-[var(--ds-text-muted)] sm:block">{{ board.lists.length }} {{ $t('gestlab.general.labels.kanban.lists') }}</p>
      </header>
      <div class="min-h-[calc(100vh-22rem)] overflow-x-auto bg-[var(--ds-panel-subtle)] p-4">
        <div class="flex min-w-max items-start gap-4">
          <CardList v-for="list in board.lists" :key="list.id" :list="list" class="h-[calc(100vh-25rem)] min-h-[28rem] w-[20rem] shrink-0" />
          <div class="w-[20rem] shrink-0">
            <CardListCreateForm :board="board" />
          </div>
        </div>
      </div>
    </section>
  </div>

  <CardListItemModal :card="props.card" />

  <confirm-dialog
    v-if="isDeleteDialogOpen"
    :title="$t('gestlab.actions.confirmation_dialog_title.delete')"
    :description="$t('gestlab.actions.confirmation_dialog_description.delete')"
    :confirm="$t('gestlab.general.buttons.yes')"
    :cancel="$t('gestlab.general.buttons.no')"
    @canceled="isDeleteDialogOpen = false"
    @close="isDeleteDialogOpen = false"
    @confirmed="deleteBoard"
  />
</template>
