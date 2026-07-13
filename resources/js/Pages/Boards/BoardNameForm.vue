<script setup>
import { CheckIcon, PencilIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import { useForm } from '@inertiajs/vue3'
import { nextTick, ref } from 'vue'

const props = defineProps({ board: Object })

const isEditing = ref(false)
const input = ref(null)
const form = useForm({
  name: props.board.name,
  icon: props.board.icon,
  description: props.board.description,
  bgcolor: props.board.bgcolor,
  iconcolor: props.board.iconcolor,
})

async function edit() {
  isEditing.value = true
  await nextTick()
  input.value?.select()
}

function cancel() {
  form.name = props.board.name
  form.clearErrors()
  isEditing.value = false
}

function submit() {
  form.put(route('boards.update', { board: props.board.id }), {
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
    },
  })
}
</script>

<template>
  <div class="min-w-0">
    <div v-if="!isEditing" class="group flex min-w-0 items-center gap-2">
      <h1 class="ds-heading truncate text-xl">{{ form.name || $t('gestlab.general.labels.kanban.board_id') }}</h1>
      <button type="button" class="ds-icon-button h-8 w-8 shrink-0" :title="$t('gestlab.general.buttons.edit')" @click="edit">
        <PencilIcon class="h-4 w-4" />
      </button>
    </div>

    <form v-else class="flex max-w-xl items-start gap-2" @submit.prevent="submit">
      <div class="min-w-0 flex-1">
        <BaseInput ref="input" v-model="form.name" type="text" class="ds-field min-h-10 py-2 text-base font-bold" :aria-invalid="Boolean(form.errors.name)" />
        <p v-if="form.errors.name" class="ds-field-error mt-1">{{ form.errors.name }}</p>
      </div>
      <button type="submit" class="ds-icon-button border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.update')" :disabled="form.processing">
        <CheckIcon class="h-4 w-4" />
      </button>
      <button type="button" class="ds-icon-button border border-[var(--ds-border)]" :title="$t('gestlab.general.buttons.cancel')" @click="cancel">
        <XMarkIcon class="h-4 w-4" />
      </button>
    </form>
  </div>
</template>
