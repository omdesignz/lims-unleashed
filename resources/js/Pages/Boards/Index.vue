<script setup>
import confirmDialog from '@/Components/confirm-dialog.vue'
import IconPicker from '@/Components/icon-picker.vue'
import Pagination from '@/Components/pagination.vue'
import slideOver from '@/Components/slide-over.vue'
import { usePermission } from '@/Composables/usePermissions'
import Layout from '@/Shared/Layouts/Layout.vue'
import {
  Search as MagnifyingGlassIcon,
  Pencil as PencilIcon,
  Plus as PlusIcon,
  Layers as RectangleStackIcon,
  LayoutGrid as Squares2X2Icon,
  Trash2 as TrashIcon,
} from '@lucide/vue'
import * as OutlinedIcons from '@heroicons/vue/24/outline'
import { Link, router, useForm, usePage } from '@inertiajs/vue3'
import debounce from 'lodash/debounce'
import { trans } from 'laravel-vue-i18n'
import { computed, reactive, ref, watch } from 'vue'

defineOptions({ layout: Layout })

const props = defineProps({
  record: Object,
  model: String,
  query: Object,
})

const page = usePage()
const { hasPermission } = usePermission()
const isDrawerOpen = ref(false)
const isIconPickerOpen = ref(false)
const boardPendingDeletion = ref(null)

const form = useForm({
  id: null,
  name: '',
  description: '',
  bgcolor: '#0f766e',
  iconcolor: '#ffffff',
  icon: 'ClipboardDocumentCheckIcon',
})

const query = reactive({
  search: props.query?.search ?? '',
  filter: props.query?.filter ?? null,
  page: null,
})

watch(query, debounce((value) => {
  router.get(page.url, value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}, 300))

const totalLists = computed(() => props.record.data.reduce((total, board) => total + Number(board.lists_count ?? 0), 0))
const drawerTitle = computed(() => form.id
  ? trans('gestlab.slideover.updating.title')
  : trans('gestlab.slideover.creating.title'))
const drawerDescription = computed(() => form.id
  ? `${trans('gestlab.slideover.updating.description')}${form.name}`
  : trans('gestlab.general.labels.kanban.page_description'))

function closeDrawer() {
  isDrawerOpen.value = false
  form.clearErrors()
  form.reset()
  form.defaults({
    id: null,
    name: '',
    description: '',
    bgcolor: '#0f766e',
    iconcolor: '#ffffff',
    icon: 'ClipboardDocumentCheckIcon',
  })
}

function openCreateDrawer() {
  closeDrawer()
  isDrawerOpen.value = true
}

function openEditDrawer(board) {
  form.id = board.id
  form.name = board.name
  form.description = board.description ?? ''
  form.bgcolor = board.bgcolor || '#0f766e'
  form.iconcolor = board.iconcolor || '#ffffff'
  form.icon = board.icon || 'ClipboardDocumentCheckIcon'
  isDrawerOpen.value = true
}

function submit() {
  const options = {
    preserveScroll: true,
    onSuccess: closeDrawer,
  }

  if (form.id) {
    form.put(route('boards.update', { board: form.id }), options)
    return
  }

  form.post(route('boards.store'), options)
}

function deleteBoard() {
  if (!boardPendingDeletion.value) {
    return
  }

  router.get(route('boards.destroy'), {
    recordIds: [boardPendingDeletion.value.id],
  }, {
    preserveScroll: true,
    onFinish: () => {
      boardPendingDeletion.value = null
    },
  })
}
</script>

<template>
  <div class="space-y-5">
    <header class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
          <p class="ds-kicker">{{ $t('gestlab.general.labels.kanban.page_title') }}</p>
          <h1 class="ds-heading mt-1 text-xl">{{ $t('gestlab.general.labels.kanban.page_title') }}</h1>
          <p class="ds-copy mt-1 max-w-2xl text-sm">{{ $t('gestlab.general.labels.kanban.page_description') }}</p>
        </div>
        <button
          v-if="hasPermission('add_' + props.model)"
          type="button"
          class="ds-button ds-button-primary shrink-0"
          @click="openCreateDrawer"
        >
          <PlusIcon class="h-4 w-4" />
          {{ $t('gestlab.general.buttons.new_record') }}
        </button>
      </div>

      <dl class="grid border-t border-[var(--ds-border)] sm:grid-cols-3 sm:divide-x sm:divide-[var(--ds-border)]">
        <div class="flex items-center gap-3 px-5 py-4">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-[rgb(var(--primary-700-rgb))]">
            <Squares2X2Icon class="h-4 w-4" />
          </span>
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.records_found') }}</dt>
            <dd class="mt-0.5 text-lg font-black tabular-nums text-[var(--ds-text)]">{{ props.record.meta.total ?? 0 }}</dd>
          </div>
        </div>
        <div class="flex items-center gap-3 border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0">
          <span class="grid h-9 w-9 place-items-center rounded-lg bg-[var(--ds-panel-subtle)] text-cyan-700 dark:text-cyan-300">
            <RectangleStackIcon class="h-4 w-4" />
          </span>
          <div>
            <dt class="text-xs font-bold uppercase text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.kanban.lists') }}</dt>
            <dd class="mt-0.5 text-lg font-black tabular-nums text-[var(--ds-text)]">{{ totalLists }}</dd>
          </div>
        </div>
        <div class="border-t border-[var(--ds-border)] px-5 py-4 sm:border-t-0">
          <label for="board-search" class="sr-only">{{ $t('gestlab.general.search_input_placeholder') }}</label>
          <div class="relative">
            <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[var(--ds-text-soft)]" />
            <BaseInput
              id="board-search"
              v-model="query.search"
              type="search"
              class="ds-field min-h-10 py-2 pl-9"
              :placeholder="$t('gestlab.general.search_input_placeholder')"
            />
          </div>
        </div>
      </dl>
    </header>

    <section v-if="props.record.data.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
      <article v-for="board in props.record.data" :key="board.id" class="ds-card group overflow-hidden">
        <div class="h-1.5" :style="{ backgroundColor: board.bgcolor }" />
        <div class="p-5">
          <div class="flex items-start gap-3">
            <span
              class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-black/10"
              :style="{ backgroundColor: board.bgcolor, color: board.iconcolor }"
            >
              <component :is="OutlinedIcons[board.icon] || RectangleStackIcon" class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <Link :href="route('boards.show', { board: board.id })" class="block truncate text-sm font-bold text-[var(--ds-text)] hover:text-[rgb(var(--primary-700-rgb))]">
                {{ board.name }}
              </Link>
              <p class="mt-1 text-xs font-semibold text-[var(--ds-text-muted)]">
                {{ board.lists_count }} {{ $t('gestlab.general.labels.kanban.lists') }}
              </p>
            </div>
            <div class="flex items-center">
              <button type="button" class="ds-icon-button" :title="$t('gestlab.general.buttons.edit')" @click="openEditDrawer(board)">
                <PencilIcon class="h-4 w-4" />
              </button>
              <button type="button" class="ds-icon-button hover:!text-red-600" :title="$t('gestlab.general.buttons.delete')" @click="boardPendingDeletion = board">
                <TrashIcon class="h-4 w-4" />
              </button>
            </div>
          </div>
          <p v-if="board.description" class="mt-4 line-clamp-2 min-h-10 text-sm leading-5 text-[var(--ds-text-muted)]">{{ board.description }}</p>
          <p v-else class="mt-4 min-h-10 text-sm italic leading-5 text-[var(--ds-text-soft)]">{{ $t('gestlab.general.labels.kanban.board_description') }}</p>
          <div class="mt-4 flex items-center justify-between border-t border-[var(--ds-border)] pt-3 text-xs font-semibold text-[var(--ds-text-soft)]">
            <span>{{ $t('gestlab.general.labels.created_at') }}</span>
            <span>{{ board.created_at || '-' }}</span>
          </div>
        </div>
      </article>
    </section>

    <section v-else class="ds-panel px-5 py-14 text-center">
      <Squares2X2Icon class="mx-auto h-9 w-9 text-[var(--ds-text-soft)]" />
      <h2 class="ds-heading mt-3 text-sm">{{ $t('gestlab.general.labels.kanban.empty_state.title') }}</h2>
      <p class="ds-copy mt-1 text-sm">{{ $t('gestlab.general.labels.kanban.empty_state.description') }}</p>
      <button v-if="hasPermission('add_' + props.model)" type="button" class="ds-button ds-button-primary mt-5" @click="openCreateDrawer">
        <PlusIcon class="h-4 w-4" />
        {{ $t('gestlab.general.buttons.new_record') }}
      </button>
    </section>

    <Pagination v-if="props.record.data.length && props.record.meta.last_page > 1" :links="props.record.meta.links" />

    <slide-over v-if="isDrawerOpen" :title="drawerTitle" :description="drawerDescription" @close="closeDrawer">
      <template #content>
        <form id="board-form" class="mx-auto w-full max-w-3xl space-y-6 px-6 py-6 sm:px-8" @submit.prevent="submit">
          <div class="ds-field-group">
            <label for="board-name" class="ds-field-label">{{ $t('gestlab.general.labels.kanban.name') }} <span class="ds-field-required">*</span></label>
            <BaseInput id="board-name" v-model="form.name" type="text" class="ds-field" :aria-invalid="Boolean(form.errors.name)" />
            <p v-if="form.errors.name" class="ds-field-error">{{ form.errors.name }}</p>
          </div>

          <div class="ds-field-group">
            <label for="board-description" class="ds-field-label">{{ $t('gestlab.general.labels.kanban.description') }}</label>
            <textarea id="board-description" v-model="form.description" rows="4" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.description)" />
            <p v-if="form.errors.description" class="ds-field-error">{{ form.errors.description }}</p>
          </div>

          <fieldset class="ds-panel overflow-hidden">
            <legend class="sr-only">{{ $t('gestlab.general.labels.kanban.bgcolor') }}</legend>
            <div class="border-b border-[var(--ds-border)] px-4 py-3">
              <p class="text-sm font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.kanban.icon') }}</p>
            </div>
            <div class="grid gap-4 p-4 sm:grid-cols-2">
              <label class="ds-field-group">
                <span class="ds-field-label">{{ $t('gestlab.general.labels.kanban.bgcolor') }}</span>
                <span class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-2">
                  <ColorInput v-model="form.bgcolor" type="color" class="h-8 w-10 cursor-pointer border-0 bg-transparent p-0" />
                  <span class="font-mono text-xs font-bold uppercase text-[var(--ds-text-muted)]">{{ form.bgcolor }}</span>
                </span>
              </label>
              <label class="ds-field-group">
                <span class="ds-field-label">{{ $t('gestlab.general.labels.kanban.iconcolor') }}</span>
                <span class="flex items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-2">
                  <ColorInput v-model="form.iconcolor" type="color" class="h-8 w-10 cursor-pointer border-0 bg-transparent p-0" />
                  <span class="font-mono text-xs font-bold uppercase text-[var(--ds-text-muted)]">{{ form.iconcolor }}</span>
                </span>
              </label>
            </div>
          </fieldset>

          <div class="ds-field-group">
            <span class="ds-field-label">{{ $t('gestlab.general.labels.kanban.icon') }}</span>
            <button type="button" class="flex w-full items-center gap-3 rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] p-3 text-left hover:bg-[var(--ds-panel-subtle)]" @click="isIconPickerOpen = true">
              <span class="grid h-9 w-9 place-items-center rounded-lg border border-black/10" :style="{ backgroundColor: form.bgcolor, color: form.iconcolor }">
                <component :is="OutlinedIcons[form.icon] || RectangleStackIcon" class="h-5 w-5" />
              </span>
              <span class="min-w-0">
                <span class="block text-sm font-bold text-[var(--ds-text)]">{{ form.icon }}</span>
                <span class="block text-xs font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.labels.kanban.select_icon') }}</span>
              </span>
            </button>
            <p v-if="form.errors.icon" class="ds-field-error">{{ form.errors.icon }}</p>
          </div>
        </form>

        <IconPicker
          v-if="isIconPickerOpen"
          v-model="form.icon"
          @picker-closed="isIconPickerOpen = false"
        />
      </template>

      <template #action_buttons>
        <div class="flex justify-end gap-2">
          <button type="button" class="ds-button ds-button-secondary" @click="closeDrawer">{{ $t('gestlab.general.buttons.cancel') }}</button>
          <button type="submit" form="board-form" class="ds-button ds-button-primary" :disabled="form.processing">
            {{ form.processing ? $t('gestlab.general.buttons.processing') : (form.id ? $t('gestlab.general.buttons.update') : $t('gestlab.general.buttons.submit')) }}
          </button>
        </div>
      </template>
    </slide-over>

    <confirm-dialog
      v-if="boardPendingDeletion"
      :title="$t('gestlab.actions.confirmation_dialog_title.delete')"
      :description="$t('gestlab.actions.confirmation_dialog_description.delete')"
      :confirm="$t('gestlab.general.buttons.yes')"
      :cancel="$t('gestlab.general.buttons.no')"
      @canceled="boardPendingDeletion = null"
      @close="boardPendingDeletion = null"
      @confirmed="deleteBoard"
    />
  </div>
</template>
