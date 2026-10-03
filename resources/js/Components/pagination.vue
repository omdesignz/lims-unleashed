<template>
  <nav class="ds-pagination flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Paginação">
    <p class="order-2 text-[var(--pl-muted)] sm:order-1" role="status">
      <span class="pl-num text-[var(--pl-fg)]">{{ from ?? 0 }}–{{ to ?? 0 }}</span>
      {{ $t('gestlab.pagination.of') }}
      <span class="pl-num text-[var(--pl-fg)]">{{ total }}</span>
      {{ $t('gestlab.pagination.records') }}
    </p>

    <div v-if="last_page > 1" class="pl-pager order-1 sm:order-2">
      <button type="button" :disabled="noPreviousPage" @click="loadPage(current_page - 1)">
        <ChevronLeftIcon aria-hidden="true" /><span>{{ $t('Previous') }}</span>
      </button>
      <template v-for="(page, index) in pages" :key="`${page}-${index}`">
        <span v-if="page === null" aria-hidden="true">…</span>
        <button v-else type="button" :aria-current="page === current_page ? 'page' : undefined" @click="loadPage(page)">{{ page }}</button>
      </template>
      <button type="button" :disabled="noNextPage" @click="loadPage(current_page + 1)">
        <span>{{ $t('Next') }}</span><ChevronRightIcon aria-hidden="true" />
      </button>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ChevronLeft as ChevronLeftIcon, ChevronRight as ChevronRightIcon } from '@lucide/vue'

const props = defineProps({
  links: Array,
  total: Number,
  from: Number,
  to: Number,
  last_page: Number,
  current_page: Number,
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

// First, last and the pages around the current one; gaps collapse to an ellipsis.
const pages = computed(() => {
  const last = props.last_page ?? 1
  const current = props.current_page ?? 1
  const wanted = [...new Set([1, current - 1, current, current + 1, last])].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b)

  return wanted.flatMap((page, index) => (index && page - wanted[index - 1] > 1 ? [null, page] : [page]))
})
</script>
