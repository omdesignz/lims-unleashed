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
      class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
      :placeholder="$t('gestlab.general.labels.kanban.card_title_placeholder')"
    ></textarea>
    <div class="flex items-center gap-2">
      <button
        type="submit"
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-900 to-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:from-sky-800 hover:to-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-800 focus:ring-offset-2"
      >
        {{ $t('gestlab.general.labels.kanban.add_card') }}
      </button>
      <button
        type="button"
        @click="store.listCreatingCardId = null"
        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
      >
        {{ $t('gestlab.general.buttons.cancel') }}
      </button>
    </div>
  </form>

  <button
    v-else
    @click="showForm()"
    class="flex w-full items-center gap-2 rounded-2xl bg-white/80 px-3 py-3 text-sm font-medium text-slate-600 transition-colors duration-200 hover:bg-white hover:text-slate-900 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
  >
    <PlusIcon class="h-5 w-5" />
    <span>{{ $t('gestlab.general.labels.kanban.add_card') }}</span>
  </button>
</template>
