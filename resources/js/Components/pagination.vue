<template>
  <nav class="flex flex-col gap-3 rounded-[1.5rem] border border-[#ded3bf] bg-[#fffdf7]/85 px-4 py-3 shadow-sm backdrop-blur dark:border-[#25443c] dark:bg-[#07110f]/80 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
    <p class="order-2 text-sm font-medium text-slate-600 dark:text-slate-300 sm:order-1">
      {{ $t('gestlab.pagination.showing') }}
      <span class="font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ from }}</span>
      {{ $t('gestlab.pagination.to') }}
      <span class="font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ to }}</span>
      {{ $t('gestlab.pagination.of') }}
      <span class="font-bold text-[#15231f] dark:text-[#f7f1e7]">{{ total }}</span>
      {{ $t('gestlab.pagination.records') }}
    </p>

    <div v-if="last_page > 1" class="order-1 flex items-center gap-1 sm:order-2">
      <!-- First Page -->
      <button
        :disabled="noPreviousPage"
        @click="loadPage(1)"
        :class="[
          'inline-flex items-center justify-center rounded-xl p-2 text-sm transition-colors duration-150',
          noPreviousPage
            ? 'cursor-not-allowed text-slate-300 dark:text-slate-600'
            : 'text-slate-500 hover:bg-[#eef7f3] hover:text-[rgb(var(--primary-800-rgb))] dark:text-slate-400 dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]',
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
          'inline-flex items-center justify-center rounded-xl p-2 text-sm transition-colors duration-150',
          noPreviousPage
            ? 'cursor-not-allowed text-slate-300 dark:text-slate-600'
            : 'text-slate-500 hover:bg-[#eef7f3] hover:text-[rgb(var(--primary-800-rgb))] dark:text-slate-400 dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]',
        ]"
      >
        <span class="sr-only">{{ $t('Previous') }}</span>
        <ChevronLeftIcon class="h-4 w-4" />
      </button>

      <!-- Page input -->
      <div class="flex items-center gap-1.5 rounded-2xl bg-[#f7f1e7] px-1.5 py-1 ring-1 ring-[#ded3bf] dark:bg-[#10231f] dark:ring-[#25443c]">
        <input
          type="number"
          min="1"
          :max="last_page"
          v-model="data.page"
          @keydown.enter="loadPage(data.page)"
          class="w-14 rounded-xl border-0 bg-white px-1 py-1.5 text-center text-sm font-bold tabular-nums text-[#15231f] shadow-sm ring-1 ring-inset ring-[#d8cfbe] focus:ring-2 focus:ring-[rgb(var(--primary-500-rgb))] dark:bg-[#07110f] dark:text-[#f7f1e7] dark:ring-[#25443c] [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        />
        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500">/ {{ last_page }}</span>
      </div>

      <!-- Next -->
      <button
        :disabled="noNextPage"
        @click="loadPage(current_page + 1)"
        :class="[
          'inline-flex items-center justify-center rounded-xl p-2 text-sm transition-colors duration-150',
          noNextPage
            ? 'cursor-not-allowed text-slate-300 dark:text-slate-600'
            : 'text-slate-500 hover:bg-[#eef7f3] hover:text-[rgb(var(--primary-800-rgb))] dark:text-slate-400 dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]',
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
          'inline-flex items-center justify-center rounded-xl p-2 text-sm transition-colors duration-150',
          noNextPage
            ? 'cursor-not-allowed text-slate-300 dark:text-slate-600'
            : 'text-slate-500 hover:bg-[#eef7f3] hover:text-[rgb(var(--primary-800-rgb))] dark:text-slate-400 dark:hover:bg-[#10231f] dark:hover:text-[#f7f1e7]',
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
