<script setup>
import {useForm} from "@inertiajs/vue3";
import {nextTick, ref, watch} from "vue";
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
  board: Object
});

const form = useForm({
  name: props?.board?.name,
  icon: props?.board?.icon,
  description: props?.board?.description,
  bgcolor: props?.board?.bgcolor,
  iconcolor: props?.board?.iconcolor,
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
      :class="[isEditing ? 'absolute -left-[1000rem]' : '']"
      class="cursor-pointer whitespace-pre-wrap break-all rounded-2xl border border-transparent px-4 py-2 text-2xl font-bold text-white transition-colors duration-200 hover:bg-white/10"
      @click="edit()"
    >
      {{ form.name ? form.name : $t('gestlab.general.labels.kanban.board_id') }}
    </h1>
    <form
      v-show="isEditing"
      @focusout="onSubmit()"
      @submit.prevent="onSubmit()"
      class="w-full"
    >
      <input
        ref="input"
        v-model="form.name"
        class="w-full rounded-2xl border border-white/20 bg-white/95 px-4 py-2 text-2xl font-bold text-[#15231f] placeholder:text-[#8d9b94] focus:border-[rgb(var(--primary-500-rgb))] focus:outline-none focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb)/0.24)]"
        :placeholder="$t('gestlab.general.labels.kanban.board_id')"
        type="text"
      />
    </form>
  </div>
</template>
