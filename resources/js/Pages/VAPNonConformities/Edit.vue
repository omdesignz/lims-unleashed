<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">CAPA dossier</span>
            <span :class="['inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', statusChipClass]">
              <span :class="['h-2 w-2 rounded-full', statusDotClass]"></span>
              {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nonConformity.status}`) }}
            </span>
          </div>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div>
              <h1 class="ds-heading text-2xl">{{ $t('gestlab.general.labels.vap_non_conformities.edit_title') }}</h1>
              <p class="ds-copy mt-2 text-sm">
                {{ $t('gestlab.general.labels.vap_non_conformities.edit_description') }}
                <span class="font-bold text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">#{{ nonConformity.nc_number }}</span>
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
          <Link :href="route('vap_non_conformities.show', nonConformity.id)" class="ds-button ds-button-secondary">
            <EyeIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.view') }}
          </Link>
          <Link :href="route('vap_non_conformities.index')" class="ds-button ds-button-secondary">
            <ArrowLeftIcon class="h-4 w-4" />
            {{ $t('gestlab.general.labels.vap_non_conformities.buttons.back_to_list') }}
          </Link>
        </div>
      </div>

    </section>

    <NonConformityForm
      :non-conformity="nonConformity"
      :labs="labs"
      :departments="departments"
      :is-editing="true"
    />
  </div>
</template>

<script setup>
import NonConformityForm from '@/Pages/VAPNonConformities/NonConformityForm.vue'
import { Link } from '@inertiajs/vue3'
import { ArrowLeftIcon, ExclamationTriangleIcon, EyeIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
  nonConformity: {
    type: Object,
    required: true,
  },
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
})

const statusChipClasses = {
  opened: 'bg-[rgb(var(--primary-50-rgb))] text-[rgb(var(--primary-800-rgb))] ring-[rgb(var(--primary-200-rgb))] dark:bg-[rgb(var(--primary-400-rgb)/0.12)] dark:text-[rgb(var(--accent-100-rgb))] dark:ring-[rgb(var(--primary-300-rgb)/0.22)]',
  in_progress: 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
  resolved: 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
  closed: 'bg-[var(--ds-panel-muted)] text-[var(--ds-text-muted)] ring-[var(--ds-border-strong)]',
}

const statusDotClasses = {
  opened: 'bg-[rgb(var(--primary-700-rgb))]',
  in_progress: 'bg-amber-500',
  resolved: 'bg-emerald-500',
  closed: 'bg-[var(--ds-text-soft)]',
}

const statusChipClass = computed(() => statusChipClasses[props.nonConformity.status] || statusChipClasses.opened)
const statusDotClass = computed(() => statusDotClasses[props.nonConformity.status] || statusDotClasses.opened)
</script>
