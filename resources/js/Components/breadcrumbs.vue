<template>
  <nav aria-label="Breadcrumb" class="flex">
    <ol role="list" class="flex items-center gap-1.5">
      <!-- Home -->
      <li>
        <div>
          <Link
            :href="route('dashboard')"
            class="text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400 transition-colors duration-150"
          >
            <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
            <span class="sr-only">Home</span>
          </Link>
        </div>
      </li>

      <!-- Pages -->
      <li v-for="page in pages" :key="page.name" class="flex items-center gap-1.5">
        <ChevronRightIcon
          class="h-4 w-4 flex-shrink-0 text-gray-400 dark:text-gray-600"
          aria-hidden="true"
        />
        <Link
          v-if="!page.current"
          :href="page.url"
          class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors duration-150"
        >
          {{ $t(page.title) }}
        </Link>
        <span
          v-else
          class="text-sm font-semibold text-primary-700 dark:text-primary-300"
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