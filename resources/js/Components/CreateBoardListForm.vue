<script setup>
import {PlusIcon} from '@heroicons/vue/24/outline';
import {nextTick, ref} from "vue";
import {useForm} from "@inertiajs/vue3";

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
  form.post(route('boardLists.store', {board: props.board.id}), {
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
    ref="formRef"
    v-if="isShowingForm"
    @submit.prevent="onSubmit()"
    class="p-3 bg-gray-200 rounded-md"
  >
    <input
      v-model="form.name"
      ref="inputNameRef"
      class="block w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-blue-400 focus:ring-blue-400"
      placeholder="Enter list name..."
      type="text"
    >

    <div class="mt-2 space-x-2">
      <button
        type="submit"
        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-500 rounded-md shadow-sm focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 focus:outline-none">Add list</button>
      <button
        type="button"
        @click="isShowingForm = false"
        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-black rounded-md focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 focus:outline-none">Cancel</button>
    </div>
  </form>

  <button
    v-if="!isShowingForm"
    @click="showForm()"
    class="flex items-center bg-white/10 w-full hover:bg-white/20 text-white p-2 text-sm font-medium rounded-md">
    <PlusIcon class="w-5 h-5"/>
    <span class="ml-1">Add another list</span>
  </button>
</template>