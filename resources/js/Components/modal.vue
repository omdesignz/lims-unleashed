<template>
  <TransitionRoot as="template" :show="show">
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

      <div class="fixed inset-0 z-10 overflow-y-auto" scroll-region>
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-6">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative w-full transform overflow-hidden rounded-[1.75rem] border border-[#ded3bf] bg-[#fffdf7] text-left shadow-[0_30px_120px_rgb(7_17_15/0.28)] ring-1 ring-white/60 transition-all dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10 sm:my-8"
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
  full: 'sm:w-full',
}[props.maxWidth]))
</script>
