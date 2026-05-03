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
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full p-4 sm:p-6 lg:p-8">
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
                <div class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_30px_120px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/60 dark:border-slate-700/80 dark:bg-gray-900 dark:ring-slate-700/60">
                  <!-- Header -->
                  <div class="relative flex-shrink-0 overflow-hidden bg-gradient-to-r from-primary-900 via-slate-900 to-slate-950 px-6 py-5 dark:from-slate-950 dark:via-slate-900 dark:to-black">
                    <div class="pointer-events-none absolute inset-y-0 right-0 w-40 bg-[radial-gradient(circle_at_center,rgba(255,255,255,0.18),transparent_70%)]"></div>
                    <div class="flex items-start justify-between gap-x-3">
                      <div class="min-w-0 space-y-1">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-200/80">
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
                  <div class="flex-1 overflow-y-auto bg-gradient-to-b from-white to-slate-50/80 dark:from-gray-900 dark:to-slate-950 scrollbar-thin">
                    <slot name="content" />
                  </div>

                  <!-- Action footer -->
                  <div
                    class="flex-shrink-0 border-t border-slate-200 bg-white/90 px-6 py-4 backdrop-blur dark:border-slate-700 dark:bg-slate-950/90"
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
