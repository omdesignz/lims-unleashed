<script setup>
import {ref, onMounted} from "vue";
import { TransitionRoot, TransitionChild } from '@headlessui/vue'
import {
    BeakerIcon,
    ArchiveBoxIcon,
    UserGroupIcon,
    DocumentTextIcon,
    BanknotesIcon,
    CubeIcon,
    DocumentCheckIcon,
    QueueListIcon,
    EyeIcon,
    EyeSlashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  stats: Object
});

const items = [
  { name: 'gestlab.stats.boards.analysis.title', icon: BeakerIcon, href: '#', members: props.stats.analysis, bgColor: 'bg-pink-600' },
  { name: 'gestlab.stats.boards.collections.title', icon: ArchiveBoxIcon, href: '#', members: props.stats.collections, bgColor: 'bg-purple-600' },
  { name: 'gestlab.stats.boards.customers.title', icon: UserGroupIcon, href: '#', members: props.stats.customers, bgColor: 'bg-yellow-500' },
  { name: 'gestlab.stats.boards.analysis_reports.title', icon: DocumentTextIcon, href: '#', members: props.stats.certificates, bgColor: 'bg-green-500' },
  { name: 'gestlab.stats.boards.invoices.title', icon: BanknotesIcon, href: '#', members: props.stats.invoices, bgColor: 'bg-pink-600' },
  { name: 'gestlab.stats.boards.products.title', icon: CubeIcon, href: '#', members: props.stats.products, bgColor: 'bg-purple-600' },
  { name: 'gestlab.stats.boards.standards.title', icon: DocumentCheckIcon, href: '#', members: props.stats.standards, bgColor: 'bg-yellow-500' },
  { name: 'gestlab.stats.boards.profiles.title', icon: QueueListIcon, href: '#', members: props.stats.profiles, bgColor: 'bg-green-500' },
]
const isShowing = ref(false);

onMounted(() => {
    isShowing.value = true;
});
</script>
<template>
      <span class="isolate inline-flex rounded-md shadow-sm m-1">
    <button class="relative inline-flex items-center gap-x-1.5 rounded-l-md bg-white dark:bg-gray-900 px-2 py-1 text-xs font-semibold text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-10">
      <!-- <BookmarkIcon class="-ml-0.5 h-5 w-5 text-gray-400" aria-hidden="true" /> -->
      
      {{ $t('gestlab.stats.title') }}
    </button>
    <button @click="isShowing = !isShowing" class="relative -ml-px inline-flex items-center rounded-r-md bg-white dark:bg-gray-900 px-2 py-1 text-xs font-semibold text-gray-900 dark:text-gray-400 ring-1 ring-inset ring-gray-300 dark:ring-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 focus:z-10">
        <EyeIcon v-if="!isShowing" class="w-5 h-5" />
            <EyeSlashIcon v-else class="w-5 h-5" />
    </button>
  </span>

    <TransitionRoot :show="isShowing">
    <!-- Background overlay -->
    <TransitionChild
      enter="transition-opacity ease-linear duration-300"
      enter-from="opacity-0"
      enter-to="opacity-100"
      leave="transition-opacity ease-linear duration-300"
      leave-from="opacity-100"
      leave-to="opacity-0"
    >
      <!-- ... -->
    </TransitionChild>

    <!-- Sliding sidebar -->
    <TransitionChild
      enter="transition ease-in-out duration-300 transform"
      enter-from="-translate-x-full"
      enter-to="translate-x-0"
      leave="transition ease-in-out duration-300 transform"
      leave-from="translate-x-0"
      leave-to="-translate-x-full"
    >
    <div class="mb-4">
    <ul role="list" class="mt-3 grid grid-cols-1 gap-5 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
      <li v-for="item in items" :key="item.name" class="col-span-1 flex rounded-md shadow-sm">
        <div :class="[item.bgColor, 'flex w-16 flex-shrink-0 items-center justify-center rounded-l-md text-sm font-medium text-white']">
            <component :is="item.icon" class="h-8 w-8" aria-hidden="true" />
        
        </div>
        <div class="flex flex-1 items-center justify-between truncate rounded-r-md border-b border-r border-t border-gray-200 dark:border-gray-900 bg-white dark:bg-gray-900">
          <div class="flex-1 truncate px-4 py-2 text-sm">
            <a :href="item.href" class="font-medium text-gray-400 hover:text-gray-600 dark:text-gray-400">{{ $t(item.name) }}</a>
            <p class="text-gray-500 dark:text-gray-400">{{ item.members }} {{ $t('gestlab.stats.records') }}</p>
          </div>
        </div>
      </li>
    </ul>
  </div>
    </TransitionChild>
  </TransitionRoot>
</template>