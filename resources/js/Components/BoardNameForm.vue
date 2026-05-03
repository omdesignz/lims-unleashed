<script setup>
import {useForm} from "@inertiajs/vue3";
import {nextTick, ref, watch} from "vue";

const props = defineProps({
  board: Object
});

const form = useForm({
  name: props.board.name
});
const heading = ref();
const input = ref();
const isEditing = ref(false);

function resize() {
  input.value.style.width = `${heading.value.offsetWidth + 2}px`;
}

watch(() => form.name, async () => {
  await nextTick();
  resize();
});

async function edit() {
  isEditing.value = true;
  await nextTick();
  input.value.select();
  resize();
}

function onSubmit() {
  isEditing.value = false;
  form.put(route('boards.update', {board: props.board.id}), {
    onError: () => form.reset()
  });
}
</script>
<template>
  <div class="flex flex-col items-start max-w-full">
    <h1
      ref="heading"
      :class="[isEditing ? 'absolute -left-[1000rem]': '']"
      class="hover:bg-white/20 whitespace-pre-wrap break-all border border-transparent rounded-md cursor-pointer px-3 py-1.5 text-2xl text-white font-bold"
      @click="edit()"
    >{{ form.name ? form.name : ' ' }}</h1>
    <form
      v-show="isEditing"
      @focusout="onSubmit()"
      @submit.prevent="onSubmit()"
      class="w-full"
    >
      <input
        ref="input"
        v-model="form.name"
        class="text-2xl max-w-full font-bold placeholder-gray-400 px-3 py-1.5 rounded-md focus:ring-2 focus:ring-blue-900"
        placeholder="Board name"
        type="text"
      >
    </form>
  </div>
</template>