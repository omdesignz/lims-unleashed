<template>
  <nav :aria-label="$t('gestlab.general.navigation.breadcrumb')" class="flex">
    <ol role="list" class="flex flex-wrap items-center gap-1.5 rounded-full border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] px-3 py-2 shadow-[var(--ds-shadow-control)] backdrop-blur">
      <!-- Home -->
      <li>
        <div>
          <Link
            :href="route('dashboard')"
            class="text-[var(--ds-text-soft)] transition-colors duration-150 hover:text-[rgb(var(--primary-700-rgb))] dark:hover:text-[rgb(var(--accent-100-rgb))]"
          >
            <HomeIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true" />
            <span class="sr-only">{{ $t('gestlab.general.navigation.home') }}</span>
          </Link>
        </div>
      </li>

      <!-- Pages -->
      <li v-for="page in pages" :key="page.name" class="flex items-center gap-1.5">
        <ChevronRightIcon
          class="h-4 w-4 flex-shrink-0 text-[var(--ds-text-soft)]"
          aria-hidden="true"
        />
        <Link
          v-if="!page.current"
          :href="page.url"
          class="text-sm font-semibold text-[var(--ds-text-muted)] transition-colors duration-150 hover:text-[rgb(var(--primary-700-rgb))] dark:hover:text-[rgb(var(--accent-100-rgb))]"
        >
          {{ $t(page.title) }}
        </Link>
        <span
          v-else
          class="text-sm font-bold text-[var(--ds-text)]"
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
