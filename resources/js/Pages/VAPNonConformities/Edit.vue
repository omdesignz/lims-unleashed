<template>
  <div class="pl-page space-y-6">
    <Head title="Editar não conformidade" />
    <PageHeader :trail="[{ title: 'Não conformidades', url: route('vap_non_conformities.index') }, { title: $t('gestlab.general.labels.vap_non_conformities.edit_title') }]" :title="$t('gestlab.general.labels.vap_non_conformities.edit_title')">
      <template #lede>{{ $t('gestlab.general.labels.vap_non_conformities.edit_description') }} <span class="font-bold text-[rgb(var(--primary-700-rgb))] dark:text-[rgb(var(--accent-200-rgb))]">#{{ nonConformity.nc_number }}</span></template>
      <template #badges>
        <span :class="['inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset', statusChipClass]">
          <span :class="['h-2 w-2 rounded-full', statusDotClass]"></span>
          {{ $t(`gestlab.general.labels.vap_non_conformities.status.${nonConformity.status}`) }}
        </span>
      </template>
      <template #actions>
        <Link :href="route('vap_non_conformities.show', nonConformity.id)" class="ds-button ds-button-secondary">
          <EyeIcon class="h-4 w-4" />
          {{ $t('gestlab.general.labels.vap_non_conformities.buttons.view') }}
        </Link>
      </template>
    </PageHeader>

    <NonConformityObservations v-if="nonConformity.status === 'closed'" :record="nonConformity" />
    <NonConformityForm v-else
      :non-conformity="nonConformity"
      :labs="labs"
      :departments="departments"
      :is-editing="true"
    />
  </div>
</template>

<script setup>
import NonConformityForm from '@/Pages/VAPNonConformities/NonConformityForm.vue'
import PageHeader from '@/Components/plano/PageHeader.vue'
import NonConformityObservations from '@/Pages/VAPNonConformities/NonConformityObservations.vue'
import { Head, Link } from '@inertiajs/vue3'
import { Eye as EyeIcon } from '@lucide/vue'
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
