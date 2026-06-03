<template>
    <Menu as="div" class="relative ml-4 inline-block text-left">
      <div>
        <MenuButton class="flex items-center rounded-full p-2 text-slate-500 transition hover:bg-[#ede5d6] hover:text-primary-700 focus:outline-none dark:text-slate-300 dark:hover:bg-[#10231f] dark:hover:text-accent-200">
          <span class="sr-only">Abrir opções</span>
          <slot name="icon" />
        </MenuButton>
      </div>
  
      <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-2xl border border-[#ded3bf] bg-[#fffaf0] p-1.5 shadow-[0_20px_55px_rgba(20,61,55,0.16)] ring-1 ring-[#143d37]/5 focus:outline-none dark:border-[#25443c] dark:bg-[#0c1714] dark:ring-white/10">
          <div class="space-y-1">
            <slot name="options" />
          </div>
        </MenuItems>
      </transition>
    </Menu>
  </template>
  
  <script setup>
  import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

  const emit = defineEmits([
    'create',
    'selected'
]);
  </script>
