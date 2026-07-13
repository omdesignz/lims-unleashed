<script setup>
import combobox from '@/Components/combobox.vue'
import confirmDialog from '@/Components/confirm-dialog.vue'
import { PlusIcon, TrashIcon, UserCircleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps({ card: Object })
const isOpen = computed(() => Boolean(props.card))
const isDeleteDialogOpen = ref(false)

function mapMembers(card) {
  return (card?.members ?? []).map((member) => ({
    user_id: {
      value: member.user_id,
      label: member.user.name,
    },
    is_responsible: Boolean(member.is_responsible),
    email: member.user.email,
  }))
}

const form = useForm({
  title: props.card?.title ?? '',
  description: props.card?.description ?? '',
  members: mapMembers(props.card),
  redirectUrl: props.card ? `/boards/${props.card.board_id}` : '',
})

watch(() => props.card, (card) => {
  if (!card) {
    return
  }

  form.title = card.title
  form.description = card.description ?? ''
  form.members = mapMembers(card)
  form.redirectUrl = `/boards/${card.board_id}`
  form.clearErrors()
})

function closeModal() {
  if (!props.card) {
    return
  }

  router.get(route('boards.show', { board: props.card.board_id }), {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

function submit() {
  if (!props.card) {
    return
  }

  form.put(route('cards.update', { card: props.card.id }), {
    preserveScroll: true,
  })
}

function deleteCard() {
  if (!props.card) {
    return
  }

  router.delete(route('cards.destroy', { card: props.card.id }), {
    preserveScroll: true,
    onFinish: () => {
      isDeleteDialogOpen.value = false
    },
  })
}

function addMember() {
  form.members.push({
    user_id: null,
    is_responsible: false,
    email: '',
  })
}

function removeMember(index) {
  form.members.splice(index, 1)
}

async function loadUsers(query, setOptions) {
  try {
    const response = await fetch(`/users/getUser?q=${encodeURIComponent(query)}`, {
      headers: { Accept: 'application/json' },
    })

    if (!response.ok) {
      setOptions([])
      return
    }

    const results = await response.json()
    setOptions(results.map((result) => ({
      value: result.id,
      label: result.name,
    })))
  } catch {
    setOptions([])
  }
}
</script>

<template>
  <TransitionRoot :show="isOpen" appear as="template">
    <Dialog as="div" class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
          <TransitionChild
            as="template"
            enter="ease-out duration-200"
            enter-from="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-150"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-3 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="ds-modal-panel relative w-full max-w-3xl overflow-hidden transition-all">
              <header class="flex items-start justify-between gap-4 border-b border-[var(--ds-border)] px-5 py-4">
                <div>
                  <p class="ds-kicker">{{ $t('gestlab.general.labels.kanban.page_title') }}</p>
                  <DialogTitle class="ds-heading mt-1 text-lg">{{ form.title || $t('gestlab.general.labels.kanban.cards.title') }}</DialogTitle>
                </div>
                <button type="button" class="ds-icon-button shrink-0" :title="$t('gestlab.general.buttons.cancel')" @click="closeModal">
                  <XMarkIcon class="h-5 w-5" />
                </button>
              </header>

              <form class="max-h-[calc(100vh-9rem)] overflow-y-auto" @submit.prevent="submit">
                <div class="space-y-6 p-5">
                  <section class="grid gap-4 sm:grid-cols-2">
                    <div class="ds-field-group sm:col-span-2">
                      <label for="card-title" class="ds-field-label">{{ $t('gestlab.general.labels.kanban.cards.title') }} <span class="ds-field-required">*</span></label>
                      <textarea id="card-title" v-model="form.title" rows="2" class="ds-field resize-y" :aria-invalid="Boolean(form.errors.title)" />
                      <p v-if="form.errors.title" class="ds-field-error">{{ form.errors.title }}</p>
                    </div>
                    <div class="ds-field-group sm:col-span-2">
                      <label for="card-description" class="ds-field-label">{{ $t('gestlab.general.labels.kanban.cards.description') }}</label>
                      <textarea id="card-description" v-model="form.description" rows="4" class="ds-field resize-y" />
                    </div>
                  </section>

                  <section class="ds-panel overflow-hidden">
                    <header class="flex flex-col gap-3 border-b border-[var(--ds-border)] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                      <div>
                        <div class="flex items-center gap-2">
                          <h3 class="text-sm font-bold text-[var(--ds-text)]">{{ $t('gestlab.general.labels.kanban.cards.members') }}</h3>
                          <span class="ds-badge bg-[var(--ds-panel-subtle)] text-[var(--ds-text-muted)] ring-1 ring-inset ring-[var(--ds-border)]">{{ form.members.length }}</span>
                        </div>
                      </div>
                      <button type="button" class="ds-button ds-button-secondary" @click="addMember">
                        <PlusIcon class="h-4 w-4" />
                        {{ $t('gestlab.general.buttons.add') }}
                      </button>
                    </header>

                    <div v-if="form.members.length" class="divide-y divide-[var(--ds-border)]">
                      <article v-for="(member, index) in form.members" :key="index" class="grid gap-3 px-4 py-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                        <div v-if="member.user_id" class="flex min-w-0 items-center gap-3">
                          <UserCircleIcon class="h-8 w-8 shrink-0 text-[var(--ds-text-soft)]" />
                          <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-[var(--ds-text)]">{{ member.user_id.label }}</p>
                            <p v-if="member.email" class="truncate text-xs font-semibold text-[var(--ds-text-muted)]">{{ member.email }}</p>
                          </div>
                        </div>
                        <combobox
                          v-else
                          v-model="member.user_id"
                          :has-error="false"
                          :load-options="loadUsers"
                          :placeholder="$t('gestlab.general.labels.kanban.cards.user_id')"
                        />

                        <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-[var(--ds-text-muted)]">
                          <CheckboxInput v-model="member.is_responsible" type="checkbox" class="ds-checkbox" />
                          {{ $t('gestlab.general.labels.kanban.cards.responsible') }}
                        </label>

                        <button type="button" class="ds-icon-button hover:!text-red-600" :title="$t('gestlab.general.buttons.delete')" @click="removeMember(index)">
                          <TrashIcon class="h-4 w-4" />
                        </button>
                      </article>
                    </div>
                    <div v-else class="px-5 py-10 text-center">
                      <UserCircleIcon class="mx-auto h-8 w-8 text-[var(--ds-text-soft)]" />
                      <p class="mt-2 text-sm font-semibold text-[var(--ds-text-muted)]">{{ $t('gestlab.general.buttons.no_items') }}</p>
                    </div>
                  </section>
                </div>

                <footer class="flex flex-col-reverse gap-3 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                  <button type="button" class="ds-button ds-button-danger" @click="isDeleteDialogOpen = true">
                    <TrashIcon class="h-4 w-4" />
                    {{ $t('gestlab.general.buttons.delete') }}
                  </button>
                  <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <button type="button" class="ds-button ds-button-secondary" @click="closeModal">{{ $t('gestlab.general.buttons.cancel') }}</button>
                    <button type="submit" class="ds-button ds-button-primary" :disabled="form.processing">{{ $t('gestlab.general.buttons.update') }}</button>
                  </div>
                </footer>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>

  <confirm-dialog
    v-if="isDeleteDialogOpen"
    :title="$t('gestlab.actions.confirmation_dialog_title.delete')"
    :description="$t('gestlab.actions.confirmation_dialog_description.delete')"
    :confirm="$t('gestlab.general.buttons.yes')"
    :cancel="$t('gestlab.general.buttons.no')"
    @canceled="isDeleteDialogOpen = false"
    @confirmed="deleteCard"
  />
</template>
