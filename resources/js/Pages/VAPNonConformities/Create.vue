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

      <dl class="grid grid-cols-2 divide-x divide-y divide-[var(--ds-border)] md:grid-cols-4 md:divide-y-0">
        <div v-for="metric in intakeMetrics" :key="metric.label" class="px-5 py-4">
          <dt class="flex items-center gap-2 text-xs font-bold uppercase text-[var(--ds-text-soft)]">
            <span class="lims-status-dot" :class="metric.dotClass"></span>
            {{ metric.label }}
          </dt>
          <dd class="mt-2 text-xl font-bold text-[var(--ds-text)]">{{ metric.value }}</dd>
          <p class="mt-1 text-xs font-semibold text-[var(--ds-text-soft)]">{{ metric.caption }}</p>
        </div>
      </dl>
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

const intakeMetrics = computed(() => [
  {
    label: 'Identificação',
    value: props.defaultNcNumber || 'NC',
    caption: 'Número reservado para rastreabilidade',
    dotClass: 'lims-status-dot-instrument',
  },
  {
    label: 'Laboratórios',
    value: props.labs.length,
    caption: 'Locais disponíveis',
    dotClass: 'lims-status-dot-release',
  },
  {
    label: 'Departamentos',
    value: props.departments.length,
    caption: 'Áreas de responsabilidade',
    dotClass: 'lims-status-dot-release',
  },
  {
    label: 'Fluxo',
    value: 'CAPA',
    caption: 'Investigação, ação e evidência',
    dotClass: 'lims-status-dot-hold',
  },
])
</script>
