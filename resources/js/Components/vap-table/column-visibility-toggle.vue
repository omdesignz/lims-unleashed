<template>
    <Menu  as="div" class="relative inline-block text-left">
        <div>
            <MenuButton class="inline-flex gap-x-1.5 items-center rounded-full bg-primary-900 dark:bg-gray-900 px-3 py-1 text-sm font-normal text-white dark:text-gray-400 shadow-sm ring-1 ring-inset ring-primary-900 dark:ring-gray-700 hover:bg-primary-800 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-primary-900 dark:disabled:hover:bg-gray-700 dark:hover:text-white">
                
                <EyeIcon class="-ml-1 mr-2 h-5 w-5 text-white dark:text-gray-400 dark:hover:text-white" aria-hidden="true" />
                {{ $t('gestlab.general.labels.columns') }}
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white dark:bg-gray-900 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="px-4 py-1 bg-primary-900 dark:bg-gray-900 rounded-br-full">
                    <p class="text-sm text-white dark:text-gray-400 font-semibold">
                        {{ $t('gestlab.general.labels.toggle_column_visibility') }}
                    </p>
                </div>
                <MenuItem as="div" class="px-4 mb-4">

                <span class="isolate inline-flex rounded-l-full rounded-r-md shadow-sm w-full mt-2" v-for="(column, index) in columns" :key="column.field">
                    <button type="button" disabled
                        class="relative w-full inline-flex items-center gap-x-1.5 rounded-l-full px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10 pointer-events-none cursor-default"
                        :class="[column.visible ? 'bg-primary-900 text-white dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white hover:bg-primary-800 dark:hover:bg-gray-700 ring-primary-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:hover:text-white dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        {{ $t(column.label) }}
                    </button>
                    <button type="button"
                        @click="() => toggleColumnVisibility(column.field)"
                        class="relative -ml-px inline-flex items-center rounded-r-md px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10"
                        :class="[column.visible ? 'bg-primary-900 text-white dark:bg-gray-800 dark:text-gray-400 dark:hover:text-white hover:bg-primary-800 dark:hover:bg-gray-700 ring-primary-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:hover:text-white dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        <EyeIcon v-if="column.visible" class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                        <EyeSlashIcon v-else class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                    </button>
                </span> <br>
                    
                </MenuItem>
            
            </MenuItems>
        </transition>
    </Menu>
  </template>
  
  <script setup>
  import { defineProps, defineEmits } from 'vue';
  import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
  import {
EyeIcon,
EyeSlashIcon
} from "@heroicons/vue/24/outline";
  
  const props = defineProps({
    columns: Array,
  });
  const emit = defineEmits(['update-columns']);
  
  const moveColumn = (index, direction) => {
    const newIndex = index + direction;
    const column = props.columns.splice(index, 1)[0];
    props.columns.splice(newIndex, 0, column);
    emit('update-columns', [...props.columns]);
  };
  
  const updateFilters = () => {
    emit('update-columns', [...props.columns]);
  };

  const updateVisibility = () => {
  emit('update-columns', [...props.columns]);
};

  const toggleAllColumnsVisibility = () => {
    props.columns.forEach(column => {
      column.visible = !column.visible;
    });
    emit('update-columns', [...props.columns]);
  };

  const toggleColumnVisibility = (field) => {

    event.preventDefault();

    props.columns.forEach(column => {
      if(column.field === field){
        column.visible = !column.visible;
      }
    });
    emit('update-columns', [...props.columns]);
  };

  </script>
  