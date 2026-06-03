<script setup>
import {computed, defineProps, watch} from 'vue'
import {Dialog, DialogPanel, TransitionChild, TransitionRoot,} from '@headlessui/vue';
import {PlusCircleIcon, TrashIcon} from "@heroicons/vue/24/outline";
import {useForm, router} from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const props = defineProps({
  card: Object,
  members: {
    type: Array,
    default: []
  }
});

const isOpen = computed(() => !!props.card);

const form = useForm({
  title: props.card?.title,
  description: props.card?.description,
  members: props.card?.members.map(member => {
    return {
      user_id: {
        value: member.user_id,
        label: member.user.name,
      },
      is_responsible: member.is_responsible,
      email: member.user.email
    }
  }),
  redirectUrl: `/boards/${props.card?.board_id}`
});

watch(() => props.card, (card) => {
  if (card) {
    form.title = card.title;
    form.description = card.description;
    form.members = card.members.map(member => {
      return {
        user_id: {
          value: member.user_id,
          label: member.user.name,
        },
        is_responsible: member.is_responsible,
        email: member.user.email
      }
    });
    form.redirectUrl = `/boards/${card.board_id}`
  }
});

function closeModal() {
  router.get(route('boards.show', {board: props.card.board_id}), {}, {
    preserveState: true
  });
}

function onSubmit() {
  form.put(route('cards.update', {card: props.card.id}));
}

const addMember = () => {
    form.members.push({
        user_id: '',
        is_responsible: false,
    });
}

const removeMember = (index) => {
    form.members.splice(index, 1);
}

function loadUsers(query, setOptions) {
    fetch('/users/getUser?q=' + encodeURIComponent(query), {
        headers: {
            Accept: 'application/json',
        },
    })
    .then(response => {
        if (! response.ok) {
            return []
        }

        return response.json()
    })
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
    })
    .catch(() => {
        setOptions([])
    });
}
</script>

<template>
  <TransitionRoot :show="isOpen" appear as="template">
    <Dialog as="div" class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-[#07110f]/70 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] text-left shadow-[0_30px_110px_rgb(20_61_55/0.26)] ring-1 ring-white/70 transition-all dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:my-8 sm:w-full sm:max-w-3xl" :class="commercialDocumentThemeClasses">
              <!-- MODAL HEADER -->
              <div class="border-b border-[#ded3bf] bg-[linear-gradient(135deg,rgb(var(--primary-800-rgb)),#07110f)] px-6 py-5 dark:border-[#25443c]">
                <div class="flex items-center justify-between">
                  <h2 class="text-lg font-semibold text-white">
                    {{ $t('gestlab.general.labels.kanban.card_details') }}
                  </h2>
                  <button
                    @click="closeModal"
                    type="button"
                    class="rounded-full p-1.5 text-white/80 transition-colors duration-200 hover:bg-white/20 hover:text-white"
                  >
                    <span class="sr-only">{{ $t('gestlab.general.buttons.close') }}</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- MODAL CONTENT -->
              <div class="p-6">
                <form @submit.prevent="onSubmit" class="space-y-6">
                  <!-- TITLE -->
                  <div class="space-y-2">
                    <label class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                      {{ $t('gestlab.general.labels.kanban.cards.title') }}
                    </label>
                    <textarea
                      v-model="form.title"
                      rows="2"
                      class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] shadow-sm placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
                    ></textarea>
                  </div>

                  <!-- DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="block text-sm font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                      {{ $t('gestlab.general.labels.kanban.cards.description') }}
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="4"
                      class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] shadow-sm placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
                    ></textarea>
                  </div>

                  <!-- MEMBERS SECTION -->
                  <div class="border-t border-[#ded3bf] pt-6 dark:border-[#25443c]">
                    <div class="flex items-center justify-between mb-4">
                      <div>
                        <h3 class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
                          {{ $t('gestlab.general.labels.kanban.cards.members') }}
                          <span class="ml-2 rounded-full bg-[#f7f1e7] px-2 py-0.5 text-xs font-semibold text-[#5f6f68] dark:bg-[#10231f] dark:text-[#a9bbb4]">
                            ({{ form.members?.length || 0 }})
                          </span>
                        </h3>
                        <p class="mt-1 text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">
                          {{ $t('gestlab.general.labels.kanban.cards.members_description') }}
                        </p>
                      </div>
                      <button
                        type="button"
                        @click="addMember"
                        class="inline-flex items-center gap-2 rounded-full border border-[rgb(var(--primary-200-rgb)/0.75)] bg-[rgb(var(--primary-50-rgb)/0.75)] px-3 py-1.5 text-xs font-semibold text-[rgb(var(--primary-900-rgb))] transition-colors duration-200 hover:bg-white dark:border-[rgb(var(--primary-300-rgb)/0.2)] dark:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:text-[rgb(var(--primary-100-rgb))]"
                      >
                        <PlusCircleIcon class="h-4 w-4" />
                        {{ $t('gestlab.general.buttons.add_member') }}
                      </button>
                    </div>

                    <!-- MEMBERS LIST -->
                    <div v-if="form.members?.length" class="space-y-3">
                      <div
                        v-for="(member, index) in form.members"
                        :key="index"
                        class="flex items-center justify-between rounded-2xl border border-[#e8ddcd] bg-[#f7f1e7]/75 p-3 dark:border-[#25443c] dark:bg-[#10231f]/75"
                      >
                        <div class="flex items-center gap-3">
                          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[rgb(var(--primary-100-rgb))] font-semibold text-[rgb(var(--primary-900-rgb))] dark:bg-[rgb(var(--primary-500-rgb)/0.18)] dark:text-[rgb(var(--primary-100-rgb))]">
                            {{ member.user_id?.label?.charAt(0) || '?' }}
                          </div>
                          <div>
                            <p class="text-sm font-semibold text-[#15231f] dark:text-[#f7f1e7]">
                              {{ member.user_id?.label || $t('gestlab.general.labels.kanban.cards.select_member') }}
                            </p>
                            <p class="text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">{{ member.email || '' }}</p>
                          </div>
                        </div>
                        <div class="flex items-center gap-3">
                          <!-- RESPONSIBLE TOGGLE -->
                          <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-[#5f6f68] dark:text-[#a9bbb4]">{{ $t('gestlab.general.labels.kanban.cards.responsible') }}</span>
                            <button
                              type="button"
                              @click="member.is_responsible = !member.is_responsible"
                              :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2',
                                member.is_responsible ? 'bg-[rgb(var(--primary-800-rgb))] dark:bg-[rgb(var(--primary-500-rgb))]' : 'bg-[#d8cbb8] dark:bg-[#315149]'
                              ]"
                            >
                              <span
                                :class="[
                                  'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                  member.is_responsible ? 'translate-x-5' : 'translate-x-0'
                                ]"
                              />
                            </button>
                          </div>
                          <!-- REMOVE BUTTON -->
                          <button
                            type="button"
                            @click="removeMember(index)"
                            class="rounded-full p-1 text-[#73827b] transition-colors duration-200 hover:bg-rose-50 hover:text-rose-600 dark:text-[#8ea49b] dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                    <div v-else class="rounded-2xl border border-dashed border-[#d8cbb8] bg-[#f7f1e7]/70 p-8 text-center dark:border-[#315149] dark:bg-[#10231f]/70">
                      <p class="text-sm font-medium text-[#5f6f68] dark:text-[#a9bbb4]">{{ $t('gestlab.general.messages.no_members') }}</p>
                    </div>

                    <!-- USER SELECTION FOR NEW MEMBERS -->
                    <div v-if="form.members?.some(m => !m.user_id)" class="space-y-3">
                      <div
                        v-for="(member, index) in form.members"
                        :key="index"
                        v-if="!member.user_id"
                        class="space-y-2"
                      >
                        <label class="block text-xs font-semibold text-[#31413b] dark:text-[#d7e2dd]">
                          {{ $t('gestlab.general.labels.kanban.cards.select_member') }}
                        </label>
                        <combobox
                          :hasError="false"
                          v-model="member.user_id"
                          :load-options="loadUsers"
                          class="w-full"
                        />
                      </div>
                    </div>
                  </div>

                  <!-- ACTION BUTTONS -->
                  <div class="flex items-center justify-between border-t border-[#ded3bf] pt-6 dark:border-[#25443c]">
                    <div class="flex items-center gap-3">
                      <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]"
                      >
                        {{ $t('gestlab.general.buttons.update') }}
                      </button>
                      <button
                        type="button"
                        @click="closeModal"
                        class="rounded-2xl border border-[#d8cbb8] px-4 py-2.5 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:border-[#315149] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
                      >
                        {{ $t('gestlab.general.buttons.cancel') }}
                      </button>
                    </div>
                    <Link
                      :href="`/cards/${card?.id}`"
                      method="delete"
                      as="button"
                      class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 px-4 py-2.5 text-sm font-semibold text-rose-600 transition-colors duration-200 hover:bg-rose-50 hover:text-rose-700 dark:border-rose-500/25 dark:text-rose-300 dark:hover:bg-rose-500/10"
                      @click="closeModal"
                    >
                      <TrashIcon class="h-4 w-4" />
                      {{ $t('gestlab.general.buttons.delete') }}
                    </Link>
                  </div>
                </form>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>
