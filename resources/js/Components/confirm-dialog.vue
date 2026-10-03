<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50" @close="cancelDialog">
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

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:p-0" :class="alignment">
          <TransitionChild
            as="template"
            enter="ease-out duration-200"
            enter-from="opacity-0 translate-y-1"
            enter-to="opacity-100 translate-y-0"
            leave="ease-out duration-100"
            leave-from="opacity-100 translate-y-0"
            leave-to="opacity-0 translate-y-1"
          >
            <DialogPanel
              class="ds-modal-panel relative w-full transform overflow-hidden transition-[opacity,transform] sm:my-8"
              :class="size"
            >
              <!-- Plano: a colour strip names the kind of decision; red only for the irreversible. -->
              <div class="pl-strip" :class="{ 'pl-strip-bad': isIrreversible }">{{ stripLabel }}</div>
              <div class="grid gap-3 px-[22px] pb-5 pt-[22px] text-left">
                <DialogTitle as="h3" class="pl-d3">
                  {{ props.title }}
                </DialogTitle>
                <p v-if="props.description" class="text-sm leading-6 text-[var(--pl-muted)]">
                  {{ props.description }}
                </p>
                <slot />
              </div>

              <!-- Footer -->
              <div
                v-if="!hideButtons"
                class="flex flex-col-reverse gap-2 border-t border-[var(--pl-line)] px-[22px] py-3.5 sm:flex-row sm:justify-end"
              >
                <button
                  type="button"
                  class="ds-button ds-button-ghost w-full sm:w-auto"
                  :disabled="props.disabled"
                  @click="cancelDialog"
                >
                  {{ props.cancel }}
                  <kbd class="pl-kbd hidden sm:inline-flex">Esc</kbd>
                </button>
                <button
                  type="button"
                  class="ds-button w-full sm:w-auto"
                  :class="confirmButtonClass"
                  :disabled="props.disabled"
                  @click="confirmDialog"
                >
                  {{ props.confirm }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  disabled: Boolean,
  keepOpenOnConfirm: Boolean,
  hideButtons: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  cancel: {
    type: String,
    default: 'Cancelar',
  },
  confirm: {
    type: String,
    default: 'Confirmar',
  },
  size: {
    type: String,
    default: 'sm:max-w-lg',
  },
  alignment: {
    type: String,
    default: 'sm:items-center',
  },
  variant: {
    type: String,
    default: 'danger',
  },
})

const emit = defineEmits(['confirmed', 'canceled'])

const open = ref(true)

function cancelDialog() {
  if (props.disabled) return
  open.value = false
  emit('canceled')
}

function confirmDialog() {
  if (props.disabled) return
  if (!props.keepOpenOnConfirm) open.value = false
  emit('confirmed', true)
}

const variantConfig = computed(() => {
  const variants = {
    danger: { strip: 'Acção irreversível', irreversible: true, button: 'ds-button-danger' },
    security: { strip: 'Segurança', irreversible: true, button: 'ds-button-danger' },
    warning: { strip: 'Atenção', irreversible: false, button: 'ds-button-primary' },
    question: { strip: 'Confirmação', irreversible: false, button: 'ds-button-primary' },
    info: { strip: 'Confirmação', irreversible: false, button: 'ds-button-primary' },
    success: { strip: 'Confirmação', irreversible: false, button: 'ds-button-primary' },
  }
  return variants[props.variant] || variants.danger
})

const stripLabel = computed(() => variantConfig.value.strip)
const isIrreversible = computed(() => variantConfig.value.irreversible)
const confirmButtonClass = computed(() => variantConfig.value.button)
</script>
