<script setup>
import {computed, defineProps, watch} from 'vue'
import {Dialog, DialogPanel, TransitionChild, TransitionRoot,} from '@headlessui/vue';
import {PlusCircleIcon, TrashIcon} from "@heroicons/vue/24/outline";
import {useForm, router} from "@inertiajs/vue3";
import combobox from '@/Components/combobox.vue';
import { trans } from 'laravel-vue-i18n';


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
    fetch('/users/getUser?q=' + query)
    .then(response => response.json())
    .then(results => {
        setOptions(
        results.map(result => {
            return {
            value: result.id,
            label: result.name,
            };
        })
        );
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
        <div class="fixed inset-0 bg-gray-900 bg-opacity-40 transition-opacity" />
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
            <DialogPanel class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
              <!-- MODAL HEADER -->
              <div class="bg-gradient-to-r from-blue-900 to-blue-800 px-6 py-4">
                <div class="flex items-center justify-between">
                  <h2 class="text-lg font-semibold text-white">
                    {{ $t('gestlab.general.labels.kanban.card_details') }}
                  </h2>
                  <button
                    @click="closeModal"
                    type="button"
                    class="rounded-full p-1 text-white/80 hover:text-white hover:bg-white/20 transition-colors duration-200"
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
                    <label class="block text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.kanban.cards.title') }}
                    </label>
                    <textarea
                      v-model="form.title"
                      rows="2"
                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                    ></textarea>
                  </div>

                  <!-- DESCRIPTION -->
                  <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-900">
                      {{ $t('gestlab.general.labels.kanban.cards.description') }}
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="4"
                      class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-900/20"
                    ></textarea>
                  </div>

                  <!-- MEMBERS SECTION -->
                  <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between mb-4">
                      <div>
                        <h3 class="text-sm font-semibold text-gray-900">
                          {{ $t('gestlab.general.labels.kanban.cards.members') }}
                          <span class="ml-2 text-xs font-normal text-gray-500">
                            ({{ form.members?.length || 0 }})
                          </span>
                        </h3>
                        <p class="mt-1 text-xs text-gray-500">
                          {{ $t('gestlab.general.labels.kanban.cards.members_description') }}
                        </p>
                      </div>
                      <button
                        type="button"
                        @click="addMember"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-900 hover:bg-blue-100 transition-colors duration-200"
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
                        class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-3"
                      >
                        <div class="flex items-center gap-3">
                          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-900 font-medium">
                            {{ member.user_id?.label?.charAt(0) || '?' }}
                          </div>
                          <div>
                            <p class="text-sm font-medium text-gray-900">
                              {{ member.user_id?.label || $t('gestlab.general.labels.kanban.cards.select_member') }}
                            </p>
                            <p class="text-xs text-gray-500">{{ member.email || '' }}</p>
                          </div>
                        </div>
                        <div class="flex items-center gap-3">
                          <!-- RESPONSIBLE TOGGLE -->
                          <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-600">{{ $t('gestlab.general.labels.kanban.cards.responsible') }}</span>
                            <button
                              type="button"
                              @click="member.is_responsible = !member.is_responsible"
                              :class="[
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2',
                                member.is_responsible ? 'bg-blue-900' : 'bg-gray-200'
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
                            class="rounded-full p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors duration-200"
                          >
                            <TrashIcon class="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                    <div v-else class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-8 text-center">
                      <p class="text-sm text-gray-500">{{ $t('gestlab.general.messages.no_members') }}</p>
                    </div>

                    <!-- USER SELECTION FOR NEW MEMBERS -->
                    <div v-if="form.members?.some(m => !m.user_id)" class="space-y-3">
                      <div
                        v-for="(member, index) in form.members"
                        :key="index"
                        v-if="!member.user_id"
                        class="space-y-2"
                      >
                        <label class="block text-xs font-medium text-gray-700">
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
                  <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                    <div class="flex items-center gap-3">
                      <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-blue-900 to-blue-800 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:from-blue-800 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-900 focus:ring-offset-2 transition-all duration-200"
                      >
                        {{ $t('gestlab.general.buttons.update') }}
                      </button>
                      <button
                        type="button"
                        @click="closeModal"
                        class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors duration-200"
                      >
                        {{ $t('gestlab.general.buttons.cancel') }}
                      </button>
                    </div>
                    <Link
                      :href="`/cards/${card?.id}`"
                      method="delete"
                      as="button"
                      class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors duration-200"
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
