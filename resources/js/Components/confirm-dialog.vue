<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-50" @close="open = false; $emit('canceled')">
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
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:p-0" :class="alignment">
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
              class="relative w-full transform overflow-hidden rounded-[2rem] border border-slate-200/80 bg-white shadow-[0_30px_120px_rgba(15,23,42,0.22)] ring-1 ring-slate-200/60 transition-all dark:border-slate-700/80 dark:bg-gray-900 dark:ring-slate-700/60 sm:my-8"
              :class="size"
            >
              <!-- Content -->
              <div class="bg-gradient-to-b from-white to-slate-50/80 px-6 pb-4 pt-6 dark:from-gray-900 dark:to-slate-950">
                <div class="sm:flex sm:items-start gap-4">
                  <!-- Icon -->
                  <div
                    class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/10 shadow-sm sm:mx-0 sm:h-11 sm:w-11"
                    :class="iconContainerClass"
                  >
                    <component :is="iconComponent" class="h-6 w-6" :class="iconColorClass" />
                  </div>

                  <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                      Confirmação
                    </p>
                    <DialogTitle
                      as="h3"
                      class="mt-1 text-lg font-semibold leading-6 text-gray-900 dark:text-gray-100"
                    >
                      {{ props.title }}
                    </DialogTitle>
                    <div v-if="props.description" class="mt-2">
                      <p class="text-sm text-gray-500 dark:text-gray-400">
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
                class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-white/90 px-6 py-4 backdrop-blur dark:border-gray-700 dark:bg-slate-950/90 sm:flex-row sm:justify-end"
              >
                <button
                  type="button"
                  class="inline-flex w-full sm:w-auto justify-center rounded-xl bg-white dark:bg-gray-800 px-4 py-2.5 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                  @click="open = false; $emit('canceled')"
                >
                  {{ props.cancel }}
                </button>
                <button
                  type="button"
                  class="inline-flex w-full sm:w-auto justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-150"
                  :class="confirmButtonClass"
                  @click="open = false; $emit('confirmed', true)"
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
  ExclamationTriangleIcon,
  ExclamationCircleIcon,
  QuestionMarkCircleIcon,
  ShieldExclamationIcon,
  ArrowPathIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
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

defineEmits(['confirmed', 'canceled'])

const open = ref(true)

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
      iconBg: 'bg-blue-50 dark:bg-blue-500/10',
      iconColor: 'text-blue-600 dark:text-blue-400',
      buttonBg: 'bg-blue-600 hover:bg-blue-500 focus-visible:outline-blue-600',
    },
    security: {
      icon: ShieldExclamationIcon,
      iconBg: 'bg-orange-50 dark:bg-orange-500/10',
      iconColor: 'text-orange-600 dark:text-orange-400',
      buttonBg: 'bg-orange-600 hover:bg-orange-500 focus-visible:outline-orange-600',
    },
    info: {
      icon: ArrowPathIcon,
      iconBg: 'bg-sky-50 dark:bg-sky-500/10',
      iconColor: 'text-sky-600 dark:text-sky-400',
      buttonBg: 'bg-primary-600 hover:bg-primary-500 focus-visible:outline-primary-600',
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
