<script setup>
import {PlusIcon} from '@heroicons/vue/24/outline';
import {nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';
import { commercialDocumentThemeClasses } from "@/Composables/useCommercialDocumentTheme";


const props = defineProps({
  board: Object
});
const inputNameRef = ref();
const formRef = ref();
const isShowingForm = ref(false);
const form = useForm({
  name: ''
});

async function showForm() {
  isShowingForm.value = true;
  await nextTick();
  inputNameRef.value.focus();
}

function onSubmit() {
  form.post(route('cardLists.store', {board: props.board.id}), {
    onSuccess: () => {
      form.reset();
      inputNameRef.value.focus();
      formRef.value.scrollIntoView();
    }
  });
}
</script>
<template>
  <form
    :class="commercialDocumentThemeClasses"
    v-if="isShowingForm"
    ref="formRef"
    @keydown.esc="isShowingForm = false"
    @submit.prevent="onSubmit()"
    class="space-y-3 rounded-[1.5rem] border border-dashed border-[#d8cbb8] bg-[#fffdf7]/85 p-4 shadow-[0_18px_55px_rgb(20_61_55/0.08)] backdrop-blur dark:border-[#315149] dark:bg-[#07110f]/85"
  >
    <input
      ref="inputNameRef"
      v-model="form.name"
      class="block w-full rounded-2xl border border-[#d8cbb8] bg-white px-3 py-3 text-sm font-medium text-[#15231f] shadow-sm placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.22)] dark:border-[#315149] dark:bg-[#10231f] dark:text-[#f7f1e7] dark:placeholder:text-[#657970]"
      :placeholder="$t('gestlab.general.labels.kanban.list_name_placeholder')"
      type="text"
    />
    <div class="flex items-center gap-2">
      <button
        type="submit"
        class="inline-flex items-center gap-2 rounded-2xl bg-[rgb(var(--primary-800-rgb))] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-[rgb(var(--primary-700-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.28)] focus:ring-offset-2 dark:bg-[rgb(var(--primary-500-rgb))] dark:text-[#07110f] dark:hover:bg-[rgb(var(--primary-300-rgb))]"
      >
        {{ $t('gestlab.general.labels.kanban.add_list') }}
      </button>
      <button
        type="button"
        @click="isShowingForm = false"
        class="rounded-2xl border border-[#d8cbb8] px-4 py-2.5 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-[#f7f1e7] hover:text-[#15231f] dark:border-[#315149] dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
      >
        {{ $t('gestlab.general.buttons.cancel') }}
      </button>
    </div>
  </form>

  <button
    v-else
    @click="showForm()"
    class="flex w-full items-center gap-2 rounded-[1.5rem] border border-dashed border-[#d8cbb8] bg-[#fffdf7]/65 px-4 py-4 text-sm font-semibold text-[#5f6f68] transition-colors duration-200 hover:bg-[#fffdf7] hover:text-[#15231f] dark:border-[#315149] dark:bg-[#07110f]/70 dark:text-[#a9bbb4] dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]"
  >
    <PlusIcon class="h-5 w-5" />
    <span>{{ $t('gestlab.general.labels.kanban.add_list') }}</span>
  </button>
</template>
