<template>
    <TransitionRoot as="template" :show="open">
      <Dialog as="div" class="relative z-50" @close="open = false, $emit('canceled')">
        <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
        </TransitionChild>
  
        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:p-0" :class="alignment">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to="opacity-100 translate-y-0 sm:scale-100" leave="ease-in duration-200" leave-from="opacity-100 translate-y-0 sm:scale-100" leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
              <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full" :class="size">
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                  <div class="">
                    <!-- <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                      <DocumentPlusIcon class="h-6 w-6 text-white" aria-hidden="true" v-if="props.type === 'file'" />
                      <FolderPlusIcon class="h-6 w-6 text-white" aria-hidden="true" v-else />
                    </div> -->
                    <div class="mt-3 text-center sm:ml-0 sm:mt-0 sm:text-left">
                      <DialogTitle as="h3" class="text-base font-semibold leading-6 text-gray-900">{{ props.title }}</DialogTitle>
                      <div class="mt-2 border-b border-gray-200">
                        <p class="text-sm text-gray-500 mb-2">{{ props.description }}</p>
                      </div>
                      <slot></slot>
                    </div>
                  </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                  <button type="button" class="inline-flex w-full justify-center rounded-md bg-blue-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-800 sm:ml-3 sm:w-auto" @click="open = false, $emit('confirmed', true)">{{ props.confirm }}</button>
                  <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" @click="open = false, $emit('canceled')" ref="cancelButtonRef">{{ props.cancel }}</button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
  import { ExclamationTriangleIcon, FolderPlusIcon, DocumentPlusIcon } from '@heroicons/vue/24/outline'

  const props = defineProps({
    title: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    cancel: {
        type: String,
        default: 'Não' 
    },
    confirm: {
        type: String,
        default: 'Sim'
    },
    size: {
      type: String,
      default: 'sm:max-w-lg'
    },
    alignment: {
      type: String,
      default: 'sm:items-center'
    },
    type: {
      type: String,
      default: 'file'
    }
  });

  const emit = defineEmits(['confirmed', 'canceled']);
  
  const open = ref(true)

  </script>