<template>
  <div class="min-w-0 space-y-6 overflow-x-clip">
    <section class="ds-panel overflow-hidden">
      <div class="flex flex-col gap-5 border-b border-[var(--ds-border)] px-5 py-5 lg:flex-row lg:items-start lg:justify-between lg:px-6">
        <div class="max-w-3xl">
          <div class="flex flex-wrap items-center gap-2">
            <span class="ds-kicker">Quality event intake</span>
            <span class="ds-chip">
              <span class="lims-status-dot lims-status-dot-hold"></span>
              {{ $t('gestlab.general.labels.vap_non_conformities.general.new') }}
            </span>
          </div>
          <div class="mt-3 flex items-start gap-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg border border-[var(--ds-border)] bg-[var(--ds-panel-raised)] text-rose-700 dark:text-rose-300">
              <ExclamationTriangleIcon class="h-5 w-5" />
            </span>
            <div>
              <h1 class="ds-heading text-2xl">{{ $t('gestlab.general.labels.vap_non_conformities.create_title') }}</h1>
              <p class="ds-copy mt-2 text-sm">
                {{ $t('gestlab.general.labels.vap_non_conformities.create_description') }}
              </p>
            </div>
          </div>
        </div>

        <Link :href="route('vap_non_conformities.index')" class="ds-button ds-button-secondary shrink-0">
          <ArrowLeftIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.back_to_list') }}
        </Link>
      </div>

    </section>

    <NonConformityForm
      :non-conformity="initialNonConformity"
      :labs="labs"
      :departments="departments"
      :is-editing="false"
    />
  </div>
</template>

<script setup>
import NonConformityForm from '@/Pages/VAPNonConformities/NonConformityForm.vue'
import { Link } from '@inertiajs/vue3'
import { ArrowLeftIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
  labs: {
    type: Array,
    default: () => [],
  },
  departments: {
    type: Array,
    default: () => [],
  },
  defaultNcNumber: {
    type: String,
    default: '',
  },
})

const initialNonConformity = computed(() => ({
  nc_number: props.defaultNcNumber,
  status: 'opened',
  severity: 'medium',
  category: 'quality',
  reported_at: new Date().toISOString().slice(0, 16),
}))

</script>
