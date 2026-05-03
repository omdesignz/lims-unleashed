<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-10" @close="open = false, emit('menu-closed')">
            <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-10">
                        <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="-translate-x-full" enter-to="-translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="-translate-x-0" leave-to="-translate-x-full">
                            <DialogPanel class="pointer-events-auto relative w-screen max-w-md">
                                <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                                    <div class="absolute top-0 right-0 -mr-8 flex pt-4 pr-2 sm:-mr-10 sm:pr-4">
                                        <button type="button" class="rounded-md text-gray-300 hover:text-white focus:outline-none focus:ring-2 focus:ring-white" @click="open = false, emit('menu-closed')">
                                            <span class="sr-only">Close panel</span>
                                            <ChevronDoubleLeftIcon class="h-6 w-6" aria-hidden="true" />
                                        </button>
                                    </div>
                                </TransitionChild>
                                <div class="flex h-full flex-col overflow-y-scroll bg-white py-6 shadow-xl rounded-md">
                                    <div class="px-4 sm:px-6">
                                        <DialogTitle class="text-lg font-medium text-gray-900">Panel title</DialogTitle>
                                    </div>
                                    <div class="relative mt-6 flex-1 px-4 sm:px-6">
                                        <!-- Replace with your content -->

                                        <menu-items />
                                        <!-- /End replace -->
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

<script setup>
import { ref } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { XMarkIcon, ChevronDoubleLeftIcon } from '@heroicons/vue/24/outline'
import MenuItems from "./menu-items.vue";

//const open = ref(true)
const props =defineProps({
    open: Boolean,
});

const emit = defineEmits(['menu-closed'])
</script>
