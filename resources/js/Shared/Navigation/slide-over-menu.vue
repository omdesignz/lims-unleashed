<template>
  <TransitionRoot as="template" :show="props.open">
    <Dialog as="div" class="relative z-50" @close="emit('menu-closed')">
      <TransitionChild as="template" enter="ease-out duration-200" enter-from="opacity-0" enter-to="opacity-100" leave="ease-out duration-150" leave-from="opacity-100" leave-to="opacity-0">
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
          <div class="pointer-events-none fixed inset-y-0 left-0 flex max-w-full pr-4 sm:pr-10">
            <TransitionChild as="template" enter="transform transition ease-out duration-200" enter-from="-translate-x-full" enter-to="-translate-x-0" leave="transform transition ease-out duration-200" leave-from="-translate-x-0" leave-to="-translate-x-full">
              <DialogPanel class="pointer-events-auto relative w-screen max-w-md p-3 sm:p-4">
                <div class="ds-sidebar-panel flex h-full flex-col overflow-hidden rounded-xl border shadow-2xl">
                  <div class="border-b border-white/10 px-5 py-5">
                    <div class="flex items-start justify-between gap-4">
                      <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-200">Navegação</p>
                        <DialogTitle class="mt-1 text-lg font-bold text-white">Menu da aplicação</DialogTitle>
                      </div>
                      <button type="button" class="ds-icon-button text-slate-300 hover:bg-white/10 hover:text-white" @click="emit('menu-closed')">
                        <span class="sr-only">Fechar painel</span>
                        <ChevronDoubleLeftIcon class="h-5 w-5" aria-hidden="true" />
                      </button>
                    </div>
                  </div>

                  <div class="flex-1 overflow-y-auto px-4 py-5">
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
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ChevronsLeft as ChevronDoubleLeftIcon } from '@lucide/vue'
import MenuItems from "./menu-items.vue";

const props = defineProps({
  open: Boolean,
})

const emit = defineEmits(['menu-closed'])
</script>
