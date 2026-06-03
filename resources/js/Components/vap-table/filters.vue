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
            <MenuButton class="inline-flex items-center gap-x-1.5 rounded-full bg-[#143d37] px-4 py-2 text-sm font-semibold text-white shadow-sm ring-1 ring-inset ring-[#143d37] transition hover:bg-[#176452] disabled:cursor-not-allowed disabled:opacity-30 dark:bg-[#10231f] dark:text-[#f7f1e7] dark:ring-[#25443c] dark:hover:bg-[#16342e]">
                
                <FunnelIcon class="-ml-1 mr-1 h-5 w-5 text-white" aria-hidden="true" />
                {{ $t('gestlab.filter.filters') }}
            </MenuButton>
        </div>

        <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
            <MenuItems class="absolute right-0 z-50 mt-2 w-72 origin-top-right overflow-hidden rounded-2xl border border-slate-200 bg-white/98 shadow-2xl ring-1 ring-slate-900/5 backdrop-blur-sm focus:outline-none dark:border-slate-700 dark:bg-slate-900/98 dark:ring-white/10">
                <div class="bg-gradient-to-r from-[#143d37] to-[#1f7a68] px-4 py-3">
                    <p class="text-sm font-bold text-white">
                        {{ $t('gestlab.filter.add_filters') }}
                    </p>
                </div>
                <MenuItem as="div" class="px-4 pb-4">

                <span class="isolate mt-3 inline-flex w-full rounded-2xl shadow-sm" v-for="(filter, index) in props.filters" :key="index">
                    <button type="button"
                        @click="updateFilters(filter)" 
                        class="relative inline-flex w-full items-center gap-x-1.5 rounded-l-2xl px-3 py-2 text-xs font-bold ring-1 ring-inset transition focus:z-10"
                        :class="[props.activeFilters.includes(filter.filter_field) ? 'bg-[#143d37] text-white ring-[#143d37] hover:bg-[#176452] dark:bg-[#1f7a68] dark:text-white dark:ring-[#1f7a68]' : 'bg-white text-slate-700 ring-slate-200 hover:bg-[#eef7f3] hover:text-[#143d37] dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700']"
                        >
                        {{ $t(filter.label) }}
                    </button>
                    <button type="button"
                        :disabled="!props.activeFilters.includes(filter.filter_field)"
                        @click="clearFilterInput(filter.filter_field)"
                        class="relative -ml-px inline-flex items-center rounded-r-2xl px-3 py-2 text-xs font-bold ring-1 ring-inset transition focus:z-10"
                        :class="[props.activeFilters.includes(filter.filter_field) ? 'bg-[#143d37] text-white ring-[#143d37] hover:bg-[#176452] dark:bg-[#1f7a68] dark:text-white dark:ring-[#1f7a68]' : 'bg-white text-slate-700 ring-slate-200 hover:bg-[#eef7f3] hover:text-[#143d37] dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700']"
                        >
                        <XMarkIcon class="-ml-0.5 h-5 w-5" aria-hidden="true" />
                    </button>
                </span>
                
                <!-- Trashed Filter -->
                <span class="isolate mt-3 inline-flex w-full rounded-2xl shadow-sm" v-if="props.trashedFilter">
                    <button type="button"
                        @click="updateFilters({ field: 'trashed', value: $t('gestlab.general.labels.trashed_only') })"
                        class="relative inline-flex w-full items-center gap-x-1.5 rounded-l-2xl px-3 py-2 text-xs font-bold ring-1 ring-inset transition focus:z-10"
                        :class="[props.activeFilters.includes('trashed') ? 'bg-[#143d37] text-white ring-[#143d37] hover:bg-[#176452] dark:bg-[#1f7a68] dark:text-white dark:ring-[#1f7a68]' : 'bg-white text-slate-700 ring-slate-200 hover:bg-[#eef7f3] hover:text-[#143d37] dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700']"
                        >
                        {{ $t('gestlab.general.labels.trashed') }}
                    </button>
                    <button type="button"
                        :disabled="!props.activeFilters.includes('trashed')"
                        @click="clearFilterInput('trashed')" 
                        class="relative -ml-px inline-flex items-center rounded-r-2xl px-3 py-2 text-xs font-bold ring-1 ring-inset transition focus:z-10"
                        :class="[props.activeFilters.includes('trashed') ? 'bg-[#143d37] text-white ring-[#143d37] hover:bg-[#176452] dark:bg-[#1f7a68] dark:text-white dark:ring-[#1f7a68]' : 'bg-white text-slate-700 ring-slate-200 hover:bg-[#eef7f3] hover:text-[#143d37] dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-700']"
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
