<template>
  <nav class="ds-pagination flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" aria-label="Pagination">
    <p class="order-2 text-sm font-semibold text-[var(--ds-text-muted)] sm:order-1">
      {{ $t('gestlab.pagination.showing') }}
      <span class="font-extrabold text-[var(--ds-text)]">{{ from }}</span>
      {{ $t('gestlab.pagination.to') }}
      <span class="font-extrabold text-[var(--ds-text)]">{{ to }}</span>
      {{ $t('gestlab.pagination.of') }}
      <span class="font-extrabold text-[var(--ds-text)]">{{ total }}</span>
      {{ $t('gestlab.pagination.records') }}
    </p>

    <div v-if="last_page > 1" class="order-1 flex items-center gap-1 sm:order-2">
      <!-- First Page -->
      <button
        :disabled="noPreviousPage"
        @click="loadPage(1)"
        :class="[
          'ds-icon-button h-9 w-9 rounded-xl',
          noPreviousPage
            ? 'cursor-not-allowed opacity-35'
            : '',
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
          'ds-icon-button h-9 w-9 rounded-xl',
          noPreviousPage
            ? 'cursor-not-allowed opacity-35'
            : '',
        ]"
      >
        <span class="sr-only">{{ $t('Previous') }}</span>
        <ChevronLeftIcon class="h-4 w-4" />
      </button>

      <!-- Page input -->
      <div class="flex items-center gap-1.5 rounded-2xl border border-[var(--ds-border)] bg-[var(--ds-panel-subtle)] px-1.5 py-1">
        <input
          type="number"
          min="1"
          :max="last_page"
          v-model="data.page"
          @keydown.enter="loadPage(data.page)"
          class="ds-field min-h-0 w-14 rounded-xl px-1 py-1.5 text-center text-sm font-extrabold tabular-nums [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
        />
        <span class="text-xs font-semibold text-[var(--ds-text-soft)]">/ {{ last_page }}</span>
      </div>

      <!-- Next -->
      <button
        :disabled="noNextPage"
        @click="loadPage(current_page + 1)"
        :class="[
          'ds-icon-button h-9 w-9 rounded-xl',
          noNextPage
            ? 'cursor-not-allowed opacity-35'
            : '',
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
          'ds-icon-button h-9 w-9 rounded-xl',
          noNextPage
            ? 'cursor-not-allowed opacity-35'
            : '',
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
