<template>
    <TransitionRoot as="template" :show="open">
        <Dialog as="div" class="relative z-50" @close="open = false, emit('menu-closed')">
            <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-4 sm:pr-10">
                        <TransitionChild as="template" enter="transform transition ease-in-out duration-500 sm:duration-700" enter-from="-translate-x-full" enter-to="-translate-x-0" leave="transform transition ease-in-out duration-500 sm:duration-700" leave-from="-translate-x-0" leave-to="-translate-x-full">
                            <DialogPanel class="pointer-events-auto relative w-screen max-w-md p-3 sm:p-4">
                                <TransitionChild as="template" enter="ease-in-out duration-500" enter-from="opacity-0" enter-to="opacity-100" leave="ease-in-out duration-500" leave-from="opacity-100" leave-to="opacity-0">
                                    <div class="absolute right-6 top-6 z-10 flex">
                                        <button type="button" class="rounded-2xl bg-white/10 p-2 text-white/80 backdrop-blur transition hover:bg-white/20 hover:text-white focus:outline-none focus:ring-2 focus:ring-white dark:bg-slate-950/50" @click="open = false, emit('menu-closed')">
                                            <span class="sr-only">Fechar painel</span>
                                            <ChevronDoubleLeftIcon class="h-6 w-6" aria-hidden="true" />
                                        </button>
                                    </div>
                                </TransitionChild>
                                <div class="flex h-full flex-col overflow-hidden rounded-[2rem] border border-[#ded3bf]/80 bg-[#fffaf0] shadow-2xl ring-1 ring-[#143d37]/5 dark:border-[#25443c] dark:bg-[#07110f] dark:ring-white/10">
                                    <div class="relative overflow-hidden border-b border-[#ded3bf] bg-gradient-to-br from-primary-800 via-primary-900 to-[#07110f] px-6 py-6 dark:border-[#25443c]">
                                        <div class="pointer-events-none absolute inset-y-0 right-0 w-40 bg-[radial-gradient(circle_at_center,rgba(217,176,95,0.25),transparent_70%)]"></div>
                                        <p class="text-[11px] font-extrabold uppercase tracking-[0.24em] text-accent-200/85">Navegação</p>
                                        <DialogTitle class="mt-1 text-lg font-semibold text-white">Menu da aplicação</DialogTitle>
                                    </div>
                                    <div class="relative flex-1 overflow-y-auto bg-[#f4efe4] px-4 py-5 dark:bg-[#07110f] sm:px-5">
                                        <menu-items />
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
