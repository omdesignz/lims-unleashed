<script setup>
import {PencilIcon} from "@heroicons/vue/16/solid";
import {computed, nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import {store} from "@/Stores/store.js";
import { trans } from 'laravel-vue-i18n';


const props = defineProps({
  card: Object
});

const inputTitleRef = ref();
const isShowingForm = computed(() => props.card.id === store.value.editingCardId);
const form = useForm({
  title: props.card.title,
});

async function showForm() {
  store.value.editingCardId = props.card.id;
  await nextTick();
  inputTitleRef.value.focus();
}

function onSubmit() {
  form.put(route('cards.update', {card: props.card.id}), {
    onSuccess: () => store.value.editingCardId = null
  });
}
</script>

<template>
  <li>
    <div class="group relative rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
      <form
        v-if="isShowingForm"
        @keydown.esc="store.editingCardId = null"
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
            {{ $t('gestlab.general.buttons.submit') }}
          </button>
          <button
            type="button"
            @click="store.editingCardId = null"
            class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
          >
            {{ $t('gestlab.general.buttons.cancel') }}
          </button>
        </div>
      </form>

      <template v-else>
        <Link
          :href="route('boards.show', {board: card.board_id, card: card.id})"
          preserve-state
          class="block text-sm font-medium text-slate-900 transition-colors duration-200 hover:text-sky-700 dark:text-slate-100 dark:hover:text-sky-300"
        >
          {{ card.title }}
        </Link>
        <button
          @click="showForm()"
          class="absolute right-2 top-2 hidden h-7 w-7 items-center justify-center rounded-full bg-slate-50 text-slate-400 transition-colors duration-200 hover:bg-sky-50 hover:text-sky-700 group-hover:flex dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-sky-300"
        >
          <PencilIcon class="h-3 w-3" />
        </button>
      </template>
    </div>
  </li>
</template>

<style scoped>
.drag > div {
  transform: rotate(5deg);
}

.ghost {
  background: lightgray;
  border-radius: 6px;
}

.ghost > div {
  visibility: hidden;
}
</style>
