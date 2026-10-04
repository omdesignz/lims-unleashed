<template>
  <div class="pl-page space-y-6">
    <Head title="Nova não conformidade" />
    <PageHeader :trail="[{ title: 'Não conformidades', url: route('vap_non_conformities.index') }, { title: $t('gestlab.general.labels.vap_non_conformities.create_title') }]" :title="$t('gestlab.general.labels.vap_non_conformities.create_title')" :lede="$t('gestlab.general.labels.vap_non_conformities.create_description')">
      <template #badges>
        <span class="ds-chip">
          <span class="lims-status-dot lims-status-dot-hold"></span>
          {{ $t('gestlab.general.labels.vap_non_conformities.general.new') }}
        </span>
      </template>
    </PageHeader>

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
import PageHeader from '@/Components/plano/PageHeader.vue'
import { Head } from '@inertiajs/vue3'
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
