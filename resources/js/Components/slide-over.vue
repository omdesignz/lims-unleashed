<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const emit = defineEmits(['closed', 'close'])

defineProps({
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
})

const open = ref(true)

const close = () => {
  open.value = false
  emit('close')
  emit('closed')
}
</script>

<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50" @close="close">
      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-[#07110f]/70 backdrop-blur-md transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full p-3 sm:p-6 lg:p-8">
            <TransitionChild
              as="template"
              enter="transform transition ease-out duration-300"
              enter-from="translate-x-full"
              enter-to="translate-x-0"
              leave="transform transition ease-in duration-200"
              leave-from="translate-x-0"
              leave-to="translate-x-full"
            >
              <DialogPanel class="pointer-events-auto w-screen max-w-4xl">
                <div class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-[#ded3bf] bg-[#fffdf7] shadow-[0_30px_120px_rgba(7,17,15,0.34)] ring-1 ring-white/60 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
                  <!-- Header -->
                  <div class="relative flex-shrink-0 overflow-hidden bg-[linear-gradient(135deg,#07110f,#143d37,#1f7a68)] px-6 py-5">
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-40 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.18),transparent_70%)]"></div>
                    <div class="flex items-start justify-between gap-x-3">
                      <div class="min-w-0 space-y-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-[#f1d78b]">
                          Painel contextual
                        </p>
                        <DialogTitle class="truncate text-lg font-semibold leading-6 text-white">
                          {{ title }}
                        </DialogTitle>
                        <p v-if="description" class="text-sm text-white/70">
                          {{ description }}
                        </p>
                      </div>
                      <div class="ml-3 flex h-7 items-center">
                        <button
                          type="button"
                          class="rounded-lg p-1.5 text-white/70 transition-colors duration-150 hover:bg-white/10 hover:text-white"
                          @click="close"
                        >
                          <span class="sr-only">Fechar painel</span>
                          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Content -->
                  <div class="vap-slideover-content flex-1 overflow-y-auto bg-gradient-to-b from-[#fffdf7] to-[#f7f1e7]/80 dark:from-[#07110f] dark:to-[#0c1714] scrollbar-thin">
                    <slot name="content" />
                  </div>

                  <!-- Action footer -->
                  <div
                    class="flex-shrink-0 border-t border-[#ded3bf] bg-[#fffdf7]/90 px-6 py-4 backdrop-blur dark:border-[#25443c] dark:bg-[#07110f]/90"
                  >
                    <slot name="action_buttons" />
                  </div>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<style scoped>
.vap-slideover-content :deep(label),
.vap-slideover-content :deep(dt) {
  color: #15231f;
}

.vap-slideover-content :deep(dd),
.vap-slideover-content :deep(p) {
  color: rgb(71 85 105);
}

.vap-slideover-content :deep(.divide-gray-200) {
  border-color: rgb(226 232 240);
}

.vap-slideover-content :deep(input:not([type='checkbox']):not([type='radio'])),
.vap-slideover-content :deep(textarea),
.vap-slideover-content :deep(select) {
  background-color: rgb(255 255 255 / 0.95);
  border: 0;
  border-radius: 1rem;
  box-shadow: inset 0 0 0 1px rgb(203 213 225), 0 1px 2px 0 rgb(15 23 42 / 0.05);
  color: rgb(15 23 42);
  transition-duration: 150ms;
  transition-property: color, background-color, border-color, box-shadow;
}

.vap-slideover-content :deep(input[type='checkbox']),
.vap-slideover-content :deep(input[type='radio']) {
  background-color: rgb(255 255 255);
  border-color: rgb(203 213 225);
  color: rgb(var(--primary-700-rgb, 20 61 55));
}

.vap-slideover-content :deep(button.bg-white) {
  background-color: rgb(255 255 255);
  border: 1px solid rgb(226 232 240);
  color: rgb(30 41 59);
}

.vap-slideover-content :deep(input:not([type='checkbox']):not([type='radio']):focus),
.vap-slideover-content :deep(textarea:focus),
.vap-slideover-content :deep(select:focus) {
  box-shadow: inset 0 0 0 2px rgb(var(--primary-600-rgb, 20 86 75));
  outline: none;
}

.dark .vap-slideover-content :deep(label),
.dark .vap-slideover-content :deep(dt) {
  color: rgb(241 245 249);
}

.dark .vap-slideover-content :deep(dd),
.dark .vap-slideover-content :deep(p) {
  color: rgb(203 213 225);
}

.dark .vap-slideover-content :deep(.divide-gray-200) {
  border-color: rgb(30 41 59);
}

.dark .vap-slideover-content :deep(input:not([type='checkbox']):not([type='radio'])),
.dark .vap-slideover-content :deep(textarea),
.dark .vap-slideover-content :deep(select) {
  background-color: rgb(2 6 23 / 0.8);
  box-shadow: inset 0 0 0 1px rgb(51 65 85);
  color: rgb(241 245 249);
}

.dark .vap-slideover-content :deep(input[type='checkbox']),
.dark .vap-slideover-content :deep(input[type='radio']) {
  background-color: rgb(2 6 23);
  border-color: rgb(71 85 105);
  color: rgb(var(--primary-400-rgb, 66 142 122));
}

.dark .vap-slideover-content :deep(button.bg-white) {
  background-color: rgb(15 23 42);
  border-color: rgb(51 65 85);
  color: rgb(241 245 249);
}
</style>
