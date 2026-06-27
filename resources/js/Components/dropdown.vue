<template>
    <Menu as="div" class="relative ml-4 inline-block text-left">
      <div>
        <MenuButton class="ds-icon-button rounded-full">
          <span class="sr-only">Abrir opções</span>
          <slot name="icon" />
        </MenuButton>
      </div>
  
      <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
        <MenuItems class="ds-card absolute right-0 z-10 mt-2 w-56 origin-top-right p-1.5 focus:outline-none">
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
