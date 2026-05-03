<script setup>
import {PlusIcon} from '@heroicons/vue/24/outline';
import {nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import { trans } from 'laravel-vue-i18n';


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
    v-if="isShowingForm"
    ref="formRef"
    @keydown.esc="isShowingForm = false"
    @submit.prevent="onSubmit()"
    class="space-y-3 rounded-[1.5rem] border border-dashed border-slate-300 bg-white/80 p-4 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/80"
  >
    <input
      ref="inputNameRef"
      v-model="form.name"
      class="block w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-700/20 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
      :placeholder="$t('gestlab.general.labels.kanban.list_name_placeholder')"
      type="text"
    />
    <div class="flex items-center gap-2">
      <button
        type="submit"
        class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-900 to-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:from-sky-800 hover:to-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-800 focus:ring-offset-2"
      >
        {{ $t('gestlab.general.labels.kanban.add_list') }}
      </button>
      <button
        type="button"
        @click="isShowingForm = false"
        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
      >
        {{ $t('gestlab.general.buttons.cancel') }}
      </button>
    </div>
  </form>

  <button
    v-else
    @click="showForm()"
    class="flex w-full items-center gap-2 rounded-[1.5rem] border border-dashed border-white/30 bg-white/10 px-4 py-4 text-sm font-medium text-slate-700 transition-colors duration-200 hover:bg-white/60 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200 dark:hover:bg-slate-800"
  >
    <PlusIcon class="h-5 w-5" />
    <span>{{ $t('gestlab.general.labels.kanban.add_list') }}</span>
  </button>
</template>
