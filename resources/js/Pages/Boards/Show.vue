<script setup>
import confirmDialog from '@/Components/confirm-dialog.vue'
import BoardNameForm from '@/Pages/Boards/BoardNameForm.vue'
import CardList from '@/Pages/Boards/CardList.vue'
import CardListCreateForm from '@/Pages/Boards/CardListCreateForm.vue'
import CardListItemModal from '@/Pages/Boards/CardListItemModal.vue'
import Layout from '@/Shared/Layouts/Layout.vue'
import { ArrowLeftIcon, RectangleStackIcon, Squares2X2Icon, TrashIcon, ViewColumnsIcon } from '@heroicons/vue/24/outline'
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
  <div class="min-w-0 space-y-4">
    <header class="ds-command-surface overflow-hidden">
      <div class="flex flex-col gap-4 px-4 py-4 sm:px-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span
            class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-black/10 text-white shadow-sm"
            :style="{ backgroundColor: board.bgcolor, color: board.iconcolor }"
          >
            <Squares2X2Icon class="h-5 w-5" />
          </span>
          <div class="min-w-0">
            <Link :href="route('boards')" class="mb-1 inline-flex items-center gap-1 text-xs font-bold text-[var(--ds-text-muted)] hover:text-[rgb(var(--primary-700-rgb))]">
              <ArrowLeftIcon class="h-3.5 w-3.5" />
              {{ $t('gestlab.general.labels.kanban.page_title') }}
            </Link>
            <BoardNameForm :board="board" />
            <p class="mt-1 max-w-3xl text-sm font-medium leading-5 text-[var(--ds-text-muted)]">
              {{ board.description || $t('gestlab.general.labels.kanban.board_description') }}
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-2">
          <span class="ds-badge ds-badge-neutral gap-1.5">
            <ViewColumnsIcon class="h-3.5 w-3.5" />
            {{ board.lists.length }} {{ $t('gestlab.general.labels.kanban.lists') }}
          </span>
          <span class="ds-badge ds-badge-neutral gap-1.5">
            <RectangleStackIcon class="h-3.5 w-3.5" />
            {{ cardCount }} {{ $t('gestlab.general.labels.kanban.cards_count') }}
          </span>
          <button type="button" class="ds-icon-button hover:!text-red-600" :title="$t('gestlab.general.buttons.delete')" @click="isDeleteDialogOpen = true">
            <TrashIcon class="h-4 w-4" />
          </button>
        </div>
      </div>
    </header>

    <section class="overflow-hidden border-y border-[var(--ds-border)] bg-[var(--ds-canvas)] sm:rounded-lg sm:border">
      <header class="flex items-center justify-between gap-4 border-b border-[var(--ds-border)] bg-[var(--ds-panel)] px-4 py-3 sm:px-5">
        <div class="flex items-center gap-2">
          <ViewColumnsIcon class="h-4 w-4 text-[rgb(var(--primary-700-rgb))]" />
          <h2 class="text-sm font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.kanban.view') }}</h2>
        </div>
        <span class="text-xs font-semibold text-[var(--ds-text-soft)]">{{ board.name }}</span>
      </header>

      <div class="min-h-[calc(100vh-15rem)] overflow-x-auto overscroll-x-contain p-3 [scrollbar-color:var(--ds-border-strong)_transparent] [scrollbar-width:thin] sm:p-4">
        <div class="flex min-w-max items-start gap-3 sm:gap-4">
          <CardList
            v-for="list in board.lists"
            :key="list.id"
            :list="list"
            :accent-color="board.bgcolor"
            class="h-[calc(100vh-19rem)] min-h-[32rem] w-[20.5rem] shrink-0"
          />
          <div class="w-[20.5rem] shrink-0">
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
