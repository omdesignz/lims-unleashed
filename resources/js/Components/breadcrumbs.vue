<template>
  <nav aria-label="Breadcrumb" class="flex">
    <ol role="list" class="flex flex-wrap items-center gap-1.5 rounded-full border border-[#ded3bf]/80 bg-[#fffaf0]/78 px-3 py-2 shadow-sm backdrop-blur dark:border-[#25443c] dark:bg-[#0c1714]/76">
      <!-- Home -->
      <li>
        <div>
          <Link
            :href="route('dashboard')"
            class="text-primary-700/70 transition-colors duration-150 hover:text-primary-800 dark:text-accent-200/70 dark:hover:text-accent-100"
          >
            <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
            <span class="sr-only">Home</span>
          </Link>
        </div>
      </li>

      <!-- Pages -->
      <li v-for="page in pages" :key="page.name" class="flex items-center gap-1.5">
        <ChevronRightIcon
          class="h-4 w-4 flex-shrink-0 text-slate-400 dark:text-slate-600"
          aria-hidden="true"
        />
        <Link
          v-if="!page.current"
          :href="page.url"
          class="text-sm font-semibold text-slate-500 transition-colors duration-150 hover:text-primary-800 dark:text-slate-400 dark:hover:text-accent-100"
        >
          {{ $t(page.title) }}
        </Link>
        <span
          v-else
          class="text-sm font-bold text-primary-800 dark:text-accent-100"
          aria-current="page"
        >
          {{ $t(page.title) }}
        </span>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { ChevronRightIcon, HomeIcon } from '@heroicons/vue/24/outline'
import { Link } from '@inertiajs/vue3'

defineProps({
  pages: {
    type: Array,
    default: () => [],
  },
})
</script>
