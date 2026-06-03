<script setup>
import {PencilIcon} from "@heroicons/vue/16/solid";
import {computed, nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import {store} from "@/Stores/store.js";
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


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
  <li :class="commercialDocumentThemeClasses">
    <div class="group relative rounded-2xl border border-[#e8ddcd] bg-[#fffdf7] p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-[rgb(var(--primary-300-rgb))] hover:shadow-[0_18px_45px_rgb(20_61_55/0.12)] dark:border-[#25443c] dark:bg-[#07110f]">
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
          class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] shadow-sm placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
          :placeholder="$t('gestlab.general.labels.kanban.card_title_placeholder')"
        ></textarea>
        <div class="flex items-center gap-2">
          <button
            type="submit"
            class="inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]"
          >
            {{ $t('gestlab.general.buttons.submit') }}
          </button>
          <button
            type="button"
            @click="store.editingCardId = null"
            class="rounded-2xl border border-[#d8cbb8] px-4 py-2.5 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:border-[#315149] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
          >
            {{ $t('gestlab.general.buttons.cancel') }}
          </button>
        </div>
      </form>

      <template v-else>
        <Link
          :href="route('boards.show', {board: card.board_id, card: card.id})"
          preserve-state
          class="block text-sm font-semibold text-[#31413b] transition-colors duration-200 hover:text-[rgb(var(--primary-800-rgb))] dark:text-[#d7e2dd] dark:hover:text-[rgb(var(--primary-100-rgb))]"
        >
          {{ card.title }}
        </Link>
        <button
          @click="showForm()"
          class="absolute right-2 top-2 hidden h-7 w-7 items-center justify-center rounded-full bg-[#f7f1e7] text-[#73827b] transition-colors duration-200 hover:bg-[rgb(var(--primary-50-rgb))] hover:text-[rgb(var(--primary-800-rgb))] group-hover:flex dark:bg-[#10231f] dark:text-[#8ea49b] dark:hover:bg-[rgb(var(--primary-500-rgb)/0.12)] dark:hover:text-[rgb(var(--primary-100-rgb))]"
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
  background: #f7f1e7;
  border: 2px dashed #d8cbb8;
  border-radius: 16px;
}

.ghost > div {
  visibility: hidden;
}
</style>
