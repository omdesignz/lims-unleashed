<template>
  <nav class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3" aria-label="Pagination">
    <p class="text-sm text-gray-500 dark:text-gray-400 order-2 sm:order-1">
      {{ $t('gestlab.pagination.showing') }}
      <span class="font-semibold text-gray-700 dark:text-gray-300">{{ from }}</span>
      {{ $t('gestlab.pagination.to') }}
      <span class="font-semibold text-gray-700 dark:text-gray-300">{{ to }}</span>
      {{ $t('gestlab.pagination.of') }}
      <span class="font-semibold text-gray-700 dark:text-gray-300">{{ total }}</span>
      {{ $t('gestlab.pagination.records') }}
    </p>

    <div v-if="last_page > 1" class="flex items-center gap-1 order-1 sm:order-2">
      <!-- First Page -->
      <button
        :disabled="noPreviousPage"
        @click="loadPage(1)"
        :class="[
          'inline-flex items-center justify-center rounded-lg p-2 text-sm transition-colors duration-150',
          noPreviousPage
            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800',
        ]"
      >
        <span class="sr-only">{{ $t('First page') }}</span>
        <ChevronDoubleLeftIcon class="h-4 w-4" />
      </button>

      <!-- Previous -->
      <button
        :disabled="noPreviousPage"
        @click="loadPage(current_page - 1)"
        :class="[
          'inline-flex items-center justify-center rounded-lg p-2 text-sm transition-colors duration-150',
          noPreviousPage
            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800',
        ]"
      >
        <span class="sr-only">{{ $t('Previous') }}</span>
        <ChevronLeftIcon class="h-4 w-4" />
      </button>

      <!-- Page input -->
      <div class="flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 px-1 py-0.5 ring-1 ring-gray-200 dark:ring-gray-700">
        <input
          type="number"
          min="1"
          :max="last_page"
          v-model="data.page"
          @keydown.enter="loadPage(data.page)"
          class="w-14 text-center rounded-md border-0 bg-white dark:bg-gray-700 px-1 py-1.5 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-primary-500 tabular-nums [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        />
        <span class="text-xs text-gray-400 dark:text-gray-500">/ {{ last_page }}</span>
      </div>

      <!-- Next -->
      <button
        :disabled="noNextPage"
        @click="loadPage(current_page + 1)"
        :class="[
          'inline-flex items-center justify-center rounded-lg p-2 text-sm transition-colors duration-150',
          noNextPage
            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800',
        ]"
      >
        <span class="sr-only">{{ $t('Next') }}</span>
        <ChevronRightIcon class="h-4 w-4" />
      </button>

      <!-- Last Page -->
      <button
        :disabled="noNextPage"
        @click="loadPage(last_page)"
        :class="[
          'inline-flex items-center justify-center rounded-lg p-2 text-sm transition-colors duration-150',
          noNextPage
            ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800',
        ]"
      >
        <span class="sr-only">{{ $t('Last page') }}</span>
        <ChevronDoubleRightIcon class="h-4 w-4" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { watch, computed, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon, ChevronDoubleLeftIcon, ChevronDoubleRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  links: Array,
  total: Number,
  from: Number,
  to: Number,
  last_page: Number,
  current_page: Number,
})

const data = reactive({
  page: props.current_page,
})

const loadPage = (page) => {
  if (page < 1 || page > props.last_page) return
  router.get(usePage().url, { page }, {
    preserveScroll: true,
    preserveState: false,
    replace: true,
  })
}

const noPreviousPage = computed(() => props.current_page <= 1)
const noNextPage = computed(() => props.current_page >= props.last_page)

watch(() => props.current_page, (value) => {
  data.page = value
})
</script>