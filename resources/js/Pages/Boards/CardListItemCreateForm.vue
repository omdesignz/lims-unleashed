<script setup>
import {PlusIcon} from '@heroicons/vue/24/outline';
import {computed, nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import {store} from "@/Stores/store.js";
import { trans } from 'laravel-vue-i18n';


const props = defineProps({
  list: Object
});
const emit = defineEmits(['created']);

const inputTitleRef = ref();
const isShowingForm = computed(() => props.list.id === store.value.listCreatingCardId);
const form = useForm({
  title: '',
  card_list_id: props.list.id,
  board_id: props.list.board_id
});

async function showForm() {
  store.value.listCreatingCardId = props.list.id;
  await nextTick();
  inputTitleRef.value.focus();
}

function onSubmit() {
  form.post(route('cards.store'), {
    onSuccess: () => {
      form.reset();
      inputTitleRef.value.focus();
      emit('created');
    }
  });
}
</script>
<template>
  <form
    v-if="isShowingForm"
    @keydown.esc="store.listCreatingCardId = null"
    @submit.prevent="onSubmit()"
    class="space-y-3"
  >
    <textarea
      ref="inputTitleRef"
      v-model="form.title"
      rows="3"
      @keydown.enter.prevent="onSubmit()"
      class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] shadow-sm placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
      :placeholder="$t('gestlab.general.labels.kanban.card_title_placeholder')"
    ></textarea>
    <div class="flex items-center gap-2">
      <button
        type="submit"
        class="inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]"
      >
        {{ $t('gestlab.general.labels.kanban.add_card') }}
      </button>
      <button
        type="button"
        @click="store.listCreatingCardId = null"
        class="rounded-2xl border border-[#d8cbb8] px-4 py-2.5 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:border-[#315149] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
      >
        {{ $t('gestlab.general.buttons.cancel') }}
      </button>
    </div>
  </form>

  <button
    v-else
    @click="showForm()"
    class="flex w-full items-center gap-2 rounded-2xl border border-[#e8ddcd] bg-[#fffdf7]/85 px-3 py-3 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-white hover:text-[#15231f] dark:border-[#25443c] dark:bg-[#07110f] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
  >
    <PlusIcon class="h-5 w-5" />
    <span>{{ $t('gestlab.general.labels.kanban.add_card') }}</span>
  </button>
</template>
