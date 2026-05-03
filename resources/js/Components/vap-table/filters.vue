<script setup>
import { defineProps, defineEmits, ref } from 'vue';
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'
import {
FunnelIcon,
XMarkIcon
} from "@heroicons/vue/24/outline";
import { onClickOutside } from '@vueuse/core'
  
  const props = defineProps({
    filters: Array,
    activeFilters: Array,
    trashedFilter: Boolean,
  });

  const filters = ref(props.filters);
  const emit = defineEmits(['update-filters', 'clear-filter-input']);
  
  const updateFilters = (e) => {
    emit('update-filters', e);
  };

  const clearFilterInput = (e) => {
    emit('clear-filter-input', e)
  }
</script>

<template>
    <Menu  as="div" class="relative inline-block text-left">
        <div>
            <MenuButton class="inline-flex gap-x-1.5 items-center rounded-full bg-blue-900 dark:bg-gray-900 px-3 py-1 text-sm font-normal text-white dark:text-gray-400 shadow-sm ring-1 ring-inset ring-blue-900 dark:ring-gray-700 hover:bg-blue-800 dark:hover:bg-gray-700 disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-blue-900 dark:disabled:hover:bg-gray-700 dark:hover:text-white">
                
                <FunnelIcon class="-ml-1 mr-2 h-5 w-5 text-white dark:text-gray-400 dark:hover:text-white" aria-hidden="true" />
                {{ $t('gestlab.filter.filters') }}
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-100 rounded-md bg-white dark:bg-gray-900 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                <div class="px-4 py-1 bg-blue-900 dark:bg-gray-900 rounded-br-full">
                    <p class="text-sm text-white dark:text-gray-400 font-semibold">
                        {{ $t('gestlab.filter.add_filters') }}
                    </p>
                </div>
                <MenuItem as="div" class="px-4 mb-4">

                <span class="isolate inline-flex rounded-l-full rounded-r-md shadow-sm w-full mt-2" v-for="(filter, index) in props.filters" :key="index">
                    <button type="button"
                        @click="updateFilters(filter)" 
                        class="relative w-full inline-flex items-center gap-x-1.5 rounded-l-full px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10"
                        :class="[props.activeFilters.includes(filter.filter_field) ? 'bg-blue-900 dark:bg-gray-800 text-white dark:text-gray-400 hover:bg-blue-800 dark:hover:bg-gray-700 dark:hover:text-white ring-blue-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        {{ $t(filter.label) }}
                    </button>
                    <button type="button"
                        :disabled="!props.activeFilters.includes(filter.filter_field)"
                        @click="clearFilterInput(filter.filter_field)"
                        class="relative -ml-px inline-flex items-center rounded-r-md px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10"
                        :class="[props.activeFilters.includes(filter.filter_field) ? 'bg-blue-900 dark:bg-gray-800 text-white dark:text-gray-400 hover:bg-blue-800 dark:hover:bg-gray-700 dark:hover:text-white ring-blue-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400 hover:bg-gray-50 dark:hover:text-white dark:hover:text-white dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        <XMarkIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                    </button>
                </span>
                
                <!-- Trashed Filter -->
                <span class="isolate inline-flex rounded-l-full rounded-r-md shadow-sm w-full mt-2" v-if="props.trashedFilter">
                    <button type="button"
                        @click="updateFilters({ field: 'trashed', value: $t('gestlab.general.labels.trashed_only') })"
                        class="relative w-full inline-flex items-center gap-x-1.5 rounded-l-full px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10"
                        :class="[props.activeFilters.includes('trashed') ? 'bg-blue-900 dark:bg-gray-800 text-white dark:text-gray-400 hover:bg-blue-800 dark:hover:bg-gray-700 dark:hover:text-white ring-blue-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        {{ $t('gestlab.general.labels.trashed') }}
                    </button>
                    <button type="button"
                        :disabled="!props.activeFilters.includes('trashed')"
                        @click="clearFilterInput('trashed')" 
                        class="relative -ml-px inline-flex items-center rounded-r-md px-3 py-1 text-xs font-semibold ring-1 ring-inset focus:z-10"
                        :class="[props.activeFilters.includes('trashed') ? 'bg-blue-900 dark:bg-gray-800 text-white dark:text-gray-400 hover:bg-blue-800 dark:hover:bg-gray-700 dark:hover:text-white ring-blue-900 dark:ring-gray-700' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-400 hover:bg-gray-50 dark:hover:text-white dark:hover:bg-gray-700 ring-gray-300 dark:ring-gray-700']"
                        >
                        <XMarkIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                    </button>
                </span>
                <br>
                    
                </MenuItem>
            
            </MenuItems>
        </transition>
    </Menu>
</template>