<template>
    <Menu as="div" class="relative inline-block text-left ml-4">
      <div>
        <MenuButton class="flex items-center text-gray-400 hover:text-gray-600 focus:outline-none">
          <span class="sr-only">Open options</span>
          <slot name="icon" />
        </MenuButton>
      </div>
  
      <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
          <div class="py-1">
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