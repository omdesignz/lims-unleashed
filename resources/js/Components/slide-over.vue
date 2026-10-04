<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { X as XMarkIcon } from '@lucide/vue'

const emit = defineEmits(['closed', 'close'])

const props = defineProps({
  disabled: Boolean,
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  // The panel is as wide as its form needs: 'narrow' for a handful of fields.
  size: {
    type: String,
    default: 'wide',
  },
})

const open = ref(true)

const close = () => {
  if (props.disabled) return
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
        enter="ease-out duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-out duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full">
            <TransitionChild
              as="template"
              enter="transition-[translate,opacity] ease-[cubic-bezier(0.32,0.72,0,1)] duration-[220ms]"
              enter-from="translate-x-full opacity-0 motion-reduce:translate-x-0"
              enter-to="translate-x-0"
              leave="transition-[translate,opacity] ease-out duration-200"
              leave-from="translate-x-0"
              leave-to="translate-x-full opacity-0 motion-reduce:translate-x-0"
            >
              <DialogPanel class="pointer-events-auto w-screen" :class="size === 'narrow' ? 'max-w-xl' : 'max-w-4xl'">
                <div class="ds-slideover-panel flex h-full flex-col overflow-hidden border-y-0 border-r-0">
                  <!-- Header -->
                  <div class="ds-slideover-header relative flex-shrink-0 border-b px-6 py-5">
                    <div class="flex items-start justify-between gap-x-3">
                      <div class="min-w-0 space-y-1">
                        <DialogTitle class="pl-d3 truncate">
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
                          :disabled="props.disabled"
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
