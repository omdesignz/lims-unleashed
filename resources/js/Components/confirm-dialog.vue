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
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-out duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-[0.97]"
          >
            <DialogPanel
              class="ds-modal-panel relative w-full transform overflow-hidden transition-[opacity,transform] sm:my-8"
              :class="size"
            >
              <!-- Content -->
              <div class="px-6 pb-5 pt-6">
                <div class="sm:flex sm:items-start gap-4">
                  <!-- Icon -->
                  <div
                    class="mx-auto flex h-10 w-10 shrink-0 items-center justify-center rounded-full sm:mx-0"
                    :class="iconContainerClass"
                  >
                    <component :is="iconComponent" class="h-5 w-5" :class="iconColorClass" />
                  </div>

                  <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                    <DialogTitle
                      as="h3"
                      class="ds-heading text-base leading-6"
                    >
                      {{ props.title }}
                    </DialogTitle>
                    <div v-if="props.description" class="mt-2">
                      <p class="ds-copy text-sm">
                        {{ props.description }}
                      </p>
                    </div>
                    <slot />
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div
                v-if="!hideButtons"
                class="flex flex-col-reverse gap-2 border-t border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-6 py-3 sm:flex-row sm:justify-end"
              >
                <button
                  type="button"
                  class="ds-button ds-button-secondary w-full sm:w-auto"
                  :disabled="props.disabled"
                  @click="cancelDialog"
                >
                  {{ props.cancel }}
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
import {
  TriangleAlert as ExclamationTriangleIcon,
  CircleAlert as ExclamationCircleIcon,
  CircleHelp as QuestionMarkCircleIcon,
  ShieldAlert as ShieldExclamationIcon,
  RefreshCw as ArrowPathIcon,
  CircleCheck as CheckCircleIcon,
} from '@lucide/vue'

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
    danger: {
      icon: ExclamationTriangleIcon,
      iconBg: 'bg-red-50 dark:bg-red-500/10',
      iconColor: 'text-red-600 dark:text-red-400',
      buttonBg: 'bg-red-600 hover:bg-red-500 focus-visible:outline-red-600',
    },
    warning: {
      icon: ExclamationCircleIcon,
      iconBg: 'bg-amber-50 dark:bg-amber-500/10',
      iconColor: 'text-amber-600 dark:text-amber-400',
      buttonBg: 'bg-amber-600 hover:bg-amber-500 focus-visible:outline-amber-600',
    },
    question: {
      icon: QuestionMarkCircleIcon,
      iconBg: 'bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)]',
      iconColor: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
      buttonBg: 'ds-button-primary',
    },
    security: {
      icon: ShieldExclamationIcon,
      iconBg: 'bg-orange-50 dark:bg-orange-500/10',
      iconColor: 'text-orange-600 dark:text-orange-400',
      buttonBg: 'bg-orange-600 hover:bg-orange-500 focus-visible:outline-orange-600',
    },
    info: {
      icon: ArrowPathIcon,
      iconBg: 'bg-[rgb(var(--primary-50-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)]',
      iconColor: 'text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]',
      buttonBg: 'ds-button-primary',
    },
    success: {
      icon: CheckCircleIcon,
      iconBg: 'bg-emerald-50 dark:bg-emerald-500/10',
      iconColor: 'text-emerald-600 dark:text-emerald-400',
      buttonBg: 'bg-emerald-600 hover:bg-emerald-500 focus-visible:outline-emerald-600',
    },
  }
  return variants[props.variant] || variants.danger
})

const iconComponent = computed(() => variantConfig.value.icon)
const iconColorClass = computed(() => variantConfig.value.iconColor)
const iconContainerClass = computed(() => variantConfig.value.iconBg)
const confirmButtonClass = computed(() => variantConfig.value.buttonBg)
</script>
