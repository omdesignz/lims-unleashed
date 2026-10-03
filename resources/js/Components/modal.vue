<template>
  <TransitionRoot as="template" :show="show">
    <Dialog as="div" class="relative z-50" @close="close">
      <!-- Backdrop -->
      <TransitionChild
        as="template"
        enter="ease-out duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-out duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="ds-modal-backdrop fixed inset-0 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto" scroll-region>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
          <TransitionChild
            as="template"
            enter="ease-out duration-200"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-out duration-150"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
          >
            <DialogPanel
              class="ds-modal-panel relative w-full transform overflow-hidden text-left transition-[opacity,transform] sm:my-8"
              :class="maxWidthClass"
            >
              <slot v-if="show" />
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  maxWidth: {
    type: String,
    default: '2xl',
  },
  closeable: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['close'])

watch(() => props.show, (value) => {
  document.body.style.overflow = value ? 'hidden' : ''
})

const close = () => {
  if (props.closeable) {
    emit('close')
  }
}

const closeOnEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

onMounted(() => document.addEventListener('keydown', closeOnEscape))

onUnmounted(() => {
  document.removeEventListener('keydown', closeOnEscape)
  document.body.style.overflow = ''
})

const maxWidthClass = computed(() => ({
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  '2xl': 'sm:max-w-2xl sm:w-full',
  '3xl': 'sm:max-w-3xl sm:w-full',
  '4xl': 'sm:max-w-4xl sm:w-full',
  '5xl': 'sm:max-w-5xl sm:w-full',
  '6xl': 'sm:max-w-6xl sm:w-full',
  full: 'sm:w-full',
}[props.maxWidth]))
</script>
