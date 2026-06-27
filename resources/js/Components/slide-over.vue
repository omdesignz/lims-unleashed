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
        <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
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
                <div class="ds-slideover-panel flex h-full flex-col overflow-hidden">
                  <!-- Header -->
                  <div class="ds-slideover-header relative flex-shrink-0 border-b px-6 py-5">
                    <div class="flex items-start justify-between gap-x-3">
                      <div class="min-w-0 space-y-1">
                        <p class="ds-kicker">
                          Painel contextual
                        </p>
                        <DialogTitle class="ds-heading truncate text-lg leading-6">
                          {{ title }}
                        </DialogTitle>
                        <p v-if="description" class="ds-copy text-sm">
                          {{ description }}
                        </p>
                      </div>
                      <div class="ml-3 flex h-7 items-center">
                        <button
                          type="button"
                          class="ds-icon-button"
                          @click="close"
                        >
                          <span class="sr-only">Fechar painel</span>
                          <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Content -->
                  <div class="vap-slideover-content ds-slideover-content flex-1 overflow-y-auto scrollbar-thin">
                    <slot name="content" />
                  </div>

                  <!-- Action footer -->
                  <div
                    class="ds-slideover-footer flex-shrink-0 border-t px-6 py-4"
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
